# -*- coding: utf-8 -*-
import paramiko

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

stdin, stdout, stderr = ssh.exec_command(f'wp post get 627 --field=post_content --path={WP_PATH} --allow-root')
content = stdout.read().decode('utf-8')

# 1. Enrich Section 1 intro with ly-son-travel-guide
old_s1 = "<p>Ly Son consists of two inhabited islands (Dao Lon / Big Island and Dao Be / Little Island) formed by ancient submarine volcanic eruptions."
new_s1 = "<p>Ly Son consists of two inhabited islands (Dao Lon / Big Island and Dao Be / Little Island) formed by ancient submarine volcanic eruptions (see our comprehensive <a href=\"/destinations/ly-son-travel-guide/\">Ly Son travel guide</a> for scooters, stays, and scenic routes)."

# 2. Enrich Section 2 intro with cham-islands-travel-guide
old_s2 = "<p>Lying just 15 kilometers offshore from Hoi An, the Cham archipelago comprises eight granite islands enveloped in dense tropical rainforest."
new_s2 = "<p>Lying just 15 kilometers offshore from Hoi An, the Cham archipelago comprises eight granite islands enveloped in dense tropical rainforest (explore our detailed <a href=\"/destinations/cham-islands-travel-guide/\">Cham Islands travel guide</a> for tour bookings and homestays)."

# 3. Add closing planning block if not present
closing_block = '<p>For full regional route planning, consult our <a href="/plan/vietnam-travel-guide/">Vietnam Travel Guide</a>, compare coastal bases in <a href="/destinations/best-beaches-in-vietnam/">Best Beaches in Vietnam</a> and <a href="/destinations/best-islands-in-vietnam/">Best Islands in Vietnam</a>, or coordinate mainland itineraries with <a href="/compare/da-nang-vs-hoi-an/">Da Nang vs Hoi An</a>, <a href="/itineraries/14-days-in-vietnam/">14 Days in Vietnam</a>, and <a href="/itineraries/21-days-in-vietnam/">21 Days in Vietnam</a>.</p>'

content = content.replace(old_s1, new_s1)
content = content.replace(old_s2, new_s2)
if closing_block not in content:
    content = content + "\n\n" + closing_block

sftp = ssh.open_sftp()
with sftp.file('/tmp/update_627.txt', 'w') as f:
    f.write(content)

php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;
$new_content = file_get_contents('/tmp/update_627.txt');
$res = wp_update_post([
    'ID' => 627,
    'post_content' => $new_content
], true);
if (is_wp_error($res)) {
    echo "Error: " . $res->get_error_message();
} else {
    $wpdb->update(
        $wpdb->posts,
        ['post_modified' => '2026-09-21 08:30:00', 'post_modified_gmt' => '2026-09-21 08:30:00'],
        ['ID' => 627]
    );
    echo "Success: 627 updated";
}
"""

with sftp.file('/tmp/run_update_627.php', 'w') as f:
    f.write(php_script)
sftp.close()


stdin, stdout, stderr = ssh.exec_command(f'wp eval-file /tmp/run_update_627.php --path={WP_PATH} --allow-root')
print(stdout.read().decode('utf-8'))
ssh.exec_command('rm -f /tmp/update_627.txt /tmp/run_update_627.php')
ssh.exec_command(f'wp litespeed-purge all --path={WP_PATH} --allow-root')
ssh.close()
