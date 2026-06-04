import argparse
import json
import re
import time
from pathlib import Path

import requests


API_BASE = "https://api.open5e.com/v2/spells/"
USER_AGENT = "DND Copilot local spells importer"
SOURCE_PRIORITY = {
    "srd-2024": 0,
    "srd-2014": 1,
    "srd": 1,
    "deepm": 2,
    "deepmx": 3,
    "kp": 4,
    "toh": 5,
    "wz": 6,
    "spells-that-dont-suck": 7,
    "a5e-ag": 8,
}


def slugify(value):
    value = value.lower()
    value = re.sub(r"[^a-z0-9]+", "-", value)
    return value.strip("-")


def get_json(url, retries=8):
    headers = {"User-Agent": USER_AGENT}
    for attempt in range(retries):
        response = requests.get(url, headers=headers, timeout=30)
        if response.status_code != 429:
            response.raise_for_status()
            return response.json()

        retry_after = response.headers.get("Retry-After")
        wait = int(retry_after) if retry_after and retry_after.isdigit() else 20 + attempt * 10
        print(f"Rate limited, waiting {wait}s...")
        time.sleep(wait)

    response.raise_for_status()
    return response.json()


def get_all_spells(limit=100):
    url = f"{API_BASE}?limit={limit}"
    results = []
    while url:
        payload = get_json(url)
        results.extend(payload["results"])
        url = payload.get("next")
    return results


def source_rank(record):
    document = record.get("document") or {}
    return SOURCE_PRIORITY.get(document.get("key"), 99)


def dedupe_by_name(records):
    chosen = {}
    duplicates = {}
    for record in records:
        key = (record.get("name") or "").strip().lower()
        if not key:
            continue
        if key in chosen:
            duplicates.setdefault(key, [chosen[key]]).append(record)
            if source_rank(record) < source_rank(chosen[key]):
                chosen[key] = record
        else:
            chosen[key] = record
    return list(chosen.values()), duplicates


def backfill_missing_text(records, selected_records):
    records_by_name = {}
    for record in records:
        key = (record.get("name") or "").strip().lower()
        if key:
            records_by_name.setdefault(key, []).append(record)

    for selected in selected_records:
        key = (selected.get("name") or "").strip().lower()
        alternatives = records_by_name.get(key, [])
        if not selected.get("desc"):
            replacement = next((record.get("desc") for record in alternatives if record.get("desc")), "")
            if replacement:
                selected["desc"] = replacement
        if not selected.get("higher_level"):
            replacement = next((record.get("higher_level") for record in alternatives if record.get("higher_level")), "")
            if replacement:
                selected["higher_level"] = replacement

    return selected_records


def normalize_class(class_record):
    name = class_record.get("name") or ""
    key = class_record.get("key") or slugify(name)
    clean_key = re.sub(r"^(srd-2024|srd|a5e-ag)_", "", key)
    return {
        "name": name,
        "key": clean_key,
    }


def normalize_spell(record):
    document = record.get("document") or {}
    publisher = document.get("publisher") or {}
    school = record.get("school") or {}
    name = record.get("name") or "Unknown Spell"
    source_key = document.get("key") or "unknown"

    components = {
        "verbal": bool(record.get("verbal")),
        "somatic": bool(record.get("somatic")),
        "material": bool(record.get("material")),
        "material_specified": record.get("material_specified") or "",
        "material_cost": record.get("material_cost"),
        "material_consumed": bool(record.get("material_consumed")),
    }

    classes = [normalize_class(item) for item in record.get("classes") or []]

    return {
        "id": record.get("key") or slugify(name),
        "name": name,
        "name_ru": "",
        "level": record.get("level") if record.get("level") is not None else 0,
        "school": school.get("name") or "",
        "school_key": school.get("key") or "",
        "classes": classes,
        "casting_time": record.get("casting_time") or "",
        "reaction_condition": record.get("reaction_condition") or "",
        "range_text": record.get("range_text") or "",
        "range": record.get("range"),
        "range_unit": record.get("range_unit") or "",
        "components": components,
        "duration": record.get("duration") or "",
        "concentration": bool(record.get("concentration")),
        "ritual": bool(record.get("ritual")),
        "description": record.get("desc") or "",
        "higher_level": record.get("higher_level") or "",
        "target_type": record.get("target_type") or "",
        "target_count": record.get("target_count"),
        "saving_throw_ability": record.get("saving_throw_ability") or "",
        "attack_roll": bool(record.get("attack_roll")),
        "damage_roll": record.get("damage_roll") or "",
        "damage_types": record.get("damage_types") or [],
        "shape_type": record.get("shape_type") or "",
        "shape_size": record.get("shape_size"),
        "shape_size_unit": record.get("shape_size_unit") or "",
        "source": document.get("name") or source_key,
        "source_key": source_key,
        "source_display": document.get("display_name") or document.get("name") or source_key,
        "source_url": document.get("permalink") or "",
        "publisher": publisher.get("name") or "",
        "license": "Open5e source data",
    }


