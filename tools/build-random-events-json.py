import json
import re
from pathlib import Path

from pypdf import PdfReader


OUT = Path("public/data/generators/random-events.json")
PDF_ROOT = Path.home() / "Dropbox"

EVENT_CATEGORIES = {
    "travel": "Дорога и странствия",
    "settlement": "Город и поселение",
    "tavern": "Таверна и отдых",
    "dream": "Сон и видение",
    "magic": "Магия и проклятия",
    "social": "Социальная сцена",
    "danger": "Опасность и бой",
    "item": "Предмет или находка",
    "oddity": "Странность",
}


def find_events_pdf():
    pdfs = list(PDF_ROOT.glob("*/*/Copilot PDF/*.pdf"))
    try:
        return next(path for path in pdfs if path.name.startswith("100 ") and "NPC" not in path.name)
    except StopIteration as error:
        raise FileNotFoundError("Не удалось найти PDF со 100 случайными событиями.") from error


def extract_text(path):
    reader = PdfReader(str(path))
    return "\n".join((page.extract_text() or "") for page in reader.pages)


def normalize_text(value):
    text = value.replace("\r", "\n").replace("\u00a0", " ").replace("\u00ad", "")
    text = re.sub(r"[ \t]+", " ", text)
    text = re.sub(r"\n+", "\n", text)
    text = re.sub(r"\s+([,.!?;:])", r"\1", text)
    text = re.sub(r"([({«])\s+", r"\1", text)
    text = re.sub(r"\s+([)}»])", r"\1", text)
    text = re.sub(r"(\d+)\s*к\s*(\d+)", r"\1к\2", text, flags=re.IGNORECASE)
    text = re.sub(r"\bк\s*(\d+)", r"к\1", text, flags=re.IGNORECASE)
    text = re.sub(r"\bСл\s+(\d+)", r"Сл \1", text)
    text = re.sub(r"(?<=\S)\s*/\s*(?=\S)", " / ", text)
    text = re.sub(r"[ \t]{2,}", " ", text)
    return text.strip()


def one_line(value):
    return re.sub(r"\s+", " ", normalize_text(value)).strip()


def make_title(text):
    clean = one_line(text)
    sentence = re.split(r"(?<=[.!?])\s+", clean, maxsplit=1)[0]
    if len(sentence) <= 86:
        return sentence
    return sentence[:83].rstrip(" ,.;:") + "..."


def classify_event(text):
    lowered = text.lower()
    checks = [
        ("dream", [r"\bснится\b", r"\bсон\b", r"\bсне\b", r"\bвидение\b", r"\bпросыпаетесь\b"]),
        ("tavern", [r"таверн", r"трактир", r"постоял", r"попойк", r"\bпиво\b", r"\bвино\b"]),
        ("travel", [r"дорог", r"\bмост", r"верхом", r"привал", r"странств", r"путешеств", r"лошад"]),
        ("settlement", [r"город", r"\bрын", r"лавк", r"поселен", r"стражник", r"площад", r"улиц"]),
        ("magic", [r"магич", r"заклин", r"прокля", r"демон", r"\bфея\b", r"артефакт", r"\bаур", r"\bчар"]),
        ("danger", [r"боев", r"урона", r"спасброс", r"\bатак", r"монстр", r"оруж", r"убит"]),
        ("social", [r"репутац", r"разговор", r"харизм", r"убежден", r"социальн", r"знаком"]),
        ("item", [r"предмет", r"трофей", r"амулет", r"медальон", r"зель", r"статуэт", r"монет"]),
    ]
    for category, patterns in checks:
        if any(re.search(pattern, lowered) for pattern in patterns):
            return category
    return "oddity"


def parse_events(path):
    text = normalize_text(extract_text(path))
    matches = list(re.finditer(r"(?m)^\s*(\d{1,3})\.\s+", text))
    events = []

    for index, match in enumerate(matches):
        roll = int(match.group(1))
        if roll < 1 or roll > 100:
            continue
        end = matches[index + 1].start() if index + 1 < len(matches) else len(text)
        body = normalize_text(text[match.end():end])
        category = classify_event(body)
        events.append({
            "roll": roll,
            "title": make_title(body),
            "text": body,
            "category": category,
            "category_label": EVENT_CATEGORIES[category],
        })

    events.sort(key=lambda entry: entry["roll"])
    return events


def main():
    pdf = find_events_pdf()
    events = parse_events(pdf)
    data = {
        "schema_version": 1,
        "source": {
            "title": "Случайности не случайны! Ещё сотня неприятных случайностей и случайных неприятностей",
            "file": pdf.name,
        },
        "tables": {
            "events": events,
            "categories": EVENT_CATEGORIES,
        },
    }
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Events: {len(events)}")
    print(f"Wrote: {OUT}")


if __name__ == "__main__":
    main()
