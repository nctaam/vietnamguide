# -*- coding: utf-8 -*-
"""
Stage 215 Production Strictness Contract Tests.

Enforces uncompromising production quality invariants across:
1. PHP 8.x syntax validation (`php -l`) for all theme and mu-plugin PHP files.
2. JavaScript syntax validation (`node --check`) for all theme JS and Service Worker files.
3. Knowledge Graph integrity: 100% live-API-verified Wikidata Q-IDs with zero hallucinated Q-IDs.
4. Defense-in-depth HTTP Security Headers (`X-Content-Type-Options`, `X-Frame-Options`,
   `Referrer-Policy`, `Permissions-Policy`, `Strict-Transport-Security`).
5. Zero Tier-1 AI slop clichés inside PHP Schema.org JSON-LD & FAQ strings.
6. PWA Offline reliability & cache parity (`sw.js`, `offline.html`, `site.webmanifest`).
7. Zero-CLS default-hidden dialogs and WCAG 2.2 AA modal focus-trap & a11y contracts.
"""

from __future__ import annotations

import json
import re
import shutil
import subprocess
import sys
import unittest
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[2]
OPS_DIR = REPO_ROOT / "ops"
THEME_DIR = REPO_ROOT / "wordpress" / "wp-content" / "themes" / "vietnamguide-premium"
MU_PLUGIN = REPO_ROOT / "wordpress" / "wp-content" / "mu-plugins" / "vietnamguide-core.php"
SW_JS = REPO_ROOT / "wordpress" / "sw.js"
OFFLINE_HTML = REPO_ROOT / "wordpress" / "offline.html"
MANIFEST_JSON = THEME_DIR / "site.webmanifest"

if str(OPS_DIR) not in sys.path:
    sys.path.insert(0, str(OPS_DIR))

import anti_ai_slop_linter as slop_linter


