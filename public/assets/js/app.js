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
const tavernModal = document.querySelector("[data-tavern-modal]");
const tavernClassInput = document.querySelector("[data-tavern-class]");
const tavernTerrainInput = document.querySelector("[data-tavern-terrain]");
const tavernTopicCountInput = document.querySelector("[data-tavern-topic-count]");
const tavernEventCountInput = document.querySelector("[data-tavern-event-count]");
const tavernResults = document.querySelector("[data-tavern-results]");
const saveTavernsButton = document.querySelector("[data-save-taverns]");
const tavernDetailModal = document.querySelector("[data-tavern-detail-modal]");
const tavernDetailName = document.querySelector("[data-tavern-detail-name]");
const tavernDetail = document.querySelector("[data-tavern-detail]");
const characterModal = document.querySelector("[data-character-modal]");
const characterRaceInput = document.querySelector("[data-character-race]");
const characterSubtypeInput = document.querySelector("[data-character-subtype]");
const characterClassInput = document.querySelector("[data-character-class]");
const characterLevelInput = document.querySelector("[data-character-level]");
const characterCountInput = document.querySelector("[data-character-count]");
const characterResults = document.querySelector("[data-character-results]");
const saveCharactersButton = document.querySelector("[data-save-characters]");
const characterDetailModal = document.querySelector("[data-character-detail-modal]");
const characterDetailName = document.querySelector("[data-character-detail-name]");
const characterDetail = document.querySelector("[data-character-detail]");
const npcModal = document.querySelector("[data-npc-modal]");
const npcGenreInput = document.querySelector("[data-npc-genre]");
const npcRoleInput = document.querySelector("[data-npc-role]");
const npcProfessionCategoryInput = document.querySelector("[data-npc-profession-category]");
const npcCountInput = document.querySelector("[data-npc-count]");
const npcResults = document.querySelector("[data-npc-results]");
const saveNpcsButton = document.querySelector("[data-save-npcs]");
const npcDetailModal = document.querySelector("[data-npc-detail-modal]");
const npcDetailName = document.querySelector("[data-npc-detail-name]");
const npcDetail = document.querySelector("[data-npc-detail]");
const randomEventModal = document.querySelector("[data-random-event-modal]");
const randomEventCategoryInput = document.querySelector("[data-random-event-category]");
const randomEventCountInput = document.querySelector("[data-random-event-count]");
const randomEventResults = document.querySelector("[data-random-event-results]");
const saveRandomEventsButton = document.querySelector("[data-save-random-events]");
const randomEventDetailModal = document.querySelector("[data-random-event-detail-modal]");
const randomEventDetailName = document.querySelector("[data-random-event-detail-name]");
const randomEventDetail = document.querySelector("[data-random-event-detail]");
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
let tavernData = null;
let currentTavernResults = [];
let characterData = null;
let equipmentData = null;
let currentCharacterResults = [];
let npcData = null;
let currentNpcResults = [];
let randomEventData = null;
let currentRandomEventResults = [];
let libraryFilter = "all";
const LIBRARY_SECTION_TITLES = {
  characters: "Мои персонажи",
  npcs: "Мои НПС",
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
const TAVERN_CLASS_LABELS = {
  cheap: "Дешёвое заведение",
  common: "Обычное заведение",
  expensive: "Дорогое заведение",
};
const TAVERN_MENU_CATEGORY_ORDER = [
  "breakfast",
  "bread",
  "main",
  "salad",
  "soup",
  "stew",
  "dessert",
  "drink",
  "alcohol",
];
const TAVERN_MENU_CATEGORY_LABELS = {
  breakfast: "Завтрак",
  bread: "Хлеб",
  main: "Основное блюдо",
  salad: "Салат",
  soup: "Суп",
  stew: "Рагу",
  dessert: "Десерт",
  drink: "Напиток",
  alcohol: "Алкоголь",
};
const TAVERN_RACE_KEYS = {
  "Дварф": "dwarf",
  "Драконорождённый": "dragonborn",
  "Полурослик": "halfling",
  "Тифлинг": "tiefling",
  "Человек": "human",
  "Полуэльф": "human",
  "Эладрин": "eladrin",
  "Эльф": "elf",
  "Стандартный": "human",
};
const ABILITY_LABELS_RU = {
  strength: "Сила",
  dexterity: "Ловкость",
  constitution: "Телосложение",
  intelligence: "Интеллект",
  wisdom: "Мудрость",
  charisma: "Харизма",
};
const CHARACTER_CLASS_SPELL_KEYS = {
  bard: "bard",
  zhrets: "cleric",
  druid: "druid",
  paladin: "paladin",
  sledopyt: "ranger",
  charodey: "sorcerer",
  koldun: "warlock",
  volshebnik: "wizard",
  izobretatel: "wizard",
};
const CHARACTER_SPELLCASTING_ABILITIES = {
  bard: "charisma",
  zhrets: "wisdom",
  druid: "wisdom",
  paladin: "charisma",
  sledopyt: "wisdom",
  charodey: "charisma",
  koldun: "charisma",
  volshebnik: "intelligence",
  izobretatel: "intelligence",
  voin: "intelligence",
  plut: "intelligence",
};
const CHARACTER_CLASS_PRIORITIES = {
  bard: ["charisma", "dexterity", "constitution", "wisdom", "intelligence", "strength"],
  varvar: ["strength", "constitution", "dexterity", "wisdom", "charisma", "intelligence"],
  voin: ["strength", "constitution", "dexterity", "wisdom", "intelligence", "charisma"],
  volshebnik: ["intelligence", "constitution", "dexterity", "wisdom", "charisma", "strength"],
  druid: ["wisdom", "constitution", "dexterity", "intelligence", "charisma", "strength"],
  zhrets: ["wisdom", "constitution", "strength", "charisma", "intelligence", "dexterity"],
  izobretatel: ["intelligence", "constitution", "dexterity", "wisdom", "charisma", "strength"],
  koldun: ["charisma", "constitution", "dexterity", "wisdom", "intelligence", "strength"],
  monah: ["dexterity", "wisdom", "constitution", "strength", "charisma", "intelligence"],
  paladin: ["strength", "charisma", "constitution", "wisdom", "dexterity", "intelligence"],
  plut: ["dexterity", "constitution", "intelligence", "charisma", "wisdom", "strength"],
  sledopyt: ["dexterity", "wisdom", "constitution", "strength", "intelligence", "charisma"],
  charodey: ["charisma", "constitution", "dexterity", "wisdom", "intelligence", "strength"],
};
const RACE_SUBTYPE_TITLES = {
  aasimar: ["Аасимар-защитник", "Аасимар-каратель", "Павший аасимар"],
  gity: ["Гитцераи", "Гитъянки"],
  gnom: ["Лесной гном", "Скальный гном", "Свирфнеблин", "Метка Письма"],
  goblinoidy: ["Багбир", "Гоблин", "Хобгоблин"],
  dvarf: ["Горный дварф", "Холмовой дварф", "Дуэргары (MTF)", "Метка Опеки"],
  dzhenazi: ["Дженази воздуха", "Дженази земли", "Дженази огня", "Дженази воды"],
  drakonorozhdennyy: ["Драконокровный", "Равенит"],
  poluork: ["Метка Поиска"],
  poluroslik: ["Коренастый", "Легконогий", "Лотосденский Полурослик", "Метка Исцеления", "Метка Гостеприимства"],
  poluelf: ["Метка Обнаружения", "Метка Бури"],
  tifling: ["Асмодей (MTF)", "Вельзевул (MTF)", "Гласия (MTF)", "Диспатер (MTF)", "Зариэль (MTF)", "Левистус (MTF)", "Маммон (MTF)", "Мефистофель (MTF)", "Фьёрна (MTF)"],
  chelovek: ["стандартный человек", "альтернативный человек", "Метка Поиска", "Метка Ухода", "Метка Создания", "Метка Прохода", "Метка Стража"],
  shifter: ["Зверошкуры", "Длиннозубы", "Быстроноги", "Дикие охотники"],
  elf: ["Высший эльф", "Лесной эльф", "Тёмный эльф (дроу)", "Морские эльфы (MTF)", "Шадар-кай (MTF)", "Эладрин (MTF)", "Бледный эльф"],
};
const RACE_SUBTYPE_INTRO_TITLES = ["Разновидности", "Подрасы", "Подраса", "Подрасы тифлингов", "Драконорожденный", "Полуэльфы Фаэруна", "Полуэльфы Эберрона", "Полуорки Эберрона", "Полурослики Фаэруна", "Полурослики Эберрона", "Дварфы Эберрона", "Тифлинги Фаэруна (SCAG)"];
const RACE_GENERIC_TRAIT_NAMES = ["возраст", "мировоззрение", "размер", "скорость"];
const CHARACTER_NAME_STARTS = ["Ар", "Бел", "Вар", "Гал", "Дар", "Ир", "Ка", "Лор", "Мир", "Ним", "Ор", "Ри", "Са", "Тар", "Эл", "Яр"];
const CHARACTER_NAME_ENDS = ["ан", "вен", "дан", "ион", "ис", "кан", "лир", "мар", "нар", "рин", "сор", "тар", "эль", "ян"];
const CHARACTER_SURNAMES = ["Тихий Клинок", "Северный Пепел", "Лунная Тропа", "Медный Порог", "Пятое Пламя", "Верный Знак", "Старая Клятва", "Серый Ветер"];
const FIGHTING_STYLES = ["Дуэлянт", "Защита", "Оборона", "Перехват", "Сражение большим оружием", "Сражение вслепую", "Сражение голыми руками", "Сражение двумя оружиями", "Сражение метательным оружием", "Стрельба"];
const METAMAGIC_OPTIONS = ["Аккуратное заклинание", "Далёкое заклинание", "Двойное заклинание", "Неуловимое заклинание", "Продлённое заклинание", "Усиленное заклинание", "Ускоренное заклинание"];
const WARLOCK_INVOCATIONS = ["Дьявольский взгляд", "Маска многих лиц", "Туманные видения", "Книга древних тайн", "Мучительный взрыв", "Отталкивающий взрыв", "Звериная речь", "Взор двух умов"];
const PACT_BOONS = ["Договор клинка", "Договор цепи", "Договор книги", "Договор талисмана"];
const ARTIFICER_INFUSIONS = ["Улучшенное оружие", "Улучшенная защита", "Возвращающееся оружие", "Повторяющий выстрел", "Репликация магического предмета", "Гомункул-слуга"];
const RANGER_FAVORED_ENEMIES = ["аберрации", "великаны", "драконы", "звери", "исчадия", "конструкты", "нежить", "растения", "феи", "гуманоиды"];
const RANGER_TERRAINS = ["Арктика", "Болото", "Горы", "Лес", "Луг", "Побережье", "Подземье", "Пустыня"];
const BATTLE_MASTER_MANEUVERS = ["Атака командира", "Атака с выпадом", "Атака с манёвром", "Парирование", "Разоружающая атака", "Точная атака", "Угрожающая атака"];
const FULL_CASTER_SLOTS = {
  1: [2],
  2: [3],
  3: [4, 2],
  4: [4, 3],
  5: [4, 3, 2],
  6: [4, 3, 3],
  7: [4, 3, 3, 1],
  8: [4, 3, 3, 2],
  9: [4, 3, 3, 3, 1],
  10: [4, 3, 3, 3, 2],
  11: [4, 3, 3, 3, 2, 1],
  12: [4, 3, 3, 3, 2, 1],
  13: [4, 3, 3, 3, 2, 1, 1],
  14: [4, 3, 3, 3, 2, 1, 1],
  15: [4, 3, 3, 3, 2, 1, 1, 1],
  16: [4, 3, 3, 3, 2, 1, 1, 1],
  17: [4, 3, 3, 3, 2, 1, 1, 1, 1],
  18: [4, 3, 3, 3, 3, 1, 1, 1, 1],
  19: [4, 3, 3, 3, 3, 2, 1, 1, 1],
  20: [4, 3, 3, 3, 3, 2, 2, 1, 1],
};
const HALF_CASTER_SLOTS = {
  2: [2],
  3: [3],
  4: [3],
  5: [4, 2],
  6: [4, 2],
  7: [4, 3],
  8: [4, 3],
  9: [4, 3, 2],
  10: [4, 3, 2],
  11: [4, 3, 3],
  12: [4, 3, 3],
  13: [4, 3, 3, 1],
  14: [4, 3, 3, 1],
  15: [4, 3, 3, 2],
  16: [4, 3, 3, 2],
  17: [4, 3, 3, 3, 1],
  18: [4, 3, 3, 3, 1],
  19: [4, 3, 3, 3, 2],
  20: [4, 3, 3, 3, 2],
};
const ARTIFICER_SLOTS = {
  1: [2],
  2: [2],
  3: [3],
  4: [3],
  ...HALF_CASTER_SLOTS,
};
const THIRD_CASTER_SLOTS = {
  3: [2],
  4: [3],
  5: [3],
  6: [3],
  7: [4, 2],
  8: [4, 2],
  9: [4, 2],
  10: [4, 3],
  11: [4, 3],
  12: [4, 3],
  13: [4, 3, 2],
  14: [4, 3, 2],
  15: [4, 3, 2],
  16: [4, 3, 3],
  17: [4, 3, 3],
  18: [4, 3, 3],
  19: [4, 3, 3, 1],
  20: [4, 3, 3, 1],
};
const WARLOCK_SLOTS = {
  1: [1],
  2: [2],
  3: [2],
  4: [2],
  5: [2],
  6: [2],
  7: [2],
  8: [2],
  9: [2],
  10: [2],
  11: [3],
  12: [3],
  13: [3],
  14: [3],
  15: [3],
  16: [3],
  17: [4],
  18: [4],
  19: [4],
  20: [4],
};
const BARD_KNOWN_SPELLS = [0, 4, 5, 6, 7, 8, 9, 10, 11, 12, 14, 15, 15, 16, 18, 19, 19, 20, 22, 22, 22];
const SORCERER_KNOWN_SPELLS = [0, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 12, 13, 13, 14, 14, 15, 15, 15, 15];
const WARLOCK_KNOWN_SPELLS = [0, 2, 3, 4, 5, 6, 7, 8, 9, 10, 10, 11, 11, 12, 12, 13, 13, 14, 14, 15, 15];
const RANGER_KNOWN_SPELLS = [0, 0, 2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 8, 8, 9, 9, 10, 10, 11, 11];
const THIRD_CASTER_KNOWN_SPELLS = [0, 0, 0, 3, 4, 4, 4, 5, 6, 6, 7, 8, 8, 9, 10, 10, 11, 11, 11, 12, 13];
const FULL_CASTER_CANTRIPS = {
  bard: [0, 2, 2, 2, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
  cleric: [0, 3, 3, 3, 4, 4, 4, 4, 4, 4, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5],
  druid: [0, 2, 2, 2, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
  sorcerer: [0, 4, 4, 4, 5, 5, 5, 5, 5, 5, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6],
  warlock: [0, 2, 2, 2, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
  wizard: [0, 3, 3, 3, 4, 4, 4, 4, 4, 4, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5],
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

function repairPdfHyphenation(text) {
  return String(text || "").replace(/(^|[^А-Яа-яЁё])([А-Яа-яЁё]+)-\s+([а-яё]+)/g, (match, boundary, left, right) => {
    const wordPrefix = left.toLowerCase();
    if (["по", "кое"].includes(wordPrefix)) {
      return `${boundary}${left}-${right}`;
    }
    return `${boundary}${left}${right}`;
  });
}

function dedupeRepeatedPhrases(text) {
  let normalized = String(text || "");
  for (let index = 0; index < 3; index += 1) {
    const next = normalized
      .replace(/([А-ЯЁA-Z][А-Яа-яЁёA-Za-z0-9()«»" .,-]{2,70}?)\s*\1/g, "$1")
      .replace(/(^|[^А-Яа-яЁё])([А-Яа-яЁё]{4,})\2(?=$|[^А-Яа-яЁё])/g, "$1$2");
    if (next === normalized) {
      break;
    }
    normalized = next;
  }
  return normalized;
}

function normalizePdfArtifacts(value) {
  return dedupeRepeatedPhrases(repairPdfHyphenation(value))
    .replace(/для сдля/gi, "для")
    .replace(/\s+([,.;:!?])/g, "$1")
    .replace(/([а-яё])([А-ЯЁ][а-яё]+(?:\s+[а-яё]+){0,2})(?=\s|$)/g, "$1\n$2")
    .replace(/[ \t]{2,}/g, " ");
}

function normalizeRichText(value) {
  const text = String(value || "")
    .replace(/\\r\\n|\\n|\\r/g, "\n")
    .replace(/\r\n?/g, "\n");
  return normalizePdfArtifacts(text).trim();
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

function cleanRulesExcerpt(value, heading = "") {
  let text = getPlainTextExcerpt(value);
  const cleanHeading = String(heading || "").trim();
  if (cleanHeading) {
    const escaped = cleanHeading.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    text = text
      .replace(new RegExp(`^(${escaped}){1,3}\\s*`, "i"), "")
      .replace(new RegExp(`^${escaped}\\s+${escaped}\\s*`, "i"), "");
  }
  return text
    .replace(/([А-ЯЁA-Z][а-яёa-z]+(?:\s+[А-ЯЁA-Zа-яёa-z]+){0,4})\1/g, "$1")
    .replace(/\s+/g, " ")
    .trim();
}

function dedupeRepeatedText(value) {
  const text = String(value || "").trim();
  const middle = Math.floor(text.length / 2);
  if (text.length > 3 && text.length % 2 === 0 && text.slice(0, middle) === text.slice(middle)) {
    return text.slice(0, middle).trim();
  }
  return text;
}

function cleanRulesText(value, heading = "") {
  let text = normalizeRichText(value).trim();
  const cleanHeading = String(heading || "").trim();
  if (cleanHeading) {
    const escaped = cleanHeading.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    text = text
      .replace(new RegExp(`^(${escaped}){1,3}\\s*`, "i"), "")
      .replace(new RegExp(`^${escaped}\\s+${escaped}\\s*`, "i"), "");
  }

  const lines = text
    .split("\n")
    .map((line) => dedupeRepeatedText(line.trim()))
    .filter((line) => line && !/^\d{1,3}$/.test(line));

  if (lines.length && /^умение\s+.+уров/i.test(lines[0])) {
    lines.shift();
  }

  return lines.join("\n").trim();
}

function cleanCharacterFeatureText(value, heading = "") {
  let text = cleanRulesText(value, heading);
  text = text
    .replace(/\s+(Бард|Варвар|Воин|Волшебник|Друид|Жрец|Изобретатель|Колдун|Монах|Паладин|Плут|Следопыт|Чародей)\s+Уровень[\s\S]*$/i, "")
    .replace(/\n?\s*\d+\s+\+\d[\s\S]*$/i, "")
    .replace(/\n?\s*\d+\s+\d+\s+[-—\d][\s\S]*$/i, "")
    .replace(/\n?\s*СнаряжениеСнаряжение[\s\S]*$/i, "")
    .trim();
  return text;
}

function isProgressionOnlyFeature(feature) {
  const text = cleanCharacterFeatureText(feature?.description || "", feature?.name || "");
  return !text || !/[А-Яа-яЁёA-Za-z]{4,}/.test(text);
}

function isInvalidFeatureName(feature) {
  return !String(feature?.name || "").trim() || /^\d+$/.test(String(feature?.name || "").trim());
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
    fetch("data/srd/spells.index.json?v=20260610-spells-ru-1"),
    fetch("data/srd/spells.json?v=20260610-spells-ru-1"),
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

function getSavedTaverns() {
  try {
    return JSON.parse(storage.get("dnd-saved-taverns", "[]"));
  } catch {
    return [];
  }
}

function setSavedTaverns(savedTaverns) {
  storage.set("dnd-saved-taverns", JSON.stringify(savedTaverns));
  updateSavedRollsCount();
  renderLibraryTaverns();
}

function getSavedCharacters() {
  try {
    return JSON.parse(storage.get("dnd-saved-characters", "[]"));
  } catch {
    return [];
  }
}

function setSavedCharacters(savedCharacters) {
  storage.set("dnd-saved-characters", JSON.stringify(savedCharacters));
  updateSavedRollsCount();
  renderLibraryCharacters();
  renderLibraryNpcs();
}

function getSavedEvents() {
  try {
    return JSON.parse(storage.get("dnd-saved-events", "[]"));
  } catch {
    return [];
  }
}

function setSavedEvents(savedEvents) {
  storage.set("dnd-saved-events", JSON.stringify(savedEvents));
  updateSavedRollsCount();
  renderLibraryEvents();
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
  const count = getSavedRolls().length + getSavedMonsters().length + getSavedPotions().length + getSavedSpells().length + getSavedLoot().length + getSavedTaverns().length + getSavedCharacters().length + getSavedEvents().length + getSavedNotes().length;
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
}

function renderLibraryCharacters() {
  if (!libraryPanel) {
    return;
  }

  const list = getLibrarySection("[data-saved-characters]", "characters");
  list.dataset.savedCharacters = "";

  const characters = getSavedCharacters().filter((entry) => entry.kind === "player-character");
  if (!characters.length) {
    list.innerHTML = `
      ${renderLibrarySectionHeader("characters")}
      <div class="library-empty">
        <strong>Сохранённых персонажей пока нет</strong>
        <span>Сгенерируй игрового персонажа и сохрани результат, чтобы карточка появилась здесь.</span>
      </div>
    `;
    applyLibraryFilter();
    return;
  }

  list.innerHTML = `
    ${renderLibrarySectionHeader("characters")}
    <div class="saved-monster-grid character-library-grid">
      ${characters.map((entry) => renderCharacterCard(entry, { saved: true })).join("")}
    </div>
  `;
  applyLibraryFilter();
}

function renderLibraryNpcs() {
  if (!libraryPanel) {
    return;
  }

  const list = getLibrarySection("[data-saved-npcs]", "npcs");
  list.dataset.savedNpcs = "";

  const npcs = getSavedCharacters().filter((entry) => entry.kind !== "player-character");
  if (!npcs.length) {
    list.innerHTML = `
      ${renderLibrarySectionHeader("npcs")}
      <div class="library-empty">
        <strong>Сохранённых НПС пока нет</strong>
        <span>Сгенерируй НПС и сохрани результат, чтобы карточка появилась здесь.</span>
      </div>
    `;
    applyLibraryFilter();
    return;
  }

  list.innerHTML = `
    ${renderLibrarySectionHeader("npcs")}
    <div class="saved-monster-grid npc-library-grid">
      ${npcs.map((npc) => renderNpcCard(npc, { saved: true })).join("")}
    </div>
  `;
  applyLibraryFilter();
}

function renderLibraryEvents() {
  if (!libraryPanel) {
    return;
  }

  const list = getLibrarySection("[data-saved-events]", "events");
  list.dataset.savedEvents = "";

  const events = getSavedEvents();
  if (!events.length) {
    list.innerHTML = `
      ${renderLibrarySectionHeader("events")}
      <div class="library-empty">
        <strong>Сохранённых событий пока нет</strong>
        <span>Сгенерируй случайное событие и сохрани результат, чтобы карточка появилась здесь.</span>
      </div>
    `;
    applyLibraryFilter();
    return;
  }

  list.innerHTML = `
    ${renderLibrarySectionHeader("events")}
    <div class="saved-monster-grid event-library-grid">
      ${events.map((randomEvent) => renderRandomEventCard(randomEvent, { saved: true })).join("")}
    </div>
  `;
  applyLibraryFilter();
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

function getTavernSummaryText(tavern) {
  const parts = [
    tavern.atmosphere?.mood,
    tavern.menu?.dish,
    tavern.menu?.drink,
  ].filter(Boolean);
  return parts.join(" · ") || "таверна";
}

function getTavernPriceCategory(tavern, kind) {
  const byClass = {
    cheap: kind === "special" ? "Дешёвое" : "Дешёвый",
    common: kind === "special" ? "Обычное" : "Обычный",
    expensive: kind === "special" ? "Роскошное" : "Роскошный",
  };
  return byClass[tavern.classKey] || byClass.common;
}

function renderTavernCard(tavern, { saved = false, index = 0 } = {}) {
  const removeAttr = saved
    ? `data-delete-saved-tavern="${escapeHtml(tavern.id)}"`
    : `data-delete-tavern-result="${escapeHtml(tavern.id)}"`;
  const removeLabel = saved ? "Удалить таверну" : "Убрать таверну";
  const openAttr = saved
    ? `data-open-saved-tavern="${escapeHtml(tavern.id)}"`
    : `data-open-tavern-result="${escapeHtml(tavern.id)}"`;

  return `
    <article class="saved-monster-card tavern-card" ${openAttr} tabindex="0" role="button">
      <button class="card-remove" type="button" ${removeAttr} aria-label="${removeLabel}">×</button>
      <strong>${escapeHtml(tavern.name)}</strong>
    </article>
  `;
}

function getTavernMenuCategoryLabel(category) {
  return tavernData?.tables?.expanded_menu?.categories?.[category]
    || TAVERN_MENU_CATEGORY_LABELS[category]
    || category;
}

function getTavernExpandedMenuRows(tavern) {
  const menu = tavern.expandedMenu || {};
  return TAVERN_MENU_CATEGORY_ORDER
    .map((category) => [category, menu[category]])
    .filter(([, item]) => item?.name || item?.description);
}

function renderTavernExpandedMenu(tavern) {
  const rows = getTavernExpandedMenuRows(tavern);
  if (!rows.length) {
    return "";
  }

  return `
    <section class="monster-section">
      <h4>Меню таверны</h4>
      <div class="tavern-menu-list">
        ${rows.map(([category, item]) => `
          <div class="tavern-menu-item">
            <span>${escapeHtml(getTavernMenuCategoryLabel(category))}</span>
            <strong>${escapeHtml(item.name || "-")}</strong>
            ${item.description ? `<p>${escapeHtml(item.description)}</p>` : ""}
          </div>
        `).join("")}
      </div>
    </section>
  `;
}

function renderTavernDetail(tavern) {
  const topics = tavern.topics || [];
  const events = tavern.events || [];
  const patrons = tavern.patrons?.types || [];
  const roomPrices = (tavern.prices?.rooms || [])
    .find((entry) => entry.category === getTavernPriceCategory(tavern, "room"))?.rooms
    || [];
  const specialPrices = (tavern.prices?.specials || [])
    .find((entry) => entry.category === getTavernPriceCategory(tavern, "special"))?.items
    || [];

  return `
    <div class="monster-meta-grid">
      <div><span>Класс</span><strong>${escapeHtml(tavern.classLabel || "-")}</strong></div>
      <div><span>Местность</span><strong>${escapeHtml(tavern.terrainLabel || "-")}</strong></div>
      <div><span>Имя хозяина</span><strong>${escapeHtml(tavern.owner?.fullName || "-")}</strong></div>
      <div><span>Хозяин</span><strong>${escapeHtml(`${tavern.owner?.race || "-"}, ${tavern.owner?.gender || "-"}, ${tavern.owner?.age || "?"} лет`)}</strong></div>
      <div><span>Комнаты</span><strong>${escapeHtml(tavern.innSize?.rooms || "0")}</strong></div>
      <div><span>Персонал</span><strong>${escapeHtml(tavern.innSize?.staff || "-")}</strong></div>
      <div><span>Заполненность</span><strong>${escapeHtml(tavern.patrons?.occupancy || "-")}</strong></div>
      <div><span>Посетителей</span><strong>${escapeHtml(tavern.patrons?.count ?? 0)}</strong></div>
    </div>

    <section class="monster-section">
      <h4>Атмосфера</h4>
      <div class="monster-text-list">
        <p><strong>${escapeHtml(tavern.atmosphere?.mood || "-")}.</strong> ${escapeHtml(tavern.atmosphere?.cause || "")}</p>
      </div>
    </section>

    <section class="monster-section">
      <h4>Особое меню</h4>
      <div class="tavern-detail-grid">
        <div><span>Блюдо</span><strong>${escapeHtml(tavern.menu?.dish || "-")}</strong></div>
        <div><span>Напиток</span><strong>${escapeHtml(tavern.menu?.drink || "-")}</strong></div>
      </div>
    </section>

    ${renderTavernExpandedMenu(tavern)}

    ${patrons.length ? `
      <section class="monster-section">
        <h4>Присутствующие МП</h4>
        <div class="monster-text-list">
          <p>${escapeHtml(patrons.join(", "))}</p>
        </div>
      </section>
    ` : ""}

    ${topics.length ? `
      <section class="monster-section">
        <h4>Обсуждаемые темы</h4>
        <div class="monster-text-list">
          ${topics.map((topic) => `<p>${escapeHtml(topic.text)}</p>`).join("")}
        </div>
      </section>
    ` : ""}

    ${events.length ? `
      <section class="monster-section">
        <h4>Случайные события</h4>
        <div class="monster-text-list">
          ${events.map((event) => `<p>${escapeHtml(event.text)}</p>`).join("")}
        </div>
      </section>
    ` : ""}

    ${roomPrices.length || specialPrices.length ? `
      <section class="monster-section">
        <h4>Цены</h4>
        <div class="tavern-detail-grid">
          ${roomPrices.map((item) => `<div><span>${escapeHtml(item.type)}</span><strong>${escapeHtml(item.price)}</strong></div>`).join("")}
          ${specialPrices.map((item) => `<div><span>${escapeHtml(item.type)}</span><strong>${escapeHtml(item.price)}</strong></div>`).join("")}
        </div>
      </section>
    ` : ""}

    <div class="license-note">
      Источник: ${escapeHtml(tavern.source || "Таверна на скорую руку")}. Сохранено локально в браузере.
    </div>
  `;
}

function renderLibraryTaverns() {
  if (!libraryPanel) {
    return;
  }

  const list = getLibrarySection("[data-saved-taverns]", "taverns");
  list.dataset.savedTaverns = "";

  const savedTaverns = getSavedTaverns();
  if (!savedTaverns.length) {
    list.innerHTML = `
      ${renderLibrarySectionHeader("taverns")}
      <div class="library-empty">
        <strong>Сохранённых таверн пока нет</strong>
        <span>Сгенерируй таверну и сохрани результат, чтобы она появилась здесь.</span>
      </div>
    `;
    applyLibraryFilter();
    return;
  }

  list.innerHTML = `
    ${renderLibrarySectionHeader("taverns")}
    <div class="saved-monster-grid tavern-library-grid">
      ${savedTaverns.map((tavern) => renderTavernCard(tavern, { saved: true })).join("")}
    </div>
  `;
  applyLibraryFilter();
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

async function loadTaverns() {
  if (tavernData) {
    setupTavernTerrainOptions();
    return;
  }

  const response = await fetch("data/generators/taverns.json?v=20260608-tavern-supplements-3");
  if (!response.ok) {
    throw new Error("Не удалось загрузить таблицы таверн.");
  }

  tavernData = await response.json();
  setupTavernTerrainOptions();
}

function setupTavernTerrainOptions() {
  if (!tavernTerrainInput || !tavernData) {
    return;
  }

  const current = tavernTerrainInput.value;
  const terrainTables = tavernData.tables?.special_menu_by_terrain || {};
  tavernTerrainInput.innerHTML = `
    <option value="">Любая местность</option>
    ${Object.entries(terrainTables)
      .map(([key, table]) => `<option value="${escapeHtml(key)}">${escapeHtml(table.label)}</option>`)
      .join("")}
  `;
  if (current && terrainTables[current]) {
    tavernTerrainInput.value = current;
  }
}

function normalizeDiceFormula(formula) {
  return String(formula || "0")
    .toLowerCase()
    .replace(/к/g, "d")
    .replace(/×/g, "*")
    .replace(/[()]/g, "")
    .replace(/\s+/g, "");
}

function rollTavernDice(term) {
  const text = normalizeDiceFormula(term);
  const match = text.match(/^(\d*)d(\d+)$/);
  if (!match) {
    return Number(text) || 0;
  }

  const count = Number(match[1] || 1);
  const sides = Number(match[2] || 0);
  if (!count || !sides) {
    return 0;
  }
  return Array.from({ length: count }, () => randomInt(1, sides)).reduce((sum, value) => sum + value, 0);
}

function rollTavernFormula(formula, roomCount = 0) {
  const text = normalizeDiceFormula(formula).replace(/количествокомнат/g, String(roomCount));
  if (/^\d+$/.test(text)) {
    return Number(text);
  }

  return text
    .split("+")
    .filter(Boolean)
    .reduce((sum, part) => {
      const factors = part.split("*").filter(Boolean);
      const value = factors.reduce((product, factor) => product * rollTavernDice(factor), 1);
      return sum + value;
    }, 0);
}

function pickRollEntry(table) {
  const entries = table?.entries || [];
  if (!entries.length) {
    return null;
  }

  const die = table.die ? normalizeDiceFormula(table.die) : "";
  const roll = die ? rollTavernDice(die) : randomInt(1, entries.length);
  return entries.find((entry) => entry.roll === roll || (roll >= entry.roll_min && roll <= entry.roll_max)) || pickFrom(entries);
}

function pickTavernTerrainKey() {
  const tables = tavernData.tables?.special_menu_by_terrain || {};
  const selected = tavernTerrainInput?.value;
  if (selected && tables[selected]) {
    return selected;
  }
  return pickFrom(Object.keys(tables).filter((key) => key !== "awful")) || pickFrom(Object.keys(tables));
}

function buildTavernName(owner) {
  const partsTable = tavernData.tables?.tavern_name_parts;
  const variationTable = tavernData.tables?.name_variations;
  const parts = pickRollEntry(partsTable) || {};
  const variation = pickRollEntry(variationTable) || { roll: 1 };
  const baseName = `${parts.first || "Сонный"} ${parts.second || "Огр"}`.trim();
  const secondName = pickRollEntry(partsTable)?.second || "нимфа";
  const ownerShortName = owner?.family || owner?.firstName || owner?.fullName || "хозяина";

  const templates = {
    1: `Таверна «${baseName}»`,
    2: `Постоялый двор «${baseName}»`,
    3: `Постоялый двор «${baseName}»`,
    4: `«${baseName}»`,
    5: `«${baseName} и ${secondName}»`,
    6: `Пивная «${baseName}»`,
    7: `Трактир «${baseName}»`,
    8: `Пивная ${ownerShortName}`,
  };

  return {
    title: templates[variation.roll] || `Таверна «${baseName}»`,
    parts,
    variation,
  };
}

function getOwnerNameTableKey(race) {
  if (race === "Полуэльф") {
    return pickFrom(["human", "elf"]);
  }
  return TAVERN_RACE_KEYS[race] || "human";
}

function getOwnerAgeRace(race) {
  if (race === "Стандартный" || race === "Полуэльф") {
    return "Человек/полуэльф";
  }
  return race;
}

function buildTavernOwner() {
  const ownerTables = tavernData.tables?.owner || {};
  const raceGender = pickRollEntry(ownerTables.race_gender) || { race: "Человек", gender: "мужчина" };
  const nameTableKey = getOwnerNameTableKey(raceGender.race);
  const namesTable = ownerTables.names_by_race?.[nameTableKey];
  const nameRow = pickRollEntry(namesTable) || { male: "Делин", female: "Фарила", family: "Рунтроп" };
  const isFemale = raceGender.gender === "женщина";
  const firstName = isFemale ? nameRow.female : nameRow.male;
  const fullName = [firstName, nameRow.family].filter(Boolean).join(" ");
  const ageRace = getOwnerAgeRace(raceGender.race);
  const ageFormula = ownerTables.age_by_race?.find((entry) => entry.race === ageRace)?.formula || "(5к10) + 16";

  return {
    race: raceGender.race,
    gender: raceGender.gender,
    firstName,
    family: nameRow.family,
    fullName,
    age: rollTavernFormula(ageFormula),
    ageFormula,
    nameTable: namesTable?.label || "Люди",
  };
}

function buildTavernPatrons(classKey, roomCount) {
  const patrons = tavernData.tables?.patrons || {};
  const occupancy = pickRollEntry(patrons.occupancy) || { label: "Несколько человек", count_formula: "1к8" };
  const count = rollTavernFormula(occupancy.count_formula, roomCount);
  const table = patrons.by_inn_class?.[classKey] || patrons.by_inn_class?.common;
  const types = Array.from({ length: count }, () => {
    const entry = pickRollEntry(table);
    if (entry?.value === "Важный МП" && table?.important_npc) {
      return pickRollEntry(table.important_npc)?.value || entry.value;
    }
    return entry?.value || "Обыватель";
  });

  return {
    occupancy: occupancy.label,
    count,
    countFormula: occupancy.count_formula,
    types,
  };
}

function pickExpandedTavernMenuEntry(category) {
  const entries = tavernData.tables?.expanded_menu?.entries || [];
  return pickFrom(entries.filter((entry) => entry.category === category));
}

function buildExpandedTavernMenu() {
  const menu = {};
  TAVERN_MENU_CATEGORY_ORDER.forEach((category) => {
    if (category === "alcohol") {
      const alcohol = pickRollEntry(tavernData.tables?.expanded_alcohol);
      if (alcohol) {
        menu.alcohol = alcohol;
      }
      return;
    }

    const entry = pickExpandedTavernMenuEntry(category);
    if (entry) {
      menu[category] = entry;
    }
  });
  return menu;
}

function pickTavernEvent() {
  return pickRollEntry(tavernData.tables?.random_events_100 || tavernData.tables?.random_events);
}

function buildTavern() {
  const classKey = tavernClassInput?.value || "common";
  const terrainKey = pickTavernTerrainKey();
  const terrainTable = tavernData.tables.special_menu_by_terrain[terrainKey];
  const owner = buildTavernOwner();
  const name = buildTavernName(owner);
  const size = pickRollEntry(tavernData.tables.inn_size) || { rooms: "4", staff: "2 прислуги" };
  const roomCount = Number.parseInt(size.rooms, 10) || 0;
  const topicCount = Math.min(Math.max(Number(tavernTopicCountInput?.value) || 1, 1), 4);
  const eventCount = Math.min(Math.max(Number(tavernEventCountInput?.value) || 1, 1), 4);

  if (tavernTopicCountInput) {
    tavernTopicCountInput.value = String(topicCount);
  }
  if (tavernEventCountInput) {
    tavernEventCountInput.value = String(eventCount);
  }

  return {
    id: crypto.randomUUID(),
    name: name.title,
    nameParts: name.parts,
    nameVariation: name.variation,
    classKey,
    classLabel: TAVERN_CLASS_LABELS[classKey] || TAVERN_CLASS_LABELS.common,
    terrainKey,
    terrainLabel: terrainTable?.label || "Любая местность",
    owner,
    atmosphere: pickRollEntry(tavernData.tables.atmosphere),
    innSize: {
      rooms: size.rooms,
      staff: size.staff,
    },
    menu: pickRollEntry(terrainTable),
    expandedMenu: buildExpandedTavernMenu(),
    patrons: buildTavernPatrons(classKey, roomCount),
    topics: Array.from({ length: topicCount }, () => pickRollEntry(tavernData.tables.conversation_topics)).filter(Boolean),
    events: Array.from({ length: eventCount }, () => pickTavernEvent()).filter(Boolean),
    prices: {
      rooms: tavernData.tables.inn_prices_daily,
      specials: tavernData.tables.special_prices,
    },
    source: tavernData.source?.title || "Таверна на скорую руку",
    savedAt: new Date().toISOString(),
  };
}

function renderTavernResults() {
  if (!tavernResults || !saveTavernsButton) {
    return;
  }

  if (!currentTavernResults.length) {
    tavernResults.innerHTML = "";
    saveTavernsButton.disabled = true;
    return;
  }

  tavernResults.innerHTML = currentTavernResults.map((tavern, index) => renderTavernCard(tavern, { index })).join("");
  saveTavernsButton.disabled = false;
}

function generateTavern() {
  if (!tavernData) {
    return;
  }
  currentTavernResults.unshift(buildTavern());
  renderTavernResults();
}

function deleteTavernResult(id) {
  currentTavernResults = currentTavernResults.filter((tavern) => tavern.id !== id);
  renderTavernResults();
}

function openTavernDetail(tavern) {
  if (!tavern || !tavernDetailModal) {
    return;
  }

  tavernDetailName.textContent = tavern.name || "Таверна";
  tavernDetail.innerHTML = renderTavernDetail(tavern);
  tavernDetailModal.classList.remove("is-hidden");
}

function openTavernResult(id) {
  openTavernDetail(currentTavernResults.find((tavern) => tavern.id === id));
}

function openSavedTavern(id) {
  openTavernDetail(getSavedTaverns().find((tavern) => tavern.id === id));
}

function closeTavernDetail() {
  tavernDetailModal.classList.add("is-hidden");
}

function saveGeneratedTaverns() {
  if (!currentTavernResults.length) {
    return;
  }

  const savedTaverns = getSavedTaverns();
  currentTavernResults.forEach((tavern) => {
    savedTaverns.unshift({
      ...JSON.parse(JSON.stringify(tavern)),
      id: crypto.randomUUID(),
      savedAt: new Date().toISOString(),
    });
  });
  setSavedTaverns(savedTaverns.slice(0, 200));
  closeTavernGenerator();
  openPanel("library");
}

async function openTavernGenerator() {
  try {
    await loadTaverns();
    currentTavernResults = [];
    tavernResults.innerHTML = "";
    saveTavernsButton.disabled = true;
    tavernModal.classList.remove("is-hidden");
  } catch (error) {
    tavernModal.classList.remove("is-hidden");
    tavernResults.innerHTML = `<div class="library-empty"><strong>Ошибка загрузки</strong><span>${escapeHtml(error.message)}</span></div>`;
    saveTavernsButton.disabled = true;
  }
}

function closeTavernGenerator() {
  tavernModal.classList.add("is-hidden");
  currentTavernResults = [];
}

function deleteSavedTavern(id) {
  setSavedTaverns(getSavedTaverns().filter((tavern) => tavern.id !== id));
}

async function loadCharacters() {
  if (characterData && equipmentData) {
    setupCharacterOptions();
    return;
  }

  const [charactersResponse, equipmentResponse] = await Promise.all([
    fetch("data/generators/characters.json?v=20260610-characters-3"),
    fetch("data/generators/equipment.json?v=20260610-equipment-1"),
  ]);
  if (!charactersResponse.ok) {
    throw new Error("Не удалось загрузить базу персонажей.");
  }
  if (!equipmentResponse.ok) {
    throw new Error("Не удалось загрузить базу снаряжения.");
  }

  characterData = await charactersResponse.json();
  equipmentData = await equipmentResponse.json();
  setupCharacterOptions();
}

function getRaceSubtypeOptions(race) {
  if (!race) {
    return [];
  }
  if (race.id === "chelovek") {
    return (RACE_SUBTYPE_TITLES.chelovek || []).map((name) => ({ id: name, name }));
  }

  const titles = RACE_SUBTYPE_TITLES[race.id] || [];
  const traits = race.traits || [];
  return titles
    .filter((title) => traits.some((trait) => trait.name === title))
    .map((name) => ({ id: name, name }));
}

function setupCharacterSubtypeOptions() {
  if (!characterSubtypeInput) {
    return;
  }

  const race = (characterData?.races || []).find((entry) => entry.id === characterRaceInput?.value);
  const current = characterSubtypeInput.value;
  const subtypes = getRaceSubtypeOptions(race);
  characterSubtypeInput.innerHTML = `
    <option value="">Случайный</option>
    ${subtypes.map((subtype) => `<option value="${escapeHtml(subtype.id)}">${escapeHtml(subtype.name)}</option>`).join("")}
  `;
  characterSubtypeInput.disabled = !race || !subtypes.length;
  if (current && subtypes.some((subtype) => subtype.id === current)) {
    characterSubtypeInput.value = current;
  }
}

function setupCharacterOptions() {
  if (!characterData) {
    return;
  }

  if (characterRaceInput) {
    const current = characterRaceInput.value;
    characterRaceInput.innerHTML = `
      <option value="">Случайная</option>
      ${(characterData.races || [])
        .map((race) => `<option value="${escapeHtml(race.id)}">${escapeHtml(race.name)}</option>`)
        .join("")}
    `;
    if (current && (characterData.races || []).some((race) => race.id === current)) {
      characterRaceInput.value = current;
    }
    setupCharacterSubtypeOptions();
  }

  if (characterClassInput) {
    const current = characterClassInput.value;
    characterClassInput.innerHTML = `
      <option value="">Случайный</option>
      ${(characterData.classes || [])
        .map((characterClass) => `<option value="${escapeHtml(characterClass.id)}">${escapeHtml(characterClass.name)}</option>`)
        .join("")}
    `;
    if (current && (characterData.classes || []).some((characterClass) => characterClass.id === current)) {
      characterClassInput.value = current;
    }
  }
}

function getCharacterAbilityItems() {
  return characterData?.abilities?.items?.length
    ? characterData.abilities.items
    : Object.entries(ABILITY_LABELS_RU).map(([id, name]) => ({ id, name, short: name.slice(0, 3) }));
}

function getAbilityLabel(ability) {
  return getCharacterAbilityItems().find((item) => item.id === ability)?.name || ABILITY_LABELS_RU[ability] || ability || "-";
}

function getCharacterLevel() {
  const selected = Number(characterLevelInput?.value);
  return selected >= 1 && selected <= 20 ? selected : randomInt(1, 20);
}

function getSelectedCharacterClass() {
  const classes = characterData?.classes || [];
  const selected = characterClassInput?.value;
  return classes.find((characterClass) => characterClass.id === selected) || pickFrom(classes);
}

function getSelectedCharacterRace() {
  const races = characterData?.races || [];
  const selected = characterRaceInput?.value;
  return races.find((race) => race.id === selected) || pickFrom(races);
}

function getProgressionRow(characterClass, level) {
  return (characterClass.progression || []).find((row) => row.level === level) || null;
}

function getAdvancementRow(level) {
  return (characterData?.advancement?.levels || []).find((row) => row.level === level) || null;
}

function getFeatureEntryLevel(feature) {
  const levels = (feature?.levels || []).filter((item) => Number(item) > 0);
  return levels.length ? Math.min(...levels) : 20;
}

function getAvailableFeatures(source, level) {
  return (source?.features || [])
    .map((feature) => ({
      ...feature,
      availableLevels: (feature.levels || []).filter((featureLevel) => featureLevel <= level),
    }))
    .filter((feature) => feature.availableLevels.length)
    .filter((feature) => !isAbilityIncreaseTrait(feature) && !isInvalidFeatureName(feature));
}

function getArchetypeEntryLevel(archetype) {
  const levels = (archetype?.features || []).flatMap((feature) => feature.levels || []).filter((level) => Number(level) > 0);
  return levels.length ? Math.min(...levels) : 3;
}

function getCharacterArchetype(characterClass, level) {
  const archetypes = (characterClass.archetypes || [])
    .map((archetype) => ({ ...archetype, entryLevel: getArchetypeEntryLevel(archetype) }))
    .filter((archetype) => archetype.entryLevel <= level);
  return pickFrom(archetypes) || null;
}

function generateCharacterName() {
  return `${pickFrom(CHARACTER_NAME_STARTS)}${pickFrom(CHARACTER_NAME_ENDS)} ${pickFrom(CHARACTER_SURNAMES)}`;
}

function getCharacterPriority(characterClass) {
  const explicit = (characterClass.primary_abilities || []).filter(Boolean);
  const fallback = CHARACTER_CLASS_PRIORITIES[characterClass.id] || ["strength", "dexterity", "constitution", "wisdom", "intelligence", "charisma"];
  return [...new Set([...explicit, ...fallback])];
}

function isHumanRace(race) {
  return race?.id === "chelovek";
}

function getSelectedRaceSubtype(race) {
  const subtypes = getRaceSubtypeOptions(race);
  if (!subtypes.length) {
    return null;
  }
  const selected = characterSubtypeInput?.value || "";
  return subtypes.find((subtype) => subtype.id === selected) || pickFrom(subtypes);
}

function getSubtypeStartIndexes(race) {
  const titles = new Set(RACE_SUBTYPE_TITLES[race?.id] || []);
  return (race?.traits || [])
    .map((trait, index) => (titles.has(trait.name) ? index : -1))
    .filter((index) => index >= 0);
}

function getBaseRaceTraits(race, subtypeIndexes) {
  const traits = race?.traits || [];
  if (!subtypeIndexes.length) {
    return traits;
  }
  return traits.slice(0, Math.min(...subtypeIndexes));
}

function getSubtypeTraitGroup(race, subtype) {
  if (!race || !subtype) {
    return [];
  }
  const traits = race.traits || [];
  const indexes = getSubtypeStartIndexes(race);
  const start = traits.findIndex((trait) => trait.name === subtype.id);
  if (start < 0) {
    return [];
  }
  const end = indexes.find((index) => index > start) ?? traits.length;
  return traits.slice(start, end);
}

function parseAbilityIncreasesFromTraits(traits, priority = []) {
  const rows = [];
  const abilityForms = [
    { ability: "strength", pattern: /сил[аы]?/i },
    { ability: "dexterity", pattern: /ловкост[ьи]?/i },
    { ability: "constitution", pattern: /телосложени[ея]?/i },
    { ability: "intelligence", pattern: /интеллект[а]?/i },
    { ability: "wisdom", pattern: /мудрост[ьи]?/i },
    { ability: "charisma", pattern: /харизм[аы]?/i },
  ];

  (traits || [])
    .filter(isAbilityIncreaseTrait)
    .forEach((trait) => {
      const text = String(trait.description || "").replace(/\bl\b/gi, "1");
      const lower = text.toLowerCase();
      if (lower.includes("всех") && lower.includes("характеристик")) {
        getCharacterAbilityItems().forEach((ability) => rows.push({ ability: ability.id, amount: 1 }));
        return;
      }

      abilityForms.forEach(({ ability, pattern }) => {
        const match = text.match(new RegExp(`${pattern.source}[\\s\\S]{0,90}?увелич\\S*\\s+на\\s+(\\d+)`, "i"));
        if (match) {
          rows.push({ ability, amount: Number(match[1]) || 1 });
        }
      });
    });

  if (!rows.length && priority.length) {
    rows.push({ ability: priority[0], amount: 1 });
  }

  return rows;
}

function getHumanRaceVariant(priority) {
  const selected = getSelectedRaceSubtype({ id: "chelovek" });
  const variantId = selected?.id || (randomInt(1, 2) === 1 ? "альтернативный человек" : "стандартный человек");
  if (variantId === "стандартный человек") {
    return {
      id: "standard-human",
      label: "стандартный человек",
      abilityIncreases: getCharacterAbilityItems().map((ability) => ({ ability: ability.id, amount: 1 })),
      extraSkill: null,
      traits: [],
    };
  }

  if (variantId === "альтернативный человек") {
    return {
      id: "variant-human",
      label: "альтернативный человек",
      abilityIncreases: [
        { ability: priority[0], amount: 1 },
        { ability: priority.find((ability) => ability !== priority[0]) || priority[1], amount: 1 },
      ],
      extraSkill: pickFrom(characterData?.skills || []),
      traits: [],
    };
  }

  const race = (characterData?.races || []).find((entry) => entry.id === "chelovek");
  const subtypeTraits = getSubtypeTraitGroup(race, { id: variantId });
  return {
    id: variantId,
    label: variantId,
    abilityIncreases: parseAbilityIncreasesFromTraits(subtypeTraits, priority),
    extraSkill: null,
    traits: subtypeTraits,
  };
}

function getRaceAbilityProfile(race, priority) {
  if (isHumanRace(race)) {
    return getHumanRaceVariant(priority);
  }

  const subtypeIndexes = getSubtypeStartIndexes(race);
  const selectedSubtype = getSelectedRaceSubtype(race);
  const baseTraits = getBaseRaceTraits(race, subtypeIndexes);
  const subtypeTraits = getSubtypeTraitGroup(race, selectedSubtype);
  const traits = [...baseTraits, ...subtypeTraits];
  const abilityIncreases = parseAbilityIncreasesFromTraits(traits, priority);

  return {
    id: selectedSubtype?.id || race.id,
    label: selectedSubtype?.name || "",
    abilityIncreases: abilityIncreases.length ? abilityIncreases : race.ability_score_increases || [],
    extraSkill: null,
    traits,
  };
}

function addAbilityScore(scores, ability, amount) {
  if (!ability || !Object.hasOwn(scores, ability)) {
    return;
  }
  scores[ability] = Math.min(20, scores[ability] + Number(amount || 0));
}

function applyClassAbilityImprovements(scores, characterClass, level, priority) {
  const feature = (characterClass.features || []).find((item) => item.name.toLowerCase().includes("увеличение") && (item.levels || []).length);
  const improvementLevels = (feature?.levels || []).filter((featureLevel) => featureLevel <= level);
  const rows = [];

  improvementLevels.forEach((featureLevel) => {
    const ability = priority.find((item) => scores[item] < 20) || priority[0];
    const before = scores[ability];
    addAbilityScore(scores, ability, 2);
    rows.push(`${featureLevel} уровень: ${getAbilityLabel(ability)} ${before} → ${scores[ability]}`);
  });

  return rows;
}

function buildCharacterAbilityScores(characterClass, race, level) {
  const abilityItems = getCharacterAbilityItems();
  const standardArray = characterData?.abilities?.standard_array || [15, 14, 13, 12, 10, 8];
  const priority = getCharacterPriority(characterClass);
  const raceProfile = getRaceAbilityProfile(race, priority);
  const scores = {};

  priority.forEach((ability, index) => {
    scores[ability] = standardArray[index] ?? 8;
  });
  abilityItems.forEach((ability, index) => {
    if (!Object.hasOwn(scores, ability.id)) {
      scores[ability.id] = standardArray[index] ?? 8;
    }
  });

  const racialIncreases = (raceProfile.abilityIncreases || []).map((increase) => {
    const before = scores[increase.ability] ?? 10;
    scores[increase.ability] = before;
    addAbilityScore(scores, increase.ability, increase.amount);
    return `${getAbilityLabel(increase.ability)} +${increase.amount}`;
  });
  const classImprovements = applyClassAbilityImprovements(scores, characterClass, level, priority);

  return {
    scores,
    priority,
    raceProfile,
    racialIncreases,
    classImprovements,
  };
}

function getHitDieSides(hitDie) {
  const match = String(hitDie || "").match(/\d+/);
  return match ? Number(match[0]) : 8;
}

function getCharacterHitPoints(characterClass, level, constitutionModifier) {
  const sides = getHitDieSides(characterClass.hit_die);
  const firstLevel = Math.max(1, sides + constitutionModifier);
  const rolls = Array.from({ length: Math.max(0, level - 1) }, () => randomInt(1, sides));
  const nextLevels = rolls.reduce((total, roll) => total + Math.max(1, roll + constitutionModifier), 0);
  return {
    value: firstLevel + nextLevels,
    formula: `${sides} + Тел на 1 уровне, затем броски ${level > 1 ? rolls.map((roll) => `к${sides}:${roll}`).join(", ") : "нет"} + Тел`,
    hitDice: `${level}${characterClass.hit_die || `к${sides}`}`,
    rolls,
    constitutionModifier,
  };
}

function skillNameMatches(text, skill) {
  const clean = String(text || "").toLowerCase();
  const names = [skill.name];
  if (skill.id === "animal-handling") {
    names.push("Обращение с животными");
  }
  return names.some((name) => clean.includes(name.toLowerCase()));
}

function inferSkillChoiceCount(characterClass) {
  const fallback = {
    bard: 3,
    plut: 4,
    sledopyt: 3,
  };
  const text = characterClass.skill_proficiencies_text || "";
  const wordMatch = text.match(/выберите\s+(один|два|три|четыре|пять|шесть|\d+)/i);
  const values = { один: 1, два: 2, три: 3, четыре: 4, пять: 5, шесть: 6 };
  if (wordMatch) {
    return Number(wordMatch[1]) || values[wordMatch[1].toLowerCase()] || fallback[characterClass.id] || 2;
  }
  return fallback[characterClass.id] || 2;
}

function getCharacterSkillOptions(characterClass) {
  const skills = characterData?.skills || [];
  const text = characterClass.skill_proficiencies_text || "";
  const options = skills.filter((skill) => skillNameMatches(text, skill));
  if (options.length) {
    return options;
  }
  if (characterClass.id === "bard") {
    return skills;
  }
  return skills.filter((skill) => (CHARACTER_CLASS_PRIORITIES[characterClass.id] || []).includes(skill.ability));
}

function pickUnique(values, count) {
  const pool = [...(values || [])];
  const result = [];
  while (pool.length && result.length < count) {
    const index = randomInt(0, pool.length - 1);
    result.push(pool.splice(index, 1)[0]);
  }
  return result;
}

function getEquipmentArmor(id) {
  return (equipmentData?.armor || []).find((item) => item.id === id);
}

function getEquipmentShield(id = "shield") {
  return (equipmentData?.shields || []).find((item) => item.id === id);
}

function getEquipmentWeapon(id) {
  return (equipmentData?.weapons || []).find((item) => item.id === id);
}

function getEquipmentWeaponPool(pool) {
  const weapons = equipmentData?.weapons || [];
  if (pool === "simple") {
    return weapons.filter((weapon) => weapon.category?.startsWith("simple_"));
  }
  if (pool === "martial") {
    return weapons.filter((weapon) => weapon.category?.startsWith("martial_"));
  }
  return weapons.filter((weapon) => weapon.category === pool);
}

function isEquipmentChoiceGroup(group) {
  if (!Array.isArray(group) || group.length < 2) {
    return false;
  }
  const types = group.map((entry) => entry.type);
  const weaponTypes = new Set(["weapon", "pick_weapon"]);
  return types.every((type) => weaponTypes.has(type)) || types.every((type) => type === "armor") || types.every((type) => type === "pack");
}

function addEquipmentItem(items, item) {
  const key = `${item.type}:${item.id || item.name}`;
  const existing = items.find((entry) => entry.key === key);
  if (existing) {
    existing.quantity += item.quantity || 1;
    return;
  }
  items.push({ ...item, key, quantity: item.quantity || 1 });
}

function expandEquipmentEntry(entry) {
  if (!entry) {
    return null;
  }
  if (entry.type === "pick_weapon") {
    const weapon = pickFrom(getEquipmentWeaponPool(entry.pool));
    return weapon ? { type: "weapon", id: weapon.id, name: weapon.name, quantity: entry.quantity || 1 } : null;
  }
  if (entry.type === "weapon") {
    const weapon = getEquipmentWeapon(entry.id);
    return weapon ? { type: "weapon", id: weapon.id, name: weapon.name, quantity: entry.quantity || 1 } : null;
  }
  if (entry.type === "armor") {
    const armor = getEquipmentArmor(entry.id);
    return armor ? { type: "armor", id: armor.id, name: armor.name, quantity: entry.quantity || 1 } : null;
  }
  if (entry.type === "shield") {
    const shield = getEquipmentShield(entry.id);
    return shield ? { type: "shield", id: shield.id, name: shield.name, quantity: entry.quantity || 1 } : null;
  }
  if (entry.type === "pack") {
    return { type: "pack", id: entry.id, name: equipmentData?.packs?.[entry.id] || entry.id, quantity: entry.quantity || 1 };
  }
  return { type: "misc", name: entry.name || "-", quantity: entry.quantity || 1 };
}

function buildStartingEquipment(characterClass) {
  const items = [];
  const kit = equipmentData?.class_starting_equipment?.[characterClass.id] || [];
  kit.forEach((group) => {
    const selectedEntries = isEquipmentChoiceGroup(group) ? [pickFrom(group)] : group;
    selectedEntries.map(expandEquipmentEntry).filter(Boolean).forEach((item) => addEquipmentItem(items, item));
  });
  return items.map(({ key, ...item }) => item);
}

function calculateArmorValue(armor, dexModifier) {
  if (!armor) {
    return 10 + dexModifier;
  }
  if (armor.category === "heavy") {
    return armor.base_ac;
  }
  const dexBonus = armor.dex_max === null ? dexModifier : Math.min(dexModifier, armor.dex_max);
  return armor.base_ac + dexBonus;
}

function getSelectedFightingStyle(choices) {
  return choices?.find((choice) => choice.label === "Боевой стиль")?.values?.[0] || "";
}

function buildArmorClass(characterClass, abilities, equipment, choices) {
  const dexModifier = abilityModifier(abilities.dexterity || 10);
  const conModifier = abilityModifier(abilities.constitution || 10);
  const wisModifier = abilityModifier(abilities.wisdom || 10);
  const armorItem = equipment.find((item) => item.type === "armor");
  const shieldItem = equipment.find((item) => item.type === "shield");
  const armor = armorItem ? getEquipmentArmor(armorItem.id) : null;
  const shield = shieldItem ? getEquipmentShield(shieldItem.id) : null;
  const shieldBonus = shield?.ac_bonus || 0;
  const defenseBonus = getSelectedFightingStyle(choices) === "Оборона" && armor ? 1 : 0;
  const candidates = [
    {
      label: armor ? armor.name : "Без доспеха",
      value: calculateArmorValue(armor, dexModifier) + shieldBonus + defenseBonus,
      armor: armor?.name || "",
      shield: shield?.name || "",
      stealthDisadvantage: Boolean(armor?.stealth_disadvantage),
      strength: armor?.strength || null,
    },
  ];

  if (characterClass.id === "varvar") {
    candidates.push({
      label: "Защита без доспехов",
      value: 10 + dexModifier + conModifier + shieldBonus,
      armor: "",
      shield: shield?.name || "",
      stealthDisadvantage: false,
      strength: null,
    });
  }
  if (characterClass.id === "monah") {
    candidates.push({
      label: "Защита без доспехов",
      value: 10 + dexModifier + wisModifier,
      armor: "",
      shield: "",
      stealthDisadvantage: false,
      strength: null,
    });
  }

  return candidates.sort((left, right) => right.value - left.value)[0];
}

function getWeaponAbility(weapon, abilities) {
  const strength = abilityModifier(abilities.strength || 10);
  const dexterity = abilityModifier(abilities.dexterity || 10);
  const properties = weapon.properties || [];
  if (properties.includes("Фехтовальное")) {
    return dexterity > strength ? { id: "dexterity", modifier: dexterity } : { id: "strength", modifier: strength };
  }
  if (weapon.category?.endsWith("_ranged") && !properties.includes("Метательное")) {
    return { id: "dexterity", modifier: dexterity };
  }
  return { id: "strength", modifier: strength };
}

function buildWeaponAttacks(equipment, abilities, proficiencyBonus) {
  return equipment
    .filter((item) => item.type === "weapon")
    .map((item) => {
      const weapon = getEquipmentWeapon(item.id);
      if (!weapon) {
        return null;
      }
      const ability = getWeaponAbility(weapon, abilities);
      const damageBonus = ability.modifier ? signed(ability.modifier) : "";
      return {
        id: weapon.id,
        name: weapon.name,
        quantity: item.quantity || 1,
        attackBonus: proficiencyBonus + ability.modifier,
        ability: ability.id,
        damage: weapon.damage === "—" ? "—" : `${weapon.damage}${damageBonus} ${weapon.damage_type}`.trim(),
        versatileDamage: weapon.versatile_damage ? `${weapon.versatile_damage}${damageBonus} ${weapon.damage_type}`.trim() : "",
        range: weapon.range || "",
        properties: weapon.properties || [],
      };
    })
    .filter(Boolean);
}

function buildCharacterEquipment(characterClass, abilities, proficiencyBonus, choices) {
  const items = buildStartingEquipment(characterClass);
  return {
    items,
    armorClass: buildArmorClass(characterClass, abilities, items, choices),
    attacks: buildWeaponAttacks(items, abilities, proficiencyBonus),
  };
}

function buildCharacterSkills(characterClass, abilityScores, proficiencyBonus, extraSkill = null) {
  const count = inferSkillChoiceCount(characterClass);
  const selected = pickUnique(getCharacterSkillOptions(characterClass), count);
  if (extraSkill && !selected.some((skill) => skill.id === extraSkill.id)) {
    selected.push(extraSkill);
  }
  return selected.map((skill) => {
    const modifier = abilityModifier(abilityScores[skill.ability] || 10);
    return {
      ...skill,
      bonus: modifier + proficiencyBonus,
      modifier,
    };
  });
}

function buildCharacterSkillChecks(abilityScores, proficiencyBonus, proficientSkills, expertiseSkills = []) {
  const proficientIds = new Set((proficientSkills || []).map((skill) => skill.id));
  const expertiseNames = new Set(expertiseSkills);
  return (characterData?.skills || []).map((skill) => {
    const modifier = abilityModifier(abilityScores[skill.ability] || 10);
    const proficient = proficientIds.has(skill.id);
    const expertise = expertiseNames.has(skill.name);
    const mastery = expertise ? proficiencyBonus * 2 : proficient ? proficiencyBonus : 0;
    return {
      ...skill,
      modifier,
      proficient,
      expertise,
      mastery,
      bonus: modifier + mastery,
    };
  });
}

function buildSavingThrows(characterClass, abilityScores, proficiencyBonus) {
  return getCharacterAbilityItems().map((ability) => {
    const proficient = (characterClass.saving_throw_proficiencies || []).includes(ability.id);
    return {
      id: ability.id,
      name: ability.name,
      bonus: abilityModifier(abilityScores[ability.id] || 10) + (proficient ? proficiencyBonus : 0),
      proficient,
    };
  });
}

function isAbilityIncreaseTrait(trait) {
  const name = String(trait?.name || "").toLowerCase().trim();
  return name.includes("увеличение") && (name.includes("характерист") || name === "увеличение");
}

function isGenericRaceTrait(trait) {
  const name = String(trait?.name || "").toLowerCase();
  return isAbilityIncreaseTrait(trait)
    || RACE_GENERIC_TRAIT_NAMES.includes(name)
    || RACE_SUBTYPE_INTRO_TITLES.some((title) => title.toLowerCase() === name)
    || ["уровень", "заклинания", "время накладывания", "дистанция", "компоненты", "длительность", "школа"].includes(name);
}

function getHumanRaceTraits(race, raceProfile) {
  if (raceProfile?.traits?.length) {
    return raceProfile.traits.filter((trait) => !isGenericRaceTrait(trait)).slice(0, 8);
  }

  const traits = race.traits || [];
  const alternativeIndex = traits.findIndex((trait) => trait.name === "Альтернативная особенность людей");
  const markIndex = traits.findIndex((trait) => String(trait.name || "").startsWith("Метка "));
  const endIndex = markIndex > -1 ? markIndex : traits.length;

  if (raceProfile?.id === "variant-human") {
    const startIndex = alternativeIndex > -1 ? alternativeIndex : 0;
    return traits.slice(startIndex, endIndex).filter((trait) => !isAbilityIncreaseTrait(trait)).slice(0, 6);
  }

  const standardTraits = alternativeIndex > -1 ? traits.slice(0, alternativeIndex) : traits.slice(0, endIndex);
  return standardTraits.filter((trait) => !isGenericRaceTrait(trait)).slice(0, 6);
}

function getRaceTraitHighlights(race, raceProfile = null) {
  if (isHumanRace(race)) {
    return getHumanRaceTraits(race, raceProfile);
  }
  const sourceTraits = raceProfile?.traits?.length ? raceProfile.traits : race.traits || [];
  const traits = sourceTraits.filter((trait) => !isGenericRaceTrait(trait));
  return traits.length ? traits.slice(0, 10) : (race.traits || []).slice(0, 8);
}

function getFeatureLevelText(feature) {
  const levels = feature.availableLevels || feature.levels || [];
  return levels.length ? `ур. ${levels.join(", ")}` : "уровень не указан";
}

function compactFeature(feature) {
  return {
    id: feature.id,
    name: feature.name,
    levels: feature.levels || [],
    availableLevels: feature.availableLevels || [],
    description: cleanCharacterFeatureText(feature.description || "", feature.name).slice(0, 12000),
  };
}

function compactCharacterClass(characterClass) {
  return {
    id: characterClass.id,
    name: characterClass.name,
    archetype_label: characterClass.archetype_label,
    primary_abilities: characterClass.primary_abilities || [],
    saving_throw_proficiencies: characterClass.saving_throw_proficiencies || [],
    saving_throw_proficiencies_text: characterClass.saving_throw_proficiencies_text || "",
    hit_die: characterClass.hit_die,
    hit_points: characterClass.hit_points || {},
    armor_proficiencies: characterClass.armor_proficiencies || "",
    weapon_proficiencies: characterClass.weapon_proficiencies || "",
    tool_proficiencies: characterClass.tool_proficiencies || "",
  };
}

function compactRace(race) {
  return {
    id: race.id,
    name: race.name,
    ability_score_increases: race.ability_score_increases || [],
    age: race.age || "",
    alignment: race.alignment || "",
    size: race.size || "",
    speed: race.speed || "",
    languages: race.languages || "",
  };
}

function compactArchetype(archetype) {
  return archetype
    ? {
        id: archetype.id,
        name: archetype.name,
        entryLevel: archetype.entryLevel,
      }
    : null;
}

function hasAvailableFeature(features, namePart) {
  return features.some((feature) => feature.name.toLowerCase().includes(namePart.toLowerCase()));
}

function getWarlockInvocationCount(level) {
  if (level >= 18) return 8;
  if (level >= 15) return 7;
  if (level >= 12) return 6;
  if (level >= 9) return 5;
  if (level >= 7) return 4;
  if (level >= 5) return 3;
  return level >= 2 ? 2 : 0;
}

function getArtificerInfusionCount(level) {
  if (level >= 14) return 5;
  if (level >= 10) return 4;
  if (level >= 6) return 3;
  return level >= 2 ? 2 : 0;
}

function getBattleManeuverCount(level) {
  if (level >= 15) return 9;
  if (level >= 10) return 7;
  if (level >= 7) return 5;
  return level >= 3 ? 3 : 0;
}

function buildCharacterChoices(character, abilityResult) {
  const choices = [];
  const classFeatures = character.classFeatures || [];
  const selectedSkillNames = character.skills.map((skill) => skill.name);

  if (hasAvailableFeature(classFeatures, "Боевой стиль")) {
    choices.push({ label: "Боевой стиль", values: [pickFrom(FIGHTING_STYLES)] });
  }
  if (hasAvailableFeature(classFeatures, "Компетентность")) {
    const expertiseCount = character.level >= 10 || character.characterClass.id === "plut" && character.level >= 6 ? 4 : 2;
    choices.push({ label: "Компетентность", values: pickUnique(selectedSkillNames, Math.min(expertiseCount, selectedSkillNames.length)) });
  }
  if (hasAvailableFeature(classFeatures, "Метамагия")) {
    const metamagicCount = character.level >= 17 ? 4 : character.level >= 10 ? 3 : 2;
    choices.push({ label: "Метамагия", values: pickUnique(METAMAGIC_OPTIONS, metamagicCount) });
  }
  if (hasAvailableFeature(classFeatures, "Таинственные воззвания")) {
    choices.push({ label: "Таинственные воззвания", values: pickUnique(WARLOCK_INVOCATIONS, getWarlockInvocationCount(character.level)) });
  }
  if (hasAvailableFeature(classFeatures, "Предмет договора")) {
    choices.push({ label: "Предмет договора", values: [pickFrom(PACT_BOONS)] });
  }
  if (hasAvailableFeature(classFeatures, "Избранный враг")) {
    choices.push({ label: "Избранный враг", values: [pickFrom(RANGER_FAVORED_ENEMIES)] });
  }
  if (hasAvailableFeature(classFeatures, "Исследователь природы")) {
    choices.push({ label: "Излюбленная местность", values: [pickFrom(RANGER_TERRAINS)] });
  }
  if (hasAvailableFeature(classFeatures, "Наполнение предмета эссенцией")) {
    choices.push({ label: "Инфузии", values: pickUnique(ARTIFICER_INFUSIONS, getArtificerInfusionCount(character.level)) });
  }
  if (character.archetype?.name === "Мастер боевых искусств") {
    choices.push({ label: "Боевые приёмы", values: pickUnique(BATTLE_MASTER_MANEUVERS, getBattleManeuverCount(character.level)) });
  }

  return choices.filter((choice) => choice.values?.length);
}

function getMaxSpellLevelFromSlots(slots) {
  return Math.max(0, ...Object.entries(slots || {}).filter(([, count]) => Number(count) > 0).map(([level]) => Number(level)));
}

function slotsArrayToMap(slots) {
  return Object.fromEntries((slots || []).map((count, index) => [index + 1, count]).filter(([, count]) => count > 0));
}

function getWarlockPactSlotLevel(level) {
  if (level >= 9) return 5;
  if (level >= 7) return 4;
  if (level >= 5) return 3;
  if (level >= 3) return 2;
  return 1;
}

function getPreparedSpellCount(characterClass, level, spellAbilityModifier) {
  if (characterClass.id === "bard") return BARD_KNOWN_SPELLS[level] || 0;
  if (characterClass.id === "charodey") return SORCERER_KNOWN_SPELLS[level] || 0;
  if (characterClass.id === "koldun") return WARLOCK_KNOWN_SPELLS[level] || 0;
  if (characterClass.id === "sledopyt") return RANGER_KNOWN_SPELLS[level] || 0;
  if (characterClass.id === "paladin") return level >= 2 ? Math.max(1, Math.floor(level / 2) + spellAbilityModifier) : 0;
  if (characterClass.id === "izobretatel") return Math.max(1, Math.ceil(level / 2) + spellAbilityModifier);
  return Math.max(1, level + spellAbilityModifier);
}

function getCharacterSpellPlan(characterClass, level, archetype, abilityScores, proficiencyBonus) {
  const ability = CHARACTER_SPELLCASTING_ABILITIES[characterClass.id];
  const spellAbilityModifier = abilityModifier(abilityScores[ability] || 10);
  let classKey = CHARACTER_CLASS_SPELL_KEYS[characterClass.id];
  let slots = {};
  let cantripCount = 0;
  let knownCount = 0;
  let pactSlotLevel = 0;
  let sourceNote = "";

  if (["bard", "zhrets", "druid", "charodey", "volshebnik"].includes(characterClass.id)) {
    slots = slotsArrayToMap(FULL_CASTER_SLOTS[level]);
    cantripCount = FULL_CASTER_CANTRIPS[classKey]?.[level] || 0;
    knownCount = getPreparedSpellCount(characterClass, level, spellAbilityModifier);
  } else if (characterClass.id === "koldun") {
    pactSlotLevel = getWarlockPactSlotLevel(level);
    slots = { [pactSlotLevel]: WARLOCK_SLOTS[level]?.[0] || 0 };
    cantripCount = FULL_CASTER_CANTRIPS.warlock[level] || 0;
    knownCount = getPreparedSpellCount(characterClass, level, spellAbilityModifier);
  } else if (["paladin", "sledopyt"].includes(characterClass.id)) {
    slots = slotsArrayToMap(HALF_CASTER_SLOTS[level]);
    knownCount = getPreparedSpellCount(characterClass, level, spellAbilityModifier);
  } else if (characterClass.id === "izobretatel") {
    slots = slotsArrayToMap(ARTIFICER_SLOTS[level]);
    classKey = "wizard";
    cantripCount = level >= 10 ? 3 : 2;
    knownCount = getPreparedSpellCount(characterClass, level, spellAbilityModifier);
    sourceNote = "В локальной базе нет отдельного списка изобретателя, поэтому набор подобран из арканных заклинаний.";
  } else if ((characterClass.id === "voin" && archetype?.name === "Мистический рыцарь") || (characterClass.id === "plut" && archetype?.name === "Мистический ловкач")) {
    slots = slotsArrayToMap(THIRD_CASTER_SLOTS[level]);
    classKey = "wizard";
    cantripCount = level >= 10 ? 3 : 2;
    knownCount = THIRD_CASTER_KNOWN_SPELLS[level] || 0;
  }

  const maxSpellLevel = getMaxSpellLevelFromSlots(slots);
  if (!classKey || !maxSpellLevel && !cantripCount) {
    return null;
  }

  return {
    classKey,
    characterLevel: level,
    ability,
    cantripCount,
    knownCount,
    slots,
    maxSpellLevel,
    pactSlotLevel,
    saveDc: 8 + proficiencyBonus + spellAbilityModifier,
    attackBonus: proficiencyBonus + spellAbilityModifier,
    sourceNote,
  };
}

function getSpellCardName(spell) {
  return spell?.name_ru || spell?.name || "-";
}

function getSpellPool(classKey, level) {
  return spellsIndex.filter((spell) => Number(spell.level) === Number(level) && (spell.classes || []).some((item) => item.key === classKey));
}

function pickSpellsFromPool(classKey, level, count, usedIds) {
  const pool = getSpellPool(classKey, level).filter((spell) => !usedIds.has(spell.id));
  const picked = pickUnique(pool, count);
  picked.forEach((spell) => usedIds.add(spell.id));
  return picked;
}

function buildCharacterSpellbook(plan) {
  if (!plan) {
    return null;
  }

  const usedIds = new Set();
  const cantrips = pickSpellsFromPool(plan.classKey, 0, plan.cantripCount, usedIds);
  const spellLevels = Array.from({ length: plan.maxSpellLevel }, (_, index) => index + 1);
  const leveledSpells = [];

  spellLevels.forEach((spellLevel) => {
    if (leveledSpells.length < plan.knownCount) {
      leveledSpells.push(...pickSpellsFromPool(plan.classKey, spellLevel, 1, usedIds));
    }
  });

  let attempts = 0;
  while (leveledSpells.length < plan.knownCount && spellLevels.length && attempts < plan.knownCount * 20) {
    attempts += 1;
    const weightedLevels = spellLevels.flatMap((spellLevel) => Array.from({ length: Math.max(1, plan.slots[spellLevel] || 1) }, () => spellLevel));
    const spellLevel = pickFrom(weightedLevels);
    const picked = pickSpellsFromPool(plan.classKey, spellLevel, 1, usedIds);
    if (!picked.length && !spellLevels.some((level) => getSpellPool(plan.classKey, level).some((spell) => !usedIds.has(spell.id)))) {
      break;
    }
    leveledSpells.push(...picked);
  }

  const arcanum = [11, 13, 15, 17]
    .map((characterLevel, index) => ({ characterLevel, spellLevel: index + 6 }))
    .filter((item) => plan.classKey === "warlock" && item.characterLevel <= plan.characterLevel)
    .flatMap((item) => pickSpellsFromPool(plan.classKey, item.spellLevel, 1, usedIds));

  return {
    cantrips,
    spells: leveledSpells.sort((left, right) => Number(left.level) - Number(right.level) || getSpellCardName(left).localeCompare(getSpellCardName(right), "ru")),
    arcanum,
  };
}

function getSpellSlotsText(plan) {
  if (!plan?.slots) {
    return "-";
  }
  if (plan.pactSlotLevel) {
    return `${plan.slots[plan.pactSlotLevel]} яч. ${plan.pactSlotLevel} уровня`;
  }
  return Object.entries(plan.slots)
    .map(([level, count]) => `${level}: ${count}`)
    .join(" · ") || "-";
}

function buildCharacter() {
  const characterClass = getSelectedCharacterClass();
  const race = getSelectedCharacterRace();
  const level = getCharacterLevel();
  const progression = getProgressionRow(characterClass, level);
  const proficiencyBonus = progression?.proficiency_bonus || getAdvancementRow(level)?.proficiency_bonus || 2;
  const archetype = getCharacterArchetype(characterClass, level);
  const classFeatures = getAvailableFeatures(characterClass, level);
  const archetypeFeatures = getAvailableFeatures(archetype, level);
  const abilityResult = buildCharacterAbilityScores(characterClass, race, level);
  const constitutionModifier = abilityModifier(abilityResult.scores.constitution || 10);
  const hitPoints = getCharacterHitPoints(characterClass, level, constitutionModifier);
  const skills = buildCharacterSkills(characterClass, abilityResult.scores, proficiencyBonus, abilityResult.raceProfile.extraSkill);
  const spellPlan = getCharacterSpellPlan(characterClass, level, archetype, abilityResult.scores, proficiencyBonus);
  const spellbook = buildCharacterSpellbook(spellPlan);
  const character = {
    kind: "player-character",
    id: crypto.randomUUID(),
    name: generateCharacterName(),
    level,
    race: compactRace(race),
    raceVariant: abilityResult.raceProfile.label || "",
    characterClass: compactCharacterClass(characterClass),
    archetype: compactArchetype(archetype),
    proficiencyBonus,
    abilities: abilityResult.scores,
    abilityPriority: abilityResult.priority,
    racialIncreases: abilityResult.racialIncreases,
    hitPoints,
    skills,
    savingThrows: buildSavingThrows(characterClass, abilityResult.scores, proficiencyBonus),
    classFeatures: classFeatures.map(compactFeature),
    archetypeFeatures: archetypeFeatures.map(compactFeature),
    raceTraits: getRaceTraitHighlights(race, abilityResult.raceProfile).map(compactFeature),
    spellcasting: spellPlan,
    spellbook,
    source: "Klassy.pdf + Rasy.pdf + локальная база заклинаний",
    savedAt: new Date().toISOString(),
  };
  character.choices = buildCharacterChoices(character, abilityResult);
  character.expertiseSkills = character.choices.find((choice) => choice.label === "Компетентность")?.values || [];
  character.skillChecks = buildCharacterSkillChecks(abilityResult.scores, proficiencyBonus, skills, character.expertiseSkills);
  character.equipment = buildCharacterEquipment(characterClass, abilityResult.scores, proficiencyBonus, character.choices);
  return character;
}

function getCharacterCardSummary(character) {
  const archetype = character.archetype?.name || "архетип ещё не выбран";
  return `${character.level} ур. · ${character.characterClass?.name || "-"} · ${character.race?.name || "-" } · ${archetype}`;
}

function renderCharacterCard(character, { saved = false } = {}) {
  const removeAttr = saved
    ? `data-delete-saved-character="${escapeHtml(character.id)}"`
    : `data-delete-character-result="${escapeHtml(character.id)}"`;
  const openAttr = saved
    ? `data-open-saved-character="${escapeHtml(character.id)}"`
    : `data-open-character-result="${escapeHtml(character.id)}"`;
  const spellText = character.spellcasting
    ? `Заклинания: ${character.spellbook?.cantrips?.length || 0} заг., ${character.spellbook?.spells?.length || 0} закл.`
    : "Без классового набора заклинаний";

  return `
    <article class="saved-monster-card character-card" ${openAttr} tabindex="0" role="button">
      <button class="card-remove" type="button" ${removeAttr} aria-label="Удалить персонажа">×</button>
      <span>${escapeHtml(getCharacterCardSummary(character))}</span>
      <strong>${escapeHtml(character.name || "Персонаж")}</strong>
      <em>HP ${escapeHtml(character.hitPoints?.value || "-")} · КД ${escapeHtml(character.equipment?.armorClass?.value || "-")} · БМ +${escapeHtml(character.proficiencyBonus || 2)}</em>
      <small class="potion-description">${escapeHtml(spellText)}</small>
    </article>
  `;
}

function renderCharacterAbilities(character) {
  return `
    <div class="character-ability-grid">
      ${getCharacterAbilityItems().map((ability) => {
        const score = character.abilities?.[ability.id] || 10;
        return `
          <div>
            <span>${escapeHtml(ability.short || ability.name)}</span>
            <strong>${escapeHtml(score)}</strong>
            <small>${escapeHtml(signed(abilityModifier(score)))}</small>
          </div>
        `;
      }).join("")}
    </div>
  `;
}

function renderCharacterSkillsAndChoices(character) {
  const skillChecks = character.skillChecks?.length
    ? character.skillChecks
    : buildCharacterSkillChecks(character.abilities || {}, character.proficiencyBonus || 2, character.skills || [], character.expertiseSkills || []);
  return `
    <section class="monster-section">
      <h4>Навыки</h4>
      <div class="character-skill-grid">
        ${skillChecks.map((skill) => `
          <div>
            <strong>${escapeHtml(skill.name)} ${skill.proficient ? "<span>владение</span>" : ""}${skill.expertise ? "<span>компетентность</span>" : ""}</strong>
            <span>${escapeHtml(signed(skill.bonus))}</span>
          </div>
        `).join("")}
      </div>
    </section>
  `;
}

function renderCharacterEquipment(character) {
  const equipment = character.equipment || {};
  const armorClass = equipment.armorClass || {};
  const items = equipment.items || [];
  const attacks = equipment.attacks || [];
  const otherItems = items.filter((item) => !["weapon", "armor", "shield"].includes(item.type));

  return `
    <section class="monster-section">
      <h4>Снаряжение</h4>
      <div class="monster-meta-grid">
        <div><span>Доспех</span><strong>${escapeHtml(armorClass.armor || "нет")}</strong></div>
        <div><span>Щит</span><strong>${escapeHtml(armorClass.shield || "нет")}</strong></div>
      </div>
      ${(armorClass.stealthDisadvantage || armorClass.strength) ? `
        <div class="monster-text-list">
          ${armorClass.stealthDisadvantage ? "<p>Скрытность: помеха от доспеха.</p>" : ""}
          ${armorClass.strength ? `<p>Требование силы для доспеха: ${escapeHtml(armorClass.strength)}.</p>` : ""}
        </div>
      ` : ""}
      ${attacks.length ? `
        <div class="potion-table-wrap character-equipment-table">
          <table class="potion-table">
            <thead>
              <tr>
                <th>Оружие</th>
                <th>Атака</th>
                <th>Урон</th>
                <th>Свойства</th>
              </tr>
            </thead>
            <tbody>
              ${attacks.map((attack) => `
                <tr>
                  <td>${escapeHtml(`${attack.name}${attack.quantity > 1 ? ` x${attack.quantity}` : ""}`)}</td>
                  <td>${escapeHtml(signed(attack.attackBonus))}</td>
                  <td>${escapeHtml([attack.damage, attack.versatileDamage ? `унив. ${attack.versatileDamage}` : ""].filter(Boolean).join(" / "))}</td>
                  <td>${escapeHtml([...(attack.properties || []), attack.range ? `дис. ${attack.range}` : ""].filter(Boolean).join(", ") || "-")}</td>
                </tr>
              `).join("")}
            </tbody>
          </table>
        </div>
      ` : ""}
      ${otherItems.length ? `
        <div class="monster-text-list">
          ${otherItems.map((item) => `<p>${escapeHtml(`${item.name}${item.quantity > 1 ? ` x${item.quantity}` : ""}`)}</p>`).join("")}
        </div>
      ` : ""}
    </section>
  `;
}

function splitCharacterRollTables(text) {
  const lines = String(text || "").split("\n");
  const body = [];
  const tables = [];

  for (let index = 0; index < lines.length; index += 1) {
    const line = lines[index].trim();
    const titleMatch = line.match(/^Таблица\s+(.+)/i);
    const headerMatch = lines[index + 1]?.trim().match(/^([кd]\d+)\s+(.+)/i);
    if (!titleMatch || !headerMatch) {
      body.push(lines[index]);
      continue;
    }

    const table = {
      title: `Таблица ${titleMatch[1].trim()}`,
      rollLabel: headerMatch[1],
      resultLabel: headerMatch[2],
      rows: [],
    };
    index += 1;

    let current = null;
    while (index + 1 < lines.length) {
      const nextLine = lines[index + 1].trim();
      if (!nextLine) {
        index += 1;
        continue;
      }
      if (/^Таблица\s+.+/i.test(nextLine)) {
        break;
      }
      const rowMatch = nextLine.match(/^(\d{1,3})\s+(.+)/);
      if (rowMatch) {
        if (current) {
          table.rows.push(current);
        }
        current = { roll: rowMatch[1], result: rowMatch[2] };
      } else if (current) {
        current.result = `${current.result} ${nextLine}`.trim();
      } else {
        body.push(nextLine);
      }
      index += 1;
    }

    if (current) {
      table.rows.push(current);
    }
    if (table.rows.length) {
      tables.push(table);
    } else {
      body.push(line, lines[index - 1] || "");
    }
  }

  return { body: body.join("\n").trim(), tables };
}

function renderCharacterRollTable(table) {
  return `
    <div class="character-roll-table">
      <strong>${escapeHtml(table.title)}</strong>
      <div class="potion-table-wrap">
        <table class="potion-table">
          <thead>
            <tr>
              <th>${escapeHtml(table.rollLabel)}</th>
              <th>${escapeHtml(table.resultLabel)}</th>
            </tr>
          </thead>
          <tbody>
            ${table.rows.map((row) => `
              <tr>
                <td>${escapeHtml(row.roll)}</td>
                <td>${escapeHtml(row.result)}</td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

function renderCharacterFeatureBody(feature) {
  const text = cleanCharacterFeatureText(feature.description || "", feature.name);
  if (!text) {
    return "";
  }
  const { body, tables } = splitCharacterRollTables(text);
  return `
    ${body ? `<div class="character-feature-description">${renderRichDescription(body)}</div>` : ""}
    ${tables.map(renderCharacterRollTable).join("")}
  `;
}

function renderCharacterFeatureSection(title, features) {
  if (!features?.length) {
    return "";
  }
  return `
    <details class="monster-section character-collapsible">
      <summary><span>${escapeHtml(title)}</span><small>${escapeHtml(features.length)} записей</small></summary>
      <div class="character-feature-list">
        ${features.map((feature) => `
          <div>
            <strong>${escapeHtml(feature.name)} <span>${escapeHtml(getFeatureLevelText(feature))}</span></strong>
            ${renderCharacterFeatureBody(feature)}
          </div>
        `).join("")}
      </div>
    </details>
  `;
}

function renderCharacterRaceTraits(character) {
  return renderCharacterFeatureSection("Расовые особенности", (character.raceTraits || []).map((trait) => ({
    ...trait,
    availableLevels: [1],
  })));
}

function renderCharacterSpellCard(spell, prefix = "") {
  const fullSpell = spellsById.get(spell.id) || spell;
  const classes = (fullSpell.classes || []).map((item) => getClassLabel(item.key, item.name)).join(", ") || "классы не указаны";
  const tags = [fullSpell.concentration ? "концентрация" : "", fullSpell.ritual ? "ритуал" : ""].filter(Boolean).join(" · ");
  return `
    <button class="monster-card spell-card character-spell-card" type="button" data-spell-id="${escapeHtml(fullSpell.id)}">
      <span>${escapeHtml(prefix || getSpellLevelLabel(fullSpell.level))} · ${escapeHtml(getSchoolLabel(fullSpell.school_key, fullSpell.school))}</span>
      <strong>${escapeHtml(getSpellCardName(fullSpell))}</strong>
      <em>${escapeHtml(fullSpell.name || "")}</em>
      <small>${escapeHtml(classes)}${tags ? ` · ${escapeHtml(tags)}` : ""}</small>
    </button>
  `;
}

function renderCharacterSpellSlotsTable(plan) {
  const rows = Object.entries(plan?.slots || {}).filter(([, count]) => Number(count) > 0);
  if (!rows.length) {
    return "";
  }

  return `
    <div class="potion-table-wrap character-spell-slots">
      <table class="potion-table">
        <thead>
          <tr>
            <th>Уровень ячейки</th>
            <th>Количество</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map(([level, count]) => `
            <tr>
              <td>${escapeHtml(plan.pactSlotLevel ? `${level} уровень договора` : `${level} уровень`)}</td>
              <td>${escapeHtml(count)}</td>
            </tr>
          `).join("")}
        </tbody>
      </table>
    </div>
  `;
}

function renderCharacterSpells(character) {
  const plan = character.spellcasting;
  if (!plan) {
    return `
      <details class="monster-section character-collapsible">
        <summary><span>Заклинания</span><small>нет классового набора</small></summary>
        <div class="monster-text-list"><p>Для этого класса и выбранного уровня классового набора заклинаний нет.</p></div>
      </details>
    `;
  }

  const cantrips = character.spellbook?.cantrips || [];
  const leveledSpells = character.spellbook?.spells || [];
  const arcanum = character.spellbook?.arcanum || [];
  const spellCount = cantrips.length + leveledSpells.length + arcanum.length;

  return `
    <details class="monster-section character-collapsible">
      <summary><span>Заклинания</span><small>${escapeHtml(spellCount)} записей</small></summary>
      <div class="monster-meta-grid">
        <div><span>Базовая характеристика</span><strong>${escapeHtml(getAbilityLabel(plan.ability))}</strong></div>
        <div><span>Сл спасброска</span><strong>${escapeHtml(plan.saveDc)}</strong></div>
        <div><span>Атака заклинанием</span><strong>${escapeHtml(signed(plan.attackBonus))}</strong></div>
      </div>
      ${renderCharacterSpellSlotsTable(plan)}
      ${spellCount ? `
        <div class="character-spell-grid">
          ${cantrips.map((spell) => renderCharacterSpellCard(spell)).join("")}
          ${leveledSpells.map((spell) => renderCharacterSpellCard(spell)).join("")}
          ${arcanum.map((spell) => renderCharacterSpellCard(spell, `Таинственный арканум · ${getSpellLevelLabel(spell.level)}`)).join("")}
        </div>
      ` : "<div class=\"monster-text-list\"><p>Подходящих заклинаний в локальной базе не найдено.</p></div>"}
      ${plan.sourceNote ? `<p class="license-note">${escapeHtml(plan.sourceNote)}</p>` : ""}
    </details>
  `;
}

function renderCharacterDetail(character) {
  return `
    <div class="monster-meta-grid">
      <div><span>Раса</span><strong>${escapeHtml(character.race?.name || "-")}</strong></div>
      ${character.raceVariant ? `<div><span>Вариант расы</span><strong>${escapeHtml(character.raceVariant)}</strong></div>` : ""}
      <div><span>Класс</span><strong>${escapeHtml(character.characterClass?.name || "-")}</strong></div>
      <div><span>Уровень</span><strong>${escapeHtml(character.level)}</strong></div>
      <div><span>Архетип</span><strong>${escapeHtml(character.archetype?.name || "ещё не выбран")}</strong></div>
      <div><span>Бонус мастерства</span><strong>+${escapeHtml(character.proficiencyBonus)}</strong></div>
      <div><span>Хиты</span><strong>${escapeHtml(character.hitPoints?.value || "-")} (${escapeHtml(character.hitPoints?.hitDice || "-")})</strong></div>
      <div><span>Класс доспеха</span><strong>${escapeHtml(character.equipment?.armorClass?.value || "-")}</strong></div>
    </div>

    <section class="monster-section">
      <h4>Характеристики</h4>
      ${renderCharacterAbilities(character)}
    </section>

    ${renderCharacterSkillsAndChoices(character)}
    ${renderCharacterEquipment(character)}
    ${renderTextList("Владения", [
      character.characterClass?.armor_proficiencies ? `Доспехи: ${character.characterClass.armor_proficiencies}` : "",
      character.characterClass?.weapon_proficiencies ? `Оружие: ${character.characterClass.weapon_proficiencies}` : "",
      character.characterClass?.tool_proficiencies ? `Инструменты: ${character.characterClass.tool_proficiencies}` : "",
      character.characterClass?.saving_throw_proficiencies_text ? `Спасброски: ${character.characterClass.saving_throw_proficiencies_text}` : "",
    ].filter(Boolean))}
    ${renderCharacterFeatureSection("Умения класса", character.classFeatures)}
    ${renderCharacterFeatureSection(`Умения архетипа${character.archetype?.name ? `: ${character.archetype.name}` : ""}`, character.archetypeFeatures)}
    ${renderCharacterRaceTraits(character)}
    ${renderCharacterSpells(character)}

    <div class="license-note">
      Источник: ${escapeHtml(character.source || "Klassy.pdf + Rasy.pdf")}. Запись сгенерирована и сохранена локально в браузере.
    </div>
  `;
}

function renderCharacterResults() {
  if (!characterResults || !saveCharactersButton) {
    return;
  }

  if (!currentCharacterResults.length) {
    characterResults.innerHTML = "";
    saveCharactersButton.disabled = true;
    return;
  }

  characterResults.innerHTML = currentCharacterResults.map((character) => renderCharacterCard(character)).join("");
  saveCharactersButton.disabled = false;
}

function generateCharacters() {
  if (!characterData) {
    return;
  }

  const count = Math.min(Math.max(Number(characterCountInput?.value) || 1, 1), 6);
  if (characterCountInput) {
    characterCountInput.value = String(count);
  }

  currentCharacterResults.unshift(...Array.from({ length: count }, () => buildCharacter()));
  currentCharacterResults = currentCharacterResults.slice(0, 24);
  renderCharacterResults();
}

function deleteCharacterResult(id) {
  currentCharacterResults = currentCharacterResults.filter((character) => character.id !== id);
  renderCharacterResults();
}

function openCharacterDetail(character) {
  if (!character || !characterDetailModal) {
    return;
  }

  characterDetailName.textContent = character.name || "Персонаж";
  characterDetail.innerHTML = renderCharacterDetail(character);
  characterDetailModal.classList.remove("is-hidden");
}

function openCharacterResult(id) {
  openCharacterDetail(currentCharacterResults.find((character) => character.id === id));
}

function closeCharacterDetail() {
  characterDetailModal.classList.add("is-hidden");
}

function saveGeneratedCharacters() {
  if (!currentCharacterResults.length) {
    return;
  }

  const savedCharacters = getSavedCharacters();
  currentCharacterResults.forEach((character) => {
    savedCharacters.unshift({
      ...JSON.parse(JSON.stringify(character)),
      id: crypto.randomUUID(),
      savedAt: new Date().toISOString(),
    });
  });
  setSavedCharacters(savedCharacters.slice(0, 200));
  closeCharacterGenerator();
  openPanel("library");
}

async function openCharacterGenerator() {
  try {
    await Promise.all([loadCharacters(), loadSpells()]);
    currentCharacterResults = [];
    characterResults.innerHTML = "";
    saveCharactersButton.disabled = true;
    characterModal.classList.remove("is-hidden");
  } catch (error) {
    characterModal.classList.remove("is-hidden");
    characterResults.innerHTML = `<div class="library-empty"><strong>Ошибка загрузки</strong><span>${escapeHtml(error.message)}</span></div>`;
    saveCharactersButton.disabled = true;
  }
}

function closeCharacterGenerator() {
  characterModal.classList.add("is-hidden");
  currentCharacterResults = [];
}

async function loadNpcs() {
  if (npcData) {
    setupNpcOptions();
    return;
  }

  const response = await fetch("data/generators/npcs.json?v=20260609-npcs-1");
  if (!response.ok) {
    throw new Error("Не удалось загрузить базу НПС.");
  }

  npcData = await response.json();
  setupNpcOptions();
}

function setupNpcOptions() {
  if (!npcData) {
    return;
  }

  if (npcGenreInput) {
    const current = npcGenreInput.value;
    npcGenreInput.innerHTML = `
      <option value="">Любой</option>
      ${Object.entries(npcData.tables?.genres || {})
        .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
        .join("")}
    `;
    if (current && npcData.tables?.genres?.[current]) {
      npcGenreInput.value = current;
    }
  }

  if (npcRoleInput) {
    const current = npcRoleInput.value;
    npcRoleInput.innerHTML = `
      <option value="">Любая</option>
      ${Object.entries(npcData.tables?.roles || {})
        .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
        .join("")}
    `;
    if (current && npcData.tables?.roles?.[current]) {
      npcRoleInput.value = current;
    }
  }

  if (npcProfessionCategoryInput) {
    const current = npcProfessionCategoryInput.value;
    const categories = Array.from(new Set((npcData.tables?.professions || []).map((entry) => entry.category))).filter(Boolean);
    npcProfessionCategoryInput.innerHTML = `
      <option value="">Любая категория</option>
      ${categories.map((category) => `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`).join("")}
    `;
    if (current && categories.includes(current)) {
      npcProfessionCategoryInput.value = current;
    }
  }
}

function getFilteredNpcPool() {
  const genre = npcGenreInput?.value || "";
  const role = npcRoleInput?.value || "";
  const npcs = npcData?.tables?.npcs || [];
  const filtered = npcs.filter((npc) => {
    if (genre && npc.genre !== genre) {
      return false;
    }
    if (role && npc.role !== role) {
      return false;
    }
    return true;
  });
  return filtered.length ? filtered : npcs;
}

function getFilteredProfessionPool() {
  const category = npcProfessionCategoryInput?.value || "";
  const professions = npcData?.tables?.professions || [];
  const filtered = category ? professions.filter((entry) => entry.category === category) : professions;
  return filtered.length ? filtered : professions;
}

function buildNpc() {
  const base = pickFrom(getFilteredNpcPool());
  const profession = pickFrom(getFilteredProfessionPool());
  return {
    ...JSON.parse(JSON.stringify(base || {})),
    kind: "npc",
    id: crypto.randomUUID(),
    sourceId: base?.id || "",
    profession: profession ? { ...profession } : null,
    source: npcData?.source?.npcs?.title || "1000 запоминающихся NPC",
    savedAt: new Date().toISOString(),
  };
}

function getNpcSummary(npc) {
  return [npc.genre_label, npc.role_label, npc.profession?.name].filter(Boolean).join(" · ");
}

function renderNpcCard(npc, { saved = false } = {}) {
  const removeAttr = saved
    ? `data-delete-saved-character="${escapeHtml(npc.id)}"`
    : `data-delete-npc-result="${escapeHtml(npc.id)}"`;
  const openAttr = saved
    ? `data-open-saved-character="${escapeHtml(npc.id)}"`
    : `data-open-npc-result="${escapeHtml(npc.id)}"`;
  const removeLabel = saved ? "Удалить персонажа" : "Убрать НПС";

  return `
    <article class="saved-monster-card npc-card" ${openAttr} tabindex="0" role="button">
      <button class="card-remove" type="button" ${removeAttr} aria-label="${removeLabel}">×</button>
      <span>${escapeHtml(getNpcSummary(npc) || "НПС")}</span>
      <strong>${escapeHtml(npc.name || "НПС")}</strong>
      <em>${escapeHtml(npc.archetype || "-")}</em>
      <small class="potion-description">${escapeHtml(npc.quote || "")}</small>
    </article>
  `;
}

function renderNpcDetail(npc) {
  const distinctions = npc.distinctions?.length ? npc.distinctions.join(", ") : "-";
  return `
    <div class="monster-meta-grid">
      <div><span>Архетип</span><strong>${escapeHtml(npc.archetype || "-")}</strong></div>
      <div><span>Профессия</span><strong>${escapeHtml(npc.profession?.name || "-")}</strong></div>
      <div><span>Категория профессии</span><strong>${escapeHtml(npc.profession?.category || "-")}</strong></div>
      <div><span>Жанр</span><strong>${escapeHtml(npc.genre_label || "-")}</strong></div>
      <div><span>Роль</span><strong>${escapeHtml(npc.role_label || "-")}</strong></div>
      <div><span>Глава</span><strong>${escapeHtml(npc.chapter || "-")}</strong></div>
    </div>

    <section class="monster-section">
      <h4>Реплика</h4>
      <div class="monster-text-list"><p>${escapeHtml(npc.quote || "-")}</p></div>
    </section>

    ${renderTextList("Внешность", [npc.appearance].filter(Boolean))}
    ${renderTextList("Отыгрыш", [npc.roleplay].filter(Boolean))}
    ${renderTextList("Личность", [npc.personality].filter(Boolean))}
    ${renderTextList("Мотивация", [npc.motivation].filter(Boolean))}
    ${renderTextList("Биография", [npc.biography].filter(Boolean))}

    <section class="monster-section">
      <h4>Отличия</h4>
      <div class="monster-text-list"><p>${escapeHtml(distinctions)}</p></div>
    </section>

    <div class="license-note">
      Источник: ${escapeHtml(npc.source || npcData?.source?.npcs?.title || "1000 запоминающихся NPC")}. Профессии: ${escapeHtml(npcData?.source?.professions?.title || "Перечень профессий")}. Сохранено локально в браузере.
    </div>
  `;
}

function renderNpcResults() {
  if (!npcResults || !saveNpcsButton) {
    return;
  }

  if (!currentNpcResults.length) {
    npcResults.innerHTML = "";
    saveNpcsButton.disabled = true;
    return;
  }

  npcResults.innerHTML = currentNpcResults.map((npc) => renderNpcCard(npc)).join("");
  saveNpcsButton.disabled = false;
}

function generateNpcs() {
  if (!npcData) {
    return;
  }

  const count = Math.min(Math.max(Number(npcCountInput?.value) || 1, 1), 8);
  if (npcCountInput) {
    npcCountInput.value = String(count);
  }

  currentNpcResults.unshift(...Array.from({ length: count }, () => buildNpc()));
  currentNpcResults = currentNpcResults.slice(0, 24);
  renderNpcResults();
}

function deleteNpcResult(id) {
  currentNpcResults = currentNpcResults.filter((npc) => npc.id !== id);
  renderNpcResults();
}

function openNpcDetail(npc) {
  if (!npc || !npcDetailModal) {
    return;
  }

  npcDetailName.textContent = npc.name || "НПС";
  npcDetail.innerHTML = renderNpcDetail(npc);
  npcDetailModal.classList.remove("is-hidden");
}

function openNpcResult(id) {
  openNpcDetail(currentNpcResults.find((npc) => npc.id === id));
}

function openSavedCharacter(id) {
  const entry = getSavedCharacters().find((character) => character.id === id);
  if (entry?.kind === "player-character") {
    openCharacterDetail(entry);
    return;
  }
  openNpcDetail(entry);
}

function closeNpcDetail() {
  npcDetailModal.classList.add("is-hidden");
}

function saveGeneratedNpcs() {
  if (!currentNpcResults.length) {
    return;
  }

  const savedCharacters = getSavedCharacters();
  currentNpcResults.forEach((npc) => {
    savedCharacters.unshift({
      ...JSON.parse(JSON.stringify(npc)),
      id: crypto.randomUUID(),
      savedAt: new Date().toISOString(),
    });
  });
  setSavedCharacters(savedCharacters.slice(0, 200));
  closeNpcGenerator();
  openPanel("library");
}

async function openNpcGenerator() {
  try {
    await loadNpcs();
    currentNpcResults = [];
    npcResults.innerHTML = "";
    saveNpcsButton.disabled = true;
    npcModal.classList.remove("is-hidden");
  } catch (error) {
    npcModal.classList.remove("is-hidden");
    npcResults.innerHTML = `<div class="library-empty"><strong>Ошибка загрузки</strong><span>${escapeHtml(error.message)}</span></div>`;
    saveNpcsButton.disabled = true;
  }
}

function closeNpcGenerator() {
  npcModal.classList.add("is-hidden");
  currentNpcResults = [];
}

function deleteSavedCharacter(id) {
  setSavedCharacters(getSavedCharacters().filter((npc) => npc.id !== id));
}

async function loadRandomEvents() {
  if (randomEventData) {
    setupRandomEventOptions();
    return;
  }

  const response = await fetch("data/generators/random-events.json?v=20260609-events-1");
  if (!response.ok) {
    throw new Error("Не удалось загрузить базу случайных событий.");
  }

  randomEventData = await response.json();
  setupRandomEventOptions();
}

function setupRandomEventOptions() {
  if (!randomEventData || !randomEventCategoryInput) {
    return;
  }

  const current = randomEventCategoryInput.value;
  randomEventCategoryInput.innerHTML = `
    <option value="">Любая категория</option>
    ${Object.entries(randomEventData.tables?.categories || {})
      .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
      .join("")}
  `;
  if (current && randomEventData.tables?.categories?.[current]) {
    randomEventCategoryInput.value = current;
  }
}

function getFilteredRandomEventPool() {
  const category = randomEventCategoryInput?.value || "";
  const events = randomEventData?.tables?.events || [];
  const filtered = category ? events.filter((entry) => entry.category === category) : events;
  return filtered.length ? filtered : events;
}

function buildRandomEvent() {
  const base = pickFrom(getFilteredRandomEventPool());
  return {
    ...JSON.parse(JSON.stringify(base || {})),
    id: crypto.randomUUID(),
    sourceRoll: base?.roll || 0,
    source: randomEventData?.source?.title || "Случайности не случайны",
    savedAt: new Date().toISOString(),
  };
}

function getRandomEventExcerpt(randomEvent) {
  return oneLineText(randomEvent.text || randomEvent.title || "").slice(0, 220);
}

function oneLineText(value) {
  return String(value || "").replace(/\s+/g, " ").trim();
}

function renderRandomEventCard(randomEvent, { saved = false } = {}) {
  const removeAttr = saved
    ? `data-delete-saved-event="${escapeHtml(randomEvent.id)}"`
    : `data-delete-random-event-result="${escapeHtml(randomEvent.id)}"`;
  const openAttr = saved
    ? `data-open-saved-event="${escapeHtml(randomEvent.id)}"`
    : `data-open-random-event-result="${escapeHtml(randomEvent.id)}"`;
  const removeLabel = saved ? "Удалить событие" : "Убрать событие";

  return `
    <article class="saved-monster-card event-card" ${openAttr} tabindex="0" role="button">
      <button class="card-remove" type="button" ${removeAttr} aria-label="${removeLabel}">×</button>
      <span>${escapeHtml(randomEvent.category_label || "Событие")} · #${escapeHtml(randomEvent.sourceRoll || randomEvent.roll || "?")}</span>
      <strong>${escapeHtml(randomEvent.title || "Случайное событие")}</strong>
      <small class="potion-description">${escapeHtml(getRandomEventExcerpt(randomEvent))}</small>
    </article>
  `;
}

function renderRandomEventDetail(randomEvent) {
  return `
    <div class="monster-meta-grid">
      <div><span>Номер</span><strong>#${escapeHtml(randomEvent.sourceRoll || randomEvent.roll || "?")}</strong></div>
      <div><span>Категория</span><strong>${escapeHtml(randomEvent.category_label || "-")}</strong></div>
    </div>

    <section class="monster-section">
      <h4>Событие</h4>
      <div class="monster-text-list">
        <p>${escapeHtml(randomEvent.text || "-")}</p>
      </div>
    </section>

    <div class="license-note">
      Источник: ${escapeHtml(randomEvent.source || randomEventData?.source?.title || "Случайности не случайны")}. Сохранено локально в браузере.
    </div>
  `;
}

function renderRandomEventResults() {
  if (!randomEventResults || !saveRandomEventsButton) {
    return;
  }

  if (!currentRandomEventResults.length) {
    randomEventResults.innerHTML = "";
    saveRandomEventsButton.disabled = true;
    return;
  }

  randomEventResults.innerHTML = currentRandomEventResults.map((randomEvent) => renderRandomEventCard(randomEvent)).join("");
  saveRandomEventsButton.disabled = false;
}

function generateRandomEvents() {
  if (!randomEventData) {
    return;
  }

  const count = Math.min(Math.max(Number(randomEventCountInput?.value) || 1, 1), 6);
  if (randomEventCountInput) {
    randomEventCountInput.value = String(count);
  }

  currentRandomEventResults.unshift(...Array.from({ length: count }, () => buildRandomEvent()));
  currentRandomEventResults = currentRandomEventResults.slice(0, 24);
  renderRandomEventResults();
}

function deleteRandomEventResult(id) {
  currentRandomEventResults = currentRandomEventResults.filter((randomEvent) => randomEvent.id !== id);
  renderRandomEventResults();
}

function openRandomEventDetail(randomEvent) {
  if (!randomEvent || !randomEventDetailModal) {
    return;
  }

  randomEventDetailName.textContent = randomEvent.title || "Событие";
  randomEventDetail.innerHTML = renderRandomEventDetail(randomEvent);
  randomEventDetailModal.classList.remove("is-hidden");
}

function openRandomEventResult(id) {
  openRandomEventDetail(currentRandomEventResults.find((randomEvent) => randomEvent.id === id));
}

function openSavedEvent(id) {
  openRandomEventDetail(getSavedEvents().find((randomEvent) => randomEvent.id === id));
}

function closeRandomEventDetail() {
  randomEventDetailModal.classList.add("is-hidden");
}

function saveGeneratedRandomEvents() {
  if (!currentRandomEventResults.length) {
    return;
  }

  const savedEvents = getSavedEvents();
  currentRandomEventResults.forEach((randomEvent) => {
    savedEvents.unshift({
      ...JSON.parse(JSON.stringify(randomEvent)),
      id: crypto.randomUUID(),
      savedAt: new Date().toISOString(),
    });
  });
  setSavedEvents(savedEvents.slice(0, 200));
  closeRandomEventGenerator();
  openPanel("library");
}

async function openRandomEventGenerator() {
  try {
    await loadRandomEvents();
    currentRandomEventResults = [];
    randomEventResults.innerHTML = "";
    saveRandomEventsButton.disabled = true;
    randomEventModal.classList.remove("is-hidden");
  } catch (error) {
    randomEventModal.classList.remove("is-hidden");
    randomEventResults.innerHTML = `<div class="library-empty"><strong>Ошибка загрузки</strong><span>${escapeHtml(error.message)}</span></div>`;
    saveRandomEventsButton.disabled = true;
  }
}

function closeRandomEventGenerator() {
  randomEventModal.classList.add("is-hidden");
  currentRandomEventResults = [];
}

function deleteSavedEvent(id) {
  setSavedEvents(getSavedEvents().filter((randomEvent) => randomEvent.id !== id));
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
  const savedTavernCard = event.target.closest("[data-open-saved-tavern]");
  const savedCharacterCard = event.target.closest("[data-open-saved-character]");
  const savedEventCard = event.target.closest("[data-open-saved-event]");
  const savedNoteCard = event.target.closest("[data-open-saved-note]");
  const potionResultCard = event.target.closest("[data-open-potion-result]");
  const lootResultCard = event.target.closest("[data-open-loot-result]");
  const tavernResultCard = event.target.closest("[data-open-tavern-result]");
  const characterResultCard = event.target.closest("[data-open-character-result]");
  const npcResultCard = event.target.closest("[data-open-npc-result]");
  const randomEventResultCard = event.target.closest("[data-open-random-event-result]");
  if (!button && !savedMonsterCard && !savedPotionCard && !savedSpellCard && !savedLootCard && !savedTavernCard && !savedCharacterCard && !savedEventCard && !savedNoteCard && !potionResultCard && !lootResultCard && !tavernResultCard && !characterResultCard && !npcResultCard && !randomEventResultCard) {
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

  if (button?.dataset.deleteSavedTavern) {
    deleteSavedTavern(button.dataset.deleteSavedTavern);
    return;
  }

  if (button?.dataset.deleteSavedCharacter) {
    deleteSavedCharacter(button.dataset.deleteSavedCharacter);
    return;
  }

  if (button?.dataset.deleteSavedEvent) {
    deleteSavedEvent(button.dataset.deleteSavedEvent);
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

  if (!button && savedTavernCard) {
    openSavedTavern(savedTavernCard.dataset.openSavedTavern);
    return;
  }

  if (!button && savedCharacterCard) {
    openSavedCharacter(savedCharacterCard.dataset.openSavedCharacter);
    return;
  }

  if (!button && savedEventCard) {
    openSavedEvent(savedEventCard.dataset.openSavedEvent);
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

  if (!button && tavernResultCard) {
    openTavernResult(tavernResultCard.dataset.openTavernResult);
    return;
  }

  if (!button && characterResultCard) {
    openCharacterResult(characterResultCard.dataset.openCharacterResult);
    return;
  }

  if (!button && npcResultCard) {
    openNpcResult(npcResultCard.dataset.openNpcResult);
    return;
  }

  if (!button && randomEventResultCard) {
    openRandomEventResult(randomEventResultCard.dataset.openRandomEventResult);
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
    return;
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

  if (button.matches("[data-open-tavern-generator]")) {
    openTavernGenerator();
  }

  if (button.matches("[data-open-character-generator]")) {
    openCharacterGenerator();
  }

  if (button.matches("[data-close-character-generator]")) {
    closeCharacterGenerator();
  }

  if (button.matches("[data-close-character-detail]")) {
    closeCharacterDetail();
  }

  if (button.matches("[data-generate-characters]")) {
    generateCharacters();
  }

  if (button.dataset.deleteCharacterResult) {
    deleteCharacterResult(button.dataset.deleteCharacterResult);
  }

  if (button.matches("[data-save-characters]")) {
    saveGeneratedCharacters();
  }

  if (button.matches("[data-open-npc-generator]")) {
    openNpcGenerator();
  }

  if (button.matches("[data-close-npc-generator]")) {
    closeNpcGenerator();
  }

  if (button.matches("[data-close-npc-detail]")) {
    closeNpcDetail();
  }

  if (button.matches("[data-generate-npcs]")) {
    generateNpcs();
  }

  if (button.dataset.deleteNpcResult) {
    deleteNpcResult(button.dataset.deleteNpcResult);
  }

  if (button.matches("[data-save-npcs]")) {
    saveGeneratedNpcs();
  }

  if (button.matches("[data-open-random-event-generator]")) {
    openRandomEventGenerator();
  }

  if (button.matches("[data-close-random-event-generator]")) {
    closeRandomEventGenerator();
  }

  if (button.matches("[data-close-random-event-detail]")) {
    closeRandomEventDetail();
  }

  if (button.matches("[data-generate-random-events]")) {
    generateRandomEvents();
  }

  if (button.dataset.deleteRandomEventResult) {
    deleteRandomEventResult(button.dataset.deleteRandomEventResult);
  }

  if (button.matches("[data-save-random-events]")) {
    saveGeneratedRandomEvents();
  }

  if (button.matches("[data-close-tavern-generator]")) {
    closeTavernGenerator();
  }

  if (button.matches("[data-close-tavern-detail]")) {
    closeTavernDetail();
  }

  if (button.matches("[data-generate-tavern]")) {
    generateTavern();
  }

  if (button.dataset.deleteTavernResult) {
    deleteTavernResult(button.dataset.deleteTavernResult);
  }

  if (button.matches("[data-save-taverns]")) {
    saveGeneratedTaverns();
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
  if (event.key === "Escape" && !tavernModal.classList.contains("is-hidden")) {
    closeTavernGenerator();
  }
  if (event.key === "Escape" && !tavernDetailModal.classList.contains("is-hidden")) {
    closeTavernDetail();
  }
  if (event.key === "Escape" && !characterModal.classList.contains("is-hidden")) {
    closeCharacterGenerator();
  }
  if (event.key === "Escape" && !characterDetailModal.classList.contains("is-hidden")) {
    closeCharacterDetail();
  }
  if (event.key === "Escape" && !npcModal.classList.contains("is-hidden")) {
    closeNpcGenerator();
  }
  if (event.key === "Escape" && !npcDetailModal.classList.contains("is-hidden")) {
    closeNpcDetail();
  }
  if (event.key === "Escape" && !randomEventModal.classList.contains("is-hidden")) {
    closeRandomEventGenerator();
  }
  if (event.key === "Escape" && !randomEventDetailModal.classList.contains("is-hidden")) {
    closeRandomEventDetail();
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

characterRaceInput?.addEventListener("change", setupCharacterSubtypeOptions);

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
renderLibraryCharacters();
renderLibraryNpcs();
renderLibraryEvents();
renderLibraryRolls();
renderLibraryMonsters();
renderLibraryPotions();
renderLibrarySpells();
renderLibraryLoot();
renderLibraryTaverns();
renderLibraryNotes();

if (state.soundEnabled) {
  music.play().then(hideAudioGate).catch(showAudioGate);
} else {
  showAudioGate();
}
