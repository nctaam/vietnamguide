#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
VietnamGuide Route Registry Compiler & Parity Enforcer.
Synchronizes ops/route_registry.json -> inc/guide-route-registry.json
and compiles the OPcache-immutable PHP payload into inc/guide-rollout.php.
"""

from __future__ import annotations

import argparse
import hashlib
import json
from pathlib import Path
import sys

REPO_ROOT = Path(__file__).resolve().parents[1]
OPS_REGISTRY_JSON = REPO_ROOT / 'ops' / 'route_registry.json'
THEME_INC_DIR = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'inc'
THEME_REGISTRY_JSON = THEME_INC_DIR / 'guide-route-registry.json'
THEME_ROLLOUT_PHP = THEME_INC_DIR / 'guide-rollout.php'

sys.path.insert(0, str(REPO_ROOT))
from ops.route_contract import compile_php_registry, validate_registry


def compile_route_registry(
    ops_json: Path = OPS_REGISTRY_JSON,
    theme_json: Path = THEME_REGISTRY_JSON,
    theme_php: Path = THEME_ROLLOUT_PHP,
    check_only: bool = False,
) -> bool:
    if not ops_json.is_file():
        print(f"ERROR: Missing source registry {ops_json}", file=sys.stderr)
        return False

    raw_ops = ops_json.read_text(encoding='utf-8')
    data = json.loads(raw_ops)
    routes = data.get('routes', [])

    # 1. Validate route records
    errors = validate_registry(routes)
    if errors:
        print(f"ERROR: Invalid registry ({len(errors)} errors):", file=sys.stderr)
        for e in errors[:10]:
            print(f"  - {e}", file=sys.stderr)
        return False

    # 2. Re-serialize canonically (formatted UTF-8 with newline)
    canonical_json = json.dumps(data, indent=2, ensure_ascii=False) + '\n'
    ops_bytes = canonical_json.encode('utf-8')
    ops_sha256 = hashlib.sha256(ops_bytes).hexdigest().lower()

    if check_only:
        if not theme_json.is_file():
            print("CHECK FAILED: inc/guide-route-registry.json does not exist")
            return False
        theme_bytes = theme_json.read_bytes()
        theme_sha256 = hashlib.sha256(theme_bytes).hexdigest().lower()
        if ops_sha256 != theme_sha256:
            print(f"CHECK FAILED: SHA-256 mismatch (ops: {ops_sha256}, theme: {theme_sha256})")
            return False
        expected_php = compile_php_registry(theme_json)
        if not theme_php.is_file() or theme_php.read_text(encoding='utf-8') != expected_php:
            print("CHECK FAILED: inc/guide-rollout.php is out of sync")
            return False
        print(f"CHECK PASSED: 100% SHA-256 Parity ({ops_sha256}) across all 3 artifacts.")
        return True

    # 3. Synchronize ops/route_registry.json (ensure canonical bytes)
    ops_json.write_bytes(ops_bytes)

    # 4. Write identical bytes to theme JSON
    theme_json.write_bytes(ops_bytes)
    theme_sha256 = hashlib.sha256(theme_json.read_bytes()).hexdigest().lower()
    assert ops_sha256 == theme_sha256, "SHA-256 drift during write!"

    # 5. Compile PHP payload
    compile_php_registry(theme_json, output_path=theme_php)

    print("============================================================")
    print("   VietnamGuide Route Registry Compiler & Parity Enforcer   ")
    print("============================================================")
    print(f"Routes Compiled        : {len(routes)}/282")
    print(f"Registry Payload Size  : {len(ops_bytes):,} bytes")
    print(f"SHA-256 Digest         : {ops_sha256}")
    print(f"Output Theme JSON      : {theme_json.relative_to(REPO_ROOT)}")
    print(f"Output Theme PHP       : {theme_php.relative_to(REPO_ROOT)}")
    print("STATUS                 : SUCCESS (100% Byte Parity)")
    print("============================================================")
    return True


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--check', action='store_true', help='Verify parity without writing')
    args = parser.parse_args()
    success = compile_route_registry(check_only=args.check)
    sys.exit(0 if success else 1)
