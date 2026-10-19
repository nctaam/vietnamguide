#!/usr/bin/env python3
"""Antigravity Local Search Client (http://localhost:20128/v1/search).

Provides a zero-dependency Python client and CLI for querying the local
Antigravity search service (web, image, news) to verify ground-truth travel
facts, statutory tariffs, and image provenance across VietnamGuide.
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import urllib.error
import urllib.request
from typing import Any

DEFAULT_SEARCH_URL = os.environ.get("AG_SEARCH_URL", "http://localhost:20128/v1/search")
DEFAULT_API_KEY = os.environ.get("AG_SEARCH_API_KEY", "sk-4fe19abbb8c5098c-b3nkl4-16b15a4a")
DEFAULT_MODEL = os.environ.get("AG_SEARCH_MODEL", "ag")


def search_ag(
    query: str,
    search_type: str = "web",
    max_results: int = 5,
    url: str = DEFAULT_SEARCH_URL,
    api_key: str = DEFAULT_API_KEY,
    model: str = DEFAULT_MODEL,
    timeout: int = 25,
) -> dict[str, Any]:
    """Execute a search request against the local Antigravity Search endpoint."""
    payload = {
        "model": model,
        "query": query,
        "search_type": search_type,
        "max_results": max_results,
    }
    req = urllib.request.Request(
        url,
        data=json.dumps(payload).encode("utf-8"),
        headers={
            "Content-Type": "application/json",
            "Authorization": f"Bearer {api_key}",
        },
        method="POST",
    )
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        raw = resp.read().decode("utf-8", errors="replace")
        return json.loads(raw)


def verify_wikimedia_thumb_url(url: str) -> tuple[bool, str]:
    """Cryptographically verify the MD5 directory prefix /<h[0]>/<h[0:2]>/ of a Wikimedia Commons thumb URL."""
    import hashlib
    import re
    from urllib.parse import unquote

    match = re.search(
        r"https://upload\.wikimedia\.org/wikipedia/commons/thumb/([0-9a-f])/([0-9a-f]{2})/([^/\"\s]+)/",
        url,
    )
    if not match:
        return False, "Not a standard Wikimedia Commons thumb URL"
    d1, d2, encoded_filename = match.groups()
    decoded_filename = unquote(encoded_filename).replace(" ", "_")
    md5_hex = hashlib.md5(decoded_filename.encode("utf-8")).hexdigest()
    expected_d1, expected_d2 = md5_hex[0], md5_hex[:2]
    if (d1, d2) != (expected_d1, expected_d2):
        return False, f"MD5 mismatch for {decoded_filename}: found /{d1}/{d2}/, expected /{expected_d1}/{expected_d2}/"
    return True, f"Valid MD5 /{expected_d1}/{expected_d2}/ for {decoded_filename}"


def main() -> int:
    parser = argparse.ArgumentParser(description="Query Antigravity Local Search API (localhost:20128/v1/search)")
    parser.add_argument("--query", "-q", required=True, help="Search query string")
    parser.add_argument(
        "--type",
        "-t",
        default="web",
        choices=["web", "image", "images", "news"],
        help="Search type (default: web)",
    )
    parser.add_argument("--max-results", "-n", type=int, default=5, help="Max results (0 for default)")
    parser.add_argument("--json", action="store_true", help="Output raw JSON")
    args = parser.parse_args()

    try:
        res = search_ag(query=args.query, search_type=args.type, max_results=args.max_results)
    except urllib.error.URLError as exc:
        print(f"[ERROR] Search request failed: {exc}", file=sys.stderr)
        return 1

    if args.json:
        print(json.dumps(res, indent=2, ensure_ascii=True))
        return 0

    print(f"Query: {res.get('query', args.query)} (Provider: {res.get('provider', 'unknown')})")
    results = res.get("results") or []
    print(f"Results: {len(results)}")
    for idx, item in enumerate(results, start=1):
        title = item.get("title") or "untitled"
        url = item.get("url") or ""
        snippet = (item.get("snippet") or item.get("content") or "").replace("\n", " ").strip()
        safe_snip = snippet.encode("ascii", "replace").decode("ascii")
        print(f"  [{idx}] {title}")
        if url:
            print(f"      URL: {url}")
        if safe_snip:
            print(f"      Snippet: {safe_snip[:400]}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
