# -*- coding: utf-8 -*-
"""
VietnamGuide Vhost Access Log Configurator
Safely adds dedicated accessLog directive to OpenLiteSpeed vhost config on the VPS.
"""

import paramiko

HOST = '66.42.48.146'
PORT = 2209
USER = 'root'
KEY_PATH = r'C:\Users\NCTaam\.ssh\deploy_bot_key'

VHOST_PATH = '/usr/local/lsws/conf/vhosts/vietnamguide.net/vietnamguide.net.conf'
ACCESS_LOG_PATH = '/usr/local/lsws/logs/vietnamguide.net.access.log'

ACCESS_LOG_BLOCK = """
accessLog /usr/local/lsws/logs/vietnamguide.net.access.log {
  useServer               0
  logFormat               "%h %l %u %t \\"%r\\" %>s %b \\"%{Referer}i\\" \\"%{User-Agent}i\\""
  logHeaders              7
  rollingSize             100M
  keepDays                30
  compressArchive         1
}
"""

def main():
    pkey = paramiko.Ed25519Key.from_private_key_file(KEY_PATH)
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, port=PORT, username=USER, pkey=pkey, timeout=15)
    
    sftp = client.open_sftp()
    
    # Read current vhost config
    with sftp.open(VHOST_PATH, 'r') as f:
        content = f.read().decode('utf-8')
        
    if 'vietnamguide.net.access.log' in content:
        print("[INFO] accessLog already present in vhost config.")
    else:
        # Backup first
        with sftp.open(VHOST_PATH + '.bak_stage55', 'w') as f:
            f.write(content)
        print("[INFO] Backup saved to .bak_stage55")
        
        # Append block
        new_content = content.rstrip() + "\n" + ACCESS_LOG_BLOCK
        with sftp.open(VHOST_PATH, 'w') as f:
            f.write(new_content)
        print("[SUCCESS] Added accessLog block to vhost config.")
        
    sftp.close()
    
    # Ensure log file exists and restart OLS
    commands = [
        f"touch {ACCESS_LOG_PATH}",
        f"chown nobody:nobody {ACCESS_LOG_PATH}",
        f"chmod 644 {ACCESS_LOG_PATH}",
        "touch /tmp/lshttpd/lsrestart.txt",
        "systemctl reload lsws 2>/dev/null || true"
    ]
    for cmd in commands:
        _, stdout, stderr = client.exec_command(cmd)
        out = stdout.read().decode('utf-8').strip()
        err = stderr.read().decode('utf-8').strip()
        if out:
            print(f"  {out}")
        if err:
            print(f"  STDERR: {err}")
            
    print("[SUCCESS] OpenLiteSpeed reloaded with dedicated accessLog.")
    client.close()

if __name__ == '__main__':
    main()
