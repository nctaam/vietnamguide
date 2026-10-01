"""Static audit for the scripts that can change a remote VietnamGuide site.

This module never opens a network connection and never reads a private key. It
only inspects source text so the release path can be kept safe while historical
one-off scripts are migrated or archived separately.
"""

from __future__ import annotations

import argparse
import ast
from collections import Counter
import json
from pathlib import Path
import re
from typing import Iterable
import warnings


EXCLUDED_DIRS = {"archive-apply-scripts", "backups", "__pycache__", "tests"}
EXCLUDED_FILES = {"deploy_security_audit.py"}
DEPLOY_NAME_PREFIXES = ("deploy", "run_remote", "sync_and_deploy")

PATTERNS: tuple[tuple[str, re.Pattern[str]], ...] = (
    (
        "hardcoded_host",
        re.compile(
            r"(?im)\b(?:SSH|VPS|REMOTE)?_?HOST\s*=\s*['\"](?:\d{1,3}\.){3}\d{1,3}['\"]"
        ),
    ),
    (
        "root_user",
        re.compile(r"(?im)\b(?:SSH|VPS)?_?USER\s*=\s*['\"]root['\"]|\busername\s*=\s*['\"]root['\"]"),
    ),
    (
        "hardcoded_key_path",
        re.compile(r"(?im)\b(?:SSH_)?KEY(?:_PATH)?\s*=\s*r?['\"][^'\"]*(?:\\|/)\.ssh(?:\\|/)[^'\"]+['\"]"),
    ),
    (
        "hardcoded_remote_root",
        re.compile(r"(?im)\b(?:REMOTE_ROOT|WP_PATH)\s*=\s*['\"]/(?:[^'\"]+)['\"]"),
    ),
    (
        "auto_add_host_key_policy",
        re.compile(r"\bAutoAddPolicy\s*\("),
    ),
    (
        "allow_root_command",
        re.compile(r"--allow-root\b"),
    ),
)


def _is_candidate(path: Path, source: str) -> bool:
    return path.name.startswith(DEPLOY_NAME_PREFIXES) or _uses_paramiko(source)


def _dotted_name(node: ast.AST) -> str:
    if isinstance(node, ast.Name):
        return node.id
    if isinstance(node, ast.Attribute):
        parent = _dotted_name(node.value)
        return f"{parent}.{node.attr}" if parent else node.attr
    return ""


def _parse_source(source: str) -> ast.AST | None:
    with warnings.catch_warnings():
        warnings.simplefilter("ignore", SyntaxWarning)
        try:
            return ast.parse(source)
        except SyntaxError:
            return None


def _paramiko_aliases(tree: ast.AST) -> set[str]:
    aliases: set[str] = set()
    for node in ast.walk(tree):
        if isinstance(node, ast.Import):
            for alias in node.names:
                if alias.name == "paramiko":
                    aliases.add(alias.asname or "paramiko")
        elif isinstance(node, ast.ImportFrom) and node.module and node.module.startswith("paramiko"):
            for alias in node.names:
                aliases.add(alias.asname or alias.name)
    return aliases


def _uses_paramiko(source: str) -> bool:
    tree = _parse_source(source)
    if tree is None:
        return bool(re.search(r"(?:import\s+paramiko|from\s+paramiko\s+import)", source))
    aliases = _paramiko_aliases(tree)
    if not aliases:
        return False
    api_names = {"SSHClient", "SFTPClient", "Transport", "connect", "open_sftp"}
    for node in ast.walk(tree):
        if not isinstance(node, ast.Call):
            continue
        dotted = _dotted_name(node.func)
        if dotted and dotted.split(".", 1)[0] in aliases:
            return True
        if dotted.rsplit(".", 1)[-1] in api_names:
            return True
    return False


def _assigned_string_values(tree: ast.AST) -> list[tuple[str, str]]:
    assignments: list[tuple[str, str]] = []
    for node in ast.walk(tree):
        if isinstance(node, ast.Assign):
            targets = node.targets
        elif isinstance(node, ast.AnnAssign):
            targets = [node.target]
        else:
            continue
        if not isinstance(getattr(node, "value", None), ast.Constant):
            continue
        value = node.value.value
        if not isinstance(value, str):
            continue
        for target in targets:
            if isinstance(target, ast.Name):
                assignments.append((target.id, value))
    return assignments


