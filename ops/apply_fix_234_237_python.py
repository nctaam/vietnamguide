# -*- coding: utf-8 -*-
"""
VietnamGuide: Fix first <figure> in posts 234 and 237 using Python regex and update via SFTP/WP-CLI
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

FIXES = [
    {
        'id': 234,
        'slug': 'phu-quoc-travel-guide',
        'url': 'https://vietnamguide.net/wp-content/uploads/2026/07/234_phu-quoc-travel-guide.jpg',
        'alt': 'Kem Beach aerial view on Phu Quoc Island',
        'caption': 'Kem Beach shows why Phu Quoc can work as a polished island resort finish. Image: <a href="https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>.'
    },
    {
        'id': 237,
        'slug': 'con-dao-travel-guide',
        'url': 'https://vietnamguide.net/wp-content/uploads/2026/07/237_con-dao-travel-guide.jpg',
        'alt': 'Beach view from a quiet Con Dao resort area',
        'caption': "Con Dao's beach value is quiet and premium, not maximum convenience. Image: <a href=\"https://commons.wikimedia.org/wiki/File:Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_(April_2022).jpg\" target=\"_blank\" rel=\"license noopener\">Daeva Trac / CC BY-SA 4.0</a>."
    }
]

def main():
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=20)

    for it in FIXES:
        pid = it['id']
        slug = it['slug']
        fig_html = f'<figure class="vg-guide-photo"><img src="{it["url"]}" alt="{it["alt"]}" width="1280" height="960" loading="lazy" decoding="async"><figcaption>{it["caption"]}</figcaption></figure>'

        stdin, stdout, stderr = ssh.exec_command(f"wp post get {pid} --field=post_content --path={WP_PATH} --allow-root")
        content = stdout.read().decode('utf-8')

        pat = re.compile(r'<figure\b[^>]*>.*?</figure>', re.DOTALL)
        m = pat.search(content)
        if not m:
            print(f"ERROR: No figure matched in post {pid}")
            continue

        print(f"Matched figure in post {pid}: {m.group(0)[:80]}...")
        new_content = pat.sub(fig_html, content, count=1)

        # Upload new content to /tmp
        sftp = ssh.open_sftp()
        with sftp.file(f"/tmp/content_{pid}.txt", "w") as f:
            f.write(new_content)
        sftp.close()

        # Update via wp post update
        stdin, stdout, stderr = ssh.exec_command(f"wp post update {pid} /tmp/content_{pid}.txt --path={WP_PATH} --allow-root")
        out = stdout.read().decode('utf-8')
        print(f"Post {pid} update result: {out.strip()}")
        ssh.exec_command(f"rm -f /tmp/content_{pid}.txt")

    print("\nPurging cache...")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root && killall -9 lsphp")
    ssh.close()
    print("Done.")

if __name__ == '__main__':
    main()
