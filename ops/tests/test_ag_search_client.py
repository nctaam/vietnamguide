# -*- coding: utf-8 -*-
"""Unit tests for Antigravity Local Search Client (ops/ag_search_client.py)."""

from __future__ import annotations

import json
import unittest
from unittest.mock import MagicMock, patch

from ops.ag_search_client import (
    DEFAULT_API_KEY,
    DEFAULT_MODEL,
    DEFAULT_SEARCH_URL,
    search_ag,
    verify_wikimedia_thumb_url,
)


class TestAgSearchClient(unittest.TestCase):
    def test_search_ag_builds_correct_post_request(self):
        fake_response_body = json.dumps(
            {
                "query": "Vietnam e-visa Resolution 127/NQ-CP",
                "provider": "ag",
                "results": [
                    {
                        "title": "Official Vietnam E-Visa Portal",
                        "url": "https://evisa.gov.vn/",
                        "snippet": "90-day e-visa valid across 42 international checkpoints.",
                    }
                ],
            }
        ).encode("utf-8")

        mock_resp = MagicMock()
        mock_resp.read.return_value = fake_response_body
        mock_resp.__enter__.return_value = mock_resp
        mock_resp.__exit__.return_value = False

        with patch("urllib.request.urlopen", return_value=mock_resp) as mock_urlopen:
            res = search_ag(
                query="Vietnam e-visa Resolution 127/NQ-CP",
                search_type="web",
                max_results=0,
            )

            self.assertEqual(res["provider"], "ag")
            self.assertEqual(len(res["results"]), 1)
            req = mock_urlopen.call_args[0][0]
            self.assertEqual(req.full_url, DEFAULT_SEARCH_URL)
            self.assertEqual(req.get_method(), "POST")
            self.assertEqual(req.get_header("Content-type"), "application/json")
            self.assertEqual(req.get_header("Authorization"), f"Bearer {DEFAULT_API_KEY}")
            sent_payload = json.loads(req.data.decode("utf-8"))
            self.assertEqual(
                sent_payload,
                {
                    "model": DEFAULT_MODEL,
                    "query": "Vietnam e-visa Resolution 127/NQ-CP",
                    "search_type": "web",
                    "max_results": 0,
                },
            )

    def test_verify_wikimedia_thumb_url_detects_broken_and_valid_md5_paths(self):
        valid_sapa = (
            "https://upload.wikimedia.org/wikipedia/commons/thumb/f/ff/"
            "Rice_terraces_in_Sapa%2C_Vietnam.jpg/1920px-Rice_terraces_in_Sapa%2C_Vietnam.jpg"
        )
        broken_sapa = (
            "https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/"
            "Rice_terraces_in_Sapa%2C_Vietnam.jpg/1920px-Rice_terraces_in_Sapa%2C_Vietnam.jpg"
        )
        valid_dalat = (
            "https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/"
            "Da_Lat_-_Xuan_Huong_Lake.jpg/1920px-Da_Lat_-_Xuan_Huong_Lake.jpg"
        )

        ok1, msg1 = verify_wikimedia_thumb_url(valid_sapa)
        self.assertTrue(ok1, msg1)

        ok2, msg2 = verify_wikimedia_thumb_url(broken_sapa)
        self.assertFalse(ok2)
        self.assertIn("expected /f/ff/", msg2)

        ok3, msg3 = verify_wikimedia_thumb_url(valid_dalat)
        self.assertTrue(ok3, msg3)

    def test_all_theme_wikimedia_urls_have_valid_md5_hash_paths(self):
        """Every Wikimedia Commons thumbnail URL in theme inc/ and patterns/ must have a valid MD5 path."""
        import os
        from pathlib import Path
        import re

        repo_root = Path(os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..")))
        theme_dir = repo_root / "wordpress" / "wp-content" / "themes" / "vietnamguide-premium"
        url_re = re.compile(r"https://upload\.wikimedia\.org/wikipedia/commons/thumb/(?:\\'|[^\s'\"<>])+")

        checked_count = 0
        broken_urls = []
        for subdir in ("inc", "patterns"):
            for php_file in (theme_dir / subdir).glob("*.php"):
                text = php_file.read_text(encoding="utf-8", errors="ignore")
                for raw_url in url_re.findall(text):
                    url = raw_url.replace("\\'", "'")
                    checked_count += 1
                    ok, msg = verify_wikimedia_thumb_url(url)
                    if not ok:
                        broken_urls.append(f"{php_file.name}: {url} ({msg})")

        self.assertGreater(checked_count, 100)
        self.assertEqual(broken_urls, [], f"Found broken Wikimedia MD5 URLs: {broken_urls}")

    def test_cost_calculator_official_tariffs_ledger(self):
        """Cost calculator must include the 2025-2026 Official Monument & Transport Tariff Ledger with zero <h2> tags."""
        import os
        from pathlib import Path
        import re

        repo_root = Path(os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..")))
        calc_path = (
            repo_root
            / "wordpress"
            / "wp-content"
            / "themes"
            / "vietnamguide-premium"
            / "inc"
            / "guide-cost-calculator.php"
        )
        content = calc_path.read_text(encoding="utf-8")
        self.assertIn('class="vg-calc-official-tariffs"', content)
        for expected_tariff in (
            "200,000 / 150,000 VND",
            "120,000 / 150,000 VND",
            "250,000 / 250,000 / 100,000 VND",
            "310,000 / 550,000",
            "150,000 / 250,000 / 450,000 VND",
            "70,000 / 100,000 / 50,000 VND",
            "850,000 / 950,000 / 650,000 VND",
            "340,000 / 390,000 VND",
            "45,000 / 120,000 VND",
        ):
            self.assertIn(expected_tariff, content)
        self.assertEqual(len(re.findall(r"<h2\b", content, re.IGNORECASE)), 0)

    def test_no_hardcoded_sk_api_secret(self):
        """ag_search_client must not contain a hardcoded sk-... API secret token."""
        import os
        from pathlib import Path
        from ops.deploy_security_audit import audit_source

        client_path = Path(os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "ag_search_client.py")))
        source = client_path.read_text(encoding="utf-8")
        self.assertNotIn("sk-", source)
        self.assertNotIn("hardcoded_api_secret", audit_source(client_path, source))
        self.assertIn(
            "hardcoded_api_secret",
            audit_source("ops/fake.py", 'API_KEY = "sk-4fe19abbb8c5098c"\n'),
        )


if __name__ == "__main__":
    unittest.main()

