# VietnamGuide.net WordPress Development Spec

Date: 2026-07-13
Project: vietnamguide.net
Platform: WordPress on an already-provisioned VPS
Primary audience: English-speaking international travelers planning trips to Vietnam
Primary channel: Organic search
Document status: Design spec for review

## 1. Executive Summary

VietnamGuide.net will be developed as an English-first, SEO-led practical travel planning website for international visitors to Vietnam. The site should not begin as a generic travel blog or visual landing page. It should begin as a structured publishing platform that helps travelers make decisions quickly: where to go, how long to stay, when to travel, what it costs, how to enter Vietnam, and how to move around safely.

The recommended product direction is:

> Practical Vietnam travel planning for first-time and repeat international visitors who want current, independent, no-fluff guidance.

The site will use WordPress as the editorial CMS, with a lightweight theme, strict SEO structure, repeatable content templates, high performance targets, clear source attribution, and a staged monetization path through affiliate partnerships only after trust and organic visibility are established.

Sensitive operational credentials must never be stored in this repository, the WordPress database as plain documentation, screenshots, public issues, exported docs, or committed files. This spec intentionally excludes server login details and WordPress admin credentials.

## 2. Current State

The user reports that a raw WordPress installation is already present on a VPS. The local repository currently contains only Git metadata and no WordPress source, theme, plugin code, deployment scripts, or documentation.

Known domain/DNS findings from prior inspection:

- The domain is registered through Namecheap.
- Nameservers are set to Namecheap registrar servers.
- At the time of inspection, the apex and www hostnames did not resolve to a web server.

The first implementation phase should therefore verify and complete:

- DNS records for apex and www.
- HTTPS certificate.
- WordPress admin hardening.
- Server firewall and SSH hardening.
- Backup strategy before any content or theme work.

## 3. Strategic Goals

### Business Goals

- Build topical authority around Vietnam travel planning in English.
- Reach organic search visibility for long-tail and mid-tail Vietnam travel queries.
- Establish user trust before monetization.
- Create a content system that can scale from 20 pages to 150+ pages without becoming messy.
- Prepare the site for future affiliate revenue from eSIM, insurance, tours, hotels, transportation, and itinerary downloads.

### User Goals

Users should be able to answer these questions quickly:

- Is Vietnam a good fit for my trip?
- How many days do I need?
- Which cities and regions should I include?
- What is the best season for my route?
- What visa or entry rules apply?
- How much will the trip cost?
- How do I move between places?
- Which destination should I choose when I cannot visit everything?
- What mistakes should I avoid?

### SEO Goals

- Build a clean indexable site architecture.
- Publish helpful, original, people-first content with source links and update dates.
- Use descriptive URLs, internal links, breadcrumbs, and XML sitemaps.
- Achieve strong Core Web Vitals before traffic scales.
- Build content clusters rather than isolated posts.

## 4. Non-Goals

The first release will not include:

- A custom booking engine.
- User accounts for travelers.
- A full interactive trip planner.
- A multilingual site.
- A marketplace for tours or hotels.
- Heavy page builders.
- Auto-generated AI content at scale.
- Aggressive display advertising.

These can be evaluated later after the site has search traction and a stable editorial workflow.

## 5. Market Thesis

Vietnam tourism is growing strongly. Public tourism reporting from VNA/VietnamPlus and Vietnam Pictorial indicates:

- Vietnam reached nearly 21.2 million international arrivals in 2025.
- 2025 arrivals exceeded the pre-pandemic 2019 benchmark.
- Vietnam targets about 25 million international arrivals in 2026.
- H1 2026 reached nearly 12.3 million international visitors, up 14.9% year on year.
- Air arrivals account for the majority of international arrivals, making airport, entry, domestic flight, and route-planning content important.

English content should focus first on users from the US, UK, Australia, India, Singapore, the EU, and other English-searching travelers. Large source markets such as China, Korea, Japan, and Taiwan can inform the long-term multilingual roadmap, but they should not split focus in the first phase.

## 6. Positioning

VietnamGuide.net should sit between official tourism promotion, guidebook publishers, travel blogs, and tour operators.

