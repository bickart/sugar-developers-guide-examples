# Custom REST API: Account Summary

Month 5 answers Anand Tupe's request for custom endpoints when Sugar's standard
API does not return the output an integration needs. This is an original example
using only the built-in Accounts module and stock fields. It has no client code,
custom modules, external services, or database changes.

## Start here

- [Read the article](article.md).
- [Open the complete endpoint](custom/clients/base/api/SdgAccountSummaryApi.php).
- [Open the platform registration](custom/Extension/application/Ext/Platforms/sdg_examples.php).
- [Run the Python client](examples/account_summary.py) (Python 3, no extra packages).
- [Download the installable ZIP](builds/sugardevelopersguiderestapiexamples_1.0.0.zip).
- [Review the isolated endpoint tests](tests/run.php) and [client tests](tests/test_client.py).
- [Browse all examples](../EXAMPLES.md).

## Terms used in this guide

- An **API** (application programming interface) lets one program ask another for
  data or an action. **REST** means Representational State Transfer, a common way
  to build web APIs. An **endpoint** is one address an app can call.
- **JSON** (JavaScript Object Notation) is a text format for exchanging data.
- A **GUID** (globally unique identifier) is the record ID format used below.
- **ACL** means access control list: Sugar's rules for who may read or change data.
- A **sandbox** is a test copy of Sugar. A **module** is a group of one record type.
- An **OAuth access token** is a temporary value that proves an app has logged in.
  A **platform** is a name used to identify the app when it logs in.
- **PHP** is the language used for the endpoint. The Python client is a separate
  program that sends requests to it. Run commands in a terminal (command window).

## Response format

`GET /rest/v11_27/SDG/Accounts/{record}/summary`

Use an API version supported by your Sugar system; the Sugar 26.1 guide lists
versions up to `v11_27`. `record` must be a Sugar GUID with hyphens. This address is separate
from stock `/Accounts/{record}` and does not override it.

```json
{
  "schema_version": 1,
  "account": {
    "id": "11111111-2222-3333-4444-555555555555",
    "name": "Example Account"
  },
  "contact": {
    "phone": "+44 20 7946 0123",
    "website": "https://example.com"
  },
  "billing": { "city": "London", "country": "United Kingdom" },
  "handoff": { "ready": true, "missing_fields": [] }
}
```

This response is illustrative, not a preinstalled record. Use an existing sandbox
Account ID. Readiness means `name`, `phone_office`, `billing_address_city`, and
`billing_address_country` are nonblank after trimming. It is an example presence
rule, not validation of phone numbers, addresses, countries, or URLs. Website is
optional. Empty/null source values become empty strings. Missing fields use the
original Sugar field names, in the order above. `schema_version` identifies the
version of our response format, separate from Sugar's API version.

An authenticated user must have record view access and read access to **all five**
source fields, including optional `website`. A restricted field rejects the whole
response with 403, before values are read or readiness is calculated. This is a
deliberate choice to keep the success response the same for all allowed requests.
If you leave out restricted fields instead, explain how that response works and
protect any values calculated from those fields too.

## Install in a sandbox

Choose **one** installation method; do not install the ZIP and manually copy the
same files. First confirm these uniquely named files do not already exist.

### Module Loader

1. Download the ZIP linked above. The GitHub repository's **Code → Download ZIP**
   downloads the repository, which is not a Module Loader package.
2. In Sugar, open **Admin → Module Loader**, upload the topic ZIP, and install it.
3. Run **Admin → Repair → Quick Repair and Rebuild** to register `sdg_examples`.
4. Open `https://YOUR-SUGAR/rest/v11_27/help` and find the SDG summary route. If the
   route is missing, use **Admin → Repair → Rebuild API Metadata** where available.
5. Try the client below with a sandbox Account the test user can view.

### Copy source files (on-site development)

From the repository root, copy only these two files into the matching paths under
your Sugar application root. Preserve existing unrelated files.

```text
rest-api/custom/clients/base/api/SdgAccountSummaryApi.php
  → custom/clients/base/api/SdgAccountSummaryApi.php
rest-api/custom/Extension/application/Ext/Platforms/sdg_examples.php
  → custom/Extension/application/Ext/Platforms/sdg_examples.php
```

