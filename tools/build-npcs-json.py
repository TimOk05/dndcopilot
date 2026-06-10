import json
import re
from pathlib import Path

from pypdf import PdfReader


OUT = Path("public/data/generators/npcs.json")
PDF_ROOT = Path.home() / "Dropbox"

PROFESSION_CATEGORIES = [
    (1, 12, "Архитектура и строительство"),
    (13, 39, "Безработные, самозанятые и изгои"),
    (40, 68, "Бизнес и торговля"),
    (69, 103, "Вера и религия"),
    (104, 148, "Вооруженные силы и безопасность"),
    (149, 162, "Здоровье"),
    (163, 189, "Искусства"),
    (190, 216, "Магические искусства"),
    (217, 240, "Образование, наука"),
    (241, 273, "Правительство, власть"),
    (274, 293, "Преступность"),
    (294, 335, "Размещение, услуги, черный труд"),
    (336, 400, "Ремесло"),
    (401, 409, "Связь"),
    (410, 446, "Сельское и лесное хозяйство, животноводство"),
    (447, 464, "Транспорт"),
]

ROLE_LABELS = {
    "villain": "Злодеи",
    "commoner": "Обыватели",
    "ally": "Союзники",
}

GENRE_LABELS = {
    "fantasy": "Фэнтези",
    "science_fiction": "Научная фантастика",
    "modern": "Современность",
}


def find_pdf(predicate):
    pdfs = list(PDF_ROOT.glob("*/*/Copilot PDF/*.pdf"))
    try:
        return next(path for path in pdfs if predicate(path))
    except StopIteration as error:
        raise FileNotFoundError("Не удалось найти нужный PDF в папке Copilot PDF.") from error


def extract_pages(path):
    reader = PdfReader(str(path))
    return [page.extract_text() or "" for page in reader.pages]


def clean_page_text(text):
    text = text.replace("\r", "\n").replace("\u00ad", "")
    text = text.replace("\u00a0", " ")
    lines = []
    for line in text.splitlines():
        line = re.sub(r"[ \t]+", " ", line).strip()
        if not line:
            continue
        if re.fullmatch(r"\d{1,3}", line):
            continue
        lines.append(line)
    return "\n".join(lines)


def normalize_value(text):
    text = re.sub(r"\s+", " ", text)
    return text.strip(" \n\t.")


def profession_category(roll):
    for start, end, category in PROFESSION_CATEGORIES:
        if start <= roll <= end:
            return category
    return "Разное"


def parse_professions(path):
    professions = {}
    for page in extract_pages(path):
        text = clean_page_text(page)
        for match in re.finditer(r"(?ms)(\d{1,3})\.\s*(.*?)(?=\n\d{1,3}\.|\Z)", text):
            roll = int(match.group(1))
            if roll < 1 or roll > 464:
                continue
            name = normalize_value(match.group(2))
            if not name:
                continue
            professions[roll] = {
                "roll": roll,
                "name": name,
                "category": profession_category(roll),
            }
    return [professions[roll] for roll in sorted(professions)]


def chapter_meta(title):
    lowered = title.lower()
    role = "commoner"
    if "злодеи" in lowered:
        role = "villain"
    elif "союзники" in lowered:
        role = "ally"

    genre = "fantasy"
    if "науч" in lowered:
        genre = "science_fiction"
    elif "современ" in lowered:
        genre = "modern"

    return {
        "chapter": title,
        "role": role,
        "role_label": ROLE_LABELS[role],
        "genre": genre,
        "genre_label": GENRE_LABELS[genre],
    }


def parse_traits(text):
    text = normalize_value(text)
    author = ""
    author_match = re.match(r"^\(([^)]+)\)\s*(.*)$", text)
    if author_match:
        author = author_match.group(1).strip()
        text = author_match.group(2).strip()

    traits = [
        normalize_value(part)
        for part in re.split(r",\s*", text)
        if normalize_value(part)
    ]
    return author, traits


