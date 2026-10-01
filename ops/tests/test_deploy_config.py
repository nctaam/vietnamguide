import os
import unittest
from pathlib import Path
from unittest.mock import patch

from ops.deploy_config import DeployConfig, load_deploy_config
from ops.deploy_theme_updates import (
    DEPLOY_FILES,
    backup_remote_files,
    get_remote_path,
    purge_cache,
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


if __name__ == '__main__':
    unittest.main()
