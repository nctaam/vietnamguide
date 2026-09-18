# -*- coding: utf-8 -*-
"""
VietnamGuide Traffic & Crawler Log Analyzer
Pulls and parses /usr/local/lsws/logs/vietnamguide.net.access.log from VPS to give real-time
telemetry on human visitors, search crawlers (Googlebot, Bingbot), AI agents, referrers, and errors.
"""

import re
import sys
from collections import Counter
import paramiko

HOST = '66.42.48.146'
PORT = 2209
USER = 'root'
KEY_PATH = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
REMOTE_LOG = '/usr/local/lsws/logs/vietnamguide.net.access.log'

# Log format: "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\""
LOG_REGEX = re.compile(
    r'^(?P<ip>\S+)\s+\S+\s+\S+\s+\[(?P<time>[^\]]+)\]\s+"(?P<method>\S+)\s+(?P<path>\S+)(?:\s+\S+)?"\s+(?P<status>\d{3})\s+(?P<bytes>\S+)\s+"(?P<referer>[^"]*)"\s+"(?P<ua>[^"]*)"'
)

BOT_PATTERNS = [
    ('Googlebot', re.compile(r'Googlebot|Google-InspectionTool|Google-Extended|Mediapartners-Google', re.I)),
    ('Bingbot', re.compile(r'bingbot|BingPreview', re.I)),
    ('GPTBot/ChatGPT', re.compile(r'GPTBot|ChatGPT-User|OAI-SearchBot', re.I)),
    ('ClaudeBot', re.compile(r'ClaudeBot|Claude-Web|Anthropic', re.I)),
    ('PerplexityBot', re.compile(r'PerplexityBot', re.I)),
    ('Applebot', re.compile(r'Applebot', re.I)),
    ('Yandex/Baidu/Seznam', re.compile(r'YandexBot|Baiduspider|SeznamBot', re.I)),
    ('Other Bot/Scraper', re.compile(r'bot|crawl|spider|slurp|curl|wget|python|headless|ahrefs|semrush', re.I)),
]

def classify_ua(ua):
    for name, pattern in BOT_PATTERNS:
        if pattern.search(ua):
            return name
    return 'Human Visitor'

def main():
    print(f"Connecting to VPS {HOST} to fetch traffic logs...")
    pkey = paramiko.Ed25519Key.from_private_key_file(KEY_PATH)
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, port=PORT, username=USER, pkey=pkey, timeout=15)
    
    stdin, stdout, stderr = client.exec_command(f"cat {REMOTE_LOG}")
    raw_logs = stdout.read().decode('utf-8', errors='ignore')
    client.close()
    
    lines = [line.strip() for line in raw_logs.splitlines() if line.strip()]
    if not lines:
        print("[INFO] Access log is currently empty. Visit https://vietnamguide.net to generate telemetry.")
        return
        
    print(f"\n=======================================================")
    print(f"  VIETNAMGUIDE.NET TELEMETRY REPORT ({len(lines)} requests)")
    print(f"=======================================================\n")
    
    ua_categories = Counter()
    status_codes = Counter()
    paths = Counter()
    referrers = Counter()
    human_ips = set()
    total_bytes = 0
    
    for line in lines:
        m = LOG_REGEX.match(line)
        if not m:
            continue
        ip = m.group('ip')
        path = m.group('path')
        status = m.group('status')
        byte_val = m.group('bytes')
        referer = m.group('referer')
        ua = m.group('ua')
        
        category = classify_ua(ua)
        ua_categories[category] += 1
        status_codes[status] += 1
        paths[path] += 1
        
        if referer and referer != '-':
            referrers[referer] += 1
            
        if category == 'Human Visitor':
            human_ips.add(ip)
            
        try:
            total_bytes += int(byte_val)
        except ValueError:
            pass

    print("=== VISITOR BREAKDOWN ===")
    for cat, count in ua_categories.most_common():
        pct = (count / len(lines)) * 100
        print(f"  {cat:<25}: {count:>5} requests ({pct:>5.1f}%)")
    print(f"  Unique Human IPs Detected: {len(human_ips)}")
    print(f"  Total Data Transferred   : {total_bytes / (1024*1024):.2f} MB\n")
    
    print("=== HTTP STATUS CODES ===")
    for code, count in status_codes.most_common():
        print(f"  HTTP {code}: {count} requests")
    print()
    
    print("=== TOP 10 REQUESTED PATHS ===")
    for p, count in paths.most_common(10):
        print(f"  {count:>4}x  {p}")
    print()
    
    if referrers:
        print("=== TOP REFERRERS ===")
        for ref, count in referrers.most_common(10):
            print(f"  {count:>4}x  {ref}")
        print()
    else:
        print("=== TOP REFERRERS ===")
        print("  (Direct visits / no external HTTP referer header recorded yet)\n")

if __name__ == '__main__':
    main()
