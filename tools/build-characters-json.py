import json
import math
import re
import shutil
from pathlib import Path

from pypdf import PdfReader


ROOT = Path(__file__).resolve().parents[1]
CACHE_DIR = ROOT / ".cache"
CLASSES_PDF = CACHE_DIR / "Klassy.pdf"
RACES_PDF = CACHE_DIR / "Rasy.pdf"
OUTPUT_PATH = ROOT / "public" / "data" / "generators" / "characters.json"

SOURCE_CLASSES_PATH = Path.home() / "Dropbox" / "ПК" / "Desktop" / "Copilot PDF" / "Klassy.pdf"
SOURCE_RACES_PATH = Path.home() / "Dropbox" / "ПК" / "Desktop" / "Copilot PDF" / "Rasy.pdf"

ABILITIES = [
    {"id": "strength", "name": "Сила", "short": "Сил"},
    {"id": "dexterity", "name": "Ловкость", "short": "Лов"},
    {"id": "constitution", "name": "Телосложение", "short": "Тел"},
    {"id": "intelligence", "name": "Интеллект", "short": "Инт"},
    {"id": "wisdom", "name": "Мудрость", "short": "Мдр"},
    {"id": "charisma", "name": "Харизма", "short": "Хар"},
]

SKILLS = [
    {"id": "acrobatics", "name": "Акробатика", "ability": "dexterity"},
    {"id": "animal-handling", "name": "Уход за животными", "ability": "wisdom"},
    {"id": "arcana", "name": "Магия", "ability": "intelligence"},
    {"id": "athletics", "name": "Атлетика", "ability": "strength"},
    {"id": "deception", "name": "Обман", "ability": "charisma"},
    {"id": "history", "name": "История", "ability": "intelligence"},
    {"id": "insight", "name": "Проницательность", "ability": "wisdom"},
    {"id": "intimidation", "name": "Запугивание", "ability": "charisma"},
    {"id": "investigation", "name": "Анализ", "ability": "intelligence"},
    {"id": "medicine", "name": "Медицина", "ability": "wisdom"},
    {"id": "nature", "name": "Природа", "ability": "intelligence"},
    {"id": "perception", "name": "Внимательность", "ability": "wisdom"},
    {"id": "performance", "name": "Выступление", "ability": "charisma"},
    {"id": "persuasion", "name": "Убеждение", "ability": "charisma"},
    {"id": "religion", "name": "Религия", "ability": "intelligence"},
    {"id": "sleight-of-hand", "name": "Ловкость рук", "ability": "dexterity"},
    {"id": "stealth", "name": "Скрытность", "ability": "dexterity"},
    {"id": "survival", "name": "Выживание", "ability": "wisdom"},
]

ADVANCEMENT = [
    (1, 0, 2),
    (2, 300, 2),
    (3, 900, 2),
    (4, 2700, 2),
    (5, 6500, 3),
    (6, 14000, 3),
    (7, 23000, 3),
    (8, 34000, 3),
    (9, 48000, 4),
    (10, 64000, 4),
    (11, 85000, 4),
    (12, 100000, 4),
    (13, 120000, 5),
    (14, 140000, 5),
    (15, 165000, 5),
    (16, 195000, 5),
    (17, 225000, 6),
    (18, 265000, 6),
    (19, 305000, 6),
    (20, 355000, 6),
]

CLASS_META = {
    "Бард": {"hit_die": "к8", "primary_abilities": ["charisma"], "saving_throws": ["dexterity", "charisma"]},
    "Варвар": {"hit_die": "к12", "primary_abilities": ["strength"], "saving_throws": ["strength", "constitution"]},
    "Воин": {"hit_die": "к10", "primary_abilities": ["strength", "dexterity"], "saving_throws": ["strength", "constitution"]},
    "Волшебник": {"hit_die": "к6", "primary_abilities": ["intelligence"], "saving_throws": ["intelligence", "wisdom"]},
    "Друид": {"hit_die": "к8", "primary_abilities": ["wisdom"], "saving_throws": ["intelligence", "wisdom"]},
    "Жрец": {"hit_die": "к8", "primary_abilities": ["wisdom"], "saving_throws": ["wisdom", "charisma"]},
    "Изобретатель": {"hit_die": "к8", "primary_abilities": ["intelligence"], "saving_throws": ["constitution", "intelligence"]},
    "Колдун": {"hit_die": "к8", "primary_abilities": ["charisma"], "saving_throws": ["wisdom", "charisma"]},
    "Монах": {"hit_die": "к8", "primary_abilities": ["dexterity", "wisdom"], "saving_throws": ["strength", "dexterity"]},
    "Паладин": {"hit_die": "к10", "primary_abilities": ["strength", "charisma"], "saving_throws": ["wisdom", "charisma"]},
    "Плут": {"hit_die": "к8", "primary_abilities": ["dexterity"], "saving_throws": ["dexterity", "intelligence"]},
    "Следопыт": {"hit_die": "к10", "primary_abilities": ["dexterity", "wisdom"], "saving_throws": ["strength", "dexterity"]},
    "Чародей": {"hit_die": "к6", "primary_abilities": ["charisma"], "saving_throws": ["constitution", "charisma"]},
}

