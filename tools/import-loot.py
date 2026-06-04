import argparse
import json
import re
import time
from pathlib import Path
from urllib.parse import urlencode

import requests


API_BASE = "https://api.open5e.com/v2/magicitems/"
USER_AGENT = "DND Copilot local loot importer"
SOURCE_PRIORITY = {
    "srd-2024": 0,
    "srd-2014": 1,
    "vom": 2,
}


LOOT_TABLES = {
    "sources": [
        {
            "name": "Open5e API v2",
            "url": API_BASE,
            "usage": "Magic item records, categories, rarities, source documents, descriptions.",
        },
        {
            "name": "D&D Copilot local treasure tables",
            "url": "",
            "usage": "Original tiered coin, gem, art object, and magic item weighting tables for local generation.",
        },
    ],
    "coin_values_gp": {
        "cp": 0.01,
        "sp": 0.1,
        "ep": 0.5,
        "gp": 1,
        "pp": 10,
    },
    "tiers": [
        {
            "id": "0-4",
            "label": "CR 0-4",
            "tone": "карманы, логова мелких угроз, первые награды",
            "individual": [
                {"min": 1, "max": 35, "coins": [{"coin": "cp", "formula": "3d6"}]},
                {"min": 36, "max": 70, "coins": [{"coin": "sp", "formula": "2d6"}]},
                {"min": 71, "max": 90, "coins": [{"coin": "ep", "formula": "1d6"}]},
                {"min": 91, "max": 100, "coins": [{"coin": "gp", "formula": "1d6"}]},
            ],
            "hoard": {
                "coins": [
                    {"coin": "cp", "formula": "4d6*100"},
                    {"coin": "sp", "formula": "3d6*100"},
                    {"coin": "gp", "formula": "2d6*10"},
                ],
                "valuables": [
                    {"chance": 45, "kind": "gems", "value": 10, "formula": "2d6"},
                    {"chance": 25, "kind": "art", "value": 25, "formula": "1d4"},
                ],
                "magic": {"chance": 35, "rarities": {"common": 60, "uncommon": 35, "rare": 5}, "formula": "1d2"},
            },
        },
        {
            "id": "5-10",
            "label": "CR 5-10",
            "tone": "опасные банды, малые крепости, значимые заказы",
            "individual": [
                {"min": 1, "max": 25, "coins": [{"coin": "sp", "formula": "4d6"}]},
                {"min": 26, "max": 60, "coins": [{"coin": "gp", "formula": "2d6"}]},
                {"min": 61, "max": 85, "coins": [{"coin": "gp", "formula": "3d6"}, {"coin": "pp", "formula": "1d4"}]},
                {"min": 86, "max": 100, "coins": [{"coin": "pp", "formula": "2d6"}]},
            ],
            "hoard": {
                "coins": [
                    {"coin": "cp", "formula": "2d6*100"},
                    {"coin": "sp", "formula": "2d6*1000"},
                    {"coin": "gp", "formula": "6d6*100"},
                    {"coin": "pp", "formula": "3d6*10"},
                ],
                "valuables": [
                    {"chance": 55, "kind": "gems", "value": 50, "formula": "2d6"},
                    {"chance": 35, "kind": "art", "value": 250, "formula": "1d4"},
                ],
                "magic": {"chance": 55, "rarities": {"common": 25, "uncommon": 50, "rare": 22, "very-rare": 3}, "formula": "1d3"},
            },
        },
        {
            "id": "11-16",
            "label": "CR 11-16",
            "tone": "владыки регионов, древние руины, политические трофеи",
            "individual": [
                {"min": 1, "max": 20, "coins": [{"coin": "gp", "formula": "4d6"}]},
                {"min": 21, "max": 55, "coins": [{"coin": "gp", "formula": "6d6"}, {"coin": "pp", "formula": "2d6"}]},
                {"min": 56, "max": 85, "coins": [{"coin": "pp", "formula": "4d6"}]},
                {"min": 86, "max": 100, "coins": [{"coin": "pp", "formula": "6d6"}, {"coin": "gp", "formula": "4d6*10"}]},
            ],
            "hoard": {
                "coins": [
                    {"coin": "gp", "formula": "8d6*100"},
                    {"coin": "pp", "formula": "8d6*100"},
                ],
                "valuables": [
                    {"chance": 60, "kind": "gems", "value": 500, "formula": "2d6"},
                    {"chance": 45, "kind": "art", "value": 750, "formula": "1d6"},
                ],
                "magic": {"chance": 70, "rarities": {"uncommon": 25, "rare": 45, "very-rare": 25, "legendary": 5}, "formula": "1d4"},
            },
        },
        {
            "id": "17+",
            "label": "CR 17+",
            "tone": "сокровищницы драконов, полубоги, финальные награды арки",
            "individual": [
                {"min": 1, "max": 25, "coins": [{"coin": "pp", "formula": "6d6"}]},
                {"min": 26, "max": 60, "coins": [{"coin": "pp", "formula": "8d6"}, {"coin": "gp", "formula": "6d6*10"}]},
                {"min": 61, "max": 90, "coins": [{"coin": "pp", "formula": "10d6"}, {"coin": "gp", "formula": "10d6*10"}]},
                {"min": 91, "max": 100, "coins": [{"coin": "pp", "formula": "12d6"}, {"coin": "gp", "formula": "12d6*10"}]},
            ],
            "hoard": {
                "coins": [
                    {"coin": "gp", "formula": "12d6*1000"},
                    {"coin": "pp", "formula": "8d6*1000"},
                ],
                "valuables": [
                    {"chance": 70, "kind": "gems", "value": 1000, "formula": "2d8"},
                    {"chance": 55, "kind": "art", "value": 2500, "formula": "1d8"},
                ],
                "magic": {"chance": 85, "rarities": {"rare": 25, "very-rare": 45, "legendary": 30}, "formula": "1d4+1"},
            },
        },
    ],
    "gem_tables": {
        "10": [
            "полированный обсидиан",
            "полупрозрачный кварц",
            "кусочек малахита",
            "голубой агат",
            "гладкий гематит",
            "перламутровая раковина",
        ],
        "50": [
            "лунный камень",
            "кровавик",
            "оникс с серебристой жилкой",
            "цитрин",
            "хризопраз",
            "дымчатый кварц",
        ],
        "500": [
            "глубокий сапфир",
            "звёздчатый рубин",
            "изумруд с тонкой трещиной",
            "чёрный опал",
            "бриллиант холодного блеска",
            "золотистый топаз",
        ],
        "1000": [
            "безупречный бриллиант",
            "королевский изумруд",
            "алмаз с радужным сердцем",
            "огненный опал",
            "звёздный сапфир",
            "рубин цвета свежей крови",
        ],
    },
    "art_tables": {
        "25": [
            "серебряная брошь с гербом забытого дома",
            "резная костяная трубка",
            "миниатюрный портрет в медной рамке",
            "лакированная шкатулка с тайным отделением",
        ],
        "250": [
            "золотой кубок с эмалью",
            "шёлковая маска для бала",
            "набор резных шахматных фигур",
            "старинная карта в кожаном футляре",
        ],
        "750": [
            "малый идол из слоновой кости и золота",
            "колье с шестью редкими камнями",
            "кинжал церемоний в ножнах из серебра",
            "манускрипт с иллюстрациями известного мастера",
        ],
        "2500": [
            "корона побеждённого князя",
            "зеркало в раме из платины",
            "древний музыкальный автомат с самоцветами",
            "гобелен с пророческой сценой",
        ],
    },
    "mundane_finds": [
        "запечатанное письмо без адресата",
        "ключ из тёмного железа",
        "потрёпанный дневник с вырванными страницами",
        "кости для игры с едва заметной меткой",
        "амулет без магии, но с сильной семейной ценностью",
        "маленький флакон редких чернил",
        "перстень с гербом местного дома",
        "латунная печать неизвестной гильдии",
    ],
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


def get_all_magic_items(limit=100):
    url = f"{API_BASE}?{urlencode({'limit': limit})}"
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


def normalize_magic_item(record):
    category = record.get("category") or {}
    rarity = record.get("rarity") or {}
    document = record.get("document") or {}
    publisher = document.get("publisher") or {}
    name = record.get("name") or "Unknown Magic Item"
    source_key = document.get("key") or "unknown"

    return {
        "id": record.get("key") or slugify(name),
        "name": name,
        "category": category.get("name") or "Magic Item",
        "category_key": category.get("key") or "magic-item",
        "rarity": rarity.get("name") or "Unknown",
        "rarity_value": rarity.get("key") or "unknown",
        "rarity_rank": rarity.get("rank"),
        "description": record.get("desc") or "",
        "attunement": bool(record.get("requires_attunement")),
        "attunement_requirement": record.get("attunement_detail") or "",
        "weapon": record.get("weapon"),
        "armor": record.get("armor"),
        "weight": record.get("weight"),
        "weight_unit": record.get("weight_unit"),
        "source": document.get("name") or source_key,
        "source_key": source_key,
        "source_display": document.get("display_name") or document.get("name") or source_key,
        "source_url": document.get("permalink") or "",
        "publisher": publisher.get("name") or "",
        "license": "Open5e source data",
    }


def validate_item(item):
    warnings = []
    if not item["description"]:
        warnings.append("missing description")
    if item["rarity_value"] == "unknown":
        warnings.append("unknown rarity")
    return warnings


def write_json(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--out", default="public/data/srd")
    parser.add_argument("--raw-out", default=".cache/open5e-loot/raw-magicitems.json")
    args = parser.parse_args()

    out_dir = Path(args.out)
    raw_out = Path(args.raw_out)
    raw_records = get_all_magic_items()
    selected_records, duplicate_groups = dedupe_by_name(raw_records)

    items = [normalize_magic_item(record) for record in selected_records]
    items.sort(key=lambda item: item["name"])

    warnings = []
    for item in items:
        item_warnings = validate_item(item)
        if item_warnings:
            warnings.append({"id": item["id"], "name": item["name"], "warnings": item_warnings})

    index_rows = [
        {
            "id": item["id"],
            "name": item["name"],
            "category": item["category"],
            "category_key": item["category_key"],
            "rarity": item["rarity"],
            "rarity_value": item["rarity_value"],
            "rarity_rank": item["rarity_rank"],
            "attunement": item["attunement"],
            "source": item["source"],
            "source_key": item["source_key"],
            "publisher": item["publisher"],
        }
        for item in items
    ]

    source_counts = {}
    category_counts = {}
    rarity_counts = {}
    for item in items:
        source_counts[item["source_key"]] = source_counts.get(item["source_key"], 0) + 1
        category_counts[item["category_key"]] = category_counts.get(item["category_key"], 0) + 1
        rarity_counts[item["rarity_value"]] = rarity_counts.get(item["rarity_value"], 0) + 1

    report = {
        "source_api": API_BASE,
        "raw_count": len(raw_records),
        "imported_count": len(items),
        "duplicate_group_count": len(duplicate_groups),
        "source_counts": source_counts,
        "category_counts": category_counts,
        "rarity_counts": rarity_counts,
        "duplicate_groups": {
            name: [{"key": record.get("key"), "document": (record.get("document") or {}).get("key")} for record in records]
            for name, records in sorted(duplicate_groups.items())
        },
        "warnings": warnings,
        "warning_count": len(warnings),
        "local_tables": "Original D&D Copilot generation tables, stored in loot-tables.json.",
    }

    raw_out.parent.mkdir(parents=True, exist_ok=True)
    write_json(raw_out, raw_records)
    write_json(out_dir / "loot-items.json", items)
    write_json(out_dir / "loot-items.index.json", index_rows)
    write_json(out_dir / "loot-tables.json", LOOT_TABLES)
    write_json(out_dir / "loot-import-report.json", report)

    print(f"Fetched {len(raw_records)} raw magic item records")
    print(f"Imported {len(items)} unique magic items into {out_dir}")
    print(f"Sources: {source_counts}")
    print(f"Categories: {category_counts}")
    print(f"Rarities: {rarity_counts}")
    print(f"Duplicate groups: {len(duplicate_groups)}")
    print(f"Warnings: {len(warnings)}")


if __name__ == "__main__":
    main()
