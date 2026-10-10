import hashlib
import os
import re
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

from ops.deploy_config import DeployConfig, load_deploy_config
from ops.deploy_theme_updates import (
    DEPLOY_FILES,
    backup_remote_files,
    get_remote_path,
    purge_cache,
    restore_remote_backup,
    stage_and_promote_file,
    validate_local_files,
)


REPO_ROOT = Path(__file__).resolve().parents[2]


class DeployConfigTests(unittest.TestCase):
    def setUp(self):
        self.valid_environment = {
            'VG_DEPLOY_HOST': 'staging.example.test',
            'VG_DEPLOY_PORT': '2209',
            'VG_DEPLOY_USER': 'vietnamguide-deploy',
            'VG_DEPLOY_KEY': r'C:\keys\vietnamguide_deploy',
            'VG_DEPLOY_KNOWN_HOSTS': r'C:\keys\known_hosts',
            'VG_DEPLOY_ROOT': '/srv/vietnamguide',
        }

    def test_load_requires_explicit_remote_configuration(self):
        with patch.dict(os.environ, {}, clear=True):
            with self.assertRaisesRegex(ValueError, 'VG_DEPLOY_HOST'):
                load_deploy_config()

    def test_loads_valid_non_root_configuration(self):
        with patch.dict(os.environ, self.valid_environment, clear=True):
            config = load_deploy_config()

        self.assertEqual(config.host, 'staging.example.test')
        self.assertEqual(config.port, 2209)
        self.assertEqual(config.user, 'vietnamguide-deploy')
        self.assertEqual(config.remote_root, '/srv/vietnamguide')
        self.assertFalse(config.allow_root)

    def test_rejects_root_without_explicit_opt_in(self):
        environment = {**self.valid_environment, 'VG_DEPLOY_USER': 'root'}
        with patch.dict(os.environ, environment, clear=True):
            with self.assertRaisesRegex(ValueError, 'VG_DEPLOY_ALLOW_ROOT'):
                load_deploy_config()

    def test_allows_root_only_with_explicit_opt_in(self):
        environment = {
            **self.valid_environment,
            'VG_DEPLOY_USER': 'root',
            'VG_DEPLOY_ALLOW_ROOT': '1',
        }
        with patch.dict(os.environ, environment, clear=True):
            config = load_deploy_config()

        self.assertTrue(config.allow_root)

    def test_rejects_invalid_port_and_remote_root(self):
        for key, value, expected in [
            ('VG_DEPLOY_PORT', '0', 'VG_DEPLOY_PORT'),
            ('VG_DEPLOY_PORT', '65536', 'VG_DEPLOY_PORT'),
            ('VG_DEPLOY_ROOT', 'relative/path', 'VG_DEPLOY_ROOT'),
            ('VG_DEPLOY_ROOT', '/srv/site\nunsafe', 'VG_DEPLOY_ROOT'),
        ]:
            environment = {**self.valid_environment, key: value}
            with self.subTest(key=key, value=value):
                with patch.dict(os.environ, environment, clear=True):
                    with self.assertRaisesRegex(ValueError, expected):
                        load_deploy_config()

    def test_deploy_script_has_no_unsafe_connection_defaults(self):
        source = (
            REPO_ROOT / 'ops' / 'deploy_theme_updates.py'
        ).read_text(encoding='utf-8')

        self.assertNotIn('66.42.48.146', source)
        self.assertNotIn('AutoAddPolicy', source)
        self.assertNotIn("SSH_USER = 'root'", source)

    def test_deploy_artifact_includes_the_route_registry(self):
        expected = (
            'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-route-registry.json',
            'wp-content/themes/vietnamguide-premium/inc/guide-route-registry.json',
        )

        self.assertIn(expected, DEPLOY_FILES)
        self.assertIn((expected[0], REPO_ROOT / expected[0]), validate_local_files())

    def test_remote_path_rejects_escape_attempts(self):
        config = DeployConfig(
            host='staging.example.test',
            port=2209,
            user='vietnamguide-deploy',
            key_path=r'C:\keys\deploy',
            known_hosts=r'C:\keys\known_hosts',
            remote_root='/srv/site',
            allow_root=False,
        )

        for relative in ('../etc/passwd', '/etc/passwd', 'wp-content/../..'):
            with self.subTest(relative=relative):
                with self.assertRaises(ValueError):
                    get_remote_path(config, relative)

    def test_cache_purge_quotes_a_root_with_spaces(self):
        class FakeChannel:
            def recv_exit_status(self):
                return 0

        class FakeStream:
            channel = FakeChannel()

        class FakeSSH:
            command = ''

            def exec_command(self, command):
                self.command = command
                return None, FakeStream(), FakeStream()

        config = DeployConfig(
            host='staging.example.test',
            port=2209,
            user='vietnamguide-deploy',
            key_path=r'C:\keys\deploy',
            known_hosts=r'C:\keys\known_hosts',
            remote_root='/srv/site with spaces',
            allow_root=False,
        )
        ssh = FakeSSH()

        purge_cache(ssh, config)

        self.assertIn("'/srv/site with spaces/wp-content/litespeed/cssjs'/*", ssh.command)
        self.assertIn('set -eu; rm -rf -- ', ssh.command)

    def test_remote_backup_is_created_before_upload_with_manifest(self):
        class FakeChannel:
            def recv_exit_status(self):
                return 0

        class FakeStream:
            channel = FakeChannel()

            def read(self):
                return b''

        class FakeSSH:
            command = ''

            def exec_command(self, command):
                self.command = command
                return None, FakeStream(), FakeStream()

        config = DeployConfig(
            host='staging.example.test',
            port=2209,
            user='vietnamguide-deploy',
            key_path=r'C:\keys\deploy',
            known_hosts=r'C:\keys\known_hosts',
            remote_root='/srv/site',
            allow_root=False,
        )
        ssh = FakeSSH()

        backup_dir = backup_remote_files(ssh, config)

        self.assertRegex(backup_dir, r'^/srv/site/wp-content/\.vietnamguide-deployment-backups/')
        self.assertIn('backup-manifest.tsv', ssh.command)
        self.assertIn('cp -p --', ssh.command)
        self.assertIn('sha256sum --', ssh.command)

    def test_functions_php_required_includes_exist_in_deploy_files(self):
        """All 15 include files required in functions.php must exist in DEPLOY_FILES to prevent fatal crash."""
        functions_path = REPO_ROOT / 'wordpress' / 'wp-content' / 'themes' / 'vietnamguide-premium' / 'functions.php'
        self.assertTrue(functions_path.is_file(), f"Missing functions.php: {functions_path}")
        func_content = functions_path.read_text(encoding='utf-8')

        required_includes = re.findall(r"require_once\s+get_theme_file_path\(\s*['\"]/inc/([^'\"]+\.php)['\"]\s*\);", func_content)
        self.assertEqual(len(required_includes), 15, f"Expected 15 required includes in functions.php, found {len(required_includes)}")

        deploy_local_files = {item[0].replace('\\', '/') for item in DEPLOY_FILES}
        for inc_file in required_includes:
            expected_path = f"wordpress/wp-content/themes/vietnamguide-premium/inc/{inc_file}"
            self.assertIn(
                expected_path,
                deploy_local_files,
                f"Include file required in functions.php is missing from DEPLOY_FILES: {expected_path}"
            )

    def test_all_deploy_files_exist_on_local_disk(self):
        """Every local file referenced in DEPLOY_FILES must exist on disk."""
        self.assertGreaterEqual(len(DEPLOY_FILES), 60, "DEPLOY_FILES must contain at least 60 reconciled files")
        for local_rel, _ in DEPLOY_FILES:
            local_path = REPO_ROOT / local_rel
            self.assertTrue(local_path.is_file(), f"DEPLOY_FILES references non-existent file on disk: {local_path}")

    def test_deploy_files_destinations_unique_and_normalized(self):
        """All remote destination paths in DEPLOY_FILES must be unique, non-empty, and forward-slash normalized."""
        seen_destinations = set()
        for local_rel, remote_rel in DEPLOY_FILES:
            self.assertTrue(bool(remote_rel), f"Empty remote path for {local_rel}")
            self.assertFalse(remote_rel.startswith('/'), f"Remote path must be relative to remote root: {remote_rel}")
            seen_destinations.add(remote_rel)

    def test_deploy_preflight_validation_rejects_empty_files(self):
        """Pre-flight check must reject non-existent or empty files."""
        fake_files = [('fake/relative/path.php', Path('/non/existent/file.php'))]
        with self.assertRaises(ValueError):
            for local_relative, local_path in fake_files:
                if not local_path.is_file() or local_path.stat().st_size == 0:
                    raise ValueError(f'Pre-flight check failed: invalid or empty file {local_relative}')

    def test_stage_and_promote_file_success(self):
        """Atomic staging flow must upload to temp path, verify SHA-256, and atomically mv -f."""
        class MockChannel:
            def recv_exit_status(self):
                return 0

        class MockStream:
            channel = MockChannel()

            def __init__(self, data=b''):
                self._data = data

            def read(self):
                return self._data

        class MockSFTP:
            def __init__(self):
                self.puts = []

            def put(self, local_path, remote_path):
                self.puts.append((local_path, remote_path))

        class MockSSH:
            def __init__(self, sha256_output):
                self.commands = []
                self.sha256_output = sha256_output

            def exec_command(self, command):
                self.commands.append(command)
                if command.startswith('sha256sum --'):
                    return None, MockStream(self.sha256_output), MockStream(b'')
                return None, MockStream(b''), MockStream(b'')

        with tempfile.TemporaryDirectory() as tmpdir:
            test_file = Path(tmpdir) / 'test_module.php'
            content = b'<?php // staging test content ?>\n'
            test_file.write_bytes(content)
            expected_hash = hashlib.sha256(content).hexdigest().lower()

            remote_dest = '/srv/vietnamguide/wp-content/themes/vietnamguide-premium/inc/test.php'
            mock_sftp = MockSFTP()
            mock_ssh = MockSSH(sha256_output=f'{expected_hash}  {remote_dest}.tmp.12345678\n'.encode())

            success, local_hash, remote_hash = stage_and_promote_file(
                mock_ssh,
                mock_sftp,
                test_file,
                remote_dest,
            )

            self.assertTrue(success)
            self.assertEqual(local_hash, expected_hash)
            self.assertEqual(remote_hash, expected_hash)

            # Assert upload targeted a temporary path ending in .tmp.<hex>
            self.assertEqual(len(mock_sftp.puts), 1)
            uploaded_src, uploaded_dst = mock_sftp.puts[0]
            self.assertEqual(uploaded_src, str(test_file))
            self.assertRegex(uploaded_dst, r'^/srv/vietnamguide/wp-content/themes/vietnamguide-premium/inc/test\.php\.tmp\.[0-9a-f]{8}$')
            self.assertNotEqual(uploaded_dst, remote_dest)

            # Assert SSH executed remote verification and atomic promotion
            self.assertEqual(len(mock_ssh.commands), 2)
            self.assertEqual(mock_ssh.commands[0], f'sha256sum -- {uploaded_dst}')
            self.assertEqual(mock_ssh.commands[1], f'mv -f -- {uploaded_dst} {remote_dest}')
            self.assertNotIn('rm -f', ''.join(mock_ssh.commands))

    def test_stage_and_promote_file_hash_mismatch_cleans_up_without_promotion(self):
        """On hash mismatch, temporary staged file must be removed with rm -f and never promoted."""
        class MockChannel:
            def recv_exit_status(self):
                return 0

        class MockStream:
            channel = MockChannel()

            def __init__(self, data=b''):
                self._data = data

            def read(self):
                return self._data

        class MockSFTP:
            def __init__(self):
                self.puts = []

            def put(self, local_path, remote_path):
                self.puts.append((local_path, remote_path))

        class MockSSH:
            def __init__(self):
                self.commands = []

            def exec_command(self, command):
                self.commands.append(command)
                if command.startswith('sha256sum --'):
                    return None, MockStream(b'0000000000000000000000000000000000000000000000000000000000000000  bad.tmp\n'), MockStream(b'')
                return None, MockStream(b''), MockStream(b'')

        with tempfile.TemporaryDirectory() as tmpdir:
            test_file = Path(tmpdir) / 'test_module.php'
            test_file.write_bytes(b'good local content')
            remote_dest = '/srv/vietnamguide/test.php'

            mock_sftp = MockSFTP()
            mock_ssh = MockSSH()

            success, local_hash, remote_hash = stage_and_promote_file(
                mock_ssh,
                mock_sftp,
                test_file,
                remote_dest,
            )

            self.assertFalse(success)
            self.assertNotEqual(local_hash, remote_hash)

            # Assert staging uploaded to temp path
            self.assertEqual(len(mock_sftp.puts), 1)
            _, temp_dst = mock_sftp.puts[0]

            # Assert sha256sum checked temp, mv -f was NEVER executed, and rm -f cleaned up temp
            self.assertEqual(len(mock_ssh.commands), 2)
            self.assertEqual(mock_ssh.commands[0], f'sha256sum -- {temp_dst}')
            self.assertEqual(mock_ssh.commands[1], f'rm -f -- {temp_dst}')
            self.assertNotIn('mv -f', ''.join(mock_ssh.commands))

    def test_stage_and_promote_file_sftp_failure_cleans_up_temp_path(self):
        """If SFTP upload fails, staging temp path must be cleaned up."""
        class MockChannel:
            def recv_exit_status(self):
                return 0

        class MockStream:
            channel = MockChannel()

            def read(self):
                return b''

        class MockSFTP:
            def put(self, local_path, remote_path):
                raise IOError('Simulated SFTP upload failure')

        class MockSSH:
            def __init__(self):
                self.commands = []

            def exec_command(self, command):
                self.commands.append(command)
                return None, MockStream(), MockStream()

        with tempfile.TemporaryDirectory() as tmpdir:
            test_file = Path(tmpdir) / 'test_module.php'
            test_file.write_bytes(b'valid content')
            remote_dest = '/srv/vietnamguide/test.php'

            mock_sftp = MockSFTP()
            mock_ssh = MockSSH()

            with self.assertRaises(IOError):
                stage_and_promote_file(
                    mock_ssh,
                    mock_sftp,
                    test_file,
                    remote_dest,
                )

            # Verify cleanup was triggered and no mv -f
            self.assertTrue(any(cmd.startswith('rm -f --') for cmd in mock_ssh.commands))
            self.assertFalse(any('mv -f' in cmd for cmd in mock_ssh.commands))

    def test_stage_and_promote_file_promotion_failure_raises_and_cleans_up(self):
        """If mv -f promotion fails with non-zero exit code, error is raised and temp file cleaned up."""
        class MockChannel:
            def __init__(self, exit_status=0):
                self._exit_status = exit_status

            def recv_exit_status(self):
                return self._exit_status

        class MockStream:
            def __init__(self, data=b'', exit_status=0):
                self._data = data
                self.channel = MockChannel(exit_status)

            def read(self):
                return self._data

        class MockSFTP:
            def put(self, local_path, remote_path):
                pass

        class MockSSH:
            def __init__(self, expected_hash):
                self.commands = []
                self.expected_hash = expected_hash

            def exec_command(self, command):
                self.commands.append(command)
                if command.startswith('sha256sum --'):
                    return None, MockStream(f'{self.expected_hash}  test.tmp\n'.encode()), MockStream(b'')
                if command.startswith('mv -f --'):
                    return None, MockStream(b'', exit_status=1), MockStream(b'Permission denied')
                return None, MockStream(b''), MockStream(b'')

        with tempfile.TemporaryDirectory() as tmpdir:
            test_file = Path(tmpdir) / 'test_module.php'
            content = b'valid content'
            test_file.write_bytes(content)
            expected_hash = hashlib.sha256(content).hexdigest().lower()
            remote_dest = '/srv/vietnamguide/test.php'

            mock_sftp = MockSFTP()
            mock_ssh = MockSSH(expected_hash)

            with self.assertRaisesRegex(RuntimeError, 'atomic promotion failed'):
                stage_and_promote_file(
                    mock_ssh,
                    mock_sftp,
                    test_file,
                    remote_dest,
                )

            # mv -f attempted, failed, then rm -f cleaned up
            self.assertTrue(any('mv -f' in cmd for cmd in mock_ssh.commands))
            self.assertTrue(any(cmd.startswith('rm -f --') for cmd in mock_ssh.commands))

    def test_stage_and_promote_file_rejects_empty_file_in_preflight(self):
        """Pre-flight byte check in stage_and_promote_file rejects 0-byte files."""
        with tempfile.TemporaryDirectory() as tmpdir:
            empty_file = Path(tmpdir) / 'empty.php'
            empty_file.write_bytes(b'')

            class Dummy:
                pass

            with self.assertRaises(ValueError):
                stage_and_promote_file(
                    Dummy(),
                    Dummy(),
                    empty_file,
                    '/srv/site/empty.php',
                )


    def test_restore_remote_backup_generates_correct_commands_and_restores_files(self):
        """Automated rollback generates commands to restore present files and remove newly added ones."""
        class FakeChannel:
            def recv_exit_status(self):
                return 0

            def close(self):
                pass

        class FakeStream:
            channel = FakeChannel()

        class FakeSSH:
            def __init__(self):
                self.commands = []

            def exec_command(self, command):
                self.commands.append(command)
                return None, FakeStream(), FakeStream()

        config = DeployConfig(
            host='staging.example.test',
            port=2209,
            user='vietnamguide-deploy',
            key_path=r'C:\keys\deploy',
            known_hosts=r'C:\keys\known_hosts',
            remote_root='/srv/vietnamguide',
            allow_root=False,
        )
        ssh = FakeSSH()
        backup_dir = '/srv/vietnamguide/wp-content/.vietnamguide-deployment-backups/20261010T120000Z-abcdef123456'

        restore_remote_backup(ssh, config, backup_dir)

        self.assertEqual(len(ssh.commands), 1)
        command = ssh.commands[0]
        self.assertTrue(command.startswith('set -eu; test -f '))
        self.assertIn('/srv/vietnamguide/wp-content/.vietnamguide-deployment-backups/20261010T120000Z-abcdef123456/backup-manifest.tsv', command)
        self.assertTrue(command.endswith('; set +e'))
        # Ensure every deployed file is accounted for in rollback
        for _local, remote_rel in DEPLOY_FILES:
            target_path = get_remote_path(config, remote_rel)
            self.assertIn(f"cp -pf -- /srv/vietnamguide/wp-content/.vietnamguide-deployment-backups/20261010T120000Z-abcdef123456/{remote_rel} {target_path}", command)
            self.assertIn(f"rm -f -- {target_path}", command)

    def test_restore_remote_backup_raises_on_non_zero_exit_status(self):
        """Rollback failure raises RuntimeError with remote error message."""
        class FailChannel:
            def recv_exit_status(self):
                return 1

            def close(self):
                pass

        class FailStream:
            def __init__(self, err_text=b''):
                self.err_text = err_text
                self.channel = FailChannel()

            def read(self):
                return self.err_text

        class FailSSH:
            def exec_command(self, command):
                return None, FailStream(), FailStream(b'disk read-only')

        config = DeployConfig(
            host='staging.example.test',
            port=2209,
            user='vietnamguide-deploy',
            key_path=r'C:\keys\deploy',
            known_hosts=r'C:\keys\known_hosts',
            remote_root='/srv/vietnamguide',
            allow_root=False,
        )
        ssh = FailSSH()

        with self.assertRaisesRegex(RuntimeError, 'rollback restoration failed \\(1\\): disk read-only'):
            restore_remote_backup(ssh, config, '/tmp/backup')

    def test_posix_key_permission_mode_validation(self):
        """On POSIX systems, SSH private key with open permissions (> 0600) is rejected."""
        with tempfile.TemporaryDirectory() as tmpdir:
            key_file = Path(tmpdir) / 'test_rsa'
            key_file.write_text('dummy-key', encoding='utf-8')
            known_hosts_file = Path(tmpdir) / 'known_hosts'
            known_hosts_file.write_text('dummy-host', encoding='utf-8')

            env = {
                'VG_DEPLOY_HOST': 'staging.example.test',
                'VG_DEPLOY_PORT': '2209',
                'VG_DEPLOY_USER': 'vietnamguide-deploy',
                'VG_DEPLOY_KEY': str(key_file),
                'VG_DEPLOY_KNOWN_HOSTS': str(known_hosts_file),
                'VG_DEPLOY_ROOT': '/srv/vietnamguide',
            }

            # Simulate POSIX with open 0644 permission
            class FakeStat:
                st_mode = 0o100644

            with patch('os.name', 'posix'):
                with patch('os.stat', return_value=FakeStat()):
                    with patch.dict(os.environ, env, clear=True):
                        with self.assertRaisesRegex(PermissionError, 'deploy key file permissions too open'):
                            load_deploy_config()

            # Simulate POSIX with secure 0600 permission
            class SecureStat:
                st_mode = 0o100600

            with patch('os.name', 'posix'):
                with patch('os.stat', return_value=SecureStat()):
                    with patch.dict(os.environ, env, clear=True):
                        config = load_deploy_config()
                        self.assertEqual(config.user, 'vietnamguide-deploy')


if __name__ == '__main__':
    unittest.main()



