<?php
if (! defined('ABSPATH')) { exit; }
function vg_home_url(string $path): string { return home_url('/' . trim($path, '/') . '/'); }
function vg_homepage_data(): array
{
    return [
        'navigation' => [
            ['label' => 'Plan', 'url' => vg_home_url('plan')],
            ['label' => 'Destinations', 'url' => vg_home_url('destinations')],
            ['label' => 'Itineraries', 'url' => vg_home_url('itineraries')],
            ['label' => 'Compare', 'url' => vg_home_url('compare')],
            ['label' => 'Costs', 'url' => vg_home_url('costs')],
        ],
        'trip_lengths' => [
            ['label' => '7 days', 'url' => vg_home_url('itineraries/7-days-in-vietnam')],
            ['label' => '10 days', 'url' => vg_home_url('itineraries/10-days-in-vietnam')],
            ['label' => '14 days', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['label' => '21 days', 'url' => vg_home_url('itineraries/21-days-in-vietnam')],
        ],
        'travel_styles' => [
            ['label' => 'First trip', 'url' => vg_home_url('plan/vietnam-travel-guide')],
            ['label' => 'Food', 'url' => vg_home_url('destinations/best-things-to-do-in-hoi-an')],
            ['label' => 'Beach', 'url' => vg_home_url('destinations/best-beaches-in-vietnam')],
            ['label' => 'Family', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['label' => 'Premium', 'url' => vg_home_url('destinations/con-dao-travel-guide')],
        ],
        'itineraries' => [
            ['eyebrow' => 'First journey', 'title' => '10 Days: North, Central, and South', 'fit' => 'Best for a first visit requiring a coherent cross-country overview without rushed transit.', 'url' => vg_home_url('itineraries/10-days-in-vietnam')],
            ['eyebrow' => 'More breathing room', 'title' => '14 Days: Vietnam at a Measured Pace', 'fit' => 'Best for travelers seeking regional depth, rest buffers, and fewer flight connections.', 'url' => vg_home_url('itineraries/14-days-in-vietnam')],
            ['eyebrow' => 'Landscape-led', 'title' => 'Regional Focus: North, Central, or South', 'fit' => 'Best for exploring one distinct region thoroughly before attempting a nationwide journey.', 'url' => vg_home_url('compare/north-central-south-vietnam')],
            ['eyebrow' => 'Island finish', 'title' => 'Con Dao: Island Retreat', 'fit' => 'Best for travelers who want to conclude their journey with quiet coastal seclusion.', 'url' => vg_home_url('destinations/con-dao-travel-guide')],
        ],
        'destinations' => [
            ['title' => 'Hanoi', 'best_for' => 'Street food culture, historical quarters, and primary Noi Bai (HAN) arrival via Bus 86 (45,000 VND).', 'skip_if' => 'You want a quiet coastal base.', 'url' => vg_home_url('destinations/hanoi-travel-guide')],
            ['title' => 'Hoi An', 'best_for' => 'Walkable heritage streets (120,000 VND 5-site pass), tailor workshops, and a relaxed central base.', 'skip_if' => 'You need a major-city itinerary.', 'url' => vg_home_url('destinations/best-things-to-do-in-hoi-an')],
            ['title' => 'Ninh Binh', 'best_for' => 'Karst landscapes and Trang An sampan routes (250,000–300,000 VND) without an overnight cruise.', 'skip_if' => 'You dislike early starts and rural transfers.', 'url' => vg_home_url('destinations/ninh-binh-travel-guide')],
            ['title' => 'Cat Ba', 'best_for' => 'An island base via the Dong Bai – Cai Vieng ferry (12,000 VND) with direct Lan Ha access.', 'skip_if' => 'You only want the classic Ha Long cruise checklist.', 'url' => vg_home_url('destinations/cat-ba-travel-guide')],
            ['title' => 'Ha Long Bay', 'best_for' => 'The classic northern seascape reached in 2.5 hours from Hanoi via the CT04/CT06 expressway.', 'skip_if' => 'You already know you want a quieter bay or a skip.', 'url' => vg_home_url('destinations/ha-long-bay-travel-guide')],
            ['title' => 'Phu Quoc', 'best_for' => 'An easy beach finish with resort choice and a 30-day island visa exemption at PQC Airport.', 'skip_if' => 'You want a culture-first final stop.', 'url' => vg_home_url('destinations/phu-quoc-travel-guide')],
        ],
        'comparisons' => [
            ['title' => 'Ha Long Bay vs Lan Ha Bay', 'verdict' => 'Compare iconic Tuan Chau / Ha Long cruise routes against quieter Cat Ba and Beo Pier sailing corridors.', 'url' => vg_home_url('compare/ha-long-bay-vs-lan-ha-bay')],
            ['title' => 'Da Nang vs Hoi An', 'verdict' => 'Weigh DAD airport-and-beach convenience (30 km apart) against a slower heritage town base.', 'url' => vg_home_url('compare/da-nang-vs-hoi-an')],
            ['title' => 'North vs Central vs South', 'verdict' => 'Anchor in one distinct climate zone before committing to a cross-country route.', 'url' => vg_home_url('compare/north-central-south-vietnam')],
        ],
        'essentials' => [
            ['title' => 'Vietnam E-Visa', 'meta' => '90-day e-visa rules ($25 single / $50 multiple USD) and 45-day exemptions for 25 countries.', 'url' => vg_home_url('plan/vietnam-evisa')],
            ['title' => 'Best time to visit', 'meta' => 'Plan around regional monsoon calendars across North, Central, and South.', 'url' => vg_home_url('plan/best-time-to-visit-vietnam')],
            ['title' => 'Travel cost', 'meta' => 'Verified 2026 daily budgets in VND and USD across backpacker, mid-range, and comfort tiers.', 'url' => vg_home_url('costs/vietnam-travel-cost')],
            ['title' => 'SIM and eSIM', 'meta' => 'Viettel and Vinaphone 4G/5G tourist data packages (150,000–350,000 VND) from arrival.', 'url' => vg_home_url('plan/sim-esim-vietnam')],
            ['title' => 'Transport', 'meta' => 'Domestic flights, Reunification Express sleeper trains (SE1–SE4), and expressway limousines.', 'url' => vg_home_url('plan/transport-within-vietnam')],
            ['title' => 'Safety and scams', 'meta' => 'Practical airport taxi verification, ATM withdrawal limits, and risk management without alarmism.', 'url' => vg_home_url('plan/safety-scams-vietnam')],
        ],
    ];
}
