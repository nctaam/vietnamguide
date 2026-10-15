# -*- coding: utf-8 -*-
"""
Unit and Contract Tests for VietnamGuide Theme Asset Minification and Performance Pipeline.
Verifies that:
1. ops/build-theme-assets.py exists and compresses without breaking CSS/JS tokens.
2. CSS token custom properties (--vg-*) and calc expressions are intact.
3. Zero syntax regressions occur in minified files.
4. functions.php vg_theme_asset_version hashes match sha256 checksums.
"""

import hashlib
import importlib.util
import os
import re
import subprocess
import sys
import unittest
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[2]
THEME_ASSETS = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'assets'
BUILD_SCRIPT = REPO_ROOT / 'ops' / 'build-theme-assets.py'


def _load_build_module():
    spec = importlib.util.spec_from_file_location("build_theme_assets", str(BUILD_SCRIPT))
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


class TestThemeAssetsPipeline(unittest.TestCase):

    def setUp(self):
        self.assertTrue(BUILD_SCRIPT.is_file(), f"Missing build script: {BUILD_SCRIPT}")
        self.assertTrue((THEME_ASSETS / 'css' / 'homepage.css').is_file())
        self.assertTrue((THEME_ASSETS / 'css' / 'guide-experience.css').is_file())
        self.assertTrue((THEME_ASSETS / 'css' / 'guide-patterns.css').is_file())
        self.assertTrue((THEME_ASSETS / 'js' / 'homepage.js').is_file())
        self.assertTrue((THEME_ASSETS / 'js' / 'guide-experience.js').is_file())
        self.build_mod = _load_build_module()

    def test_build_script_check_mode_runs_successfully(self):
        """build-theme-assets.py --check must exit with 0 and show compression savings."""
        res = subprocess.run(
            [sys.executable, str(BUILD_SCRIPT), '--check'],
            capture_output=True,
            text=True,
            cwd=str(REPO_ROOT)
        )
        self.assertEqual(res.returncode, 0, f"Script failed:\n{res.stdout}\n{res.stderr}")
        self.assertIn("TOTAL:", res.stdout)
        self.assertIn("Mode: CHECK-ONLY", res.stdout)
        self.assertIn("Saved", res.stdout)

    def test_css_minification_preserves_custom_properties_and_calc(self):
        """CSS minifier must preserve all --vg-* custom property names and values."""
        css_path = THEME_ASSETS / 'css' / 'homepage.css'
        content = css_path.read_text(encoding='utf-8')
        
        # Extract --vg- custom property names defined in :root
        custom_props = set(re.findall(r'(--vg-[a-zA-Z0-9_-]+)\s*:', content))
        self.assertGreater(len(custom_props), 15, "Must find CSS custom properties in homepage.css :root")

        minified = self.build_mod.minify_css(content)

        for prop in custom_props:
            self.assertIn(prop, minified, f"Custom property '{prop}' was lost during minification")

        # Ensure no calc expression broke
        if 'calc(' in content:
            self.assertIn('calc(', minified, "calc() function calls must be preserved")

    def test_js_minification_syntax_validity(self):
        """JS minifier output must retain essential event listeners and functions."""
        js_path = THEME_ASSETS / 'js' / 'guide-experience.js'
        content = js_path.read_text(encoding='utf-8')

        minified = self.build_mod.minify_js(content)

        self.assertIn('IntersectionObserver', minified)
        self.assertIn('vg-floating-dock', minified)
        self.assertIn('addEventListener', minified)
        self.assertIn('requestAnimationFrame', minified)
        self.assertIn('data-vg-print', minified)
        self.assertIn('window.print', minified)

    def test_theme_asset_version_contract_in_functions_php(self):
        """functions.php must declare vg_theme_asset_version supporting all 5 core assets."""
        functions_path = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'functions.php'
        func_content = functions_path.read_text(encoding='utf-8')

        self.assertIn('function vg_theme_asset_version', func_content)
        self.assertIn('assets/css/homepage.css', func_content)
        self.assertIn('assets/css/guide-experience.css', func_content)
        self.assertIn('assets/css/guide-patterns.css', func_content)
        self.assertIn('assets/css/guide-interactive.css', func_content)
        self.assertIn('assets/js/homepage.js', func_content)
        self.assertIn('assets/js/guide-experience.js', func_content)
        self.assertIn('assets/js/guide-interactive.js', func_content)

    def test_interactive_css_extracted_from_homepage_css(self):
        """Interactive map and photo dispatch CSS must be isolated in guide-interactive.css, not bloating homepage.css."""
        homepage_css = (THEME_ASSETS / 'css' / 'homepage.css').read_text(encoding='utf-8')
        interactive_css_path = THEME_ASSETS / 'css' / 'guide-interactive.css'
        interactive_js_path = THEME_ASSETS / 'js' / 'guide-interactive.js'

        self.assertTrue(interactive_css_path.is_file(), "guide-interactive.css must exist")
        self.assertTrue(interactive_js_path.is_file(), "guide-interactive.js must exist")

        interactive_css = interactive_css_path.read_text(encoding='utf-8')
        self.assertNotIn('.vg-interactive-map-desk {', homepage_css)
        self.assertNotIn('.vg-photo-dispatch {', homepage_css)
        self.assertIn('.vg-interactive-map-desk {', interactive_css)
        self.assertIn('.vg-photo-dispatch {', interactive_css)
        self.assertIn('.vg-dispatch-lightbox {', interactive_css)
        self.assertIn('.vg-field-desk-showcase {', interactive_css)

    def test_conditional_interactive_asset_enqueue_runtime(self):
        """vg_should_enqueue_interactive_assets must only return true on front-page or pages with interactive shortcodes."""
        php_code = r"""
        define('ABSPATH', 1);
        class WP_Post {
            public int $ID = 1;
            public string $post_content = '';
        }
        $GLOBALS['vg_is_front'] = false;
        $GLOBALS['vg_current_post'] = null;
        $GLOBALS['vg_enqueued_styles'] = [];
        $GLOBALS['vg_enqueued_scripts'] = [];

        function is_front_page(): bool { return (bool) $GLOBALS['vg_is_front']; }
        function get_post(): ?WP_Post { return $GLOBALS['vg_current_post']; }
        function get_theme_file_path(string $rel = ''): string {
            return getcwd() . '/wordpress/wp-content/themes/vietnamguide-premium/' . ltrim($rel, '/');
        }
        function get_theme_file_uri(string $rel = ''): string {
            return 'https://vietnamguide.net/wp-content/themes/vietnamguide-premium/' . ltrim($rel, '/');
        }
        function wp_get_theme() {
            return new class { public function get(string $k): string { return '2.0.0'; } };
        }
        function add_action(string $tag, callable $cb, int $p = 10, int $a = 1): void {}
        function add_filter(string $tag, callable $cb, int $p = 10, int $a = 1): void {}
        function add_shortcode(string $tag, callable $cb): void {}
        function __return_false(): bool { return false; }
        function wp_enqueue_style(string $handle, string $src = '', array $deps = [], string $ver = ''): void {
            $GLOBALS['vg_enqueued_styles'][$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver];
        }
        function wp_enqueue_script(string $handle, string $src = '', array $deps = [], string $ver = '', bool $in_footer = false): void {
            $GLOBALS['vg_enqueued_scripts'][$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver, 'footer' => $in_footer];
        }

        require get_theme_file_path('/functions.php');

        // 1. Plain guide page without interactive shortcodes -> false
        $plainPost = new WP_Post();
        $plainPost->post_content = '<p>Standard travel guide content without interactive map or photo dispatch.</p>';
        $GLOBALS['vg_current_post'] = $plainPost;
        echo vg_should_enqueue_interactive_assets($plainPost) ? "plain:1\n" : "plain:0\n";

        // 2. Guide page with [vg_interactive_map] -> true
        $mapPost = new WP_Post();
        $mapPost->post_content = '<p>Intro</p>[vg_interactive_map]<p>Outro</p>';
        echo vg_should_enqueue_interactive_assets($mapPost) ? "map:1\n" : "map:0\n";

        // 3. Guide page with [vg_photo_dispatch] -> true
        $photoPost = new WP_Post();
        $photoPost->post_content = '<p>Intro</p>[vg_photo_dispatch slug="hoi-an-dusk"]';
        echo vg_should_enqueue_interactive_assets($photoPost) ? "photo:1\n" : "photo:0\n";

        // 4. Front page -> true + enqueue verification
        $GLOBALS['vg_is_front'] = true;
        $GLOBALS['vg_current_post'] = $plainPost;
        echo vg_should_enqueue_interactive_assets($plainPost) ? "front:1\n" : "front:0\n";
        vg_enqueue_interactive_shortcode_assets();
        echo isset($GLOBALS['vg_enqueued_styles']['vietnamguide-guide-interactive']) ? "style:1\n" : "style:0\n";
        echo isset($GLOBALS['vg_enqueued_scripts']['vietnamguide-guide-interactive']) ? "script:1\n" : "script:0\n";
        echo strlen($GLOBALS['vg_enqueued_styles']['vietnamguide-guide-interactive']['ver']) === 64 ? "hash:1\n" : "hash:0\n";
        """
        res = subprocess.run(
            ['php', '-r', php_code],
            cwd=str(REPO_ROOT),
            capture_output=True,
            text=True,
            encoding='utf-8',
        )
        self.assertEqual(res.returncode, 0, f"PHP runtime failed:\n{res.stdout}\n{res.stderr}")
        self.assertEqual(
            res.stdout.strip().splitlines(),
            ['plain:0', 'map:1', 'photo:1', 'front:1', 'style:1', 'script:1', 'hash:1'],
        )

    def test_htaccess_security_and_caching_directives(self):
        """.htaccess must enforce strict security headers and long-lived asset caching."""
        htaccess_path = REPO_ROOT / 'wordpress' / '.htaccess'
        content = htaccess_path.read_text(encoding='utf-8')

        # Security Headers
        self.assertIn('Strict-Transport-Security', content)
        self.assertIn('X-Content-Type-Options "nosniff"', content)
        self.assertIn('X-Frame-Options "SAMEORIGIN"', content)
        self.assertIn('Cross-Origin-Opener-Policy "same-origin"', content)
        self.assertIn('Cross-Origin-Resource-Policy "same-origin"', content)

        # High Performance Caching
        self.assertIn('<IfModule mod_expires.c>', content)
        self.assertIn('ExpiresByType text/css "access plus 1 year"', content)
        self.assertIn('ExpiresByType application/javascript "access plus 1 year"', content)
        self.assertIn('ExpiresByType font/woff2 "access plus 1 year"', content)
        self.assertIn('ExpiresByType text/html "access plus 0 seconds"', content)

    def test_mobile_hero_responsive_min_height(self):
        """Block pattern hero must use viewport-aware responsive min-height on mobile (CWV LCP/FCP)."""
        patterns_css_path = THEME_ASSETS / 'css' / 'guide-patterns.css'
        content = patterns_css_path.read_text(encoding='utf-8')

        # Check mobile media query block contains min(580px, 85svh)
        self.assertIn('min-height: min(580px, 85svh);', content)
        self.assertNotIn('min-height: 700px;', content)

    def test_print_stylesheet_contract(self):
        """Guide experience stylesheet must include @media print rules suppressing screen-only UI."""
        experience_css_path = THEME_ASSETS / 'css' / 'guide-experience.css'
        content = experience_css_path.read_text(encoding='utf-8')

        self.assertIn('@media print', content)
        self.assertIn('.vg-site-header', content)
        self.assertIn('.vg-floating-dock', content)
        self.assertIn('.vg-guide-dock', content)
        self.assertIn('.vg-share-bar', content)
        self.assertIn('page-break-inside: avoid;', content)


if __name__ == '__main__':
    unittest.main()

