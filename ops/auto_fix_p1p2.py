# -*- coding: utf-8 -*-
"""Auto-fix ALL articles in content_fix/ that are not yet passing."""
import sys, os, re, json
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from anti_ai_slop_linter import analyze_text
from auto_fix_content import (
    fix_strong_labels, fix_skip_if_bigrams, fix_use_bigrams,
    fix_sentence_openers, fix_paragraph_openers, strip_html_for_lint
)

CONTENT_FIX_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "content_fix")

# All P1+P2 articles with IDs
ALL_ARTICLES = [
    # P1 (HLS 70-85)
    ("tam-coc-travel-guide", 418), ("da-nang-vs-hoi-an", 209), ("ninh-binh-travel-guide", 190),
    ("how-to-get-to-con-dao-flight-vs-ferry", 1139), ("where-to-stay-in-mui-ne", 925),
    ("where-to-stay-in-meo-vac", 1070), ("ho-chi-minh-city-to-can-tho-transport", 927),
    ("best-day-trips-from-hanoi", 309), ("old-quarter-vs-french-quarter-vs-west-lake", 301),
    ("phu-quoc-vs-nha-trang", 241), ("bai-tu-long-bay-guide", 201), ("trang-an-vs-tam-coc", 336),
    ("vietnam-rainy-season-flexible-route", 482), ("where-to-stay-in-ho-chi-minh-city", 281),
    ("hanoi-to-sapa-transport", 520), ("hoi-an-vs-hue", 227), ("ha-long-bay-travel-guide", 195),
    ("where-to-stay-in-ben-tre", 1233), ("where-to-stay-in-phu-quoc", 857),
    ("ninh-binh-without-rushing", 478), ("where-to-stay-in-chau-doc", 1203),
    ("quy-nhon-travel-guide", 250), ("hanoi-in-2-days", 320),
    ("best-vietnam-cities-for-first-time-visitors", 497), ("hanoi-travel-guide", 287),
    ("tet-in-vietnam-travel-guide", 496), ("ha-long-bay-vs-lan-ha-bay", 21),
    ("hoi-an-ancient-town-guide", 499), ("mai-chau-to-pu-luong-transport", 1499),
    ("hanoi-to-phong-nha-transport", 823), ("hue-street-food-guide", 791),
    # P2 (HLS 90)
    ("what-to-pack-for-vietnam-region-season", 475), ("best-time-to-visit-vietnam", 14),
    ("cu-chi-tunnels-vs-mekong-delta-day-trip", 279), ("hanoi-first-time-visitor-mistakes", 477),
    ("hanoi-to-ha-giang-transport", 521), ("ly-son-travel-guide", 257),
    ("phong-nha-travel-guide", 503), ("da-nang-travel-guide", 213), ("21-days-in-vietnam", 224),
    ("ho-chi-minh-city-travel-guide", 262), ("northwest-vietnam-itinerary", 1069),
    ("pu-luong-trekking-routes-guide", 1037), ("where-to-stay-in-da-lat", 888),
    ("ha-long-bay-day-trip-vs-overnight-cruise", 682), ("where-to-stay-in-hoi-an", 681),
    ("safety-scams-vietnam", 181), ("vietnam-in-april", 685), ("cat-ba-travel-guide", 198),
    ("hanoi-airport-to-old-quarter", 316), ("pleiku-to-kon-tum-transport", 1274),
    ("vietnam-in-may", 721), ("cat-ba-to-ninh-binh-transport", 1273),
    ("ha-giang-easy-rider-vs-self-drive", 523), ("nha-trang-travel-guide", 244),
    ("hanoi-to-ninh-binh-transport", 331), ("ninh-binh-day-trip-vs-overnight", 326),
    ("ha-giang-loop-planning-guide", 519), ("best-things-to-do-in-hanoi", 173),
    ("14-days-in-vietnam", 20), ("where-to-stay-in-hanoi", 294),
    ("ha-long-bay-cruise-questions-before-booking", 479), ("where-to-stay-in-ninh-binh", 341),
    ("vietnam-food-safety-street-food-etiquette", 480), ("best-islands-in-vietnam", 231),
]


def fix_article(slug):
    html_path = os.path.join(CONTENT_FIX_DIR, f"{slug}.html")
    if not os.path.exists(html_path):
        return None, 0
    with open(html_path, "r", encoding="utf-8") as f:
        html = f.read()
    
    total_changes = 0
    for fixer in [fix_strong_labels, fix_skip_if_bigrams, fix_use_bigrams, fix_sentence_openers, fix_paragraph_openers]:
        html, c = fixer(html)
        total_changes += c
    
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html)
    
    text = strip_html_for_lint(html)
    result = analyze_text(text, source_name=slug)
    return result, total_changes


def main():
    print("=" * 80)
    print(f"AUTO-FIX P1+P2 — {len(ALL_ARTICLES)} articles")
    print("=" * 80)
    
    passed = []
    still_failing = []
    
    for slug, pid in ALL_ARTICLES:
        result, changes = fix_article(slug)
        if result is None:
            continue
        
        hls = result.get("hls_score", 0)
        is_passed = result.get("passed", False)
        status = "PASS" if is_passed else "FAIL"
        print(f"  {status} HLS={hls:>3} | {slug} (changes={changes})")
        
        if is_passed:
            passed.append((slug, pid, hls))
        else:
            violation_keys = [k for k in result.keys() if k.endswith("_violations") and result[k]]
            viol_summary = ", ".join(f"{k.replace('_violations','')}: {len(result[k])}" for k in violation_keys)
            still_failing.append((slug, pid, hls, viol_summary))
    
    print(f"\n{'='*80}")
    print(f"RESULTS: {len(passed)} passed, {len(still_failing)} still failing")
    print(f"{'='*80}")
    
    if still_failing:
        print(f"\nStill failing ({len(still_failing)}):")
        for slug, pid, hls, viols in still_failing:
            print(f"  HLS={hls:>3} ID={pid:>5} | {slug} | {viols}")
    
    # Save deployable list
    deploy_list = [(s, p) for s, p, _ in passed]
    with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), "p1p2_deploy_list.json"), "w") as f:
        json.dump({"passed": deploy_list, "still_failing": [(s,p,h,v) for s,p,h,v in still_failing]}, f, indent=2)
    
    print(f"\nDeploy list saved to ops/p1p2_deploy_list.json")


if __name__ == "__main__":
    main()
