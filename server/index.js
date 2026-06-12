require("dotenv").config();

const crypto = require("node:crypto");
const fs = require("node:fs");
const path = require("node:path");

const express = require("express");
const session = require("express-session");
const FileStoreFactory = require("session-file-store");
const helmet = require("helmet");
const OpenAI = require("openai");
const passport = require("passport");
const GoogleStrategy = require("passport-google-oauth20").Strategy;

const rootDir = path.resolve(__dirname, "..");
const publicDir = path.join(rootDir, "public");
const dataDir = path.resolve(rootDir, process.env.DATA_DIR || ".data");
const port = Number(process.env.PORT || 3000);
const authBaseUrl = (process.env.AUTH_BASE_URL || `http://localhost:${port}`).replace(/\/$/, "");
const isProduction = process.env.NODE_ENV === "production";
if (isProduction && (!process.env.AUTH_BASE_URL || !authBaseUrl.startsWith("https://"))) {
  throw new Error("AUTH_BASE_URL must be an HTTPS URL in production.");
}
if (isProduction && !process.env.SESSION_SECRET) {
  throw new Error("SESSION_SECRET must be set in production.");
}
const sessionSecret = process.env.SESSION_SECRET || crypto.randomBytes(32).toString("hex");
const jsonBodyLimit = process.env.JSON_BODY_LIMIT || "5mb";
const aiRateLimitWindowMs = Number(process.env.AI_RATE_LIMIT_WINDOW_MS || 60_000);
const aiRateLimitMax = Number(process.env.AI_RATE_LIMIT_MAX || 20);
const allowedEmails = new Set(
  String(process.env.ALLOWED_EMAILS || "")
    .split(",")
    .map((email) => email.trim().toLowerCase())
    .filter(Boolean)
);
const hasGoogleAuth = Boolean(process.env.GOOGLE_CLIENT_ID && process.env.GOOGLE_CLIENT_SECRET);
if (isProduction && !hasGoogleAuth) {
  throw new Error("GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET must be set in production.");
}

fs.mkdirSync(dataDir, { recursive: true });

const databasePath = path.join(dataDir, "dnd-copilot.json");
let database = loadDatabase();
const aiRateBuckets = new Map();

function loadDatabase() {
  try {
    const parsed = JSON.parse(fs.readFileSync(databasePath, "utf8"));
    return {
      users: Array.isArray(parsed.users) ? parsed.users : [],
      userStorage: parsed.userStorage && typeof parsed.userStorage === "object" ? parsed.userStorage : {},
    };
  } catch {
    return { users: [], userStorage: {} };
  }
}

function saveDatabase() {
  const temporaryPath = `${databasePath}.tmp`;
  fs.writeFileSync(temporaryPath, JSON.stringify(database, null, 2));
  fs.renameSync(temporaryPath, databasePath);
}

function nowIso() {
  return new Date().toISOString();
}

function findUserById(id) {
  return database.users.find((user) => user.id === id) || null;
}

function findUserByGoogleId(googleId) {
  return database.users.find((user) => user.google_id === googleId) || null;
}

function upsertUser(userData) {
  const existingIndex = database.users.findIndex((user) => user.id === userData.id);
  const timestamp = nowIso();
  const nextUser = {
    ...userData,
    created_at: existingIndex >= 0 ? database.users[existingIndex].created_at : timestamp,
    updated_at: timestamp,
  };

  if (existingIndex >= 0) {
    database.users[existingIndex] = nextUser;
  } else {
    database.users.push(nextUser);
  }

  saveDatabase();
  return nextUser;
}

function getUserStorageBucket(userId) {
  database.userStorage[userId] ||= {};
  return database.userStorage[userId];
}

if (hasGoogleAuth) {
  passport.use(new GoogleStrategy(
    {
      clientID: process.env.GOOGLE_CLIENT_ID,
      clientSecret: process.env.GOOGLE_CLIENT_SECRET,
      callbackURL: `${authBaseUrl}/auth/google/callback`,
    },
    (accessToken, refreshToken, profile, done) => {
      try {
        const email = profile.emails?.[0]?.value?.toLowerCase();
        if (!email) {
          return done(null, false, { message: "Google account has no public email." });
        }
        if (allowedEmails.size && !allowedEmails.has(email)) {
          return done(null, false, { message: "Email is not allowed." });
        }

        const existingUser = findUserByGoogleId(profile.id);
        const userData = {
          id: existingUser?.id || crypto.randomUUID(),
          google_id: profile.id,
          email,
          name: profile.displayName || email,
          avatar_url: profile.photos?.[0]?.value || null,
        };

        return done(null, upsertUser(userData));
      } catch (error) {
        return done(error);
      }
    }
  ));
}

