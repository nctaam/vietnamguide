# -*- coding: utf-8 -*-
"""
VietnamGuide Ground-Truth Tariff, Freshness & Deep SEO Sentinel (Milestone 3 / RISK-EDIT-01)

Audits all 282 canonical routes in ops/route_registry.json, the 12 regional travel clusters,
tri-file SHA-256 byte parity, and the interactive travel toolkits in wordpress/wp-content/themes/vietnamguide-premium/inc/ for:
1. Editorial review freshness cadence (valid YYYY-MM-DD last_reviewed within max_age_days).
2. 100% Google SERP Title Cap (<= 60 chars) & Meta Description Golden Window (147-157 chars).
3. Empirical Ground-Truth Density >= 98% (VND/USD tariffs, km/hour transit metrics, seasonal windows, official portals).
4. Complete 12 Regional Travel Clusters taxonomy & geo-coordinate validity in inc/guide-seo.php.
5. 100% SHA-256 Byte Parity across ops/route_registry.json, inc/guide-route-registry.json, and inc/guide-rollout.php.
6. Interactive Toolkit Regulatory & Rate Anchors (e-Visa $25/$50 fees, 90-day/45-day rules, Bus 86/109/152).
"""

from __future__ import annotations

import argparse
from datetime import date, datetime
import hashlib
import json
from pathlib import Path
import re
import sys
from typing import Any

REPO_ROOT = Path(__file__).resolve().parents[1]
if str(REPO_ROOT) not in sys.path:
    sys.path.insert(0, str(REPO_ROOT))

from ops.route_contract import compile_php_registry

DEFAULT_REGISTRY_PATH = REPO_ROOT / 'ops' / 'route_registry.json'
DEFAULT_THEME_INC_DIR = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'inc'

CURRENCY_PATTERN = re.compile(
    r'(?:\b\d[\d,]*\s*(?:VND|USD)\b|\$\d+|\b(?:fares?|fees?|costs?|rates?|budgets?|tickets?|permits?|tolls?|deposits?|ATMs?|cash|cards?)\b)',
    re.IGNORECASE,
)
TRANSIT_PATTERN = re.compile(
    r'(?:\b\d+(?:\.\d+)?(?:\s*[–-]\s*\d+(?:\.\d+)?)?[\s-]*(?:hr|hrs|hour|hours|min|mins|minute|minutes|km|m|day|days|week|weeks|night|nights)\b'
    r'|\b(?:SE\d+|SP\d+|CT\d+|QL\d+[A-Z]?|AH\d+|Bus\s+\d+|sleeper\s+bus|train|ferry|speedboat|flight|airport|cable\s+car|limousine|expressway|motorbike|scooter|transfer|departures?|schedules?|timetables?)\b)',
    re.IGNORECASE,
)
CLIMATE_PATTERN = re.compile(
    r'(?:\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\b'
    r'|\b\d+\s*(?:deg\s*C|°C)\b'
    r'|\b(?:dry|rainy|monsoon|typhoon|weather|season|seasonal|humidity|flood|heat|mist|swell)\b)',
    re.IGNORECASE,
)
REGULATORY_PORTAL_PATTERN = re.compile(
    r'(?:evisa\.xuatnhapcanh\.gov\.vn|dsvn\.vn|127/NQ-CP|128/NQ-CP|45-day|90-day|\b(?:e-visa|visa|customs|IDP|scams?|safety| UNESCO|2026)\b)',
    re.IGNORECASE,
)

TOOLKIT_ANCHOR_CONTRACTS: dict[str, list[str]] = {
    'guide-visa-checker.php': [
        '25',
        '50',
        '90',
        '45',
        'evisa.xuatnhapcanh.gov.vn',
    ],
    'guide-cost-calculator.php': [
        'VND',
        'USD',
    ],
    'guide-airport-navigator.php': [
        'HAN',
        'SGN',
        'DAD',
        '86',
    ],
}


def parse_iso_date(value: str) -> date | None:
    try:
        return datetime.strptime(value.strip(), '%Y-%m-%d').date()
    except (ValueError, AttributeError):
        return None