def validate_spell(spell):
    warnings = []
    if not spell["description"]:
        warnings.append("missing description")
    if not spell["school_key"]:
        warnings.append("missing school")
    if spell["level"] is None:
        warnings.append("missing level")
    return warnings


def write_json(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--out", default="public/data/srd")
    parser.add_argument("--raw-out", default=".cache/open5e-spells/raw-spells.json")
    args = parser.parse_args()

    out_dir = Path(args.out)
    raw_out = Path(args.raw_out)
    raw_records = get_all_spells()
    selected_records, duplicate_groups = dedupe_by_name(raw_records)
    selected_records = backfill_missing_text(raw_records, selected_records)

    spells = [normalize_spell(record) for record in selected_records]
    spells.sort(key=lambda item: (item["level"], item["name"]))

    warnings = []
    for spell in spells:
        spell_warnings = validate_spell(spell)
        if spell_warnings:
            warnings.append({"id": spell["id"], "name": spell["name"], "warnings": spell_warnings})

    index_rows = [
        {
            "id": spell["id"],
            "name": spell["name"],
            "name_ru": spell["name_ru"],
            "level": spell["level"],
            "school": spell["school"],
            "school_key": spell["school_key"],
            "classes": spell["classes"],
            "casting_time": spell["casting_time"],
            "range_text": spell["range_text"],
            "duration": spell["duration"],
            "concentration": spell["concentration"],
            "ritual": spell["ritual"],
            "damage_types": spell["damage_types"],
            "source": spell["source"],
            "source_key": spell["source_key"],
            "publisher": spell["publisher"],
        }
        for spell in spells
    ]

    source_counts = {}
    school_counts = {}
    level_counts = {}
    class_counts = {}
    for spell in spells:
        source_counts[spell["source_key"]] = source_counts.get(spell["source_key"], 0) + 1
        school_counts[spell["school_key"]] = school_counts.get(spell["school_key"], 0) + 1
        level_counts[str(spell["level"])] = level_counts.get(str(spell["level"]), 0) + 1
        for class_record in spell["classes"]:
            class_counts[class_record["key"]] = class_counts.get(class_record["key"], 0) + 1

    report = {
        "source_api": API_BASE,
        "raw_count": len(raw_records),
        "imported_count": len(spells),
        "duplicate_group_count": len(duplicate_groups),
        "source_counts": source_counts,
        "school_counts": school_counts,
        "level_counts": level_counts,
        "class_counts": class_counts,
        "duplicate_groups": {
            name: [{"key": record.get("key"), "document": (record.get("document") or {}).get("key")} for record in records]
            for name, records in sorted(duplicate_groups.items())
        },
        "warnings": warnings,
        "warning_count": len(warnings),
    }

    raw_out.parent.mkdir(parents=True, exist_ok=True)
    write_json(raw_out, raw_records)
    write_json(out_dir / "spells.json", spells)
    write_json(out_dir / "spells.index.json", index_rows)
    write_json(out_dir / "spells-import-report.json", report)

    print(f"Fetched {len(raw_records)} raw spell records")
    print(f"Imported {len(spells)} unique spells into {out_dir}")
    print(f"Sources: {source_counts}")
    print(f"Schools: {school_counts}")
    print(f"Levels: {level_counts}")
    print(f"Classes: {class_counts}")
    print(f"Duplicate groups: {len(duplicate_groups)}")
    print(f"Warnings: {len(warnings)}")


if __name__ == "__main__":
    main()
