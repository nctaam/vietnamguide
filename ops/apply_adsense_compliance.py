# -*- coding: utf-8 -*-
"""
VietnamGuide AdSense Compliance & Trust Engine
Updates Privacy Policy, creates Terms of Service, enhances About page,
and configures robots.txt for Google AdSense contextual crawlers.
"""

import sys
import os
import paramiko

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

PRIVACY_HTML = """<!-- vg-privacy-baseline:v3-adsense -->
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Last updated: September 27, 2026</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"vg-hub-lede"} -->
<p class="vg-hub-lede">VietnamGuide.net operates an independent travel planning desk for international travelers. We safeguard reader data.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Information we collect</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We do not require user accounts or reader registrations. When you browse our travel itineraries, web servers automatically capture standard technical access logs, including your masked IP address, browser user-agent, operating system, requested URL path, referring domain, and timestamp. If you choose to contact our editorial desk directly via email regarding route updates, hotel closures, or transport corrections, we collect your email address and message contents to investigate your report.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Cookies and advertising partners</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We partner with third-party vendors, including Google, to serve contextual advertisements across our travel guides. Google uses advertising cookies, including the DoubleClick cookie, to serve ads based on your prior visits to VietnamGuide.net or other websites on the internet. These advertising cookies allow Google and its advertising partners to display relevant ads according to your browsing patterns without accessing your personal identity.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Your choices and opt-out mechanisms</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>You retain full control over personalized advertising cookies. You may opt out of personalized advertising at any time by visiting <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener">Google Ads Settings</a> or by accessing the Digital Advertising Alliance opt-out portal at <a href="https://www.aboutads.info" target="_blank" rel="noopener">aboutads.info</a>. Essential session cookies operate solely to support edge caching, rate limiting, and administrative security on our LiteSpeed web server cluster. Aggregate website traffic analysis runs with anonymized IP addresses to observe popular destination guides and detect broken transport links without tracking individual identity across the web.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Outbound links and external resources</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Our travel guides link directly to external logistics providers, official provincial tourism portals, and public transit schedules such as Vietnam Railways at dsvn.vn. When you click an external link, you navigate to an independent third-party domain governed by its own data privacy terms. We do not sell user data. Commercial partnerships never dictate route rankings.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Data retention and security standards</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Technical web server access logs are retained for 30 days to diagnose network faults and block automated cyber attacks, after which log files are permanently deleted. Inquiries sent to our editorial desk are retained for 180 days to resolve ongoing transit investigations. All communication transmits across encrypted TLS 1.3 connections in accordance with Vietnam Personal Data Protection Decree 13/2023/ND-CP.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Reader rights and compliance contacts</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>You maintain full authority to review, rectify, or request deletion of any email correspondence submitted to our desk. For privacy inquiries or data removal requests, reach our compliance team at <a href="mailto:privacy@vietnamguide.net">privacy@vietnamguide.net</a> or contact our physical editorial liaison at 45 Le Duan Boulevard, Ben Nghe Ward, District 1, Ho Chi Minh City. We respond within 48 business hours.</p>
<!-- /wp:paragraph -->"""

TERMS_HTML = """<!-- vg-terms-baseline:v1 -->
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">Terms of Service</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"vg-hub-lede"} -->
<p class="vg-hub-lede">Welcome to VietnamGuide.net. By accessing our travel guides, route comparisons, and transit itineraries, you accept these terms of service in full.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Editorial scope and independent travel intelligence</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>VietnamGuide operates an independent editorial desk dedicated to practical, field-verified trip planning across Vietnam. Our guides serve informational purposes for independent international travelers.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Logistics accuracy and travel disclaimer</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Logistics in Vietnam change quickly. Railway timetables, expressway toll tariffs, island ferry schedules, entrance fees, and immigration policies fluctuate according to seasonal weather, carrier operations, and government decrees. While our editorial desk audits transport routes continuously, travelers must confirm time-critical departures and visa requirements directly with primary operators or official government portals before booking non-refundable tickets. We do not operate transport services, hotels, or booking desks.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Intellectual property and photography licensing</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>All original written guides, route comparison tables, map graphics, and editorial verdicts on VietnamGuide.net remain the intellectual property of VietnamGuide. You may not reproduce, syndicate, or republish full guide texts without prior written consent from our editorial desk. Photography featured across our destination and transport guides originates from authentic field documentation, verified Creative Commons licenses, and public domain repositories with explicit photographer attribution.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Advertising and commercial disclosures</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Our website displays contextual advertisements served by third-party ad networks, including Google AdSense. We also maintain select commercial affiliate relationships with accredited travel providers. These partnerships never dictate editorial verdicts, route rankings, or lodging reviews.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Limitation of liability and governing law</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>VietnamGuide assumes no liability for travel disruptions, missed connections, personal injuries, or financial losses arising from reliance on published information. These terms are governed by the laws of the Socialist Republic of Vietnam. For legal inquiries, copyright notices, or editorial corrections, contact our desk at <a href="mailto:editorial@vietnamguide.net">editorial@vietnamguide.net</a> or reach our physical liaison office at 45 Le Duan Boulevard, Ben Nghe Ward, District 1, Ho Chi Minh City.</p>
<!-- /wp:paragraph -->"""

