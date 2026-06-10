import json
import re
from json import JSONDecoder
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TRANSLATION_PATH = Path("C:/Users/Tim/Dropbox/\u041f\u041a/Desktop/\u043f\u0435\u0440\u0435\u0432\u043e\u0434 \u0431\u0434 \u0441\u043f\u0435\u043b.txt")
SPELLS_PATH = ROOT / "public/data/srd/spells.json"
INDEX_PATH = ROOT / "public/data/srd/spells.index.json"
MISSING_JSON_PATH = ROOT / "public/data/srd/spells-untranslated.json"
MISSING_TXT_PATH = ROOT / "public/data/srd/spells-untranslated.txt"

TRANSLATED_DISPLAY_FIELDS = [
    "school",
    "casting_time",
    "reaction_condition",
    "range_text",
    "duration",
    "source_url",
]


def read_loose_json_records(path):
    text = path.read_text(encoding="utf-8-sig")
    decoder = JSONDecoder()
    position = 0
    records = []
    while position < len(text):
        while position < len(text) and (text[position].isspace() or text[position] == ","):
            position += 1
        if position >= len(text):
            break
        value, end = decoder.raw_decode(text, position)
        if isinstance(value, list):
            records.extend(value)
        else:
            records.append(value)
        position = end
    return records


def has_cyrillic(value):
    return bool(re.search(r"[А-Яа-яЁё]", str(value or "")))


def is_translated_name(spell):
    name = spell.get("name_ru") or spell.get("name")
    return has_cyrillic(name)


def is_translated_description(spell):
    return has_cyrillic(spell.get("description_ru") or spell.get("description"))


def merge_translation(spell, translation):
    updated = dict(spell)
    if translation.get("name") and has_cyrillic(translation.get("name")):
        updated["name_ru"] = translation["name"]
    if translation.get("name_ru") and has_cyrillic(translation.get("name_ru")):
        updated["name_ru"] = translation["name_ru"]
    if translation.get("description") and has_cyrillic(translation.get("description")):
        updated["description_ru"] = translation["description"]
    if translation.get("higher_level") and has_cyrillic(translation.get("higher_level")):
        updated["higher_level_ru"] = translation["higher_level"]

    for field in TRANSLATED_DISPLAY_FIELDS:
        if translation.get(field) and has_cyrillic(translation.get(field)):
            updated[field] = translation[field]

    if translation.get("classes"):
        classes_by_key = {
            item.get("key"): item
            for item in translation["classes"]
            if item.get("key") and item.get("name") and has_cyrillic(item.get("name"))
        }
        if classes_by_key:
            updated["classes"] = [
                {**item, "name": classes_by_key[item.get("key")]["name"]}
                if item.get("key") in classes_by_key
                else item
                for item in updated.get("classes", [])
            ]

    return updated


def to_index_row(spell):
    keys = [
        "id",
        "name",
        "name_ru",
        "level",
        "school",
        "school_key",
        "classes",
        "casting_time",
        "range_text",
        "duration",
        "concentration",
        "ritual",
        "damage_types",
        "source",
        "source_key",
        "publisher",
    ]
    return {key: spell.get(key) for key in keys if key in spell}


def main():
    translations = read_loose_json_records(TRANSLATION_PATH)
    translations_by_id = {item["id"]: item for item in translations if item.get("id")}

    spells = json.loads(SPELLS_PATH.read_text(encoding="utf-8"))
    updated_spells = [
        merge_translation(spell, translations_by_id[spell["id"]])
        if spell.get("id") in translations_by_id
        else spell
        for spell in spells
    ]
    updated_index = [to_index_row(spell) for spell in updated_spells]

    missing = [
        {
            "id": spell.get("id"),
            "name": spell.get("name"),
            "level": spell.get("level"),
            "school": spell.get("school"),
            "source": spell.get("source_display") or spell.get("source"),
            "missing_name_ru": not is_translated_name(spell),
            "missing_description_ru": not is_translated_description(spell),
        }
        for spell in updated_spells
        if not is_translated_name(spell) or not is_translated_description(spell)
    ]

    SPELLS_PATH.write_text(json.dumps(updated_spells, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    INDEX_PATH.write_text(json.dumps(updated_index, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    MISSING_JSON_PATH.write_text(json.dumps(missing, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    MISSING_TXT_PATH.write_text(
        "\n".join(
            [
                "Заклинания без полного перевода",
                "Формат: id | name | level | source | что отсутствует",
                "",
                *[
                    f"{item['id']} | {item['name']} | {item['level']} | {item['source']} | "
                    f"{', '.join(part for part, missing_part in [('name_ru', item['missing_name_ru']), ('description_ru', item['missing_description_ru'])] if missing_part)}"
                    for item in missing
                ],
            ]
        )
        + "\n",
        encoding="utf-8",
    )

    print(f"Translations read: {len(translations_by_id)}")
    print(f"Spells updated: {sum(1 for spell in spells if spell.get('id') in translations_by_id)}")
    print(f"Missing full translation: {len(missing)}")
    print(f"Wrote {SPELLS_PATH}")
    print(f"Wrote {INDEX_PATH}")
    print(f"Wrote {MISSING_JSON_PATH}")
    print(f"Wrote {MISSING_TXT_PATH}")


if __name__ == "__main__":
    main()
