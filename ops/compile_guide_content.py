# -*- coding: utf-8 -*-
"""
VietnamGuide Content-as-Code Markdown-to-Gutenberg HTML Compiler.
Compiles canonical Markdown articles in content/<category>/<slug>.md into deterministic
Gutenberg-compatible HTML structures expected by vg_split_guide_blocks() and
vg_prepare_guide_content() in inc/guide-content.php.
"""

from __future__ import annotations

import argparse
import html
import re
import sys
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

OPS_DIR = Path(__file__).resolve().parent
if str(OPS_DIR) not in sys.path:
    sys.path.insert(0, str(OPS_DIR))

from content_depth_auditor import parse_frontmatter


def slugify_heading(text: str) -> str:
    """Create a URL-safe slug for heading IDs matching WordPress sanitize_title behavior."""
    clean = re.sub(r"<[^>]+>", "", text)
    clean = html.unescape(clean).lower()
    clean = re.sub(r"[^a-z0-9]+", "-", clean).strip("-")
    return clean or "section"


def render_inline_markdown(text: str) -> str:
    """Convert inline markdown (**bold**, *italic*, `code`, [label](url)) into safe HTML."""
    escaped = html.escape(text, quote=False)

    # Inline code `...`
    escaped = re.sub(
        r"`([^`]+)`",
        lambda m: f"<code>{m.group(1)}</code>",
        escaped,
    )

    # Markdown links [label](url)
    def _replace_link(match: re.Match) -> str:
        label = match.group(1)
        url = html.escape(match.group(2).strip(), quote=True)
        return f'<a href="{url}">{label}</a>'

    escaped = re.sub(r"\[([^\]]+)\]\(([^)]+)\)", _replace_link, escaped)

    # Bold **...**
    escaped = re.sub(r"\*\*([^*]+)\*\*", r"<strong>\1</strong>", escaped)

    # Italic *...*
    escaped = re.sub(r"(?<!\*)\*([^*\n]+)\*(?!\*)", r"<em>\1</em>", escaped)

    return escaped


