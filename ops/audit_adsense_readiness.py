import urllib.request
import ssl
import json
import re

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

def fetch(url):
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=10) as resp:
            return resp.status, resp.read().decode('utf-8', errors='ignore')
    except Exception as e:
        return None, str(e)

print("==================================================")
print("   VIETNAMGUIDE ADSENSE COMPLIANCE & READINESS   ")
print("==================================================")

# 1. ads.txt check
print("\n[1] ADS.TXT VERIFICATION")
s, c = fetch('https://vietnamguide.net/ads.txt')
print(f"Status: {s}")
print(f"Content: {c.strip() if c else 'EMPTY'}")
ads_ok = s == 200 and 'pub-9279498490263775' in (c or '')
print(f"Result: {'PASS' if ads_ok else 'FAIL'}")

# 2. robots.txt check
print("\n[2] ROBOTS.TXT CRAWLER DIRECTIVES")
s, c = fetch('https://vietnamguide.net/robots.txt')
print(f"Status: {s}")
mediapartners_ok = 'Mediapartners-Google' in (c or '')
adsbot_ok = 'AdsBot-Google' in (c or '')
print(f"Mediapartners-Google explicitly allowed: {mediapartners_ok}")
print(f"AdsBot-Google explicitly allowed: {adsbot_ok}")

# 3. Privacy Policy check for mandatory Google AdSense clauses
print("\n[3] PRIVACY POLICY MANDATORY ADSENSE CLAUSES")
s, html = fetch('https://vietnamguide.net/privacy-policy/')
print(f"Status: {s}")
if html:
    checks = {
        'Mentions Google': 'google' in html.lower(),
        'Mentions Cookies': 'cookie' in html.lower(),
        'Mentions Third-Party Advertisers / Vendors': 'third-party' in html.lower() or 'third party' in html.lower(),
        'Mentions Personalized / Interest Advertising': 'interest' in html.lower() or 'personalized' in html.lower(),
        'Mentions AdSense / DART / DoubleClick': 'adsense' in html.lower() or 'doubleclick' in html.lower() or 'dart' in html.lower(),
        'Mentions Google Ads Settings opt-out link': 'google.com/settings/ads' in html.lower() or 'adssettings.google.com' in html.lower(),
        'Mentions AboutAds / Network Advertising opt-out link': 'aboutads.info' in html.lower() or 'networkadvertising.org' in html.lower(),
    }
    for label, passed in checks.items():
        print(f"  - {label}: {'PASS' if passed else 'FAIL (MANDATORY FOR ADSENSE)'}")

# 4. Terms of Service
print("\n[4] TERMS OF SERVICE (E-E-A-T & TRANSPARENCY)")
s, html = fetch('https://vietnamguide.net/terms-of-service/')
print(f"Status /terms-of-service/: {s}")
s2, html2 = fetch('https://vietnamguide.net/terms/')
print(f"Status /terms/: {s2}")

# 5. About Page
print("\n[5] ABOUT PAGE (E-E-A-T & PUBLISHER IDENTITY)")
s, html = fetch('https://vietnamguide.net/about/')
print(f"Status: {s}")
if html:
    print(f"  Length: {len(html)} chars")
    print(f"  Editorial methodology mentioned: {'editorial' in html.lower()}")
    print(f"  Physical address or location mentioned: {'district' in html.lower() or 'hcm' in html.lower() or 'le duan' in html.lower()}")

# 6. Contact Page
print("\n[6] CONTACT PAGE (ACCESSIBILITY & WORKING INQUIRIES)")
s, html = fetch('https://vietnamguide.net/contact/')
print(f"Status: {s}")
if html:
    print(f"  Email contact present: {'@vietnamguide.net' in html}")
    print(f"  Physical office / address present: {'le duan' in html.lower() or 'ho chi minh' in html.lower()}")

# 7. Broken links check on Footer
print("\n[7] FOOTER LINKS AUDIT")
s, html = fetch('https://vietnamguide.net/')
if html:
    footer_match = re.search(r'<footer[^>]*>(.*?)</footer>', html, re.DOTALL | re.IGNORECASE)
    if footer_match:
        links = re.findall(r'href=["\'](https?://vietnamguide\.net/[^"\']*)["\']', footer_match.group(1))
        print(f"Found {len(links)} internal links in footer. Checking status:")
        for link in set(links):
            ls, _ = fetch(link)
            print(f"  - {link} -> HTTP {ls}")
