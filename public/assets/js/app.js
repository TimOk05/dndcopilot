const themeConfig = {
  orange: {
    title: "Теплая",
    music: "sound/bg music orange.mp3",
  },
  mystic: {
    title: "Мистика",
    music: "sound/bg music mystic.mp3",
  },
  cold: {
    title: "Лед",
    music: "sound/bg music ice.mp3",
  },
  dark: {
    title: "Тьма",
    music: "sound/bg music dark.mp3",
  },
};

const panelTitles = {
  dashboard: "Главная",
  generators: "Генераторы",
  bestiary: "Бестиарий",
  spells: "Заклинания",
  loot: "Предметы",
  library: "Библиотека",
};

const memoryStorage = new Map();
const storage = {
  get(key, fallback = null) {
    try {
      return globalThis.localStorage?.getItem(key) ?? memoryStorage.get(key) ?? fallback;
    } catch {
      return memoryStorage.get(key) ?? fallback;
    }
  },
  set(key, value) {
    memoryStorage.set(key, value);
    try {
      globalThis.localStorage?.setItem(key, value);
    } catch {
      // Some embedded browser contexts can hide persistent storage.
    }
  },
};

const state = {
  theme: storage.get("dnd-theme", "orange"),
  soundEnabled: storage.get("dnd-sound") === "on",
  volume: Number(storage.get("dnd-volume", "45")),
};

const root = document.documentElement;
const music = document.querySelector("[data-music]");
const clickSound = document.querySelector("[data-click-sound]");
const soundToggle = document.querySelector("[data-sound-toggle]");
const volumeControl = document.querySelector("[data-volume]");
const audioGate = document.querySelector("[data-audio-gate]");
const panelTitle = document.querySelector("[data-panel-title]");
const diceModal = document.querySelector("[data-dice-modal]");
const diceSetup = document.querySelector("[data-dice-setup]");
const diceStage = document.querySelector("[data-dice-stage]");
const diceOrbit = document.querySelector("[data-dice-orbit]");
const diceFace = document.querySelector("[data-dice-face]");
const diceResult = document.querySelector("[data-dice-result]");
const diceActions = document.querySelector("[data-dice-actions]");
const diceSidesInput = document.querySelector("[data-dice-sides]");
const diceCountInput = document.querySelector("[data-dice-count]");
const diceLabelInput = document.querySelector("[data-dice-label]");
const diceFormula = document.querySelector("[data-dice-formula]");
const diceTotal = document.querySelector("[data-dice-total]");
const diceBreakdown = document.querySelector("[data-dice-breakdown]");
const notesTotal = document.querySelector(".status-block:nth-child(2) strong");
const libraryPanel = document.querySelector('[data-panel-view="library"]');
const libraryFilters = document.querySelectorAll("[data-library-filter]");
const bestiarySearch = document.querySelector("[data-bestiary-search]");
const bestiaryType = document.querySelector("[data-bestiary-type]");
const bestiaryCr = document.querySelector("[data-bestiary-cr]");
const bestiarySummary = document.querySelector("[data-bestiary-summary]");
const bestiaryList = document.querySelector("[data-bestiary-list]");
const monsterModal = document.querySelector("[data-monster-modal]");
const monsterName = document.querySelector("[data-monster-name]");
const monsterDetail = document.querySelector("[data-monster-detail]");
const deleteCustomMonsterButton = document.querySelector("[data-delete-custom-monster]");
const spellsSearch = document.querySelector("[data-spells-search]");
const spellsLevel = document.querySelector("[data-spells-level]");
const spellsSchool = document.querySelector("[data-spells-school]");
const spellsClass = document.querySelector("[data-spells-class]");
const spellsTag = document.querySelector("[data-spells-tag]");
const spellsSummary = document.querySelector("[data-spells-summary]");
const spellsList = document.querySelector("[data-spells-list]");
const spellModal = document.querySelector("[data-spell-modal]");
const spellName = document.querySelector("[data-spell-name]");
const spellDetail = document.querySelector("[data-spell-detail]");
const lootSearch = document.querySelector("[data-loot-search]");
const lootRarityInput = document.querySelector("[data-loot-rarity]");
const lootListCategoryInput = document.querySelector("[data-loot-list-category]");
const lootSummary = document.querySelector("[data-loot-summary]");
const lootList = document.querySelector("[data-loot-list]");
const randomMonsterModal = document.querySelector("[data-random-monster-modal]");
const randomCrInput = document.querySelector("[data-random-cr]");
const randomKindInput = document.querySelector("[data-random-kind]");
const randomCountInput = document.querySelector("[data-random-count]");
const randomSameKindInput = document.querySelector("[data-random-same-kind]");
const randomMonsterResults = document.querySelector("[data-random-monster-results]");
const saveRandomMonstersButton = document.querySelector("[data-save-random-monsters]");
const createMonsterModal = document.querySelector("[data-create-monster-modal]");
const createMonsterForm = document.querySelector("[data-create-monster-form]");
const customTypeInput = document.querySelector("[data-custom-type]");
const createLootItemModal = document.querySelector("[data-create-loot-item-modal]");
const createLootItemForm = document.querySelector("[data-create-loot-item-form]");
const customLootCategoryInput = document.querySelector("[data-custom-loot-category]");
const customLootRarityInput = document.querySelector("[data-custom-loot-rarity]");
const potionModal = document.querySelector("[data-potion-modal]");
const potionRarityInput = document.querySelector("[data-potion-rarity]");
const potionKindInput = document.querySelector("[data-potion-kind]");
const potionCountInput = document.querySelector("[data-potion-count]");
const potionSameKindInput = document.querySelector("[data-potion-same-kind]");
const potionResults = document.querySelector("[data-potion-results]");
const savePotionsButton = document.querySelector("[data-save-potions]");
const potionDetailModal = document.querySelector("[data-potion-detail-modal]");
const potionDetailName = document.querySelector("[data-potion-detail-name]");
const potionDetail = document.querySelector("[data-potion-detail]");
const lootModal = document.querySelector("[data-loot-modal]");
const lootTierInput = document.querySelector("[data-loot-tier]");
const lootCategoryInput = document.querySelector("[data-loot-category]");
const lootCountInput = document.querySelector("[data-loot-count]");
const lootResults = document.querySelector("[data-loot-results]");
const saveLootButton = document.querySelector("[data-save-loot]");
const randomLootItemModal = document.querySelector("[data-random-loot-item-modal]");
const randomLootRarityInput = document.querySelector("[data-random-loot-rarity]");
const randomLootCategoryInput = document.querySelector("[data-random-loot-category]");
const randomLootCountInput = document.querySelector("[data-random-loot-count]");
const randomLootResults = document.querySelector("[data-random-loot-results]");
const saveRandomLootButton = document.querySelector("[data-save-random-loot-item]");
const lootDetailModal = document.querySelector("[data-loot-detail-modal]");
const lootDetailName = document.querySelector("[data-loot-detail-name]");
const lootDetail = document.querySelector("[data-loot-detail]");
const saveCurrentLootItemButton = document.querySelector("[data-save-current-loot-item]");
const deleteCustomLootItemButton = document.querySelector("[data-delete-custom-loot-item]");
const noteModal = document.querySelector("[data-note-modal]");
const noteForm = document.querySelector("[data-note-form]");
const noteModalTitle = document.querySelector("[data-note-modal-title]");
const noteIdInput = document.querySelector("[data-note-id]");
const noteTitleInput = document.querySelector("[data-note-title]");
const noteBodyInput = document.querySelector("[data-note-body]");
const deleteNoteButton = document.querySelector("[data-delete-note]");

let lastDiceRoll = null;
let bestiaryIndex = [];
let bestiaryMonsters = [];
let bestiaryById = new Map();
let bestiaryLocaleById = new Map();
let currentMonster = null;
let spellsIndex = [];
let spells = [];
let spellsById = new Map();
let currentSpell = null;
let currentRandomMonsters = [];
let bestiaryScope = "all";
let potionsIndex = [];
let potions = [];
let potionsById = new Map();
let potionsLocaleById = new Map();
let currentPotionResults = [];
let lootTables = null;
let lootItemsIndex = [];
let lootItems = [];
let lootItemsById = new Map();
let lootLocaleById = new Map();
let currentLootResults = [];
let currentLootItem = null;
let lootGeneratorMode = "reward";
let lootScope = "all";
let libraryFilter = "all";
const LIBRARY_SECTION_TITLES = {
  characters: "Мои персонажи",
  rolls: "Мои броски",
  creatures: "Мои существа",
  rewards: "Мои награды",
  taverns: "Мои таверны",
  events: "Мои события",
  potions: "Мои зелья",
  spells: "Мои заклинания",
  items: "Мои предметы",
  notes: "Мои заметки",
};
const DICE_LABEL_LIMIT = 48;
const CR_GROUPS = [
  { value: "0-0.125", label: "CR 0-1/8", min: 0, max: 0.125 },
  { value: "0.25-0.5", label: "CR 1/4-1/2", min: 0.25, max: 0.5 },
  { value: "1-2", label: "CR 1-2", min: 1, max: 2 },
  { value: "3-4", label: "CR 3-4", min: 3, max: 4 },
];
const TYPE_LABELS_RU = {
  aberration: "аберрация",
  beast: "зверь",
  celestial: "небожитель",
  construct: "конструкт",
  dragon: "дракон",
  elemental: "элементаль",
  fey: "фея",
  fiend: "исчадие",
  giant: "великан",
  humanoid: "гуманоид",
  monstrosity: "монстр",
  ooze: "слизь",
  plant: "растение",
  undead: "нежить",
};
const TYPE_ALIASES = {
  "or small humanoid": "humanoid",
  "or small monstrosity": "monstrosity",
  "or small undead": "undead",
  "swarm of tiny beasts": "beast",
  "swarm of tiny undead": "undead",
};
const SCHOOL_LABELS_RU = {
  abjuration: "ограждение",
  conjuration: "вызов",
  divination: "прорицание",
  enchantment: "очарование",
  evocation: "воплощение",
  illusion: "иллюзия",
  necromancy: "некромантия",
  transmutation: "преобразование",
};
const CLASS_LABELS_RU = {
  artificer: "изобретатель",
  bard: "бард",
  cleric: "жрец",
  druid: "друид",
  paladin: "паладин",
  ranger: "следопыт",
  sorcerer: "чародей",
  warlock: "колдун",
  wizard: "волшебник",
};
const RARITY_LABELS_RU = {
  common: "обычная",
  uncommon: "необычная",
  rare: "редкая",
  "very-rare": "очень редкая",
  legendary: "легендарная",
  "rarity-varies": "редкость варьируется",
  artifact: "артефакт",
  unknown: "неизвестно",
};
const POTION_RARITY_ORDER = ["common", "uncommon", "rare", "very-rare", "legendary"];
const MAGIC_RARITY_ORDER = ["common", "uncommon", "rare", "very-rare", "legendary", "artifact"];
const MAGIC_CATEGORY_LABELS_RU = {
  ammunition: "боеприпасы",
  armor: "доспех",
  potion: "зелье",
  ring: "кольцо",
  "rod-staff": "посохи и жезлы",
  rod: "жезл",
  scroll: "свиток",
  shield: "щит",
  staff: "посох",
  wand: "волшебная палочка",
  weapon: "оружие",
  "wondrous-item": "чудесный предмет",
};
const COIN_LABELS_RU = {
  cp: "мм",
  sp: "см",
  ep: "эм",
  gp: "зм",
  pp: "пм",
};

function cleanDiceLabel(value) {
  return value.trim().replace(/\s+/g, " ").slice(0, DICE_LABEL_LIMIT);
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function setVolume(value) {
  state.volume = value;
  const normalized = value / 100;
  music.volume = normalized;
  clickSound.volume = Math.min(normalized + 0.18, 1);
  storage.set("dnd-volume", String(value));
}

function setTheme(theme) {
  state.theme = themeConfig[theme] ? theme : "orange";
  root.dataset.theme = state.theme;
  storage.set("dnd-theme", state.theme);

  document.querySelectorAll("[data-theme-choice]").forEach((button) => {
    button.classList.toggle("active", button.dataset.themeChoice === state.theme);
  });

  const nextMusic = themeConfig[state.theme].music;
  if (!music.src.endsWith(encodeURI(nextMusic))) {
    music.src = nextMusic;
  }

  if (state.soundEnabled) {
    music.play().catch(showAudioGate);
  }
}

function showAudioGate() {
  audioGate.classList.remove("is-hidden");
}

function hideAudioGate() {
  audioGate.classList.add("is-hidden");
}

function updateSoundButton() {
  soundToggle.classList.toggle("is-on", state.soundEnabled);
  soundToggle.textContent = state.soundEnabled ? "Музыка включена" : "Включить музыку";
}

async function enableSound() {
  state.soundEnabled = true;
  storage.set("dnd-sound", "on");
  updateSoundButton();
  hideAudioGate();
  await music.play().catch(showAudioGate);
}

function disableSound() {
  state.soundEnabled = false;
  storage.set("dnd-sound", "off");
  music.pause();
  updateSoundButton();
}

function playClick() {
  if (!state.soundEnabled) {
    return;
  }
  clickSound.currentTime = 0;
  clickSound.play().catch(() => {});
}

function openPanel(panel) {
  document.querySelectorAll("[data-panel-view]").forEach((view) => {
    view.classList.toggle("active", view.dataset.panelView === panel);
  });

  document.querySelectorAll("[data-panel]").forEach((button) => {
    button.classList.toggle("active", button.dataset.panel === panel);
  });

  panelTitle.textContent = panelTitles[panel] || panelTitles.dashboard;

  if (panel === "bestiary") {
    loadBestiary();
  }
  if (panel === "spells") {
    loadSpells();
  }
  if (panel === "loot") {
    loadLoot();
  }
  if (panel === "library") {
    applyLibraryFilter();
  }
}

function capitalize(value) {
  const text = String(value || "").trim();
  return text ? text.charAt(0).toUpperCase() + text.slice(1) : "-";
}

function signed(value) {
  if (value === undefined || value === null || value === "") {
    return "-";
  }
  return Number(value) >= 0 ? `+${value}` : String(value);
}

function formatSpeed(speed) {
  if (!speed) {
    return "-";
  }
  const labels = {
    walk: "ходьба",
    fly: "полёт",
    swim: "плавание",
    burrow: "копание",
    climb: "лазание",
  };
  return Object.entries(labels)
    .filter(([key]) => speed[key])
    .map(([key, label]) => `${label} ${speed[key]} фт.`)
    .join(", ") || "-";
}

function formatSenses(senses) {
  if (!senses) {
    return "-";
  }
  const rows = [];
  if (senses.darkvision) rows.push(`тёмное зрение ${senses.darkvision} фт.`);
  if (senses.blindsight) rows.push(`слепое зрение ${senses.blindsight} фт.`);
  if (senses.truesight) rows.push(`истинное зрение ${senses.truesight} фт.`);
  if (senses.tremorsense) rows.push(`чувство вибрации ${senses.tremorsense} фт.`);
  if (senses.passivePerception) rows.push(`пассивная Внимательность ${senses.passivePerception}`);
  return rows.join(", ") || "-";
}

function listText(values) {
  return values?.length ? values.join(", ") : "-";
}

function splitLines(value) {
  return String(value || "")
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean);
}

function abilityModifier(score) {
  return Math.floor((Number(score) - 10) / 2);
}

function parseCrValue(value) {
  const text = String(value ?? "0").trim();
  if (text.includes("/")) {
    const [top, bottom] = text.split("/").map(Number);
    return bottom ? top / bottom : 0;
  }
  return Number(text) || 0;
}

function normalizeCreatureType(value) {
  const clean = String(value || "").trim().toLowerCase();
  return TYPE_ALIASES[clean] || clean;
}

function getTypeLabelRu(type) {
  const clean = normalizeCreatureType(type);
  return TYPE_LABELS_RU[clean] || capitalize(clean);
}

