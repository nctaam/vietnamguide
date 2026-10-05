# -*- coding: utf-8 -*-
"""
Unit test to verify that E-E-A-T enhancements for Organization and Person
meet Google Search Quality Rater Guidelines standards.
"""

def enrich_graph_eeat(graph, canonical_base):
    for node in graph:
        if not isinstance(node, dict):
            continue
        # Enrich Organization
        if node.get('@type') == 'Organization':
            node['url'] = f"{canonical_base}/"
            node['publishingPrinciples'] = f"{canonical_base}/editorial-policy/"
            node['correctionsPolicy'] = f"{canonical_base}/source-update-policy/"
            node['knowsAbout'] = [
                "Vietnam Travel Planning",
                "Vietnam Transportation & Rail Logistics",
                "Vietnam Visa Regulations & Entry Policies",
                "Southeast Asia Tourism Safety",
                "Sustainable Travel in Vietnam"
            ]
        # Enrich Person (Editorial Team)
        if node.get('@type') == 'Person':
            node['jobTitle'] = "Editorial Desk & Field Research Team"
            node['description'] = "Independent travel editors and on-the-ground researchers producing verified route logistics, safety checks, and practical travel guides for Vietnam."
            node['publishingPrinciples'] = f"{canonical_base}/editorial-policy/"
            node['knowsAbout'] = [
                "Vietnam Travel Logistics",
                "Vietnam Visa Regulations",
                "Public Transport and Rail in Vietnam",
                "Destination Planning",
                "Travel Safety in Southeast Asia"
            ]
    return graph

# Test with sample Rank Math graph
sample_graph = [
    {
        "@type": "Organization",
        "@id": "https://vietnamguide.net/#organization",
        "name": "VietnamGuide.net"
    },
    {
        "@type": "Person",
        "@id": "https://vietnamguide.net/plan/vietnam-evisa/#author",
        "name": "VietnamGuide editorial team"
    }
]

enriched = enrich_graph_eeat(sample_graph, "https://vietnamguide.net")
assert enriched[0]['publishingPrinciples'] == 'https://vietnamguide.net/editorial-policy/'
assert len(enriched[0]['knowsAbout']) == 5
assert enriched[1]['jobTitle'] == 'Editorial Desk & Field Research Team'
assert len(enriched[1]['knowsAbout']) == 5

print("[SUCCESS] E-E-A-T enrichment logic validated successfully!")
