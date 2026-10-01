import json
import tempfile
import unittest
from pathlib import Path

from ops.deploy_security_audit import audit_deploy_surface, audit_source, build_report


class DeploySecurityAuditTests(unittest.TestCase):
    def test_safe_configured_script_has_no_release_blockers(self):
        source = '''
from ops.deploy_config import load_deploy_config
import paramiko

def deploy():
    config = load_deploy_config()
    client = paramiko.SSHClient()
    client.load_host_keys(config.known_hosts)
    client.set_missing_host_key_policy(paramiko.RejectPolicy())
'''

        findings = audit_source('ops/deploy_safe.py', source)

        self.assertEqual(findings, [])

    def test_comments_and_pattern_documentation_do_not_create_findings(self):
        source = '''
# Example documentation may mention AutoAddPolicy() and --allow-root.
PATTERN_DOC = "AutoAddPolicy() --allow-root"
'''

        self.assertEqual(audit_source('ops/documentation.py', source), [])

    def test_paramiko_alias_is_detected_without_textual_false_positive(self):
        source = '''
from paramiko import SSHClient
client = SSHClient()
'''

        findings = audit_source('ops/legacy_alias.py', source)

        self.assertIn('missing_validated_config', findings)
        self.assertIn('missing_host_key_verification', findings)
        self.assertIn('missing_known_hosts', findings)

    def test_direct_ssh_literals_are_audited(self):
        source = '''
import paramiko
ssh = paramiko.SSHClient()
ssh.connect('198.51.100.10', username='root', key_filename=r'C:\\Users\\me\\.ssh\\key')
'''

        findings = audit_source('ops/legacy_direct.py', source)

        self.assertIn('hardcoded_host', findings)
        self.assertIn('root_user', findings)
        self.assertIn('hardcoded_key_path', findings)
        self.assertIn('missing_validated_config', findings)

    def test_paramiko_policy_aliases_are_audited(self):
        source = '''
from paramiko import AutoAddPolicy as AAP, SSHClient
client = SSHClient()
client.set_missing_host_key_policy(AAP())
'''

        self.assertIn('auto_add_host_key_policy', audit_source('ops/legacy_alias.py', source))

    def test_legacy_script_reports_each_unsafe_connection_pattern(self):
        source = '''
import paramiko
SSH_HOST = '198.51.100.10'
SSH_USER = 'root'
SSH_KEY = r'C:\\Users\\me\\.ssh\\deploy_key'
REMOTE_ROOT = '/srv/site'
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
command = 'wp eval-file fix.php --allow-root'
'''

        findings = audit_source('ops/deploy_legacy.py', source)

        self.assertIn('hardcoded_host', findings)
        self.assertIn('root_user', findings)
        self.assertIn('hardcoded_key_path', findings)
        self.assertIn('hardcoded_remote_root', findings)
        self.assertIn('auto_add_host_key_policy', findings)
        self.assertIn('allow_root_command', findings)
        self.assertIn('missing_validated_config', findings)

    def test_surface_excludes_archive_and_tests(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            (root / 'deploy_ok.py').write_text(
                'from ops.deploy_config import load_deploy_config\n', encoding='utf-8'
            )
            (root / 'archive-apply-scripts').mkdir()
            (root / 'archive-apply-scripts' / 'deploy_old.py').write_text(
                "import paramiko\nSSH_USER='root'\n", encoding='utf-8'
            )
            (root / 'tests').mkdir()
            (root / 'tests' / 'test_deploy.py').write_text(
                "SSH_USER='root'\n", encoding='utf-8'
            )

            records = audit_deploy_surface(root)

        self.assertEqual([record['path'] for record in records], ['deploy_ok.py'])

    def test_report_marks_only_configured_release_path_eligible(self):
        records = [
            {'path': 'deploy_theme_updates.py', 'findings': [], 'release_candidate': True},
            {'path': 'deploy_legacy.py', 'findings': ['root_user'], 'release_candidate': True},
        ]

        report = build_report(records, release_paths=['deploy_theme_updates.py'])

        self.assertEqual(report['summary']['release_ready_count'], 1)
        self.assertEqual(report['summary']['blocked_release_count'], 0)
        self.assertEqual(report['summary']['legacy_blocked_count'], 1)

    def test_report_marks_missing_release_path_as_not_ready(self):
        report = build_report([], release_paths=['deploy_missing.py'])

        self.assertEqual(report['summary']['missing_release_count'], 1)
        self.assertEqual(report['missing_release_paths'], ['deploy_missing.py'])


if __name__ == '__main__':
    unittest.main()
