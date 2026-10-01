#!/usr/bin/env python3
"""VietnamGuide Guide Experience Rollout Manager.

Provides CLI commands and programmatic utilities to orchestrate the phased
rollout of the 195 legacy content routes to the enhanced Guide Shell.
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path
import sys
from typing import Any, Dict, List, Optional


DEFAULT_REGISTRY_PATH = Path("ops/route_registry.json")
DEFAULT_MANIFEST_PATH = Path("docs/baselines/2026-09-28-rollout-batches.json")


def load_manifest(manifest_path: Path | str = DEFAULT_MANIFEST_PATH) -> dict[str, Any]:
    """Load the rollout manifest JSON."""
    path = Path(manifest_path)
    if not path.is_file():
        raise FileNotFoundError(f"Rollout manifest not found: {path}")
    with path.open("r", encoding="utf-8") as f:
        return json.load(f)


def load_registry(registry_path: Path | str = DEFAULT_REGISTRY_PATH) -> dict[str, Any]:
    """Load the canonical route registry JSON."""
    path = Path(registry_path)
    if not path.is_file():
        raise FileNotFoundError(f"Route registry not found: {path}")
    with path.open("r", encoding="utf-8") as f:
        return json.load(f)


def get_rollout_status(
    manifest_path: Path | str = DEFAULT_MANIFEST_PATH,
    registry_path: Path | str = DEFAULT_REGISTRY_PATH,
) -> dict[str, Any]:
    """Calculate and return full rollout progress statistics."""
    manifest = load_manifest(manifest_path)
    registry = load_registry(registry_path)

    total_routes = registry.get("route_count", len(registry.get("routes", [])))
    batches = manifest.get("batches", [])

    pilot_count = manifest.get("summary", {}).get("pilot_route_count", 87)
    pending_count = manifest.get("summary", {}).get("pending_route_count", 195)

    batch_stats = []
    cumulative = pilot_count
    for b in batches:
        count = b.get("count", len(b.get("paths", [])))
        cumulative += count
        pct = (cumulative / total_routes) * 100 if total_routes else 0
        batch_stats.append({
            "id": b.get("id"),
            "order": b.get("order"),
            "count": count,
            "cumulative_active": cumulative,
            "percentage": round(pct, 2),
            "purpose": b.get("purpose", ""),
        })

    return {
        "total_canonical_routes": total_routes,
        "pilot_routes": pilot_count,
        "pending_routes": pending_count,
        "batches": batch_stats,
    }


def verify_batches(
    manifest_path: Path | str = DEFAULT_MANIFEST_PATH,
    registry_path: Path | str = DEFAULT_REGISTRY_PATH,
) -> dict[str, Any]:
    """Verify that every route in the manifest exists in the registry with target template 'guide'."""
    manifest = load_manifest(manifest_path)
    registry = load_registry(registry_path)

    routes_by_path = {r["path"]: r for r in registry.get("routes", [])}
    errors = []
    seen_paths = set()

    for batch in manifest.get("batches", []):
        batch_id = batch.get("id", "unknown")
        for path in batch.get("paths", []):
            if path in seen_paths:
                errors.append(f"Duplicate path across batches: {path} in {batch_id}")
            seen_paths.add(path)

            record = routes_by_path.get(path)
            if not record:
                errors.append(f"Route not found in registry: {path} (in {batch_id})")
                continue

            if record.get("status") != "published":
                errors.append(f"Route is not published: {path} (status: {record.get('status')})")

            if record.get("template") != "guide":
                errors.append(f"Route target template is not guide: {path} ({record.get('template')})")

    return {
        "valid": len(errors) == 0,
        "verified_route_count": len(seen_paths),
        "expected_route_count": manifest.get("summary", {}).get("pending_route_count", 195),
        "errors": errors,
    }


def generate_php_config(
    stage: str,
    manifest_path: Path | str = DEFAULT_MANIFEST_PATH,
) -> str:
    """Generate the PHP snippet for VG_GUIDE_REGISTRY_ROLLOUT in wp-config.php."""
    normalized_stage = stage.lower().strip()

    if normalized_stage == "rollback":
        return (
            "// VietnamGuide Rollout: Instant Rollback to Pilot\n"
            "// Only the initial 87 pilot routes will render via Guide Shell.\n"
            "define('VG_GUIDE_REGISTRY_ROLLOUT', []);\n"
        )

    if normalized_stage in ("all", "batch_3", "full"):
        return (
            "// VietnamGuide Rollout: 100% Sitewide Conversion (All 282 Routes)\n"
            "// Enables Guide Shell across all canonical destinations, itineraries, comparisons, and plan guides.\n"
            "define('VG_GUIDE_REGISTRY_ROLLOUT', true);\n"
        )

    manifest = load_manifest(manifest_path)
    batches = {b["id"]: b for b in manifest.get("batches", [])}

    active_paths: list[str] = []
    description = ""

    if normalized_stage in ("batch_1", "1", "canary"):
        b1 = batches.get("legacy-batch-1")
        if not b1:
            raise ValueError("Batch 1 not found in manifest")
        active_paths = b1.get("paths", [])
        description = "Batch 1 Canary (50 Routes: Comparisons + Central Vietnam Destinations)\n// Active Guides: 87 Pilot + 50 Batch 1 = 137 routes (48.6%)"

    elif normalized_stage in ("batch_2", "2"):
        b1 = batches.get("legacy-batch-1")
        b2 = batches.get("legacy-batch-2")
        if not b1 or not b2:
            raise ValueError("Batches 1 or 2 not found in manifest")
        active_paths = b1.get("paths", []) + b2.get("paths", [])
        description = "Batch 1 + Batch 2 (100 Routes: 100% Destinations, Itineraries, Comparisons)\n// Active Guides: 87 Pilot + 100 Batches = 187 routes (66.3%)"

    else:
        raise ValueError(f"Unknown rollout stage: {stage}. Choose from: batch_1, batch_2, batch_3, all, rollback")

    lines = [
        f"// VietnamGuide Rollout: {description}",
        "define('VG_GUIDE_REGISTRY_ROLLOUT', [",
    ]
    for p in active_paths:
        lines.append(f"    '{p}',")
    lines.append("]);\n")
    return "\n".join(lines)


def main(argv: Optional[list[str]] = None) -> int:
    parser = argparse.ArgumentParser(description="VietnamGuide Rollout Manager")
    parser.add_argument("--status", action="store_true", help="Display current rollout progress and batch roadmap")
    parser.add_argument("--verify", action="store_true", help="Verify rollout manifest integrity against registry")
    parser.add_argument(
        "--generate-config",
        choices=["batch_1", "batch_2", "batch_3", "all", "rollback"],
        help="Generate wp-config.php definition for the specified rollout stage",
    )
    parser.add_argument("--output", help="Optional file path to write generated PHP configuration")

    args = parser.parse_args(argv)

    if args.status:
        status = get_rollout_status()
        print("\n" + "=" * 60)
        print("   VIETNAMGUIDE GUIDE SHELL ROLLOUT PROGRESS REPORT   ")
        print("=" * 60)
        print(f" Total Canonical Routes: {status['total_canonical_routes']}")
        print(f" Pilot Active Routes:    {status['pilot_routes']} (30.85%)")
        print(f" Pending Legacy Routes:  {status['pending_routes']} (69.15%)")
        print("-" * 60)
        print(f" {'Batch':<18} | {'Count':<6} | {'Cumulative':<10} | {'Sitewide %':<10}")
        print("-" * 60)
        print(f" {'Initial Pilot':<18} | {status['pilot_routes']:<6} | {status['pilot_routes']:<10} | 30.85%")
        for b in status["batches"]:
            print(f" {b['id']:<18} | {b['count']:<6} | {b['cumulative_active']:<10} | {b['percentage']}% ({b['purpose']})")
        print("=" * 60 + "\n")
        return 0

    if args.verify:
        result = verify_batches()
        if result["valid"]:
            print(f"[OK] Rollout manifest is 100% valid! {result['verified_route_count']}/{result['expected_route_count']} routes verified.")
            return 0
        else:
            print(f"[ERROR] Rollout manifest validation failed with {len(result['errors'])} errors:")
            for err in result["errors"]:
                print(f"  - {err}")
            return 1

    if args.generate_config:
        config = generate_php_config(args.generate_config)
        if args.output:
            out_path = Path(args.output)
            out_path.parent.mkdir(parents=True, exist_ok=True)
            out_path.write_text(config, encoding="utf-8")
            print(f"[OK] Configuration written to {out_path}")
        else:
            print(config)
        return 0

    parser.print_help()
    return 0


if __name__ == "__main__":
    sys.exit(main())