function getSchoolLabel(schoolKey, fallback = "") {
  return SCHOOL_LABELS_RU[schoolKey] || fallback || "неизвестная школа";
}

function getClassLabel(classKey, fallback = "") {
  return CLASS_LABELS_RU[classKey] || fallback || classKey || "-";
}

function getSpellLevelLabel(level) {
  const value = Number(level);
  return value === 0 ? "заговор" : `${value} уровень`;
}

function getSpellComponents(spell) {
  const components = spell.components || {};
  const rows = [];
  if (components.verbal) rows.push("В");
  if (components.somatic) rows.push("С");
  if (components.material) rows.push("М");
  const base = rows.join(", ") || "-";
  return components.material_specified ? `${base} (${components.material_specified})` : base;
}

function getSpellClasses(spell) {
  return (spell.classes || []).map((item) => getClassLabel(item.key, item.name)).join(", ") || "-";
}

function getSpellName(spell) {
  return spell.name_ru || spell.name;
}

function getSpellDescription(spell) {
  return normalizeRichText(spell.description_ru || spell.description);
}

function getSpellHigherLevel(spell) {
  return normalizeRichText(spell.higher_level_ru || spell.higher_level);
}

function getRarityLabel(rarityValue, rarity = "") {
  return RARITY_LABELS_RU[rarityValue] || rarity || "неизвестно";
}

function renderTextList(title, values) {
  if (!values?.length) {
    return "";
  }
  return `
    <section class="monster-section">
      <h4>${title}</h4>
      <div class="monster-text-list">
        ${values.map((value) => `<p>${escapeHtml(value)}</p>`).join("")}
      </div>
    </section>
  `;
}

function normalizeRichText(value) {
  return String(value || "")
    .replace(/\\r\\n|\\n|\\r/g, "\n")
    .replace(/\r\n?/g, "\n")
    .trim();
}

function renderInlineMarkdown(value) {
  return escapeHtml(value)
    .replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>")
    .replace(/(^|[^*])\*(?!\s)(.+?)(?<!\s)\*/g, "$1<em>$2</em>")
    .replace(/_(?!\s)(.+?)(?<!\s)_/g, "<em>$1</em>");
}

function isMarkdownTableLine(line) {
  const clean = line.trim();
  return clean.startsWith("|") && clean.endsWith("|") && clean.slice(1, -1).includes("|");
}

function isMarkdownTableSeparator(cells) {
  return cells.every((cell) => /^:?-{3,}:?$/.test(cell.trim()));
}

function parseMarkdownTable(lines) {
  const rows = lines.map((line) => line.trim().slice(1, -1).split("|").map((cell) => cell.trim()));
  const hasHeader = rows.length > 1 && isMarkdownTableSeparator(rows[1]);
  const header = hasHeader ? rows[0] : [];
  const body = hasHeader ? rows.slice(2) : rows;

  return `
    <div class="potion-table-wrap">
      <table class="potion-table">
        ${header.length ? `<thead><tr>${header.map((cell) => `<th>${renderInlineMarkdown(cell)}</th>`).join("")}</tr></thead>` : ""}
        <tbody>
          ${body.map((row) => `<tr>${row.map((cell) => `<td>${renderInlineMarkdown(cell)}</td>`).join("")}</tr>`).join("")}
        </tbody>
      </table>
    </div>
  `;
}

function renderRichDescription(value) {
  const lines = normalizeRichText(value).split("\n");
  const blocks = [];
  let paragraph = [];
  let table = [];

  const flushParagraph = () => {
    if (!paragraph.length) {
      return;
    }
    const text = paragraph.join(" ").trim();
    if (/^\*\*[^*]+\*\*$/.test(text)) {
      blocks.push(`<h5>${renderInlineMarkdown(text.replace(/^\*\*|\*\*$/g, ""))}</h5>`);
    } else {
      blocks.push(`<p>${renderInlineMarkdown(text)}</p>`);
    }
    paragraph = [];
  };

  const flushTable = () => {
    if (!table.length) {
      return;
    }
    blocks.push(parseMarkdownTable(table));
    table = [];
  };

  lines.forEach((line) => {
    if (isMarkdownTableLine(line)) {
      flushParagraph();
      table.push(line);
      return;
    }

    flushTable();
    if (!line.trim()) {
      flushParagraph();
      return;
    }
    paragraph.push(line.trim());
  });

  flushParagraph();
  flushTable();
  return blocks.join("");
}

function getPlainTextExcerpt(value) {
  return normalizeRichText(value)
    .split(/\n{2,}/)[0]
    .replace(/\|/g, " ")
    .replace(/\*\*?|_/g, "")
    .replace(/\s+/g, " ")
    .trim();
}

function getCustomMonsters() {
  try {
    return JSON.parse(storage.get("dnd-custom-monsters", "[]"));
  } catch {
    return [];
  }
}

function setCustomMonsters(monsters) {
  storage.set("dnd-custom-monsters", JSON.stringify(monsters));
}

function getCustomLootItems() {
  try {
    return JSON.parse(storage.get("dnd-custom-loot-items", "[]"));
  } catch {
    return [];
  }
}

function setCustomLootItems(items) {
  storage.set("dnd-custom-loot-items", JSON.stringify(items));
}

function toMonsterIndexRow(monster) {
  const type = normalizeCreatureType(monster.type);
  return {
    id: monster.id,
    name: monster.name,
    name_ru: monster.name_ru || monster.name,
    type,
    type_ru: monster.type_ru || getTypeLabelRu(type),
    size: monster.size,
    size_ru: monster.size_ru || capitalize(monster.size),
    cr: monster.cr,
    cr_value: monster.cr_value,
    hp_average: monster.hit_points?.average ?? monster.hp_average ?? 1,
    hp_formula: monster.hit_points?.formula ?? monster.hp_formula ?? String(monster.hit_points?.average ?? 1),
    is_custom: Boolean(monster.is_custom),
  };
}

function getAvailableCreatureTypes() {
  return [...new Set(bestiaryIndex.map((monster) => normalizeCreatureType(monster.type)).filter(Boolean))]
    .sort((left, right) => getTypeLabel(left).localeCompare(getTypeLabel(right), "ru"));
}

async function loadBestiary() {
  if (bestiaryIndex.length || !bestiaryList) {
    return;
  }

  bestiarySummary.textContent = "Загрузка бестиария...";

  try {
    const [indexResponse, monstersResponse, localeResponse, textLocaleResponse] = await Promise.all([
      fetch("data/srd/monsters.index.json"),
      fetch("data/srd/monsters.json"),
      fetch("data/i18n/ru/monsters.index.json").catch(() => null),
      fetch("data/i18n/ru/monsters.text.json").catch(() => null),
    ]);

    if (!indexResponse.ok || !monstersResponse.ok) {
      throw new Error("Не удалось загрузить файлы бестиария");
    }

    bestiaryIndex = (await indexResponse.json()).map((monster) => ({
      ...monster,
      type: normalizeCreatureType(monster.type),
    }));
    bestiaryMonsters = (await monstersResponse.json()).map((monster) => ({
      ...monster,
      type: normalizeCreatureType(monster.type),
    }));
    const customMonsters = getCustomMonsters();
    bestiaryIndex = [...bestiaryIndex, ...customMonsters.map(toMonsterIndexRow)];
    bestiaryMonsters = [...bestiaryMonsters, ...customMonsters];
    bestiaryById = new Map(bestiaryMonsters.map((monster) => [monster.id, monster]));

    const localeRows = localeResponse?.ok ? await localeResponse.json() : [];
    const textLocaleRows = textLocaleResponse?.ok ? await textLocaleResponse.json() : [];
    bestiaryLocaleById = new Map();

    [...localeRows, ...textLocaleRows].forEach((row) => {
      bestiaryLocaleById.set(row.id, {
        ...(bestiaryLocaleById.get(row.id) || {}),
        ...row,
      });
    });

    if (bestiaryLocaleById.size) {
      bestiaryIndex = bestiaryIndex.map((monster) => ({
        ...monster,
        ...(bestiaryLocaleById.get(monster.id) || {}),
      }));
    }

    setupBestiaryFilters();
    setupRandomCrOptions();
    setupRandomKindOptions();
    setupCustomTypeOptions();
    renderBestiary();
  } catch (error) {
    bestiarySummary.textContent = "Не удалось загрузить бестиарий.";
    bestiaryList.innerHTML = `<div class="library-empty"><strong>Ошибка загрузки</strong><span>${escapeHtml(error.message)}</span></div>`;
  }
}

async function loadSpells() {
  if (spellsIndex.length || !spellsList) {
    return;
  }

  spellsSummary.textContent = "Загрузка заклинаний...";

  try {
    const [indexResponse, spellsResponse] = await Promise.all([
      fetch("data/srd/spells.index.json"),
      fetch("data/srd/spells.json"),
    ]);

    if (!indexResponse.ok || !spellsResponse.ok) {
      throw new Error("Не удалось загрузить базу заклинаний");
    }

    spellsIndex = await indexResponse.json();
    spells = await spellsResponse.json();
    spellsById = new Map(spells.map((spell) => [spell.id, spell]));

    setupSpellFilters();
    renderSpells();
  } catch (error) {
    spellsSummary.textContent = "Не удалось загрузить заклинания.";
    spellsList.innerHTML = `<div class="library-empty"><strong>Ошибка загрузки</strong><span>${escapeHtml(error.message)}</span></div>`;
  }
}

function setupSpellFilters() {
  const levels = [...new Set(spellsIndex.map((spell) => spell.level))]
    .filter((level) => level !== undefined && level !== null)
    .sort((left, right) => Number(left) - Number(right));
  const schools = [...new Set(spellsIndex.map((spell) => spell.school_key).filter(Boolean))]
    .sort((left, right) => getSchoolLabel(left).localeCompare(getSchoolLabel(right), "ru"));
  const classes = [...new Set(spellsIndex.flatMap((spell) => spell.classes || []).map((item) => item.key).filter(Boolean))]
    .sort((left, right) => getClassLabel(left).localeCompare(getClassLabel(right), "ru"));

  spellsLevel.innerHTML = `
    <option value="">Любой</option>
    ${levels.map((level) => `<option value="${escapeHtml(level)}">${escapeHtml(getSpellLevelLabel(level))}</option>`).join("")}
  `;
  spellsSchool.innerHTML = `
    <option value="">Все школы</option>
    ${schools.map((school) => `<option value="${escapeHtml(school)}">${escapeHtml(getSchoolLabel(school))}</option>`).join("")}
  `;
  spellsClass.innerHTML = `
    <option value="">Любой класс</option>
    ${classes.map((classKey) => `<option value="${escapeHtml(classKey)}">${escapeHtml(getClassLabel(classKey))}</option>`).join("")}
  `;
}

function getFilteredSpells() {
  const query = spellsSearch.value.trim().toLowerCase();
  const level = spellsLevel.value;
  const school = spellsSchool.value;
  const classKey = spellsClass.value;
  const tag = spellsTag.value;

  return spellsIndex.filter((spell) => {
    const matchesQuery = !query || spell.name.toLowerCase().includes(query) || (spell.name_ru || "").toLowerCase().includes(query);
    const matchesLevel = !level || String(spell.level) === level;
    const matchesSchool = !school || spell.school_key === school;
    const matchesClass = !classKey || (spell.classes || []).some((item) => item.key === classKey);
    const matchesTag = !tag || (tag === "concentration" && spell.concentration) || (tag === "ritual" && spell.ritual);
    return matchesQuery && matchesLevel && matchesSchool && matchesClass && matchesTag;
  });
}

function renderSpells() {
  const filtered = getFilteredSpells();
  spellsSummary.textContent = `Показано ${filtered.length} из ${spellsIndex.length} заклинаний`;

  if (!filtered.length) {
    spellsList.innerHTML = `<div class="library-empty"><strong>Ничего не найдено</strong><span>Попробуй изменить поиск или фильтры.</span></div>`;
    return;
  }

  spellsList.innerHTML = filtered
    .map((spell) => {
      const classes = (spell.classes || []).map((item) => getClassLabel(item.key, item.name)).join(", ") || "классы не указаны";
      const tags = [spell.concentration ? "концентрация" : "", spell.ritual ? "ритуал" : ""].filter(Boolean).join(" · ");
      return `
        <button class="monster-card spell-card" type="button" data-spell-id="${escapeHtml(spell.id)}">
          <span>${escapeHtml(getSpellLevelLabel(spell.level))} · ${escapeHtml(getSchoolLabel(spell.school_key, spell.school))}</span>
          <strong>${escapeHtml(spell.name_ru || spell.name)}</strong>
          <em>${escapeHtml(spell.name)}</em>
          <small>${escapeHtml(classes)}${tags ? ` · ${escapeHtml(tags)}` : ""}</small>
        </button>
      `;
    })
    .join("");
}

function openSpellModal(id) {
  const spell = spellsById.get(id);
  if (!spell) {
    return;
  }

  currentSpell = spell;
  spellName.textContent = getSpellName(spell);
  spellDetail.innerHTML = renderSpellDetail(spell);
  spellModal.classList.remove("is-hidden");
}

function closeSpellModal() {
  spellModal.classList.add("is-hidden");
  currentSpell = null;
}

function renderSpellDetail(spell) {
  const flags = [spell.concentration ? "концентрация" : "", spell.ritual ? "ритуал" : ""].filter(Boolean).join(", ") || "-";
  const damage = spell.damage_roll
    ? `${spell.damage_roll}${spell.damage_types?.length ? ` (${spell.damage_types.join(", ")})` : ""}`
    : "-";
  const saveOrAttack = [
    spell.saving_throw_ability ? `спасбросок: ${spell.saving_throw_ability}` : "",
    spell.attack_roll ? "бросок атаки" : "",
  ].filter(Boolean).join(", ") || "-";
  const higherLevel = getSpellHigherLevel(spell);

  return `
    <div class="monster-meta-grid">
      <div><span>Английское название</span><strong>${escapeHtml(spell.name)}</strong></div>
      <div><span>Уровень</span><strong>${escapeHtml(getSpellLevelLabel(spell.level))}</strong></div>
      <div><span>Школа</span><strong>${escapeHtml(getSchoolLabel(spell.school_key, spell.school))}</strong></div>
      <div><span>Время сотворения</span><strong>${escapeHtml(spell.casting_time || "-")}</strong></div>
      <div><span>Дистанция</span><strong>${escapeHtml(spell.range_text || "-")}</strong></div>
      <div><span>Длительность</span><strong>${escapeHtml(spell.duration || "-")}</strong></div>
      <div><span>Компоненты</span><strong>${escapeHtml(getSpellComponents(spell))}</strong></div>
      <div><span>Классы</span><strong>${escapeHtml(getSpellClasses(spell))}</strong></div>
      <div><span>Особенности</span><strong>${escapeHtml(flags)}</strong></div>
      <div><span>Урон</span><strong>${escapeHtml(damage)}</strong></div>
      <div><span>Проверка</span><strong>${escapeHtml(saveOrAttack)}</strong></div>
      <div><span>Источник</span><strong>${escapeHtml(spell.source_display || spell.source || "Open5e")}</strong></div>
    </div>

    <section class="monster-section">
      <h4>Описание</h4>
      <div class="monster-text-list">
        ${renderRichDescription(getSpellDescription(spell) || "Описание отсутствует.")}
      </div>
    </section>

    ${higherLevel ? `
      <section class="monster-section">
        <h4>На высоких уровнях</h4>
        <div class="monster-text-list">
          ${renderRichDescription(higherLevel)}
        </div>
      </section>
    ` : ""}

    <div class="license-note">
      Данные: Open5e API v2. Источник записи: ${escapeHtml(spell.source_display || spell.source || "Open5e")}.
    </div>
  `;
}

