# -*- coding: utf-8 -*-
"""
Playwright Headless Chromium E2E Smoke Tests for VietnamGuide (Phase P2).
Verifies real browser DOM, CSS layout, keyboard accessibility, zero console errors,
and interactive state transitions for:
1. [vg_interactive_map] (guide-interactive-map.php + guide-interactive.css + guide-interactive.js)
2. [vg_photo_dispatch] + Lightbox Modal (guide-photo-dispatch.php + guide-interactive.css + guide-interactive.js)
3. Guide Shell Experience (Sticky Reading Spine TOC, Decision Table Scroll Wrapper, Reading Progress)
"""

from pathlib import Path
import subprocess
import unittest

try:
    from playwright.sync_api import sync_playwright
    HAS_PLAYWRIGHT = True
except ImportError:
    HAS_PLAYWRIGHT = False

REPO_ROOT = Path(__file__).resolve().parents[2]
THEME_ROOT = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium'
ASSETS_DIR = THEME_ROOT / 'assets'


def _render_php_component(php_snippet: str) -> str:
    bootstrap = r"""
    define('ABSPATH', 1);
    function add_shortcode($tag, $cb) {}
    function add_action($tag, $cb, $p = 10) {}
    function add_filter($tag, $cb, $p = 10, $accepted_args = 1) {}
    function selected($selected, $current = true, $echo = true) { if ($selected === $current) { if ($echo) { echo ' selected="selected"'; } return ' selected="selected"'; } return ''; }
    function sanitize_key($k) { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$k)); }
    function shortcode_atts($pairs, $atts, $shortcode = '') {
        $out = [];
        foreach ($pairs as $k => $def) {
            $out[$k] = array_key_exists($k, (array)$atts) ? $atts[$k] : $def;
        }
        return $out;
    }
    function get_template_directory_uri() { return 'https://vietnamguide.net/wp-content/themes/vietnamguide-premium'; }
    function esc_url($u) { return htmlspecialchars((string)$u, ENT_QUOTES, 'UTF-8'); }
    function esc_attr($a) { return htmlspecialchars((string)$a, ENT_QUOTES, 'UTF-8'); }
    function esc_attr_e($a, $d = '') { echo htmlspecialchars((string)$a, ENT_QUOTES, 'UTF-8'); }
    function esc_html($h) { return htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8'); }
    function esc_html__($h, $d = '') { return htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8'); }
    function esc_html_e($h, $d = '') { echo htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8'); }
    function __($h, $d = '') { return (string)$h; }
    function home_url($p = '') { return 'https://vietnamguide.net/' . ltrim((string)$p, '/'); }
    function vg_home_url($p = '') { return 'https://vietnamguide.net/' . ltrim((string)$p, '/'); }
    function esc_attr__($a, $d = '') { return htmlspecialchars((string)$a, ENT_QUOTES, 'UTF-8'); }
    function wp_kses($s, $allowed = []) { return (string)$s; }
    function wp_kses_post($s) { return (string)$s; }
    function wp_strip_all_tags($s) { return strip_tags((string)$s); }
    function wp_json_encode($data, $flags = 0, $depth = 512) { return json_encode($data, $flags, $depth); }
    """
    res = subprocess.run(
        ['php', '-r', bootstrap + "\n" + php_snippet],
        cwd=str(REPO_ROOT),
        capture_output=True,
        text=True,
        encoding='utf-8',
    )
    if res.returncode != 0:
        raise RuntimeError(f"PHP component render failed:\n{res.stdout}\n{res.stderr}")
    return res.stdout


