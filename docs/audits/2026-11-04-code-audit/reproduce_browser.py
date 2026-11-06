"""Reproduce local audit observations without WordPress DB, mail or deployment.

Run from any directory with PHP, Python Playwright and Chromium installed:
    python docs/audits/2026-11-04-code-audit/reproduce_browser.py

The browser intercepts all network traffic. PHP renders actual project components
using the existing test suite's WordPress stubs. Output is an observation report,
not a replacement for integration testing against a complete WordPress install.
"""
from pathlib import Path
import json
import os
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[3]
OUTPUT = Path(__file__).resolve().parent
sys.path.insert(0, str(ROOT))
os.chdir(ROOT)

from ops.tests.test_e2e_playwright_smoke import _render_php_component
from playwright.sync_api import sync_playwright

THEME = Path('wordpress/wp-content/themes/vietnamguide-premium')
css = '\n'.join(p.read_text(encoding='utf-8') for p in (THEME / 'assets/css').glob('*.css'))
php = subprocess.run(
    ['php', str(OUTPUT / 'repro.php')], capture_output=True,
    text=True, encoding='utf-8', check=True, cwd=ROOT,
)
results = json.loads(php.stdout)

with sync_playwright() as pw:
    browser = pw.chromium.launch()

    def make(module, expression, extra_script=''):
        component = _render_php_component(
            f"require '{THEME.as_posix()}/inc/{module}.php'; echo {expression};"
        )
        document = (
            '<!doctype html><meta charset="utf-8">'
            '<meta name="viewport" content="width=device-width,initial-scale=1">'
            f'<style>{css}</style>{component}<script>{extra_script}</script>'
        )
        page = browser.new_page(viewport={'width': 390, 'height': 844})
        errors = []
        page.on('pageerror', lambda error: errors.append(str(error)))
        page.route(
            '**/*',
            lambda route: route.fulfill(status=200, content_type='text/html', body=document)
            if route.request.resource_type == 'document' else route.abort(),
        )
        page.goto('https://audit.local/', wait_until='domcontentloaded')
        return page, errors

    for stage in ('before', 'after'):
        page = browser.new_page()
        page.route('**/*', lambda route: route.abort())
        page.set_content(results[f'jsonld_{stage}'])
        results[f'jsonld_script_exec_{stage}'] = page.evaluate('window.__auditExecuted === true')
        page.close()

    page, errors = make('guide-cost-calculator', 'vg_render_cost_calculator_html()')
    page.evaluate("Object.defineProperty(navigator,'clipboard',{value:undefined,configurable:true})")
    page.locator('#vg-calc-copy-btn').click()
    results['cost_clipboard_unavailable'] = list(errors)
    page.locator('.vg-calc-route-chip[data-route="ha-giang"]').click()
    page.locator('.vg-calc-preset-chip[data-days="7"]').click()

    def cost_snapshot():
        return {
            'url': page.url,
            'days': page.locator('#vg-calc-days-slider').input_value(),
            'total': page.locator('#vg-calc-total-display').inner_text(),
        }

    results['cost_shared_state'] = {'before_reload': cost_snapshot()}
    page.reload(wait_until='domcontentloaded')
    results['cost_shared_state']['after_reload'] = cost_snapshot()
    page.locator('#vg-calc-reset-btn').click()
    results['cost_reset'] = {'before_reload': cost_snapshot()}
    page.reload(wait_until='domcontentloaded')
    results['cost_reset']['after_reload'] = cost_snapshot()
    page.close()

    page, errors = make('guide-visa-checker', 'vg_render_visa_checker_html()')
    page.locator('[data-entry="multiple"]').click()
    results['visa_shared_state'] = {
        'before_reload': page.locator('[data-entry="multiple"]').get_attribute('aria-checked'),
        'url': page.url,
    }
    page.reload(wait_until='domcontentloaded')
    results['visa_shared_state']['after_reload'] = page.locator('[data-entry="multiple"]').get_attribute('aria-checked')
    page.close()

    guide_js = (THEME / 'assets/js/guide-experience.js').read_text(encoding='utf-8')
    page, errors = make(
        'guide-packing-checklist', 'vg_render_packing_checklist()',
        'window.__printCount=0;window.print=function(){window.__printCount++};' + guide_js,
    )
    page.locator('[data-vg-print]').first.click()
    results['packing_print_calls'] = page.evaluate('window.__printCount')
    page.close()

    homepage_js = (THEME / 'assets/js/homepage.js').read_text(encoding='utf-8')
    results['reduced_motion'] = {}
    for motion in ('no-preference', 'reduce'):
        page = browser.new_page(reduced_motion=motion)
        page.route('**/*', lambda route: route.abort())
        page.set_content(
            '<!doctype html><main id="main"><input type="search">'
            '<button data-vg-copy-link><span class="vg-copy-link__text">Copy</span></button>'
            '<div data-vg-reveal>Content</div></main>'
            '<script>window.__copied=false;'
            "Object.defineProperty(navigator,'clipboard',{value:{writeText:()=>{window.__copied=true;return Promise.resolve()}}});"
            + homepage_js + '</script>'
        )
        page.locator('[data-vg-copy-link]').click()
        page.keyboard.press('/')
        results['reduced_motion'][motion] = {
            'copied': page.evaluate('window.__copied'),
            'search_focused': page.locator('input[type=search]').evaluate('(el)=>document.activeElement===el'),
        }
        page.close()

    browser.close()

target = OUTPUT / 'browser-reproductions.json'
target.write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps(results, ensure_ascii=False, indent=2))