function toSavedSpell(spell) {
  return {
    id: crypto.randomUUID(),
    source_id: spell.id,
    name: getSpellName(spell),
    original_name: spell.name,
    level: spell.level,
    level_label: getSpellLevelLabel(spell.level),
    school: getSchoolLabel(spell.school_key, spell.school),
    classes: getSpellClasses(spell),
    summary: getPlainTextExcerpt(getSpellDescription(spell)),
    source: spell.source_display || spell.source || "Open5e",
    savedAt: new Date().toISOString(),
  };
}

function saveCurrentSpell() {
  if (!currentSpell) {
    return;
  }

  const savedSpells = getSavedSpells();
  savedSpells.unshift(toSavedSpell(currentSpell));
  setSavedSpells(savedSpells.slice(0, 200));
  closeSpellModal();
  openPanel("library");
}

function deleteSavedSpell(id) {
  setSavedSpells(getSavedSpells().filter((spell) => spell.id !== id));
}

async function openSavedSpell(id) {
  await loadSpells();
  const entry = getSavedSpells().find((spell) => spell.id === id);
  if (!entry) {
    return;
  }

  const source = spellsById.get(entry.source_id);
  const spell = source || {
    id: entry.source_id || entry.id,
    name: entry.original_name || entry.name,
    name_ru: entry.name,
    level: entry.level,
    school: entry.school,
    school_key: "",
    classes: [],
    casting_time: "",
    range_text: "",
    duration: "",
    components: {},
    concentration: false,
    ritual: false,
    description: entry.summary || "",
    higher_level: "",
    damage_types: [],
    source: entry.source,
    source_display: entry.source,
  };

  currentSpell = spell;
  spellName.textContent = getSpellName(spell);
  spellDetail.innerHTML = renderSpellDetail(spell);
  spellModal.classList.remove("is-hidden");
}

async function loadPotions() {
  if (potions.length) {
    setupPotionRarityOptions();
    return;
  }

  const [indexResponse, potionsResponse, localeResponse] = await Promise.all([
    fetch("data/srd/potions.index.json"),
    fetch("data/srd/potions.json"),
    fetch("data/i18n/ru/potions.json").catch(() => null),
  ]);

  if (!indexResponse.ok || !potionsResponse.ok) {
    throw new Error("Не удалось загрузить базу зелий.");
  }

  potionsIndex = await indexResponse.json();
  potions = await potionsResponse.json();
  potionsById = new Map(potions.map((potion) => [potion.id, potion]));

  if (localeResponse?.ok) {
    const localeRows = await localeResponse.json();
    potionsLocaleById = new Map(localeRows.map((row) => [row.id, row]));
  }

  setupPotionRarityOptions();
}

async function loadLoot() {
  if (lootTables && lootItems.length) {
    return;
  }

  const [tablesResponse, indexResponse, itemsResponse, localeResponse] = await Promise.all([
    fetch("data/srd/loot-tables.json"),
    fetch("data/srd/loot-items.index.json"),
    fetch("data/srd/loot-items.json"),
    fetch("data/i18n/ru/loot-items.json").catch(() => null),
  ]);

  if (!tablesResponse.ok || !indexResponse.ok || !itemsResponse.ok) {
    throw new Error("Не удалось загрузить базу лута.");
  }

  lootTables = await tablesResponse.json();
  lootItemsIndex = await indexResponse.json();
  lootItems = await itemsResponse.json();
  if (localeResponse?.ok) {
    const localeRows = await localeResponse.json();
    lootLocaleById = new Map(localeRows.map((row) => [row.id, row]));
  }
  const customLootItems = getCustomLootItems();
  lootItems = [...lootItems, ...customLootItems];
  lootItemsIndex = [...lootItemsIndex, ...customLootItems.map(toLootIndexRow)];
  lootItemsById = new Map(lootItems.map((item) => [item.id, item]));

  setupLootTierOptions();
  setupLootCategoryOptions();
  setupLootListFilters();
  renderLootList();
}

function setupLootTierOptions() {
  if (!lootTierInput || !lootTables?.tiers?.length) {
    return;
  }

  lootTierInput.innerHTML = lootTables.tiers
    .map((tier) => `<option value="${escapeHtml(tier.id)}">${escapeHtml(tier.label)}</option>`)
    .join("");
}

function setupLootCategoryOptions() {
  if (!lootCategoryInput && !randomLootCategoryInput) {
    return;
  }

  const categories = [...new Set(lootItemsIndex.map((item) => getLootDisplayCategoryKey(item.category_key)).filter(Boolean))]
    .sort((left, right) => getMagicCategoryLabel(left).localeCompare(getMagicCategoryLabel(right), "ru"));

  const options = `
    <option value="">Любая</option>
    ${categories.map((category) => `<option value="${escapeHtml(category)}">${escapeHtml(getMagicCategoryLabel(category))}</option>`).join("")}
  `;
  if (lootCategoryInput) {
    lootCategoryInput.innerHTML = options;
  }
  if (randomLootCategoryInput) {
    randomLootCategoryInput.innerHTML = options;
  }
}

function toLootIndexRow(item) {
  return {
    id: item.id,
    name: item.name,
    category: item.category,
    category_key: item.category_key,
    rarity: item.rarity,
    rarity_value: item.rarity_value,
    rarity_rank: item.rarity_rank,
    attunement: Boolean(item.attunement),
    source: item.source,
    source_key: item.source_key,
    publisher: item.publisher,
    is_custom: Boolean(item.is_custom),
  };
}

function setupLootListFilters() {
  if (!lootListCategoryInput || !lootRarityInput) {
    return;
  }

  const categories = [...new Set(lootItemsIndex.map((item) => getLootDisplayCategoryKey(item.category_key)).filter(Boolean))]
    .sort((left, right) => getMagicCategoryLabel(left).localeCompare(getMagicCategoryLabel(right), "ru"));
  const rarities = [...new Set(lootItemsIndex.map((item) => getLootDisplayRarityValue(item.rarity_value)).filter(Boolean))]
    .sort((left, right) => getMagicRaritySortValue(left) - getMagicRaritySortValue(right));

  lootListCategoryInput.innerHTML = `
    <option value="">Любая категория</option>
    ${categories.map((category) => `<option value="${escapeHtml(category)}">${escapeHtml(getMagicCategoryLabel(category))}</option>`).join("")}
  `;
  lootRarityInput.innerHTML = `
    <option value="">Любая редкость</option>
    ${rarities.map((rarity) => `<option value="${escapeHtml(rarity)}">${escapeHtml(getMagicRarityLabel(rarity))}</option>`).join("")}
  `;

  if (customLootCategoryInput) {
    customLootCategoryInput.innerHTML = categories
      .map((category) => `<option value="${escapeHtml(category)}">${escapeHtml(getMagicCategoryLabel(category))}</option>`)
      .join("");
  }
  if (customLootRarityInput) {
    customLootRarityInput.innerHTML = rarities
      .filter((rarity) => rarity !== "artifact")
      .map((rarity) => `<option value="${escapeHtml(rarity)}">${escapeHtml(getMagicRarityLabel(rarity))}</option>`)
      .join("");
  }
  if (randomLootRarityInput) {
    randomLootRarityInput.innerHTML = `
      <option value="">Любая редкость</option>
      ${rarities.map((rarity) => `<option value="${escapeHtml(rarity)}">${escapeHtml(getMagicRarityLabel(rarity))}</option>`).join("")}
    `;
  }
}

function setupPotionRarityOptions() {
  if (!potionRarityInput) {
    return;
  }

  const rarities = [...new Set(potionsIndex.map((potion) => potion.rarity_value).filter(Boolean))]
    .sort((left, right) => {
      const leftIndex = POTION_RARITY_ORDER.indexOf(left);
      const rightIndex = POTION_RARITY_ORDER.indexOf(right);
      if (leftIndex !== -1 || rightIndex !== -1) {
        return (leftIndex === -1 ? Number.MAX_SAFE_INTEGER : leftIndex)
          - (rightIndex === -1 ? Number.MAX_SAFE_INTEGER : rightIndex);
      }
      return getRarityLabel(left).localeCompare(getRarityLabel(right), "ru");
    });

  potionRarityInput.innerHTML = `
    <option value="">Любая</option>
    ${rarities.map((rarity) => {
      const item = potionsIndex.find((potion) => potion.rarity_value === rarity);
      return `<option value="${escapeHtml(rarity)}">${escapeHtml(getRarityLabel(rarity, item?.rarity))}</option>`;
    }).join("")}
  `;
}

function setupBestiaryFilters() {
  const types = getAvailableCreatureTypes();
  const crs = [...new Set(bestiaryIndex.map((monster) => monster.cr).filter((cr) => cr !== ""))]
    .sort((a, b) => {
      const left = bestiaryIndex.find((monster) => monster.cr === a)?.cr_value ?? 0;
      const right = bestiaryIndex.find((monster) => monster.cr === b)?.cr_value ?? 0;
      return left - right;
    });

  bestiaryType.innerHTML = `<option value="">Все типы</option>${types
    .map((type) => `<option value="${escapeHtml(type)}">${escapeHtml(getTypeLabel(type))}</option>`)
    .join("")}`;

  bestiaryCr.innerHTML = `<option value="">Любой CR</option>${crs
    .map((cr) => `<option value="${escapeHtml(cr)}">CR ${escapeHtml(cr)}</option>`)
    .join("")}`;
}

function setupRandomCrOptions() {
  if (!randomCrInput) {
    return;
  }

  const singleCrs = [...new Set(bestiaryIndex.map((monster) => monster.cr).filter(Boolean))]
    .map((cr) => ({
      cr,
      value: bestiaryIndex.find((monster) => monster.cr === cr)?.cr_value ?? parseCrValue(cr),
    }))
    .filter(({ value }) => value >= 5)
    .sort((a, b) => a.value - b.value);

  randomCrInput.innerHTML = `
    <option value="">Любой CR</option>
    ${CR_GROUPS.map((group) => `<option value="${group.value}">${group.label}</option>`).join("")}
    ${singleCrs.map(({ cr }) => `<option value="${escapeHtml(cr)}">CR ${escapeHtml(cr)}</option>`).join("")}
  `;
}

function setupRandomKindOptions() {
  if (!randomKindInput) {
    return;
  }

  const types = getAvailableCreatureTypes();

  randomKindInput.innerHTML = `
    <option value="">Любая</option>
    ${types.map((type) => `<option value="type:${escapeHtml(type)}">${escapeHtml(getTypeLabel(type))}</option>`).join("")}
  `;
}

function setupCustomTypeOptions() {
  if (!customTypeInput) {
    return;
  }

  const currentValue = normalizeCreatureType(customTypeInput.value) || "humanoid";
  const types = getAvailableCreatureTypes();
  customTypeInput.innerHTML = types
    .map((type) => `<option value="${escapeHtml(type)}">${escapeHtml(getTypeLabel(type))}</option>`)
    .join("");

  customTypeInput.value = types.includes(currentValue) ? currentValue : types[0] || "";
}

function getFilteredBestiary() {
  const query = bestiarySearch.value.trim().toLowerCase();
  const type = bestiaryType.value;
  const cr = bestiaryCr.value;

  return bestiaryIndex.filter((monster) => {
    const matchesScope = bestiaryScope === "custom" ? monster.is_custom : true;
    const matchesQuery = !query || monster.name.toLowerCase().includes(query) || (monster.name_ru || "").toLowerCase().includes(query);
    const matchesType = !type || normalizeCreatureType(monster.type) === normalizeCreatureType(type);
    const matchesCr = !cr || monster.cr === cr;
    return matchesScope && matchesQuery && matchesType && matchesCr;
  });
}

function getTypeLabel(type) {
  const clean = normalizeCreatureType(type);
  const match = bestiaryIndex.find((monster) => normalizeCreatureType(monster.type) === clean && monster.type_ru);
  return match?.type_ru || getTypeLabelRu(clean);
}

function getMonsterLocale(id) {
  return bestiaryLocaleById.get(id) || {};
}

function getMonsterName(monster) {
  return monster.name_ru || getMonsterLocale(monster.id).name_ru || monster.name;
}

function getMonsterType(monster) {
  const type = normalizeCreatureType(monster.type);
  const localeType = getMonsterLocale(monster.id).type_ru;
  return monster.type_ru || localeType || getTypeLabelRu(type);
}

function getMonsterSize(monster) {
  return monster.size_ru || getMonsterLocale(monster.id).size_ru || capitalize(monster.size);
}

function getMonsterText(monster, field) {
  const locale = getMonsterLocale(monster.id);
  const original = monster[field] || [];
  const translated = locale[`${field}_ru`];
  return translated?.length || !original.length ? translated || [] : original;
}

function renderBestiary() {
  const filtered = getFilteredBestiary();
  const total = bestiaryScope === "custom" ? bestiaryIndex.filter((monster) => monster.is_custom).length : bestiaryIndex.length;
  bestiarySummary.textContent = `Показано ${filtered.length} из ${total} существ`;

  if (!filtered.length) {
    const emptyText = bestiaryScope === "custom"
      ? "Создай своё первое существо, и оно появится в этом разделе."
      : "Попробуй изменить поиск или фильтры.";
    bestiaryList.innerHTML = `<div class="library-empty"><strong>Ничего не найдено</strong><span>${emptyText}</span></div>`;
    return;
  }

  bestiaryList.innerHTML = filtered
    .map((monster) => {
      const content = `
        <span>${escapeHtml(getMonsterType(monster))} · ${escapeHtml(getMonsterSize(monster))}</span>
        <strong>${escapeHtml(getMonsterName(monster))}</strong>
        <em>${escapeHtml(monster.name)}</em>
        <small>CR ${escapeHtml(monster.cr)} · HP ${escapeHtml(monster.hp_average)} (${escapeHtml(monster.hp_formula)})</small>
      `;
      if (monster.is_custom) {
        return `
          <article class="monster-card custom-monster-card is-custom">
            <button class="monster-card-main" type="button" data-monster-id="${escapeHtml(monster.id)}">${content}</button>
            <button class="danger-action" type="button" data-delete-custom-monster-id="${escapeHtml(monster.id)}">Удалить</button>
          </article>
        `;
      }
      return `<button class="monster-card" type="button" data-monster-id="${escapeHtml(monster.id)}">${content}</button>`;
    })
    .join("");
}

function openMonsterModal(id) {
  const monster = bestiaryById.get(id);
  if (!monster) {
    return;
  }

  currentMonster = monster;
  deleteCustomMonsterButton?.classList.toggle("is-hidden", !monster.is_custom);
  monsterName.textContent = getMonsterName(monster);
  monsterDetail.innerHTML = renderMonsterDetail(monster);
  monsterModal.classList.remove("is-hidden");
}

function closeMonsterModal() {
  monsterModal.classList.add("is-hidden");
  currentMonster = null;
}

