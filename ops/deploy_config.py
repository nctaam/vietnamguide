"""Validated configuration for the controlled VietnamGuide deploy path."""

from __future__ import annotations

from dataclasses import dataclass
import os
from collections.abc import Mapping


TRUE_VALUES = {"1", "true", "yes", "on"}


@dataclass(frozen=True)
class DeployConfig:
    host: str
    port: int
    user: str
    key_path: str
    known_hosts: str
    remote_root: str
    allow_root: bool


def _required(environment: Mapping[str, str], name: str) -> str:
    value = environment.get(name, "").strip()
    if not value:
        raise ValueError(f"{name} must be set explicitly")
    if any(character in value for character in "\r\n\x00"):
        raise ValueError(f"{name} contains a forbidden control character")
    return value


def _parse_port(environment: Mapping[str, str]) -> int:
    raw_port = _required(environment, "VG_DEPLOY_PORT")
    try:
        port = int(raw_port, 10)
    except ValueError as exc:
        raise ValueError("VG_DEPLOY_PORT must be an integer") from exc
    if not 1 <= port <= 65535:
        raise ValueError("VG_DEPLOY_PORT must be between 1 and 65535")
    return port


def _parse_bool(environment: Mapping[str, str], name: str) -> bool:
    raw_value = environment.get(name, "").strip().lower()
    return raw_value in TRUE_VALUES


def load_deploy_config(environment: Mapping[str, str] | None = None) -> DeployConfig:
    """Load deploy configuration without production defaults or implicit trust."""

    values = environment if environment is not None else os.environ
    host = _required(values, "VG_DEPLOY_HOST")
    user = _required(values, "VG_DEPLOY_USER")
    key_path = os.path.expanduser(_required(values, "VG_DEPLOY_KEY"))
    known_hosts = os.path.expanduser(_required(values, "VG_DEPLOY_KNOWN_HOSTS"))
    remote_root = _required(values, "VG_DEPLOY_ROOT")
    if not remote_root.startswith("/") or remote_root.endswith("/"):
        raise ValueError("VG_DEPLOY_ROOT must be an absolute path without a trailing slash")
    if ".." in remote_root.split("/"):
        raise ValueError("VG_DEPLOY_ROOT must not contain parent path segments")

    allow_root = _parse_bool(values, "VG_DEPLOY_ALLOW_ROOT")
    if user == "root" and not allow_root:
        raise ValueError("VG_DEPLOY_ALLOW_ROOT=1 is required for root deployment")

    return DeployConfig(
        host=host,
        port=_parse_port(values),
        user=user,
        key_path=key_path,
        known_hosts=known_hosts,
        remote_root=remote_root,
        allow_root=allow_root,
    )
