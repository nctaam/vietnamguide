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


if __name__ == "__main__":
    unittest.main()
