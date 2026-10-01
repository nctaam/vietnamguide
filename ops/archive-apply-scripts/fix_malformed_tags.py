import paramiko

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\NCTaam\.ssh\deploy_bot_key')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.42.48.146', port=2209, username='root', pkey=pkey, timeout=15)

php_code = """<?php
define('VG_ADMIN_FIRST_ALLOW_AUTOMATION_OVERWRITE', true);
add_filter('pre_http_request', function() { return new WP_Error('http_request_failed', 'CLI offline'); }, 999);
global $wpdb;

$fixes = [
    'ha-long-bay-travel-guide' => [
        '<a href="/destinaour activity guide on <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a>',
        'our activity guide on <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a>'
    ],
    'ha-long-bay-vs-lan-ha-bay' => [
        '<a href="/destinaour itinerary guide on <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a>',
        'our itinerary guide on <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a>'
    ],
    'bai-tu-long-bay-guide' => [
        '<a href="/destinaour comprehensive <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a> guide',
        'our comprehensive <a href="/destinations/best-things-to-do-in-ha-long-bay/">Best Things to Do in Ha Long Bay</a> guide'
    ],
    'quy-nhon-travel-guide' => [
        '<a hreour activity breakdown on <a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>',
        'our activity breakdown on <a href="/destinations/best-things-to-do-in-quy-nhon/">Best Things to Do in Quy Nhon</a>'
    ]
];

foreach ($fixes as $slug => $pair) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT ID, post_content FROM {$wpdb->prefix}posts WHERE post_name = %s", $slug), ARRAY_A);
    if (!$row) {
        echo "[ERROR] Could not find slug $slug\\n";
        continue;
    }
    $post_id = (int)$row['ID'];
    $content = $row['post_content'];
    list($target, $replacement) = $pair;
    
    if (strpos($content, $target) === false) {
        echo "[ERROR] Target not found in $slug\\n";
        continue;
    }
    
    $new_content = str_replace($target, $replacement, $content);
    wp_update_post([
        'ID' => $post_id,
        'post_content' => $new_content
    ]);
    $wpdb->update(
        $wpdb->posts,
        [
            'post_modified' => '2026-09-23 11:21:00',
            'post_modified_gmt' => '2026-09-23 11:21:00'
        ],
        ['ID' => $post_id]
    );
    echo "[OK] Fixed malformed tag in $slug (ID: $post_id)\\n";
}

echo "\\nPurging LiteSpeed cache...\\n";
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/fix_malformed_tags.php', 'w') as f:
    f.write(php_code)
sftp.close()

cmd = "wp eval-file /tmp/fix_malformed_tags.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root"
stdin, stdout, stderr = ssh.exec_command(cmd)
print(stdout.read().decode('utf-8'))
ssh.exec_command("rm -f /tmp/fix_malformed_tags.php")
ssh.exec_command("wp litespeed-purge all --path=/usr/local/lsws/vietnamguide.net/html --allow-root")
ssh.close()
print("Cache purged.")