class TestPlaywrightE2ESmoke(unittest.TestCase):

    @classmethod
    def setUpClass(cls):
        if not HAS_PLAYWRIGHT:
            raise unittest.SkipTest("Playwright Python package is not installed")
        try:
            cls._pw = sync_playwright().start()
            cls._browser = cls._pw.chromium.launch(headless=True)
        except Exception as exc:
            if hasattr(cls, '_pw') and cls._pw:
                cls._pw.stop()
            raise unittest.SkipTest(f"Playwright Chromium browser binary unavailable: {exc}")

        cls.homepage_css = (ASSETS_DIR / 'css' / 'homepage.css').read_text(encoding='utf-8')
        cls.interactive_css = (ASSETS_DIR / 'css' / 'guide-interactive.css').read_text(encoding='utf-8')
        cls.guide_css = (ASSETS_DIR / 'css' / 'guide-experience.css').read_text(encoding='utf-8')
        cls.interactive_js = (ASSETS_DIR / 'js' / 'guide-interactive.js').read_text(encoding='utf-8')
        cls.guide_js = (ASSETS_DIR / 'js' / 'guide-experience.js').read_text(encoding='utf-8')

    @classmethod
    def tearDownClass(cls):
        if hasattr(cls, '_browser') and cls._browser:
            cls._browser.close()
        if hasattr(cls, '_pw') and cls._pw:
            cls._pw.stop()

    def test_e2e_interactive_map_corridor_switching_and_keyboard_a11y(self):
        """Playwright E2E: [vg_interactive_map] switches corridors via tabs and keyboard with 0 console errors."""
        map_html = _render_php_component(
            "require 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-interactive-map.php';\n"
            "echo vg_interactive_map_shortcode();\n"
        )
        # Strip inline script so we test the external guide-interactive.js bundle directly
        map_html_clean = map_html.split('<script>')[0] + '</section>'

        full_doc = f"""<!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <style>{self.homepage_css}</style>
            <style>{self.interactive_css}</style>
        </head>
        <body>
            {map_html_clean}
            <script>{self.interactive_js}</script>
        </body>
        </html>"""

        page = self._browser.new_page()
        console_errors = []
        page_errors = []
        page.on("console", lambda msg: console_errors.append(msg.text) if msg.type == "error" else None)
        page.on("pageerror", lambda err: page_errors.append(str(err)))

        try:
            page.set_content(full_doc, wait_until="domcontentloaded")

            # Verify default corridor is central-heritage
            self.assertEqual(
                page.locator('#vg-tab-central-heritage').get_attribute('aria-selected'),
                'true',
            )
            self.assertTrue(page.locator('#vg-dossier-central-heritage').is_visible())
            self.assertFalse(page.locator('#vg-dossier-northern-highlands').is_visible())

            # Click northern-highlands tab button
            page.locator('#vg-tab-northern-highlands').click()
            self.assertEqual(
                page.locator('#vg-tab-northern-highlands').get_attribute('aria-selected'),
                'true',
            )
            self.assertEqual(
                page.locator('#vg-tab-central-heritage').get_attribute('aria-selected'),
                'false',
            )
            self.assertTrue(page.locator('#vg-dossier-northern-highlands').is_visible())
            self.assertFalse(page.locator('#vg-dossier-central-heritage').is_visible())
            self.assertEqual(
                page.locator('[data-corridor-pin="northern-highlands"] .vg-map-pin').get_attribute('fill'),
                '#C99446',
            )

            # Keyboard navigation on SVG pin for southern-metropolis
            southern_pin = page.locator('[data-corridor-pin="southern-metropolis"]')
            self.assertEqual(southern_pin.get_attribute('role'), 'button')
            self.assertEqual(southern_pin.get_attribute('tabindex'), '0')
            southern_pin.focus()
            page.keyboard.press('Enter')
            self.assertTrue(page.locator('#vg-dossier-southern-metropolis').is_visible())
            self.assertEqual(
                page.locator('#vg-tab-southern-metropolis').get_attribute('aria-selected'),
                'true',
            )

            self.assertEqual(console_errors, [])
            self.assertEqual(page_errors, [])
        finally:
            page.close()

    def test_e2e_photo_dispatch_tabs_and_lightbox_escape_modal(self):
        """Playwright E2E: [vg_photo_dispatch] switches location tabs and opens/closes Lightbox via Escape key."""
        dispatch_html = _render_php_component(
            "require 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-photo-dispatch.php';\n"
            "echo vg_render_photo_dispatch(['slug' => 'ha-long-dawn', 'layout' => 'standard']);\n"
            "vg_render_photo_dispatch_lightbox_template();\n"
        )
        dispatch_html_clean = dispatch_html.split('<script>')[0]

        full_doc = f"""<!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <style>{self.homepage_css}</style>
            <style>{self.interactive_css}</style>
        </head>
        <body>
            {dispatch_html_clean}
            <script>{self.interactive_js}</script>
        </body>
        </html>"""

        page = self._browser.new_page()
        console_errors = []
        page_errors = []
        page.on("console", lambda msg: console_errors.append(msg.text) if msg.type == "error" else None)
        page.on("pageerror", lambda err: page_errors.append(str(err)))

        try:
            page.set_content(full_doc, wait_until="domcontentloaded")

            # Verify default tab ha-long-dawn is active
            self.assertTrue(page.locator('#vg-dispatch-panel-ha-long-dawn').is_visible())
            self.assertFalse(page.locator('#vg-dispatch-panel-hoi-an-dusk').is_visible())

            # Switch to hoi-an-dusk tab
            page.locator('#vg-dispatch-tab-hoi-an-dusk').click()
            self.assertTrue(page.locator('#vg-dispatch-panel-hoi-an-dusk').is_visible())
            self.assertFalse(page.locator('#vg-dispatch-panel-ha-long-dawn').is_visible())
            self.assertEqual(
                page.locator('#vg-dispatch-tab-hoi-an-dusk').get_attribute('aria-selected'),
                'true',
            )

            # Open Lightbox modal from active Hoi An panel
            page.locator('#vg-dispatch-panel-hoi-an-dusk .vg-dispatch-expand-btn').click()
            modal = page.locator('#vg-dispatch-modal')
            self.assertIn('is-active', modal.get_attribute('class') or '')
            self.assertIsNone(modal.get_attribute('hidden'))
            self.assertIsNotNone(modal.bounding_box())
            body_overflow = page.evaluate("() => document.body.style.overflow")
            self.assertEqual(body_overflow, 'hidden')

            # Press Escape key to close Lightbox modal
            page.keyboard.press('Escape')
            self.assertNotIn('is-active', modal.get_attribute('class') or '')
            self.assertIsNotNone(modal.get_attribute('hidden'))
            body_overflow_after = page.evaluate("() => document.body.style.overflow")
            self.assertEqual(body_overflow_after, '')

            self.assertEqual(console_errors, [])
            self.assertEqual(page_errors, [])
        finally:
            page.close()

    def test_e2e_guide_shell_sticky_toc_and_decision_table_wrapper(self):
        """Playwright E2E: Guide Shell wraps legacy decision tables and initializes Sticky TOC & reading progress."""
        guide_doc = f"""<!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <style>{self.homepage_css}</style>
            <style>{self.guide_css}</style>
        </head>
        <body>
            <div id="vg-reading-progress" class="vg-reading-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
            <article class="vg-guide-experience" data-vg-guide data-vg-guide-type="destination">
                <div class="vg-guide-shell">
                    <aside class="vg-guide-spine">
                        <nav class="vg-guide-spine__toc" aria-label="Table of contents">
                            <ol class="vg-guide-toc" data-vg-toc>
                                <li><a href="#when-to-go">When to Go</a></li>
                                <li><a href="#transport-costs">Transport &amp; Costs</a></li>
                            </ol>
                        </nav>
                    </aside>
                    <div class="vg-guide-article" style="padding-top: 320px; min-height: 2800px;">
                        <h2 id="when-to-go">When to Go</h2>
                        <p style="min-height: 900px;">October through April delivers dry conditions across Northern Vietnam.</p>
                        <table class="vg-decision-table">
                            <thead><tr><th>Corridor</th><th>Transit</th></tr></thead>
                            <tbody><tr><td>Hanoi to Ninh Binh</td><td>2.0 hours rail</td></tr></tbody>
                        </table>
                        <h2 id="transport-costs">Transport &amp; Costs</h2>
                        <p style="min-height: 1200px;">Expressway limousines depart hourly for 250,000 VND per seat.</p>
                    </div>
                </div>
            </article>
            <script>{self.guide_js}</script>
        </body>
        </html>"""

        page = self._browser.new_page()
        console_errors = []
        page_errors = []
        page.on("console", lambda msg: console_errors.append(msg.text) if msg.type == "error" else None)
        page.on("pageerror", lambda err: page_errors.append(str(err)))

        try:
            page.set_content(guide_doc, wait_until="domcontentloaded")

            # Verify legacy decision table is automatically wrapped in .vg-decision-table__scroll with tabindex="0"
            scroll_wrapper = page.locator('.vg-decision-table__scroll')
            self.assertEqual(scroll_wrapper.count(), 1)
            self.assertEqual(scroll_wrapper.get_attribute('tabindex'), '0')

            # Scroll #when-to-go into the 20%..32% IntersectionObserver band (25% of viewport height)
            page.evaluate(
                "() => {\n"
                "  const el = document.getElementById('when-to-go');\n"
                "  const top = el.getBoundingClientRect().top + window.scrollY;\n"
                "  window.scrollTo(0, Math.max(0, top - window.innerHeight * 0.25));\n"
                "}"
            )

            # Verify IntersectionObserver marks the visible section (#when-to-go) active in Sticky TOC
            first_toc_link = page.locator('[data-vg-toc] a[href="#when-to-go"]')
            page.wait_for_function(
                "() => document.querySelector('[data-vg-toc] a[href=\"#when-to-go\"]').getAttribute('aria-current') === 'location'",
                timeout=3000,
            )
            self.assertEqual(first_toc_link.get_attribute('aria-current'), 'location')
            self.assertIn('is-active', first_toc_link.get_attribute('class') or '')

            # Verify --vg-guide-progress custom property and #vg-reading-progress bar are updated
            progress_val = page.evaluate(
                "() => document.querySelector('[data-vg-guide]').style.getPropertyValue('--vg-guide-progress')"
            )
            self.assertRegex(progress_val, r'^\d+\.\d{2}$')
            bar_width = page.evaluate("() => document.getElementById('vg-reading-progress').style.width")
            self.assertTrue(bar_width.endswith('%'), f"Expected percentage bar width, got {bar_width!r}")

            self.assertEqual(console_errors, [])
            self.assertEqual(page_errors, [])
        finally:
            page.close()

    def test_e2e_visa_checker_interactive_calculations(self):
        """Playwright E2E: [vg_visa_checker] computes exemption vs e-visa fees on nationality & slider change."""
        vc_html = _render_php_component(
            "require 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php';\n"
            "echo vg_render_visa_checker_html();\n"
        )

        full_doc = f"""<!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <style>{self.homepage_css}</style>
            <style>{self.interactive_css}</style>
        </head>
        <body>
            {vc_html}
        </body>
        </html>"""

        page = self._browser.new_page()
        console_errors = []
        page_errors = []
        page.on("console", lambda msg: console_errors.append(msg.text) if msg.type == "error" else None)
        page.on("pageerror", lambda err: page_errors.append(str(err)))

        try:
            page.set_content(full_doc, wait_until="domcontentloaded")

            # Default nationality is UK (GB) for 14 days -> 45-day exemption eligible ($0 USD)
            verdict_card = page.locator('#vg-vc-verdict-card')
            cost_amount = page.locator('#vg-vc-cost-amount')
            self.assertIn('status-exempt', verdict_card.get_attribute('class') or '')
            self.assertEqual(cost_amount.inner_text().strip(), '$0 USD')

            # Select US via quick chip -> requires 90-day E-Visa ($25 USD)
            page.locator('.vg-vc-chip[data-country="US"]').click()
            self.assertIn('status-evisa', verdict_card.get_attribute('class') or '')
            self.assertEqual(cost_amount.inner_text().strip(), '$25 USD')

            # Select UK again and change stay duration to 60 days (> 45 days exemption cap)
            page.locator('.vg-vc-chip[data-country="GB"]').click()
            slider = page.locator('#vg-vc-duration-slider')
            slider.fill('60')
            slider.dispatch_event('input')
            slider.dispatch_event('change')

            # Should transition to status-warning / E-Visa required because stay exceeds 45 days
            self.assertIn('status-warning', verdict_card.get_attribute('class') or '')
            self.assertEqual(cost_amount.inner_text().strip(), '$25 USD')
            self.assertIn('EXCEEDS 45 DAYS', page.locator('#vg-vc-status-text').inner_text().upper())

            self.assertEqual(console_errors, [])
            self.assertEqual(page_errors, [])
        finally:
            page.close()

    def test_e2e_cost_calculator_interactive_calculations(self):
        """Playwright E2E: [vg_cost_calculator] recalculates budget on duration and travel style change."""
        calc_html = _render_php_component(
            "require 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php';\n"
            "echo vg_render_cost_calculator_html();\n"
        )
        full_doc = f"""<!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <style>{self.homepage_css}</style>
            <style>{self.interactive_css}</style>
        </head>
        <body>
            {calc_html}
        </body>
        </html>"""

        page = self._browser.new_page()
        console_errors = []
        page_errors = []
        page.on("console", lambda msg: console_errors.append(msg.text) if msg.type == "error" else None)
        page.on("pageerror", lambda err: page_errors.append(str(err)))

        try:
            page.set_content(full_doc, wait_until="domcontentloaded")

            total_el = page.locator('#vg-calc-total-display')
            days_el = page.locator('#vg-calc-days-display')
            self.assertIn('$1,563', total_el.inner_text())
            self.assertIn('10', days_el.inner_text().upper())

            # Change to 7 days preset chip
            page.locator('.vg-calc-preset-chip[data-days="7"]').click()
            self.assertIn('7', days_el.inner_text().upper())
            self.assertNotEqual(total_el.inner_text().strip(), '$1,563')

            # Switch to Backpacker style by clicking the visible card label
            page.locator('.vg-calc-style-card:has(input[value="backpacker"])').click()
            daily_el = page.locator('#vg-calc-daily-display')
            self.assertIn('$48', daily_el.inner_text())

            self.assertEqual(console_errors, [])
            self.assertEqual(page_errors, [])
        finally:
            page.close()

    def test_e2e_season_matrix_interactive_navigation(self):
        """Playwright E2E: [vg_season_matrix] switches months and updates regional climate briefs with 0 errors."""
        sm_html = _render_php_component(
            "require 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-season-matrix.php';\n"
            "echo vg_render_season_matrix_html();\n"
        )
        full_doc = f"""<!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <style>{self.homepage_css}</style>
            <style>{self.interactive_css}</style>
        </head>
        <body>
            {sm_html}
        </body>
        </html>"""

        page = self._browser.new_page()
        console_errors = []
        page_errors = []
        page.on("console", lambda msg: console_errors.append(msg.text) if msg.type == "error" else None)
        page.on("pageerror", lambda err: page_errors.append(str(err)))

        try:
            page.set_content(full_doc, wait_until="domcontentloaded")

            verdict_title = page.locator('#vg-sm-verdict-title')
            self.assertIn('November', verdict_title.inner_text())

            # Click February pill
            page.locator('.vg-sm-month-pill[data-month="feb"]').click()
            self.assertIn('February', verdict_title.inner_text())

            # Click °F unit toggle
            page.locator('.vg-sm-unit-btn[data-unit="f"]').click()
            north_temp = page.locator('#vg-temp-north')
            self.assertIn('°F', north_temp.inner_text())

            self.assertEqual(console_errors, [])
            self.assertEqual(page_errors, [])
        finally:
            page.close()

    def test_e2e_airport_navigator_interactive_tabs(self):
        """Playwright E2E: [vg_airport_navigator] switches airports (HAN/SGN/DAD) and information tabs."""
        an_html = _render_php_component(
            "require 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-airport-navigator.php';\n"
            "echo vg_render_airport_navigator_html();\n"
        )
        full_doc = f"""<!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <style>{self.homepage_css}</style>
            <style>{self.interactive_css}</style>
        </head>
        <body>
            {an_html}
        </body>
        </html>"""

        page = self._browser.new_page()
        console_errors = []
        page_errors = []
        page.on("console", lambda msg: console_errors.append(msg.text) if msg.type == "error" else None)
        page.on("pageerror", lambda err: page_errors.append(str(err)))

        try:
            page.set_content(full_doc, wait_until="domcontentloaded")
            page.wait_for_selector('#vg-an-b-code')

            code_el = page.locator('#vg-an-b-code')
            self.assertEqual(code_el.text_content().strip(), 'HAN')

            # Switch to Tan Son Nhat (SGN)
            page.locator('.vg-an-chip[data-airport="SGN"]').click()
            self.assertEqual(code_el.text_content().strip(), 'SGN')
            name_el = page.locator('#vg-an-b-name')
            self.assertIn('Tan Son Nhat', name_el.text_content())

            # Switch to Scam Shield tab
            page.locator('.vg-an-tab[data-tab="scam-shield"]').click()
            scam_panel = page.locator('#vg-panel-scam-shield')
            self.assertIn('active', scam_panel.get_attribute('class') or '')

            self.assertEqual(console_errors, [])
            self.assertEqual(page_errors, [])
        finally:
            page.close()

    def test_e2e_packing_checklist_interactive_toggles(self):
        """Playwright E2E: [vg_packing_checklist] tracks checked items and updates progress percentage."""
        pc_html = _render_php_component(
            "require 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-packing-checklist.php';\n"
            "echo vg_render_packing_checklist();\n"
        )
        full_doc = f"""<!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <style>{self.homepage_css}</style>
            <style>{self.interactive_css}</style>
        </head>
        <body>
            {pc_html}
        </body>
        </html>"""

        page = self._browser.new_page()
        console_errors = []
        page_errors = []
        page.on("console", lambda msg: console_errors.append(msg.text) if msg.type == "error" else None)
        page.on("pageerror", lambda err: page_errors.append(str(err)))

        try:
            page.set_content(full_doc, wait_until="domcontentloaded")

            counter_el = page.locator('[data-vg-checklist-counter]')
            initial_text = counter_el.inner_text()
            self.assertIn('0 of', initial_text)

            # Check the first item by clicking its custom visual checkbox
            page.locator('.vg-checklist-box-custom').first.click()

            updated_text = counter_el.inner_text()
            self.assertIn('1 of', updated_text)

            self.assertEqual(console_errors, [])
            self.assertEqual(page_errors, [])
        finally:
            page.close()

    def test_e2e_itinerary_finder_interactive_filtering(self):
        """Playwright E2E: [vg_itinerary_finder] filters curated itineraries by duration and style."""
        it_html = _render_php_component(
            "require 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-itinerary-finder.php';\n"
            "echo vg_render_itinerary_finder_html();\n"
        )
        full_doc = f"""<!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <style>{self.homepage_css}</style>
            <style>{self.interactive_css}</style>
        </head>
        <body>
            {it_html}
        </body>
        </html>"""

        page = self._browser.new_page()
        console_errors = []
        page_errors = []
        page.on("console", lambda msg: console_errors.append(msg.text) if msg.type == "error" else None)
        page.on("pageerror", lambda err: page_errors.append(str(err)))

        try:
            page.set_content(full_doc, wait_until="domcontentloaded")

            count_el = page.locator('.vg-finder-count')
            self.assertTrue(count_el.is_visible())

            # Filter by 7 Days
            page.locator('.vg-finder-pill[data-group="duration"][data-value="7d"]').click()
            visible_cards = page.locator('.vg-finder-card:not([style*="display: none"])')
            self.assertGreater(visible_cards.count(), 0)

            self.assertEqual(console_errors, [])
            self.assertEqual(page_errors, [])
        finally:
            page.close()


if __name__ == '__main__':
    unittest.main()