function renderMonsterDetail(monster) {
  const hitPoints = monster.hit_points || {
    average: monster.hp_average || 1,
    formula: monster.hp_formula || String(monster.hp_average || 1),
  };
  const abilities = ["str", "dex", "con", "int", "wis", "cha"]
    .map((key) => `
      <div class="ability-cell">
        <span>${key.toUpperCase()}</span>
        <strong>${escapeHtml(monster.abilities?.[key] ?? "-")}</strong>
        <small>${escapeHtml(signed(monster.modifiers?.[key]))}</small>
      </div>
    `)
    .join("");

  return `
    <div class="monster-meta-grid">
      <div><span>Английское название</span><strong>${escapeHtml(monster.name)}</strong></div>
      <div><span>Размер и тип</span><strong>${escapeHtml(getMonsterSize(monster))} ${escapeHtml(getMonsterType(monster))}</strong></div>
      <div><span>Мировоззрение</span><strong>${escapeHtml(monster.alignment || "-")}</strong></div>
      <div><span>Класс доспеха</span><strong>${escapeHtml(monster.armor_class ?? "-")}</strong></div>
      <div><span>Хиты</span><strong>${escapeHtml(hitPoints.average)} (${escapeHtml(hitPoints.formula)})</strong></div>
      <div><span>Скорость</span><strong>${escapeHtml(formatSpeed(monster.speed))}</strong></div>
      <div><span>Опасность</span><strong>CR ${escapeHtml(monster.cr)} · ${escapeHtml(monster.xp)} XP</strong></div>
    </div>

    <div class="abilities-grid">${abilities}</div>

    <div class="monster-info-lines">
      <p><strong>Спасброски:</strong> ${escapeHtml(Object.entries(monster.saving_throws || {}).map(([key, value]) => `${key.toUpperCase()} ${signed(value)}`).join(", ") || "-")}</p>
      <p><strong>Навыки:</strong> ${escapeHtml(Object.entries(monster.skills || {}).map(([key, value]) => `${key} ${signed(value)}`).join(", ") || "-")}</p>
      <p><strong>Чувства:</strong> ${escapeHtml(formatSenses(monster.senses))}</p>
      <p><strong>Языки:</strong> ${escapeHtml(listText(monster.languages))}</p>
      <p><strong>Сопротивления:</strong> ${escapeHtml(listText(monster.damage?.resistances))}</p>
      <p><strong>Иммунитет к урону:</strong> ${escapeHtml(listText(monster.damage?.immunities))}</p>
      <p><strong>Иммунитет к состояниям:</strong> ${escapeHtml(listText(monster.condition_immunities))}</p>
    </div>

    ${renderTextList("Особенности", getMonsterText(monster, "traits"))}
    ${renderTextList("Действия", getMonsterText(monster, "actions"))}
    ${renderTextList("Бонусные действия", getMonsterText(monster, "bonus_actions"))}
    ${renderTextList("Реакции", getMonsterText(monster, "reactions"))}
    ${renderTextList("Легендарные действия", getMonsterText(monster, "legendary_actions"))}

    <div class="license-note">
      ${monster.is_custom ? "Пользовательское существо. Данные сохранены локально в этом браузере." : "Данные: SRD 5.2, CC-BY-4.0. Перевод названий и ручная разметка будут добавляться отдельно."}
    </div>
  `;
}

function getSavedRolls() {
  try {
    return JSON.parse(storage.get("dnd-saved-rolls", "[]"));
  } catch {
    return [];
  }
}

function setSavedRolls(rolls) {
  storage.set("dnd-saved-rolls", JSON.stringify(rolls));
  updateSavedRollsCount();
  renderLibraryRolls();
}

function getSavedMonsters() {
  try {
    return JSON.parse(storage.get("dnd-saved-monsters", "[]"));
  } catch {
    return [];
  }
}

function setSavedMonsters(monsters) {
  storage.set("dnd-saved-monsters", JSON.stringify(monsters));
  updateSavedRollsCount();
  renderLibraryMonsters();
}

function getSavedPotions() {
  try {
    return JSON.parse(storage.get("dnd-saved-potions", "[]"));
  } catch {
    return [];
  }
}

function setSavedPotions(savedPotions) {
  storage.set("dnd-saved-potions", JSON.stringify(savedPotions));
  updateSavedRollsCount();
  renderLibraryPotions();
}

function getSavedSpells() {
  try {
    return JSON.parse(storage.get("dnd-saved-spells", "[]"));
  } catch {
    return [];
  }
}

function setSavedSpells(savedSpells) {
  storage.set("dnd-saved-spells", JSON.stringify(savedSpells));
  updateSavedRollsCount();
  renderLibrarySpells();
}

function getSavedLoot() {
  try {
    return JSON.parse(storage.get("dnd-saved-loot", "[]"));
  } catch {
    return [];
  }
}

function setSavedLoot(savedLoot) {
  storage.set("dnd-saved-loot", JSON.stringify(savedLoot));
  updateSavedRollsCount();
  renderLibraryLoot();
}

function getSavedNotes() {
  try {
    return JSON.parse(storage.get("dnd-library-notes", "[]"));
  } catch {
    return [];
  }
}

function setSavedNotes(savedNotes) {
  storage.set("dnd-library-notes", JSON.stringify(savedNotes));
  updateSavedRollsCount();
  renderLibraryNotes();
}

function updateSavedRollsCount() {
  if (!notesTotal) {
    return;
  }
  const count = getSavedRolls().length + getSavedMonsters().length + getSavedPotions().length + getSavedSpells().length + getSavedLoot().length + getSavedNotes().length;
  notesTotal.textContent = `${count} сохранено`;
}

function getLibrarySection(selector, category) {
  if (!libraryPanel) {
    return null;
  }

  let section = libraryPanel.querySelector(selector);
  if (!section) {
    section = document.createElement("div");
    section.className = "saved-rolls";
    libraryPanel.appendChild(section);
  }
  section.dataset.librarySection = "";
  section.dataset.libraryCategory = category;
  return section;
}

function applyLibraryFilter() {
  if (!libraryPanel) {
    return;
  }

  libraryFilters.forEach((button) => {
    button.classList.toggle("active", button.dataset.libraryFilter === libraryFilter);
  });
  libraryPanel.querySelectorAll("[data-library-section]").forEach((section) => {
    const isVisible = libraryFilter === "all" || section.dataset.libraryCategory === libraryFilter;
    section.classList.toggle("is-hidden", !isVisible);
  });
}

function renderLibrarySectionHeader(category, { withNoteAction = false } = {}) {
  const title = LIBRARY_SECTION_TITLES[category] || "Мои записи";
  const action = withNoteAction
    ? `<button class="library-add-button" type="button" data-open-note-editor title="Новая заметка" aria-label="Новая заметка">+</button>`
    : "";
  return `
    <div class="library-action-row">
      <h4>${escapeHtml(title)}</h4>
      ${action}
    </div>
  `;
}

function renderLibraryEmptySection(selector, category, title, text) {
  const section = getLibrarySection(selector, category);
  if (!section) {
    return;
  }
  section.dataset[selector.replace(/^\[data-|\]$/g, "").replace(/-([a-z])/g, (_, letter) => letter.toUpperCase())] = "";
  section.innerHTML = `
    ${renderLibrarySectionHeader(category)}
    <div class="library-empty">
      <strong>${escapeHtml(title)}</strong>
      <span>${escapeHtml(text)}</span>
    </div>
  `;
  applyLibraryFilter();
}

function renderLibraryPlaceholders() {
  renderLibraryEmptySection("[data-saved-characters]", "characters", "Сохранённых персонажей пока нет", "Когда появится генератор персонажей, его результаты будут здесь.");
  renderLibraryEmptySection("[data-saved-taverns]", "taverns", "Сохранённых таверн пока нет", "Сохрани таверну после генерации, и она появится здесь.");
  renderLibraryEmptySection("[data-saved-events]", "events", "Сохранённых событий пока нет", "Сохрани случайное событие, и оно появится здесь.");
}

function isSavedLibraryItem(entry) {
  return entry.libraryType === "item" || entry.tierId === "random-item" || entry.tierLabel === "выбрано мастером" || Boolean(entry.source_id);
}

function isSavedLibraryReward(entry) {
  return !isSavedLibraryItem(entry);
}

function renderLibraryRolls() {
  if (!libraryPanel) {
    return;
  }

  const list = getLibrarySection("[data-saved-rolls]", "rolls");
  list.dataset.savedRolls = "";

  const rolls = getSavedRolls();
  if (!rolls.length) {
    list.innerHTML = `
      ${renderLibrarySectionHeader("rolls")}
      <div class="library-empty">
        <strong>Сохранённых бросков пока нет</strong>
        <span>Сохрани понравившийся результат после броска, и он появится здесь.</span>
      </div>
    `;
    applyLibraryFilter();
    return;
  }

  list.innerHTML = `
    ${renderLibrarySectionHeader("rolls")}
    <div class="saved-roll-grid">
      ${rolls
        .map((roll) => {
          const date = new Date(roll.createdAt).toLocaleString("ru-RU", {
            day: "2-digit",
            month: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
          });
          const label = roll.label || "Без подписи";
          return `
            <article class="saved-roll-card">
              <button class="card-remove" type="button" data-delete-roll="${roll.id}" aria-label="Удалить бросок">×</button>
              <span>${escapeHtml(date)} · ${escapeHtml(roll.formula)}</span>
              <strong>${escapeHtml(roll.total)}</strong>
              <p>${escapeHtml(label)}</p>
              <small>${escapeHtml(roll.rolls.join(", "))}</small>
            </article>
          `;
        })
        .join("")}
    </div>
  `;
  applyLibraryFilter();
}

function renderLibraryMonsters() {
  if (!libraryPanel) {
    return;
  }

  const list = getLibrarySection("[data-saved-monsters]", "creatures");
  list.dataset.savedMonsters = "";

  const monsters = getSavedMonsters();
  if (!monsters.length) {
    list.innerHTML = `
      ${renderLibrarySectionHeader("creatures")}
      <div class="library-empty">
        <strong>Сохранённых существ пока нет</strong>
        <span>Сохрани существо из Бестиария или из случайной генерации, и оно появится здесь.</span>
      </div>
    `;
    applyLibraryFilter();
    return;
  }

  list.innerHTML = `
    ${renderLibrarySectionHeader("creatures")}
    <div class="saved-monster-grid">
      ${monsters
        .map((entry) => `
          <article class="saved-monster-card" data-open-saved-monster="${escapeHtml(entry.id)}" tabindex="0" role="button">
            <button class="card-remove" type="button" data-delete-saved-monster="${escapeHtml(entry.id)}" aria-label="Удалить существо">×</button>
            <span>${escapeHtml(entry.type)} · CR ${escapeHtml(entry.cr)}</span>
            <strong>${escapeHtml(entry.name)}</strong>
            <small>HP ${escapeHtml(entry.hp)}${entry.hp_formula ? ` (${escapeHtml(entry.hp_formula)})` : ""}</small>
          </article>
        `)
        .join("")}
    </div>
  `;
  applyLibraryFilter();
}

function renderLibraryPotions() {
  if (!libraryPanel) {
    return;
  }

  const list = getLibrarySection("[data-saved-potions]", "potions");
  list.dataset.savedPotions = "";

  const savedPotions = getSavedPotions();
  if (!savedPotions.length) {
    list.innerHTML = `
      ${renderLibrarySectionHeader("potions")}
      <div class="library-empty">
        <strong>Сохранённых зелий пока нет</strong>
        <span>Сгенерируй зелья и сохрани результат, чтобы они появились здесь.</span>
      </div>
    `;
    applyLibraryFilter();
    return;
  }

  list.innerHTML = `
    ${renderLibrarySectionHeader("potions")}
    <div class="saved-monster-grid">
      ${savedPotions
        .map((entry) => `
            <article class="saved-monster-card potion-library-card" data-open-saved-potion="${escapeHtml(entry.id)}" tabindex="0" role="button">
              <button class="card-remove" type="button" data-delete-saved-potion="${escapeHtml(entry.id)}" aria-label="Удалить зелье">×</button>
              <span>${escapeHtml(entry.kind)} · ${escapeHtml(entry.rarity)}</span>
              <strong>${escapeHtml(entry.name)}</strong>
              <em>${escapeHtml(entry.source || "Open5e")}</em>
              <small class="potion-description">${escapeHtml(getPlainTextExcerpt(entry.description))}</small>
            </article>
          `)
        .join("")}
    </div>
  `;
  applyLibraryFilter();
}

function renderLibrarySpells() {
  if (!libraryPanel) {
    return;
  }

  const list = getLibrarySection("[data-saved-spells]", "spells");
  list.dataset.savedSpells = "";

  const savedSpells = getSavedSpells();
  if (!savedSpells.length) {
    list.innerHTML = `
      ${renderLibrarySectionHeader("spells")}
      <div class="library-empty">
        <strong>Сохранённых заклинаний пока нет</strong>
        <span>Сохрани заклинание из списка, и оно появится здесь.</span>
      </div>
    `;
    applyLibraryFilter();
    return;
  }

  list.innerHTML = `
    ${renderLibrarySectionHeader("spells")}
    <div class="saved-monster-grid">
      ${savedSpells
        .map((entry) => `
          <article class="saved-monster-card spell-library-card" data-open-saved-spell="${escapeHtml(entry.id)}" tabindex="0" role="button">
            <button class="card-remove" type="button" data-delete-saved-spell="${escapeHtml(entry.id)}" aria-label="Удалить заклинание">×</button>
            <span>${escapeHtml(entry.level_label)} · ${escapeHtml(entry.school)}</span>
            <strong>${escapeHtml(entry.name)}</strong>
            <em>${escapeHtml(entry.classes || "-")}</em>
            <small>${escapeHtml(entry.summary || "-")}</small>
          </article>
        `)
        .join("")}
    </div>
  `;
  applyLibraryFilter();
}

function renderSavedLootSection(selector, category, title, emptyTitle, emptyText, savedLoot) {
  if (!libraryPanel) {
    return;
  }

  const list = getLibrarySection(selector, category);
  if (selector === "[data-saved-loot-rewards]") {
    list.dataset.savedLootRewards = "";
  }
  if (selector === "[data-saved-loot-items]") {
    list.dataset.savedLootItems = "";
  }
  if (!savedLoot.length) {
    list.innerHTML = `
      ${renderLibrarySectionHeader(category)}
      <div class="library-empty">
        <strong>${escapeHtml(emptyTitle)}</strong>
        <span>${escapeHtml(emptyText)}</span>
      </div>
    `;
    applyLibraryFilter();
    return;
  }

  list.innerHTML = `
    ${renderLibrarySectionHeader(category)}
    <div class="saved-monster-grid">
      ${savedLoot
        .map((entry) => `
          <article class="saved-monster-card loot-library-card" data-open-saved-loot="${escapeHtml(entry.id)}" tabindex="0" role="button">
            <button class="card-remove" type="button" data-delete-saved-loot="${escapeHtml(entry.id)}" aria-label="Удалить лут">×</button>
            <span>${escapeHtml(entry.typeLabel || "находка")}</span>
            <strong>${escapeHtml(entry.title)}</strong>
            <em>${escapeHtml(getLootCardMeta(entry))}</em>
            <small class="potion-description">${escapeHtml(getLootSummaryText(entry))}</small>
          </article>
        `)
        .join("")}
    </div>
  `;
  applyLibraryFilter();
}

function renderLibraryLoot() {
  const savedLoot = getSavedLoot();
  renderSavedLootSection(
    "[data-saved-loot-rewards]",
    "rewards",
    "Мои награды",
    "Сохранённых наград пока нет",
    "Сгенерируй награду и сохрани результат, чтобы карточка появилась здесь.",
    savedLoot.filter(isSavedLibraryReward)
  );
  renderSavedLootSection(
    "[data-saved-loot-items]",
    "items",
    "Мои предметы",
    "Сохранённых предметов пока нет",
    "Сохрани предмет из списка или случайной находки, и он появится здесь.",
    savedLoot.filter(isSavedLibraryItem)
  );
}

