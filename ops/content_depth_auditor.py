# -*- coding: utf-8 -*-
"""
VietnamGuide Content-as-Code Depth & Quality Auditor.
Audits Markdown pillar articles against strict editorial depth, structural integrity,
empirical signal density, and Anti-AI Slop standards.
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sys
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

# Add ops to path to import anti_ai_slop_linter
OPS_DIR = Path(__file__).resolve().parent
if str(OPS_DIR) not in sys.path:
    sys.path.insert(0, str(OPS_DIR))

try:
    import anti_ai_slop_linter as slop_linter
except ImportError:
    slop_linter = None

# Default validation thresholds
DEFAULT_MIN_WORDS = 1200
DEFAULT_MIN_H2_COUNT = 4
DEFAULT_MIN_TABLES = 1
DEFAULT_MIN_BLOCKQUOTES = 1
DEFAULT_MIN_HLS = 85.0

MANDATORY_FRONTMATTER_FIELDS = [
    "title",
    "slug",
    "route_path",
    "description",
    "travel_cluster",
    "last_verified",
    "author",
    "reading_time_minutes",
    "word_count",
]

# Empirical signal regexes
EMPIRICAL_CURRENCY_REGEX = re.compile(
    r"(?:\b\d{1,3}(?:[.,]\d{3})*\s*(?:VND|k\s*VND|d\b|đ\b)|\$\s*\d+(?:\.\d{2})?)",
    re.IGNORECASE,
)
EMPIRICAL_DISTANCE_REGEX = re.compile(
    r"\b\d+(?:\.\d+)?\s*(?:km|kilometers?|km/h|m\b|meters?)\b",
    re.IGNORECASE,
)
EMPIRICAL_TRANSIT_TIME_REGEX = re.compile(
    r"\b\d+(?:[.,]\d+)?\s*(?:hours?|hrs?|minutes?|mins?)\b",
    re.IGNORECASE,
)


def parse_frontmatter(content: str) -> Tuple[Dict[str, Any], str]:
    """Extract YAML frontmatter and markdown body from file content."""
    lines = content.splitlines(keepends=True)
    if not lines or lines[0].strip() != "---":
        return {}, content

    end_idx = -1
    for i in range(1, len(lines)):
        if lines[i].strip() == "---":
            end_idx = i
            break

    if end_idx == -1:
        return {}, content

    fm_raw = "".join(lines[1:end_idx])
    body = "".join(lines[end_idx + 1 :])

    # Simple YAML key-value parser (supports string, int, list)
    frontmatter: Dict[str, Any] = {}
    current_list_key: Optional[str] = None

    for line in fm_raw.splitlines():
        trimmed = line.strip()
        if not trimmed or trimmed.startswith("#"):
            continue

        if trimmed.startswith("- ") and current_list_key:
            item_val = trimmed[2:].strip().strip("\"'")
            frontmatter[current_list_key].append(item_val)
            continue

        if ":" in line:
            key, val = line.split(":", 1)
            key = key.strip()
            val = val.strip()

            if not val:  # Potential list start
                frontmatter[key] = []
                current_list_key = key
            else:
                current_list_key = None
                # Strip quotes if string
                if (val.startswith('"') and val.endswith('"')) or (
                    val.startswith("'") and val.endswith("'")
                ):
                    val = val[1:-1]
                elif val.isdigit():
                    val = int(val)
                elif val.lower() == "true":
                    val = True
                elif val.lower() == "false":
                    val = False
                frontmatter[key] = val

    return frontmatter, body


def count_substantive_words(body: str) -> int:
    """Count substantive prose words excluding markdown headers, tables, code blocks, and comments."""
    # Remove HTML comments
    clean = re.sub(r"<!--.*?-->", "", body, flags=re.DOTALL)
    # Remove code blocks
    clean = re.sub(r"```.*?```", "", clean, flags=re.DOTALL)
    # Remove inline code
    clean = re.sub(r"`[^`]*`", "", clean)
    # Remove markdown link syntax [text](url) -> text
    clean = re.sub(r"\[([^\]]+)\]\([^)]+\)", r"\1", clean)
    # Remove markdown images
    clean = re.sub(r"!\[[^\]]*\]\([^)]+\)", "", clean)
    # Remove markdown headers and blockquotes
    clean = re.sub(r"^#{1,6}\s+.*$", "", clean, flags=re.MULTILINE)
    clean = re.sub(r"^>\s+.*$", "", clean, flags=re.MULTILINE)
    # Remove table markup lines
    clean = re.sub(r"^\|.*\|$", "", clean, flags=re.MULTILINE)

    words = re.findall(r"\b[A-Za-z0-9'-]+\b", clean)
    return len(words)


def extract_headings(body: str) -> List[Dict[str, Any]]:
    """Extract markdown headings with level and title."""
    headings = []
    for line in body.splitlines():
        m = re.match(r"^(#{1,6})\s+(.+)$", line.strip())
        if m:
            headings.append({
                "level": len(m.group(1)),
                "title": m.group(2).strip(),
            })
    return headings


def count_markdown_elements(body: str) -> Dict[str, int]:
    """Count structural markdown elements: tables, blockquotes, code blocks, lists."""
    h2_count = len(re.findall(r"^##\s+.*$", body, re.MULTILINE))
    h3_count = len(re.findall(r"^###\s+.*$", body, re.MULTILINE))

    # Tables: counted by rows with pipe delimiters
    table_separator_count = len(re.findall(r"^\|?\s*[-:]+[-| :]*\s*\|?$", body, re.MULTILINE))
    blockquote_count = len(re.findall(r"^>\s+.*$", body, re.MULTILINE))

    # Pro-tip or tactical alert blocks
    callouts_count = len(re.findall(r"^>\s*\[!(?:NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]", body, re.MULTILINE))

    # FAQ presence
    has_faq = bool(
        re.search(r"^##\s+.*(?:Frequently Asked Questions|FAQ|Common Questions).*$", body, re.IGNORECASE | re.MULTILINE)
    )

    # HowTo / Step-by-Step presence
    has_steps = bool(
        re.search(r"^##\s+.*(?:Step-by-Step|How to|Protocol|Practical Checklist).*$", body, re.IGNORECASE | re.MULTILINE)
    )

    return {
        "h2_count": h2_count,
        "h3_count": h3_count,
        "table_count": table_separator_count,
        "blockquote_count": blockquote_count,
        "callout_count": callouts_count,
        "has_faq": has_faq,
        "has_steps": has_steps,
    }


def extract_empirical_signals(body: str) -> Dict[str, Any]:
    """Scan article text for verifiable real-world empirical figures."""
    currencies = EMPIRICAL_CURRENCY_REGEX.findall(body)
    distances = EMPIRICAL_DISTANCE_REGEX.findall(body)
    transit_times = EMPIRICAL_TRANSIT_TIME_REGEX.findall(body)

    total_signals = len(currencies) + len(distances) + len(transit_times)

    return {
        "currency_mentions": len(currencies),
        "distance_mentions": len(distances),
        "transit_time_mentions": len(transit_times),
        "total_empirical_signals": total_signals,
        "sample_currencies": currencies[:5],
        "sample_distances": distances[:5],
        "sample_transit_times": transit_times[:5],
    }


def audit_markdown_file(
    file_path: Path,
    min_words: int = DEFAULT_MIN_WORDS,
    min_hls: float = DEFAULT_MIN_HLS,
) -> Dict[str, Any]:
    """Run full comprehensive depth and quality audit on a single Markdown article."""
    if not file_path.exists():
        return {
            "path": str(file_path),
            "passed": False,
            "errors": [f"File does not exist: {file_path}"],
        }

    try:
        content = file_path.read_text(encoding="utf-8")
    except Exception as e:
        return {
            "path": str(file_path),
            "passed": False,
            "errors": [f"Failed to read file: {e}"],
        }

    errors: List[str] = []
    warnings: List[str] = []

    # 1. Frontmatter Validation
    frontmatter, body = parse_frontmatter(content)
    if not frontmatter:
        errors.append("Missing YAML frontmatter (must start and close with '---')")
    else:
        for field in MANDATORY_FRONTMATTER_FIELDS:
            if field not in frontmatter or frontmatter[field] is None or frontmatter[field] == "":
                errors.append(f"Frontmatter missing mandatory field: '{field}'")
        fm_slug = str(frontmatter.get("slug", "")).strip()
        if fm_slug and fm_slug != file_path.stem:
            errors.append(
                f"Frontmatter slug '{fm_slug}' does not match filename stem '{file_path.stem}'"
            )
        fm_route = str(frontmatter.get("route_path", "")).strip()
        if fm_route and (fm_route.startswith("/") or fm_route.endswith("/")):
            errors.append(
                f"Frontmatter route_path '{fm_route}' must not have leading or trailing slashes"
            )
        if file_path.parent.name in {"destinations", "itineraries", "compare", "plan"}:
            expected_route = f"{file_path.parent.name}/{file_path.stem}"
            if fm_route and fm_route != expected_route:
                errors.append(
                    f"Frontmatter route_path '{fm_route}' does not match relative path '{expected_route}'"
                )

    # 2. Substantive Word Count
    word_count = count_substantive_words(body)
    if word_count < min_words:
        errors.append(
            f"Word count below minimum substantive threshold: {word_count} words (required >= {min_words})"
        )

    # 3. Structural Markdown Elements
    elements = count_markdown_elements(body)
    if elements["h2_count"] < DEFAULT_MIN_H2_COUNT:
        errors.append(
            f"Insufficient H2 sections: found {elements['h2_count']}, required >= {DEFAULT_MIN_H2_COUNT} (needed for Sticky Reading Spine TOC)"
        )

    if elements["table_count"] < DEFAULT_MIN_TABLES:
        errors.append(
            f"Missing structured comparison/spec table: found {elements['table_count']}, required >= {DEFAULT_MIN_TABLES}"
        )

    if elements["blockquote_count"] < DEFAULT_MIN_BLOCKQUOTES and elements["callout_count"] == 0:
        errors.append(
            f"Missing editorial callout/pro-tip blockquote: required >= {DEFAULT_MIN_BLOCKQUOTES}"
        )

    if not (elements["has_faq"] or elements["has_steps"]):
        warnings.append(
            "Article does not include a dedicated FAQ or Step-by-Step section"
        )

    # 4. Empirical Signal Density
    signals = extract_empirical_signals(body)
    if signals["currency_mentions"] == 0:
        errors.append("No concrete pricing or fees found in text (missing VND/USD amounts)")
    if signals["distance_mentions"] == 0 and signals["transit_time_mentions"] == 0:
        warnings.append("Low transit/spatial density: no km or transit time mentions found")

    # 5. Anti-AI Slop Quality Engine Verification
    slop_report: Dict[str, Any] = {}
    if slop_linter:
        slop_report = slop_linter.analyze_text(body)
        tier1_count = slop_report.get("tier1_count", 0)
        hls_score = slop_report.get("hls_score", 100.0)

        if tier1_count > 0:
            tier1_phrases = [v.get("phrase", "") for v in slop_report.get("tier1_violations", [])]
            errors.append(
                f"Anti-AI Slop Tier 1 clichés detected ({tier1_count}): {', '.join(tier1_phrases)}"
            )

        if hls_score < min_hls:
            errors.append(
                f"Human-Likeness Score (HLS) below threshold: {hls_score:.1f}/100 (required >= {min_hls:.1f})"
            )
    else:
        warnings.append("anti_ai_slop_linter module unavailable; skipped linguistic scan")

    passed = len(errors) == 0

    return {
        "path": str(file_path.as_posix()),
        "relative_path": str(file_path.relative_to(OPS_DIR.parent).as_posix()) if file_path.is_relative_to(OPS_DIR.parent) else str(file_path),
        "passed": passed,
        "word_count": word_count,
        "frontmatter": frontmatter,
        "elements": elements,
        "empirical_signals": signals,
        "slop_report": {
            "passed": slop_report.get("passed", True) if slop_report else True,
            "hls_score": slop_report.get("hls_score", 100.0) if slop_report else 100.0,
            "tier1_count": slop_report.get("tier1_count", 0) if slop_report else 0,
            "tier1_violations": slop_report.get("tier1_violations", []) if slop_report else [],
        },
        "errors": errors,
        "warnings": warnings,
    }


def audit_content_directory(
    root_dir: Path,
    min_words: int = DEFAULT_MIN_WORDS,
    min_hls: float = DEFAULT_MIN_HLS,
) -> Dict[str, Any]:
    """Scan all .md files in the content/ directory tree and enforce 1-to-1 route registry parity."""
    if not root_dir.exists():
        return {
            "root": str(root_dir),
            "passed": False,
            "scanned_files": 0,
            "results": [],
            "errors": [f"Directory not found: {root_dir}"],
        }

    registry_path = OPS_DIR / "route_registry.json"
    registry_routes: set[str] = set()
    if registry_path.exists():
        try:
            reg_data = json.loads(registry_path.read_text(encoding="utf-8"))
            registry_routes = {
                str(r.get("path", "")).strip("/")
                for r in reg_data.get("routes", [])
                if r.get("path")
            }
        except Exception:
            registry_routes = set()

    md_files = sorted(root_dir.rglob("*.md"))
    results = [audit_markdown_file(p, min_words=min_words, min_hls=min_hls) for p in md_files]

    route_counts: Dict[str, int] = {}
    for p, r in zip(md_files, results):
        fm = r.get("frontmatter", {})
        rp = str(fm.get("route_path", "")).strip()
        if rp:
            route_counts[rp] = route_counts.get(rp, 0) + 1

    duplicate_routes = sorted([rp for rp, cnt in route_counts.items() if cnt > 1])

    for p, r in zip(md_files, results):
        fm = r.get("frontmatter", {})
        rp = str(fm.get("route_path", "")).strip()
        slug = str(fm.get("slug", "")).strip()
        rel_expected = p.relative_to(root_dir).with_suffix("").as_posix()

        if slug != p.stem:
            msg = f"Slug '{slug}' does not match filename stem '{p.stem}'"
            if msg not in r["errors"]:
                r["errors"].append(msg)
        if rp != rel_expected:
            msg = f"route_path '{rp}' does not match file relative path '{rel_expected}'"
            if msg not in r["errors"]:
                r["errors"].append(msg)
        if registry_routes and rp not in registry_routes:
            r["errors"].append(
                f"route_path '{rp}' does not exist in ops/route_registry.json"
            )
        if rp in duplicate_routes:
            r["errors"].append(
                f"Duplicate route_path '{rp}' detected across multiple Markdown files"
            )
        r["passed"] = len(r["errors"]) == 0

    total_scanned = len(results)
    total_passed = sum(1 for r in results if r["passed"])
    all_passed = (
        total_scanned > 0
        and total_passed == total_scanned
        and len(duplicate_routes) == 0
    )

    return {
        "root": str(root_dir.as_posix()),
        "passed": all_passed,
        "scanned_files": total_scanned,
        "passed_files": total_passed,
        "failed_files": total_scanned - total_passed,
        "duplicate_route_paths": duplicate_routes,
        "results": results,
    }


def main() -> int:
    parser = argparse.ArgumentParser(
        description="VietnamGuide Content-as-Code Depth & Quality Auditor"
    )
    parser.add_argument(
        "--dir",
        type=str,
        default="content",
        help="Root directory to scan Markdown articles (default: content)",
    )
    parser.add_argument(
        "--file",
        type=str,
        default=None,
        help="Scan a single Markdown file",
    )
    parser.add_argument(
        "--min-words",
        type=int,
        default=DEFAULT_MIN_WORDS,
        help=f"Minimum substantive word count threshold (default: {DEFAULT_MIN_WORDS})",
    )
    parser.add_argument(
        "--min-hls",
        type=float,
        default=DEFAULT_MIN_HLS,
        help=f"Minimum Human-Likeness Score (default: {DEFAULT_MIN_HLS})",
    )
    parser.add_argument(
        "--strict",
        action="store_true",
        help="Strict mode: exit with non-zero code on any error",
    )
    parser.add_argument(
        "--json-report",
        type=str,
        default=None,
        help="Path to write JSON audit report",
    )

    args = parser.parse_args()

    project_root = OPS_DIR.parent

    if args.file:
        target_path = Path(args.file)
        if not target_path.is_absolute():
            target_path = project_root / target_path
        report = audit_markdown_file(
            target_path, min_words=args.min_words, min_hls=args.min_hls
        )
        reports = [report]
        summary_passed = report["passed"]
    else:
        target_dir = Path(args.dir)
        if not target_dir.is_absolute():
            target_dir = project_root / target_dir
        dir_report = audit_content_directory(
            target_dir, min_words=args.min_words, min_hls=args.min_hls
        )
        reports = dir_report["results"]
        summary_passed = dir_report["passed"]

    # Console display
    print("=" * 72)
    print("   VietnamGuide Content-as-Code Depth & Quality Auditor   ")
    print("=" * 72)

    for r in reports:
        status_str = "[PASS]" if r["passed"] else "[FAIL]"
        rel_path = r.get("relative_path", r["path"])
        words = r.get("word_count", 0)
        hls = r.get("slop_report", {}).get("hls_score", 0.0)
        signals = r.get("empirical_signals", {}).get("total_empirical_signals", 0)

        print(f"\n{status_str} {rel_path}")
        print(f"       Words: {words} | HLS: {hls:.1f}/100 | Empirical Signals: {signals}")

        if r.get("errors"):
            print("       Errors:")
            for err in r["errors"]:
                print(f"         x {err}")
        if r.get("warnings"):
            print("       Warnings:")
            for w in r["warnings"]:
                print(f"         ! {w}")

    print("\n" + "=" * 72)
    total_files = len(reports)
    passed_count = sum(1 for r in reports if r["passed"])
    print(f"Summary: {passed_count}/{total_files} articles passed quality audit.")
    print("=" * 72)

    if args.json_report:
        out_path = Path(args.json_report)
        if not out_path.is_absolute():
            out_path = project_root / out_path
        out_path.parent.mkdir(parents=True, exist_ok=True)
        report_data = {
            "passed": summary_passed,
            "total_articles": total_files,
            "passed_articles": passed_count,
            "min_words_threshold": args.min_words,
            "min_hls_threshold": args.min_hls,
            "articles": reports,
        }
        out_path.write_text(json.dumps(report_data, indent=2, ensure_ascii=False), encoding="utf-8")
        print(f"Wrote JSON report to: {out_path}")

    if args.strict and not summary_passed:
        return 1

    return 0 if summary_passed else (1 if args.strict else 0)


if __name__ == "__main__":
    sys.exit(main())
