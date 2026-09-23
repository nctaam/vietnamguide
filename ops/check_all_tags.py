import paramiko

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\NCTaam\.ssh\deploy_bot_key')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('66.42.48.146', port=2209, username='root', pkey=pkey, timeout=15)

php_code = """<?php
global $wpdb;
$sql = "SELECT ID, post_name, post_content FROM {$wpdb->prefix}posts WHERE post_type = 'page' AND post_status = 'publish';";
$posts = $wpdb->get_results($sql, ARRAY_A);

$found_errors = 0;
foreach ($posts as $p) {
    $c = $p['post_content'];
    if (strpos($c, '<a href="/destina') !== false) {
        // check if it's followed immediately by something other than tions/
        if (!preg_match('#<a href="/destinations/#', $c)) {
            echo "ERROR: {$p['post_name']} has malformed /destina tag\\n";
            $found_errors++;
        }
    }
    // check for nested <a inside <a
    if (preg_match('#<a\s+[^>]*<a\s+#i', $c)) {
        echo "ERROR: {$p['post_name']} (ID: {$p['ID']}) has nested/unclosed <a tags!\\n";
        $found_errors++;
    }
}

echo "\\nTotal malformed tag issues found: $found_errors\\n";
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/check_all_tags.php', 'w') as f:
    f.write(php_code)
sftp.close()

cmd = "wp eval-file /tmp/check_all_tags.php --path=/usr/local/lsws/vietnamguide.net/html --allow-root"
stdin, stdout, stderr = ssh.exec_command(cmd)
print(stdout.read().decode('utf-8'))
ssh.exec_command("rm -f /tmp/check_all_tags.php")
ssh.close()
