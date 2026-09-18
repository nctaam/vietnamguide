# -*- coding: utf-8 -*-
"""
VietnamGuide Stage 49: Destinations Link Mesh Applier
Inserts high-value in-content editorial links to /destinations/ in 10-days-in-vietnam,
14-days-in-vietnam, and vietnam-travel-cost, elevating /destinations/ to 5 incoming links.
"""
import paramiko
import json

SSH_HOST = '66.42.48.146'
SSH_PORT = 2209
SSH_USER = 'root'
SSH_KEY = r'C:\Users\NCTaam\.ssh\deploy_bot_key'
WP_PATH = '/usr/local/lsws/vietnamguide.net/html'

OPS = [
    {
        'post_id': 19,
        'slug': '10-days-in-vietnam',
        'desc': 'Link to /destinations/ in 10-day itinerary route advice',
        'target': '<p>If this is your first Vietnam trip and you have about ten days on the ground, build the trip around two strong regions. North Vietnam gives you Hanoi, countryside, and limestone-bay scenery. Central Vietnam gives you imperial history, Hoi An, coastal food, and an easier final airport if Da Nang flights work for you.</p>',
        'replacement': '<p>If this is your first Vietnam trip and you have about ten days on the ground, build the trip around two strong regions selected from our <a href="/destinations/">Vietnam destination guides</a>. North Vietnam gives you Hanoi, countryside, and limestone-bay scenery. Central Vietnam gives you imperial history, Hoi An, coastal food, and an easier final airport if Da Nang flights work for you.</p>'
    },
    {
        'post_id': 20,
        'slug': '14-days-in-vietnam',
        'desc': 'Link to /destinations/ in 14-day itinerary decision framework',
        'target': "<p>The two-week route should start with a job, not a destination list. Choose the route shape that solves the trip's main problem, then remove anything that does not support that job.</p>",
        'replacement': '<p>The two-week route should start with a trip objective, not a generic bucket list. Rather than skimming individual <a href="/destinations/">Vietnam destinations</a>, choose the route shape that solves your main travel priorities, then remove any transfer that does not support that focus.</p>'
    },
    {
        'post_id': 22,
        'slug': 'vietnam-travel-cost',
        'desc': 'Link to /destinations/ in cost planning lens paragraph',
        'target': '<p>The cleanest Vietnam budget is built from named choices: where you sleep, how often you move, which transfers protect energy, and which one or two experiences deserve a higher spend. A daily average is useful only after those choices are visible.</p>',
        'replacement': '<p>The cleanest Vietnam budget is built from named choices: which <a href="/destinations/">destinations across Vietnam</a> you choose as anchor bases, where you sleep, how often you move, which transfers protect energy, and which one or two experiences deserve a higher spend. A daily average is useful only after those choices are visible.</p>'
    }
]

def main():
    print("=== Applying Stage 49 Destinations Link Mesh Insertions ===")
    pkey = paramiko.Ed25519Key.from_private_key_file(SSH_KEY)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, pkey=pkey, timeout=15)

    sftp = ssh.open_sftp()
    with sftp.file('/tmp/stage49_dest_ops.json', 'w') as f:
        f.write(json.dumps(OPS, ensure_ascii=False, indent=2))

    php_code = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
global $wpdb;

$json_str = file_get_contents('/tmp/stage49_dest_ops.json');
$ops = json_decode($json_str, true);
$updated = 0;
$errors = 0;

foreach ($ops as $op) {
    $postId = $op['post_id'];
    $slug = $op['slug'];
    $row = $wpdb->get_row($wpdb->prepare("SELECT ID, post_content FROM {$wpdb->posts} WHERE ID = %d AND post_status = 'publish'", $postId));
    if (!$row) {
        echo "[ERROR] Post ID {$postId} ($slug) not found\n";
        $errors++;
        continue;
    }

    $content = $row->post_content;
    $target = $op['target'];
    $replacement = $op['replacement'];

    if (strpos($content, $target) !== false) {
        $new_content = str_replace($target, $replacement, $content);
        $result = $wpdb->update(
            $wpdb->posts,
            array('post_content' => $new_content),
            array('ID' => $row->ID),
            array('%s'),
            array('%d')
        );
        if ($result !== false) {
            echo "[SUCCESS] Applied '{$op['desc']}' on post $slug (ID {$row->ID})\n";
            $updated++;
        } else {
            echo "[ERROR] Database update failed for post $slug (ID {$row->ID})\n";
            $errors++;
        }
    } else {
        if (strpos($content, $replacement) !== false) {
            echo "[SKIP] Already applied on post $slug (ID {$row->ID})\n";
        } else {
            echo "[FAIL] Target string not found on post $slug (ID {$row->ID})\n";
            $errors++;
        }
    }
}

echo "\nSummary: $updated updated, $errors errors.\n";
"""

    with sftp.file('/tmp/apply_stage49_dest_links.php', 'w') as f:
        f.write(php_code)
    sftp.close()

    print("Executing remote Stage 49 link insertion script...")
    cmd = f"wp eval-file /tmp/apply_stage49_dest_links.php --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    print(out)
    if err.strip():
        print(f"Stderr: {err}")

    ssh.exec_command("rm -f /tmp/apply_stage49_dest_links.php /tmp/stage49_dest_ops.json")

    print("Purging LiteSpeed cache...")
    purge_cmd = f"wp litespeed-purge all --path={WP_PATH} --allow-root"
    stdin, stdout, stderr = ssh.exec_command(purge_cmd)
    print(stdout.read().decode('utf-8'))

    ssh.close()
    print("=== Stage 49 Destinations Link Mesh Insertion Complete ===")

if __name__ == '__main__':
    main()