def _contains_allow_root_command(tree: ast.AST) -> bool:
    command_names = {"command", "cmd", "remote_command", "ssh_command", "shell_command", "wp_command"}
    for node in ast.walk(tree):
        if isinstance(node, ast.Call):
            values = [argument for argument in (*node.args, *(keyword.value for keyword in node.keywords))]
            if any(isinstance(value, ast.Constant) and isinstance(value.value, str) and "--allow-root" in value.value for value in values):
                return True
        if isinstance(node, ast.Assign) and isinstance(node.value, ast.Constant) and isinstance(node.value.value, str):
            if any(
                isinstance(target, ast.Name)
                and (target.id.lower() in command_names or "command" in target.id.lower())
                and "--allow-root" in node.value.value
                for target in node.targets
            ):
                return True
        if isinstance(node, ast.JoinedStr) and any(
            isinstance(value, ast.Constant) and isinstance(value.value, str) and "--allow-root" in value.value
            for value in node.values
        ):
            return True
    return False


def _call_has_name(tree: ast.AST, name: str) -> bool:
    aliases = {name}
    for node in ast.walk(tree):
        if isinstance(node, ast.ImportFrom) and node.module and node.module.startswith("paramiko"):
            for alias in node.names:
                if alias.name == name:
                    aliases.add(alias.asname or alias.name)
    return any(
        isinstance(node, ast.Call) and _dotted_name(node.func).rsplit(".", 1)[-1] in aliases
        for node in ast.walk(tree)
    )


def _uses_validated_config(tree: ast.AST) -> bool:
    names = {"load_deploy_config", "DeployConfig"}
    for node in ast.walk(tree):
        if isinstance(node, ast.ImportFrom) and node.module and node.module.endswith("deploy_config"):
            if any(alias.name in names for alias in node.names):
                return True
            names.update(alias.asname for alias in node.names if alias.name in names and alias.asname)
        if isinstance(node, ast.Name) and node.id in names:
            return True
        if isinstance(node, ast.Attribute) and node.attr in names:
            return True
    return False


def _connection_literal_findings(tree: ast.AST) -> set[str]:
    """Find endpoint/user/key literals passed directly to SSH APIs."""

    findings: set[str] = set()
    for node in ast.walk(tree):
        if not isinstance(node, ast.Call):
            continue
        call_name = _dotted_name(node.func).rsplit(".", 1)[-1]
        keyword_values = {keyword.arg: keyword.value for keyword in node.keywords if keyword.arg}
        if call_name == "connect":
            host_value = node.args[0] if node.args else keyword_values.get("hostname") or keyword_values.get("host")
            if isinstance(host_value, ast.Constant) and isinstance(host_value.value, str):
                if re.fullmatch(r"(?:\d{1,3}\.){3}\d{1,3}", host_value.value):
                    findings.add("hardcoded_host")
            user_value = keyword_values.get("username") or keyword_values.get("user")
            if isinstance(user_value, ast.Constant) and user_value.value == "root":
                findings.add("root_user")
            key_value = keyword_values.get("key_filename") or keyword_values.get("key_path")
            if isinstance(key_value, ast.Constant) and isinstance(key_value.value, str) and ".ssh" in key_value.value.lower():
                findings.add("hardcoded_key_path")
        if call_name == "from_private_key_file":
            key_value = node.args[0] if node.args else None
            if isinstance(key_value, ast.Constant) and isinstance(key_value.value, str) and ".ssh" in key_value.value.lower():
                findings.add("hardcoded_key_path")
    return findings


def audit_source(path: str | Path, source: str) -> list[str]:
    """Return stable safety findings for one deploy-capable Python source."""

    tree = _parse_source(source)
    if tree is None:
        findings = [name for name, pattern in PATTERNS if pattern.search(source)]
        uses_paramiko = bool(re.search(r"(?:import\s+paramiko|from\s+paramiko\s+import)", source))
        has_validated_config = bool(re.search(r"\b(?:load_deploy_config|DeployConfig)\b", source))
    else:
        assignments = _assigned_string_values(tree)
        findings: list[str] = []
        if any(name.upper().endswith("HOST") and re.fullmatch(r"(?:\d{1,3}\.){3}\d{1,3}", value) for name, value in assignments):
            findings.append("hardcoded_host")
        if any(
            (name.upper().endswith("USER") or name.lower() == "username") and value == "root"
            for name, value in assignments
        ):
            findings.append("root_user")
        if any(
            (
                name.upper() in {"KEY", "SSH_KEY", "KEY_PATH", "SSH_KEY_PATH"}
                or name.upper().endswith("_KEY")
                or name.upper().endswith("_KEY_PATH")
            )
            and ".ssh" in value.lower()
            for name, value in assignments
        ):
            findings.append("hardcoded_key_path")
        if any(name.upper() in {"REMOTE_ROOT", "WP_PATH"} and value.startswith("/") for name, value in assignments):
            findings.append("hardcoded_remote_root")
        if _call_has_name(tree, "AutoAddPolicy"):
            findings.append("auto_add_host_key_policy")
        if _contains_allow_root_command(tree):
            findings.append("allow_root_command")
        findings.extend(_connection_literal_findings(tree))
        uses_paramiko = _uses_paramiko(source)
        has_validated_config = _uses_validated_config(tree)
    if uses_paramiko and not has_validated_config:
        findings.append("missing_validated_config")
    if uses_paramiko:
        has_reject_policy = _call_has_name(tree, "RejectPolicy") if tree is not None else bool(re.search(r"\bRejectPolicy\s*\(", source))
        has_known_hosts = _call_has_name(tree, "load_host_keys") if tree is not None else bool(re.search(r"\bload_host_keys\s*\(", source))
        if not has_reject_policy:
            findings.append("missing_host_key_verification")
        if not has_known_hosts:
            findings.append("missing_known_hosts")
    return sorted(set(findings))