Official tourism sites are trustworthy but often promotional. Guidebook publishers have authority but can be broad and less operationally specific. Travel blogs can be experience-rich but inconsistent. Tour operators have useful itineraries but are commercially biased.

VietnamGuide.net should own the following position:

> Independent practical guidance with clear decisions, current logistics, real trade-offs, and structured route planning.

Every important guide should include a direct recommendation, not just a list of options.

## 7. Brand and Editorial Voice

The voice should be:

- Clear.
- Practical.
- Calm.
- Honest about trade-offs.
- Respectful toward Vietnam and local culture.
- Useful for first-time visitors without being simplistic.

Avoid:

- Overly poetic travel writing.
- Generic destination hype.
- Unsupported superlatives.
- Keyword-stuffed paragraphs.
- Content that reads like a tour brochure.
- Claims about safety, law, visa, weather, or health without update context.

Preferred content pattern:

- Give the quick answer first.
- Explain the trade-offs.
- Add practical details.
- Link to deeper pages.
- Cite official or reliable sources where facts may change.

## 8. Information Architecture

### Primary Navigation

Recommended top-level navigation:

- Plan
- Destinations
- Itineraries
- Things To Do
- Compare
- Costs

Optional secondary navigation:

- Visa
- Transport
- Best Time
- Food
- Safety
- About

### URL Structure

Use short, descriptive, folder-based URLs:

- `/plan/vietnam-evisa/`
- `/plan/best-time-to-visit-vietnam/`
- `/plan/getting-around-vietnam/`
- `/destinations/hanoi/`
- `/destinations/hoi-an/`
- `/itineraries/10-days-in-vietnam/`
- `/itineraries/northern-vietnam-7-days/`
- `/compare/ha-long-bay-vs-lan-ha-bay/`
- `/costs/vietnam-travel-cost/`

Avoid date-based URLs, post IDs, and deeply nested city/category paths for core evergreen guides.

## 9. Content Clusters

### Cluster 1: Planning Hub

Purpose: Capture broad first-time planning intent and distribute internal links to deeper pages.

Core pages:

- Vietnam Travel Guide
- Vietnam for First-Time Visitors
- How Many Days in Vietnam
- Best Time to Visit Vietnam
- Vietnam Travel Mistakes
- Vietnam Packing List

### Cluster 2: Visa and Entry Hub

Purpose: Capture high-intent practical searches and build trust through accurate updates.

Core pages:

- Vietnam E-Visa Guide
- Vietnam Visa Requirements
- Vietnam Visa Exemption Countries
- Vietnam Entry Airports and Border Gates
- Vietnam E-Visa Mistakes to Avoid

Maintenance rule: These pages require scheduled review at least monthly, and immediately after any official visa update.

### Cluster 3: Itinerary Hub

Purpose: Capture high-volume planning keywords and create natural internal links to destination pages.

Core pages:

- 7 Days in Vietnam
- 10 Days in Vietnam
- 14 Days in Vietnam
- 21 Days in Vietnam
- Northern Vietnam Itinerary
- Central Vietnam Itinerary
- Southern Vietnam Itinerary
- Vietnam Family Itinerary
- Vietnam Food Itinerary
- Vietnam Beach Itinerary

### Cluster 4: Destination Hub

Purpose: Build topical authority across major destinations and support itinerary pages.

Initial destinations:

- Hanoi
- Ho Chi Minh City
- Da Nang
- Hoi An
- Hue
- Ninh Binh
- Ha Long Bay
- Lan Ha Bay
- Sapa
- Ha Giang
- Phu Quoc
- Can Tho
- Mekong Delta
- Dalat
- Nha Trang
- Phong Nha
- Con Dao

### Cluster 5: Comparison Hub

Purpose: Win decision-stage searches where big guidebooks often underperform.

Initial comparison pages:

- Ha Long Bay vs Lan Ha Bay
- Sapa vs Ha Giang
- Hanoi vs Ho Chi Minh City
- Da Nang vs Nha Trang
- Hoi An vs Hue
- Phu Quoc vs Con Dao
- North Vietnam vs Central Vietnam
- Vietnam vs Thailand for First-Time Travelers