Then perform steps 3–5 above. Never deploy `tests/`, `examples/`, or `article.md`
under Sugar's public application directory.

## Make a request

From the repository's main folder:

```sh
python3 rest-api/examples/account_summary.py \
  --base https://YOUR-SUGAR/rest/v11_27 \
  --record YOUR-ACCOUNT-GUID \
  --username YOUR-TEST-USER
```

Replace all three placeholders. The client prompts for a password, authenticates
with the registered `sdg_examples` platform, then calls the summary using the
`OAuth-Token` header. It keeps tokens in memory and prints only the record response.
It requires HTTPS and refuses redirects. Use your canonical Sugar URL.

For SugarIdentity, single sign-on (SSO), or other login rules that do not permit this
password flow, obtain an access token through your supported flow and omit
`--username`; the client prompts for that token instead. Passwords and tokens are
not command-line arguments. Repeated logins with the same user/platform may
invalidate another session. Use a dedicated test user/platform combination.

Add `--standard` to call the existing `/Accounts/{record}?fields=...` endpoint with
the same source fields. Compare the stock result with the tailored contract. If
field selection and client-side mapping solve your requirement, you do not need
a custom endpoint.

The client makes a read-only record request; it does not create, update, or delete
Accounts. Authentication still creates a session. No sample credentials or real
account data are included.

## Verify in your instance

| Scenario                                          | Expected result                                       |
| ------------------------------------------------- | ----------------------------------------------------- |
| Valid visible Account, all source fields readable | 200; nested response                                  |
| Required field blank                              | 200; `ready: false`, field listed in `missing_fields` |
| Website blank, required fields populated          | 200; `website: ""`, `ready: true`                     |
| Invalid GUID, with a valid token                  | 422; `invalid_parameter`                              |
| Unknown GUID                                      | 404; `not_found`                                      |
| Record denied by ACL                              | 403, or 404 if normal retrieval cannot see it         |
| Account outside user's teams                      | No account data; ordinarily 404 at retrieval          |
| Any source field denied by role                   | 403; no partial response or readiness                 |
| Missing/expired token                             | Authentication rejects request before handler runs    |

Check HTTP status in your HTTP client or browser network tools. The Python client
reports non-2xx status and exits 1; it intentionally does not print error bodies.
To test a malformed ID, use an HTTP client with a valid token: the Python client
rejects malformed GUIDs locally. Test missing authentication with an HTTP client
that sends no token. Sugar's authentication error label/status can vary with the
failure and authentication configuration; consult your instance's API help.

## Local checks and rebuild

No Sugar installation is required for these tests. They use stand-ins for Sugar:

```sh
php rest-api/tests/run.php
python3 -m unittest discover -s rest-api/tests -p 'test_*.py'
```

The PHP tests use explicit Sugar doubles to check route metadata, validation,
retrieval arguments, permission denial, contract shape, and presence rules. The
Python tests inspect outgoing requests without a network connection. They do not
verify Sugar's real route discovery, OAuth, team security, Module Loader, or role
engine. **Live Sugar installation and the scenario checks above remain required
before production use or a claim of runtime compatibility.** Target: Sugar 26.1,
API v11_27. No live Sugar execution was available when this example was authored.

Rebuild the topic package from the repository root with a new version number:

```sh
php bin/pack.php rest-api 1.0.1
```

Only `custom/` files and the repository license are included by the existing
packager, alongside its generated manifest. Package versions are independent of
Sugar versions. The generated manifest currently accepts Sugar 26.x; that range
is a packaging constraint, not proof that every release was tested.

## Remove

For Module Loader installations, uninstall the package through Module Loader.
For manual installations, remove only the two files listed above. Run Quick
Repair and Rebuild and rebuild API metadata afterward. No business data or fields
are created by this example. Tokens already issued for a platform are not
automatically revoked by deleting its registration; end test sessions through
your normal session-management process.

## References

- [Sugar 26.1 REST API and authentication](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_26.1/integration/web_services/rest_api/)
- [Custom endpoint registration](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_26.1/integration/web_services/rest_api/extending_endpoints/)
- [API exceptions](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_26.1/integration/web_services/rest_api/api_exceptions/)
- [Platforms extension](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_14.0/architecture/extensions/platforms/)
- [BeanFactory](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_25.2/data_framework/models/beanfactory/)
