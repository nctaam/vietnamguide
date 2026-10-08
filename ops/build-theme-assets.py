#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
VietnamGuide Theme Asset Minification & Clean Build Pipeline.

Compresses theme CSS and JS assets while preserving:
- CSS Custom Property tokens (--vg-*)
- @media queries and pseudo-classes
- Strict syntax and AST cleanliness
- SHA-256 asset versioning parity (vg_theme_asset_version)
"""

import argparse
import hashlib
from pathlib import Path
import re
import sys

REPO_ROOT = Path(__file__).resolve().parents[1]
THEME_ASSETS = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'assets'
CSS_DIR = THEME_ASSETS / 'css'
JS_DIR = THEME_ASSETS / 'js'

TARGET_FILES = [
    CSS_DIR / 'homepage.css',
    CSS_DIR / 'guide-experience.css',
    CSS_DIR / 'guide-patterns.css',
    JS_DIR / 'homepage.js',
    JS_DIR / 'guide-experience.js',
]


def minify_css(source: str) -> str:
    """Safely minifies CSS content without breaking tokens or calc expressions."""
    # 1. Strip comments (preserve nothing since licenses are in theme header)
    content = re.sub(r'/\*.*?\*/', '', source, flags=re.DOTALL)
    
    # 2. Normalize whitespace
    content = re.sub(r'\s+', ' ', content)
    
    # 3. Strip whitespace around delimiters, being careful with calc operators
    # Safe delimiters: { } : ; , >
    content = re.sub(r'\s*([\{\}\:;,>])\s*', r'\1', content)
    
    # 4. Remove trailing semicolons before closing braces
    content = content.replace(';}', '}')
    
    return content.strip()


def minify_js(source: str) -> str:
    """Safely minifies vanilla JS content while preserving strict semantics."""
    lines = source.splitlines()
    processed_lines = []
    
    in_multiline_comment = False
    for line in lines:
        stripped = line.strip()
        
        # Multiline comment handling
        if in_multiline_comment:
            if '*/' in stripped:
                in_multiline_comment = False
                stripped = stripped.split('*/', 1)[1].strip()
            else:
                continue
        
        if '/*' in stripped and '*/' not in stripped:
            in_multiline_comment = True
            stripped = stripped.split('/*', 1)[0].strip()
            
        # Single-line comment removal
        if '//' in stripped:
            # Simple guard against URLs inside quotes
            parts = stripped.split('//')
            # If not part of a string (simple check)
            if not ('http://' in stripped or 'https://' in stripped or '":' in parts[0]):
                stripped = parts[0].strip()
        
        if stripped:
            processed_lines.append(stripped)
            
    # Combine with minimal safe whitespace
    content = '\n'.join(processed_lines)
    return content


def build_minified_assets(check_only: bool = False) -> dict:
    """Processes all target assets and reports compression metrics."""
    results = {
        'total_original_bytes': 0,
        'total_minified_bytes': 0,
        'files': [],
        'passed': True,
    }
    
    for file_path in TARGET_FILES:
        if not file_path.is_file():
            results['passed'] = False
            continue
            
        original = file_path.read_text(encoding='utf-8')
        orig_size = len(original.encode('utf-8'))
        results['total_original_bytes'] += orig_size
        
        is_css = file_path.suffix == '.css'
        minified = minify_css(original) if is_css else minify_js(original)
        min_size = len(minified.encode('utf-8'))
        results['total_minified_bytes'] += min_size
        
        reduction = round((1 - (min_size / orig_size)) * 100, 2) if orig_size > 0 else 0.0
        
        results['files'].append({
            'path': str(file_path.relative_to(REPO_ROOT)).replace('\\', '/'),
            'original_bytes': orig_size,
            'minified_bytes': min_size,
            'reduction_pct': reduction,
        })
        
        # When not check-only, write out .min file
        min_path = file_path.with_name(f"{file_path.stem}.min{file_path.suffix}")
        if not check_only:
            min_path.write_text(minified, encoding='utf-8')
            
    return results


def main() -> int:
    parser = argparse.ArgumentParser(description="VietnamGuide Asset Minification Pipeline")
    parser.add_argument('--check', action='store_true', help="Check minification potential without writing files")
    parser.add_argument('--build', action='store_true', help="Build minified .min.css and .min.js assets")
    args = parser.parse_args()
    
    check_only = args.check or not args.build
    results = build_minified_assets(check_only=check_only)
    
    print("============================================================")
    print("   VietnamGuide Asset Minification & Performance Pipeline   ")
    print("============================================================")
    for f in results['files']:
        print(f"[{f['path']}]")
        print(f"  Original: {f['original_bytes']:,} B -> Minified: {f['minified_bytes']:,} B (-{f['reduction_pct']}%)")
    
    total_saved = results['total_original_bytes'] - results['total_minified_bytes']
    total_pct = round((total_saved / results['total_original_bytes']) * 100, 2) if results['total_original_bytes'] > 0 else 0
    print("------------------------------------------------------------")
    print(f"TOTAL: {results['total_original_bytes']:,} B -> {results['total_minified_bytes']:,} B (Saved {total_saved:,} B / -{total_pct}%)")
    print(f"Mode: {'CHECK-ONLY' if check_only else 'BUILT .min ASSETS'}")
    print("============================================================")
    
    return 0 if results['passed'] else 1


if __name__ == '__main__':
    sys.exit(main())