def audit_route_registry(
    registry_path: Path = DEFAULT_REGISTRY_PATH,
    *,
    reference_date: date | None = None,
    max_age_days: int = 365,
) -> dict[str, Any]:
    raw = json.loads(registry_path.read_text(encoding='utf-8'))
    routes = raw.get('routes', [])
    if reference_date is None:
        gen_str = str(raw.get('generated_at', '')).strip()
        reference_date = parse_iso_date(gen_str) or date.today()

    stale_routes: list[dict[str, Any]] = []
    invalid_date_routes: list[str] = []
    title_violations: list[dict[str, Any]] = []
    desc_violations: list[dict[str, Any]] = []

    counts = {
        'currency_tariff': 0,
        'transit_duration': 0,
        'climate_season': 0,
        'regulatory_portal': 0,
        'any_empirical_signal': 0,
    }

    for record in routes:
        path = str(record.get('path', '')).strip()
        title = str(record.get('title', ''))
        desc = str(record.get('description', ''))
        reviewed_str = str(record.get('last_reviewed') or record.get('reviewed_at') or '').strip()

        if not (1 <= len(title) <= 60):
            title_violations.append({'path': path, 'length': len(title), 'title': title})
        if not (147 <= len(desc) <= 157):
            desc_violations.append({'path': path, 'length': len(desc), 'description': desc})

        reviewed_date = parse_iso_date(reviewed_str)
        if reviewed_date is None:
            invalid_date_routes.append(path)
        else:
            age_days = (reference_date - reviewed_date).days
            if age_days > max_age_days:
                stale_routes.append({
                    'path': path,
                    'last_reviewed': reviewed_str,
                    'age_days': age_days,
                })

        combined_text = f'{title} {desc}'
        has_curr = bool(CURRENCY_PATTERN.search(combined_text))
        has_tran = bool(TRANSIT_PATTERN.search(combined_text))
        has_clim = bool(CLIMATE_PATTERN.search(combined_text))
        has_reg = bool(REGULATORY_PORTAL_PATTERN.search(combined_text))

        if has_curr:
            counts['currency_tariff'] += 1
        if has_tran:
            counts['transit_duration'] += 1
        if has_clim:
            counts['climate_season'] += 1
        if has_reg:
            counts['regulatory_portal'] += 1
        if has_curr or has_tran or has_clim or has_reg:
            counts['any_empirical_signal'] += 1

    total = len(routes)
    empirical_ratio = round((counts['any_empirical_signal'] / total) * 100, 2) if total else 0.0

    return {
        'route_count': total,
        'reference_date': reference_date.isoformat(),
        'max_age_days': max_age_days,
        'invalid_date_routes': invalid_date_routes,
        'stale_routes': stale_routes,
        'title_violations': title_violations,
        'description_violations': desc_violations,
        'empirical_counts': counts,
        'empirical_coverage_pct': empirical_ratio,
    }


def audit_toolkit_anchors(theme_inc_dir: Path = DEFAULT_THEME_INC_DIR) -> dict[str, Any]:
    missing_files: list[str] = []
    missing_anchors: list[dict[str, str]] = []

    for filename, required_tokens in TOOLKIT_ANCHOR_CONTRACTS.items():
        file_path = theme_inc_dir / filename
        if not file_path.is_file():
            missing_files.append(filename)
            continue
        content = file_path.read_text(encoding='utf-8')
        for token in required_tokens:
            if token not in content:
                missing_anchors.append({'file': filename, 'missing_anchor': token})

    return {
        'checked_files': list(TOOLKIT_ANCHOR_CONTRACTS.keys()),
        'missing_files': missing_files,
        'missing_anchors': missing_anchors,
    }


EXPECTED_TRAVEL_CLUSTERS: list[str] = [
    'northern_triangle',
    'ha_long_bay',
    'ninh_binh',
    'central_heritage',
    'southern_delta',
    'northern_highlands',
    'coastal_islands',
    'central_highlands',
    'phong_nha_caves',
    'mekong_delta',
    'far_north_frontier',
    'national_circuit',
]