### Cluster 6: Cost and Logistics Hub

Purpose: Capture practical planning searches and support affiliate conversion later.

Core pages:

- Vietnam Travel Cost
- Vietnam Backpacking Budget
- Mid-Range Vietnam Trip Cost
- Cash, Cards, and ATMs in Vietnam
- SIM and eSIM in Vietnam
- Domestic Flights in Vietnam
- Vietnam Trains Guide
- Vietnam Sleeper Buses Guide
- Grab in Vietnam

### Cluster 7: Seasonal and Risk Hub

Purpose: Capture recurring seasonal demand and reduce visitor uncertainty.

Core pages:

- Vietnam Rainy Season
- Vietnam in January
- Vietnam in February
- Vietnam in March
- Vietnam in April
- Vietnam in December
- Traveling Vietnam During Tet
- Worst Time to Visit Vietnam
- Is Vietnam Safe?

## 10. Content Type Model

WordPress can implement this using standard posts plus categories and custom fields, or with custom post types. The recommended first phase is:

- Use Pages for core evergreen hub pages.
- Use Posts for editorial guides.
- Use Categories only for broad content groups.
- Use Tags sparingly.
- Use ACF or native custom fields for structured guide metadata.

If the site reaches 100+ guides and the editorial model becomes complex, add custom post types:

- Destination
- Itinerary
- Comparison
- Practical Guide

### Shared Fields

Most guide pages should support:

- Last reviewed date.
- Last updated date.
- Primary destination.
- Region.
- Recommended trip length.
- Best months.
- Budget range.
- Travel style.
- Author.
- Reviewer.
- Official source URLs.
- Affiliate disclosure flag.

## 11. Page Templates

### Destination Template

Sections:

- Quick verdict.
- Best for.
- Not ideal for.
- How many days to spend.
- Best time to visit.
- Top things to do.
- Where to stay.
- Food highlights.
- How to get there.
- How to get around.
- Cost range.
- Suggested itinerary links.
- Common mistakes.
- Nearby destinations.
- FAQ.
- Sources and update history.

### Itinerary Template

Sections:

- Quick route summary.
- Who this itinerary is for.
- Route map placeholder.
- Day-by-day plan.
- Transport between stops.
- Recommended pace.
- Budget estimate.
- Upgrade and budget alternatives.
- What to skip if short on time.
- Extension options.
- Related destination guides.
- FAQ.

### Practical Guide Template

Sections:

- Quick answer.
- Current status or rule.
- Step-by-step guidance.
- Official source links.
- Common mistakes.
- Edge cases.
- When to verify externally.
- FAQ.
- Last reviewed date.

### Comparison Template

Sections:

- Quick verdict.
- Summary comparison table.
- Choose A if.
- Choose B if.
- Cost comparison.
- Time required.
- Accessibility.
- Best season.
- Crowd level.
- Suggested itinerary pairings.
- FAQ.

## 12. WordPress Technical Architecture

### Recommended Stack

- WordPress latest stable release.
- PHP 8.3 or newer.
- MariaDB 10.11+ or MySQL 8.0+.
- HTTPS enabled.
- Nginx or Apache with rewrite support.
- Cloudflare DNS/CDN recommended.
- Server-side caching where possible.

### Theme

Recommended options:

- GeneratePress.
- Kadence.
- Blocksy.
- A minimal custom block theme later if needed.

Avoid heavy visual builders during phase 1. The site should prioritize editorial speed, clean HTML, maintainability, and performance.

### Plugins

Install only what is necessary.

Core plugins:

- SEO: Rank Math or Yoast SEO.
- Cache/performance: LiteSpeed Cache if supported by server, otherwise WP Rocket or equivalent.
- Image optimization: ShortPixel, Imagify, or a server-side WebP/AVIF workflow.
- Custom fields: ACF.
- Backup: UpdraftPlus or host-level automated backups.
- Security: Wordfence, Patchstack, or host-level WAF plus strict login protection.
- Redirects: Redirection.
- Analytics integration: Site Kit or manual GA4/Search Console setup.

