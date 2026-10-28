# Copyright (C) Amaiza LLC. - All Rights Reserved
#
# This source code is proprietary and confidential and protected under
# international copyright law. All rights reserved and protected by
# the copyright holders. This file is only available to authorized individuals
# with the permission of the copyright holders. Unauthorized copying of this file,
# via any medium is strictly prohibited. If you encounter this file and do not have
# permission, please contact the copyright holders and delete this file.

"""Offline request checks; never contacts Sugar."""

import importlib.util
import io
import json
from pathlib import Path
import unittest
from unittest.mock import patch

SPEC = importlib.util.spec_from_file_location(
    "account_summary", Path(__file__).resolve().parents[1] / "examples/account_summary.py"
)
CLIENT = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(CLIENT)
BASE = "https://crm.example.com/rest/v11_27"
RECORD = "11111111-2222-3333-4444-555555555555"


class FakeOpener:
    def __init__(self):
        self.requests = []

    def open(self, request, timeout):
        self.requests.append((request, timeout))
        payload = {"access_token": "test-token", "refresh_token": "never-print"}
        if not request.full_url.endswith("/oauth2/token"):
            payload = {"schema_version": 1}
        return io.BytesIO(json.dumps(payload).encode())


class ClientTest(unittest.TestCase):
    def test_base_validation(self):
        self.assertEqual(CLIENT.validate_base(BASE + "/"), BASE)
        self.assertEqual(CLIENT.validate_base("https://example.com/sugar/rest/v11"),
                         "https://example.com/sugar/rest/v11")
        for invalid in ["http://example.com/rest/v11", "https://example.com",
                        BASE + "?token=x", BASE + "#x", "https://u:p@example.com/rest/v11"]:
            with self.subTest(invalid=invalid), self.assertRaises(ValueError):
                CLIENT.validate_base(invalid)

    def test_login_then_summary_and_no_tokens_in_output(self):
        opener = FakeOpener()
        argv = ["client", "--base", BASE, "--record", RECORD, "--username", "test-user"]
        with patch("sys.argv", argv), patch.object(CLIENT, "build_opener", return_value=opener), \
                patch.object(CLIENT.getpass, "getpass", return_value="not-a-real-password"), \
                patch("sys.stdout", new_callable=io.StringIO) as output:
            self.assertEqual(CLIENT.main(), 0)
        login, get = [entry[0] for entry in opener.requests]
        body = json.loads(login.data)
        self.assertEqual(body["platform"], "sdg_examples")
        self.assertEqual(body["grant_type"], "password")
        self.assertEqual(login.get_method(), "POST")
        self.assertEqual(get.get_method(), "GET")
        self.assertEqual(get.get_header("Oauth-token"), "test-token")
        self.assertEqual(get.full_url, BASE + "/SDG/Accounts/" + RECORD + "/summary")
        self.assertEqual(json.loads(output.getvalue()), {"schema_version": 1})
        self.assertNotIn("test-token", output.getvalue())
        self.assertNotIn("never-print", output.getvalue())

    def test_existing_token_and_standard_fields(self):
        opener = FakeOpener()
        argv = ["client", "--base", BASE, "--record", RECORD, "--standard"]
        with patch("sys.argv", argv), patch.object(CLIENT, "build_opener", return_value=opener), \
                patch.object(CLIENT.getpass, "getpass", return_value="existing-token"), \
                patch("sys.stdout", new_callable=io.StringIO):
            self.assertEqual(CLIENT.main(), 0)
        self.assertEqual(len(opener.requests), 1)
        request = opener.requests[0][0]
        self.assertTrue(request.full_url.startswith(BASE + "/Accounts/" + RECORD + "?fields="))
        self.assertEqual(request.get_header("Oauth-token"), "existing-token")

    def test_redirects_are_not_followed(self):
        self.assertIsNone(CLIENT.NoRedirects().redirect_request(None, None, 302, "", {}, "https://other.example"))

    def test_invalid_record_does_not_request_credentials_or_network(self):
        with patch("sys.argv", ["client", "--base", BASE, "--record", "../Accounts"]), \
                patch.object(CLIENT.getpass, "getpass") as prompt, \
                patch.object(CLIENT, "build_opener") as network, \
                patch("sys.stderr", new_callable=io.StringIO):
            self.assertEqual(CLIENT.main(), 1)
            prompt.assert_not_called()
            network.assert_not_called()


if __name__ == "__main__":
    unittest.main()