def parse_npcs(path):
    pages = extract_pages(path)
    text = "\n".join(clean_page_text(page) for page in pages[4:])
    text = re.sub(r"Риплика:", "Реплика:", text)

    chapter_matches = list(re.finditer(r"Глава\s+[^\n:]+:\s*(?:\n)?([^\n]+)", text))
    quote_matches = list(re.finditer(r"Реплика:\s*", text))

    def previous_title_lines(position):
        lines = []
        search_position = position
        while len(lines) < 2 and search_position > 0:
            line_end = text.rfind("\n", 0, search_position)
            if line_end < 0:
                line_end = search_position
                line_start = 0
            else:
                line_start = text.rfind("\n", 0, line_end) + 1
            line = text[line_start:line_end].strip()
            search_position = line_start
            if not line or line.startswith("Глава "):
                continue
            lines.append((line_start, line))
        if len(lines) < 2:
            return None
        archetype_start, archetype = lines[0]
        name_start, name = lines[1]
        return name_start, name, archetype

    def label_match(segment, label):
        return re.search(rf"[.\s]*{label}:\s*", segment)

    def field_between(segment, start_label, end_label):
        start = label_match(segment, start_label)
        end = label_match(segment, end_label)
        if not start or not end or end.start() < start.end():
            return ""
        return normalize_value(segment[start.end():end.start()])

    def valid_title(title):
        if not title:
            return False
        _, name, archetype = title
        if ":" in name or ":" in archetype:
            return False
        return bool(re.match(r"^[А-ЯЁA-Z]", name)) and bool(re.match(r"^[А-ЯЁA-Z]", archetype))

    def next_valid_entry_start(start_index):
        for future_quote in quote_matches[start_index:]:
            title = previous_title_lines(future_quote.start())
            if valid_title(title):
                return title[0]
        return len(text)

    npcs = []
    for quote_index, quote_match in enumerate(quote_matches):
        title = previous_title_lines(quote_match.start())
        if not valid_title(title):
            continue

        entry_start, name, archetype = title
        next_entry_start = next_valid_entry_start(quote_index + 1)

        segment = text[quote_match.end():next_entry_start]
        appearance = field_between(segment, "Внешность", "Отыгрыш")
        roleplay = field_between(segment, "Отыгрыш", "Личность")
        personality = field_between(segment, "Личность", "Мотивация")
        motivation = field_between(segment, "Мотивация", "Биография")
        biography = field_between(segment, "Биография", "Отличи(?:я|е)")
        traits_match = label_match(segment, "Отличи(?:я|е)")
        if not traits_match:
            continue

        quote_end = label_match(segment, "Внешность")
        quote = normalize_value(segment[:quote_end.start()]) if quote_end else ""
        traits_text = normalize_value(segment[traits_match.end():])
        if not quote or not appearance:
            continue

        index = len(npcs) + 1
        chapter_title = "Злодеи фэнтези"
        for chapter in chapter_matches:
            if chapter.start() <= entry_start:
                chapter_title = normalize_value(chapter.group(1))
            else:
                break

        author, traits = parse_traits(traits_text)
        meta = chapter_meta(chapter_title)
        npcs.append({
            "id": f"npc-{index:04d}",
            "roll": index,
            "name": normalize_value(name),
            "archetype": normalize_value(archetype),
            "quote": quote,
            "appearance": appearance,
            "roleplay": roleplay,
            "personality": personality,
            "motivation": motivation,
            "biography": biography,
            "distinctions": traits,
            "author_code": author,
            **meta,
        })
    return npcs


def main():
    npc_pdf = find_pdf(lambda path: path.name.startswith("1000_") and "NPC" in path.name)
    professions_pdf = find_pdf(lambda path: path.name.startswith("464 ") and "LSS" in path.name)

    npcs = parse_npcs(npc_pdf)
    professions = parse_professions(professions_pdf)

    data = {
        "schema_version": 1,
        "source": {
            "npcs": {
                "title": "1000 запоминающихся NPC для любой ролевой игры",
                "file": npc_pdf.name,
            },
            "professions": {
                "title": "Перечень профессий",
                "file": professions_pdf.name,
            },
        },
        "tables": {
            "npcs": npcs,
            "professions": professions,
            "profession_categories": [
                {"start": start, "end": end, "label": category}
                for start, end, category in PROFESSION_CATEGORIES
            ],
            "roles": ROLE_LABELS,
            "genres": GENRE_LABELS,
        },
    }

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"NPC: {len(npcs)}")
    print(f"Professions: {len(professions)}")
    print(f"Wrote: {OUT}")


if __name__ == "__main__":
    main()
