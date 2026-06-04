import argparse
import json
import re
import time
from pathlib import Path
from urllib.parse import urlencode

import requests


API_BASE = "https://api.open5e.com/v2/magicitems/"
USER_AGENT = "DND Copilot local potion importer"
SOURCE_PRIORITY = {
    "srd-2024": 0,
    "srd-2014": 1,
    "vom": 2,
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


def get_all_potions(limit=100):
    url = f"{API_BASE}?{urlencode({'category': 'potion', 'limit': limit})}"
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


def normalize_potion(record):
    category = record.get("category") or {}
    rarity = record.get("rarity") or {}
    document = record.get("document") or {}
    publisher = document.get("publisher") or {}
    name = record.get("name") or "Unknown Potion"
    source_key = document.get("key") or "unknown"

    return {
        "id": record.get("key") or slugify(name),
        "name": name,
        "name_ru": "",
        "type": category.get("name") or "Potion",
        "type_key": category.get("key") or "potion",
        "rarity": rarity.get("name") or "Unknown",
        "rarity_value": rarity.get("key") or "unknown",
        "rarity_rank": rarity.get("rank"),
        "description": record.get("desc") or "",
        "description_md": "",
        "attunement": bool(record.get("requires_attunement")),
        "attunement_by": None,
        "attunement_requirement": record.get("attunement_detail") or "",
        "is_oil": name.lower().startswith("oil of"),
        "is_healing_group": name.lower() in {"potion of healing", "potions of healing"},
        "source": document.get("name") or source_key,
        "source_key": source_key,
        "source_display": document.get("display_name") or document.get("name") or source_key,
        "source_url": document.get("permalink") or "",
        "publisher": publisher.get("name") or "",
        "license": "Open5e source data",
    }


def validate_potion(potion):
    warnings = []
    if potion["type_key"] != "potion":
        warnings.append(f"unexpected category: {potion['type_key']!r}")
    if not potion["description"]:
        warnings.append("missing description")
    if potion["attunement"]:
        warnings.append("potion unexpectedly requires attunement")
    return warnings


def write_json(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--out", default="public/data/srd")
    parser.add_argument("--raw-out", default=".cache/open5e-potions/raw-category-potion.json")
    args = parser.parse_args()

    out_dir = Path(args.out)
    raw_out = Path(args.raw_out)
    raw_records = get_all_potions()
    selected_records, duplicate_groups = dedupe_by_name(raw_records)

    potions = [normalize_potion(record) for record in selected_records]
    potions.sort(key=lambda item: item["name"])

    warnings = []
    for potion in potions:
        potion_warnings = validate_potion(potion)
        if potion_warnings:
            warnings.append({"id": potion["id"], "name": potion["name"], "warnings": potion_warnings})

    index_rows = [
        {
            "id": potion["id"],
            "name": potion["name"],
            "name_ru": potion["name_ru"],
            "type": potion["type"],
            "type_key": potion["type_key"],
            "rarity": potion["rarity"],
            "rarity_value": potion["rarity_value"],
            "rarity_rank": potion["rarity_rank"],
            "is_oil": potion["is_oil"],
            "is_healing_group": potion["is_healing_group"],
            "source": potion["source"],
            "source_key": potion["source_key"],
            "publisher": potion["publisher"],
        }
        for potion in potions
    ]

    source_counts = {}
    for potion in potions:
        source_counts[potion["source_key"]] = source_counts.get(potion["source_key"], 0) + 1

    report = {
        "source_api": API_BASE,
        "source_filter": "category=potion",
        "raw_count": len(raw_records),
        "imported_count": len(potions),
        "duplicate_group_count": len(duplicate_groups),
        "source_counts": source_counts,
        "duplicate_groups": {
            name: [{"key": record.get("key"), "document": (record.get("document") or {}).get("key")} for record in records]
            for name, records in sorted(duplicate_groups.items())
        },
        "warnings": warnings,
        "warning_count": len(warnings),
    }

    raw_out.parent.mkdir(parents=True, exist_ok=True)
    write_json(raw_out, raw_records)
    write_json(out_dir / "potions.json", potions)
    write_json(out_dir / "potions.index.json", index_rows)
    write_json(out_dir / "potions-import-report.json", report)

    print(f"Fetched {len(raw_records)} raw potion records")
    print(f"Imported {len(potions)} unique potions into {out_dir}")
    print(f"Sources: {source_counts}")
    print(f"Duplicate groups: {len(duplicate_groups)}")
    print(f"Warnings: {len(warnings)}")


if __name__ == "__main__":
    main()