passport.serializeUser((user, done) => done(null, user.id));
passport.deserializeUser((id, done) => {
  try {
    done(null, findUserById(id) || false);
  } catch (error) {
    done(error);
  }
});

const app = express();
const FileStore = FileStoreFactory(session);

app.disable("x-powered-by");
if (process.env.TRUST_PROXY === "1" || authBaseUrl.startsWith("https://")) {
  app.set("trust proxy", 1);
}
app.use(helmet({
  contentSecurityPolicy: false,
  crossOriginEmbedderPolicy: false,
}));
app.use(express.json({ limit: jsonBodyLimit }));
app.use(session({
  store: new FileStore({
    path: path.join(dataDir, "sessions"),
    retries: 0,
  }),
  secret: sessionSecret,
  resave: false,
  saveUninitialized: false,
  cookie: {
    httpOnly: true,
    sameSite: "lax",
    secure: authBaseUrl.startsWith("https://"),
    maxAge: 1000 * 60 * 60 * 24 * 14,
  },
}));
app.use(passport.initialize());
app.use(passport.session());
app.use("/auth-assets", express.static(path.join(publicDir, "assets", "backgrounds"), {
  maxAge: isProduction ? "1d" : 0,
}));

function requireAuth(req, res, next) {
  if (req.isAuthenticated?.()) {
    return next();
  }

  if (req.path.startsWith("/api/")) {
    return res.status(401).json({ error: "Authentication required." });
  }

  return res.redirect("/login");
}

function validateStorageKey(key) {
  return typeof key === "string" && /^dnd-[a-z0-9-]{1,80}$/i.test(key);
}

function requireAiRateLimit(req, res, next) {
  const key = req.user?.id || req.ip;
  const now = Date.now();
  const bucket = aiRateBuckets.get(key);

  if (!bucket || bucket.resetAt <= now) {
    aiRateBuckets.set(key, { count: 1, resetAt: now + aiRateLimitWindowMs });
    return next();
  }

  if (bucket.count >= aiRateLimitMax) {
    return res.status(429).json({ error: "Too many AI requests. Try again later." });
  }

  bucket.count += 1;
  return next();
}

function renderLoginPage({ error = "" } = {}) {
  const authButton = hasGoogleAuth
    ? '<a class="auth-button" href="/auth/google">Войти через Google</a>'
    : '<div class="setup-warning">Google OAuth ещё не настроен. Заполни GOOGLE_CLIENT_ID и GOOGLE_CLIENT_SECRET в .env.</div>';

  return `<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>D&D Copilot - вход</title>
  <style>
    :root { color-scheme: dark; font-family: Inter, "Segoe UI", Arial, sans-serif; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      color: #fff7e8;
      background:
        linear-gradient(180deg, rgba(0,0,0,.18), rgba(0,0,0,.52)),
        url("/auth-assets/orange-theme.png") center / cover no-repeat #100719;
    }
    main {
      width: min(440px, calc(100vw - 32px));
      padding: 28px;
      border: 1px solid rgba(242,184,106,.28);
      border-radius: 8px;
      background: rgba(35,20,13,.74);
      box-shadow: 0 24px 70px rgba(0,0,0,.48);
      backdrop-filter: blur(14px);
    }
    h1 { margin: 0 0 8px; font-size: 30px; letter-spacing: 0; }
    p { margin: 0 0 22px; color: #d7bea0; line-height: 1.5; }
    .auth-button {
      display: grid;
      place-items: center;
      min-height: 46px;
      padding: 0 18px;
      color: #130b06;
      border-radius: 8px;
      background: linear-gradient(180deg, #fff0bd, #d9964a);
      font-weight: 800;
      text-decoration: none;
    }
    .setup-warning,
    .error {
      padding: 14px;
      border: 1px solid rgba(255,120,96,.46);
      border-radius: 8px;
      color: #ffe2da;
      background: rgba(172,48,38,.24);
      line-height: 1.45;
    }
    .error { margin-bottom: 14px; }
  </style>
</head>
<body>
  <main>
    <h1>D&D Copilot</h1>
    <p>Закрытый рабочий стол мастера. Войди, чтобы открыть приложение и личную библиотеку.</p>
    ${error ? `<div class="error">${error}</div>` : ""}
    ${authButton}
  </main>
</body>
</html>`;
}

