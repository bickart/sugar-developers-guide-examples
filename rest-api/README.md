# Accounts RestAPI Example

The Accounts RestAPI Example answers Anand Tupe's request for custom endpoints when Sugar's standard
API does not return the output an integration needs. This is an original example
using only the built-in Accounts module and stock fields. It adds no custom
modules, external services, or database changes.

The example uses the repository's [MIT license](../LICENSE). You may use, copy,
and adapt the code. Keep the copyright and license notice with copies or
substantial parts of the code.

## Start here

- [Read the article](article.md).
- [Open the complete endpoint](custom/modules/Accounts/clients/base/api/SdgAccountSummaryApi.php).
- [Open the readiness service](custom/modules/Accounts/Services/AccountReadinessService.php).
- [Open the platform registration](custom/Extension/application/Ext/Platforms/sdg_examples.php).
- [Run the PHP client](examples/account_summary.php) (PHP 8.2 or later, with cURL).
- [Download the installable ZIP](builds/sugardevelopersguideaccountsrestapiexample_1.0.1.zip).
- [Review the service unit tests](tests/unit/AccountReadinessServiceTest.php),
  [endpoint checks](tests/run.php), [client tests](tests/client.php), and
  [live integration tests](tests/integration/AccountSummaryEndpointTest.php).
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
- **PHP** is the language used for the endpoint. The PHP client is a separate
  program that sends requests to it. Run commands in a terminal (command window).

## Response format

`GET /rest/v11/SDG/Accounts/{record}/summary`

Use the widely supported `/rest/v11` base path for this example. The endpoint
sets no API version limits, so it is not restricted to `v11_27`. `record` is the
Account ID. The endpoint adds no GUID format rule. This address is separate
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
optional. Returned blank/null values become empty strings; fields left out by Sugar
become `null`. Missing fields use the
original Sugar field names, in the order above. `schema_version` identifies the
version of our response format, separate from Sugar's API version.

The endpoint extends `ModuleApi` and calls `parent::retrieveRecord()`. This uses
Sugar’s existing argument checks, record loading, view access, and field filtering.
Our code does not add its own ID pattern check or call the record/field permission
methods directly. The standalone client also sends the ID without a GUID rule.

Only values from the built-in API response are used. If Sugar leaves out a field,
its summary value is `null`, meaning unavailable. If a required value is unavailable,
`handoff.ready` and `handoff.missing_fields` are both `null`. A blank value that
Sugar does return is still treated as blank. Website is optional: an unavailable
website is `null` but does not prevent readiness when required values are present.

`requireArgs()` checks for required arguments; it is not a general input cleaner.
`retrieveBean()` loads a record; the API’s formatter applies field permissions.
The custom summary reuses the complete built-in API path rather than reading raw
bean properties or repeating those permission checks.

## Keep the business rule in a service

`AccountReadinessService` is a plain PHP class. It defines each field once, with
`required` set to boolean `true` or `false`. The array key is the Sugar field name;
there is no duplicate `field` property. These settings apply to our readiness
rule, not Sugar's required-field settings.

The API asks `getFieldNames()` which fields to request. After Sugar returns the
allowed data, the API passes it to `evaluate()`. The service decides readiness.
The API still groups the response into account, contact, and billing details.

The service does not load records, run database queries, or depend on an API
request. Other code can call the same rule. Unit tests can check it without a
Sugar installation. Sugar loads the service through its existing class loader;
no extra class registration or manual include is needed in the endpoint.

## Install in a sandbox

The endpoint lives under `custom/modules/Accounts/clients/base/api/` because it is
specific to Accounts. Global endpoints belong under `custom/clients/base/api/`.
The file location does not change the registered URL.

Choose **one** installation method; do not install the ZIP and manually copy the
same files. First confirm these uniquely named files do not already exist.

If you installed an earlier draft of this example, uninstall that package first.
If you copied the earlier file by hand, remove
`custom/clients/base/api/SdgAccountSummaryApi.php` before copying the new file.
Keep only one copy of the class so Sugar does not load it twice.

### Module Loader

1. Download the ZIP linked above. The GitHub repository's **Code → Download ZIP**
   downloads the repository, which is not a Module Loader package.
2. In Sugar, open **Admin → Module Loader**, upload the topic ZIP, and install it.
3. Run **Admin → Repair → Quick Repair and Rebuild** to register `sdg_examples`.
4. Open `https://YOUR-SUGAR/rest/v11/help` and find the SDG summary route. Quick
   Repair and Rebuild clears Sugar's cached list of API endpoints. If the route
   is missing, check the file paths and class name, then run that repair again.
5. Try the client below with a sandbox Account the test user can view.

### Copy source files (on-site development)

From the repository root, copy these three files into the matching paths under
your Sugar application root. Preserve existing unrelated files.

```text
rest-api/custom/modules/Accounts/clients/base/api/SdgAccountSummaryApi.php
  → custom/modules/Accounts/clients/base/api/SdgAccountSummaryApi.php
rest-api/custom/modules/Accounts/Services/AccountReadinessService.php
  → custom/modules/Accounts/Services/AccountReadinessService.php
rest-api/custom/Extension/application/Ext/Platforms/sdg_examples.php
  → custom/Extension/application/Ext/Platforms/sdg_examples.php
```