function formatLibraryDate(value) {
  if (!value) {
    return "без даты";
  }
  return new Date(value).toLocaleString("ru-RU", {
    day: "2-digit",
    month: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function renderLibraryNotes() {
  if (!libraryPanel) {
    return;
  }

  const list = getLibrarySection("[data-saved-notes]", "notes");
  list.dataset.savedNotes = "";
  const notes = getSavedNotes();
  const header = renderLibrarySectionHeader("notes", { withNoteAction: true });

  if (!notes.length) {
    list.innerHTML = `
      ${header}
      <div class="library-empty">
        <strong>Заметок пока нет</strong>
        <span>Сохрани идею, сцену, NPC или напоминание, и заметка появится здесь.</span>
      </div>
    `;
    applyLibraryFilter();
    return;
  }

  list.innerHTML = `
    ${header}
    <div class="saved-monster-grid">
      ${notes
        .map((note) => `
          <article class="saved-monster-card note-library-card" data-open-saved-note="${escapeHtml(note.id)}" tabindex="0" role="button">
            <button class="card-remove" type="button" data-delete-saved-note="${escapeHtml(note.id)}" aria-label="Удалить заметку">×</button>
            <span>${escapeHtml(formatLibraryDate(note.updatedAt || note.createdAt))}</span>
            <strong>${escapeHtml(note.title || "Без названия")}</strong>
            <small class="note-description">${escapeHtml(getPlainTextExcerpt(note.body || ""))}</small>
          </article>
        `)
        .join("")}
    </div>
  `;
  applyLibraryFilter();
}

function openNoteEditor(noteId = "") {
  const note = getSavedNotes().find((entry) => entry.id === noteId);
  noteModalTitle.textContent = note ? "Редактировать заметку" : "Новая заметка";
  noteIdInput.value = note?.id || "";
  noteTitleInput.value = note?.title || "";
  noteBodyInput.value = note?.body || "";
  deleteNoteButton.classList.toggle("is-hidden", !note);
  noteModal.classList.remove("is-hidden");
  noteTitleInput.focus();
}

function closeNoteEditor() {
  noteModal.classList.add("is-hidden");
  noteForm.reset();
  noteIdInput.value = "";
  deleteNoteButton.classList.add("is-hidden");
}

function saveLibraryNote(event) {
  event.preventDefault();
  const now = new Date().toISOString();
  const id = noteIdInput.value || `note-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  const title = noteTitleInput.value.trim() || "Без названия";
  const body = noteBodyInput.value.trim();
  if (!body && title === "Без названия") {
    return;
  }

  const notes = getSavedNotes();
  const existing = notes.find((entry) => entry.id === id);
  const savedNote = {
    id,
    title,
    body,
    createdAt: existing?.createdAt || now,
    updatedAt: now,
  };
  const nextNotes = [savedNote, ...notes.filter((entry) => entry.id !== id)];
  setSavedNotes(nextNotes.slice(0, 300));
  libraryFilter = "notes";
  applyLibraryFilter();
  closeNoteEditor();
}

function deleteLibraryNote(noteId) {
  if (!noteId) {
    return;
  }
  setSavedNotes(getSavedNotes().filter((note) => note.id !== noteId));
}

function isDiceButton(button) {
  const text = button.textContent.trim().toLowerCase();
  return text === "бросить кость" || text === "кости";
}

function openDiceModal() {
  lastDiceRoll = null;
  diceLabelInput.value = "";
  diceModal.classList.remove("is-hidden");
  diceSetup.classList.remove("is-hidden");
  diceOrbit.classList.remove("is-rolling");
  diceResult.classList.add("is-hidden");
  diceActions.classList.add("is-hidden");
  diceFace.textContent = diceSidesInput.value;
  diceLabelInput.focus();
}

function closeDiceModal() {
  diceModal.classList.add("is-hidden");
  diceOrbit.classList.remove("is-rolling");
  diceLabelInput.value = "";
  lastDiceRoll = null;
}

function randomInt(min, max) {
  const array = new Uint32Array(1);
  crypto.getRandomValues(array);
  return min + (array[0] % (max - min + 1));
}

function rollDice() {
  const sides = Number(diceSidesInput.value);
  const count = Math.min(Math.max(Number(diceCountInput.value) || 1, 1), 20);
  const label = cleanDiceLabel(diceLabelInput.value);

  diceCountInput.value = String(count);
  diceLabelInput.value = label;
  diceSetup.classList.add("is-hidden");
  diceResult.classList.add("is-hidden");
  diceActions.classList.add("is-hidden");
  diceOrbit.classList.add("is-rolling");

  let ticks = 0;
  const tickTimer = window.setInterval(() => {
    ticks += 1;
    diceFace.textContent = String(randomInt(1, sides));
    if (ticks >= 18) {
      window.clearInterval(tickTimer);
    }
  }, 70);

  window.setTimeout(() => {
    const rolls = Array.from({ length: count }, () => randomInt(1, sides));
    const total = rolls.reduce((sum, value) => sum + value, 0);
    const formula = `${count}d${sides}`;

    lastDiceRoll = {
      id: crypto.randomUUID(),
      label,
      formula,
      rolls,
      total,
      createdAt: new Date().toISOString(),
    };

    diceOrbit.classList.remove("is-rolling");
    diceFace.textContent = String(total);
    diceFormula.textContent = label ? `${label} · ${formula}` : formula;
    diceTotal.textContent = String(total);
    diceBreakdown.textContent = count > 1 ? `Выпало: ${rolls.join(", ")}` : `Выпало: ${rolls[0]}`;
    diceResult.classList.remove("is-hidden");
    diceActions.classList.remove("is-hidden");
  }, 1350);
}

function saveDiceRoll() {
  if (!lastDiceRoll) {
    return;
  }

  const currentLabel = cleanDiceLabel(diceLabelInput.value);
  lastDiceRoll.label = currentLabel;

  const rolls = getSavedRolls();
  rolls.unshift(lastDiceRoll);
  setSavedRolls(rolls.slice(0, 100));
  closeDiceModal();
  openPanel("library");
}

function deleteSavedRoll(id) {
  const rolls = getSavedRolls().filter((roll) => roll.id !== id);
  setSavedRolls(rolls);
}

function rollHpFormula(formula, fallback = 1) {
  const text = String(formula || "").replace(/\s+/g, "");
  const match = text.match(/^(\d+)d(\d+)([+-]\d+)?$/i);
  if (!match) {
    return Math.max(1, Number(fallback) || 1);
  }

  const count = Number(match[1]);
  const sides = Number(match[2]);
  const modifier = Number(match[3] || 0);
  const total = Array.from({ length: count }, () => randomInt(1, sides)).reduce((sum, value) => sum + value, modifier);
  return Math.max(1, total);
}

function toSavedMonster(monster, hp = null) {
  const hitPoints = monster.hit_points || {
    average: monster.hp_average || 1,
    formula: monster.hp_formula || String(monster.hp_average || 1),
  };
  return {
    id: crypto.randomUUID(),
    source_id: monster.id,
    name: getMonsterName(monster),
    type: getMonsterType(monster),
    size: getMonsterSize(monster),
    cr: monster.cr,
    hp: hp ?? hitPoints.average,
    hp_formula: hitPoints.formula,
    savedAt: new Date().toISOString(),
  };
}

function saveMonsterToLibrary(monster, hp = null) {
  const monsters = getSavedMonsters();
  monsters.unshift(toSavedMonster(monster, hp));
  setSavedMonsters(monsters.slice(0, 200));
}

async function openSavedMonster(id) {
  await loadBestiary();
  const entry = getSavedMonsters().find((monster) => monster.id === id);
  if (!entry) {
    return;
  }

  const source = bestiaryById.get(entry.source_id);
  const monster = source
    ? {
        ...source,
        hit_points: {
          ...(source.hit_points || {}),
          average: entry.hp,
          formula: entry.hp_formula || source.hit_points?.formula || String(entry.hp),
        },
      }
    : {
        id: entry.source_id || entry.id,
        name: entry.name,
        name_ru: entry.name,
        type: entry.type,
        type_ru: entry.type,
        size: entry.size || "-",
        size_ru: entry.size || "-",
        alignment: "-",
        armor_class: "-",
        hit_points: {
          average: entry.hp,
          formula: entry.hp_formula || String(entry.hp),
        },
        speed: {},
        cr: entry.cr,
        xp: 0,
        abilities: {},
        modifiers: {},
        saving_throws: {},
        skills: {},
        senses: {},
        languages: [],
        damage: { resistances: [], immunities: [] },
        condition_immunities: [],
        traits: [],
        actions: [],
        bonus_actions: [],
        reactions: [],
        legendary_actions: [],
      };

  currentMonster = monster;
  deleteCustomMonsterButton?.classList.add("is-hidden");
  monsterName.textContent = getMonsterName(monster);
  monsterDetail.innerHTML = renderMonsterDetail(monster);
  monsterModal.classList.remove("is-hidden");
}

function saveCurrentMonster() {
  if (!currentMonster) {
    return;
  }
  saveMonsterToLibrary(currentMonster);
  closeMonsterModal();
  openPanel("library");
}

function deleteSavedMonster(id) {
  setSavedMonsters(getSavedMonsters().filter((monster) => monster.id !== id));
}

function deleteCustomMonster() {
  if (!currentMonster?.is_custom) {
    return;
  }

  deleteCustomMonsterById(currentMonster.id);
  closeMonsterModal();
}

function deleteCustomMonsterById(id) {
  setCustomMonsters(getCustomMonsters().filter((monster) => monster.id !== id));
  bestiaryMonsters = bestiaryMonsters.filter((monster) => monster.id !== id);
  bestiaryIndex = bestiaryIndex.filter((monster) => monster.id !== id);
  bestiaryById.delete(id);
  setupBestiaryFilters();
  setupRandomKindOptions();
  setupCustomTypeOptions();
  renderBestiary();
}

function matchesRandomCr(monster, value) {
  if (!value) {
    return true;
  }
  const group = CR_GROUPS.find((item) => item.value === value);
  const cr = monster.cr_value ?? parseCrValue(monster.cr);
  if (group) {
    return cr >= group.min && cr <= group.max;
  }
  return monster.cr === value;
}

function matchesRandomKind(monster, kind) {
  if (!kind) {
    return true;
  }

  const type = normalizeCreatureType(monster.type);
  if (kind.startsWith("type:")) {
    return type === normalizeCreatureType(kind.slice(5));
  }
  return true;
}

function getRandomMonsterPool() {
  return bestiaryIndex
    .filter((monster) => matchesRandomCr(monster, randomCrInput.value))
    .filter((monster) => matchesRandomKind(monster, randomKindInput.value))
    .map((monster) => bestiaryById.get(monster.id))
    .filter(Boolean);
}

function renderRandomMonsterResults() {
  if (!currentRandomMonsters.length) {
    randomMonsterResults.innerHTML = "";
    saveRandomMonstersButton.disabled = true;
    return;
  }

  randomMonsterResults.innerHTML = currentRandomMonsters
    .map(({ id, monster, hp }, index) => `
      <article class="random-monster-card">
        <button class="card-remove" type="button" data-delete-random-monster="${escapeHtml(id)}" aria-label="Убрать существо">×</button>
        <span>${index + 1}. ${escapeHtml(getMonsterType(monster))} · CR ${escapeHtml(monster.cr)}</span>
        <strong>${escapeHtml(getMonsterName(monster))}</strong>
        <small>HP ${escapeHtml(hp)} · формула ${escapeHtml(monster.hit_points?.formula || "-")}</small>
      </article>
    `)
    .join("");
  saveRandomMonstersButton.disabled = false;
}

function generateRandomMonsters() {
  const count = Math.min(Math.max(Number(randomCountInput.value) || 1, 1), 12);
  const pool = getRandomMonsterPool();
  randomCountInput.value = String(count);

  if (!pool.length) {
    randomMonsterResults.innerHTML = `<div class="library-empty"><strong>Подходящих существ нет</strong><span>Измени уровень опасности или тип.</span></div>`;
    saveRandomMonstersButton.disabled = !currentRandomMonsters.length;
    return;
  }

  const nextMonsters = [];
  if (randomSameKindInput.checked) {
    const monster = pool[randomInt(0, pool.length - 1)];
    nextMonsters.push(...Array.from({ length: count }, () => ({
      id: crypto.randomUUID(),
      monster,
      hp: rollHpFormula(monster.hit_points?.formula, monster.hit_points?.average),
    })));
  } else {
    nextMonsters.push(...Array.from({ length: count }, () => {
      const monster = pool[randomInt(0, pool.length - 1)];
      return {
        id: crypto.randomUUID(),
        monster,
        hp: rollHpFormula(monster.hit_points?.formula, monster.hit_points?.average),
      };
    }));
  }

  currentRandomMonsters.push(...nextMonsters);
  renderRandomMonsterResults();
}

function deleteRandomMonster(id) {
  currentRandomMonsters = currentRandomMonsters.filter((entry) => entry.id !== id);
  renderRandomMonsterResults();
}

function getPotionPool() {
  const rarity = potionRarityInput.value;
  const kind = potionKindInput.value;

  return potionsIndex
    .filter((potion) => !rarity || potion.rarity_value === rarity)
    .filter((potion) => {
      if (kind === "oil") return potion.is_oil;
      if (kind === "potion") return !potion.is_oil;
      return true;
    })
    .map((potion) => potionsById.get(potion.id))
    .filter(Boolean);
}

function getPotionLocale(potion) {
  return potionsLocaleById.get(potion.id) || {};
}

function getPotionName(potion) {
  return getPotionLocale(potion).name_ru || potion.name;
}

function getPotionDescription(potion) {
  return normalizeRichText(getPotionLocale(potion).description_ru || potion.description);
}

function getPotionRarity(potion) {
  return getPotionLocale(potion).rarity_ru || getRarityLabel(potion.rarity_value, potion.rarity);
}

function getPotionKindLabel(potion) {
  return getPotionLocale(potion).type_ru || (potion.is_oil ? "масло" : "зелье");
}

function renderPotionDetail(potion) {
  return `
    <div class="monster-meta-grid">
      <div><span>Английское название</span><strong>${escapeHtml(potion.name || "-")}</strong></div>
      <div><span>Тип</span><strong>${escapeHtml(getPotionKindLabel(potion))}</strong></div>
      <div><span>Редкость</span><strong>${escapeHtml(getPotionRarity(potion))}</strong></div>
      <div><span>Источник</span><strong>${escapeHtml(potion.source_display || potion.source || "Open5e")}</strong></div>
      <div><span>Настройка</span><strong>${potion.attunement ? "требуется" : "не требуется"}</strong></div>
      <div><span>Лицензия</span><strong>${escapeHtml(potion.license || "Open5e source data")}</strong></div>
    </div>

    <section class="monster-section">
      <h4>Описание</h4>
      <div class="monster-text-list">
        ${renderRichDescription(getPotionDescription(potion))}
      </div>
    </section>
  `;
}

function openPotionDetail(potion) {
  if (!potion) {
    return;
  }

  potionDetailName.textContent = getPotionName(potion);
  potionDetail.innerHTML = renderPotionDetail(potion);
  potionDetailModal.classList.remove("is-hidden");
}

function closePotionDetail() {
  potionDetailModal.classList.add("is-hidden");
}

function openPotionResult(id) {
  const entry = currentPotionResults.find((result) => result.id === id);
  openPotionDetail(entry?.potion);
}

async function openSavedPotion(id) {
  await loadPotions();
  const entry = getSavedPotions().find((potion) => potion.id === id);
  if (!entry) {
    return;
  }

  const source = potionsById.get(entry.source_id);
  const potion = source
    ? source
    : {
        id: entry.source_id || entry.id,
        name: entry.name,
        type: "Potion",
        rarity: entry.rarity,
        rarity_value: "",
        description: entry.description,
        attunement: false,
        is_oil: entry.kind?.toLowerCase().includes("масло") || entry.kind?.toLowerCase().includes("oil"),
        source: entry.source,
        source_display: entry.source,
        license: "Локально сохранённая запись",
      };

  openPotionDetail(potion);
}

function renderPotionResults() {
  if (!currentPotionResults.length) {
    potionResults.innerHTML = "";
    savePotionsButton.disabled = true;
    return;
  }

  potionResults.innerHTML = currentPotionResults
    .map(({ id, potion }, index) => `
        <article class="random-monster-card potion-result-card" data-open-potion-result="${escapeHtml(id)}" tabindex="0" role="button">
          <button class="card-remove" type="button" data-delete-potion-result="${escapeHtml(id)}" aria-label="Убрать зелье">×</button>
          <span>${index + 1}. ${escapeHtml(getPotionKindLabel(potion))} · ${escapeHtml(getPotionRarity(potion))}</span>
          <strong>${escapeHtml(getPotionName(potion))}</strong>
          <em>${escapeHtml(potion.source_display || potion.source || "Open5e")}</em>
          <small class="potion-description">${escapeHtml(getPlainTextExcerpt(getPotionDescription(potion)))}</small>
        </article>
      `)
    .join("");
  savePotionsButton.disabled = false;
}

function generatePotions() {
  const count = Math.min(Math.max(Number(potionCountInput.value) || 1, 1), 12);
  const pool = getPotionPool();
  potionCountInput.value = String(count);

  if (!pool.length) {
    potionResults.innerHTML = `<div class="library-empty"><strong>Подходящих зелий нет</strong><span>Измени редкость или тип.</span></div>`;
    savePotionsButton.disabled = !currentPotionResults.length;
    return;
  }

  const nextPotions = [];
  if (potionSameKindInput.checked) {
    const potion = pool[randomInt(0, pool.length - 1)];
    nextPotions.push(...Array.from({ length: count }, () => ({
      id: crypto.randomUUID(),
      potion,
    })));
  } else {
    nextPotions.push(...Array.from({ length: count }, () => ({
      id: crypto.randomUUID(),
      potion: pool[randomInt(0, pool.length - 1)],
    })));
  }

  currentPotionResults.push(...nextPotions);
  renderPotionResults();
}

function deletePotionResult(id) {
  currentPotionResults = currentPotionResults.filter((entry) => entry.id !== id);
  renderPotionResults();
}

function toSavedPotion(potion) {
  return {
    id: crypto.randomUUID(),
    source_id: potion.id,
    name: getPotionName(potion),
    rarity: getPotionRarity(potion),
    kind: getPotionKindLabel(potion),
    source: potion.source_display || potion.source || "Open5e",
    description: getPotionDescription(potion),
    savedAt: new Date().toISOString(),
  };
}

function saveGeneratedPotions() {
  if (!currentPotionResults.length) {
    return;
  }

  const savedPotions = getSavedPotions();
  currentPotionResults.forEach(({ potion }) => {
    savedPotions.unshift(toSavedPotion(potion));
  });
  setSavedPotions(savedPotions.slice(0, 200));
  closePotionGenerator();
  openPanel("library");
}

async function openPotionGenerator() {
  try {
    await loadPotions();
    currentPotionResults = [];
    potionResults.innerHTML = "";
    savePotionsButton.disabled = true;
    potionModal.classList.remove("is-hidden");
  } catch (error) {
    potionModal.classList.remove("is-hidden");
    potionResults.innerHTML = `<div class="library-empty"><strong>Ошибка загрузки</strong><span>${escapeHtml(error.message)}</span></div>`;
    savePotionsButton.disabled = true;
  }
}

function closePotionGenerator() {
  potionModal.classList.add("is-hidden");
  currentPotionResults = [];
}

function rollFormula(formula) {
  const text = String(formula || "0").replace(/\s+/g, "");
  const fixed = text.match(/^\d+$/);
  if (fixed) {
    return Number(text);
  }

  const match = text.match(/^(\d*)d(\d+)([+-]\d+)?(?:\*(\d+))?$/i);
  if (!match) {
    return 0;
  }

  const count = Number(match[1] || 1);
  const sides = Number(match[2]);
  const modifier = Number(match[3] || 0);
  const multiplier = Number(match[4] || 1);
  const subtotal = Array.from({ length: count }, () => randomInt(1, sides)).reduce((sum, value) => sum + value, modifier);
  return Math.max(0, subtotal * multiplier);
}

function pickWeighted(weightMap) {
  const entries = Object.entries(weightMap || {}).filter(([, weight]) => Number(weight) > 0);
  const total = entries.reduce((sum, [, weight]) => sum + Number(weight), 0);
  if (!total) {
    return "";
  }

  let roll = randomInt(1, total);
  for (const [value, weight] of entries) {
    roll -= Number(weight);
    if (roll <= 0) {
      return value;
    }
  }
  return entries[entries.length - 1][0];
}

function pickFrom(values) {
  return values?.length ? values[randomInt(0, values.length - 1)] : "";
}

function getLootTier() {
  return lootTables?.tiers?.find((tier) => tier.id === lootTierInput.value) || lootTables?.tiers?.[0];
}

function getMagicCategoryLabel(category) {
  return MAGIC_CATEGORY_LABELS_RU[category] || capitalize(String(category || "предмет").replace(/-/g, " "));
}

function getLootDisplayCategoryKey(category) {
  return category === "rod" || category === "staff" ? "rod-staff" : category;
}

function matchesLootCategory(category, selectedCategory) {
  if (!selectedCategory) {
    return true;
  }
  return getLootDisplayCategoryKey(category) === selectedCategory;
}

function getMagicRarityLabel(rarityValue, rarity = "") {
  return RARITY_LABELS_RU[rarityValue] || rarity || "неизвестно";
}

function getMagicRaritySortValue(rarityValue) {
  const index = MAGIC_RARITY_ORDER.indexOf(rarityValue);
  return index === -1 ? Number.MAX_SAFE_INTEGER : index;
}

function getLootDisplayRarityValue(rarityValue) {
  return rarityValue === "artifact" ? "legendary" : rarityValue;
}

function getLootItemLocale(item) {
  return lootLocaleById.get(item.id) || {};
}

function getLootItemName(item) {
  return getLootItemLocale(item).name_ru || item.name;
}

function getLootItemCategory(item) {
  return getLootItemLocale(item).category_ru || getMagicCategoryLabel(item.category_key);
}

function getLootItemRarity(item) {
  const displayRarityValue = getLootDisplayRarityValue(item.rarity_value);
  if (displayRarityValue !== item.rarity_value) {
    return getMagicRarityLabel(displayRarityValue);
  }
  return getLootItemLocale(item).rarity_ru || getMagicRarityLabel(item.rarity_value, item.rarity);
}

function getLootItemDescription(item) {
  return normalizeRichText(getLootItemLocale(item).description_ru || item.description);
}

function getLootItemSource(item) {
  return item.source_display || item.source || "Open5e";
}

function getLootItemWeight(item) {
  const weight = Number(item.weight);
  if (!Number.isFinite(weight) || weight <= 0) {
    return "-";
  }
  return `${weight.toLocaleString("ru-RU")} ${item.weight_unit || "lb"}`;
}

function getLootItemAttunement(item) {
  if (item.attunement_requirement) {
    return `требуется: ${item.attunement_requirement}`;
  }
  return item.attunement ? "требуется" : "не требуется";
}

function getLootItemArmorText(item) {
  const armor = item.armor;
  if (!armor) {
    return "";
  }
  const localeArmor = getLootItemLocale(item).armor || {};
  const parts = [
    localeArmor.name_ru || armor.name,
    localeArmor.category_ru || armor.category,
    armor.ac_display || (armor.ac_base ? `КД ${armor.ac_base}` : ""),
    armor.grants_stealth_disadvantage ? "помеха скрытности" : "",
    armor.strength_score_required ? `Сила ${armor.strength_score_required}` : "",
  ].filter(Boolean);
  return parts.join(" · ");
}

function getLootItemWeaponText(item) {
  const weapon = item.weapon;
  if (!weapon) {
    return "";
  }
  const localeWeapon = getLootItemLocale(item).weapon || {};
  const parts = [
    localeWeapon.name_ru || weapon.name,
    localeWeapon.category_ru || weapon.category,
    weapon.damage_dice ? `${weapon.damage_dice}${weapon.damage_type?.name ? ` ${weapon.damage_type.name}` : ""}` : "",
    Array.isArray(weapon.properties)
      ? weapon.properties.map((entry) => [entry.property?.name, entry.detail].filter(Boolean).join(" ")).filter(Boolean).join(", ")
      : "",
  ].filter(Boolean);
  return parts.join(" · ");
}

function getFilteredLootItems() {
  if (!lootList) {
    return [];
  }

  const query = lootSearch.value.trim().toLowerCase();
  const rarity = lootRarityInput.value;
  const category = lootListCategoryInput.value;

  return lootItemsIndex.filter((item) => {
    const source = lootItemsById.get(item.id) || item;
    const name = `${source.name || ""} ${getLootItemName(source) || ""}`.toLowerCase();
    const matchesScope = lootScope === "custom" ? source.is_custom : true;
    const matchesQuery = !query || name.includes(query);
    const matchesRarity = !rarity || getLootDisplayRarityValue(source.rarity_value) === rarity;
    const matchesCategory = matchesLootCategory(source.category_key, category);
    return matchesScope && matchesQuery && matchesRarity && matchesCategory;
  });
}

function renderLootList() {
  if (!lootList || !lootSummary) {
    return;
  }

  const filtered = getFilteredLootItems();
  const total = lootScope === "custom" ? lootItemsIndex.filter((item) => lootItemsById.get(item.id)?.is_custom).length : lootItemsIndex.length;
  lootSummary.textContent = `Показано ${filtered.length} из ${total} предметов`;

  if (!filtered.length) {
    const emptyText = lootScope === "custom"
      ? "Создай свой первый предмет, и он появится в этом разделе."
      : "Попробуй изменить поиск или фильтры.";
    lootList.innerHTML = `<div class="library-empty"><strong>Ничего не найдено</strong><span>${emptyText}</span></div>`;
    return;
  }

  lootList.innerHTML = filtered
    .map((item) => {
      const source = lootItemsById.get(item.id) || item;
      const content = `
        <span>${escapeHtml(getLootItemCategory(source))} · ${escapeHtml(getLootItemRarity(source))}</span>
        <strong>${escapeHtml(getLootItemName(source))}</strong>
        <em>${escapeHtml(source.name || getLootItemName(source))}</em>
        <small>${escapeHtml(getLootItemSource(source))}${source.attunement ? " · настройка" : ""}</small>
      `;
      if (source.is_custom) {
        return `
          <article class="monster-card custom-monster-card is-custom">
            <button class="monster-card-main" type="button" data-loot-item-id="${escapeHtml(source.id)}">${content}</button>
            <button class="danger-action" type="button" data-delete-custom-loot-id="${escapeHtml(source.id)}">Удалить</button>
          </article>
        `;
      }
      return `<button class="monster-card loot-item-card" type="button" data-loot-item-id="${escapeHtml(source.id)}">${content}</button>`;
    })
    .join("");
}

function renderLootItemDetail(item) {
  const armorText = getLootItemArmorText(item);
  const weaponText = getLootItemWeaponText(item);
  return `
    <div class="monster-meta-grid">
      <div><span>Английское название</span><strong>${escapeHtml(item.name || "-")}</strong></div>
      <div><span>Категория</span><strong>${escapeHtml(getLootItemCategory(item))}</strong></div>
      <div><span>Редкость</span><strong>${escapeHtml(getLootItemRarity(item))}</strong></div>
      <div><span>Настройка</span><strong>${escapeHtml(getLootItemAttunement(item))}</strong></div>
      <div><span>Вес</span><strong>${escapeHtml(getLootItemWeight(item))}</strong></div>
      <div><span>Источник</span><strong>${escapeHtml(getLootItemSource(item))}</strong></div>
      ${armorText ? `<div><span>Доспех</span><strong>${escapeHtml(armorText)}</strong></div>` : ""}
      ${weaponText ? `<div><span>Оружие</span><strong>${escapeHtml(weaponText)}</strong></div>` : ""}
    </div>

    <section class="monster-section">
      <h4>Описание</h4>
      <div class="monster-text-list">
        ${renderRichDescription(getLootItemDescription(item) || "Описание отсутствует.")}
      </div>
    </section>

    <div class="license-note">
      ${item.is_custom ? "Пользовательский предмет. Данные сохранены локально в этом браузере." : `Данные: ${escapeHtml(getLootItemSource(item))}. ${escapeHtml(item.license || "Open5e source data")}.`}
    </div>
  `;
}

function addCoins(target, coinRows) {
  coinRows.forEach((row) => {
    target[row.coin] = (target[row.coin] || 0) + rollFormula(row.formula);
  });
}

function getCoinsGpValue(coins) {
  const values = lootTables?.coin_values_gp || {};
  return Object.entries(coins || {}).reduce((sum, [coin, amount]) => sum + (Number(amount) || 0) * (Number(values[coin]) || 0), 0);
}

function formatCoins(coins) {
  const order = ["pp", "gp", "ep", "sp", "cp"];
  const rows = order
    .filter((coin) => coins?.[coin])
    .map((coin) => `${coins[coin]} ${COIN_LABELS_RU[coin] || coin}`);
  return rows.length ? rows.join(", ") : "монет нет";
}

function formatGp(value) {
  const amount = Math.round((Number(value) || 0) * 10) / 10;
  return `${amount.toLocaleString("ru-RU")} зм`;
}

function rollValuable(table) {
  const count = rollFormula(table.formula);
  const valueKey = String(table.value);
  const source = table.kind === "art" ? lootTables.art_tables?.[valueKey] : lootTables.gem_tables?.[valueKey];
  const names = Array.from({ length: count }, () => pickFrom(source)).filter(Boolean);
  return {
    kind: table.kind,
    kindLabel: table.kind === "art" ? "арт-объекты" : "драгоценности",
    value_gp: table.value,
    count,
    names,
    total_gp: count * table.value,
  };
}

function rollSingleValuable(tier) {
  const tables = tier.hoard?.valuables?.length ? tier.hoard.valuables : [];
  const table = pickFrom(tables);
  if (!table) {
    return null;
  }

  const valueKey = String(table.value);
  const source = table.kind === "art" ? lootTables.art_tables?.[valueKey] : lootTables.gem_tables?.[valueKey];
  return {
    kind: table.kind,
    kindLabel: table.kind === "art" ? "арт-объект" : "драгоценность",
    value_gp: table.value,
    count: 1,
    names: [pickFrom(source)].filter(Boolean),
    total_gp: table.value,
  };
}

function matchesLootRarity(item, rarityValue = "") {
  if (!rarityValue) {
    return true;
  }
  return item.rarity_value === rarityValue || getLootDisplayRarityValue(item.rarity_value) === rarityValue;
}

function getMagicItemPool(rarityValue = "", category = lootCategoryInput?.value || "") {
  return lootItemsIndex
    .filter((item) => matchesLootRarity(item, rarityValue))
    .filter((item) => matchesLootCategory(item.category_key, category))
    .map((item) => lootItemsById.get(item.id))
    .filter(Boolean);
}

function getFallbackMagicItemPool(rarityValue = "", category = lootCategoryInput?.value || "") {
  if (category) {
    const categoryPool = lootItemsIndex
      .filter((item) => matchesLootCategory(item.category_key, category))
      .map((item) => lootItemsById.get(item.id))
      .filter(Boolean);
    if (categoryPool.length) {
      return categoryPool;
    }
  }

  return lootItemsIndex
    .filter((item) => matchesLootRarity(item, rarityValue))
    .map((item) => lootItemsById.get(item.id))
    .filter(Boolean);
}

function toMagicLootItem(item) {
  if (!item) {
    return null;
  }

  return {
    id: item.id,
    name: getLootItemName(item),
    category: getLootItemCategory(item),
    category_key: item.category_key,
    rarity: getLootItemRarity(item),
    rarity_value: item.rarity_value,
    attunement: Boolean(item.attunement),
    source: getLootItemSource(item),
    description: getLootItemDescription(item),
  };
}

function pickMagicItem(magicTable) {
  const rarityValue = pickWeighted(magicTable?.rarities);
  const strictPool = getMagicItemPool(rarityValue);
  const fallbackPool = strictPool.length ? strictPool : getFallbackMagicItemPool(rarityValue);
  return toMagicLootItem(pickFrom(fallbackPool));
}

function pickRandomLootItem() {
  const rarity = randomLootRarityInput?.value || "";
  const category = randomLootCategoryInput?.value || "";
  return toMagicLootItem(pickFrom(getMagicItemPool(rarity, category)));
}

function getLootFindType() {
  const selectedCategory = lootCategoryInput?.value || "";
  if (selectedCategory) {
    return "magic";
  }

  const weights = {
    coins: 56,
    valuable: 20,
    mundane: 18,
    magic: 6,
  };
  return pickWeighted(weights) || "coins";
}

function baseLootFind(tier, findType, title) {
  return {
    id: crypto.randomUUID(),
    libraryType: "reward",
    title,
    findType,
    tierId: tier.id,
    tierLabel: tier.label,
    tone: tier.tone,
    total_gp: 0,
    source_note: "Монеты, ценности и странные вещи: локальные таблицы D&D Copilot.",
    savedAt: new Date().toISOString(),
  };
}

function buildCoinFind(tier) {
  const coins = {};
  const roll = randomInt(1, 100);
  const row = tier.individual.find((entry) => roll >= entry.min && roll <= entry.max) || tier.individual[0];
  addCoins(coins, row.coins || []);

  return {
    ...baseLootFind(tier, "coins", `Монеты: ${formatCoins(coins)}`),
    typeLabel: "монеты",
    coins,
    total_gp: getCoinsGpValue(coins),
  };
}

function buildValuableFind(tier) {
  const valuable = rollSingleValuable(tier);
  const name = valuable?.names?.[0] || "Ценность";
  return {
    ...baseLootFind(tier, "valuable", name),
    typeLabel: valuable?.kindLabel || "ценность",
    valuable,
    total_gp: valuable?.total_gp || 0,
  };
}

function buildMundaneFind(tier) {
  const item = pickFrom(lootTables.mundane_finds) || "странная вещь";
  return {
    ...baseLootFind(tier, "mundane", capitalize(item)),
    typeLabel: "странная вещь",
    mundane: item,
  };
}

function buildMagicFind(tier) {
  const item = pickMagicItem(tier.hoard?.magic);
  if (!item) {
    return buildMundaneFind(tier);
  }

  return {
    ...baseLootFind(tier, "magic", item.name),
    typeLabel: item.category,
    magic_item: item,
    source_note: "Магические предметы: Open5e API v2.",
  };
}

function buildRandomLootItemFind() {
  const item = pickRandomLootItem();
  if (!item) {
    return null;
  }

  return {
    id: crypto.randomUUID(),
    libraryType: "item",
    title: item.name,
    findType: "magic",
    tierId: "random-item",
    tierLabel: "По редкости",
    tone: "",
    total_gp: 0,
    typeLabel: item.category,
    magic_item: item,
    source_note: `Данные: ${item.source}.`,
    savedAt: new Date().toISOString(),
  };
}

function buildLootCard() {
  if (lootGeneratorMode === "item") {
    return buildRandomLootItemFind();
  }

  const tier = getLootTier();
  const findType = getLootFindType();
  const builders = {
    coins: buildCoinFind,
    valuable: buildValuableFind,
    mundane: buildMundaneFind,
    magic: buildMagicFind,
  };

  return (builders[findType] || buildCoinFind)(tier);
}

function getLootSummaryText(loot) {
  if (loot.findType === "coins") {
    return `${formatCoins(loot.coins)} · ${formatGp(loot.total_gp)}`;
  }
  if (loot.findType === "valuable") {
    return `${loot.typeLabel || "ценность"} · ${formatGp(loot.total_gp)}`;
  }
  if (loot.findType === "magic" && loot.magic_item) {
    return getPlainTextExcerpt(loot.magic_item.description) || `${loot.magic_item.category} · ${loot.magic_item.rarity}`;
  }
  if (loot.findType === "mundane") {
    return "странная вещь";
  }

  const legacyParts = [];
  if (Object.values(loot.coins || {}).some(Boolean)) {
    legacyParts.push(formatCoins(loot.coins));
  }
  if (loot.valuables?.length) {
    legacyParts.push(`${loot.valuables.reduce((sum, item) => sum + item.count, 0)} ценностей`);
  }
  if (loot.magic_items?.length) {
    legacyParts.push(`${loot.magic_items.length} маг. предмет(а)`);
  }
  return legacyParts.length ? legacyParts.join(" · ") : "находка";
}

function getLootCardMeta(loot) {
  if (loot.findType === "magic" && loot.magic_item) {
    return `${loot.magic_item.category} · ${loot.magic_item.rarity}`;
  }
  if (loot.findType === "coins" || loot.findType === "valuable") {
    return formatGp(loot.total_gp);
  }
  return loot.typeLabel || "находка";
}

function renderLootDetail(loot) {
  if (loot.findType === "magic" && loot.magic_item) {
    const item = loot.magic_item;
    return `
      <div class="monster-meta-grid">
        <div><span>Категория</span><strong>${escapeHtml(item.category)}</strong></div>
        <div><span>Редкость</span><strong>${escapeHtml(item.rarity)}</strong></div>
        <div><span>Настройка</span><strong>${item.attunement ? "требуется" : "не требуется"}</strong></div>
        <div><span>Источник</span><strong>${escapeHtml(item.source)}</strong></div>
      </div>
      <section class="monster-section">
        <h4>Описание</h4>
        <div class="monster-text-list">
          ${renderRichDescription(item.description || "Описание отсутствует.")}
        </div>
      </section>
    `;
  }

  if (loot.findType === "coins") {
    return `
      <div class="monster-meta-grid">
        <div><span>Монеты</span><strong>${escapeHtml(formatCoins(loot.coins))}</strong></div>
        <div><span>Оценка</span><strong>${escapeHtml(formatGp(loot.total_gp))}</strong></div>
        <div><span>Опасность</span><strong>${escapeHtml(loot.tierLabel)}</strong></div>
      </div>
      <div class="license-note">${escapeHtml(loot.source_note || "Локально сохранённая запись.")}</div>
    `;
  }

  if (loot.findType === "valuable" && loot.valuable) {
    return `
      <div class="monster-meta-grid">
        <div><span>Тип</span><strong>${escapeHtml(loot.valuable.kindLabel)}</strong></div>
        <div><span>Оценка</span><strong>${escapeHtml(formatGp(loot.valuable.total_gp))}</strong></div>
        <div><span>Опасность</span><strong>${escapeHtml(loot.tierLabel)}</strong></div>
      </div>
      <section class="monster-section">
        <h4>Описание</h4>
        <div class="monster-text-list">
          <p>${escapeHtml(loot.title)}</p>
        </div>
      </section>
      <div class="license-note">${escapeHtml(loot.source_note || "Локально сохранённая запись.")}</div>
    `;
  }

  if (loot.findType === "mundane") {
    return `
      <div class="monster-meta-grid">
        <div><span>Тип</span><strong>странная вещь</strong></div>
        <div><span>Опасность</span><strong>${escapeHtml(loot.tierLabel)}</strong></div>
      </div>
      <section class="monster-section">
        <h4>Описание</h4>
        <div class="monster-text-list">
          <p>${escapeHtml(loot.mundane || loot.title)}</p>
        </div>
      </section>
      <div class="license-note">${escapeHtml(loot.source_note || "Локально сохранённая запись.")}</div>
    `;
  }

  return `
    <section class="monster-section">
      <h4>Находка</h4>
      <div class="monster-text-list">
        <p>${escapeHtml(getLootSummaryText(loot))}</p>
      </div>
    </section>
  `;
}

function openLootDetailCard(loot) {
  if (!loot) {
    return;
  }

  currentLootItem = null;
  saveCurrentLootItemButton?.classList.add("is-hidden");
  deleteCustomLootItemButton?.classList.add("is-hidden");
  lootDetailName.textContent = loot.title;
  lootDetail.innerHTML = renderLootDetail(loot);
  lootDetailModal.classList.remove("is-hidden");
}

function openLootItemModal(id) {
  const item = lootItemsById.get(id);
  if (!item) {
    return;
  }

  currentLootItem = item;
  saveCurrentLootItemButton?.classList.remove("is-hidden");
  deleteCustomLootItemButton?.classList.toggle("is-hidden", !item.is_custom);
  lootDetailName.textContent = getLootItemName(item);
  lootDetail.innerHTML = renderLootItemDetail(item);
  lootDetailModal.classList.remove("is-hidden");
}

function closeLootDetail() {
  lootDetailModal.classList.add("is-hidden");
  currentLootItem = null;
}

function openLootResult(id) {
  openLootDetailCard(currentLootResults.find((loot) => loot.id === id));
}

function openSavedLoot(id) {
  openLootDetailCard(getSavedLoot().find((loot) => loot.id === id));
}

function toSavedLootItem(item) {
  return {
    id: crypto.randomUUID(),
    libraryType: "item",
    source_id: item.id,
    title: getLootItemName(item),
    typeLabel: "магический предмет",
    findType: "magic",
    tierLabel: "выбрано мастером",
    total_gp: 0,
    magic_item: {
      id: item.id,
      name: getLootItemName(item),
      category: getLootItemCategory(item),
      category_key: item.category_key,
      rarity: getLootItemRarity(item),
      rarity_value: item.rarity_value,
      attunement: Boolean(item.attunement),
      source: getLootItemSource(item),
      description: getLootItemDescription(item),
    },
    source_note: item.is_custom ? "Пользовательский предмет." : `Данные: ${getLootItemSource(item)}.`,
    savedAt: new Date().toISOString(),
  };
}

function saveCurrentLootItem() {
  if (!currentLootItem) {
    return;
  }

  const savedLoot = getSavedLoot();
  savedLoot.unshift(toSavedLootItem(currentLootItem));
  setSavedLoot(savedLoot.slice(0, 200));
  closeLootDetail();
  openPanel("library");
}

function getActiveLootUi() {
  const isItemMode = lootGeneratorMode === "item";
  return {
    results: isItemMode ? randomLootResults : lootResults,
    saveButton: isItemMode ? saveRandomLootButton : saveLootButton,
  };
}

function renderLootResults() {
  const { results, saveButton } = getActiveLootUi();
  if (!results || !saveButton) {
    return;
  }

  if (!currentLootResults.length) {
    results.innerHTML = "";
    saveButton.disabled = true;
    return;
  }

  results.innerHTML = currentLootResults
    .map((loot, index) => `
      <article class="random-monster-card potion-result-card loot-result-card" data-open-loot-result="${escapeHtml(loot.id)}" tabindex="0" role="button">
        <button class="card-remove" type="button" data-delete-loot-result="${escapeHtml(loot.id)}" aria-label="Убрать лут">×</button>
        <span>${index + 1}. ${escapeHtml(loot.typeLabel || "находка")}</span>
        <strong>${escapeHtml(loot.title)}</strong>
        <em>${escapeHtml(getLootCardMeta(loot))}</em>
        <small class="potion-description">${escapeHtml(getLootSummaryText(loot))}</small>
      </article>
    `)
    .join("");
  saveButton.disabled = false;
}

function generateLoot() {
  const countInput = lootGeneratorMode === "item" ? randomLootCountInput : lootCountInput;
  const count = Math.min(Math.max(Number(countInput?.value) || 1, 1), 8);
  if (countInput) {
    countInput.value = String(count);
  }
  const results = Array.from({ length: count }, buildLootCard).filter(Boolean);
  if (!results.length && lootGeneratorMode === "item") {
    const ui = getActiveLootUi();
    ui.results.innerHTML = `<div class="library-empty"><strong>Подходящих предметов нет</strong><span>Измени редкость или категорию.</span></div>`;
    ui.saveButton.disabled = !currentLootResults.length;
    return;
  }
  currentLootResults.push(...results);
  renderLootResults();
}

function deleteLootResult(id) {
  currentLootResults = currentLootResults.filter((loot) => loot.id !== id);
  renderLootResults();
}

function saveGeneratedLoot() {
  if (!currentLootResults.length) {
    return;
  }

  const savedLoot = getSavedLoot();
  currentLootResults.forEach((loot) => {
    savedLoot.unshift({
      ...JSON.parse(JSON.stringify(loot)),
      id: crypto.randomUUID(),
      savedAt: new Date().toISOString(),
    });
  });
  setSavedLoot(savedLoot.slice(0, 200));
  closeActiveLootGenerator();
  openPanel("library");
}

async function openLootGenerator() {
  lootGeneratorMode = "reward";
  try {
    await loadLoot();
    currentLootResults = [];
    lootResults.innerHTML = "";
    saveLootButton.disabled = true;
    lootModal.classList.remove("is-hidden");
  } catch (error) {
    lootModal.classList.remove("is-hidden");
    lootResults.innerHTML = `<div class="library-empty"><strong>Ошибка загрузки</strong><span>${escapeHtml(error.message)}</span></div>`;
    saveLootButton.disabled = true;
  }
}

async function openRandomLootItemGenerator() {
  lootGeneratorMode = "item";
  try {
    await loadLoot();
    currentLootResults = [];
    randomLootResults.innerHTML = "";
    saveRandomLootButton.disabled = true;
    randomLootItemModal.classList.remove("is-hidden");
  } catch (error) {
    randomLootItemModal.classList.remove("is-hidden");
    randomLootResults.innerHTML = `<div class="library-empty"><strong>Ошибка загрузки</strong><span>${escapeHtml(error.message)}</span></div>`;
    saveRandomLootButton.disabled = true;
  }
}

function closeLootGenerator() {
  lootModal.classList.add("is-hidden");
  currentLootResults = [];
}

function closeRandomLootItemGenerator() {
  randomLootItemModal.classList.add("is-hidden");
  currentLootResults = [];
}

function closeActiveLootGenerator() {
  if (lootGeneratorMode === "item") {
    closeRandomLootItemGenerator();
    return;
  }
  closeLootGenerator();
}

async function openCreateLootItemModal() {
  await loadLoot();
  setupLootListFilters();
  createLootItemForm?.reset();
  if (customLootCategoryInput && !customLootCategoryInput.value) {
    customLootCategoryInput.value = "wondrous-item";
  }
  if (customLootRarityInput && !customLootRarityInput.value) {
    customLootRarityInput.value = "common";
  }
  createLootItemModal.classList.remove("is-hidden");
  createLootItemForm?.querySelector("[data-custom-loot-name]")?.focus();
}

function closeCreateLootItemModal() {
  createLootItemModal.classList.add("is-hidden");
}

function buildCustomLootItem() {
  const name = createLootItemForm.querySelector("[data-custom-loot-name]").value.trim();
  const categoryKey = createLootItemForm.querySelector("[data-custom-loot-category]").value || "wondrous-item";
  const rarityValue = createLootItemForm.querySelector("[data-custom-loot-rarity]").value || "common";
  const source = createLootItemForm.querySelector("[data-custom-loot-source]").value.trim() || "Домашний предмет";
  const description = createLootItemForm.querySelector("[data-custom-loot-description]").value.trim();

  return {
    id: `custom-loot-${crypto.randomUUID()}`,
    is_custom: true,
    name,
    category: capitalize(getMagicCategoryLabel(categoryKey)),
    category_key: categoryKey,
    rarity: capitalize(getMagicRarityLabel(rarityValue)),
    rarity_value: rarityValue,
    rarity_rank: getMagicRaritySortValue(rarityValue) + 1,
    description,
    attunement: Boolean(createLootItemForm.querySelector("[data-custom-loot-attunement]").checked),
    attunement_requirement: "",
    weapon: null,
    armor: null,
    weight: "0.000",
    weight_unit: "lb",
    source,
    source_display: source,
    source_key: "custom",
    publisher: "Домашняя база",
    license: "Локально сохранённая запись",
  };
}

function saveCustomLootItem(event) {
  event.preventDefault();
  const item = buildCustomLootItem();
  if (!item.name) {
    return;
  }

  const items = getCustomLootItems();
  items.unshift(item);
  setCustomLootItems(items.slice(0, 200));
  lootItems.unshift(item);
  lootItemsIndex.unshift(toLootIndexRow(item));
  lootItemsById.set(item.id, item);
  lootScope = "custom";
  document.querySelectorAll("[data-loot-scope]").forEach((button) => {
    button.classList.toggle("active", button.dataset.lootScope === lootScope);
  });
  setupLootCategoryOptions();
  setupLootListFilters();
  renderLootList();
  closeCreateLootItemModal();
}

function deleteCustomLootItemById(id) {
  setCustomLootItems(getCustomLootItems().filter((item) => item.id !== id));
  lootItems = lootItems.filter((item) => item.id !== id);
  lootItemsIndex = lootItemsIndex.filter((item) => item.id !== id);
  lootItemsById.delete(id);
  setupLootCategoryOptions();
  setupLootListFilters();
  renderLootList();
}

function deleteCurrentCustomLootItem() {
  if (!currentLootItem?.is_custom) {
    return;
  }
  deleteCustomLootItemById(currentLootItem.id);
  closeLootDetail();
}

function saveRandomMonsters() {
  if (!currentRandomMonsters.length) {
    return;
  }

  const saved = getSavedMonsters();
  currentRandomMonsters.forEach(({ monster, hp }) => {
    saved.unshift(toSavedMonster(monster, hp));
  });
  setSavedMonsters(saved.slice(0, 200));
  closeRandomMonsterModal();
  openPanel("library");
}

async function openRandomMonsterModal() {
  await loadBestiary();
  currentRandomMonsters = [];
  randomMonsterResults.innerHTML = "";
  saveRandomMonstersButton.disabled = true;
  randomMonsterModal.classList.remove("is-hidden");
}

function closeRandomMonsterModal() {
  randomMonsterModal.classList.add("is-hidden");
  currentRandomMonsters = [];
}

async function openCreateMonsterModal() {
  await loadBestiary();
  setupCustomTypeOptions();
  createMonsterForm?.reset();
  if (customTypeInput && !customTypeInput.value) {
    customTypeInput.value = getAvailableCreatureTypes()[0] || "";
  }
  createMonsterModal.classList.remove("is-hidden");
  createMonsterForm?.querySelector("[data-custom-name]")?.focus();
}

function closeCreateMonsterModal() {
  createMonsterModal.classList.add("is-hidden");
}

function buildCustomMonster() {
  const name = createMonsterForm.querySelector("[data-custom-name]").value.trim();
  const type = normalizeCreatureType(createMonsterForm.querySelector("[data-custom-type]").value) || "humanoid";
  const size = createMonsterForm.querySelector("[data-custom-size]").value;
  const cr = createMonsterForm.querySelector("[data-custom-cr]").value.trim() || "0";
  const hp = Math.max(1, Number(createMonsterForm.querySelector("[data-custom-hp]").value) || 1);
  const abilities = {};
  createMonsterForm.querySelectorAll("[data-custom-ability]").forEach((input) => {
    abilities[input.dataset.customAbility] = Math.min(Math.max(Number(input.value) || 10, 1), 30);
  });

  const modifiers = Object.fromEntries(Object.entries(abilities).map(([key, value]) => [key, abilityModifier(value)]));
  return {
    id: `custom-${crypto.randomUUID()}`,
    is_custom: true,
    name,
    name_ru: name,
    type,
    type_ru: getTypeLabel(type),
    size,
    size_ru: getCustomSizeLabel(size),
    alignment: "-",
    armor_class: Math.max(0, Number(createMonsterForm.querySelector("[data-custom-ac]").value) || 10),
    hit_points: {
      average: hp,
      formula: String(hp),
    },
    speed: {
      walk: Math.max(0, Number(createMonsterForm.querySelector("[data-custom-speed]").value) || 0),
    },
    cr,
    cr_value: parseCrValue(cr),
    xp: 0,
    abilities,
    modifiers,
    saving_throws: {},
    skills: {},
    senses: {
      passivePerception: 10 + modifiers.wis,
    },
    languages: [],
    damage: {
      resistances: [],
      immunities: [],
    },
    condition_immunities: [],
    traits: splitLines(createMonsterForm.querySelector("[data-custom-traits]").value),
    actions: splitLines(createMonsterForm.querySelector("[data-custom-actions]").value),
    bonus_actions: [],
    reactions: [],
    legendary_actions: [],
  };
}

function getCustomSizeLabel(size) {
  const labels = {
    tiny: "крошечный",
    small: "маленький",
    medium: "средний",
    large: "большой",
    huge: "огромный",
    gargantuan: "гигантский",
  };
  return labels[size] || size;
}

function saveCustomMonster(event) {
  event.preventDefault();
  const monster = buildCustomMonster();
  if (!monster.name) {
    return;
  }

  const monsters = getCustomMonsters();
  monsters.unshift(monster);
  setCustomMonsters(monsters.slice(0, 200));
  bestiaryMonsters.unshift(monster);
  bestiaryIndex.unshift(toMonsterIndexRow(monster));
  bestiaryById.set(monster.id, monster);
  bestiaryScope = "custom";
  document.querySelectorAll("[data-bestiary-scope]").forEach((button) => {
    button.classList.toggle("active", button.dataset.bestiaryScope === bestiaryScope);
  });
  setupBestiaryFilters();
  setupCustomTypeOptions();
  renderBestiary();
  closeCreateMonsterModal();
}

document.addEventListener("click", (event) => {
  const button = event.target.closest("button");
  const savedMonsterCard = event.target.closest("[data-open-saved-monster]");
  const savedPotionCard = event.target.closest("[data-open-saved-potion]");
  const savedSpellCard = event.target.closest("[data-open-saved-spell]");
  const savedLootCard = event.target.closest("[data-open-saved-loot]");
  const savedNoteCard = event.target.closest("[data-open-saved-note]");
  const potionResultCard = event.target.closest("[data-open-potion-result]");
  const lootResultCard = event.target.closest("[data-open-loot-result]");
  if (!button && !savedMonsterCard && !savedPotionCard && !savedSpellCard && !savedLootCard && !savedNoteCard && !potionResultCard && !lootResultCard) {
    return;
  }

  playClick();

  if (button?.dataset.libraryFilter) {
    libraryFilter = button.dataset.libraryFilter;
    applyLibraryFilter();
    return;
  }

  if (button?.dataset.deleteSavedMonster) {
    deleteSavedMonster(button.dataset.deleteSavedMonster);
    return;
  }

  if (button?.dataset.deleteSavedPotion) {
    setSavedPotions(getSavedPotions().filter((potion) => potion.id !== button.dataset.deleteSavedPotion));
    return;
  }

  if (button?.dataset.deleteSavedSpell) {
    deleteSavedSpell(button.dataset.deleteSavedSpell);
    return;
  }

  if (button?.dataset.deleteSavedLoot) {
    setSavedLoot(getSavedLoot().filter((loot) => loot.id !== button.dataset.deleteSavedLoot));
    return;
  }

  if (button?.dataset.deleteSavedNote) {
    deleteLibraryNote(button.dataset.deleteSavedNote);
    return;
  }

  if (button?.dataset.deleteCustomMonsterId) {
    deleteCustomMonsterById(button.dataset.deleteCustomMonsterId);
    return;
  }

  if (button?.dataset.deleteCustomLootId) {
    deleteCustomLootItemById(button.dataset.deleteCustomLootId);
    return;
  }

  if (!button && savedMonsterCard) {
    openSavedMonster(savedMonsterCard.dataset.openSavedMonster);
    return;
  }

  if (!button && savedPotionCard) {
    openSavedPotion(savedPotionCard.dataset.openSavedPotion);
    return;
  }

  if (!button && savedSpellCard) {
    openSavedSpell(savedSpellCard.dataset.openSavedSpell);
    return;
  }

  if (!button && savedLootCard) {
    openSavedLoot(savedLootCard.dataset.openSavedLoot);
    return;
  }

  if (!button && savedNoteCard) {
    openNoteEditor(savedNoteCard.dataset.openSavedNote);
    return;
  }

  if (!button && potionResultCard) {
    openPotionResult(potionResultCard.dataset.openPotionResult);
    return;
  }

  if (!button && lootResultCard) {
    openLootResult(lootResultCard.dataset.openLootResult);
    return;
  }

  if (button.dataset.panel) {
    openPanel(button.dataset.panel);
  }

  if (isDiceButton(button)) {
    openDiceModal();
  }

  if (button.dataset.themeChoice) {
    setTheme(button.dataset.themeChoice);
  }

  if (button.matches("[data-sound-toggle]")) {
    if (state.soundEnabled) {
      disableSound();
    } else {
      enableSound();
    }
  }

  if (button.matches("[data-enable-audio]")) {
    enableSound();
  }

  if (button.matches("[data-skip-audio]")) {
    disableSound();
    hideAudioGate();
  }

  if (button.matches("[data-close-dice]")) {
    closeDiceModal();
  }

  if (button.matches("[data-roll-dice]")) {
    rollDice();
  }

  if (button.matches("[data-reset-dice]")) {
    openDiceModal();
  }

  if (button.matches("[data-save-dice]")) {
    saveDiceRoll();
  }

  if (button.matches("[data-open-note-editor]")) {
    libraryFilter = "notes";
    openPanel("library");
    openNoteEditor();
  }

  if (button.matches("[data-close-note]")) {
    closeNoteEditor();
  }

  if (button.matches("[data-delete-note]")) {
    deleteLibraryNote(noteIdInput.value);
    closeNoteEditor();
  }

  if (button.dataset.deleteRoll) {
    deleteSavedRoll(button.dataset.deleteRoll);
  }

  if (button.dataset.bestiaryScope) {
    bestiaryScope = button.dataset.bestiaryScope;
    document.querySelectorAll("[data-bestiary-scope]").forEach((scopeButton) => {
      scopeButton.classList.toggle("active", scopeButton.dataset.bestiaryScope === bestiaryScope);
    });
    renderBestiary();
  }

  if (button.dataset.monsterId) {
    openMonsterModal(button.dataset.monsterId);
  }

  if (button.dataset.spellId) {
    openSpellModal(button.dataset.spellId);
  }

  if (button.dataset.lootItemId) {
    openLootItemModal(button.dataset.lootItemId);
  }

  if (button.matches("[data-close-monster]")) {
    closeMonsterModal();
  }

  if (button.matches("[data-save-current-monster]")) {
    saveCurrentMonster();
  }

  if (button.matches("[data-delete-custom-monster]")) {
    deleteCustomMonster();
  }

  if (button.matches("[data-close-spell]")) {
    closeSpellModal();
  }

  if (button.matches("[data-save-current-spell]")) {
    saveCurrentSpell();
  }

  if (button.dataset.lootScope) {
    lootScope = button.dataset.lootScope;
    document.querySelectorAll("[data-loot-scope]").forEach((scopeButton) => {
      scopeButton.classList.toggle("active", scopeButton.dataset.lootScope === lootScope);
    });
    renderLootList();
  }

  if (button.matches("[data-open-create-loot-item]")) {
    openCreateLootItemModal();
  }

  if (button.matches("[data-close-create-loot-item]")) {
    closeCreateLootItemModal();
  }

  if (button.matches("[data-save-current-loot-item]")) {
    saveCurrentLootItem();
  }

  if (button.matches("[data-delete-custom-loot-item]")) {
    deleteCurrentCustomLootItem();
  }

  if (button.matches("[data-open-random-monster]")) {
    openRandomMonsterModal();
  }

  if (button.matches("[data-close-random-monster]")) {
    closeRandomMonsterModal();
  }

  if (button.matches("[data-generate-random-monster]")) {
    generateRandomMonsters();
  }

  if (button.dataset.deleteRandomMonster) {
    deleteRandomMonster(button.dataset.deleteRandomMonster);
  }

  if (button.matches("[data-save-random-monsters]")) {
    saveRandomMonsters();
  }

  if (button.matches("[data-open-create-monster]")) {
    openCreateMonsterModal();
  }

  if (button.matches("[data-close-create-monster]")) {
    closeCreateMonsterModal();
  }

  if (button.matches("[data-open-potion-generator]")) {
    openPotionGenerator();
  }

  if (button.matches("[data-close-potion-generator]")) {
    closePotionGenerator();
  }

  if (button.matches("[data-close-potion-detail]")) {
    closePotionDetail();
  }

  if (button.matches("[data-generate-potions]")) {
    generatePotions();
  }

  if (button.dataset.deletePotionResult) {
    deletePotionResult(button.dataset.deletePotionResult);
  }

  if (button.matches("[data-save-potions]")) {
    saveGeneratedPotions();
  }

  if (button.matches("[data-open-loot-generator]")) {
    openLootGenerator();
  }

  if (button.matches("[data-open-random-loot-item]")) {
    openRandomLootItemGenerator();
  }

  if (button.matches("[data-close-loot-generator]")) {
    closeLootGenerator();
  }

  if (button.matches("[data-close-random-loot-item]")) {
    closeRandomLootItemGenerator();
  }

  if (button.matches("[data-close-loot-detail]")) {
    closeLootDetail();
  }

  if (button.matches("[data-generate-loot]")) {
    lootGeneratorMode = "reward";
    generateLoot();
  }

  if (button.matches("[data-generate-random-loot-item]")) {
    lootGeneratorMode = "item";
    generateLoot();
  }

  if (button.dataset.deleteLootResult) {
    deleteLootResult(button.dataset.deleteLootResult);
  }

  if (button.matches("[data-save-loot]")) {
    lootGeneratorMode = "reward";
    saveGeneratedLoot();
  }

  if (button.matches("[data-save-random-loot-item]")) {
    lootGeneratorMode = "item";
    saveGeneratedLoot();
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && !diceModal.classList.contains("is-hidden")) {
    closeDiceModal();
  }
  if (event.key === "Escape" && !monsterModal.classList.contains("is-hidden")) {
    closeMonsterModal();
  }
  if (event.key === "Escape" && !spellModal.classList.contains("is-hidden")) {
    closeSpellModal();
  }
  if (event.key === "Escape" && !randomMonsterModal.classList.contains("is-hidden")) {
    closeRandomMonsterModal();
  }
  if (event.key === "Escape" && !createMonsterModal.classList.contains("is-hidden")) {
    closeCreateMonsterModal();
  }
  if (event.key === "Escape" && !createLootItemModal.classList.contains("is-hidden")) {
    closeCreateLootItemModal();
  }
  if (event.key === "Escape" && !potionModal.classList.contains("is-hidden")) {
    closePotionGenerator();
  }
  if (event.key === "Escape" && !potionDetailModal.classList.contains("is-hidden")) {
    closePotionDetail();
  }
  if (event.key === "Escape" && !lootModal.classList.contains("is-hidden")) {
    closeLootGenerator();
  }
  if (event.key === "Escape" && !randomLootItemModal.classList.contains("is-hidden")) {
    closeRandomLootItemGenerator();
  }
  if (event.key === "Escape" && !lootDetailModal.classList.contains("is-hidden")) {
    closeLootDetail();
  }
  if (event.key === "Escape" && !noteModal.classList.contains("is-hidden")) {
    closeNoteEditor();
  }
});

volumeControl.addEventListener("input", (event) => {
  setVolume(Number(event.target.value));
});

[bestiarySearch, bestiaryType, bestiaryCr].forEach((control) => {
  control?.addEventListener("input", renderBestiary);
  control?.addEventListener("change", renderBestiary);
});

[spellsSearch, spellsLevel, spellsSchool, spellsClass, spellsTag].forEach((control) => {
  control?.addEventListener("input", renderSpells);
  control?.addEventListener("change", renderSpells);
});

[lootSearch, lootRarityInput, lootListCategoryInput].forEach((control) => {
  control?.addEventListener("input", renderLootList);
  control?.addEventListener("change", renderLootList);
});

createMonsterForm?.addEventListener("submit", saveCustomMonster);
createLootItemForm?.addEventListener("submit", saveCustomLootItem);
noteForm?.addEventListener("submit", saveLibraryNote);

setVolume(state.volume);
volumeControl.value = String(state.volume);
setTheme(state.theme);
updateSoundButton();
updateSavedRollsCount();
renderLibraryPlaceholders();
renderLibraryRolls();
renderLibraryMonsters();
renderLibraryPotions();
renderLibrarySpells();
renderLibraryLoot();
renderLibraryNotes();

if (state.soundEnabled) {
  music.play().then(hideAudioGate).catch(showAudioGate);
} else {
  showAudioGate();
}
