# -*- coding: utf-8 -*-
"""
VietnamGuide: Extract hotlinked images from the 64 Group B posts
"""
import paramiko
import json
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

def main():
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=20)

    with open('ops/media_audit_result.json', 'r', encoding='utf-8') as f:
        audit_data = json.load(f)

    group_b = audit_data['has_figure_no_thumb']
    ids = [p['id'] for p in group_b]

    php_script = f"""<?php
global $wpdb;
$ids = implode(',', {json.dumps(ids)});
$posts = $wpdb->get_results("
    SELECT ID, post_name, post_title, post_content
    FROM {{$wpdb->posts}}
    WHERE ID IN ($ids)
    ORDER BY FIELD(ID, $ids)
", ARRAY_A);

$out = [];
foreach ($posts as $p) {{
    $content = $p['post_content'];
    preg_match('#<figure\s+class="vg-guide-photo".*?</figure>#s', $content, $m);
    $figure = $m ? $m[0] : '';
    
    preg_match('#src="([^"]+)"#i', $figure, $m_src);
    $src = $m_src ? $m_src[1] : '';

    preg_match('#alt="([^"]+)"#i', $figure, $m_alt);
    $alt = $m_alt ? $m_alt[1] : '';

    preg_match('#<figcaption>(.*?)</figcaption>#s', $figure, $m_cap);
    $caption = $m_cap ? trim($m_cap[1]) : '';

    $out[] = [
        'id' => (int)$p['ID'],
        'slug' => $p['post_name'],
        'title' => $p['post_title'],
        'src' => $src,
        'alt' => $alt,
        'caption' => $caption,
        'figure_html' => $figure
    ];
}}
echo json_encode($out);
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/extract_b.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/extract_b.php --path={WP_PATH} --allow-root")
    raw = stdout.read().decode('utf-8')
    ssh.exec_command("rm -f /tmp/extract_b.php")
    ssh.close()

    json_line = [l for l in raw.strip().splitlines() if l.strip().startswith('[')][-1]
    items = json.loads(json_line)

    print(f"Extracted figures from {len(items)} posts.")
    hotlinked = [it for it in items if it['src'].startswith('http') and 'vietnamguide.net' not in it['src']]
    local = [it for it in items if 'vietnamguide.net' in it['src'] or it['src'].startswith('/wp-content/')]
    missing = [it for it in items if not it['src']]

    print(f"Hotlinked external sources: {len(hotlinked)}")
    print(f"Already local sources: {len(local)}")
    print(f"Missing src: {len(missing)}")

    with open('ops/group_b_extracted.json', 'w', encoding='utf-8') as f:
        json.dump(items, f, indent=2, ensure_ascii=False)
    print("Saved to ops/group_b_extracted.json")

if __name__ == '__main__':
    main()