CLASSES = [
    {
        "name": "Бард",
        "page": 7,
        "archetype_label": "Коллегия бардов",
        "archetypes": [
            ("Коллегия доблести", "PHB", 14),
            ("Коллегия знаний", "PHB", 15),
            ("Коллегия мечей", "XGE", 16),
            ("Коллегия очарования", "XGE", 17),
            ("Коллегия шёпотов", "XGE", 18),
            ("Коллегия красноречия", "TCE", 20),
            ("Коллегия созидания", "TCE", 21),
        ],
    },
    {
        "name": "Варвар",
        "page": 23,
        "archetype_label": "Путь варвара",
        "archetypes": [
            ("Путь берсерка", "PHB", 28),
            ("Путь тотемного воина", "PHB", 29),
            ("Путь буревестника", "XGE", 30),
            ("Путь предка-хранителя", "XGE", 32),
            ("Путь фанатика", "XGE", 33),
            ("Путь бушующего в бою", "SCAG", 34),
            ("Путь дикой магии", "TCE", 35),
            ("Путь зверя", "TCE", 37),
        ],
    },
    {
        "name": "Воин",
        "page": 39,
        "archetype_label": "Воинский архетип",
        "archetypes": [
            ("Мастер боевых искусств", "PHB", 45),
            ("Мистический рыцарь", "PHB", 47),
            ("Чемпион", "PHB", 49),
            ("Кавалерист", "XGE", 49),
            ("Мистический лучник", "XGE", 51),
            ("Самурай", "XGE", 53),
            ("Рыцарь Пурпурного Дракона (Баннерет)", "SCAG", 54),
            ("Рыцарь Эхо", "EGW", 55),
            ("Пси-воин", "TCE", 56),
            ("Рунный рыцарь", "TCE", 57),
        ],
    },
    {
        "name": "Волшебник",
        "page": 60,
        "archetype_label": "Магическая традиция",
        "archetypes": [
            ("Школа воплощения", "PHB", 65),
            ("Школа вызова", "PHB", 66),
            ("Школа иллюзии", "PHB", 67),
            ("Школа некромантии", "PHB", 68),
            ("Школа ограждения", "PHB", 69),
            ("Школа очарования", "PHB", 70),
            ("Школа преобразования", "PHB", 71),
            ("Школа прорицания", "PHB", 72),
            ("Военная магия", "XGE", 73),
            ("Песнь Клинка", "TCE", 74),
            ("Хрономаг", "EGW", 75),
            ("Гравитург", "EGW", 76),
            ("Орден писцов", "TCE", 77),
        ],
    },
    {
        "name": "Друид",
        "page": 79,
        "archetype_label": "Круг друида",
        "archetypes": [
            ("Круг земли", "PHB", 85),
            ("Круг луны", "PHB", 87),
            ("Круг пастыря", "XGE", 88),
            ("Круг снов", "XGE", 89),
            ("Круг спор", "TCE", 90),
            ("Круг звёзд", "TCE", 92),
            ("Круг Дикого огня", "TCE", 93),
        ],
    },
    {
        "name": "Жрец",
        "page": 98,
        "archetype_label": "Божественный домен",
        "archetypes": [
            ("Домен бури", "PHB", 105),
            ("Домен войны", "PHB", 106),
            ("Домен жизни", "PHB", 107),
            ("Домен знания", "PHB", 108),
            ("Домен обмана", "PHB", 109),
            ("Домен природы", "PHB", 110),
            ("Домен света", "PHB", 111),
            ("Домен кузни", "XGE", 112),
            ("Домен упокоения", "XGE", 113),
            ("Домен порядка", "TCE", 114),
            ("Домен смерти", "DMG", 116),
            ("Домен магии", "SCAG", 117),
            ("Домен сумерек", "TCE", 118),
            ("Домен мира", "TCE", 119),
        ],
    },
    {
        "name": "Изобретатель",
        "page": 121,
        "archetype_label": "Специальность изобретателя",
        "archetypes": [
            ("Алхимик", "TCE", 125),
            ("Артиллерист", "TCE", 127),
            ("Боевой кузнец", "TCE", 128),
            ("Бронник", "TCE", 130),
        ],
    },
    {
        "name": "Колдун",
        "page": 136,
        "archetype_label": "Покровитель",
        "archetypes": [
            ("Архифея", "PHB", 143),
            ("Исчадие", "PHB", 144),
            ("Великий древний", "PHB", 145),
            ("Бессмертные", "SCAG", 146),
            ("Ведьмовской клинок", "XGE", 147),
            ("Небожитель", "XGE", 149),
            ("Гений", "TCE", 150),
            ("Бездонный", "TCE", 152),
        ],
    },
    {
        "name": "Монах",
        "page": 159,
        "archetype_label": "Монашеская традиция",
        "archetypes": [
            ("Путь открытой ладони", "PHB", 166),
            ("Путь тени", "PHB", 167),
            ("Путь четырёх стихий", "PHB", 167),
            ("Путь кенсэя", "XGE", 169),
            ("Путь пьяного мастера", "XGE", 170),
            ("Путь долгой смерти", "SCAG", 171),
            ("Путь солнечной души", "SCAG", 172),
            ("Путь милосердия", "TCE", 173),
            ("Путь астрального тела", "TCE", 174),
        ],
    },
    {
        "name": "Паладин",
        "page": 176,
        "archetype_label": "Священная клятва",
        "archetypes": [
            ("Клятва преданности", "PHB", 184),
            ("Клятва Древних", "PHB", 185),
            ("Клятва мести", "PHB", 186),
            ("Клятва искупления", "XGE", 187),
            ("Клятва покорения", "XGE", 189),
            ("Клятва Короны", "SCAG", 190),
            ("Клятва славы", "TCE", 191),
            ("Клятва Хранителей", "TCE", 192),
            ("Клятвопреступник", "DMG", 194),
        ],
    },
    {
        "name": "Плут",
        "page": 196,
        "archetype_label": "Плутовской архетип",
        "archetypes": [
            ("Вор", "PHB", 201),
            ("Убийца", "PHB", 202),
            ("Мистический ловкач", "PHB", 203),
            ("Дуэлянт", "XGE", 204),
            ("Комбинатор", "XGE", 205),
            ("Скаут", "XGE", 206),
            ("Сыщик", "XGE", 206),
            ("Фантом", "TCE", 208),
            ("Клинок души", "TCE", 209),
        ],
    },
    {
        "name": "Следопыт",
        "page": 211,
        "archetype_label": "Архетип следопыта",
        "archetypes": [
            ("Охотник", "PHB", 219),
            ("Повелитель зверей", "PHB", 220),
            ("Странник горизонта", "XGE", 222),
            ("Сумрачный охотник", "XGE", 223),
            ("Убийца монстров", "XGE", 224),
            ("Странник фей", "TCE", 225),
            ("Хранитель роя", "TCE", 226),
        ],
    },
    {
        "name": "Чародей",
        "page": 228,
        "archetype_label": "Чародейское происхождение",
        "archetypes": [
            ("Наследие драконьей крови", "PHB", 235),
            ("Дикая магия", "PHB", 236),
            ("Божественная душа", "XGE", 239),
            ("Теневая магия", "XGE", 240),
            ("Штормовое колдовство", "XGE", 241),
            ("Аберрантный разум", "TCE", 243),
            ("Заводная душа", "TCE", 245),
        ],
    },
]

