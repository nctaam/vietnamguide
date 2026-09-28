# -*- coding: utf-8 -*-
"""
Content Quality Audit — fetch all published pages from VPS,
run through Anti-AI Slop linter, rank by quality score.
"""

import sys
import os
import json
import re

# Fix Windows console encoding
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    sys.stderr.reconfigure(encoding="utf-8", errors="replace")
import csv
import io
import paramiko

# Add ops to path for linter import
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text

# === Config ===
VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"

# Utility/legal pages to skip (not content pillars)
SKIP_SLUGS = {
    "home", "plan", "destinations", "itineraries", "compare", "costs",
    "newsletter", "affiliate-disclosure", "about", "editorial-policy",
    "source-update-policy", "contact", "affiliate-review-policy",
    "privacy-policy", "terms-of-service",
}


def ssh_connect():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)
    return client


def ssh_exec(client, cmd):
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode("utf-8", errors="replace")
    err = stderr.read().decode("utf-8", errors="replace")
    return out, err


def get_page_list(client):
    """Get list of all published pages with ID, title, slug."""
    cmd = (
        f"wp post list --post_type=page --post_status=publish "
        f"--fields=ID,post_title,post_name --format=csv "
        f"--path={WP_PATH} --allow-root 2>/dev/null"
    )
    out, err = ssh_exec(client, cmd)
    pages = []
    reader = csv.DictReader(io.StringIO(out))
    for row in reader:
        slug = row.get("post_name", "")
        if slug in SKIP_SLUGS:
            continue
        pages.append({
            "id": row["ID"],
            "title": row["post_title"],
            "slug": slug,
        })
    return pages


def get_page_content(client, page_id):
    """Fetch raw content of a page by ID, strip HTML tags for text analysis."""
    cmd = (
        f"wp post get {page_id} --field=content "
        f"--path={WP_PATH} --allow-root 2>/dev/null"
    )
    out, err = ssh_exec(client, cmd)
    # Strip HTML tags for text analysis
    text = re.sub(r"<[^>]+>", " ", out)
    text = re.sub(r"\s+", " ", text).strip()
    # Decode HTML entities
    text = text.replace("&amp;", "&").replace("&lt;", "<").replace("&gt;", ">")
    text = text.replace("&quot;", '"').replace("&#039;", "'").replace("&nbsp;", " ")
    return text


def compute_quality_score(result):
    """
    Compute a composite quality score (0-100) from linter metrics.
    Higher = better quality.
    """
    hls = result.get("hls_score", 100)
    cv = result.get("cv", 0.45)
    edi = result.get("edi", 0)
    word_count = result.get("word_count", 0)
    lex_div = result.get("lexical_diversity", 0)
    flesch = result.get("flesch_reading_ease", 0)

    # HLS component (40% weight) — already 0-100
    hls_component = hls * 0.40

    # EDI component (25% weight) — target >= 5.0, cap at 10
    edi_norm = min(edi / 10.0, 1.0) if edi else 0
    edi_component = edi_norm * 100 * 0.25

    # CV component (15% weight) — target >= 0.45, cap at 0.6
    cv_norm = min(cv / 0.6, 1.0) if cv else 0
    cv_component = cv_norm * 100 * 0.15

    # Word count component (10% weight) — target >= 1500, cap at 3000
    wc_norm = min(word_count / 3000, 1.0) if word_count else 0
    wc_component = wc_norm * 100 * 0.10

    # Lexical diversity (10% weight) — target >= 0.4, cap at 0.7
    ld_norm = min(lex_div / 0.7, 1.0) if lex_div else 0
    ld_component = ld_norm * 100 * 0.10

    return round(hls_component + edi_component + cv_component + wc_component + ld_component, 1)