def audit_deploy_surface(root: str | Path) -> list[dict[str, object]]:
    """Audit Python deploy candidates below root, excluding archived/test code."""

    root_path = Path(root)
    records: list[dict[str, object]] = []
    for path in sorted(root_path.rglob("*.py")):
        relative = path.relative_to(root_path)
        if any(part in EXCLUDED_DIRS for part in relative.parts) or path.name in EXCLUDED_FILES:
            continue
        source = path.read_text(encoding="utf-8", errors="replace")
        if not _is_candidate(path, source):
            continue
        records.append(
            {
                "path": relative.as_posix(),
                "findings": audit_source(relative, source),
            }
        )
    return records


def build_report(
    records: Iterable[dict[str, object]],
    *,
    release_paths: Iterable[str] = (),
    root: str = "ops",
) -> dict[str, object]:
    """Build a JSON-serializable audit report and release-path summary."""

    release_set = {Path(path).as_posix().lstrip("./") for path in release_paths}
    normalized_records: list[dict[str, object]] = []
    finding_counts: Counter[str] = Counter()
    release_ready_count = 0
    blocked_release_count = 0
    legacy_blocked_count = 0
    seen_paths: set[str] = set()

    for record in sorted(records, key=lambda item: str(item.get("path", ""))):
        path = Path(str(record.get("path", ""))).as_posix().lstrip("./")
        seen_paths.add(path)
        findings = sorted({str(value) for value in record.get("findings", [])})
        is_release_path = path in release_set
        if is_release_path and findings:
            blocked_release_count += 1
        elif is_release_path:
            release_ready_count += 1
        elif findings:
            legacy_blocked_count += 1
        finding_counts.update(findings)
        normalized_records.append(
            {
                "path": path,
                "findings": findings,
                "release_path": is_release_path,
                "release_ready": is_release_path and not findings,
            }
        )

    return {
        "schema_version": 1,
        "scope": {
            "root": root,
            "excluded_directories": sorted(EXCLUDED_DIRS),
            "excluded_files": sorted(EXCLUDED_FILES),
        },
        "summary": {
            "candidate_count": len(normalized_records),
            "release_path_count": len(release_set),
            "release_ready_count": release_ready_count,
            "blocked_release_count": blocked_release_count,
            "missing_release_count": len(release_set - seen_paths),
            "legacy_blocked_count": legacy_blocked_count,
            "finding_counts": dict(sorted(finding_counts.items())),
        },
        "release_paths": sorted(release_set),
        "missing_release_paths": sorted(release_set - seen_paths),
        "records": normalized_records,
    }


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--root", default="ops", help="directory to inspect")
    parser.add_argument("--release-path", action="append", default=[], help="relative release candidate path")
    parser.add_argument("--output", help="write JSON report to this path")
    parser.add_argument("--strict-release", action="store_true", help="fail if a release path has findings")
    args = parser.parse_args(argv)

    records = audit_deploy_surface(args.root)
    report = build_report(records, release_paths=args.release_path, root=args.root)
    serialized = json.dumps(report, ensure_ascii=False, indent=2) + "\n"
    if args.output:
        output = Path(args.output)
        output.parent.mkdir(parents=True, exist_ok=True)
        output.write_text(serialized, encoding="utf-8")
    else:
        print(serialized, end="")

    if args.strict_release and (
        report["summary"]["blocked_release_count"] or report["summary"]["missing_release_count"]
    ):
        return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