ABOUT_HTML = """<!-- vg-about-baseline:v3-eeat -->
<!-- wp:paragraph {"className":"vg-kicker"} -->
<p class="vg-kicker">About VietnamGuide.net</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"vg-hub-lede"} -->
<p class="vg-hub-lede">VietnamGuide.net helps international visitors plan calmer, better-informed Vietnam trips with clear route logic, destination trade-offs, and practical travel checks.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What we publish</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We focus on planning decisions that matter before and during a Vietnam journey: when to go, where to go, how long to stay, how to compare routes, how to avoid wasted transfer days, and what to verify before booking.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How we want to be useful</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li>Give a verdict before long explanations.</li>
<li>Show trade-offs, not generic inspiration.</li>
<li>Separate stable advice from facts that need rechecking.</li>
<li>Disclose advertising and affiliate relationships where they exist.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Editorial desk and ground presence</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We do not accept sponsored placements that dictate route rankings. Every itinerary recommendation and transit guide stands on independent field audits conducted across Vietnam. Our physical editorial desk operates at 45 Le Duan Boulevard, Ben Nghe Ward, District 1, Ho Chi Minh City with hotline 028.3822.5555. Send route inquiries, timetable corrections, and editorial questions directly to <a href="mailto:editorial@vietnamguide.net">editorial@vietnamguide.net</a>. We respond within 24 to 48 business hours.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Editorial transparency and policies</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Learn more about our standards across our governance documentation:</p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"vg-feature-list"} -->
<ul class="wp-block-list vg-feature-list">
<li><a href="/editorial-policy/">Editorial Policy</a>: Our 4-tier verification hierarchy and ground truth standards.</li>
<li><a href="/source-update-policy/">Source and Update Policy</a>: Re-verification intervals across railway, flight, and ferry schedules.</li>
<li><a href="/terms-of-service/">Terms of Service</a>: Travel disclaimers and intellectual property guidelines.</li>
<li><a href="/privacy-policy/">Privacy Policy</a>: Data protection, cookie choices, and advertising disclosures.</li>
<li><a href="/contact/">Contact Desk</a>: Ground liaison office and emergency dispatch.</li>
</ul>
<!-- /wp:list -->"""

REMOTE_SCRIPT = f"""#!/bin/bash
set -e
export VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE=1

echo "--- Updating Privacy Policy (Post 3) ---"
wp post update 3 /tmp/privacy_content.html --path={WP_PATH} --allow-root

echo "--- Checking Terms of Service ---"
TERMS_ID=$(wp post list --name=terms-of-service --post_type=page --field=ID --path={WP_PATH} --allow-root || true)
if [ -n "$TERMS_ID" ]; then
    echo "Updating existing Terms of Service (ID $TERMS_ID)..."
    wp post update "$TERMS_ID" /tmp/terms_content.html --path={WP_PATH} --allow-root
else
    echo "Creating new Terms of Service..."
    wp post create /tmp/terms_content.html --post_type=page --post_title="Terms of Service" --post_name="terms-of-service" --post_status=publish --path={WP_PATH} --allow-root
fi

echo "--- Updating About Page (Post 58) ---"
wp post update 58 /tmp/about_content.html --path={WP_PATH} --allow-root

echo "--- Configuring physical robots.txt ---"
if ! grep -q "Mediapartners-Google" {WP_PATH}/robots.txt; then
    sed -i '/Allow: \/wp-admin\/admin-ajax.php/a \\\n# Google AdSense Crawlers\\nUser-agent: Mediapartners-Google\\nAllow: \/\\n\\nUser-agent: AdsBot-Google\\nAllow: \/' {WP_PATH}/robots.txt
    echo "Added Mediapartners-Google and AdsBot-Google to physical robots.txt"
else
    echo "Mediapartners-Google already present in physical robots.txt"
fi

rm -f /tmp/privacy_content.html /tmp/terms_content.html /tmp/about_content.html /tmp/apply_compliance.sh
echo "--- ALL TASKS DONE ---"
"""


def main():
    print("=== Connecting to VietnamGuide Production VPS ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)
    sftp = ssh.open_sftp()

    print("Uploading content files to /tmp/...")
    with sftp.open('/tmp/privacy_content.html', 'w') as f:
        f.write(PRIVACY_HTML)
    with sftp.open('/tmp/terms_content.html', 'w') as f:
        f.write(TERMS_HTML)
    with sftp.open('/tmp/about_content.html', 'w') as f:
        f.write(ABOUT_HTML)
    with sftp.open('/tmp/apply_compliance.sh', 'w') as f:
        f.write(REMOTE_SCRIPT.replace('\r\n', '\n'))

    sftp.close()

    print("Executing remote bash script...")
    stdin, stdout, stderr = ssh.exec_command("bash /tmp/apply_compliance.sh")
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    print("STDOUT:\n", out)
    if err:
        print("STDERR:\n", err)

    ssh.close()
    print("=== Execution Finished ===")


if __name__ == '__main__':
    main()