def main():
    print("=" * 80)
    print("VIETNAMGUIDE CONTENT QUALITY AUDIT")
    print("=" * 80)

    client = ssh_connect()
    pages = get_page_list(client)
    print(f"\nFound {len(pages)} content pillars to audit.\n")

    results = []
    batch_size = 5
    total = len(pages)

    for i, page in enumerate(pages, 1):
        slug = page["slug"]
        pid = page["id"]
        title = page["title"]

        print(f"  [{i}/{total}] Auditing: {title[:60]}...", end="", flush=True)

        try:
            text = get_page_content(client, pid)
            if len(text) < 50:
                print(" SKIP (empty/too short)")
                continue

            analysis = analyze_text(text, source_name=slug)
            quality_score = compute_quality_score(analysis)

            total_violations = sum([
                analysis.get(f"tier{t}_count", 0) for t in range(1, 13)
            ]) + analysis.get("passive_count", 0)

            results.append({
                "id": pid,
                "title": title,
                "slug": slug,
                "quality_score": quality_score,
                "hls_score": analysis.get("hls_score", 0),
                "edi": analysis.get("edi", 0),
                "cv": analysis.get("cv", 0),
                "word_count": analysis.get("word_count", 0),
                "lexical_diversity": analysis.get("lexical_diversity", 0),
                "flesch_reading_ease": analysis.get("flesch_reading_ease", 0),
                "total_violations": total_violations,
                "passed": analysis.get("passed", False),
            })
            status = "PASS" if analysis.get("passed", False) else "FAIL"
            print(f" {status} Q={quality_score} HLS={analysis.get('hls_score',0)} EDI={analysis.get('edi',0):.1f} CV={analysis.get('cv',0):.2f} W={analysis.get('word_count',0)}")
        except Exception as e:
            print(f" ERROR: {e}")

    client.close()

    # Sort by quality score ascending (worst first)
    results.sort(key=lambda x: x["quality_score"])

    # Save full results
    output_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_audit_results.json")
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(results, f, ensure_ascii=False, indent=2)

    # Print summary
    print("\n" + "=" * 80)
    print("AUDIT SUMMARY")
    print("=" * 80)
    print(f"Total content pillars audited: {len(results)}")
    if results:
        avg_score = sum(r["quality_score"] for r in results) / len(results)
        avg_hls = sum(r["hls_score"] for r in results) / len(results)
        avg_edi = sum(r["edi"] for r in results) / len(results)
        avg_cv = sum(r["cv"] for r in results) / len(results)
        avg_wc = sum(r["word_count"] for r in results) / len(results)
        passed = sum(1 for r in results if r["passed"])

        print(f"Passed linter: {passed}/{len(results)} ({100*passed/len(results):.0f}%)")
        print(f"Average quality score: {avg_score:.1f}/100")
        print(f"Average HLS: {avg_hls:.0f}")
        print(f"Average EDI: {avg_edi:.1f}")
        print(f"Average CV: {avg_cv:.2f}")
        print(f"Average word count: {avg_wc:.0f}")

        # Bottom 20 worst articles
        print(f"\n{'='*80}")
        print("BOTTOM 30 — PRIORITY OPTIMIZATION TARGETS")
        print(f"{'='*80}")
        print(f"{'#':<4} {'Score':<7} {'HLS':<5} {'EDI':<6} {'CV':<6} {'Words':<7} {'Viol':<6} {'Title'}")
        print("-" * 100)
        for i, r in enumerate(results[:30], 1):
            print(f"{i:<4} {r['quality_score']:<7} {r['hls_score']:<5} {r['edi']:<6.1f} {r['cv']:<6.2f} {r['word_count']:<7} {r['total_violations']:<6} {r['title'][:50]}")

        # Categorize issues
        low_edi = [r for r in results if r["edi"] < 5.0]
        low_cv = [r for r in results if r["cv"] < 0.40]
        low_wc = [r for r in results if r["word_count"] < 1000]
        high_violations = [r for r in results if r["total_violations"] > 5]
        not_passed = [r for r in results if not r["passed"]]

        print(f"\n{'='*80}")
        print("ISSUE CATEGORIES")
        print(f"{'='*80}")
        print(f"Low EDI (< 5.0):        {len(low_edi)} articles")
        print(f"Low CV (< 0.40):        {len(low_cv)} articles")
        print(f"Thin content (< 1000w): {len(low_wc)} articles")
        print(f"High violations (> 5):  {len(high_violations)} articles")
        print(f"Failed linter:          {len(not_passed)} articles")

    print(f"\nFull results saved to: {output_path}")


if __name__ == "__main__":
    main()