RACES = [
    ("Ааракокра", 4),
    ("Аасимар", 7),
    ("Ведалкин", 10),
    ("Вердан", 12),
    ("Гибриды Симиков", 15),
    ("Гиты", 17),
    ("Гном", 21),
    ("Гоблиноиды", 28),
    ("Голиаф", 32),
    ("Грунг", 34),
    ("Дварф", 36),
    ("Дженази", 42),
    ("Драконорожденный", 47),
    ("Изменяющийся", 51),
    ("Калаштар", 53),
    ("Кенку", 55),
    ("Кентавр", 58),
    ("Кованый", 60),
    ("Кобольд", 62),
    ("Леонин", 63),
    ("Локсодон", 65),
    ("Локата", 67),
    ("Людоящер", 68),
    ("Минотавр", 71),
    ("Орк", 73),
    ("Полуорк", 75),
    ("Полурослик", 79),
    ("Полуэльф", 86),
    ("Сатир", 91),
    ("Табакси", 93),
    ("Тифлинг", 96),
    ("Тортл", 107),
    ("Тритон", 109),
    ("Фирболг", 113),
    ("Человек", 117),
    ("Шифтер", 126),
    ("Эльф", 129),
    ("Юань-ти", 145),
]

TRANSLIT = {
    "а": "a",
    "б": "b",
    "в": "v",
    "г": "g",
    "д": "d",
    "е": "e",
    "ё": "e",
    "ж": "zh",
    "з": "z",
    "и": "i",
    "й": "y",
    "к": "k",
    "л": "l",
    "м": "m",
    "н": "n",
    "о": "o",
    "п": "p",
    "р": "r",
    "с": "s",
    "т": "t",
    "у": "u",
    "ф": "f",
    "х": "h",
    "ц": "ts",
    "ч": "ch",
    "ш": "sh",
    "щ": "sch",
    "ъ": "",
    "ы": "y",
    "ь": "",
    "э": "e",
    "ю": "yu",
    "я": "ya",
}


