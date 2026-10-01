"""Canonical route contract helpers for VietnamGuide operations tooling.

The functions in this module deliberately have no WordPress or network
dependency.  They validate the route registry before it is consumed by
theme routing, AIO generation, or public verification jobs.
"""

from __future__ import annotations

from collections.abc import Iterable, Mapping
import json
from pathlib import Path
from urllib.parse import unquote, urlsplit


ROUTE_TYPES = {
    "destinations": "destination",
    "itineraries": "itinerary",
    "compare": "comparison",
    "plan": "practical",
}
ALLOWED_STATUSES = {"published", "draft", "archived"}
ALLOWED_TEMPLATES = {"guide", "legacy", "hub", "tool"}


def load_registry(path: str | Path) -> list[dict[str, object]]:
    """Load a route registry JSON artifact and return its route records."""

    registry_path = Path(path)
    try:
        payload = json.loads(registry_path.read_text(encoding="utf-8"))
    except FileNotFoundError as exc:
        raise ValueError(f"registry file not found: {registry_path}") from exc
    except json.JSONDecodeError as exc:
        raise ValueError(f"registry JSON is invalid: {registry_path}: {exc}") from exc

    if not isinstance(payload, Mapping):
        raise ValueError("registry must be an object with a routes array")
    if payload.get("schema_version") != 1:
        raise ValueError("registry schema_version must be 1")
    route_count = payload.get("route_count")
    if isinstance(route_count, bool) or not isinstance(route_count, int):
        raise ValueError("registry route_count must be an integer")
    records = payload.get("routes")
    if not isinstance(records, list):
        raise ValueError("registry routes must be a list")
    if route_count != len(records):
        raise ValueError("registry route_count does not match routes")
    if not all(isinstance(record, dict) for record in records):
        raise ValueError("registry routes must contain only objects")

    return records


def load_rollout_manifest(path: str | Path) -> dict[str, object]:
    """Load a deterministic staging rollout manifest."""

    manifest_path = Path(path)
    try:
        payload = json.loads(manifest_path.read_text(encoding="utf-8"))
    except FileNotFoundError as exc:
        raise ValueError(f"rollout manifest not found: {manifest_path}") from exc
    except json.JSONDecodeError as exc:
        raise ValueError(f"rollout manifest JSON is invalid: {manifest_path}: {exc}") from exc

    if not isinstance(payload, dict):
        raise ValueError("rollout manifest must be a JSON object")

    return payload


def route_set_diff(expected: Iterable[str], actual: Iterable[str]) -> tuple[set[str], set[str]]:
    """Return normalized paths missing from, and extra to, the expected set."""

    expected_paths = {normalize_path(path) for path in expected}
    actual_paths = {normalize_path(path) for path in actual}
    return expected_paths - actual_paths, actual_paths - expected_paths


def validate_rollout_manifest(
    registry: Iterable[Mapping[str, object]],
    manifest: Mapping[str, object],
) -> list[str]:
    """Validate that rollout batches cover each pending route exactly once."""

    errors: list[str] = []
    if not isinstance(manifest, Mapping):
        return ["rollout manifest must be an object"]

    schema_version = manifest.get("schema_version", 1)
    if schema_version != 1:
        errors.append(f"unsupported rollout manifest schema: {schema_version!r}")

    pending_paths: set[str] = set()
    registry_paths: set[str] = set()
    for record in registry:
        if not isinstance(record, Mapping):
            continue
        raw_path = record.get("path")
        try:
            path = normalize_path(raw_path)  # type: ignore[arg-type]
        except ValueError:
            continue
        registry_paths.add(path)
        if (
            record.get("status") == "published"
            and record.get("template") == "guide"
            and record.get("current_template") == "legacy"
        ):
            pending_paths.add(path)

    batches = manifest.get("batches")
    if not isinstance(batches, list):
        return errors + ["rollout manifest batches must be a list"]

    seen_batch_ids: set[str] = set()
    seen_batch_orders: set[int] = set()
    seen_paths: set[str] = set()
    for index, batch in enumerate(batches):
        if not isinstance(batch, Mapping):
            errors.append(f"batch {index}: expected an object")
            continue

        batch_id = batch.get("id")
        if not isinstance(batch_id, str) or not batch_id.strip():
            errors.append(f"batch {index}: missing id")
        elif batch_id in seen_batch_ids:
            errors.append(f"duplicate rollout batch: {batch_id}")
        else:
            seen_batch_ids.add(batch_id)

        order = batch.get("order")
        if isinstance(order, bool) or not isinstance(order, int) or order <= 0:
            errors.append(f"batch {batch_id or index}: invalid order")
        elif order in seen_batch_orders:
            errors.append(f"duplicate rollout order: {order}")
        else:
            seen_batch_orders.add(order)

        if batch.get("from_template") != "legacy":
            errors.append(f"batch {batch_id or index}: expected from_template=legacy")
        if batch.get("target_template") != "guide":
            errors.append(f"batch {batch_id or index}: expected target_template=guide")
        if not isinstance(batch.get("purpose"), str) or not batch["purpose"].strip():
            errors.append(f"batch {batch_id or index}: missing purpose")

        paths = batch.get("paths")
        if not isinstance(paths, list):
            errors.append(f"batch {index}: paths must be a list")
            continue

        count = batch.get("count")
        if isinstance(count, bool) or not isinstance(count, int) or count != len(paths):
            errors.append(f"batch {batch_id or index}: count does not match paths")

        for raw_path in paths:
            try:
                path = normalize_path(raw_path)  # type: ignore[arg-type]
            except ValueError as exc:
                errors.append(f"invalid rollout path: {raw_path!r}: {exc}")
                continue

            if path in seen_paths:
                errors.append(f"duplicate rollout path: {path}")
            seen_paths.add(path)
            if path not in registry_paths:
                errors.append(f"unknown rollout path: {path}")

    if seen_paths != pending_paths:
        errors.append("rollout manifest path coverage mismatch")

    summary = manifest.get("summary")
    if isinstance(summary, Mapping):
        expected_count = summary.get("pending_route_count")
        if expected_count is not None and expected_count != len(pending_paths):
            errors.append("rollout summary pending_route_count mismatch")
        batch_count = summary.get("batch_count")
        if batch_count is not None and batch_count != len(batches):
            errors.append("rollout summary batch_count mismatch")
        batch_sizes = summary.get("batch_sizes")
        actual_sizes = [batch.get("count") for batch in batches if isinstance(batch, Mapping)]
        if batch_sizes is not None and batch_sizes != actual_sizes:
            errors.append("rollout summary batch_sizes mismatch")

    return errors


