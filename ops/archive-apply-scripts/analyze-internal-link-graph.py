import os
import re
import glob
from collections import defaultdict

def analyze_internal_links(ops_dir):
    php_files = glob.glob(os.path.join(ops_dir, "apply-*.php"))
    
    # Structure to hold guide info
    guides = {} # slug -> {title, type, file, related_routes: [], content_links: []}
    
    slug_pattern = re.compile(r"['\"]post_name['\"]\s*=>\s*['\"]([^'\"]+)['\"]")
    title_pattern = re.compile(r"['\"]post_title['\"]\s*=>\s*['\"]([^'\"]+)['\"]")
    type_pattern = re.compile(r"['\"]post_type['\"]\s*=>\s*['\"]([^'\"]+)['\"]")
    
    # Path patterns
    path_pattern = re.compile(r"(?:/)(destinations|itineraries|compare|plan)/([a-z0-9-]+)/?")
    link_pattern = re.compile(r"href=[\"'](?:https?://[^/]+)?/([^\"'#?]+)/?[\"']")
    
    for filepath in php_files:
        basename = os.path.basename(filepath)
        # Skip batch appliers or general runners
        if "batch" in basename or "trust" in basename or "footer" in basename:
            continue
            
        with open(filepath, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()
            
        slug_match = slug_pattern.search(content)
        title_match = title_pattern.search(content)
        type_match = type_pattern.search(content)
        
        slug = slug_match.group(1) if slug_match else None
        if not slug:
            # Try to derive from filename: apply-something.php
            m = re.match(r"apply-(.+?)(?:-post|-guide)?\.php", basename)
            if m:
                slug = m.group(1)
            else:
                continue
                
        title = title_match.group(1) if title_match else slug.replace("-", " ").title()
        p_type = type_match.group(1) if type_match else ("post" if "post" in basename else "page")
        
        # Extract related routes
        related_routes = []
        related_section = re.search(r"related_routes['\"]\s*=>\s*<<<['\"]?EOD['\"]?(.*?)EOD", content, re.DOTALL)
        if not related_section:
            related_section = re.search(r"vg_eeat_related_routes['\"].*?['\"](.*?)['\"]", content, re.DOTALL)
            
        if related_section:
            for line in related_section.group(1).splitlines():
                parts = [p.strip() for p in line.split("|")]
                if len(parts) >= 2 and parts[1].startswith("/"):
                    target_path = parts[1].strip("/")
                    target_slug = target_path.split("/")[-1]
                    related_routes.append(target_slug)
                    
        # Extract all content links
        content_links = []
        for raw_link in link_pattern.findall(content):
            clean_link = raw_link.strip("/")
            if clean_link and not clean_link.startswith(("http", "wp-", "category", "tag", "author")):
                target_slug = clean_link.split("/")[-1]
                content_links.append(target_slug)
                
        guides[slug] = {
            "title": title,
            "type": p_type,
            "file": basename,
            "related_routes": list(set(related_routes)),
            "content_links": list(set(content_links))
        }
        
    # Build graph
    in_links = defaultdict(set)
    out_links = defaultdict(set)
    
    all_slugs = set(guides.keys())
    
    for src_slug, data in guides.items():
        all_outgoing = set(data["related_routes"] + data["content_links"])
        for target_slug in all_outgoing:
            if target_slug in all_slugs and target_slug != src_slug:
                out_links[src_slug].add(target_slug)
                in_links[target_slug].add(src_slug)
                
    orphans = [s for s in all_slugs if len(in_links[s]) == 0]
    dead_ends = [s for s in all_slugs if len(out_links[s]) == 0]
    
    print(f"=== VIETNAMGUIDE INTERNAL LINK GRAPH ANALYSIS ===")
    print(f"Total Guides Analyzed: {len(all_slugs)}")
    total_edges = sum(len(targets) for targets in out_links.values())
    print(f"Total Internal Links: {total_edges}")
    avg_out = total_edges / len(all_slugs) if all_slugs else 0
    print(f"Average Outgoing Links per Guide: {avg_out:.1f}")
    print(f"Orphan Guides (In-degree = 0): {len(orphans)}")
    print(f"Dead-end Guides (Out-degree = 0): {len(dead_ends)}")
    
    # Top Hubs
    top_inbound = sorted(all_slugs, key=lambda s: len(in_links[s]), reverse=True)[:8]
    print(f"\n--- TOP LINKED HUBS (Highest Inbound Authority) ---")
    for s in top_inbound:
        print(f"  [{len(in_links[s])} inbound links] {guides[s]['title']} (/{s}/)")
        
    top_outbound = sorted(all_slugs, key=lambda s: len(out_links[s]), reverse=True)[:8]
    print(f"\n--- TOP OUTBOUND ROUTE HUBS (Best Routing Architecture) ---")
    for s in top_outbound:
        print(f"  [{len(out_links[s])} outbound links] {guides[s]['title']} (/{s}/)")

if __name__ == "__main__":
    ops_directory = r"C:\Users\NCTaam\projects\vietnamguide\ops"
    analyze_internal_links(ops_directory)
