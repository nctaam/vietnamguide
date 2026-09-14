import json
import paramiko
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\NCTaam\.ssh\deploy_bot_key')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.42.48.146', port=2209, username='root', pkey=pkey)

cmd = (
    "wp db query \"SELECT ID, post_name, post_content FROM Sf5bm6_posts "
    "WHERE post_type='page' AND post_status='publish';\" "
    "--path=/usr/local/lsws/vietnamguide.net/html --allow-root"
)
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8')
lines = out.strip().splitlines()

faq_pages = []
for line in lines[1:]:
    parts = line.split('\t')
    if len(parts) >= 3:
        pid, slug, content = parts[0], parts[1], parts[2]
        if 'faq' in content.lower() or 'frequently asked' in content.lower():
            # count questions
            q_matches = re.findall(r'<h[234][^>]*>(.*?\?)\s*</h[234]>', content, re.I)
            faq_pages.append((pid, slug, len(q_matches), q_matches))

print(f"Pages with FAQ sections: {len(faq_pages)}")
for pid, slug, cnt, qs in faq_pages[:15]:
    print(f"  ID {pid} /{slug}/ -> {cnt} question headings:")
    for q in qs[:3]:
        clean_q = re.sub(r'<[^>]+>', '', q).strip()
        print(f"     Q: {clean_q}")