Then perform steps 3–5 above. Never deploy `tests/`, `examples/`, or `article.md`
under Sugar's public application directory.

## Make a request

Use PHP 8.2 or later with the cURL extension enabled. cURL is the PHP tool that
sends web requests. You do not need Composer or any extra packages to run this
client. Run it on your own computer, outside the Sugar application.

From the repository's main folder:

```sh
php rest-api/examples/account_summary.php \
  --base https://YOUR-SUGAR/rest/v11 \
  --record YOUR-ACCOUNT-ID \
  --username YOUR-TEST-USER
```

Replace all three placeholders. The client prompts for a password, authenticates
with the registered `sdg_examples` platform, then calls the summary using the
`OAuth-Token` header. It keeps tokens in memory and prints only the record response.
It requires HTTPS and refuses redirects. Use your canonical Sugar URL.

On macOS and Linux, the client hides typed passwords and tokens with the `stty`
terminal tool. On Windows, or where that tool is not available, pass the secret
through standard input from a trusted password tool or a protected local file.
Standard input is data sent to a command by another command or a file. Do not put
secrets in command arguments, shell history, or committed files. If you interrupt
a hidden prompt and your terminal stops showing text, run `stty echo` to restore
normal input display.

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
| Unknown record ID                                 | 404; `not_found`                                      |
| Record denied by ACL                              | 403, or 404 if normal retrieval cannot see it         |
| Account hidden by team security                   | 404; `not_found` during normal retrieval              |
| Required source field omitted by built-in API     | 200; field value and handoff result are `null`        |
| Missing/expired token                             | Authentication rejects request before handler runs    |

Check HTTP status in your HTTP client or browser network tools. The PHP client
reports non-2xx status and exits 1; it intentionally does not print error bodies.
The client encodes the ID as one address segment and lets Sugar handle lookup;
it does not enforce a GUID pattern. A missing record segment may fail route matching
before the handler runs. Test missing authentication with an HTTP client that
sends no token. Sugar's authentication error label/status can vary with the
failure and authentication configuration; consult your instance's API help.

## Local checks and rebuild

### Unit tests

A unit test checks one class on its own. These PHPUnit tests call the real service
with small arrays. They need no Sugar installation, database, or test doubles:

```sh
vendor/bin/phpunit rest-api/tests/unit
```

They cover complete records, each required blank value, omitted required values,
optional website values, more than one missing field, zero values, and unchanged
input data. A missing key means unknown; a returned null value counts as blank.

### Endpoint and client checks

These checks run locally without a Sugar installation:

```sh
php rest-api/tests/run.php
php rest-api/tests/client.php
```

The endpoint check uses the real service and a stand-in for `ModuleApi`. It checks
the API's field selection, response mapping, and handling of Sugar errors. The
client checks inspect requests without contacting a server. These checks do not
test the real Sugar permission or login systems.

### Integration tests

An integration test checks parts working together. The live tests call both the
built-in Accounts API and the installed custom endpoint. They compare the allowed
values and readiness result. A second test checks that Sugar rejects a bad token.

Install the package in a sandbox. Set these environment variables securely in your
test runner. Environment variables are named settings available to a program:

| Variable                | Value                                                            |
| ----------------------- | ---------------------------------------------------------------- |
| `SDG_TEST_BASE`         | Your HTTPS Sugar API base, such as `https://YOUR-SUGAR/rest/v11` |
| `SDG_TEST_ACCOUNT_ID`   | An existing test Account with made-up data                       |
| `SDG_TEST_ACCESS_TOKEN` | A valid access token for your test user                          |

Then run:

```sh
vendor/bin/phpunit rest-api/tests/integration
```

The tests read records; they do not create or change them. Run them with a normal
test user, then with a user whose field permissions are restricted. Keep the
record unchanged during the test, since it makes two separate read requests.
The tests may show expected and actual values on failure, so use made-up data.
Do not commit credentials or put tokens in command history.

If configuration or cURL is missing, the tests are marked **skipped**, not passed.
The separate team and role scenarios above still need testing in your own setup.
**Live Sugar installation and those checks remain required before production
use or a claim of runtime compatibility.** Target: Sugar 26.1, API v11.

The service, standalone client, and example tests use PHP 8.2-compatible code. The repository's
development tools need PHP 8.4, as listed in `composer.json`. That higher version
is needed to run the formatters, not to run this client. Keep using the PHP version
supported by your Sugar server for the endpoint itself.

Rebuild the topic package from the repository root with a new version number:

```sh
php bin/pack.php rest-api 1.0.2
```

Only `custom/` files and the repository license are included by the existing
packager, alongside its generated manifest. Package versions are independent of
Sugar versions. The generated manifest currently accepts Sugar 26.x; that range
is a packaging constraint, not proof that every release was tested.

## Remove

For Module Loader installations, uninstall the package through Module Loader.
For manual installations, remove only the three files listed above. Run Quick
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