def slugify(value):
    value = "".join(TRANSLIT.get(char.lower(), char.lower()) for char in value)
    value = re.sub(r"[^a-z0-9]+", "-", value)
    return value.strip("-")


def normalize_ws(value):
    return re.sub(r"\s+", " ", value or "").strip()


def clean_text(value):
    value = value.replace("\u00ad", "")
    value = value.replace("\xa0", " ")
    value = value.replace("−", "-").replace("–", "-").replace("—", "-")
    value = re.sub(r"([А-ЯЁа-яёA-Za-z])- *\n *([а-яёa-z])", r"\1\2", value)
    value = re.sub(r"[ \t]+", " ", value)
    value = re.sub(r"\n{3,}", "\n\n", value)
    lines = [line.strip() for line in value.splitlines()]
    return "\n".join(line for line in lines if line).strip()


def compact_title(value):
    value = normalize_ws(value)
    value = re.sub(r"\s*\([^)]*\)$", "", value).strip()
    return value


def dedupe_title_line(line):
    original = normalize_ws(line)
    if not original or len(original) > 120:
        return None
    cleaned = re.sub(r"^[0-9]+[.)]?\s*", "", original).strip()
    for pattern in [
        r"^(.{3,70})\1$",
        r"^(.{3,70})\.\s*\1\.?$",
        r"^(.{3,70})\s+\1$",
        r"^(.{3,70})\1\.\s*\.?$",
    ]:
        match = re.match(pattern, cleaned)
        if match:
            title = normalize_ws(match.group(1)).strip(" .:")
            if re.search(r"[А-ЯЁа-яё]", title):
                return title
    return None


def ensure_local_pdf(cache_path, source_path):
    CACHE_DIR.mkdir(parents=True, exist_ok=True)
    if cache_path.exists():
        return
    if not source_path.exists():
        raise FileNotFoundError(f"Не найден PDF: {cache_path} или {source_path}")
    shutil.copyfile(source_path, cache_path)


def read_pages(reader, start_page, end_page):
    chunks = []
    for page_number in range(start_page, end_page + 1):
        if page_number < 1 or page_number > len(reader.pages):
            continue
        text = reader.pages[page_number - 1].extract_text() or ""
        chunks.append(text)
    return clean_text("\n".join(chunks))


def find_heading_position(text, heading, start=0):
    heading = heading.strip()
    variants = [
        heading,
        heading.replace("ё", "е"),
        re.sub(r"\s*\([^)]*\)", "", heading).strip(),
    ]
    for variant in variants:
        if not variant:
            continue
        index = text.find(variant, start)
        if index >= 0:
            return index
    return -1


def page_marker_position(text, page):
    match = re.search(rf"(?:^|\n){page}\n", text)
    return match.start() if match else -1


def source_book(name):
    match = re.search(r"\(([^)]+)\)", name)
    return match.group(1) if match else None


def hit_die_sides(hit_die):
    match = re.search(r"\d+", hit_die or "")
    return int(match.group(0)) if match else None


def hit_points_from_die(hit_die):
    sides = hit_die_sides(hit_die)
    if not sides:
        return {}
    return {
        "level_1": f"{sides} + модификатор Телосложения",
        "per_level_fixed_after_1": f"{math.floor(sides / 2) + 1} + модификатор Телосложения",
        "per_level_roll_after_1": f"1{hit_die} + модификатор Телосложения, минимум 1",
    }


def extract_named_field(text, names):
    for name in names:
        match = re.search(rf"{re.escape(name)}\s*{re.escape(name)}?\s*:\s*([^\n]+(?:\n(?![А-ЯЁа-яёA-Za-z ]{{2,40}}\s*:).+)*)", text)
        if match:
            return normalize_ws(match.group(1))
    return ""


def clean_feature_name(value):
    value = normalize_ws(value)
    value = re.sub(r"^(?:\d+к\d+|\d+|[-+]\d+|[-+]\d+\s*фт\.?|фт\.?|[-—])\s+", "", value).strip()
    value = re.sub(r"\([^)]*\)", "", value)
    value = value.replace("Умение коллегии бардов", "")
    value = value.replace("Умение специальности изобретателя", "")
    value = value.replace("Умение архетипа следопыта", "")
    value = value.replace("Умение божественного домена", "")
    value = re.sub(r"Умение .*", "", value)
    return value.strip(" ,-")