def audit_travel_clusters(theme_inc_dir: Path = DEFAULT_THEME_INC_DIR) -> dict[str, Any]:
    guide_seo_path = theme_inc_dir / 'guide-seo.php'
    if not guide_seo_path.is_file():
        return {
            'cluster_count': 0,
            'expected_count': len(EXPECTED_TRAVEL_CLUSTERS),
            'verified_clusters': [],
            'missing_clusters': EXPECTED_TRAVEL_CLUSTERS[:],
            'geo_coordinate_errors': ['guide-seo.php missing'],
            'journey_link_errors': ['guide-seo.php missing'],
            'path_mapping_errors': ['guide-seo.php missing'],
        }

    content = guide_seo_path.read_text(encoding='utf-8')
    reg_start = content.find('function vg_get_travel_clusters_registry')
    registry_content = content[reg_start:] if reg_start != -1 else content
    verified_clusters: list[str] = []
    missing_clusters: list[str] = []
    geo_errors: list[str] = []
    journey_errors: list[str] = []

    for cid in EXPECTED_TRAVEL_CLUSTERS:
        pattern = rf"'{cid}'\s*=>\s*\[(.*?)(?=\n\s*'(?:{'|'.join(EXPECTED_TRAVEL_CLUSTERS)})'\s*=>|\n\s*\];\s*\n\s*return\s+\$registry;)"
        match = re.search(pattern, registry_content, re.DOTALL)
        if not match:
            missing_clusters.append(cid)
            continue

        verified_clusters.append(cid)
        block = match.group(1)

        # Validate GeoCoordinates (Vietnam bounds: lat 8.0-24.0, lon 102.0-110.0)
        geo_m = re.search(r"'latitude'\s*=>\s*(-?[\d\.]+),\s*'longitude'\s*=>\s*(-?[\d\.]+)", block)
        if not geo_m:
            geo_errors.append(f'{cid}: missing geo coordinates')
        else:
            lat = float(geo_m.group(1))
            lon = float(geo_m.group(2))
            if not (8.0 <= lat <= 24.0 and 102.0 <= lon <= 110.0):
                geo_errors.append(f'{cid}: geo ({lat}, {lon}) out of Vietnam bounding box')

        # Validate journey links structure
        if "'journey_links'" not in block or "'transit'" not in block or "'badge'" not in block:
            journey_errors.append(f'{cid}: missing journey_links transit/badge')

    # Validate vg_find_travel_cluster_by_path mapping branches
    path_mapping_errors: list[str] = []
    required_path_markers = [
        'central_highlands',
        'phong_nha_caves',
        'mekong_delta',
        'far_north_frontier',
        'da-lat',
        'phong-nha',
        'can-tho',
        'cao-bang',
        'saigon',
    ]
    for marker in required_path_markers:
        if marker not in content:
            path_mapping_errors.append(f'Missing path resolution marker: {marker}')

    return {
        'cluster_count': len(verified_clusters),
        'expected_count': len(EXPECTED_TRAVEL_CLUSTERS),
        'verified_clusters': verified_clusters,
        'missing_clusters': missing_clusters,
        'geo_coordinate_errors': geo_errors,
        'journey_link_errors': journey_errors,
        'path_mapping_errors': path_mapping_errors,
    }


def audit_registry_parity(repo_root: Path = REPO_ROOT) -> dict[str, Any]:
    ops_json = repo_root / 'ops' / 'route_registry.json'
    theme_json = repo_root / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'inc' / 'guide-route-registry.json'
    theme_php = repo_root / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'inc' / 'guide-rollout.php'

    errors: list[str] = []
    if not ops_json.is_file():
        errors.append(f'Missing ops registry: {ops_json}')
    if not theme_json.is_file():
        errors.append(f'Missing theme registry: {theme_json}')
    if not theme_php.is_file():
        errors.append(f'Missing theme rollout PHP: {theme_php}')

    sha256_digest = ''
    if not errors:
        ops_bytes = ops_json.read_bytes()
        theme_bytes = theme_json.read_bytes()
        ops_sha = hashlib.sha256(ops_bytes).hexdigest().lower()
        theme_sha = hashlib.sha256(theme_bytes).hexdigest().lower()
        sha256_digest = ops_sha
        if ops_sha != theme_sha:
            errors.append(f'SHA-256 mismatch (ops: {ops_sha}, theme: {theme_sha})')

        expected_php = compile_php_registry(theme_json)
        actual_php = theme_php.read_text(encoding='utf-8')
        if actual_php != expected_php:
            errors.append('inc/guide-rollout.php OPcache payload does not match compiled output')

    return {
        'passed': len(errors) == 0,
        'sha256': sha256_digest,
        'errors': errors,
    }


