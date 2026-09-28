# -*- coding: utf-8 -*-
"""
Fast Content Quality Audit — dumps all published pages on VPS to JSON via WP-CLI,
downloads locally, runs Anti-AI Slop linter, and saves updated content_audit_results.json.
Execution time: ~15-20 seconds total!
"""

import sys
import os
import json
import re
import paramiko
import time

if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    sys.stderr.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text

VPS_HOST = "66.42.48.146"
VPS_PORT = 2209
VPS_USER = "root"
VPS_KEY = r"C:\Users\NCTaam\.ssh\deploy_bot_key"
WP_PATH = "/usr/local/lsws/vietnamguide.net/html"

SKIP_SLUGS = {
    "home", "plan", "destinations", "itineraries", "compare", "costs",
    "newsletter", "affiliate-disclosure", "about", "editorial-policy",
    "source-update-policy", "contact", "affiliate-review-policy",
    "privacy-policy", "terms-of-service",
}


def compute_quality_score(result):
    hls = result.get("hls_score", 100)
    cv = result.get("cv", 0.45)
    edi = result.get("edi", 0)
    word_count = result.get("word_count", 0)
    lex_div = result.get("lexical_diversity", 0)

    hls_component = hls * 0.40
    edi_norm = min(edi / 10.0, 1.0) if edi else 0
    edi_component = edi_norm * 100 * 0.25
    cv_norm = min(cv / 0.6, 1.0) if cv else 0
    cv_component = cv_norm * 100 * 0.15
    wc_norm = min(word_count / 3000, 1.0) if word_count else 0
    wc_component = wc_norm * 100 * 0.10
    ld_norm = min(lex_div / 0.7, 1.0) if lex_div else 0
    ld_component = ld_norm * 100 * 0.10

    return round(hls_component + edi_component + cv_component + wc_component + ld_component, 1)


def clean_text(raw_html):
    text = re.sub(r"<[^>]+>", " ", raw_html)
    text = re.sub(r"\s+", " ", text).strip()
    text = text.replace("&amp;", "&").replace("&lt;", "<").replace("&gt;", ">")
    text = text.replace("&quot;", '"').replace("&#039;", "'").replace("&nbsp;", " ")
    return text


def main():
    start_time = time.time()
    print("=" * 80)
    print("FAST CONTENT QUALITY AUDIT")
    print("=" * 80)

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(VPS_HOST, port=VPS_PORT, username=VPS_USER, key_filename=VPS_KEY)

    print("Step 1: Exporting all published pages on VPS to JSON...")
    cmd = (
        f"wp post list --post_type=page --post_status=publish "
        f"--fields=ID,post_title,post_name,post_content --format=json "
        f"--path={WP_PATH} --allow-root > /tmp/vg_all_posts.json"
    )
    stdin, stdout, stderr = client.exec_command(cmd)
    stdout.channel.recv_exit_status()

    print("Step 2: Downloading dump via SFTP...")
    sftp = client.open_sftp()
    local_dump = os.path.join(os.path.dirname(os.path.abspath(__file__)), "vg_all_posts.json")
    sftp.get("/tmp/vg_all_posts.json", local_dump)
    client.exec_command("rm -f /tmp/vg_all_posts.json")
    sftp.close()
    client.close()

    print(f"Downloaded dump: {os.path.getsize(local_dump) / 1024:.1f} KB")

    print("Step 3: Parsing and analyzing all content pillars...")
    with open(local_dump, "r", encoding="utf-8") as f:
        posts = json.load(f)

    results = []
    skipped = 0

    for post in posts:
        slug = post.get("post_name", "")
        pid = post.get("ID")
        title = post.get("post_title", "")
        content = post.get("post_content", "")

        if slug in SKIP_SLUGS:
            skipped += 1
            continue

        text = clean_text(content)
        if len(text) < 50:
            skipped += 1
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

    # Sort ascending by quality score
    results.sort(key=lambda x: x["quality_score"])

    # Save to ops/content_audit_results.json
    out_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_audit_results.json")
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(results, f, indent=2, ensure_ascii=False)

    # Print summary statistics
    total = len(results)
    passed_count = sum(1 for r in results if r["passed"])
    failed_count = total - passed_count
    pass_rate = (passed_count / total * 100) if total else 0

    avg_qs = sum(r["quality_score"] for r in results) / total if total else 0
    avg_hls = sum(r["hls_score"] for r in results) / total if total else 0
    avg_edi = sum(r["edi"] for r in results) / total if total else 0
    avg_cv = sum(r["cv"] for r in results) / total if total else 0

    hls_100 = sum(1 for r in results if r["hls_score"] == 100)
    hls_90_95 = sum(1 for r in results if 90 <= r["hls_score"] < 100)
    hls_80_85 = sum(1 for r in results if 80 <= r["hls_score"] < 90)
    hls_60_70 = sum(1 for r in results if 60 <= r["hls_score"] < 80)
    hls_under_60 = sum(1 for r in results if r["hls_score"] < 60)

    elapsed = time.time() - start_time
    print("\n" + "=" * 80)
    print("AUDIT SUMMARY (POST-DEPLOYMENT OF 88 ARTICLES)")
    print("=" * 80)
    print(f"Total Content Pillars Audited: {total} (Skipped {skipped} utility/legal pages)")
    print(f"Passed Linter:                 {passed_count}/{total} ({pass_rate:.1f}%)")
    print(f"Failed Linter:                 {failed_count}/{total} ({100-pass_rate:.1f}%)")
    print(f"Average Quality Score:         {avg_qs:.1f}/100")
    print(f"Average HLS Score:             {avg_hls:.1f}/100")
    print(f"Average EDI:                   {avg_edi:.1f}")
    print(f"Average CV:                    {avg_cv:.2f}")
    print("-" * 80)
    print("HLS Distribution:")
    print(f"  HLS = 100 (Perfect):         {hls_100} ({hls_100/total*100:.1f}%)")
    print(f"  HLS 90-95:                   {hls_90_95}")
    print(f"  HLS 80-85:                   {hls_80_85}")
    print(f"  HLS 60-70:                   {hls_60_70}")
    print(f"  HLS < 60 (Critical):         {hls_under_60}")
    print(f"Completed in {elapsed:.1f} seconds.")
    print("=" * 80)


if __name__ == "__main__":
    main()