def trim_progression_columns(value):
    value = normalize_ws(value)
    previous = None
    while previous != value:
        previous = value
        value = re.sub(r"^(?:\d+к\d+|\d+|[-+]\d+|[-+]\d+\s*фт\.?|фт\.?|[-—])\s+", "", value).strip()
    previous = None
    while previous != value:
        previous = value
        value = re.sub(r"(?:\s+(?:\d+|-|—)){2,}$", "", value).strip()
        value = re.sub(r"\s+\d+\s+\+\d+$", "", value).strip()
        value = re.sub(r"\s+Неограниченно\s+\+\d+$", "", value).strip()
    return value


def extract_progression_table(base_text, class_name):
    row_starts = list(re.finditer(r"(?m)^\d{1,2} \+\d+ ", base_text))
    if row_starts:
        first = row_starts[0]
        last = row_starts[-1]
        header_start = max(0, first.start() - 800)
        line_end = base_text.find("\n", last.start())
        end = line_end if line_end > last.start() else min(len(base_text), last.start() + 800)
        return clean_text(base_text[header_start:end])
    markers = ["Быстрое создание", f"Создание {class_name}", "Классовые умения"]
    cut_points = [base_text.find(marker) for marker in markers if base_text.find(marker) > 0]
    end = min(cut_points) if cut_points else min(len(base_text), 8000)
    return clean_text(base_text[:end])


def parse_progression_rows(table_text):
    row_pattern = re.compile(r"(?m)^(\d{1,2}) \+(\d+) ([\s\S]*?)(?=^\d{1,2} \+\d+ |\nБыстрое создание|\nСоздание |\nКлассовые умения|$)")
    rows = []
    for match in row_pattern.finditer(table_text):
        level = int(match.group(1))
        proficiency = int(match.group(2))
        raw = clean_text(match.group(3))
        feature_text = trim_progression_columns(raw)
        features = [clean_feature_name(part) for part in re.split(r",|\n", feature_text)]
        features = [feature for feature in features if feature and feature not in {"-", "—"}]
        rows.append(
            {
                "level": level,
                "proficiency_bonus": proficiency,
                "features": features,
                "raw": raw,
            }
        )
    return rows


def feature_levels_from_progression(rows):
    levels = {}
    for row in rows:
        for feature in row["features"]:
            key = compact_title(feature)
            if not key:
                continue
            levels.setdefault(key, []).append(row["level"])
    return levels


def locate_feature_titles(area, feature_names):
    positions = []
    used_positions = set()
    for name in feature_names:
        base = compact_title(name)
        if not base or len(base) < 3:
            continue
        index = area.find(base)
        if index >= 0 and index not in used_positions:
            positions.append((index, base))
            used_positions.add(index)
    positions.sort()
    return positions


def infer_levels(text):
    patterns = [
        r"(\d{1,2})[- ]?(?:го|й|м|ом)? уровня",
        r"(\d{1,2})м уровне",
        r"(\d{1,2}) уровне",
        r"Начиная с(?:о)? (\d{1,2})",
        r"На (\d{1,2}) уровне",
        r"К (\d{1,2}) уровню",
        r"При достижении (\d{1,2})",
    ]
    levels = []
    for pattern in patterns:
        for match in re.finditer(pattern, text, re.IGNORECASE):
            level = int(match.group(1))
            if 1 <= level <= 20 and level not in levels:
                levels.append(level)
    return levels


def extract_class_feature_blocks(base_text, progression_rows):
    area_start = base_text.find("Классовые умения")
    area = base_text[area_start:] if area_start >= 0 else base_text
    levels_by_name = feature_levels_from_progression(progression_rows)
    positions = locate_feature_titles(area, levels_by_name.keys())
    features = []
    for index, (position, name) in enumerate(positions):
        end = positions[index + 1][0] if index + 1 < len(positions) else len(area)
        description = clean_text(area[position:end])
        levels = levels_by_name.get(name, infer_levels(description))
        features.append(
            {
                "id": slugify(name),
                "name": name,
                "levels": sorted(set(levels)),
                "description": description,
            }
        )

    existing = {feature["name"] for feature in features}
    for name, levels in levels_by_name.items():
        if name not in existing:
            features.append({"id": slugify(name), "name": name, "levels": sorted(set(levels)), "description": ""})
    features.sort(key=lambda item: (item["levels"][0] if item["levels"] else 99, item["name"]))
    return features


