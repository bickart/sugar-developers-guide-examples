#!/usr/bin/env python3
# Copyright (C) Amaiza LLC. - All Rights Reserved
#
# This source code is proprietary and confidential and protected under
# international copyright law. All rights reserved and protected by
# the copyright holders. This file is only available to authorized individuals
# with the permission of the copyright holders. Unauthorized copying of this file,
# via any medium is strictly prohibited. If you encounter this file and do not have
# permission, please contact the copyright holders and delete this file.

"""Read a stock Account or the SDG summary. Requires only Python 3's standard library."""

import argparse
import getpass
import json
import re
import sys
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode, urlsplit
from urllib.request import HTTPRedirectHandler, Request, build_opener

FIELDS = "name,phone_office,website,billing_address_city,billing_address_country"
GUID = re.compile(r"[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}", re.I)


class NoRedirects(HTTPRedirectHandler):
    """Do not forward a password or token to a redirected destination."""

    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def validate_base(value):
    parts = urlsplit(value)
    if (
        parts.scheme != "https"
        or not parts.hostname
        or parts.username
        or parts.password
        or parts.query
        or parts.fragment
        or not re.fullmatch(r".*/rest/v\d+(?:_\d+)?/?", parts.path)
    ):
        raise ValueError("Use an HTTPS API base such as https://crm.example.com/rest/v11_27")
    return value.rstrip("/")


def request_json(opener, url, payload=None, token=None):
    headers = {"Accept": "application/json"}
    data = None
    if payload is not None:
        data = json.dumps(payload).encode("utf-8")
        headers["Content-Type"] = "application/json"
    if token:
        headers["OAuth-Token"] = token
    request = Request(url, data=data, headers=headers)
    with opener.open(request, timeout=30) as response:
        return json.load(response)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base", required=True, help="HTTPS Sugar API base, including /rest/v11_27")
    parser.add_argument("--record", required=True, help="Existing sandbox Account GUID")
    parser.add_argument("--username", help="Prompt for a password; otherwise prompt for an existing token")
    parser.add_argument("--standard", action="store_true", help="Call Sugar's stock record endpoint")
    args = parser.parse_args()
    try:
        base = validate_base(args.base)
        if not GUID.fullmatch(args.record):
            raise ValueError("--record must be a Sugar GUID")
        opener = build_opener(NoRedirects())
        if args.username:
            password = getpass.getpass("Sugar password: ")
            auth = request_json(opener, base + "/oauth2/token", {
                "grant_type": "password",
                "client_id": "sugar",
                "client_secret": "",
                "platform": "sdg_examples",
                "username": args.username,
                "password": password,
            })
            token = auth["access_token"]
            del password, auth
        else:
            token = getpass.getpass("OAuth access token: ")
        if not token:
            raise ValueError("An access token is required")
        if args.standard:
            path = "/Accounts/" + args.record + "?" + urlencode({"fields": FIELDS})
        else:
            path = "/SDG/Accounts/" + args.record + "/summary"
        result = request_json(opener, base + path, token=token)
        print(json.dumps(result, indent=2, ensure_ascii=False))
    except HTTPError as exc:
        print(f"HTTP {exc.code}: request failed. Check the API help and your permissions.", file=sys.stderr)
        return 1
    except (URLError, ValueError, KeyError, OSError) as exc:
        print(f"Request failed: {exc}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