Do not install:

- Multiple SEO plugins.
- Multiple cache plugins.
- Heavy sliders.
- Unused form builders.
- Auto-blogging plugins.
- AI content generators that publish directly.

## 13. SEO Requirements

### Technical SEO

- XML sitemap submitted to Google Search Console and Bing Webmaster Tools.
- Clean robots.txt.
- Canonical URLs for all indexable pages.
- No indexation of internal search results, thin tag archives, author archives if not useful, and staging URLs.
- Breadcrumb navigation on all guide pages.
- BreadcrumbList structured data.
- Article structured data for guides.
- FAQ structured data only when the on-page FAQ is genuinely useful.
- Descriptive title tags and meta descriptions.
- Open Graph image per major guide.
- No orphan pages.

### Internal Linking Rules

Each guide should link to:

- Parent hub page.
- At least 2 related destination pages where relevant.
- At least 2 related practical pages where relevant.
- At least 1 itinerary page where relevant.
- Official source links for volatile facts.

Each hub page should link down to all relevant child pages and explain how to choose among them.

### Content Quality Rules

Each published page should pass this checklist:

- The main question is answered in the first 150 words.
- There is a clear recommendation or verdict.
- The article contains original organization, decision help, or practical insight.
- Volatile facts are dated and sourced.
- The page has a useful table, checklist, map placeholder, or decision framework where appropriate.
- Internal links are contextual, not dumped at the end.
- The page has a visible last updated or last reviewed date.
- The author or site expertise is discoverable.

## 14. Performance Requirements

Target Core Web Vitals:

- LCP: 2.5 seconds or less.
- INP: 200 milliseconds or less.
- CLS: 0.1 or less.

Implementation requirements:

- Use compressed images with explicit dimensions.
- Prefer WebP or AVIF.
- Lazy-load below-the-fold images.
- Avoid layout shifts from ads, embeds, fonts, and maps.
- Defer non-critical JavaScript.
- Avoid heavy third-party scripts in phase 1.
- Use a system font stack or carefully loaded web fonts.
- Cache full pages for anonymous visitors.

## 15. Security and Operations

Immediate hardening tasks:

- Change any shared/default WordPress admin password after setup.
- Store credentials in a password manager, never in Git.
- Disable password login over SSH if key-based access is configured.
- Keep SSH on the custom port if already configured, but also use firewall rules.
- Install and configure fail2ban or equivalent login protection.
- Disable XML-RPC unless needed.
- Enforce HTTPS and secure cookies.
- Set correct file permissions.
- Ensure daily backups for files and database.
- Test restore procedure before publishing content at scale.

Backups:

- Daily database backup.
- Weekly full-site backup.
- Off-server backup storage.
- Monthly restore test.

Access:

- Use named admin/editor accounts instead of shared credentials.
- Use least privilege for writers.
- Enable 2FA for all admin users.

## 16. Analytics and Measurement

Required tools:

- Google Search Console.
- Bing Webmaster Tools.
- GA4.
- Microsoft Clarity or a privacy-conscious equivalent.
- PageSpeed Insights.

Events to track:

- Outbound affiliate clicks.
- Email signup.
- PDF download.
- Internal search.
- Scroll depth on pillar pages.
- Clicks from itinerary pages to destination pages.

SEO reports:

- Weekly during first 90 days.
- Monthly after 90 days.

Core metrics:

- Indexed pages.
- Impressions by cluster.
- Clicks by cluster.
- Average position by cluster.
- Pages with no impressions after 60 days.
- Pages with high impressions but low CTR.
- Pages with rankings 8-20 for improvement.

## 17. Editorial Workflow

### Publishing Pipeline

1. Keyword and SERP review.
2. Search intent classification.
3. Outline using the relevant page template.
4. Source collection.
5. Draft.
6. Editorial review.
7. Fact review for volatile details.
8. SEO review.
9. Image optimization.
10. Publish.
11. Submit or request indexing for priority pages.
12. Recheck after 30, 60, and 90 days.