def split_embedded_class_features(features):
    split_rules = {
        "Безрассудная атака": [
            ("Чувство опасности", [2]),
            ("Первобытное знание (TCE)", [3, 10]),
        ],
    }
    result = []
    for feature in features:
        splits = split_rules.get(feature["name"])
        if not splits:
            result.append(feature)
            continue

        description = feature.get("description", "")
        markers = []
        for title, levels in splits:
            position = description.find(title + title)
            if position < 0:
                position = description.find(title)
            if position > 0:
                markers.append((position, title, levels))

        if not markers:
            result.append(feature)
            continue

        markers.sort()
        original = dict(feature)
        original["description"] = description[: markers[0][0]].strip()
        result.append(original)

        for index, (position, title, levels) in enumerate(markers):
            end = markers[index + 1][0] if index + 1 < len(markers) else len(description)
            block = description[position:end].strip()
            result.append(
                {
                    "id": slugify(title),
                    "name": title,
                    "levels": levels,
                    "description": block,
                }
            )

    result.sort(key=lambda item: (item["levels"][0] if item["levels"] else 99, item["name"]))
    return result


def group_features_by_level(features):
    grouped = {str(level): [] for level in range(1, 21)}
    for feature in features:
        for level in feature.get("levels", []):
            grouped.setdefault(str(level), []).append(feature["name"])
    return grouped


def extract_archetype_features(section):
    lines = section.splitlines()
    starts = []
    for index, line in enumerate(lines[:-1]):
        title = dedupe_title_line(line)
        if not title:
            continue
        meta = " ".join(lines[index + offset] for offset in range(1, min(4, len(lines) - index)))
        if re.search(r"\bумение\b", meta, re.IGNORECASE) and re.search(r"уров", meta, re.IGNORECASE):
            levels = infer_levels(meta)
            starts.append((index, title, levels))

    features = []
    for item_index, (line_index, title, levels) in enumerate(starts):
        next_line = starts[item_index + 1][0] if item_index + 1 < len(starts) else len(lines)
        block = clean_text("\n".join(lines[line_index:next_line]))
        features.append(
            {
                "id": slugify(title),
                "name": title,
                "levels": sorted(set(levels)),
                "description": block,
            }
        )
    return features


def extract_archetypes(class_text, archetype_entries):
    located = []
    for name, book, page in archetype_entries:
        lower_bound = page_marker_position(class_text, page)
        position = find_heading_position(class_text, name, max(0, lower_bound))
        located.append({"name": name, "book": book, "page": page, "position": position})
    found_positions = [entry["position"] for entry in located if entry["position"] >= 0]
    for entry in located:
        if entry["position"] < 0:
            entry["position"] = min(found_positions) if found_positions else 0
    located.sort(key=lambda entry: (entry["position"], entry["page"], entry["name"]))

    archetypes = []
    for index, entry in enumerate(located):
        start = entry["position"]
        end = located[index + 1]["position"] if index + 1 < len(located) else len(class_text)
        section = clean_text(class_text[start:end])
        features = extract_archetype_features(section)
        archetypes.append(
            {
                "id": slugify(entry["name"]),
                "name": entry["name"],
                "source_book": entry["book"],
                "source_page": entry["page"],
                "features": features,
                "features_by_level": group_features_by_level(features),
                "rules_text": section,
            }
        )
    return archetypes


def extract_class_basics(base_text, meta):
    class_features_start = base_text.find("Классовые умения")
    basics_text = base_text[class_features_start:] if class_features_start >= 0 else base_text
    hit_die = extract_named_field(basics_text, ["Кость Хитов", "Кость хитов"]) or meta.get("hit_die", "")
    return {
        "hit_die": meta.get("hit_die") or hit_die,
        "hit_points": hit_points_from_die(meta.get("hit_die") or hit_die),
        "armor_proficiencies": extract_named_field(basics_text, ["Доспехи"]),
        "weapon_proficiencies": extract_named_field(basics_text, ["Оружие"]),
        "tool_proficiencies": extract_named_field(basics_text, ["Инструменты"]),
        "saving_throw_proficiencies_text": extract_named_field(basics_text, ["Спасброски"]),
        "skill_proficiencies_text": extract_named_field(basics_text, ["Навыки"]),
        "equipment_text": extract_named_field(basics_text, ["Снаряжение"]),
    }


