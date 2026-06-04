import argparse
import json
import re
import time
from pathlib import Path

import requests


API_BASE = "https://apisearch.thedmstoolkit.com/api/2024/monsters"
LICENSE = {
    "source": "System Reference Document 5.2",
    "source_short": "SRD 5.2",
    "publisher": "Wizards of the Coast LLC",
    "license": "Creative Commons Attribution 4.0 International",
    "license_url": "https://creativecommons.org/licenses/by/4.0/legalcode",
    "source_url": "https://www.dndbeyond.com/srd",
    "attribution": (
        'This work includes material taken from the System Reference Document 5.2 ("SRD 5.2") '
        "by Wizards of the Coast LLC and available at https://www.dndbeyond.com/srd. "
        "The SRD 5.2 is licensed under the Creative Commons Attribution 4.0 International License "
        "available at https://creativecommons.org/licenses/by/4.0/legalcode."
    ),
}


def slugify(value):
    value = value.lower()
    value = re.sub(r"[^a-z0-9]+", "-", value)
    return value.strip("-")


def normalize_creature_type(value):
    remap = {
        "or small humanoid": "humanoid",
        "or small monstrosity": "monstrosity",
        "or small undead": "undead",
        "swarm of tiny beasts": "beast",
        "swarm of tiny undead": "undead",
    }
    value = (value or "").strip().lower()
    return remap.get(value, value)


def get_json(url, retries=8):
    for attempt in range(retries):
        response = requests.get(url, timeout=30)
        if response.status_code != 429:
            response.raise_for_status()
            return response.json()

        retry_after = response.headers.get("Retry-After")
        wait = int(retry_after) if retry_after and retry_after.isdigit() else 20 + attempt * 10
        print(f"Rate limited, waiting {wait}s...")
        time.sleep(wait)

    response.raise_for_status()
    return response.json()


def normalize_monster(record):
    props = record.get("properties") or {}
    slug = record.get("slug") or slugify(record["name"])
    name = record.get("name") or props.get("name") or slug
    challenge = props.get("challenge") or {}

    monster = {
        "id": slug,
        "name": name,
        "name_ru": "",
        "source": LICENSE["source_short"],
        "license": "CC-BY-4.0",
        "type": normalize_creature_type(props.get("creatureType")),
        "subtype": props.get("creatureSubtype") or "",
        "size": props.get("size") or "",
        "alignment": props.get("alignment") or "",
        "cr": props.get("cr") or challenge.get("rating") or "",
        "cr_value": props.get("crValue"),
        "xp": props.get("xp") or challenge.get("xp") or 0,
        "xp_in_lair": props.get("xpInLair") or challenge.get("xpInLair") or 0,
        "proficiency_bonus": props.get("proficiencyBonus"),
        "armor_class": props.get("ac"),
        "armor_class_notes": props.get("acNotes") or "",
        "hit_points": {
            "average": props.get("hp") or props.get("maxHitPoints"),
            "formula": props.get("hitDice") or "",
        },
        "speed": props.get("speed") or {},
        "initiative": props.get("initiative"),
        "abilities": props.get("stats") or {},
        "modifiers": props.get("modifiers") or {},
        "saving_throws": props.get("savingThrows") or {},
        "skills": props.get("skills") or {},
        "senses": props.get("senses") or {},
        "languages": props.get("languages") or [],
        "damage": {
            "vulnerabilities": props.get("damageVulnerabilities") or [],
            "resistances": props.get("damageResistances") or [],
            "immunities": props.get("damageImmunities") or [],
        },
        "condition_immunities": props.get("conditionImmunities") or [],
        "gear": props.get("gear") or "",
        "traits": props.get("traits") or [],
        "actions": (props.get("actions") or {}).get("list") or [],
        "bonus_actions": props.get("bonusActions") or [],
        "reactions": props.get("reactions") or [],
        "legendary_actions": props.get("legendaryActions") or [],
        "habitats": [],
        "description_md": record.get("description_md") or "",
        "description": record.get("description") or "",
    }

    return monster


def validate_monster(raw, monster):
    warnings = []
    props = raw.get("properties") or {}

    if props.get("name") and props["name"] != monster["name"]:
        warnings.append(f"properties.name differs: {props['name']!r}")

    heading = f"## {monster['name']}"
    if monster["description_md"] and heading not in monster["description_md"][:120]:
        warnings.append("markdown heading does not match monster name")

    joined = " ".join(monster["traits"] + monster["actions"] + monster["bonus_actions"]).lower()
    name_tokens = set(monster["name"].lower().replace("-", " ").split())
    suspicious_names = ["half-dragon", "dragon", "aboleth", "wolf", "guard"]
    for token in suspicious_names:
        token_words = set(token.split("-"))
        if token in joined and not token_words.issubset(name_tokens):
            warnings.append(f"possible copied text from another stat block: {token}")
            break

    if not monster["hit_points"]["formula"]:
        warnings.append("missing HP dice formula")

    if monster["cr_value"] is None:
        warnings.append("missing numeric CR value")

    return warnings


def write_json(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--out", default="public/data/srd")
    parser.add_argument("--cache", default=".cache/srd52-monsters")
    parser.add_argument("--delay", type=float, default=1.05)
    parser.add_argument("--limit", type=int, default=0)
    args = parser.parse_args()

    out_dir = Path(args.out)
    cache_dir = Path(args.cache)
    cache_dir.mkdir(parents=True, exist_ok=True)

    list_payload = get_json(API_BASE)
    entries = list_payload["data"]
    if args.limit:
        entries = entries[: args.limit]

    monsters = []
    report = {
        "source_api": API_BASE,
        "expected_count": list_payload.get("count"),
        "imported_count": 0,
        "warnings": [],
    }

    for index, entry in enumerate(entries, start=1):
        slug = entry["slug"]
        cache_file = cache_dir / f"{slug}.json"
        if cache_file.exists():
            raw = json.loads(cache_file.read_text(encoding="utf-8"))
        else:
            raw = get_json(f"{API_BASE}/{slug}")["data"]
            cache_file.write_text(json.dumps(raw, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        monster = normalize_monster(raw)
        warnings = validate_monster(raw, monster)
        if warnings:
            report["warnings"].append({"id": monster["id"], "name": monster["name"], "warnings": warnings})
        monsters.append(monster)
        print(f"[{index}/{len(entries)}] {monster['name']}")
        if index < len(entries) and args.delay:
            time.sleep(args.delay)

    monsters.sort(key=lambda item: item["name"])
    index_rows = [
        {
            "id": monster["id"],
            "name": monster["name"],
            "name_ru": monster["name_ru"],
            "type": monster["type"],
            "size": monster["size"],
            "cr": monster["cr"],
            "cr_value": monster["cr_value"],
            "xp": monster["xp"],
            "hp_average": monster["hit_points"]["average"],
            "hp_formula": monster["hit_points"]["formula"],
            "habitats": monster["habitats"],
        }
        for monster in monsters
    ]

    report["imported_count"] = len(monsters)
    report["warning_count"] = len(report["warnings"])

    write_json(out_dir / "monsters.json", monsters)
    write_json(out_dir / "monsters.index.json", index_rows)
    write_json(out_dir / "licenses.json", {"srd52": LICENSE})
    write_json(out_dir / "import-report.json", report)

    print(f"Imported {len(monsters)} monsters into {out_dir}")
    print(f"Warnings: {len(report['warnings'])}")


if __name__ == "__main__":
    main()