### Update Cadence

- Visa and entry pages: monthly review.
- Cost pages: quarterly review.
- Seasonal pages: before relevant season.
- Destination pages: twice yearly.
- Itinerary pages: twice yearly.
- Safety and health pages: monthly source check.

## 18. Initial 100-Page Roadmap

### First 20 Pages

1. Vietnam Travel Guide
2. Vietnam for First-Time Visitors
3. Best Time to Visit Vietnam
4. Vietnam E-Visa Guide
5. Vietnam Visa Requirements
6. 10 Days in Vietnam
7. 14 Days in Vietnam
8. Vietnam Travel Cost
9. How to Get Around Vietnam
10. Hanoi Travel Guide
11. Ho Chi Minh City Travel Guide
12. Da Nang Travel Guide
13. Hoi An Travel Guide
14. Ninh Binh Travel Guide
15. Ha Long Bay vs Lan Ha Bay
16. Sapa vs Ha Giang
17. SIM and eSIM in Vietnam
18. Is Vietnam Safe?
19. Vietnamese Food Guide for First-Time Visitors
20. Vietnam Travel Mistakes

### Pages 21-50

- Hue Travel Guide
- Ha Long Bay Travel Guide
- Lan Ha Bay Travel Guide
- Sapa Travel Guide
- Ha Giang Travel Guide
- Phu Quoc Travel Guide
- Can Tho Travel Guide
- Mekong Delta Travel Guide
- Dalat Travel Guide
- Nha Trang Travel Guide
- Phong Nha Travel Guide
- 7 Days in Vietnam
- 21 Days in Vietnam
- Northern Vietnam Itinerary
- Central Vietnam Itinerary
- Southern Vietnam Itinerary
- Vietnam Family Itinerary
- Vietnam Food Itinerary
- Vietnam Beach Itinerary
- Vietnam Backpacking Budget
- Cash and ATMs in Vietnam
- Domestic Flights in Vietnam
- Vietnam Trains Guide
- Vietnam Sleeper Buses Guide
- Traveling Vietnam During Tet
- Vietnam Rainy Season
- Vietnam Packing List
- Where to Stay in Vietnam
- Grab in Vietnam
- Vietnam Airport Guide

### Pages 51-100

Expand comparison, seasonal, and monetization-support content:

- Hanoi vs Ho Chi Minh City
- Da Nang vs Nha Trang
- Hoi An vs Hue
- Phu Quoc vs Con Dao
- North Vietnam vs Central Vietnam
- Vietnam vs Thailand for First-Time Travelers
- Vietnam in January
- Vietnam in February
- Vietnam in March
- Vietnam in April
- Vietnam in May
- Vietnam in June
- Vietnam in July
- Vietnam in August
- Vietnam in September
- Vietnam in October
- Vietnam in November
- Vietnam in December
- Best eSIM for Vietnam
- Best Travel Insurance for Vietnam
- Best Day Trips from Hanoi
- Best Day Trips from Ho Chi Minh City
- Best Cruises in Ha Long Bay
- Best Areas to Stay in Hanoi
- Best Areas to Stay in Ho Chi Minh City
- Best Areas to Stay in Da Nang
- Best Areas to Stay in Hoi An
- Vietnam Street Food Safety
- Vietnam Coffee Guide
- Vietnam Markets Guide
- Vietnam Beaches Guide
- Vietnam UNESCO Sites Guide
- Vietnam for Digital Nomads
- Vietnam with Kids
- Vietnam Honeymoon Itinerary
- Vietnam Luxury Itinerary
- Vietnam Motorcycle Travel Safety
- Vietnam Tipping Guide
- Vietnam Language Basics
- Vietnam Scams and Tourist Traps
- Vietnam Weather by Region
- Vietnam Border Gates for E-Visa
- Vietnam Entry Airports
- Vietnam Domestic Airlines Guide
- Vietnam Train Routes
- Vietnam Bus Routes
- Vietnam Ferry Guide
- Vietnam Responsible Travel Guide
- Vietnam Photography Guide
- Vietnam Emergency Numbers

