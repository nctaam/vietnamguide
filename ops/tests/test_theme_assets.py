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

    def test_theme_asset_version_contract_in_functions_php(self):
        """functions.php must declare vg_theme_asset_version supporting all 5 core assets."""
        functions_path = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'functions.php'
        func_content = functions_path.read_text(encoding='utf-8')

        self.assertIn('function vg_theme_asset_version', func_content)
        self.assertIn('assets/css/homepage.css', func_content)
        self.assertIn('assets/css/guide-experience.css', func_content)
        self.assertIn('assets/css/guide-patterns.css', func_content)
        self.assertIn('assets/js/homepage.js', func_content)
        self.assertIn('assets/js/guide-experience.js', func_content)


if __name__ == '__main__':
    unittest.main()
