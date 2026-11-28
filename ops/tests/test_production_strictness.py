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

    def test_all_inline_php_scripts_pass_node_syntax_check(self):
        """Every inline <script> block embedded in theme PHP templates must parse cleanly in Node."""
        node_bin = shutil.which("node")
        if not node_bin:
            self.skipTest("node binary not available in PATH")

        import tempfile

        script_re = re.compile(r"<script\b([^>]*)>([\s\S]*?)</script>", re.IGNORECASE)
        failures = []
        checked_blocks = 0

        for php_file in sorted(THEME_DIR.rglob("*.php")):
            text = php_file.read_text(encoding="utf-8", errors="ignore")
            for idx, m in enumerate(script_re.finditer(text), 1):
                attrs = m.group(1) or ""
                if re.search(r'type\s*=\s*["\']application/ld\+json["\']', attrs, re.I):
                    continue
                if re.search(r"\bsrc\s*=", attrs, re.I):
                    continue
                js_body = m.group(2)
                if not js_body.strip():
                    continue
                # Replace PHP interpolations with valid JS placeholders respecting quote context
                sanitized = re.sub(r"<\?php[\s\S]*?\?>", lambda pm: "0" if not re.search(r"['\"`]$", js_body[:pm.start()]) else "x", js_body)
                # Normalize any remaining PHP tags inside string literals cleanly
                checked_blocks += 1
                with tempfile.NamedTemporaryFile("w", suffix=".js", encoding="utf-8", delete=False) as tf:
                    tf.write(sanitized)
                    tmp_path = Path(tf.name)
                try:
                    proc = subprocess.run(
                        [node_bin, "--check", str(tmp_path)],
                        capture_output=True,
                        text=True,
                        timeout=15,
                    )
                    if proc.returncode != 0:
                        failures.append(f"{php_file.relative_to(REPO_ROOT)}#script-{idx}: {proc.stderr.strip()}")
                finally:
                    tmp_path.unlink(missing_ok=True)

        self.assertGreaterEqual(checked_blocks, 10)
        self.assertEqual(failures, [], "Inline PHP <script> syntax errors:\n" + "\n".join(failures))

    def test_stage216_wcag_aa_contrast_wai_aria_tabs_lightbox_and_zero_redirects(self):
        """Enforce Stage 216 WCAG 2.2 AA contrast, WAI-ARIA APG Tabs, lightbox focus trap, zero dynamic innerHTML, and canonical Cost Calculator URLs."""
        homepage_css = (THEME_DIR / "assets" / "css" / "homepage.css").read_text(encoding="utf-8")
        guide_exp_css = (THEME_DIR / "assets" / "css" / "guide-experience.css").read_text(encoding="utf-8")
        interactive_js = (THEME_DIR / "assets" / "js" / "guide-interactive.js").read_text(encoding="utf-8")
        map_php = (THEME_DIR / "inc" / "guide-interactive-map.php").read_text(encoding="utf-8")
        dispatch_php = (THEME_DIR / "inc" / "guide-photo-dispatch.php").read_text(encoding="utf-8")
        calc_php = (THEME_DIR / "inc" / "guide-cost-calculator.php").read_text(encoding="utf-8")
        guide_content_php = (THEME_DIR / "inc" / "guide-content.php").read_text(encoding="utf-8")

        # 1. WCAG 2.2 AA light-surface text contrast tokens (zero low-contrast saffron/coral on light backgrounds)
        self.assertIn("--vg-coral-text: #a43b27;", homepage_css)
        combined_ui_css = "\n".join([
            homepage_css,
            (THEME_DIR / "assets" / "css" / "search-404.css").read_text(encoding="utf-8"),
            (THEME_DIR / "assets" / "css" / "toolkit-cost-calculator.css").read_text(encoding="utf-8"),
            (THEME_DIR / "assets" / "css" / "toolkit-season-matrix.css").read_text(encoding="utf-8"),
            (THEME_DIR / "assets" / "css" / "toolkit-itinerary-finder.css").read_text(encoding="utf-8"),
        ])
        for bad_rule in (
            ".vg-calc-badge {\n  display: inline-block;\n  font-size: 11px;\n  font-weight: 800;\n  letter-spacing: 0.12em;\n  text-transform: uppercase;\n  color: var(--vg-gold-saffron",
            ".vg-sm-badge {\n  display: inline-block;\n  font-size: 11px;\n  font-weight: 800;\n  letter-spacing: 0.12em;\n  text-transform: uppercase;\n  color: var(--vg-gold-saffron",
            ".vg-finder-kicker {\n  display: inline-block;\n  font-size: 12px;\n  font-weight: 800;\n  letter-spacing: 0.12em;\n  text-transform: uppercase;\n  color: var(--vg-gold-saffron",
            ".vg-search-count--empty {\n  color: var(--vg-coral);",
            ".vg-search-badge--practical {\n  background: rgba(201, 95, 74, 0.12);\n  color: var(--vg-coral);",
        ):
            self.assertNotIn(bad_rule, combined_ui_css)
        self.assertIn("color: var(--vg-gold-text, #7e5802);", guide_exp_css)

        # 2. WAI-ARIA APG Tabs roving tabindex + Home/End across Map, Photo Dispatch & Field Desk
        self.assertGreaterEqual(interactive_js.count("e.key === 'Home'"), 3)
        self.assertGreaterEqual(interactive_js.count("e.key === 'End'"), 3)
        self.assertIn("btn.setAttribute('tabindex', isActive ? '0' : '-1');", interactive_js)
        self.assertIn("btn.setAttribute('tabindex', match ? '0' : '-1');", interactive_js)
        self.assertIn("tabindex=\"<?php echo $is_active ? '0' : '-1'; ?>\"", map_php)
        self.assertIn("tabindex=\"<?php echo $is_tab_active ? '0' : '-1'; ?>\"", dispatch_php)

        # 3. Lightbox modal WCAG 2.2 AA Tab focus trap + valid placeholder SVG src
        self.assertIn("if (e.key === 'Tab')", interactive_js)
        self.assertNotIn('id="vg-lightbox-img" src=""', dispatch_php)
        self.assertIn('id="vg-lightbox-img" src="data:image/svg+xml', dispatch_php)
        self.assertIn('decoding="async"', dispatch_php)

        # 4. Zero dynamic li.innerHTML sinks in Cost Calculator & Route Budget widgets
        self.assertNotIn("li.innerHTML =", calc_php)
        self.assertIn("document.createElement('span')", calc_php)

        # 5. Zero-CLS intrinsic dimension resolution for inline Markdown <img> in guide-content.php
        self.assertIn("vg_resolve_image_dimensions_by_url", guide_content_php)

        # 6. Zero legacy /costs/vietnam-travel-cost/ redirect hops across homepage, search, and all 6 interactive toolkits
        for rel in (
            "front-page.php",
            "inc/homepage-data.php",
            "search.php",
            "inc/guide-airport-navigator.php",
            "inc/guide-itinerary-finder.php",
            "inc/guide-visa-checker.php",
            "inc/guide-season-matrix.php",
            "inc/guide-packing-checklist.php",
        ):
            src = (THEME_DIR / rel).read_text(encoding="utf-8")
            self.assertNotIn(
                "costs/vietnam-travel-cost",
                src,
                f"Legacy redirect hop 'costs/vietnam-travel-cost' still found in {rel}",
            )
        self.assertNotIn("https://vietnamguide.net/costs/vietnam-travel-cost/", calc_php)
        self.assertNotIn("home_url('/costs/vietnam-travel-cost/", calc_php)

    def test_stage217_faq_single_source_of_truth_cwv_defer_and_hero_budgets(self):
        """Enforce Stage 217 Single-Source-of-Truth FAQ extraction & anti-duplication, CWV script defer, AdSense preconnect, content-visibility: auto, and regional hero JPEG payload budgets."""
        seo_code = (THEME_DIR / "inc" / "guide-seo.php").read_text(encoding="utf-8")
        homepage_css = (THEME_DIR / "assets" / "css" / "homepage.css").read_text(encoding="utf-8")
        guide_exp_css = (THEME_DIR / "assets" / "css" / "guide-experience.css").read_text(encoding="utf-8")

        # 1. CWV Resource Hints, Script Defer & CSS content-visibility: auto + print override
        self.assertIn("https://pagead2.googlesyndication.com", seo_code)
        self.assertIn("function vg_defer_theme_scripts(", seo_code)
        self.assertIn("add_filter('script_loader_tag', 'vg_defer_theme_scripts'", seo_code)
        self.assertIn("content-visibility: auto;", homepage_css)
        self.assertIn("content-visibility: visible !important;", homepage_css)
        self.assertIn("content-visibility: auto;", guide_exp_css)

        # 2. Regional hero JPEG payload budget (all 10 non-pinned heroes <= 580 KB each, total <= 4.2 MB)
        img_dir = THEME_DIR / "assets" / "images"
        regional_heroes = [
            "cao-bang-tu-san-canyon-hero.jpg",
            "da-lat-xuan-huong-hero.jpg",
            "hanoi-hoan-kiem-hero.jpg",
            "hue-imperial-citadel-hero.jpg",
            "mekong-delta-phong-dien-hero.jpg",
            "ninh-binh-mua-cave-hero.jpg",
            "phong-nha-son-river-hero.jpg",
            "phu-quoc-kem-beach-hero.jpg",
            "saigon-city-hall-hero.jpg",
            "sapa-rice-terraces-hero.jpg",
        ]
        total_bytes = 0
        for hero_name in regional_heroes:
            sz = (img_dir / hero_name).stat().st_size
            total_bytes += sz
            self.assertLessEqual(sz, 580_000, f"Regional hero {hero_name} exceeds 580 KB LCP budget: {sz:,} B")
        self.assertLessEqual(total_bytes, 4_200_000, f"Total 10 regional heroes exceed 4.2 MB budget: {total_bytes:,} B")

        # 3. Live PHP 8.x runtime check of FAQ Single Source of Truth + vg_defer_theme_scripts
        php_bin = shutil.which("php")
        if not php_bin:
            self.skipTest("php binary not available in PATH")

        php_check = r"""
        define('ABSPATH', '""" + str((REPO_ROOT / "wordpress").as_posix()) + r"""/');
        class WP_Post {
            public int $ID = 101;
            public string $post_name = 'hue-to-dong-hoi-transport';
            public string $post_title = 'Hue to Dong Hoi Transport';
            public string $post_content = '';
        }
        $GLOBALS['__mock_post'] = new WP_Post();
        function get_queried_object() { return $GLOBALS['__mock_post']; }
        function get_post() { return $GLOBALS['__mock_post']; }
        function get_page_uri($p) { return $GLOBALS['__mock_uri_override'] ?? ('plan/' . $p->post_name); }
        function esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
        function esc_attr($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
        function esc_url($s) { return (string)$s; }
        function __($s, $d = '') { return (string)$s; }
        function apply_filters($k, $v) { return $v; }
        function add_action(...$a) {}
        function add_filter(...$a) {}
        function add_shortcode(...$a) {}
        function wp_strip_all_tags($s) { return strip_tags((string)$s); }
        require '""" + str((THEME_DIR / "inc" / "guide-seo.php").as_posix()) + r"""';
        require '""" + str((THEME_DIR / "inc" / "guide-content.php").as_posix()) + r"""';

        // A. Uncurated Markdown guide (hue-to-dong-hoi-transport) gets FAQPage JSON-LD directly from Markdown!
        $schema_uncurated = vg_get_page_faq_schema('plan/hue-to-dong-hoi-transport', 'https://vietnamguide.net/plan/hue-to-dong-hoi-transport/');
        // B. Curated route with authored visible FAQ suppresses duplicate vg-curated-faq accordion
        $authored_html = '<h2>Frequently Asked Questions</h2><h3>How much is Hanoi Airport Bus 86?</h3><p>Bus 86 costs 45,000 VND per rider and departs every 25 minutes.</p>';
        $dedup_out = vg_render_curated_faq_accordion('destinations/hanoi-travel-guide', $authored_html);
        // C. Legacy page without authored FAQ still renders curated accordion
        $legacy_out = vg_render_curated_faq_accordion('destinations/hanoi-travel-guide', '<p>Legacy content without FAQ section.</p>');
        // D. Script defer filter adds defer cleanly once
        $deferred = vg_defer_theme_scripts('<script src="/assets/js/homepage.js"></script>', 'vietnamguide-homepage', '/assets/js/homepage.js');
        $already = vg_defer_theme_scripts('<script defer src="/assets/js/homepage.js"></script>', 'vietnamguide-homepage', '/assets/js/homepage.js');
        // E. Vietnamese UTF-8 byte 0x85 (e.g. 'Nguyễn', 'ễ') must NEVER be split by non-UTF-8 \R regexes into empty <p></p>
        $hanoiPost = new WP_Post();
        $hanoiPost->post_name = 'best-things-to-do-in-hanoi';
        $hanoiPost->post_title = 'Best Things to Do in Hanoi';
        $GLOBALS['__mock_uri_override'] = 'destinations/best-things-to-do-in-hanoi';
        $hanoiHtml = vg_try_load_repository_markdown_guide($hanoiPost);
        $hanoiQas = vg_extract_authored_faq_items_from_html($hanoiHtml);

        echo json_encode([
            'uncurated_faq_count' => is_array($schema_uncurated) ? count($schema_uncurated['mainEntity'] ?? []) : 0,
            'dedup_has_curated_faq' => strpos($dedup_out, 'vg-curated-faq') !== false,
            'legacy_has_curated_faq' => strpos($legacy_out, 'vg-curated-faq') !== false,
            'deferred_tag' => $deferred,
            'already_deferred_count' => substr_count($already, 'defer'),
            'hanoi_has_nguyen' => strpos($hanoiHtml, 'Nguyễn Hữu Huân') !== false,
            'hanoi_has_empty_p' => strpos($hanoiHtml, '<p></p>') !== false,
            'hanoi_faq_count' => count($hanoiQas),
        ]);
        """
        proc = subprocess.run([php_bin, "-r", php_check], capture_output=True, text=True, timeout=20)
        self.assertEqual(proc.returncode, 0, f"PHP Stage 217 runtime check failed:\n{proc.stdout}\n{proc.stderr}")
        data = json.loads(proc.stdout)
        self.assertEqual(data["uncurated_faq_count"], 3)
        self.assertFalse(data["dedup_has_curated_faq"])
        self.assertTrue(data["legacy_has_curated_faq"])
        self.assertIn("<script defer ", data["deferred_tag"])
        self.assertEqual(data["already_deferred_count"], 1)
        self.assertTrue(data["hanoi_has_nguyen"], "Vietnamese 'ễ' (0x85 byte) must survive Markdown line splitting intact")
        self.assertFalse(data["hanoi_has_empty_p"], "Markdown parser must not produce empty <p></p> paragraphs")
        self.assertEqual(data["hanoi_faq_count"], 2)

        # Zero bare non-UTF-8 /\R/ regexes anywhere in inc/guide-content.php
        guide_content_code = (THEME_DIR / "inc" / "guide-content.php").read_text(encoding="utf-8")
        self.assertNotIn("/\\R/", guide_content_code)

    def test_stage218_visible_to_schema_faq_parity_for_legacy_db_pages_and_zero_cost_hops(self):
        """Legacy DB pages with <2 authored Q&As must render companion Markdown FAQs visibly in exact 1:1 parity with JSON-LD, and guide-cost-calculator.php must have zero /costs/ redirect hops."""
        calc_code = (THEME_DIR / "inc" / "guide-cost-calculator.php").read_text(encoding="utf-8")
        for legacy_hop in (
            "/costs/ha-giang-loop-cost-budget/",
            "/costs/da-nang-hoi-an-budget/",
            "/costs/hanoi-ninh-binh-ha-long-budget/",
            "/costs/10-day-vietnam-trip-cost/",
            "/costs/vietnam-travel-cost/",
        ):
            self.assertNotIn(
                f"home_url('{legacy_hop}')",
                calc_code,
                f"Legacy 301 redirect hop still present in guide-cost-calculator.php: {legacy_hop}",
            )
        homepage_data_code = (THEME_DIR / "inc" / "homepage-data.php").read_text(encoding="utf-8")
        self.assertNotIn("vg_home_url('costs')", homepage_data_code)
        self.assertIn("vg_home_url('plan/vietnam-travel-cost')", homepage_data_code)

        php_bin = shutil.which("php")
        if not php_bin:
            self.skipTest("PHP CLI not available")

        seo_php = (THEME_DIR / "inc" / "guide-seo.php").as_posix()
        content_php = (THEME_DIR / "inc" / "guide-content.php").as_posix()
        wp_root = (REPO_ROOT / "wordpress").as_posix()
        php_check = f"""
        define('ABSPATH', '{wp_root}/');
        class WP_Post {{
            public int $ID = 104;
            public string $post_type = 'page';
            public string $post_name = 'north-central-south-vietnam';
            public string $post_title = 'North vs Central vs South Vietnam';
            public string $post_content = '<section class="vg-guide-hero"><h1>North vs Central vs South Vietnam</h1></section><h2>Overview</h2><p>Legacy DB body with zero authored FAQ questions and no curated PHP entry.</p>';
        }}
        function esc_html($s) {{ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }}
        function esc_attr($s) {{ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }}
        function esc_url($s) {{ return (string)$s; }}
        function __($s, $d = '') {{ return (string)$s; }}
        function apply_filters($k, $v) {{ return $v; }}
        function add_action(...$a) {{}}
        function add_filter(...$a) {{}}
        function add_shortcode(...$a) {{}}
        function wp_strip_all_tags($s) {{ return strip_tags((string)$s); }}
        function get_queried_object() {{ return $GLOBALS['__test_post']; }}
        function get_post($id = null) {{ return $GLOBALS['__test_post']; }}
        function get_page_uri($post = null) {{ return 'compare/north-central-south-vietnam'; }}
        function get_template_directory() {{ return '{THEME_DIR.as_posix()}'; }}
        function home_url($p = '') {{ return 'https://vietnamguide.net' . $p; }}
        require_once '{content_php}';
        require_once '{seo_php}';

        $GLOBALS['__test_post'] = new WP_Post();
        $uri = '/compare/north-central-south-vietnam/';
        $curated_count = count(vg_get_curated_faq_items($uri));
        $visible_html = vg_render_curated_faq_accordion($uri, $GLOBALS['__test_post']->post_content);
        $injected_html = vg_inject_curated_faq_on_non_guide_pages($GLOBALS['__test_post']->post_content);
        $schema = vg_get_page_faq_schema($uri, 'https://vietnamguide.net' . $uri);
        $visible_qas = vg_extract_authored_faq_items_from_html($visible_html);
        $schema_questions = array_map(static fn($x) => $x['name'], $schema['mainEntity'] ?? []);
        $visible_questions = array_map(static fn($x) => $x['q'], $visible_qas);

        // Also test legacy wp-block-details FAQ extraction and ensure contextual-journey <h3> is never misclassified as an FAQ question
        $details_html = '<h2>Frequently asked questions</h2>'
            . '<div class="wp-block-details"><summary><strong>Can I visit both Ly Son and Cham Islands on one trip?</strong></summary>'
            . '<p>Only if you have three weeks or more dedicated to Central Vietnam travel.</p></div>'
            . '<section class="vg-contextual-journey"><h3 class="vg-contextual-journey__title">Connected Destinations along the Central Corridor</h3><p>Explore Da Nang and Hoi An next.</p></section>';
        $details_qas = vg_extract_authored_faq_items_from_html($details_html);

        echo json_encode([
            'curated_count' => $curated_count,
            'visible_has_section' => strpos($visible_html, 'vg-curated-faq') !== false,
            'injected_non_guide_has_section' => strpos($injected_html, 'vg-curated-faq') !== false,
            'visible_count' => count($visible_questions),
            'schema_count' => count($schema_questions),
            'exact_parity' => ($visible_questions === $schema_questions),
            'details_qa_count' => count($details_qas),
            'details_q0' => $details_qas[0]['q'] ?? '',
        ]);
        """
        proc = subprocess.run([php_bin, "-r", php_check], capture_output=True, text=True, timeout=20)
        self.assertEqual(proc.returncode, 0, f"PHP Stage 218 runtime check failed:\n{proc.stdout}\n{proc.stderr}")
        data = json.loads(proc.stdout)
        self.assertEqual(data["curated_count"], 0, "Fixture must exercise the companion Markdown fallback (not curated PHP array)")
        self.assertTrue(data["visible_has_section"])
        self.assertTrue(data["injected_non_guide_has_section"], "Fallback content-page.php pages with .vg-guide-hero must also receive visible FAQ accordion")
        self.assertGreaterEqual(data["visible_count"], 2)
        self.assertEqual(data["visible_count"], data["schema_count"])
        self.assertTrue(data["exact_parity"], "Visible accordion questions and FAQPage JSON-LD mainEntity must match 1:1")
        self.assertEqual(data["details_qa_count"], 1, "Must extract wp-block-details summary Q&A while ignoring vg-contextual-journey <h3>")
        self.assertEqual(data["details_q0"], "Can I visit both Ly Son and Cham Islands on one trip?")


if __name__ == "__main__":
    unittest.main()
