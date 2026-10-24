# -*- coding: utf-8 -*-
"""
Unit tests for VietnamGuide Content-as-Code Depth & Quality Auditor.
Validates frontmatter parsing, structural elements counting, empirical signal extraction,
and ensures canonical Markdown files meet strict editorial depth standards.
"""

from __future__ import annotations

import os
import sys
import unittest
from pathlib import Path

# Add ops directory to sys.path
OPS_DIR = Path(__file__).resolve().parent.parent
if str(OPS_DIR) not in sys.path:
    sys.path.insert(0, str(OPS_DIR))

import content_depth_auditor as auditor


class TestContentDepthAuditor(unittest.TestCase):
    """Test suite for content depth auditor parser, metrics, and contracts."""

    def test_parse_frontmatter_valid(self):
        sample = (
            "---\n"
            'title: "Sample Travel Guide"\n'
            'slug: "sample-guide"\n'
            'route_path: "destinations/sample-guide"\n'
            'travel_cluster: "hanoi-red-river-delta"\n'
            'last_verified: "2026-10-20"\n'
            'author: "Editorial Team"\n'
            "reading_time_minutes: 8\n"
            "word_count: 1400\n"
            "verified: true\n"
            "---\n"
            "# Main Heading\n\n"
            "This is body text."
        )
        fm, body = auditor.parse_frontmatter(sample)
        self.assertEqual(fm.get("title"), "Sample Travel Guide")
        self.assertEqual(fm.get("slug"), "sample-guide")
        self.assertEqual(fm.get("reading_time_minutes"), 8)
        self.assertEqual(fm.get("word_count"), 1400)
        self.assertTrue(fm.get("verified"))
        self.assertIn("This is body text.", body)

    def test_parse_frontmatter_missing(self):
        sample = "# Plain Markdown\nNo frontmatter here."
        fm, body = auditor.parse_frontmatter(sample)
        self.assertEqual(fm, {})
        self.assertEqual(body, sample)

    def test_count_substantive_words(self):
        sample = (
            "# Main Title\n\n"
            "<!-- This is a comment that should not be counted -->\n"
            "Here is the first sentence with substantive prose.\n\n"
            "```python\n"
            "def code_block():\n"
            "    return 'should not be counted'\n"
            "```\n\n"
            "> [!NOTE]\n"
            "> This is a blockquote advice.\n\n"
            "| Header 1 | Header 2 |\n"
            "| :--- | :--- |\n"
            "| Cell 1 | Cell 2 |\n\n"
            "A second paragraph with [a markdown link](https://vietnamguide.net) that stays."
        )
        word_count = auditor.count_substantive_words(sample)
        # Should count words from the 2 prose paragraphs only
        self.assertGreater(word_count, 10)
        self.assertLess(word_count, 35)

    def test_extract_empirical_signals(self):
        sample = (
            "The VIP limousine charges 280,000 VND per ticket ($11.00 USD). "
            "The journey covers 160 km along expressway CT04 in 3.5 hours at speeds up to 100 km/h."
        )
        signals = auditor.extract_empirical_signals(sample)
        self.assertGreaterEqual(signals["currency_mentions"], 2)  # 280,000 VND and $11.00 USD
        self.assertGreaterEqual(signals["distance_mentions"], 2)  # 160 km and 100 km/h
        self.assertGreaterEqual(signals["transit_time_mentions"], 1)  # 3.5 hours
        self.assertGreaterEqual(signals["total_empirical_signals"], 5)

    def test_count_markdown_elements(self):
        sample = (
            "## Overview\nSection text.\n\n"
            "## Logistics and Transport\nSection text.\n\n"
            "### Morning Departure\nDetails.\n\n"
            "| Option | Price |\n"
            "| :--- | :--- |\n"
            "| Bus | 250,000 VND |\n\n"
            "> [!NOTE]\n"
            "> Always carry small banknotes.\n\n"
            "## Frequently Asked Questions\nFAQ text."
        )
        elems = auditor.count_markdown_elements(sample)
        self.assertEqual(elems["h2_count"], 3)
        self.assertEqual(elems["h3_count"], 1)
        self.assertEqual(elems["table_count"], 1)
        self.assertEqual(elems["blockquote_count"], 2)
        self.assertEqual(elems["callout_count"], 1)
        self.assertTrue(elems["has_faq"])

    def test_canonical_content_files_pass_depth_audit(self):
        content_root = OPS_DIR.parent / "content"
        self.assertTrue(content_root.exists(), "content/ root directory must exist")

        md_files = list(content_root.rglob("*.md"))
        self.assertGreater(
            len(md_files), 0, "There must be at least one canonical article in content/"
        )

        for md_path in md_files:
            report = auditor.audit_markdown_file(md_path, min_words=1200, min_hls=85.0)
            self.assertTrue(
                report["passed"],
                f"Content depth audit failed for {md_path.name}: {report.get('errors')}",
            )
            self.assertGreaterEqual(
                report["word_count"],
                1200,
                f"{md_path.name} must have >= 1200 substantive words",
            )
            self.assertEqual(
                report["slop_report"]["tier1_count"],
                0,
                f"{md_path.name} must have 0 Tier 1 clichés",
            )
            self.assertGreaterEqual(
                report["slop_report"]["hls_score"],
                85.0,
                f"{md_path.name} HLS must be >= 85.0",
            )
            self.assertGreaterEqual(
                report["empirical_signals"]["total_empirical_signals"],
                10,
                f"{md_path.name} must contain rich empirical signals",
            )

    def test_canonical_content_registry_parity_and_no_duplicates(self):
        import json

        content_root = OPS_DIR.parent / "content"
        registry_path = OPS_DIR / "route_registry.json"
        self.assertTrue(registry_path.exists(), "ops/route_registry.json must exist")

        reg_data = json.loads(registry_path.read_text(encoding="utf-8"))
        registry_routes = {
            str(r.get("path", "")).strip("/")
            for r in reg_data.get("routes", [])
            if r.get("path")
        }

        dir_report = auditor.audit_content_directory(content_root, min_words=1200, min_hls=85.0)
        self.assertTrue(
            dir_report["passed"],
            f"Directory audit failed: failed_files={dir_report['failed_files']}, duplicates={dir_report.get('duplicate_route_paths')}",
        )
        self.assertGreaterEqual(dir_report["scanned_files"], 193)
        self.assertLessEqual(dir_report["scanned_files"], len(registry_routes))
        self.assertEqual(dir_report["passed_files"], dir_report["scanned_files"])
        self.assertEqual(dir_report.get("duplicate_route_paths"), [])

        seen_routes = set()
        for r in dir_report["results"]:
            fm = r.get("frontmatter", {})
            rp = str(fm.get("route_path", "")).strip()
            self.assertIn(rp, registry_routes, f"route_path '{rp}' must exist in route_registry.json")
            self.assertNotIn(rp, seen_routes, f"Duplicate route_path '{rp}' found")
            seen_routes.add(rp)
        self.assertEqual(len(seen_routes), dir_report["scanned_files"])

    def test_compile_guide_content_and_php_runtime_bridge(self):
        import compile_guide_content as compiler

        content_root = OPS_DIR.parent / "content"
        md_files = sorted(content_root.rglob("*.md"))
        self.assertGreaterEqual(len(md_files), 193)

        for md_path in md_files:
            compiled = compiler.compile_markdown_file(md_path)
            self.assertTrue(
                compiled["passed"],
                f"Compilation failed for {md_path.name}: {compiled['errors']}",
            )
            self.assertIn("vg-guide-hero", compiled["hero_html"])
            self.assertIn("<h1>", compiled["hero_html"])
            self.assertNotIn("<h1", compiled["body_html"].lower())
            self.assertGreaterEqual(compiled["stats"]["h2_count"], 4)
            self.assertGreaterEqual(compiled["stats"]["table_count"], 1)

        php_path = (
            OPS_DIR.parent
            / "wordpress"
            / "wp-content"
            / "themes"
            / "vietnamguide-premium"
            / "inc"
            / "guide-content.php"
        )
        php_src = php_path.read_text(encoding="utf-8")
        self.assertIn("vg_try_load_repository_markdown_guide", php_src)
        self.assertNotIn("<h2", php_src.lower())


if __name__ == "__main__":
    unittest.main()