def compile_markdown_body_to_html(body: str) -> Tuple[Optional[str], str, Dict[str, int]]:
    """
    Parse Markdown body into:
      - extracted_h1: First H1 text if present in body
      - body_html: Compiled HTML for body sections (guaranteed 0 <h1> tags)
      - stats: Counts of h2, h3, table, callout, blockquote elements
    """
    lines = body.splitlines()
    extracted_h1: Optional[str] = None
    html_blocks: List[str] = []

    h2_count = 0
    h3_count = 0
    table_count = 0
    callout_count = 0
    blockquote_count = 0
    used_ids: Dict[str, int] = {}

    def allocate_id(title_text: str) -> str:
        base = slugify_heading(title_text)
        if base not in used_ids:
            used_ids[base] = 1
            return base
        used_ids[base] += 1
        return f"{base}-{used_ids[base]}"

    i = 0
    n = len(lines)
    while i < n:
        line = lines[i]
        stripped = line.strip()

        if not stripped:
            i += 1
            continue

        # HTML comments
        if stripped.startswith("<!--"):
            while i < n and "-->" not in lines[i]:
                i += 1
            i += 1
            continue

        # Horizontal rule
        if re.match(r"^-{3,}$|^\*{3,}$|^_{3,}$", stripped):
            html_blocks.append('<hr class="wp-block-separator has-alpha-channel-opacity"/>')
            i += 1
            continue

        # Fenced code block
        if stripped.startswith("```"):
            lang = stripped[3:].strip()
            raw_code_lines: List[str] = []
            code_lines: List[str] = []
            i += 1
            while i < n and not lines[i].strip().startswith("```"):
                raw_code_lines.append(lines[i])
                code_lines.append(html.escape(lines[i], quote=False))
                i += 1
            i += 1  # skip closing ```
            raw_joined = "\n".join(raw_code_lines)
            if re.search(r"^\+[=-]+\+", raw_joined, re.MULTILINE) and "|" in raw_joined:
                table_count += 1
            cls_attr = f' class="language-{html.escape(lang, quote=True)}"' if lang else ""
            html_blocks.append(
                f'<pre class="wp-block-code"><code{cls_attr}>'
                + "\n".join(code_lines)
                + "</code></pre>"
            )
            continue

        # Headings
        heading_match = re.match(r"^(#{1,6})\s+(.+)$", stripped)
        if heading_match:
            level = len(heading_match.group(1))
            raw_title = heading_match.group(2).strip()
            if level == 1:
                if extracted_h1 is None:
                    extracted_h1 = raw_title
                else:
                    # Demote any secondary H1 in body to H2 so body_source never contains <h1>
                    h_id = allocate_id(raw_title)
                    h2_count += 1
                    html_blocks.append(
                        f'<h2 id="{h_id}">{render_inline_markdown(raw_title)}</h2>'
                    )
            elif level == 2:
                h_id = allocate_id(raw_title)
                h2_count += 1
                html_blocks.append(
                    f'<h2 id="{h_id}">{render_inline_markdown(raw_title)}</h2>'
                )
            elif level == 3:
                h_id = allocate_id(raw_title)
                h3_count += 1
                html_blocks.append(
                    f'<h3 id="{h_id}">{render_inline_markdown(raw_title)}</h3>'
                )
            else:
                tag = f"h{min(level, 6)}"
                html_blocks.append(
                    f"<{tag}>{render_inline_markdown(raw_title)}</{tag}>"
                )
            i += 1
            continue

        # Pipe table
        if stripped.startswith("|") and stripped.endswith("|"):
            table_lines: List[str] = []
            while i < n and lines[i].strip().startswith("|"):
                table_lines.append(lines[i].strip())
                i += 1
            if len(table_lines) >= 2:
                header_cells = [
                    c.strip() for c in table_lines[0].strip("|").split("|")
                ]
                body_start = 2 if re.match(r"^\|?\s*[-:]+[-| :]*\s*\|?$", table_lines[1]) else 1
                thead_html = (
                    "<thead><tr>"
                    + "".join(f"<th>{render_inline_markdown(c)}</th>" for c in header_cells)
                    + "</tr></thead>"
                )
                tbody_rows: List[str] = []
                for row_line in table_lines[body_start:]:
                    cells = [c.strip() for c in row_line.strip("|").split("|")]
                    tbody_rows.append(
                        "<tr>"
                        + "".join(f"<td>{render_inline_markdown(c)}</td>" for c in cells)
                        + "</tr>"
                    )
                tbody_html = "<tbody>" + "".join(tbody_rows) + "</tbody>"
                html_blocks.append(
                    f'<figure class="wp-block-table"><table>{thead_html}{tbody_html}</table></figure>'
                )
                table_count += 1
            continue

        # Blockquote or GitHub Callout (> [!NOTE], > [!TIP], etc.)
        if stripped.startswith(">"):
            bq_lines: List[str] = []
            while i < n and lines[i].strip().startswith(">"):
                content_part = re.sub(r"^>\s?", "", lines[i].strip())
                bq_lines.append(content_part)
                i += 1
            blockquote_count += 1
            first_line = bq_lines[0].strip() if bq_lines else ""
            alert_match = re.match(
                r"^\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*(.*)$",
                first_line,
                re.IGNORECASE,
            )
            if alert_match:
                callout_type = alert_match.group(1).lower()
                remainder_first = alert_match.group(2).strip()
                inner_lines = ([remainder_first] if remainder_first else []) + [
                    ln for ln in bq_lines[1:] if ln.strip()
                ]
                inner_html = "".join(
                    f"<p>{render_inline_markdown(ln)}</p>" for ln in inner_lines
                )
                callout_count += 1
                html_blocks.append(
                    f'<blockquote class="wp-block-quote vg-callout vg-callout-{callout_type}">'
                    f'<p class="vg-callout__title"><strong>{callout_type.upper()}</strong></p>'
                    f"{inner_html}</blockquote>"
                )
            else:
                inner_html = "".join(
                    f"<p>{render_inline_markdown(ln)}</p>"
                    for ln in bq_lines
                    if ln.strip()
                )
                html_blocks.append(
                    f'<blockquote class="wp-block-quote">{inner_html}</blockquote>'
                )
            continue

        # Unordered list
        if re.match(r"^[-*+]\s+", stripped):
            items: List[str] = []
            while i < n and re.match(r"^\s*[-*+]\s+", lines[i]):
                item_text = re.sub(r"^\s*[-*+]\s+", "", lines[i]).strip()
                items.append(f"<li>{render_inline_markdown(item_text)}</li>")
                i += 1
            html_blocks.append("<ul>" + "".join(items) + "</ul>")
            continue

        # Ordered list
        if re.match(r"^\d+\.\s+", stripped):
            items = []
            while i < n and re.match(r"^\s*\d+\.\s+", lines[i]):
                item_text = re.sub(r"^\s*\d+\.\s+", "", lines[i]).strip()
                items.append(f"<li>{render_inline_markdown(item_text)}</li>")
                i += 1
            html_blocks.append("<ol>" + "".join(items) + "</ol>")
            continue

        # Standard paragraph (collect contiguous non-blank, non-special lines)
        para_lines: List[str] = []
        while i < n:
            cur = lines[i].strip()
            if (
                not cur
                or cur.startswith("#")
                or cur.startswith(">")
                or cur.startswith("|")
                or cur.startswith("```")
                or cur.startswith("<!--")
                or re.match(r"^[-*+]\s+", cur)
                or re.match(r"^\d+\.\s+", cur)
                or re.match(r"^-{3,}$|^\*{3,}$|^_{3,}$", cur)
            ):
                break
            para_lines.append(cur)
            i += 1

        if para_lines:
            joined = " ".join(para_lines)
            html_blocks.append(f"<p>{render_inline_markdown(joined)}</p>")

    stats = {
        "h2_count": h2_count,
        "h3_count": h3_count,
        "table_count": table_count,
        "callout_count": callout_count,
        "blockquote_count": blockquote_count,
    }
    return extracted_h1, "\n".join(html_blocks), stats


