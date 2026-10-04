# -*- coding: utf-8 -*-
"""
VietnamGuide Production SFTP Deployer
Deploys updated theme widgets and CSS to VPS with SHA-256 parity verification and cache purge.
"""

import argparse
from datetime import datetime, timezone
import hashlib
import os
from pathlib import Path
import posixpath
import shlex
import sys
import uuid

try:
    from deploy_config import DeployConfig, load_deploy_config
except ImportError:  # pragma: no cover - supports package imports from repository root
    from ops.deploy_config import DeployConfig, load_deploy_config

REPO_ROOT = Path(__file__).resolve().parents[1]

DEPLOY_FILES = [
    ('wordpress/wp-content/themes/vietnamguide-premium/header.php',
     'wp-content/themes/vietnamguide-premium/header.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/footer.php',
     'wp-content/themes/vietnamguide-premium/footer.php'),
    ('wordpress/ads.txt',
     'ads.txt'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-seo.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/search.php',
     'wp-content/themes/vietnamguide-premium/search.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/404.php',
     'wp-content/themes/vietnamguide-premium/404.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/assets/css/homepage.css',
     'wp-content/themes/vietnamguide-premium/assets/css/homepage.css'),
    ('wordpress/wp-content/themes/vietnamguide-premium/site.webmanifest',
     'wp-content/themes/vietnamguide-premium/site.webmanifest'),
    ('wordpress/wp-content/themes/vietnamguide-premium/assets/images/vg-icon.svg',
     'wp-content/themes/vietnamguide-premium/assets/images/vg-icon.svg'),
    ('wordpress/wp-content/themes/vietnamguide-premium/assets/images/vg-icon-192.png',
     'wp-content/themes/vietnamguide-premium/assets/images/vg-icon-192.png'),
    ('wordpress/wp-content/themes/vietnamguide-premium/assets/images/vg-icon-512.png',
     'wp-content/themes/vietnamguide-premium/assets/images/vg-icon-512.png'),
    ('wordpress/sw.js',
     'sw.js'),
    ('wordpress/offline.html',
     'offline.html'),
    ('wordpress/wp-content/themes/vietnamguide-premium/assets/js/homepage.js',
     'wp-content/themes/vietnamguide-premium/assets/js/homepage.js'),
    ('wordpress/wp-content/themes/vietnamguide-premium/index.php',
     'wp-content/themes/vietnamguide-premium/index.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/template-parts/content-page.php',
     'wp-content/themes/vietnamguide-premium/template-parts/content-page.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/template-parts/guide-page.php',
     'wp-content/themes/vietnamguide-premium/template-parts/guide-page.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/functions.php',
     'wp-content/themes/vietnamguide-premium/functions.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-packing-checklist.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-packing-checklist.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-cost-calculator.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-visa-checker.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-season-matrix.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-season-matrix.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-airport-navigator.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-airport-navigator.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-itinerary-finder.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-itinerary-finder.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-aio.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-aio.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-routing.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-route-registry.json',
     'wp-content/themes/vietnamguide-premium/inc/guide-route-registry.json'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-rollout.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-rollout.php'),
    ('wordpress/wp-content/themes/vietnamguide-premium/inc/guide-analytics.php',
     'wp-content/themes/vietnamguide-premium/inc/guide-analytics.php'),
    ('wordpress/.htaccess',
     '.htaccess'),
    ('wordpress/BingSiteAuth.xml',
     'BingSiteAuth.xml'),
    ('wordpress/852ef594b29d4da5a639612da3430b0f.txt',
     '852ef594b29d4da5a639612da3430b0f.txt'),
]

def get_sha256(filepath: Path) -> str:
    return hashlib.sha256(filepath.read_bytes()).hexdigest().lower()


def get_remote_path(config: DeployConfig, remote_relative: str) -> str:
    root = config.remote_root.rstrip('/')
    candidate = posixpath.normpath(posixpath.join(root, remote_relative))
    if candidate != root and not candidate.startswith(root + '/'):
        raise ValueError(f"remote path escapes configured root: {remote_relative}")
    return candidate


def validate_local_files() -> list[tuple[str, Path]]:
    files = []
    for local_relative, _remote_relative in DEPLOY_FILES:
        local_path = REPO_ROOT / local_relative.replace('/', os.sep)
        if not local_path.is_file():
            raise FileNotFoundError(f"deploy source file not found: {local_relative}")
        files.append((local_relative, local_path))
    return files


def connect_ssh(config: DeployConfig):
    import paramiko

    key_path = Path(config.key_path)
    known_hosts = Path(config.known_hosts)
    if not key_path.is_file():
        raise FileNotFoundError(f"deploy key not found: {key_path}")
    if not known_hosts.is_file():
        raise FileNotFoundError(f"known_hosts file not found: {known_hosts}")

    client = paramiko.SSHClient()
    client.load_host_keys(str(known_hosts))
    client.set_missing_host_key_policy(paramiko.RejectPolicy())
    client.connect(
        config.host,
        port=config.port,
        username=config.user,
        key_filename=str(key_path),
        timeout=15,
        look_for_keys=False,
        allow_agent=False,
    )
    return client


def backup_remote_files(ssh, config: DeployConfig) -> str:
    """Create a timestamped, per-file rollback copy before uploading anything."""

    backup_id = datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ') + '-' + uuid.uuid4().hex[:12]
    backup_root = posixpath.join(config.remote_root, 'wp-content', '.vietnamguide-deployment-backups')
    backup_dir = posixpath.join(backup_root, backup_id)
    manifest_path = posixpath.join(backup_dir, 'backup-manifest.tsv')
    commands = [
        'set -eu',
        f'mkdir -p -- {shlex.quote(backup_dir)}',
        f': > {shlex.quote(manifest_path)}',
    ]

    for _local_relative, remote_relative in DEPLOY_FILES:
        source = get_remote_path(config, remote_relative)
        destination = posixpath.join(backup_dir, remote_relative)
        destination_parent = posixpath.dirname(destination)
        source_q = shlex.quote(source)
        destination_q = shlex.quote(destination)
        commands.extend(
            [
                f'mkdir -p -- {shlex.quote(destination_parent)}',
                f'if [ -f {source_q} ]; then '
                f'cp -p -- {source_q} {destination_q}; '
                f'printf "%s\\t%s\\n" PRESENT {shlex.quote(remote_relative)} >> {shlex.quote(manifest_path)}; '
                f'sha256sum -- {source_q} >> {shlex.quote(manifest_path)}; '
                f'else printf "%s\\t%s\\n" MISSING {shlex.quote(remote_relative)} >> {shlex.quote(manifest_path)}; fi',
            ]
        )

    stdin, stdout, stderr = ssh.exec_command('; '.join(commands))
    exit_status = stdout.channel.recv_exit_status()
    if exit_status != 0:
        error = stderr.read().decode('utf-8', errors='replace').strip()
        raise RuntimeError(f'remote backup failed ({exit_status}): {error}')
    return backup_dir


def purge_cache(ssh, config: DeployConfig) -> None:
    cache_paths = [
        posixpath.join(config.remote_root, 'wp-content/litespeed/cssjs'),
        posixpath.join(config.remote_root, 'wp-content/litespeed/htmlc'),
        posixpath.join(config.remote_root, 'wp-content/cache/litespeed'),
    ]
    quoted_paths = [shlex.quote(path) + '/*' for path in cache_paths]
    command = 'set -eu; rm -rf -- ' + ' '.join(quoted_paths)
    stdin, stdout, stderr = ssh.exec_command(command)
    exit_status = stdout.channel.recv_exit_status()
    if exit_status != 0:
        error = stderr.read().decode('utf-8', errors='replace').strip()
        raise RuntimeError(f'cache purge failed ({exit_status}): {error}')


def deploy(config: DeployConfig | None = None, *, dry_run: bool = False, purge: bool = False) -> None:
    local_files = validate_local_files()
    if dry_run:
        print('=== Dry-run: validating deploy artifact and hashes ===')
        for local_relative, local_path in local_files:
            print(f'  [OK] {local_relative}: {get_sha256(local_path)}')
        print(f'Validated {len(local_files)} deploy files; no network connection made.')
        return

    if config is None:
        config = load_deploy_config()

    print(f'=== Deploying theme artifact to {config.user}@{config.host}:{config.port} ===')
    ssh = connect_ssh(config)
    all_matched = True
    try:
        backup_dir = backup_remote_files(ssh, config)
        print(f'Created rollback backup: {backup_dir}')
        sftp = ssh.open_sftp()
        try:
            for (local_relative, local_path), (_unused, remote_relative) in zip(local_files, DEPLOY_FILES):
                remote_path = get_remote_path(config, remote_relative)
                local_hash = get_sha256(local_path)
                print(f'Uploading: {local_relative} -> {remote_path}')
                sftp.put(str(local_path), remote_path)

                command = f"sha256sum -- {shlex.quote(remote_path)}"
                _stdin, stdout, _stderr = ssh.exec_command(command)
                remote_out = stdout.read().decode('utf-8', errors='replace').strip()
                remote_hash = remote_out.split()[0].lower() if remote_out else ''
                if local_hash == remote_hash:
                    print(f'  [OK] SHA256 parity: {local_hash}')
                else:
                    print(f'  [FAIL] SHA256 mismatch: local={local_hash}, remote={remote_hash}')
                    all_matched = False
        finally:
            sftp.close()

        if not all_matched:
            raise RuntimeError('one or more files failed SHA256 parity verification')

        if purge:
            print('Purging configured LiteSpeed cache directories...')
            purge_cache(ssh, config)
        else:
            print('Cache purge skipped; pass --purge-cache only after post-upload verification.')
    finally:
        ssh.close()

    print('=== Deployment complete ===')


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--dry-run', action='store_true', help='validate local files without SSH')
    parser.add_argument('--purge-cache', action='store_true', help='purge configured public cache paths after parity checks')
    args = parser.parse_args(argv)

    try:
        deploy(dry_run=args.dry_run, purge=args.purge_cache)
    except (FileNotFoundError, RuntimeError, ValueError, OSError) as exc:
        print(f'Deploy aborted: {exc}', file=sys.stderr)
        return 2
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