def normalize_path(value: str) -> str:
    """Return a canonical route path without origin, query, fragment or slashes.

    The registry stores paths relative to the site root.  URL decoding is
    applied once so equivalent encoded and unencoded paths compare equally.
    Empty or non-string values are rejected rather than silently becoming the
    site root.
    """

    if not isinstance(value, str):
        raise ValueError("path must be a string")

    raw = value.strip()
    if not raw:
        raise ValueError("path must not be empty")

    parsed = urlsplit(raw)
    path = unquote(parsed.path)
    normalized = path.strip("/")
    if not normalized:
        raise ValueError("path must not be the site root")

    segments = normalized.split("/")
    if (
        any(not segment or segment in {".", ".."} for segment in segments)
        or "\\" in normalized
        or any(any(character.isspace() for character in segment) for segment in segments)
    ):
        raise ValueError("path contains ambiguous segments")

    return normalized


def classify_path(path: str) -> str | None:
    """Return the registry route type for a supported content path."""

    try:
        normalized = normalize_path(path)
    except ValueError:
        return None

    segments = normalized.split("/", 1)
    if len(segments) != 2 or not segments[1]:
        return None

    return ROUTE_TYPES.get(segments[0])


def validate_registry(records: Iterable[Mapping[str, object]]) -> list[str]:
    """Validate registry records and return stable, human-readable errors."""

    errors: list[str] = []
    seen: set[str] = set()

    if isinstance(records, (str, bytes)):
        return ["registry must be an iterable of mapping records"]

    try:
        iterator = iter(records)
    except TypeError:
        return ["registry must be an iterable of mapping records"]

    for index, record in enumerate(iterator):
        if not isinstance(record, Mapping):
            errors.append(f"record {index}: expected an object")
            continue

        raw_path = record.get("path")
        try:
            path = normalize_path(raw_path)  # type: ignore[arg-type]
        except ValueError as exc:
            errors.append(f"record {index}: invalid path: {exc}")
            continue

        if path in seen:
            errors.append(f"duplicate path: {path}")
        seen.add(path)

        route_type = classify_path(path)
        if route_type is None:
            errors.append(f"unsupported route path: {path}")
        elif record.get("type") != route_type:
            errors.append(
                f"type mismatch: {path}: expected {route_type}, got {record.get('type')!r}"
            )

        for field in (
            "title",
            "description",
            "status",
            "template",
            "current_template",
            "parent",
            "last_reviewed",
            "source",
            "content_owner",
        ):
            value = record.get(field)
            if not isinstance(value, str) or not value.strip():
                errors.append(f"missing {field}: {path}")

        status = record.get("status")
        if isinstance(status, str) and status not in ALLOWED_STATUSES:
            errors.append(f"invalid status: {path}: {status}")

        template = record.get("template")
        if isinstance(template, str) and template not in ALLOWED_TEMPLATES:
            errors.append(f"invalid template: {path}: {template}")

        current_template = record.get("current_template")
        if isinstance(current_template, str) and current_template not in ALLOWED_TEMPLATES:
            errors.append(f"invalid current_template: {path}: {current_template}")

    return errors