## 19. Monetization Roadmap

### Phase 1: Trust First

No aggressive ads. No intrusive affiliate modules. Focus on content quality, speed, and credibility.

### Phase 2: Low-Friction Affiliate

Add contextual affiliate links where they solve real user needs:

- eSIM.
- Travel insurance.
- Airport transfers.
- Day tours.
- Domestic transport.

### Phase 3: Higher-Value Affiliate

After organic traction:

- Hotel booking.
- Cruises.
- Multi-day tours.
- Custom itinerary PDFs.

### Disclosure

All affiliate pages and links must include clear disclosure. Affiliate links should use proper `sponsored` or `nofollow` attributes where appropriate.

## 20. Milestones

### Milestone 0: Foundation

Acceptance criteria:

- DNS resolves for apex and www.
- HTTPS works.
- WordPress login is secure.
- Backup and restore path is verified.
- Search Console and GA4 are configured.
- Theme and core plugins installed.
- Permalinks configured.
- Noindex disabled only when ready to launch.

### Milestone 1: SEO Launch

Acceptance criteria:

- 20 priority pages published or staged.
- XML sitemap submitted.
- Robots and canonical rules verified.
- Core templates finalized.
- Internal links between first 20 pages complete.
- PageSpeed passes reasonable lab checks on mobile.

### Milestone 2: Authority Build

Acceptance criteria:

- 50 published pages.
- At least 4 content clusters complete enough to crawl as a topical structure.
- Search Console impressions visible.
- Initial update workflow running.

### Milestone 3: Scale and Monetize Carefully

Acceptance criteria:

- 100+ published pages.
- At least 10 pages ranking in striking distance positions 8-20.
- Affiliate links added only to relevant high-intent pages.
- Conversion tracking tested.
- Content refresh process established.

## 21. Risks and Mitigations

Risk: Competing against large travel publishers.
Mitigation: Focus on decision pages, comparisons, current logistics, and long-tail route planning.

Risk: Visa and legal information becoming outdated.
Mitigation: Add last reviewed dates, official source links, and monthly review tasks.

Risk: WordPress performance degrading with plugins.
Mitigation: Keep plugin count low, cache pages, optimize images, and avoid heavy builders.

Risk: Thin AI-like content.
Mitigation: Use structured templates, source checks, original decision frameworks, and editorial review.

Risk: Monetization reducing trust.
Mitigation: Delay monetization and keep affiliate placement contextual and disclosed.

Risk: Credentials leaking into code or docs.
Mitigation: Never commit credentials, use a password manager, rotate passwords after shared operational use, and use least-privilege accounts.

## 22. Implementation Boundaries

This spec defines the site direction and development target. It does not yet implement:

- Theme customization.
- WordPress plugin installation.
- Server hardening.
- DNS changes.
- Content drafting.
- Analytics setup.

Those should be handled in a separate implementation plan after this spec is reviewed and approved.

## 23. Source References

- VietnamPlus/VNA, international arrivals H1 2026: https://en.vietnamplus.vn/international-tourist-arrivals-to-vietnam-rise-nearly-15-in-first-half-of-2026-post347706.vnp
- Vietnam Pictorial/VNA, 2025 tourism record: https://vietnam.vnanet.vn/english/tin-van/international-arrivals-to-vietnam-hit-new-record-in-2025-up-over-20-420386.html
- Vietnam.travel visa requirements: https://vietnam.travel/plan-your-trip/visa-requirements
- Vietnam.travel plan your trip: https://vietnam.travel/plan-your-trip
- WordPress requirements: https://wordpress.org/about/requirements/
- Google SEO starter guide: https://developers.google.com/search/docs/fundamentals/seo-starter-guide
- Google helpful content guidance: https://developers.google.com/search/docs/fundamentals/creating-helpful-content
- Google localized versions and hreflang: https://developers.google.com/search/docs/specialty/international/localized-versions
- Google breadcrumb structured data: https://developers.google.com/search/docs/appearance/structured-data/breadcrumb
- web.dev Core Web Vitals: https://web.dev/articles/vitals