app.get("/health", (req, res) => {
  res.json({ ok: true });
});

app.get("/login", (req, res) => {
  if (req.isAuthenticated?.()) {
    return res.redirect("/public/index.html");
  }
  return res.type("html").send(renderLoginPage({ error: req.query.error || "" }));
});

app.get("/auth/google", (req, res, next) => {
  if (!hasGoogleAuth) {
    return res.redirect("/login?error=Google%20OAuth%20is%20not%20configured.");
  }
  return passport.authenticate("google", { scope: ["profile", "email"] })(req, res, next);
});

app.get("/auth/google/callback",
  passport.authenticate("google", {
    failureRedirect: "/login?error=Google%20login%20failed.",
  }),
  (req, res) => {
    res.redirect("/public/index.html");
  }
);

app.post("/auth/logout", requireAuth, (req, res, next) => {
  req.logout((error) => {
    if (error) {
      return next(error);
    }
    req.session.destroy(() => {
      res.clearCookie("connect.sid");
      res.json({ ok: true });
    });
  });
});

app.get("/api/me", requireAuth, (req, res) => {
  res.json({
    id: req.user.id,
    email: req.user.email,
    name: req.user.name,
    avatarUrl: req.user.avatar_url,
  });
});

app.get("/api/storage", requireAuth, (req, res) => {
  const bucket = getUserStorageBucket(req.user.id);
  const entries = Object.entries(bucket)
    .sort(([left], [right]) => left.localeCompare(right))
    .map(([key, entry]) => ({
      key,
      value: entry.value,
      updatedAt: entry.updated_at,
    }));
  res.json({ entries });
});

app.get("/api/storage/:key", requireAuth, (req, res) => {
  if (!validateStorageKey(req.params.key)) {
    return res.status(400).json({ error: "Invalid storage key." });
  }
  const bucket = getUserStorageBucket(req.user.id);
  res.json({ key: req.params.key, value: bucket[req.params.key]?.value ?? null });
});

app.put("/api/storage/:key", requireAuth, (req, res) => {
  if (!validateStorageKey(req.params.key)) {
    return res.status(400).json({ error: "Invalid storage key." });
  }
  const bucket = getUserStorageBucket(req.user.id);
  bucket[req.params.key] = {
    value: req.body?.value ?? null,
    updated_at: nowIso(),
  };
  saveDatabase();
  res.json({ ok: true });
});

app.delete("/api/storage/:key", requireAuth, (req, res) => {
  if (!validateStorageKey(req.params.key)) {
    return res.status(400).json({ error: "Invalid storage key." });
  }
  const bucket = getUserStorageBucket(req.user.id);
  delete bucket[req.params.key];
  saveDatabase();
  res.json({ ok: true });
});

app.post("/api/ai/chat", requireAuth, requireAiRateLimit, async (req, res, next) => {
  try {
    if (!process.env.OPENAI_API_KEY) {
      return res.status(503).json({ error: "OPENAI_API_KEY is not configured." });
    }

    const input = req.body?.input || req.body?.message;
    if (!input || typeof input !== "string") {
      return res.status(400).json({ error: "Expected a text input." });
    }

    const client = new OpenAI({ apiKey: process.env.OPENAI_API_KEY });
    const response = await client.responses.create({
      model: process.env.OPENAI_MODEL || "gpt-5.5",
      instructions: "Ты помощник мастера D&D. Отвечай по-русски, кратко и практично.",
      input,
    });

    res.json({ text: response.output_text });
  } catch (error) {
    next(error);
  }
});

app.get("/", requireAuth, (req, res) => {
  res.redirect("/public/index.html");
});

app.use("/public", requireAuth, express.static(publicDir, {
  index: "index.html",
  extensions: ["html"],
}));

app.use((req, res) => {
  res.status(404).type("html").send("Страница не найдена.");
});

app.use((error, req, res, next) => {
  console.error(error);
  if (res.headersSent) {
    return next(error);
  }
  res.status(500).json({ error: "Internal server error." });
});

app.listen(port, () => {
  console.log(`D&D Copilot server: ${authBaseUrl}`);
});
