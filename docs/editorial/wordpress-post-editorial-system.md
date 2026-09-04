# VietnamGuide WordPress Post Editorial System

**Principle:** WordPress Posts are the source of truth for calendar content. Scripts may create draft briefs and run verification-only QA, but complete writing, images, Rank Math tuning, internal links, categories, tags, and publishing decisions happen in WordPress Admin.

## What Belongs in Posts

Use Posts for supporting evergreen content that grows the site without duplicating pillar Pages:

- First-time planning checklists.
- Mistake-prevention articles.
- Smaller destination decisions.
- Food, culture, etiquette, and market/cafe topics.
- Arrival, packing, rainy-season, and logistics support articles.
- Search Console response articles when a query deserves its own answer.

Keep Pages for pillars, major destinations, main itineraries, and high-value comparison pages.

## Editorial Categories

The native category system should make the calendar easy to scan:

- Travel Planning
- Itineraries
- Destinations
- Transport & Logistics
- Food & Culture
- Beaches & Islands
- Practicalities
- Hotels & Neighborhoods
- Seasonal Travel
- Heritage & Culture

Tags should describe traveler jobs and content use cases, not keyword-stuffing variants.

## Draft Brief Workflow

The `apply-wordpress-post-editorial-system.php` script creates draft briefs only. It does not publish or schedule future posts.

Each draft brief includes:

- Search intent.
- Reader job.
- Evidence moat.
- Internal link plan.
- Target publish date.
- Publish gate.

Before publishing, replace brief language with a complete article. Do not publish the brief itself.

## Batch Expansion

Batch 2 extends the native post system with deeper evergreen intent clusters for monthly weather, Tet, city comparisons, heritage decisions, beach planning, cave planning, and mountain planning.

The second batch is still draft-only and Admin-first locked. It adds:

- Vietnam in December, January, and February.
- Tet in Vietnam Travel Guide.
- Best Vietnam Cities for First-Time Visitors.
- Hanoi vs Ho Chi Minh City.
- Hoi An Ancient Town Guide.
- Hue Imperial City Guide.
- Da Nang Beaches Guide.
- Mekong Delta Overnight vs Day Trip.
- Phong Nha Travel Guide.
- Sapa vs Ha Giang.

Batch 2 uses the same publish gate as Batch 1, plus explicit source-plan, image-plan, and what-not-to-write metadata so writers can expand the brief into a durable article without drifting into keyword-variant spam.

The batch 2 drafts store source guidance in `vg_editorial_sources_to_check` and image guidance in `vg_editorial_image_plan`, so the brief itself already carries the research trail the editor needs before manual expansion.

## Batch 3

Batch 3 opens the northern mountains and scenery support cluster. It is draft-only and Admin-first locked. The goal is to deepen the route value created by Hanoi, Ninh Binh, Ha Long Bay, Sapa vs Ha Giang, Best Time, Transport, Insurance, and Safety pages without creating keyword-variant spam.

Batch 3 adds:

- Sapa Travel Guide.
- Ha Giang Loop Planning Guide.
- Hanoi to Sapa Transport.
- Hanoi to Ha Giang Transport.
- Ha Giang Safety Guide.
- Ha Giang Easy Rider vs Self-Drive.
- Sapa Trekking Guided vs Self-Guided.
- Where to Stay in Sapa.
- Best Time for Northern Vietnam.
- Vietnam Rice Terraces Guide.
- Mu Cang Chai Travel Guide.
- Pu Luong Travel Guide.

Batch 3 should be expanded in dependency order: publish Sapa and Ha Giang pillars first, then transport and safety pages, then trekking/stay support, then seasonal and rice-terrace comparison pages. Each article must answer one traveler decision and preserve the same source-plan, image-plan, anti-spam, update-log, and low-linkout discipline as Batch 2.

## Publish Gate

Every Post must pass this publish gate:

- The article answers one clear traveler decision.
- The first screen gives a practical verdict or decision path.
- The article adds original judgment, not rewritten search results.
- At least one decision table, checklist, route framework, or comparison matrix is present.
- Internal links point to relevant pillar Pages and related Posts.
- Images have useful alt text and text credits.
- Rank Math title, description, focus keyword, and schema are manually tuned.
- The article has an update date and a clear reason for the update.
- No affiliate or outbound link is added unless it helps the reader make a decision.

## One Article Per Day

Publishing one article per day is acceptable only when quality stays above the gate. A skipped day is better than a thin post. If a brief cannot be expanded into a durable piece, update an existing Page/Post instead.

Use this daily rhythm:

- Day before: finish sources, images, internal links, and Rank Math.
- Publish day: final mobile preview, title/meta check, sitemap check.
- Day after: check indexability, internal links, and GSC URL inspection when available.
- 30 days later: review impressions, CTR, and whether the post should link into a stronger hub.

## Verification

Run after changing the native post system:

```bash
wp eval-file ops/verify-wordpress-post-editorial-system.php --allow-root
```

Run locally after code changes:

```powershell
powershell -ExecutionPolicy Bypass -File ops\verify-wordpress-post-system-static.ps1
```