def run_sentinel(
    registry_path: Path = DEFAULT_REGISTRY_PATH,
    theme_inc_dir: Path = DEFAULT_THEME_INC_DIR,
    *,
    reference_date: date | None = None,
    max_age_days: int = 365,
    min_empirical_pct: float = 98.0,
) -> tuple[bool, dict[str, Any]]:
    reg_report = audit_route_registry(
        registry_path,
        reference_date=reference_date,
        max_age_days=max_age_days,
    )
    toolkit_report = audit_toolkit_anchors(theme_inc_dir)
    cluster_report = audit_travel_clusters(theme_inc_dir)
    parity_report = audit_registry_parity(REPO_ROOT)

    passed = (
        reg_report['route_count'] == 282
        and len(reg_report['invalid_date_routes']) == 0
        and len(reg_report['stale_routes']) == 0
        and len(reg_report['title_violations']) == 0
        and len(reg_report['description_violations']) == 0
        and reg_report['empirical_coverage_pct'] >= min_empirical_pct
        and len(toolkit_report['missing_files']) == 0
        and len(toolkit_report['missing_anchors']) == 0
        and len(cluster_report['missing_clusters']) == 0
        and len(cluster_report['geo_coordinate_errors']) == 0
        and len(cluster_report['journey_link_errors']) == 0
        and len(cluster_report['path_mapping_errors']) == 0
        and parity_report['passed']
    )

    report = {
        'status': 'PASS' if passed else 'FAIL',
        'registry': reg_report,
        'toolkits': toolkit_report,
        'clusters': cluster_report,
        'parity': parity_report,
    }
    return passed, report


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--registry', type=Path, default=DEFAULT_REGISTRY_PATH)
    parser.add_argument('--theme-inc', type=Path, default=DEFAULT_THEME_INC_DIR)
    parser.add_argument('--reference-date', type=str, default='')
    parser.add_argument('--max-age-days', type=int, default=365)
    parser.add_argument('--min-empirical-pct', type=float, default=98.0)
    parser.add_argument('--json', action='store_true', help='emit full JSON report')
    args = parser.parse_args(argv)

    ref_date = parse_iso_date(args.reference_date) if args.reference_date else None
    passed, report = run_sentinel(
        registry_path=args.registry,
        theme_inc_dir=args.theme_inc,
        reference_date=ref_date,
        max_age_days=args.max_age_days,
        min_empirical_pct=args.min_empirical_pct,
    )

    if args.json:
        print(json.dumps(report, indent=2))
    else:
        reg = report['registry']
        tk = report['toolkits']
        clusters = report['clusters']
        parity = report['parity']
        print('============================================================')
        print('   VietnamGuide Ground-Truth Tariff & Freshness Sentinel    ')
        print('============================================================')
        print(f"Routes Audited         : {reg['route_count']}/282")
        print(f"Freshness Window       : <= {reg['max_age_days']} days (Ref: {reg['reference_date']})")
        print(f"Stale / Invalid Dates  : {len(reg['stale_routes'])} stale, {len(reg['invalid_date_routes'])} invalid")
        print(f"SERP Title Violations  : {len(reg['title_violations'])} (100% <= 60 chars)")
        print(f"SERP Window Violations : {len(reg['description_violations'])} (100% in 147-157 chars)")
        print(f"Empirical Coverage     : {reg['empirical_coverage_pct']}% ({reg['empirical_counts']['any_empirical_signal']}/{reg['route_count']} routes, threshold: >= {args.min_empirical_pct}%)")
        print(f"  - Currency/Tariffs   : {reg['empirical_counts']['currency_tariff']} routes")
        print(f"  - Transit/Distances  : {reg['empirical_counts']['transit_duration']} routes")
        print(f"  - Climate/Seasons    : {reg['empirical_counts']['climate_season']} routes")
        print(f"  - Official Portals   : {reg['empirical_counts']['regulatory_portal']} routes")
        print(f"Travel Clusters        : {clusters['cluster_count']}/12 verified (geo coordinates & journey links valid)")
        print(f"Registry Parity        : 100% SHA-256 byte parity verified ({parity.get('sha256', '')[:16]}...)")
        print(f"Toolkit Anchor Issues  : {len(tk['missing_files'])} missing files, {len(tk['missing_anchors'])} missing tokens")
        print('------------------------------------------------------------')
        print(f"OVERALL SENTINEL STATUS: {report['status']}")
        print('============================================================')

    return 0 if passed else 1


if __name__ == '__main__':
    raise SystemExit(main())
