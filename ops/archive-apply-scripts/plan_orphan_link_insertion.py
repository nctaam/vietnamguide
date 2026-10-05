# -*- coding: utf-8 -*-
"""
Plan contextual internal link insertions for the 14 orphan articles:
For each orphan article, identify 2-3 natural hub pages that should link to it.
"""

import json

ORPHANS_TARGET_LINKS = {
    "da-nang-beaches-guide": [
        ("da-nang-travel-guide", "My Khe beach", "Check our detailed guide to <a href=\"/destinations/da-nang-beaches-guide/\">Da Nang beaches</a> for water conditions, resort vs public sections, and seasonal wave patterns."),
        ("best-beaches-in-vietnam", "Da Nang", "Explore our dedicated <a href=\"/destinations/da-nang-beaches-guide/\">Da Nang beaches guide</a> for neighborhood comparisons between My Khe and Non Nuoc."),
        ("da-nang-vs-hoi-an", "beach base", "Read our complete <a href=\"/destinations/da-nang-beaches-guide/\">Da Nang beaches guide</a> to decide between beachfront city resorts and tranquil coastal stays.")
    ],
    "phong-nha-travel-guide": [
        ("best-places-to-visit-vietnam", "Phong Nha", "For cave expeditions and national park hikes, consult our <a href=\"/destinations/phong-nha-travel-guide/\">Phong Nha travel guide</a>."),
        ("hue-imperial-city-guide", "onward travel", "Travelers continuing north along the heritage corridor can read our <a href=\"/destinations/phong-nha-travel-guide/\">Phong Nha travel guide</a> for cave logistics."),
        ("ninh-binh-travel-guide", "southbound route", "If heading south by train, our <a href=\"/destinations/phong-nha-travel-guide/\">Phong Nha travel guide</a> details transfer times and cave tour choices.")
    ],
    "hanoi-vs-ho-chi-minh-city": [
        ("hanoi-travel-guide", "comparing northern and southern capitals", "Deciding between the two metropolises? Review our head-to-head comparison of <a href=\"/compare/hanoi-vs-ho-chi-minh-city/\">Hanoi vs Ho Chi Minh City</a>."),
        ("ho-chi-minh-city-travel-guide", "first-time city choice", "Compare pace, dining, and atmosphere in our guide to <a href=\"/compare/hanoi-vs-ho-chi-minh-city/\">Hanoi vs Ho Chi Minh City</a>."),
        ("best-vietnam-cities-for-first-time-visitors", "capital comparison", "See our direct breakdown of <a href=\"/compare/hanoi-vs-ho-chi-minh-city/\">Hanoi vs Ho Chi Minh City</a> to determine your entry port.")
    ],
    "best-vietnam-routes-first-time-visitors": [
        ("vietnam-travel-guide", "first trip routes", "Review our recommended <a href=\"/plan/best-vietnam-routes-first-time-visitors/\">Vietnam routes for first-time visitors</a> before booking domestic flights."),
        ("vietnam-first-trip-planning-checklist", "route architecture", "Select your travel corridor with our guide to <a href=\"/plan/best-vietnam-routes-first-time-visitors/\">best Vietnam routes for first-timers</a>."),
        ("where-to-stay-in-vietnam-base-decisions", "route bases", "Align your accommodation choices with our <a href=\"/plan/best-vietnam-routes-first-time-visitors/\">first-time Vietnam route guide</a>.")
    ],
    "mekong-delta-overnight-vs-day-trip": [
        ("mekong-delta-travel-guide", "day trip or overnight stay", "Weigh your timing options in our guide on <a href=\"/compare/mekong-delta-overnight-vs-day-trip/\">Mekong Delta overnight vs day trip</a>."),
        ("best-day-trips-from-ho-chi-minh-city", "Mekong River excursion", "Decide whether a quick visit is sufficient with our <a href=\"/compare/mekong-delta-overnight-vs-day-trip/\">Mekong Delta day trip vs overnight comparison</a>.")
    ],
    "vietnam-in-december": [
        ("best-time-to-visit-vietnam", "December weather", "For holiday route planning, read our month-specific guide on <a href=\"/plan/vietnam-in-december/\">Vietnam in December</a>."),
        ("what-to-pack-for-vietnam-region-season", "winter packing", "See regional climate details in our guide to <a href=\"/plan/vietnam-in-december/\">Vietnam in December</a>.")
    ],
    "vietnam-in-february": [
        ("best-time-to-visit-vietnam", "February travel", "Check our detailed breakdown of <a href=\"/plan/vietnam-in-february/\">Vietnam in February</a> for post-Tet reopening schedules and weather."),
        ("tet-in-vietnam-travel-guide", "holiday travel dates", "Plan your route around the festive season with our overview of <a href=\"/plan/vietnam-in-february/\">Vietnam in February</a>.")
    ],
    "vietnam-rainy-season-flexible-route": [
        ("best-time-to-visit-vietnam", "rainy season buffer", "Build storm resilience into your schedule with our <a href=\"/plan/vietnam-rainy-season-flexible-route/\">Vietnam rainy season flexible route guide</a>."),
        ("what-to-pack-for-vietnam-region-season", "monsoon gear", "Learn how to adapt your transit using our <a href=\"/plan/vietnam-rainy-season-flexible-route/\">Vietnam rainy season route planner</a>.")
    ],
    "vietnam-first-trip-planning-checklist": [
        ("vietnam-travel-guide", "step-by-step checklist", "Follow our chronological <a href=\"/plan/vietnam-first-trip-planning-checklist/\">Vietnam first trip planning checklist</a> to avoid missing critical preparation steps."),
        ("transport-within-vietnam", "booking timeline", "Review the full sequence of booking steps in our <a href=\"/plan/vietnam-first-trip-planning-checklist/\">first-trip Vietnam checklist</a>.")
    ],
    "hanoi-first-time-visitor-mistakes": [
        ("hanoi-travel-guide", "common mistakes", "Avoid taxi traps, counterfeit booking desks, and pacing errors with our <a href=\"/plan/hanoi-first-time-visitor-mistakes/\">Hanoi first-time visitor mistakes guide</a>."),
        ("best-things-to-do-in-hanoi", "practical visitor advice", "Make the most of your capital stay by reviewing <a href=\"/plan/hanoi-first-time-visitor-mistakes/\">Hanoi mistakes to avoid</a>."),
        ("hanoi-in-2-days", "arrival tips", "Keep your weekend smooth by checking common <a href=\"/plan/hanoi-first-time-visitor-mistakes/\">Hanoi tourist mistakes</a>.")
    ],
    "ninh-binh-without-rushing": [
        ("ninh-binh-travel-guide", "unhurried pace", "Plan a calm itinerary with our guide to <a href=\"/plan/ninh-binh-without-rushing/\">visiting Ninh Binh without rushing</a>."),
        ("ninh-binh-day-trip-vs-overnight", "pacing considerations", "Discover why staying overnight matters in our advice on <a href=\"/plan/ninh-binh-without-rushing/\">Ninh Binh without rushing</a>.")
    ],
    "ha-long-bay-cruise-questions-before-booking": [
        ("ha-long-bay-travel-guide", "booking questions", "Protect your investment by reviewing <a href=\"/plan/ha-long-bay-cruise-questions-before-booking/\">key questions to ask before booking a Ha Long Bay cruise</a>."),
        ("ha-long-bay-vs-lan-ha-bay", "cruise operator evaluation", "Vet your cabin amenities and itinerary terms with our <a href=\"/plan/ha-long-bay-cruise-questions-before-booking/\">Ha Long Bay cruise booking checklist</a>.")
    ],
    "best-vietnam-cities-for-first-time-visitors": [
        ("where-to-stay-in-vietnam-base-decisions", "choosing city hubs", "Compare the strategic advantages of each urban base in our guide to <a href=\"/plan/best-vietnam-cities-for-first-time-visitors/\">best Vietnam cities for first-time visitors</a>."),
        ("vietnam-travel-guide", "urban hubs", "Select your base hubs with our ranking of the <a href=\"/plan/best-vietnam-cities-for-first-time-visitors/\">best Vietnam cities for first-timers</a>.")
    ],
    "ha-giang-easy-rider-vs-self-drive": [
        ("ha-giang-loop-planning-guide", "rider selection", "Decide between driving or sitting pillion with our guide to <a href=\"/compare/ha-giang-easy-rider-vs-self-drive/\">Ha Giang easy rider vs self-drive</a>."),
        ("ha-giang-safety-guide", "driving risks", "Evaluate your skills and license legality in our direct breakdown of <a href=\"/compare/ha-giang-easy-rider-vs-self-drive/\">Ha Giang easy rider vs self-drive</a>.")
    ]
}

print(f"Total orphan articles mapped: {len(ORPHANS_TARGET_LINKS)}")
total_insertions = sum(len(v) for v in ORPHANS_TARGET_LINKS.values())
print(f"Total contextual link insertions planned: {total_insertions}")
