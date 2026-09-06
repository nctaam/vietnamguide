# -*- coding: utf-8 -*-
import os
import sys
import paramiko

sys.stdout.reconfigure(encoding='utf-8')

HOST = '66.42.48.146'
PORT = 2209
USER = 'root'
KEY_PATH = r'C:\Users\NCTaam\.ssh\deploy_bot_key'

def get_client():
    pkey = paramiko.Ed25519Key.from_private_key_file(KEY_PATH)
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, port=PORT, username=USER, pkey=pkey, timeout=15)
    return client

def exec_cmd(cmd):
    client = get_client()
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='ignore')
    err = stderr.read().decode('utf-8', errors='ignore')
    client.close()
    return out, err

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Usage: python run_remote.py <command>")
        sys.exit(1)

    cmd = sys.argv[1]
    out, err = exec_cmd(cmd)
    if out:
        print(out.strip())
    if err:
        print("STDERR:", err.strip())
