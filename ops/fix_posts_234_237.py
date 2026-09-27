# -*- coding: utf-8 -*-
"""
VietnamGuide: Fix first <figure> in posts 234 and 237 to include class="vg-guide-photo" and local URL
"""
import paramiko
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

    php_script = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
global $wpdb;

$fixes = [
    234 => [
        'slug' => 'phu-quoc-travel-guide',
        'url' => 'https://vietnamguide.net/wp-content/uploads/2026/07/234_phu-quoc-travel-guide.jpg',
        'alt' => 'Kem Beach aerial view on Phu Quoc Island',
        'caption' => 'Kem Beach shows why Phu Quoc can work as a polished island resort finish. Image: <a href="https://commons.wikimedia.org/wiki/File:Kem_Beach_aerial_view_Phu_Quoc_Island_Vietnam.jpg" target="_blank" rel="license noopener">Vivu Vietnam / CC BY-SA 4.0</a>.'
    ],
    237 => [
        'slug' => 'con-dao-travel-guide',
        'url' => 'https://vietnamguide.net/wp-content/uploads/2026/07/237_con-dao-travel-guide.jpg',
        'alt' => 'Beach view from a quiet Con Dao resort area',
        'caption' => "Con Dao's beach value is quiet and premium, not maximum convenience. Image: <a href=\\"https://commons.wikimedia.org/wiki/File:Beach_view_from_Six_Senses_Resort_in_C%C3%B4n_%C4%90%E1%BA%A3o_(April_2022).jpg\\" target=\\"_blank\\" rel=\\"license noopener\\">Daeva Trac / CC BY-SA 4.0</a>."
    ]
];

foreach ($fixes as $pid => $data) {
    $p = $wpdb->get_row($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d;", $pid), ARRAY_A);
    if (!$p) continue;
    $content = $p['post_content'];

    $fig = '<figure class="vg-guide-photo"><img src="' . $data['url'] . '" alt="' . $data['alt'] . '" width="1280" height="960" loading="lazy" decoding="async"><figcaption>' . $data['caption'] . '</figcaption></figure>';

    // Replace first <figure>
    $pattern = '#<figure\b[^>]*>.*?</figure>#s';
    $new_content = preg_replace($pattern, addcslashes($fig, '$'), $content, 1);

    if ($new_content !== $content) {
        wp_update_post([
            'ID' => $pid,
            'post_content' => $new_content
        ], true);
        echo "Successfully updated post {$pid}\\n";
    } else {
        echo "No change for post {$pid}\\n";
    }
}
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/fix_234_237.php', 'w') as f:
        f.write(php_script)
    sftp.close()

    stdin, stdout, stderr = ssh.exec_command(f"wp eval-file /tmp/fix_234_237.php --path={WP_PATH} --allow-root")
    print(stdout.read().decode('utf-8'))
    err = stderr.read().decode('utf-8')
    if err.strip():
        print("ERR:", err)

    ssh.exec_command("rm -f /tmp/fix_234_237.php")
    ssh.exec_command(f"wp litespeed-purge all --path={WP_PATH} --allow-root && killall -9 lsphp")
    ssh.close()
    print("Purged cache.")

if __name__ == '__main__':
    main()