def build_classes(reader):
    classes = []
    for index, item in enumerate(CLASSES):
        next_start = CLASSES[index + 1]["page"] if index + 1 < len(CLASSES) else 249
        section = read_pages(reader, item["page"], next_start - 1)
        first_archetype_page = item["archetypes"][0][2]
        archetype_lower_bound = max(0, page_marker_position(section, first_archetype_page))
        archetypes_start = min(
            (
                find_heading_position(section, name, archetype_lower_bound)
                for name, _, _ in item["archetypes"]
                if find_heading_position(section, name, archetype_lower_bound) >= 0
            ),
            default=len(section),
        )
        base_text = clean_text(section[:archetypes_start])
        progression_table = extract_progression_table(base_text, item["name"])
        progression_rows = parse_progression_rows(progression_table)
        features = extract_class_feature_blocks(base_text, progression_rows)
        features = split_embedded_class_features(features)
        meta = CLASS_META[item["name"]]
        basics = extract_class_basics(base_text, meta)
        classes.append(
            {
                "id": slugify(item["name"]),
                "name": item["name"],
                "source_page": item["page"],
                "page_range": {"start": item["page"], "end": next_start - 1},
                "archetype_label": item["archetype_label"],
                "primary_abilities": meta["primary_abilities"],
                "saving_throw_proficiencies": meta["saving_throws"],
                **basics,
                "progression": progression_rows,
                "progression_table_text": progression_table,
                "features": features,
                "features_by_level": group_features_by_level(features),
                "archetypes": extract_archetypes(section, item["archetypes"]),
                "rules_text": section,
            }
        )
    return classes


def find_race_features_start(section):
    matches = list(re.finditer(r"Особенности [А-ЯЁа-яё -]+", section))
    if not matches:
        matches = list(re.finditer(r"[Рр]асовые особенности|Птичьи особенности", section))
    if not matches:
        return 0
    for match in matches:
        preview = section[match.start() : match.start() + 320].lower()
        if any(marker in preview for marker in ["ваш персонаж", "у вас,", "обладает следующ", "расовыми особенностями"]):
            return match.start()
    return matches[0].start()


def repeated_heading_matches(text):
    pattern = re.compile(r"(?P<title>[А-ЯЁ][А-ЯЁа-яёA-Za-z0-9 «»\"(),/-]{2,65}?)(?:\.|:)?\s*(?P=title)(?:\.|:)?")
    matches = []
    for match in pattern.finditer(text):
        title = normalize_ws(match.group("title")).strip(" .:")
        if len(title) < 3 or len(title) > 65:
            continue
        if title.lower() in {"содержание", "таблица", "раса"}:
            continue
        lowered = title.lower()
        if "имена" in lowered or "особенности" in lowered:
            continue
        matches.append((match.start(), match.end(), title))
    deduped = []
    last_start = -1
    for item in matches:
        if item[0] == last_start:
            continue
        deduped.append(item)
        last_start = item[0]
    return deduped


def parse_ability_increases(text):
    ability_aliases = {
        "Сил": "strength",
        "Ловк": "dexterity",
        "Телослож": "constitution",
        "Интеллект": "intelligence",
        "Мудрост": "wisdom",
        "Харизм": "charisma",
    }
    increases = []
    for label, ability in ability_aliases.items():
        pattern = rf"{label}[а-яё ]* увеличивается на (\d+)"
        for match in re.finditer(pattern, text, re.IGNORECASE):
            increases.append({"ability": ability, "amount": int(match.group(1))})
    return increases


def extract_race_traits(section):
    start = find_race_features_start(section)
    area = clean_text(section[start:])
    matches = repeated_heading_matches(area)
    traits = []
    for index, (start_pos, end_pos, title) in enumerate(matches):
        next_start = matches[index + 1][0] if index + 1 < len(matches) else len(area)
        description = clean_text(area[end_pos:next_start])
        if len(description) < 8:
            continue
        traits.append(
            {
                "id": slugify(title),
                "name": title,
                "description": description,
            }
        )
    return traits, area


def extract_common_race_fields(traits):
    by_name = {trait["name"].lower(): trait["description"] for trait in traits}
    return {
        "ability_score_increases": parse_ability_increases(by_name.get("увеличение характеристик", "")),
        "age": by_name.get("возраст", ""),
        "alignment": by_name.get("мировоззрение", ""),
        "size": by_name.get("размер", ""),
        "speed": by_name.get("скорость", ""),
        "languages": by_name.get("языки", by_name.get("язык", "")),
    }


def extract_race_subsections(section, traits):
    trait_names = {trait["name"] for trait in traits}
    skip = trait_names | {"Особенности", "Имена", "Подрасы", "Варианты"}
    subsections = []
    for index, line in enumerate(section.splitlines()):
        title = dedupe_title_line(line)
        if not title or title in skip:
            continue
        if any(word in title.lower() for word in ["особенности", "имена", "таблица", "сводка"]):
            continue
        if 3 <= len(title) <= 55:
            subsections.append({"id": slugify(title), "name": title, "line": index + 1})
    unique = []
    seen = set()
    for item in subsections:
        if item["name"] in seen:
            continue
        unique.append(item)
        seen.add(item["name"])
    return unique[:30]


