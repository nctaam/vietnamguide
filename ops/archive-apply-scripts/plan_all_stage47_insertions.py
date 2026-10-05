# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 47 Master Link Insertion Builder & Tester
Builds exact, literal string replacements across parent posts,
verifying 100% match existence, uniqueness, and Anti-AI Slop compliance.
"""
import json
import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(REPO_ROOT / 'ops'))
from anti_ai_slop_linter import analyze_text

with open('ops/stage47_parents_cache.json', 'r', encoding='utf-8') as f:
    parents = json.load(f)

# Define all high-value in-content link insertions
OPS = [
    # 1. Parent: ninh-binh-travel-guide
    # Targets: pu-luong-travel-guide, phong-nha-travel-guide
    {
        'parent': 'ninh-binh-travel-guide',
        'desc': 'Link pu-luong-travel-guide and phong-nha-travel-guide',
        'target': 'Ninh Binh is at its best when it calms the route.',
        'replacement': 'Ninh Binh is at its best when it calms the route. For travelers continuing southwest into remote karst nature, our <a href="/destinations/pu-luong-travel-guide/">Pu Luong travel guide</a> covers the adjacent terraced reserve, while the overnight train south connects directly to the underground caverns in our <a href="/destinations/phong-nha-travel-guide/">Phong Nha travel guide</a>.'
    },

    # 2. Parent: sapa-travel-guide
    # Targets: where-to-stay-in-sapa
    {
        'parent': 'sapa-travel-guide',
        'desc': 'Link where-to-stay-in-sapa',
        'target': 'Sapa town is useful for arrival, restaurants, taxis, markets, and a shorter stay. The valleys make Sapa feel more like a mountain trip.',
        'replacement': 'Sapa town is useful for arrival, restaurants, taxis, markets, and a shorter stay. The valleys make Sapa feel more like a mountain trip (see our neighborhood breakdown on <a href="/destinations/where-to-stay-in-sapa/">where to stay in Sapa</a>).'
    },
    # Targets: sapa-trekking-guided-vs-self-guided
    {
        'parent': 'sapa-travel-guide',
        'desc': 'Link sapa-trekking-guided-vs-self-guided',
        'target': 'Self-guided walks can still work for confident travelers on short, obvious routes near town or lodging.',
        'replacement': 'Self-guided walks can still work for confident travelers on short, obvious routes near town or lodging (weigh both approaches in our guide on <a href="/destinations/sapa-trekking-guided-vs-self-guided/">Sapa trekking guided vs self-guided</a>).'
    },
    # Targets: mu-cang-chai-travel-guide, vietnam-rice-terraces-guide
    {
        'parent': 'sapa-travel-guide',
        'desc': 'Link mu-cang-chai-travel-guide and vietnam-rice-terraces-guide',
        'target': '<strong>Add Sapa when the route wants terraces, guided walking, and a softer mountain stay.</strong>',
        'replacement': '<strong>Add Sapa when the route wants terraces, guided walking, and a softer mountain stay.</strong> Compare regional highland valleys in our <a href="/destinations/vietnam-rice-terraces-guide/">Vietnam rice terraces guide</a> and dedicated <a href="/destinations/mu-cang-chai-travel-guide/">Mu Cang Chai travel guide</a>.'
    },
    # Targets: hanoi-to-sapa-transport
    {
        'parent': 'sapa-travel-guide',
        'desc': 'Link hanoi-to-sapa-transport',
        'target': 'Most Sapa plans start and end in Hanoi. The transfer choice should be judged by sleep, arrival time, luggage, pickup location, motion comfort, and how much the next day matters.',
        'replacement': 'Most Sapa plans start and end in Hanoi (see our transit breakdown for <a href="/transport/hanoi-to-sapa-transport/">Hanoi to Sapa transport</a>). The transfer choice should be judged by sleep, arrival time, luggage, pickup location, motion comfort, and how much the next day matters.'
    },

    # 3. Parent: ha-giang-loop-planning-guide
    # Targets: ha-giang-safety-guide, hanoi-to-ha-giang-transport, best-time-for-northern-vietnam
    {
        'parent': 'ha-giang-loop-planning-guide',
        'desc': 'Link ha-giang-safety-guide, hanoi-to-ha-giang-transport, and best-time-for-northern-vietnam',
        'target': 'The same scenery that makes Ha Giang memorable also creates the planning problem: long riding days, exposed roads, changing mountain weather, limited recovery margin, and a strong temptation to underrate fatigue.',
        'replacement': 'The same scenery that makes Ha Giang memorable also creates the planning problem: long riding days, exposed roads, changing mountain weather, limited recovery margin, and a strong temptation to underrate fatigue. Review critical mountain highway guidelines in our <a href="/destinations/ha-giang-safety-guide/">Ha Giang safety guide</a>, coordinate bus routes via <a href="/transport/hanoi-to-ha-giang-transport/">Hanoi to Ha Giang transport</a>, and verify seasonal mist windows with our guide to the <a href="/plan/best-time-for-northern-vietnam/">best time for Northern Vietnam</a>.'
    },

    # 4. Parent: safety-scams-vietnam
    # Targets: ha-giang-safety-guide, ha-giang-easy-rider-vs-self-drive, hanoi-first-time-visitor-mistakes
    {
        'parent': 'safety-scams-vietnam',
        'desc': 'Link ha-giang-safety-guide, ha-giang-easy-rider-vs-self-drive, and hanoi-first-time-visitor-mistakes',
        'target': 'Only if you are licensed, insured, experienced, sober, rested, and comfortable with local traffic. For most short first trips, private cars, walks, taxis, and guided transfers are better than learning Vietnamese traffic under pressure.',
        'replacement': 'Only if you are licensed, insured, experienced, sober, rested, and comfortable with local traffic. Before booking highland motorcycle routes, review our <a href="/destinations/ha-giang-safety-guide/">Ha Giang safety guide</a> and compare legal permits in our <a href="/destinations/ha-giang-easy-rider-vs-self-drive/">Ha Giang Easy Rider vs self-drive guide</a>. In capital traffic, avoid common arrival errors outlined in our review of <a href="/destinations/hanoi-first-time-visitor-mistakes/">Hanoi first-time visitor mistakes</a>. For most short first trips, private cars, walks, taxis, and guided transfers are better than learning Vietnamese traffic under pressure.'
    },

    # 5. Parent: transport-within-vietnam
    # Targets: hanoi-to-sapa-transport, hanoi-to-ha-giang-transport
    {
        'parent': 'transport-within-vietnam',
        'desc': 'Link hanoi-to-sapa-transport and hanoi-to-ha-giang-transport',
        'target': 'and keep buses or ferries for routes where they genuinely fit.',
        'replacement': 'and keep buses or ferries for routes where they genuinely fit (compare our specific route guides for <a href="/transport/hanoi-to-sapa-transport/">Hanoi to Sapa transport</a> and <a href="/transport/hanoi-to-ha-giang-transport/">Hanoi to Ha Giang transport</a>).'
    },

    # 6. Parent: best-beaches-in-vietnam
    # Targets: da-nang-beaches-guide, quy-nhon-travel-guide, ly-son-travel-guide
    {
        'parent': 'best-beaches-in-vietnam',
        'desc': 'Link da-nang-beaches-guide, quy-nhon-travel-guide, and ly-son-travel-guide',
        'target': 'The best beach in Vietnam depends on month, route shape, and travel style. If you only need one safe first-trip beach answer, choose Da Nang/My Khe or Hoi An/An Bang. Choose Phu Quoc when the trip should become beach-led, Con Dao for quiet luxury, Nha Trang for city-beach activity, Mui Ne for wind sports, Quy Nhon for quieter value, and Cat Ba only when the north needs an island-and-bay base.',
        'replacement': 'The best beach in Vietnam depends on month, route shape, and travel style. If you only need one safe first-trip beach answer, choose Da Nang/My Khe (explore our detailed <a href="/destinations/da-nang-beaches-guide/">Da Nang beaches guide</a>) or Hoi An/An Bang. Choose Phu Quoc when the trip should become beach-led, Con Dao for quiet luxury, Nha Trang for city-beach activity, Mui Ne for wind sports, Quy Nhon for quieter value (see our <a href="/destinations/quy-nhon-travel-guide/">Quy Nhon travel guide</a>), Ly Son for volcanic coastal geology (review our <a href="/destinations/ly-son-travel-guide/">Ly Son travel guide</a>), and Cat Ba only when the north needs an island-and-bay base.'
    },

    # 7. Parent: da-nang-vs-hoi-an
    # Targets: da-nang-beaches-guide
    {
        'parent': 'da-nang-vs-hoi-an',
        'desc': 'Link da-nang-beaches-guide',
        'target': 'Da Nang gives the airport, beach hotels, city scale, and cleaner logistics.',
        'replacement': 'Da Nang gives the airport, beach hotels (explored in our <a href="/destinations/da-nang-beaches-guide/">Da Nang beaches guide</a>), city scale, and cleaner logistics.'
    },
    # Targets: hoi-an-ancient-town-guide, cham-islands-travel-guide
    {
        'parent': 'da-nang-vs-hoi-an',
        'desc': 'Link hoi-an-ancient-town-guide and cham-islands-travel-guide',
        'target': 'Hoi An if the trip needs old-town evenings, food, heritage, cafes, and a slower central chapter.',
        'replacement': 'Hoi An if the trip needs old-town evenings (see our <a href="/destinations/hoi-an-ancient-town-guide/">Hoi An ancient town guide</a>), food, heritage, cafes, day trips to the <a href="/destinations/cham-islands-travel-guide/">Cham Islands</a>, and a slower central chapter.'
    },

    # 8. Parent: best-islands-in-vietnam
    # Targets: cham-islands-travel-guide, ly-son-travel-guide
    {
        'parent': 'best-islands-in-vietnam',
        'desc': 'Link cham-islands-travel-guide and ly-son-travel-guide',
        'target': 'Cham Islands, Ly Son, Phu Quy, Nam Du, and Co To can be excellent,',
        'replacement': 'The marine sanctuary in our <a href="/destinations/cham-islands-travel-guide/">Cham Islands travel guide</a>, the volcanic geology in our <a href="/destinations/ly-son-travel-guide/">Ly Son travel guide</a>, alongside Phu Quy, Nam Du, and Co To, can be rewarding alternatives,'
    },

    # 9. Parent: ho-chi-minh-city-travel-guide
    # Targets: mekong-delta-overnight-vs-day-trip
    {
        'parent': 'ho-chi-minh-city-travel-guide',
        'desc': 'Link mekong-delta-overnight-vs-day-trip',
        'target': 'and optional Cu Chi or Mekong logic.',
        'replacement': 'and optional Cu Chi or Mekong logic (evaluate day vs multi-day itineraries in our <a href="/destinations/mekong-delta-overnight-vs-day-trip/">Mekong Delta overnight vs day trip breakdown</a>).'
    },
    # Targets: hanoi-vs-ho-chi-minh-city, airport checklist, food safety
    {
        'parent': 'ho-chi-minh-city-travel-guide',
        'desc': 'Link hanoi-vs-ho-chi-minh-city, airport checklist, and food safety',
        'target': 'Field note: build HCMC around a base, not a checklist. The best version protects one central district, one serious history block, one food/market layer,',
        'replacement': 'Field note: build HCMC around a base, not a checklist. Deciding between the two metropolises? Consult our direct analysis of <a href="/destinations/hanoi-vs-ho-chi-minh-city/">Hanoi vs Ho Chi Minh City</a>. Protect your arrival with our <a href="/plan/vietnam-airport-arrival-checklist/">airport arrival checklist</a>, and dine safely using our <a href="/plan/vietnam-food-safety-street-food-etiquette/">street food safety guide</a>. The best version protects one central district, one serious history block, one food/market layer,'
    },

    # 10. Parent: hanoi-travel-guide
    # Targets: pu-luong-travel-guide, airport arrival checklist, food safety
    {
        'parent': 'hanoi-travel-guide',
        'desc': 'Link pu-luong-travel-guide, airport checklist, and food safety',
        'target': 'Hanoi becomes more valuable when it is not treated as a checklist.',
        'replacement': 'Hanoi becomes more valuable when it is not treated as a checklist. For calm rural excursions beyond the delta, review our <a href="/destinations/pu-luong-travel-guide/">Pu Luong travel guide</a>. Travelers arriving internationally can streamline their terminal steps with our <a href="/plan/vietnam-airport-arrival-checklist/">airport arrival checklist</a> and navigate street dining with our <a href="/plan/vietnam-food-safety-street-food-etiquette/">food safety and street food etiquette guide</a>.'
    },

    # 11. Parent: north-central-south-vietnam
    # Targets: hanoi-vs-ho-chi-minh-city
    {
        'parent': 'north-central-south-vietnam',
        'desc': 'Link hanoi-vs-ho-chi-minh-city',
        'target': 'and the south if you want Ho Chi Minh City, Mekong life, islands, winter sun, or an easier southern arrival.',
        'replacement': 'and the south if you want Ho Chi Minh City (see our direct comparison of <a href="/destinations/hanoi-vs-ho-chi-minh-city/">Hanoi vs Ho Chi Minh City</a>), Mekong life, islands, winter sun, or an easier southern arrival.'
    },

    # 12. Parent: best-time-to-visit-vietnam
    # Targets: tet-in-vietnam-travel-guide, vietnam-in-january
    {
        'parent': 'best-time-to-visit-vietnam',
        'desc': 'Link tet-in-vietnam-travel-guide and vietnam-in-january',
        'target': 'Choose December-April for a southern or island-led trip (consult our month guides for <a href="/plan/vietnam-in-december/">Vietnam in December</a> and <a href="/plan/vietnam-in-february/">Vietnam in February</a>),',
        'replacement': 'Choose December-April for a southern or island-led trip (consult our month guides for <a href="/plan/vietnam-in-december/">Vietnam in December</a>, <a href="/plan/vietnam-in-january/">Vietnam in January</a>, and <a href="/plan/vietnam-in-february/">Vietnam in February</a>, alongside our <a href="/plan/tet-in-vietnam-travel-guide/">Tet in Vietnam guide</a>),'
    },
    # Targets: mu-cang-chai-travel-guide
    {
        'parent': 'best-time-to-visit-vietnam',
        'desc': 'Link mu-cang-chai-travel-guide',
        'target': 'It is the month that fits your route: northern landscapes, central-coast weather, southern dry-season logic, flight shape, and how much flexibility you can keep.',
        'replacement': 'It is the month that fits your route: northern mountain harvests (check our <a href="/destinations/mu-cang-chai-travel-guide/">Mu Cang Chai travel guide</a>), central-coast weather, southern dry-season logic, flight shape, and how much flexibility you can keep.'
    },

    # 13. Parent: vietnam-in-december
    # Targets: vietnam-in-january
    {
        'parent': 'vietnam-in-december',
        'desc': 'Link vietnam-in-january',
        'target': 'Use this after <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, then check <a href="/compare/north-central-south-vietnam/">North Central South Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a> before locking flights or cruises.',
        'replacement': 'Use this after <a href="/plan/best-time-to-visit-vietnam/">Best Time to Visit Vietnam</a>, then check <a href="/compare/north-central-south-vietnam/">North Central South Vietnam</a>, <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, <a href="/plan/transport-within-vietnam/">Transport Within Vietnam</a>, and our next-month guide for <a href="/plan/vietnam-in-january/">Vietnam in January</a> before locking flights or cruises.'
    },

    # 14. Parent: vietnam-in-january
    # Targets: vietnam-in-december, vietnam-in-february
    {
        'parent': 'vietnam-in-january',
        'desc': 'Link vietnam-in-december and vietnam-in-february',
        'target': 'and the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a> route planner before final payment.',
        'replacement': 'and the <a href="/itineraries/10-days-in-vietnam/">10 Days in Vietnam</a> route planner before final payment (compare adjacent months in our guides for <a href="/plan/vietnam-in-december/">Vietnam in December</a> and <a href="/plan/vietnam-in-february/">Vietnam in February</a>).'
    },

    # 15. Parent: 10-days-in-vietnam
    # Targets: hue-imperial-city-guide
    {
        'parent': '10-days-in-vietnam',
        'desc': 'Link hue-imperial-city-guide',
        'target': 'Choose Hue if history matters. If you want a calmer route, skip Hue and spend this night in Hoi An or Da Nang instead.',
        'replacement': 'Choose Hue if history matters (review our <a href="/destinations/hue-imperial-city-guide/">Hue Imperial City guide</a>). If you want a calmer route, skip Hue and spend this night in Hoi An or Da Nang instead.'
    },
    # Targets: ninh-binh-without-rushing
    {
        'parent': '10-days-in-vietnam',
        'desc': 'Link ninh-binh-without-rushing',
        'target': 'Stay overnight in Ninh Binh to secure peaceful sunrise boat departures before tourist buses arrive from Hanoi. Limit it to a day trip only when minimizing hotel changes overrides countryside tranquility.',
        'replacement': 'Stay overnight in Ninh Binh to secure peaceful sunrise boat departures before tourist buses arrive from Hanoi (see our guide to <a href="/destinations/ninh-binh-without-rushing/">visiting Ninh Binh without rushing</a>). Limit it to a day trip only when minimizing hotel changes overrides countryside tranquility.'
    },
    # Targets: ha-long-bay-cruise-questions-before-booking
    {
        'parent': '10-days-in-vietnam',
        'desc': 'Link ha-long-bay-cruise-questions-before-booking',
        'target': 'Treat the cruise as a separate logistics module. Check pickup point, transfer inclusions, weather policy, cabin type, and whether the itinerary suits your energy level.',
        'replacement': 'Treat the cruise as a separate logistics module. Check pickup point, transfer inclusions, weather policy, cabin type, and whether the itinerary suits your energy level (consult our <a href="/destinations/ha-long-bay-cruise-questions-before-booking/">Ha Long Bay cruise questions before booking</a>).'
    },
    # Targets: sapa-vs-ha-giang, sapa-trekking
    {
        'parent': '10-days-in-vietnam',
        'desc': 'Link sapa-vs-ha-giang and sapa-trekking',
        'target': 'Hanoi, Ninh Binh, bay, and one northern extension. Strong when landscapes matter more than a full-country introduction.',
        'replacement': 'Hanoi, Ninh Binh, bay, and one northern extension. Weigh trekking vs road exploration in our <a href="/compare/sapa-vs-ha-giang/">Sapa vs Ha Giang comparison</a>, and review guided routes in our <a href="/destinations/sapa-trekking-guided-vs-self-guided/">Sapa trekking guide</a>. Strong when landscapes matter more than a full-country introduction.'
    },
    # Targets: vietnam-in-december, tet-in-vietnam-travel-guide, vietnam-rainy-season-flexible-route
    {
        'parent': '10-days-in-vietnam',
        'desc': 'Link vietnam-in-december, tet-in-vietnam-travel-guide, and vietnam-rainy-season-flexible-route',
        'target': 'Ten days leaves less room to absorb bad routing. Use seasonal guidance to choose the route bias, then verify current forecasts and operator policies before paying for anything that depends on water, mountains, beaches, or tight transfers.',
        'replacement': 'Ten days leaves less room to absorb bad routing. Use seasonal guidance to choose the route bias (check our guides for <a href="/plan/vietnam-in-december/">Vietnam in December</a> and <a href="/plan/tet-in-vietnam-travel-guide/">Tet in Vietnam</a>, or prepare storm buffers with our <a href="/routes/vietnam-rainy-season-flexible-route/">rainy season flexible route</a>), then verify current forecasts and operator policies before paying for anything that depends on water, mountains, beaches, or tight transfers.'
    },
    # Targets: vietnam-first-trip-planning-checklist
    {
        'parent': '10-days-in-vietnam',
        'desc': 'Link vietnam-first-trip-planning-checklist',
        'target': 'Check e-visa/entry status, regional weather, bay cruise transfer terms, domestic flight or rail times, hotel cancellation terms, and whether Da Nang departure really saves time.',
        'replacement': 'Check e-visa/entry status, review our <a href="/plan/vietnam-first-trip-planning-checklist/">first trip planning checklist</a>, regional weather, bay cruise transfer terms, domestic flight or rail times, hotel cancellation terms, and whether Da Nang departure really saves time.'
    },
    # Targets: hanoi-first-time-visitor-mistakes
    {
        'parent': '10-days-in-vietnam',
        'desc': 'Link hanoi-first-time-visitor-mistakes',
        'target': 'Stay central, keep dinner simple, and avoid scheduling a paid tour on arrival night. The first win is arriving safely and sleeping well.',
        'replacement': 'Stay central, keep dinner simple, and avoid scheduling a paid tour on arrival night (consult our guide to <a href="/destinations/hanoi-first-time-visitor-mistakes/">Hanoi first-time visitor mistakes</a>). The first win is arriving safely and sleeping well.'
    },
    # Targets: best-vietnam-cities-for-first-time-visitors
    {
        'parent': '10-days-in-vietnam',
        'desc': 'Link best-vietnam-cities-for-first-time-visitors',
        'target': 'Hanoi, Ninh Binh, one bay night, Hue or Hoi An, and a Da Nang exit. This protects variety without pretending ten days is two weeks.',
        'replacement': 'Hanoi, Ninh Binh, one bay night, Hue or Hoi An, and a Da Nang exit (see our profiles of the <a href="/destinations/best-vietnam-cities-for-first-time-visitors/">best Vietnam cities for first-time visitors</a>). This protects variety without pretending ten days is two weeks.'
    },

    # 16. Parent: 14-days-in-vietnam
    # Targets: hue-imperial-city-guide
    {
        'parent': '14-days-in-vietnam',
        'desc': 'Link hue-imperial-city-guide',
        'target': 'Use Hue for imperial history, slower food, and context. If heritage is not your priority, swap this for an extra Hoi An or Da Nang night.',
        'replacement': 'Use Hue for imperial history, slower food, and context (see our <a href="/destinations/hue-imperial-city-guide/">Hue Imperial City guide</a>). If heritage is not your priority, swap this for an extra Hoi An or Da Nang night.'
    },
    # Targets: phong-nha-travel-guide
    {
        'parent': '14-days-in-vietnam',
        'desc': 'Link phong-nha-travel-guide',
        'target': 'Use this as the hinge day. Add Phong Nha, a beach rest, a better guided day, or nothing at all if the trip already feels full.',
        'replacement': 'Use this as the hinge day. Add cave expeditions via our <a href="/destinations/phong-nha-travel-guide/">Phong Nha travel guide</a>, a beach rest, a better guided day, or nothing at all if the trip already feels full.'
    },
    # Targets: mekong-delta-overnight-vs-day-trip
    {
        'parent': '14-days-in-vietnam',
        'desc': 'Link mekong-delta-overnight-vs-day-trip',
        'target': 'Pick one southern add-on. Mekong works for river life and food context; a beach or Da Lat extension needs more nights and should replace something earlier.',
        'replacement': 'Pick one southern add-on. The delta works for river life and food context (see our <a href="/destinations/mekong-delta-overnight-vs-day-trip/">Mekong Delta overnight vs day trip breakdown</a>); a beach or Da Lat extension needs more nights and should replace something earlier.'
    },
    # Targets: where-to-stay-in-sapa, pu-luong-travel-guide, ha-giang-easy-rider-vs-self-drive
    {
        'parent': '14-days-in-vietnam',
        'desc': 'Link where-to-stay-in-sapa, pu-luong-travel-guide, and ha-giang-easy-rider-vs-self-drive',
        'target': 'Only if northern landscapes are the point of the trip. Do not add Sapa or Ha Giang to the balanced north-central-south route unless you remove the south, reduce central Vietnam, or accept a much faster trip.',
        'replacement': 'Only if northern landscapes are the point of the trip. Review valley stays in our <a href="/destinations/where-to-stay-in-sapa/">where to stay in Sapa guide</a>, check alternative routes in our <a href="/destinations/pu-luong-travel-guide/">Pu Luong travel guide</a>, or evaluate road licensing with our <a href="/destinations/ha-giang-easy-rider-vs-self-drive/">Ha Giang Easy Rider vs self-drive guide</a> before modifying your route. Do not add Sapa or Ha Giang to the balanced north-central-south route unless you remove the south, reduce central Vietnam, or accept a much faster trip.'
    },
    # Targets: vietnam-in-december, vietnam-in-february, vietnam-rainy-season-flexible-route
    {
        'parent': '14-days-in-vietnam',
        'desc': 'Link vietnam-in-december, vietnam-in-february, and vietnam-rainy-season-flexible-route',
        'target': 'Vietnam does not have one simple best month. A two-week route usually crosses weather zones, so the better question is which part of the country deserves your flexibility.',
        'replacement': 'Vietnam does not have one simple best month. A two-week route usually crosses weather zones, so the better question is which part of the country deserves your flexibility. For month-specific travel strategies, consult our guides for <a href="/plan/vietnam-in-december/">Vietnam in December</a> and <a href="/plan/vietnam-in-february/">Vietnam in February</a>, or follow a <a href="/routes/vietnam-rainy-season-flexible-route/">flexible rainy season route</a> during monsoon months.'
    },
    # Targets: where-to-stay-in-vietnam-base-decisions, vietnam-first-trip-planning-checklist
    {
        'parent': '14-days-in-vietnam',
        'desc': 'Link where-to-stay-in-vietnam-base-decisions and vietnam-first-trip-planning-checklist',
        'target': 'The easiest way to make a two-week itinerary feel expensive is to stop changing hotels without a reason. Use nights as the real planning currency: they reveal whether the trip is a route or a blur.',
        'replacement': 'The easiest way to make a two-week itinerary feel expensive is to stop changing hotels without a reason. Use our framework on <a href="/plan/where-to-stay-in-vietnam-base-decisions/">where to stay in Vietnam</a> to choose anchors, and consult our <a href="/plan/vietnam-first-trip-planning-checklist/">first trip planning checklist</a> before locking dates. Use nights as the real planning currency: they reveal whether the trip is a route or a blur.'
    },
    # Targets: ninh-binh-without-rushing
    {
        'parent': '14-days-in-vietnam',
        'desc': 'Link ninh-binh-without-rushing',
        'target': 'Go overnight if you want calmer mornings. Make it a day trip only if you need fewer hotel changes or have limited luggage flexibility.',
        'replacement': 'Go overnight if you want calmer mornings (see our advice on <a href="/destinations/ninh-binh-without-rushing/">visiting Ninh Binh without rushing</a>). Make it a day trip only if you need fewer hotel changes or have limited luggage flexibility.'
    },
    # Targets: ha-long-bay-cruise-questions-before-booking
    {
        'parent': '14-days-in-vietnam',
        'desc': 'Link ha-long-bay-cruise-questions-before-booking',
        'target': 'Book the cruise as a logistics product, not just a pretty photo: pickup point, port, cabin, cancellation policy, and weather process matter.',
        'replacement': 'Book the cruise as a logistics product, not just a pretty photo: pickup point, port, cabin, cancellation policy, and weather process matter (review our <a href="/destinations/ha-long-bay-cruise-questions-before-booking/">Ha Long Bay cruise questions before booking</a>).'
    },
    # Targets: best-vietnam-routes-first-time-visitors
    {
        'parent': '14-days-in-vietnam',
        'desc': 'Link best-vietnam-routes-first-time-visitors',
        'target': 'A 14-day Vietnam itinerary has two good shapes. The first is a balanced north-central-south route: Hanoi, countryside, bay, central heritage, Hoi An, Ho Chi Minh City, and one southern add-on.',
        'replacement': 'A 14-day Vietnam itinerary has two good shapes (compare alternative routes in our guide to the <a href="/routes/best-vietnam-routes-first-time-visitors/">best Vietnam routes for first-time visitors</a>). The first is a balanced north-central-south route: Hanoi, countryside, bay, central heritage, Hoi An, Ho Chi Minh City, and one southern add-on.'
    },

    # 17. Parent: vietnam-travel-guide
    # Targets: best-vietnam-routes-first-time-visitors, best-vietnam-cities-for-first-time-visitors, where-to-stay-in-vietnam-base-decisions
    {
        'parent': 'vietnam-travel-guide',
        'desc': 'Link best-vietnam-routes, best-vietnam-cities, and where-to-stay-in-vietnam',
        'target': 'keep the trip to two strong regions if time is short, price the route honestly, and leave transfer buffer before high-value experiences.',
        'replacement': 'keep the trip to two strong regions if time is short (compare curated schedules in our guide to the <a href="/routes/best-vietnam-routes-first-time-visitors/">best Vietnam routes for first-time visitors</a>, review urban hubs with our guide to <a href="/destinations/best-vietnam-cities-for-first-time-visitors/">best Vietnam cities for first-time visitors</a>, and choose strategic bases using our guide on <a href="/plan/where-to-stay-in-vietnam-base-decisions/">where to stay in Vietnam</a>), price the route honestly, and leave transfer buffer before high-value experiences.'
    }
]

def main():
    print(f"=== Testing {len(OPS)} Master Link Operations ===")
    errors = 0
    passed = 0

    for idx, op in enumerate(OPS, 1):
        p_slug = op['parent']
        if p_slug not in parents:
            print(f"[{idx} FAIL] Parent '{p_slug}' not in cache!")
            errors += 1
            continue

        content = parents[p_slug]['content']
        target = op['target']
        if target not in content:
            print(f"[{idx} FAIL] Target not found in '{p_slug}': {op['desc']}")
            print(f"         Target: {target[:70]}...")
            errors += 1
        else:
            cnt = content.count(target)
            if cnt > 1:
                print(f"[{idx} FAIL] Target occurs {cnt} times in '{p_slug}': {op['desc']}")
                errors += 1
            else:
                passed += 1

    print(f"\nTarget Verification: {passed} PASSED, {errors} ERRORS.")

    # Count simulated in-links per target
    target_in_counts = {}
    for op in OPS:
        old_links = set(re.findall(r'href=["\'](?:https?://vietnamguide\.net)?(/[^"\'#?]+)', op['target']))
        new_links = set(re.findall(r'href=["\'](?:https?://vietnamguide\.net)?(/[^"\'#?]+)', op['replacement']))
        added = new_links - old_links
        for a in added:
            slug = a.strip('/').split('/')[-1]
            target_in_counts.setdefault(slug, []).append(op['parent'])

    print(f"\nTotal targets receiving new in-links: {len(target_in_counts)}")
    for s, plist in sorted(target_in_counts.items()):
        print(f"  /{s}/: +{len(plist)} in-links from {plist}")

if __name__ == '__main__':
    main()
