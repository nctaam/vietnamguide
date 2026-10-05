# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 79: Assemble and Strictly Validate the Internal Link Mesh
"""

import json
import os
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def main():
    targets_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage79_mesh_targets.json')
    targets = json.load(open(targets_path, encoding='utf-8'))

    replacements = {
        # --- 27: da-nang-to-hue-train-vs-car -> where-to-stay-in-lang-co ---
        "da-nang-to-hue-train-vs-car": [
            (
                '<li><a href="/itineraries/central-vietnam-itinerary/">Central Vietnam Itinerary</a> &mdash; complete 5 to 7-day circuit across Hue, Hai Van Pass, Da Nang, and Hoi An.</li>',
                '<li><a href="/destinations/where-to-stay-in-lang-co/">Where to Stay in Lang Co</a> &mdash; beachfront resorts and oyster lagoon villas at the foot of Hai Van Pass.</li>\n<li><a href="/itineraries/central-vietnam-itinerary/">Central Vietnam Itinerary</a> &mdash; complete 5 to 7-day circuit across Hue, Hai Van Pass, Da Nang, and Hoi An.</li>',
                ["where-to-stay-in-lang-co"]
            )
        ],
        # --- 1: where-to-stay-in-nha-trang -> nha-trang-to-quy-nhon-transport ---
        "where-to-stay-in-nha-trang": [
            (
                '<li><a href="/destinations/best-things-to-do-in-nha-trang/">Best Things to Do in Nha Trang</a></li>',
                '<li><a href="/destinations/best-things-to-do-in-nha-trang/">Best Things to Do in Nha Trang</a></li>\n        <li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Coastal trains and shared limousine vans via Highway 1D.</li>',
                ["nha-trang-to-quy-nhon-transport"]
            )
        ],

        # --- 2: where-to-stay-in-quy-nhon -> nha-trang-to-quy-nhon-transport ---
        "where-to-stay-in-quy-nhon": [
            (
                '<li><a href="/destinations/quy-nhon-travel-guide/">Quy Nhon Travel Guide</a>: Comprehensive overview of beaches, attractions, and history.</li>',
                '<li><a href="/destinations/quy-nhon-travel-guide/">Quy Nhon Travel Guide</a>: Comprehensive overview of beaches, attractions, and history.</li>\n        <li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Scenic rail journeys and limousine vans from Khanh Hoa.</li>',
                ["nha-trang-to-quy-nhon-transport"]
            )
        ],

        # --- 3: da-nang-to-quy-nhon-transport -> nha-trang-to-quy-nhon-transport ---
        "da-nang-to-quy-nhon-transport": [
            (
                '<li><a href="/destinations/quy-nhon-travel-guide/">Quy Nhon Travel Guide</a>: Ky Co beach, Eo Gio cliffs, and Twin Cham towers.</li>',
                '<li><a href="/destinations/quy-nhon-travel-guide/">Quy Nhon Travel Guide</a>: Ky Co beach, Eo Gio cliffs, and Twin Cham towers.</li>\n        <li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>: Coastal trains and Highway 1D minivans from the south.</li>',
                ["nha-trang-to-quy-nhon-transport"]
            )
        ],

        # --- 4: quy-nhon-travel-guide -> nha-trang-to-quy-nhon-transport ---
        "quy-nhon-travel-guide": [
            (
                '<a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central coast chapter.',
                '<a href="/plan/da-nang-to-quy-nhon-transport/">Da Nang to Quy Nhon Transport</a>, <a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a> before adding a south-central coast chapter.',
                ["nha-trang-to-quy-nhon-transport"]
            )
        ],

        # --- 5: vietnam-train-travel -> nha-trang-to-quy-nhon-transport ---
        "vietnam-train-travel": [
            (
                '<li><a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips: Sleeper Berths, Door Locks &amp; Luggage Security</a></li>',
                '<li><a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips: Sleeper Berths, Door Locks &amp; Luggage Security</a></li>\n<li><a href="/plan/nha-trang-to-quy-nhon-transport/">Nha Trang to Quy Nhon Transport: Reunification Express Coastal Rail</a></li>',
                ["nha-trang-to-quy-nhon-transport"]
            )
        ],

        # --- 6: where-to-stay-in-can-tho -> can-tho-to-chau-doc-transport, where-to-stay-in-ben-tre ---
        "where-to-stay-in-can-tho": [
            (
                '<li><a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho Transport</a>: Limousine vans, express buses, and car routes.</li>',
                '<li><a href="/plan/ho-chi-minh-city-to-can-tho-transport/">Ho Chi Minh City to Can Tho Transport</a>: Limousine vans, express buses, and car routes.</li>\n        <li><a href="/plan/can-tho-to-chau-doc-transport/">Can Tho to Chau Doc Transport</a>: Futa express buses and VIP limousine vans via Highway 91.</li>\n        <li><a href="/destinations/where-to-stay-in-ben-tre/">Where to Stay in Ben Tre</a>: Ham Luong river eco-resorts and orchard homestays.</li>',
                ["can-tho-to-chau-doc-transport", "where-to-stay-in-ben-tre"]
            )
        ],

        # --- 7: where-to-stay-in-chau-doc -> can-tho-to-chau-doc-transport ---
        "where-to-stay-in-chau-doc": [
            (
                '<li><a href="/destinations/can-tho-travel-guide/">Can Tho Travel Guide</a>: Cai Rang floating market, canal boat tours, and delta dining.</li>',
                '<li><a href="/destinations/can-tho-travel-guide/">Can Tho Travel Guide</a>: Cai Rang floating market, canal boat tours, and delta dining.</li>\n        <li><a href="/plan/can-tho-to-chau-doc-transport/">Can Tho to Chau Doc Transport</a>: Futa buses and shared limousine shuttles from the delta hub.</li>',
                ["can-tho-to-chau-doc-transport"]
            )
        ],

        # --- 8: vietnam-to-cambodia-boat-guide -> can-tho-to-chau-doc-transport ---
        "vietnam-to-cambodia-boat-guide": [
            (
                '<li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>: Riverfront hotels near the boat pier and Sam Mountain lodges.</li>',
                '<li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>: Riverfront hotels near the boat pier and Sam Mountain lodges.</li>\n        <li><a href="/plan/can-tho-to-chau-doc-transport/">Can Tho to Chau Doc Transport</a>: Connecting Can Tho floating markets with Chau Doc border boats.</li>',
                ["can-tho-to-chau-doc-transport"]
            )
        ],

        # --- 9: ho-chi-minh-city-to-can-tho-transport -> can-tho-to-chau-doc-transport, where-to-stay-in-ben-tre ---
        "ho-chi-minh-city-to-can-tho-transport": [
            (
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf vs riverside eco-resorts.</li>',
                '<li><a href="/destinations/where-to-stay-in-can-tho/">Where to Stay in Can Tho</a>: Ninh Kieu Wharf vs riverside eco-resorts.</li>\n        <li><a href="/plan/can-tho-to-chau-doc-transport/">Can Tho to Chau Doc Transport</a>: Onward travel from Can Tho deeper west to the Cambodian border.</li>\n        <li><a href="/destinations/where-to-stay-in-ben-tre/">Where to Stay in Ben Tre</a>: Riverside eco-lodges along the coconut canal belt.</li>',
                ["can-tho-to-chau-doc-transport", "where-to-stay-in-ben-tre"]
            )
        ],

        # --- 10: mekong-delta-travel-guide -> can-tho-to-chau-doc-transport, where-to-stay-in-ben-tre ---
        "mekong-delta-travel-guide": [
            (
                '<li>Review our dedicated accommodation advice in <a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a> when planning an overnight river stay near Sam Mountain or the Cambodian border.</li>',
                '<li>Review our dedicated accommodation advice in <a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a> when planning an overnight river stay near Sam Mountain or the Cambodian border.</li>\n<li>Check regional overland transit in <a href="/plan/can-tho-to-chau-doc-transport/">Can Tho to Chau Doc Transport</a> and riverside lodging options in <a href="/destinations/where-to-stay-in-ben-tre/">Where to Stay in Ben Tre</a>.</li>',
                ["can-tho-to-chau-doc-transport", "where-to-stay-in-ben-tre"]
            )
        ],

        # --- 11: where-to-stay-in-mai-chau -> hanoi-to-mai-chau-transport, hanoi-to-pu-luong-transport ---
        "where-to-stay-in-mai-chau": [
            (
                '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Compare stilt homestays with terraced valley retreats.</li>',
                '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Compare stilt homestays with terraced valley retreats.</li>\n        <li><a href="/plan/hanoi-to-mai-chau-transport/">Hanoi to Mai Chau Transport</a>: Old Quarter limousine vans and local buses via Thung Khe Pass.</li>\n        <li><a href="/plan/hanoi-to-pu-luong-transport/">Hanoi to Pu Luong Transport</a>: Direct valley shuttles and onward connections to Mai Chau.</li>',
                ["hanoi-to-mai-chau-transport", "hanoi-to-pu-luong-transport"]
            )
        ],

        # --- 12: best-day-trips-from-hanoi -> hanoi-to-mai-chau-transport ---
        "best-day-trips-from-hanoi": [
            (
                'our valley overnight guide on <a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>',
                'our valley overnight guide on <a href="/destinations/where-to-stay-in-mai-chau/">Where to Stay in Mai Chau</a>, our transit route in <a href="/plan/hanoi-to-mai-chau-transport/">Hanoi to Mai Chau Transport</a>',
                ["hanoi-to-mai-chau-transport"]
            )
        ],

        # --- 13: hanoi-travel-guide -> hanoi-to-mai-chau-transport ---
        "hanoi-travel-guide": [
            (
                'or northwest battlefield routes via <a href="/plan/hanoi-to-dien-bien-phu-transport/">Hanoi to Dien Bien Phu Transport</a>.',
                'or northwest battlefield routes via <a href="/plan/hanoi-to-dien-bien-phu-transport/">Hanoi to Dien Bien Phu Transport</a>, or peaceful valley escapes via <a href="/plan/hanoi-to-mai-chau-transport/">Hanoi to Mai Chau Transport</a>.',
                ["hanoi-to-mai-chau-transport"]
            )
        ],

        # --- 14: northwest-vietnam-itinerary -> hanoi-to-mai-chau-transport, hanoi-to-pu-luong-transport ---
        "northwest-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Valley viewpoints, mountain retreats, and Ban Don eco-resorts.</li>',
                '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Valley viewpoints, mountain retreats, and Ban Don eco-resorts.</li>\n        <li><a href="/plan/hanoi-to-mai-chau-transport/">Hanoi to Mai Chau Transport</a>: Old Quarter limousine shuttles and scenic Thung Khe Pass routes.</li>\n        <li><a href="/plan/hanoi-to-pu-luong-transport/">Hanoi to Pu Luong Transport</a>: Direct eco-bus shuttles to Ban Don terraced lodges.</li>',
                ["hanoi-to-mai-chau-transport", "hanoi-to-pu-luong-transport"]
            )
        ],

        # --- 15: southern-vietnam-itinerary -> where-to-stay-in-ben-tre ---
        "southern-vietnam-itinerary": [
            (
                '<li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>: Sam Mountain sunset resorts vs Hau River hotels.</li>',
                '<li><a href="/destinations/where-to-stay-in-chau-doc/">Where to Stay in Chau Doc</a>: Sam Mountain sunset resorts vs Hau River hotels.</li>\n        <li><a href="/destinations/where-to-stay-in-ben-tre/">Where to Stay in Ben Tre</a>: Ham Luong riverfront eco-lodges and coconut orchard homestays.</li>',
                ["where-to-stay-in-ben-tre"]
            )
        ],

        # --- 16: where-to-stay-in-hue -> where-to-stay-in-lang-co ---
        "where-to-stay-in-hue": [
            (
                '<li><a href="/plan/da-nang-to-hue-train-vs-car/">Da Nang to Hue: Train vs Car</a></li>',
                '<li><a href="/plan/da-nang-to-hue-train-vs-car/">Da Nang to Hue: Train vs Car</a></li>\n        <li><a href="/destinations/where-to-stay-in-lang-co/">Where to Stay in Lang Co</a>: Laguna luxury villas, peninsula beach resorts, and Lap An lagoon stays.</li>',
                ["where-to-stay-in-lang-co"]
            )
        ],

        # --- 17: where-to-stay-in-da-nang -> where-to-stay-in-lang-co ---
        "where-to-stay-in-da-nang": [
            (
                '<li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a> &mdash; Reunification Express train berths, sleeper buses, and DMZ transfers.</li>',
                '<li><a href="/plan/da-nang-to-phong-nha-transport/">Da Nang to Phong Nha Transport</a> &mdash; Reunification Express train berths, sleeper buses, and DMZ transfers.</li>\n<li><a href="/destinations/where-to-stay-in-lang-co/">Where to Stay in Lang Co</a> &mdash; beachfront resorts and oyster lagoon villas under Hai Van Pass.</li>',
                ["where-to-stay-in-lang-co"]
            )
        ],

        # --- 18: central-vietnam-itinerary -> where-to-stay-in-lang-co ---
        "central-vietnam-itinerary": [
            (
                '<li><a href="/destinations/hue-imperial-city-guide/">Hue Imperial City Guide</a>: Palaces, royal tombs, and Perfume River sights.</li>',
                '<li><a href="/destinations/hue-imperial-city-guide/">Hue Imperial City Guide</a>: Palaces, royal tombs, and Perfume River sights.</li>\n        <li><a href="/destinations/where-to-stay-in-lang-co/">Where to Stay in Lang Co</a>: Luxury bay villas and Lap An oyster lagoon retreats.</li>',
                ["where-to-stay-in-lang-co"]
            )
        ],

        # --- 19: hue-to-hoi-an-transport -> where-to-stay-in-lang-co ---
        "hue-to-hoi-an-transport": [
            (
                '<li><a href="/compare/da-nang-to-hue-train-vs-car/">Da Nang to Hue Train vs Car</a></li>',
                '<li><a href="/compare/da-nang-to-hue-train-vs-car/">Da Nang to Hue Train vs Car</a></li>\n        <li><a href="/destinations/where-to-stay-in-lang-co/">Where to Stay in Lang Co</a>: Seaside resorts and lagoon stays along the coastal route.</li>',
                ["where-to-stay-in-lang-co"]
            )
        ],

        # --- 20: transport-within-vietnam -> vietnam-sleeper-bus-survival-guide ---
        "transport-within-vietnam": [
            (
                'compare mountain flight options in <a href="/plan/hanoi-to-dien-bien-phu-transport/">Hanoi to Dien Bien Phu Transport</a>, and consult <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.',
                'compare mountain flight options in <a href="/plan/hanoi-to-dien-bien-phu-transport/">Hanoi to Dien Bien Phu Transport</a>, prepare for long road trips with our <a href="/plan/vietnam-sleeper-bus-survival-guide/">Vietnam Sleeper Bus Survival Guide</a>, and consult <a href="/costs/vietnam-travel-cost/">Vietnam Travel Cost</a> before paying for high-friction transfers.',
                ["vietnam-sleeper-bus-survival-guide"]
            )
        ],

        # --- 21: vietnam-night-train-safety-tips -> vietnam-sleeper-bus-survival-guide ---
        "vietnam-night-train-safety-tips": [
            (
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel Guide</a>: Reunification Express routes, booking portals, and ticket classes.</li>',
                '<li><a href="/plan/vietnam-train-travel/">Vietnam Train Travel Guide</a>: Reunification Express routes, booking portals, and ticket classes.</li>\n        <li><a href="/plan/vietnam-sleeper-bus-survival-guide/">Vietnam Sleeper Bus Survival Guide</a>: VIP limousine cabins, bunk selection, and highway rest stops.</li>',
                ["vietnam-sleeper-bus-survival-guide"]
            )
        ],

        # --- 22: vietnam-scooter-rental-checklist -> vietnam-sleeper-bus-survival-guide ---
        "vietnam-scooter-rental-checklist": [
            (
                '<li><a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>: 1968 IDP requirements, 50cc exemptions, and police fines.</li>',
                '<li><a href="/plan/vietnam-motorbike-license-laws/">Vietnam Motorbike License Laws</a>: 1968 IDP requirements, 50cc exemptions, and police fines.</li>\n        <li><a href="/plan/vietnam-sleeper-bus-survival-guide/">Vietnam Sleeper Bus Survival Guide</a>: Bunk selection, luggage security, and highway night buses.</li>',
                ["vietnam-sleeper-bus-survival-guide"]
            )
        ],

        # --- 23: safety-scams-vietnam -> vietnam-sleeper-bus-survival-guide ---
        "safety-scams-vietnam": [
            (
                '<li>Review overnight rail security protocols in our dedicated <a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips</a> before boarding sleeper carriages.</li>',
                '<li>Review overnight rail security protocols in our dedicated <a href="/plan/vietnam-night-train-safety-tips/">Vietnam Night Train Safety Tips</a> before boarding sleeper carriages.</li>\n<li>Review long-distance highway bus tips in our <a href="/plan/vietnam-sleeper-bus-survival-guide/">Vietnam Sleeper Bus Survival Guide</a> before booking overnight berths.</li>',
                ["vietnam-sleeper-bus-survival-guide"]
            )
        ],

        # --- 24: hanoi-to-sapa-train-vs-sleeper-bus -> vietnam-sleeper-bus-survival-guide ---
        "hanoi-to-sapa-train-vs-sleeper-bus": [
            (
                '<li><a href="/plan/sapa-vs-ha-giang/">Sapa vs Ha Giang</a>: How to decide between northern Vietnam\'s premier mountain destinations.</li>',
                '<li><a href="/plan/sapa-vs-ha-giang/">Sapa vs Ha Giang</a>: How to decide between northern Vietnam\'s premier mountain destinations.</li>\n        <li><a href="/plan/vietnam-sleeper-bus-survival-guide/">Vietnam Sleeper Bus Survival Guide</a>: VIP cabin bunks, luggage safety, and rest stop advice.</li>',
                ["vietnam-sleeper-bus-survival-guide"]
            )
        ],

        # --- 25: where-to-stay-in-pu-luong -> hanoi-to-pu-luong-transport ---
        "where-to-stay-in-pu-luong": [
            (
                '<li><a href="/plan/pu-luong-trekking-routes-guide/">Pu Luong Trekking Routes</a>: Day hikes from Ban Don to Kho Muong, Hieu waterfall trails, and guide hiring.</li>',
                '<li><a href="/plan/pu-luong-trekking-routes-guide/">Pu Luong Trekking Routes</a>: Day hikes from Ban Don to Kho Muong, Hieu waterfall trails, and guide hiring.</li>\n        <li><a href="/plan/hanoi-to-pu-luong-transport/">Hanoi to Pu Luong Transport</a>: Direct Old Quarter eco-bus shuttles and private transfers.</li>',
                ["hanoi-to-pu-luong-transport"]
            )
        ],

        # --- 26: pu-luong-trekking-routes-guide -> hanoi-to-pu-luong-transport ---
        "pu-luong-trekking-routes-guide": [
            (
                '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Valley-view infinity pool retreats vs waterfall homestays.</li>',
                '<li><a href="/destinations/where-to-stay-in-pu-luong/">Where to Stay in Pu Luong</a>: Valley-view infinity pool retreats vs waterfall homestays.</li>\n        <li><a href="/plan/hanoi-to-pu-luong-transport/">Hanoi to Pu Luong Transport</a>: Eco-bus shuttles, private cars, and village arrival logistics.</li>',
                ["hanoi-to-pu-luong-transport"]
            )
        ],
    }

    updated_posts = []
    links_created = {
        'nha-trang-to-quy-nhon-transport': 0,
        'can-tho-to-chau-doc-transport': 0,
        'hanoi-to-mai-chau-transport': 0,
        'where-to-stay-in-ben-tre': 0,
        'where-to-stay-in-lang-co': 0,
        'vietnam-sleeper-bus-survival-guide': 0,
        'hanoi-to-pu-luong-transport': 0
    }

    for slug, ops in replacements.items():
        if slug not in targets:
            print(f"[ERROR] Target post not found: {slug}")
            sys.exit(1)
        
        post_data = targets[slug]
        content = post_data['content']
        
        for search_str, replace_str, targets_linked in ops:
            count = content.count(search_str)
            if count != 1:
                print(f"[ERROR] In {slug}: Expected exactly 1 match for search snippet, found {count}")
                print("Search snippet was:")
                print(repr(search_str))
                sys.exit(1)
            
            content = content.replace(search_str, replace_str)
            for t in targets_linked:
                links_created[t] += 1
                
        updated_posts.append({
            'id': post_data['id'],
            'slug': slug,
            'content': content
        })

    print("=== Replacement Verification Summary ===")
    all_ok = True
    for pillar, count in links_created.items():
        print(f"  {pillar}: {count} new inbound link operations")
        if count < 4:
            all_ok = False

    if not all_ok:
        print("[ERROR] Some pillars do not meet the minimum inbound links threshold!")
        sys.exit(1)

    out_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'stage79_mesh_ops.json')
    with open(out_path, 'w', encoding='utf-8') as f:
        json.dump(updated_posts, f, ensure_ascii=False, indent=2)

    print(f"\nSuccessfully validated and saved {len(updated_posts)} post updates to {out_path}")

if __name__ == '__main__':
    main()