def build_races(reader):
    races = []
    for index, (name, printed_page) in enumerate(RACES):
        next_printed = RACES[index + 1][1] if index + 1 < len(RACES) else 148
        start_page = printed_page + 2
        end_page = next_printed + 1
        section = read_pages(reader, start_page, end_page)
        traits, traits_text = extract_race_traits(section)
        races.append(
            {
                "id": slugify(name),
                "name": name,
                "source_page": printed_page,
                "pdf_page_range": {"start": start_page, "end": end_page},
                **extract_common_race_fields(traits),
                "traits": traits,
                "subsections": extract_race_subsections(section, traits),
                "traits_text": traits_text,
                "rules_text": section,
            }
        )
    return races


def build_data():
    ensure_local_pdf(CLASSES_PDF, SOURCE_CLASSES_PATH)
    ensure_local_pdf(RACES_PDF, SOURCE_RACES_PATH)
    class_reader = PdfReader(str(CLASSES_PDF))
    race_reader = PdfReader(str(RACES_PDF))
    classes = build_classes(class_reader)
    races = build_races(race_reader)

    return {
        "schema_version": 2,
        "generated_from": {
            "classes_pdf": {
                "title": "Klassy.pdf",
                "source_path": str(SOURCE_CLASSES_PATH),
                "cached_path": str(CLASSES_PDF),
                "pages": len(class_reader.pages),
            },
            "races_pdf": {
                "title": "Rasy.pdf",
                "source_path": str(SOURCE_RACES_PATH),
                "cached_path": str(RACES_PDF),
                "pages": len(race_reader.pages),
            },
            "notes": [
                "Данные извлечены из пользовательских PDF с русскоязычными материалами D&D 5e.",
                "Полный текст правил по классам, архетипам и расам сохранен в rules_text; структурированные поля подготовлены для генератора персонажей.",
            ],
        },
        "character_model": {
            "creation_steps": [
                "choose_class",
                "choose_archetype_when_level_allows",
                "choose_race_or_origin",
                "assign_ability_scores",
                "choose_background",
                "fill_proficiencies_features_spells_equipment",
            ],
            "sheet_numbers": [
                "ability_scores",
                "ability_modifiers",
                "proficiency_bonus",
                "saving_throws",
                "skills",
                "passive_perception",
                "hit_points",
                "hit_dice",
                "initiative",
                "armor_class",
                "weapon_attack_bonuses",
                "spell_save_dc",
                "spell_attack_bonus",
                "spell_slots",
                "known_spells",
                "prepared_spells",
            ],
        },
        "abilities": {
            "items": ABILITIES,
            "modifier_formula": "floor((score - 10) / 2)",
            "standard_array": [15, 14, 13, 12, 10, 8],
            "point_buy": {
                "budget": 27,
                "costs": {"8": 0, "9": 1, "10": 2, "11": 3, "12": 4, "13": 5, "14": 7, "15": 9},
            },
        },
        "skills": SKILLS,
        "advancement": {
            "levels": [
                {
                    "level": level,
                    "minimum_xp": xp,
                    "proficiency_bonus": proficiency,
                    "tier": 1 if level <= 4 else 2 if level <= 10 else 3 if level <= 16 else 4,
                }
                for level, xp, proficiency in ADVANCEMENT
            ],
        },
        "calculation_formulas": {
            "passive_perception": "10 + модификатор Мудрости (Внимательность)",
            "unarmored_armor_class": "10 + модификатор Ловкости",
            "melee_attack_bonus": "модификатор Силы + бонус мастерства, если есть владение оружием",
            "ranged_attack_bonus": "модификатор Ловкости + бонус мастерства, если есть владение оружием",
            "spell_save_dc": "8 + бонус мастерства + модификатор базовой характеристики заклинаний",
            "spell_attack_bonus": "бонус мастерства + модификатор базовой характеристики заклинаний",
        },
        "classes": classes,
        "races": races,
    }


def main():
    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    data = build_data()
    OUTPUT_PATH.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    archetype_count = sum(len(item["archetypes"]) for item in data["classes"])
    class_feature_count = sum(len(item["features"]) for item in data["classes"])
    archetype_feature_count = sum(len(archetype["features"]) for item in data["classes"] for archetype in item["archetypes"])
    race_trait_count = sum(len(item["traits"]) for item in data["races"])
    print(f"Wrote {OUTPUT_PATH}")
    print(f"Classes: {len(data['classes'])}; archetypes: {archetype_count}; class features: {class_feature_count}; archetype features: {archetype_feature_count}")
    print(f"Races: {len(data['races'])}; race traits: {race_trait_count}")


if __name__ == "__main__":
    main()