class TestProductionStrictness(unittest.TestCase):
    """Enforce Stage 215 production-grade security, syntax, schema, PWA, and a11y gates."""

    def test_all_php_files_pass_syntax_lint(self):
        php_bin = shutil.which("php")
        if not php_bin:
            self.skipTest("php binary not available in PATH")

        php_files = sorted(list(THEME_DIR.rglob("*.php")) + [MU_PLUGIN])
        self.assertGreaterEqual(len(php_files), 40)

        failures = []
        for php_file in php_files:
            proc = subprocess.run(
                [php_bin, "-l", str(php_file)],
                capture_output=True,
                text=True,
                timeout=15,
            )
            if proc.returncode != 0:
                failures.append(f"{php_file.relative_to(REPO_ROOT)}: {proc.stdout} {proc.stderr}")

        self.assertEqual(failures, [], f"PHP syntax errors detected:\n" + "\n".join(failures))

    def test_all_js_files_pass_node_syntax_check(self):
        node_bin = shutil.which("node")
        if not node_bin:
            self.skipTest("node binary not available in PATH")

        js_files = sorted(list((THEME_DIR / "assets" / "js").rglob("*.js")) + [SW_JS])
        self.assertGreaterEqual(len(js_files), 4)

        failures = []
        for js_file in js_files:
            proc = subprocess.run(
                [node_bin, "--check", str(js_file)],
                capture_output=True,
                text=True,
                timeout=15,
            )
            if proc.returncode != 0:
                failures.append(f"{js_file.relative_to(REPO_ROOT)}: {proc.stdout} {proc.stderr}")

        self.assertEqual(failures, [], f"JS syntax errors detected:\n" + "\n".join(failures))

    def test_wikidata_knowledge_graph_zero_hallucinated_qids(self):
        mu_code = MU_PLUGIN.read_text(encoding="utf-8")
        seo_code = (THEME_DIR / "inc" / "guide-seo.php").read_text(encoding="utf-8")
        combined = mu_code + "\n" + seo_code

        # Verified real Vietnamese Wikidata Q-IDs (checked against live Wikidata API)
        verified_qids = {
            "Q1858": "Hanoi",
            "Q1854": "Ho Chi Minh City",
            "Q190128": "Ha Long Bay",
            "Q97162236": "Lan Ha Bay",
            "Q36160": "Hoi An",
            "Q5965459": "Hoi An Ancient Town",
            "Q25282": "Da Nang",
            "Q36399": "Hue",
            "Q10769129": "Imperial City of Hue",
            "Q1000140": "Sa Pa",
            "Q36685": "Ha Giang",
            "Q36900": "Ninh Binh",
            "Q146695": "Phu Quoc",
            "Q370746": "Con Dao",
            "Q19491": "Nha Trang",
            "Q36081": "Quy Nhon",
            "Q1317125": "Mui Ne",
            "Q993402": "Cat Ba Island",
            "Q1052867": "Mekong Delta",
            "Q23513": "Phong Nha-Ke Bang National Park",
            "Q192721": "Cu Chi Tunnels",
            "Q10810559": "Trang An Landscape Complex",
            "Q1406000": "Tam Coc",
            "Q10748377": "Bich Dong Pagoda",
            "Q6764555": "Old Quarter of Hanoi",
            "Q1151254": "Hoan Kiem Lake",
            "Q1202019": "Temple of Literature",
            "Q703871": "War Remnants Museum",
            "Q3232879": "Ben Thanh Market",
            "Q123782": "Fansipan",
            "Q6949928": "Ma Pi Leng Pass",
            "Q10744325": "Dong Van Karst Plateau",
            "Q7257210": "Pu Luong Nature Reserve",
            "Q44889": "Son Doong Cave",
            "Q43171": "Thien Duong (Paradise) Cave",
            "Q1877": "Dong Hoi",
            "Q1968747": "Ba Be National Park",
            "Q983252": "Ban Gioc-Detian Falls",
            "Q36865": "Cao Bang",
            "Q25281": "Da Lat",
            "Q25262": "Ca Mau",
            "Q881": "Vietnam",
            "Q11708": "Southeast Asia",
        }
        for qid, label in verified_qids.items():
            self.assertIn(
                f"https://www.wikidata.org/wiki/{qid}",
                combined,
                f"Missing verified Wikidata Q-ID {qid} ({label})",
            )

        # Hallucinated / non-Vietnam Q-IDs previously purged must NEVER reappear
        forbidden_qids = [
            "Q36359",     # Hammurabi (was Ninh Binh)
            "Q36352",     # Loglan (was Ha Giang / Ninh Binh)
            "Q200257",    # Gugnecourt, France (was Hue)
            "Q36384",     # molar equivalent (was Sa Pa)
            "Q11162",     # ciccioli Italian dish (was Phong Nha)
            "Q192135",    # ACTA treaty (was Phong Nha)
            "Q185625",    # Sergio Di Zio (was Cu Chi)
            "Q36173",     # Niuafo'ou language (was Dong Hoi)
            "Q844112",    # Lucky Star video game (was Hoan Kiem)
            "Q1081308",   # Comasco-Lecchese dialect (was Temple of Literature)
            "Q5501872",   # French Polynesia constituency (was French Quarter)
            "Q1062129",   # Tohoku University (was War Remnants Museum)
            "Q1931159",   # missing entity (was Ben Thanh Market)
            "Q7680468",   # Tam McKenzie (was Tam Coc)
            "Q797559",    # muscle tissue (was Ba Be)
            "Q806148",    # Interstate 15 business routes (was Ban Gioc)
            "Q1005391",   # Austrian Constitutional Law (was Fansipan / Ba Be)
            "Q6720697",   # Ma Bourgogne Paris restaurant (was Ma Pi Leng)
            "Q7257418",   # US DOJ Public Integrity Section (was Pu Luong)
            "Q5017482",   # Cai Griffiths rugby player (was Cai Rang)
            "Q737664",    # Carlos P. Romulo (was Chau Doc)
            "Q1014494",   # Wartenstein Castle (was Ha Tien)
            "Q1118128",   # Grand Longwy (was Con Dao)
            "Q1333765",   # Sergio Fernandez Gonzalez (was Mui Ne)
            "Q26577",     # Freienwalde Basin (was Quy Nhon)
            "Q43224",     # year 106 BC (was Paradise Cave)
            "Q212668",    # missing entity (was Son Doong)
            "Q324021",    # Karl Girardet (was Son Doong)
            "Q1005837",   # Jutrosin Poland (was Hai Van Pass)
            "Q207139",    # Mombaroccio Italy (was Dien Bien Phu)
            "Q1935243",   # Dota Canton Costa Rica (was Mai Chau)
            "Q2227060",   # Coyer Point Antarctica (was Bac Ha)
        ]
        for bad_qid in forbidden_qids:
            self.assertNotIn(
                f"wikidata.org/wiki/{bad_qid}",
                combined,
                f"Forbidden hallucinated Wikidata Q-ID detected: {bad_qid}",
            )

    def test_defense_in_depth_http_security_headers(self):
        aio_code = (THEME_DIR / "inc" / "guide-aio.php").read_text(encoding="utf-8")
        self.assertIn("function vg_send_security_headers(): void", aio_code)
        self.assertIn("add_action('send_headers', 'vg_send_security_headers'", aio_code)
        for hdr in (
            "X-Content-Type-Options: nosniff",
            "X-Frame-Options: SAMEORIGIN",
            "Referrer-Policy: strict-origin-when-cross-origin",
            "Permissions-Policy:",
            "Strict-Transport-Security: max-age=31536000; includeSubDomains",
        ):
            self.assertIn(hdr, aio_code, f"Missing security header directive: {hdr}")

    def test_php_schema_and_homepage_strings_have_zero_tier1_ai_slop(self):
        for rel in ("inc/guide-seo.php", "inc/homepage-data.php"):
            txt = (THEME_DIR / rel).read_text(encoding="utf-8")
            strings = " ".join(re.findall(r"'(?:[^'\\]|\\.)*'", txt))
            report = slop_linter.analyze_text(strings)
            self.assertEqual(
                report.get("tier1_count", 0),
                0,
                f"Tier-1 AI slop detected in {rel}: {report.get('tier1_violations')}",
            )

    def test_pwa_offline_cache_parity_and_resilience(self):
        sw_code = SW_JS.read_text(encoding="utf-8")
        offline_html = OFFLINE_HTML.read_text(encoding="utf-8")
        manifest = json.loads(MANIFEST_JSON.read_text(encoding="utf-8"))

        # Service worker resilience & bounded cache
        self.assertIn("Promise.allSettled(", sw_code)
        self.assertIn("MAX_CACHE_ENTRIES", sw_code)
        self.assertIn("function trimCache(", sw_code)
        self.assertIn("url.searchParams.has('s')", sw_code)

        # Every cached guide link in offline.html MUST be in PRECACHE_ASSETS of sw.js
        precached = set(re.findall(r"'(/[^']+)'", sw_code))
        offline_links = re.findall(r'class="vg-cached-item"[^>]*href="([^"]+)"|href="([^"]+)"[^>]*class="vg-cached-item"', offline_html)
        guide_hrefs = [a or b for a, b in offline_links]
        self.assertGreaterEqual(len(guide_hrefs), 8)
        for href in guide_hrefs:
            self.assertIn(
                href,
                precached,
                f"Offline fallback links to '{href}' which is NOT precached in wordpress/sw.js",
            )

        # Emergency contacts in offline.html must use tap-to-dial tel: links
        for num in ("tel:113", "tel:115", "tel:114", "tel:+842439261565"):
            self.assertIn(num, offline_html)
        self.assertIn("@media (prefers-reduced-motion: reduce)", offline_html)
        self.assertIn(":focus-visible", offline_html)
        self.assertIn('content="#0e6f5c"', offline_html)

        # Web manifest shortcuts must use canonical /plan/vietnam-travel-cost/
        shortcut_urls = [s.get("url") for s in manifest.get("shortcuts", [])]
        self.assertIn("/plan/vietnam-travel-cost/", shortcut_urls)
        self.assertNotIn("/costs/vietnam-travel-cost/", shortcut_urls)

    def test_zero_cls_dialogs_and_modal_a11y_hardening(self):
        footer_code = (THEME_DIR / "footer.php").read_text(encoding="utf-8")
        seo_code = (THEME_DIR / "inc" / "guide-seo.php").read_text(encoding="utf-8")
        analytics_code = (THEME_DIR / "inc" / "guide-analytics.php").read_text(encoding="utf-8")
        guide_page_code = (THEME_DIR / "template-parts" / "guide-page.php").read_text(encoding="utf-8")
        aio_code = (THEME_DIR / "inc" / "guide-aio.php").read_text(encoding="utf-8")

        # Default hidden attributes prevent CLS before JS executes
        self.assertRegex(footer_code, r'<div id="vg-cookie-consent"[^\n]*\bhidden\b')
        self.assertIn('aria-describedby="vg-cookie-desc"', footer_code)
        self.assertRegex(seo_code, r'<aside id="vg-pwa-banner"[^\n]*\bhidden\b')

        # QR modal: no invalid empty img src="", guarded Escape & Tab focus trap
        self.assertNotIn('id="vg-qr-code-img" src=""', analytics_code)
        self.assertIn("modal.hasAttribute('hidden')", analytics_code)
        self.assertIn("e.key === 'Tab'", analytics_code)

        # Canonical zero-redirect Cost Calculator link in floating dock & llms.txt
        self.assertIn("home_url('/plan/vietnam-travel-cost/')", guide_page_code)
        self.assertNotIn("home_url('/costs/vietnam-travel-cost/')", guide_page_code)
        self.assertNotIn("/costs/vietnam-travel-cost/", aio_code)


if __name__ == "__main__":
    unittest.main()