def compile_markdown_file(file_path: Path) -> Dict[str, Any]:
    """Compile a single Markdown file into deterministic VietnamGuide Gutenberg HTML."""
    raw = file_path.read_text(encoding="utf-8")
    frontmatter, body = parse_frontmatter(raw)

    extracted_h1, body_html, stats = compile_markdown_body_to_html(body)

    title = str(frontmatter.get("title") or extracted_h1 or file_path.stem).strip()
    description = str(frontmatter.get("description") or "").strip()
    category = str(frontmatter.get("category") or file_path.parent.name).strip()
    route_path = str(
        frontmatter.get("route_path") or f"{category}/{file_path.stem}"
    ).strip("/")
    author = str(frontmatter.get("author") or "VietnamGuide Editorial Team").strip()
    last_updated = str(
        frontmatter.get("last_updated")
        or frontmatter.get("last_verified")
        or "2026-06-15"
    ).strip()
    reading_time = frontmatter.get("reading_time_minutes") or max(
        5, int(frontmatter.get("word_count", 1300)) // 200
    )

    kicker_html = f'<p class="vg-guide-kicker">{html.escape(category.upper())}</p>'
    h1_html = f"<h1>{html.escape(title)}</h1>"
    lead_html = (
        f'<p class="vg-guide-lead">{html.escape(description)}</p>'
        if description
        else ""
    )
    meta_html = (
        f'<div class="vg-guide-meta">'
        f'<span class="vg-guide-meta__author">By {html.escape(author)}</span>'
        f'<span class="vg-guide-meta__sep" aria-hidden="true"> &middot; </span>'
        f'<span class="vg-guide-meta__updated">Updated {html.escape(last_updated)}</span>'
        f'<span class="vg-guide-meta__sep" aria-hidden="true"> &middot; </span>'
        f'<span class="vg-guide-meta__reading-time">{int(reading_time)} min read</span>'
        f"</div>"
    )

    hero_html = (
        '<div class="wp-block-group alignfull vg-guide-hero">'
        '<div class="vg-guide-hero__inner">'
        f"{kicker_html}{h1_html}{lead_html}{meta_html}"
        "</div>"
        "</div>"
    )

    full_html = hero_html + "\n" + body_html

    # Structural validation matching vg_extract_balanced_hero_container & vg_inspect_guide_html
    hero_h1_count = len(re.findall(r"<h1\b", hero_html, re.IGNORECASE))
    body_h1_count = len(re.findall(r"<h1\b", body_html, re.IGNORECASE))
    errors: List[str] = []
    if "vg-guide-hero" not in hero_html:
        errors.append("Hero container missing vg-guide-hero class")
    if hero_h1_count != 1:
        errors.append(f"Hero container must have exactly 1 <h1>, found {hero_h1_count}")
    if body_h1_count != 0:
        errors.append(f"Body HTML must have 0 <h1> tags, found {body_h1_count}")
    if stats["h2_count"] < 4:
        errors.append(f"Body HTML must have >= 4 <h2> tags, found {stats['h2_count']}")
    if stats["table_count"] < 1:
        errors.append(f"Body HTML must have >= 1 <table>, found {stats['table_count']}")

    return {
        "route_path": route_path,
        "file_path": str(file_path.as_posix()),
        "passed": len(errors) == 0,
        "errors": errors,
        "hero_html": hero_html,
        "body_html": body_html,
        "full_html": full_html,
        "stats": stats,
    }


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Compile VietnamGuide Markdown articles into Gutenberg Guide HTML"
    )
    parser.add_argument(
        "--dir",
        type=str,
        default="content",
        help="Root content directory (default: content)",
    )
    parser.add_argument(
        "--route",
        type=str,
        default=None,
        help="Compile a specific route_path (e.g. destinations/hanoi-travel-guide) and print HTML to stdout",
    )
    parser.add_argument(
        "--check",
        action="store_true",
        help="Verify all Markdown files compile into valid Gutenberg guide HTML",
    )
    args = parser.parse_args()

    project_root = OPS_DIR.parent
    content_root = Path(args.dir)
    if not content_root.is_absolute():
        content_root = project_root / content_root

    if args.route:
        clean_route = args.route.strip("/")
        md_file = content_root / f"{clean_route}.md"
        if not md_file.exists():
            print(f"Error: Markdown file not found for route '{clean_route}': {md_file}", file=sys.stderr)
            return 1
        compiled = compile_markdown_file(md_file)
        if not compiled["passed"]:
            for err in compiled["errors"]:
                print(f"Error: {err}", file=sys.stderr)
            return 1
        sys.stdout.write(compiled["full_html"] + "\n")
        return 0

    md_files = sorted(content_root.rglob("*.md"))
    if not md_files:
        print(f"No Markdown files found in {content_root}", file=sys.stderr)
        return 1

    passed_count = 0
    for md_file in md_files:
        res = compile_markdown_file(md_file)
        if res["passed"]:
            passed_count += 1
        else:
            print(f"[FAIL] {res['route_path']}: {res['errors']}", file=sys.stderr)

    print(
        f"Compiled {passed_count}/{len(md_files)} canonical Markdown guides into Gutenberg HTML."
    )
    return 0 if passed_count == len(md_files) else 1


if __name__ == "__main__":
    sys.exit(main())
