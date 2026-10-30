# When Sugar’s REST API Doesn’t Return What You Need

Create a custom Sugar endpoint that returns data in the format your app needs.

I [asked on LinkedIn](https://www.linkedin.com/feed/update/urn:li:activity:7509297833817100288/) which code examples would help Sugar developers. [Anand Tupe](https://www.linkedin.com/in/anand-tupe-300264112/)
asked for REST APIs, specifically new endpoints when Sugar’s API does not return
what an app needs. Thanks, Anand. This post works through that problem from start
to finish.

An API (application programming interface) lets one program ask another for data
or an action. REST (Representational State Transfer) is a common approach to
building web APIs. An endpoint is one address that an app can call through an API.

We will build an endpoint that reads one Account. In Sugar, a module is a group of
records of one type. The Accounts module holds records for businesses and other
organizations. Our example uses only fields already in that module.

**[Get the code and setup guide](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/tree/main/rest-api)**

The GitHub repository is a shared folder of example files. It includes the complete
PHP endpoint, a PHP client program to call it, an installable ZIP file, and tests.
You can try the example in a sandbox—a test copy of Sugar—without adding a custom
module.

The examples use the repository's [MIT license](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/blob/main/LICENSE).
You may use, copy, and adapt the code. Keep the copyright and license notice with
copies or substantial parts of the code.

## Check the existing API first

You may not need a new endpoint. Sugar’s built-in API can return selected fields,
find records that match a condition, and read linked records. Your app can also
change field names or group the data after it receives a response.

Our example reads five fields: `name`, `phone_office`, `website`,
`billing_address_city`, and `billing_address_country`. Sugar’s existing API can
already return all five.

So why add an endpoint? Suppose several apps need the same account summary, with
contact and billing details in separate groups. Each app also needs to know whether
the Account has enough data for the next step. Putting that format and rule in
Sugar gives every app the same answer.

## Decide what the response should contain

Our endpoint has this address:

```text
/rest/v11/SDG/Accounts/{record}/summary
```

The app sends a GET request to this address. GET asks for data; this endpoint does
not change the Account. `SDG` gives our example a separate name, so it does not
replace Sugar’s existing Accounts endpoints.

The `{record}` part is the Account ID. We use the widely supported `/rest/v11`
base path. Our endpoint does not set API version limits, so it is not restricted
to `v11_27`.

Here is a sample response. It uses JSON (JavaScript Object Notation), a text
format that programs use to exchange data. The labels on the left name each
value or group.

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

These are made-up values. You will use an Account from your test system.

The `handoff` group says whether the Account can pass to the next step. `ready` is
true when the name, office phone, billing city, and billing country contain data
other than spaces. Website is optional. This checks only for missing data; it does
not check whether a phone number, web address, or postal address is correct.
Change the rule to suit your process.

`missing_fields` lists the Sugar field names that need data. An empty list means
none of the required values are missing. `schema_version` is our response format’s
version number. It is separate from Sugar’s API version. If you change the
response in a way that could break an app, plan how that app will move to the new
format.

## Put the readiness rule in a service

The API should handle the request and response. The readiness rule belongs in a
separate PHP class. This is a small service: code that performs one business
operation. A logic hook, scheduled job, or another endpoint can use the same rule.

Create this file:

`custom/modules/Accounts/Services/AccountReadinessService.php`

`FIELD_DEFINITIONS` maps each Sugar field name to a `required` setting: boolean
`true` or `false`, without quotes. This readiness
rule is separate from Sugar’s own required-field settings.

```php
<?php

declare(strict_types=1);

namespace Sugarcrm\Sugarcrm\custom\modules\Accounts\Services;

/** Evaluates account readiness using only values supplied by its caller. */
final class AccountReadinessService
{
    private const FIELD_DEFINITIONS = [
        'name' => ['required' => true],
        'phone_office' => ['required' => true],
        'website' => ['required' => false],
        'billing_address_city' => ['required' => true],
        'billing_address_country' => ['required' => true],
    ];

    public function getFieldNames(): array
    {
        return array_keys(self::FIELD_DEFINITIONS);
    }

    /** A missing key is unknown; a returned null or blank value is missing data. */
    public function evaluate(array $data): array
    {
        $missing = [];
        $canCheck = true;

        foreach (self::FIELD_DEFINITIONS as $field => $definition) {
            if (!$definition['required']) {
                continue;
            }
            if (!array_key_exists($field, $data)) {
                $canCheck = false;
            } elseif (trim((string) ($data[$field] ?? '')) === '') {
                $missing[] = $field;
            }
        }

        return [
            'ready' => $canCheck ? $missing === [] : null,
            'missing_fields' => $canCheck ? $missing : null,
        ];
    }
}
```

`getFieldNames()` gives the API the field names from that one definition.
`evaluate()` decides whether the returned data is complete. The service takes an
array; it does not load a bean, access the database, or read the web request.

The namespace at the top groups the class under Accounts services. Sugar's class
loader maps that name to the file location. The API uses a `use` statement to
refer to the class by its short name. It needs no manual `require_once`.

This separation also lets us add focused unit tests for the rule and integration
tests for the full endpoint. We will explain both below.

## Add the endpoint

Create this file under your Sugar application folder:

`custom/modules/Accounts/clients/base/api/SdgAccountSummaryApi.php`

This endpoint belongs in the Accounts folder because it works only with Accounts.
Use `custom/clients/base/api/` for endpoints not tied to one module. The folder
does not change the URL; `registerApiRest()` defines that.

The PHP class and file have the same name. A class groups related code. Here,
`SdgAccountSummaryApi` extends Sugar’s `ModuleApi` class, so it can reuse the
built-in record API. `registerApiRest()` tells Sugar which address to use and which method
should handle the request. A method is a function inside a class.

This is the full endpoint. The same code is available as a file in the repository.

```php
<?php

use Sugarcrm\Sugarcrm\custom\modules\Accounts\Services\AccountReadinessService;

/** Original tutorial example using only stock Accounts fields. */
class SdgAccountSummaryApi extends ModuleApi
{
    public function registerApiRest()
    {
        return [
            'sdgAccountSummary' => [
                'reqType' => 'GET',
                'noLoginRequired' => false,
                'path' => ['SDG', 'Accounts', '?', 'summary'],
                'pathVars' => ['', '', 'record', ''],
                'method' => 'getAccountSummary',
                'shortHelp' => 'Return a permission-checked account handoff summary',
            ],
        ];
    }

    public function getAccountSummary($api, $args)
    {
        $service = new AccountReadinessService();
        $fields = $service->getFieldNames();
        $args['module'] = 'Accounts';
        $args['fields'] = implode(',', $fields);
        $data = parent::retrieveRecord($api, $args);

        // Use only the data returned by Sugar's standard record API.
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = array_key_exists($field, $data)
                ? trim((string) ($data[$field] ?? ''))
                : null;
        }

        return [
            'schema_version' => 1,
            'account' => ['id' => $data['id'], 'name' => $values['name']],
            'contact' => ['phone' => $values['phone_office'], 'website' => $values['website']],
            'billing' => [
                'city' => $values['billing_address_city'],
                'country' => $values['billing_address_country'],
            ],
            'handoff' => $service->evaluate($data),
        ];
    }
}
```

## Check access before returning data

The code uses Sugar’s existing record API instead of adding its own ID or
permission checks.

First, `noLoginRequired` is false, so Sugar checks the user’s login. This is
called authentication: proof of who is making the request.

Next, the API asks the service which fields it needs. We fix the module to
Accounts and select those fields. The caller cannot change that selection. `parent::retrieveRecord()` then calls the
built-in `ModuleApi` method. It checks required arguments, loads the record,
checks view access, and formats the response using Sugar’s field permissions.
`implode(',', $fields)` joins the field names into the comma-separated form used
in a normal REST request.

`requireArgs()` checks that arguments are present. It is not a general tool for
cleaning or changing every input value. We use the same record-loading path as
Sugar’s built-in API and do not add a separate GUID format rule. A GUID (globally
unique identifier) is the type of ID Sugar normally creates for a record.

`BeanFactory::retrieveBean()` loads a bean,
which is Sugar’s code object for a record. It does not format a safe API response
by itself. `ModuleApi::retrieveRecord()` also applies the record and field rules
needed for that response. Our summary uses only the data that method returns.

If Sugar leaves out a field, we return `null` for its value. `null` means the value
is not available; it does not mean the field is blank. If a required value is not
available, both `ready` and `missing_fields` are `null`. We cannot decide whether
that Account is ready from the data we received. A website left out by Sugar
remains `null`, but does not affect readiness because website is optional.
A visible blank value is returned as `""`, an empty string. A visible value made
only of spaces is also returned as `""`.

This keeps permission handling in Sugar’s standard code. The API groups the
allowed values. The service applies the missing-data rule to that same returned
data.

## Install it in a test copy of Sugar

The [setup guide](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/blob/main/rest-api/README.md)
links to the ZIP file. Upload that file through Admin → Module Loader. Then run
Admin → Repair → Quick Repair and Rebuild.

The package adds three files: the endpoint, the readiness service, and a file that
registers `sdg_examples` as an API platform. In this case, a platform is a name used to identify the app
when it logs in. A separate name helps avoid conflicts with your browser login.

If you manage Sugar on your own server, you can copy the three files into the paths
listed in the guide instead. Choose one install method. Keep the article, client
program, and tests outside Sugar’s public application folder.

Open `/rest/v11/help` on your Sugar site and look for the SDG summary address.
Quick Repair and Rebuild clears Sugar’s cached list of API endpoints. If the
address is missing, check the file paths and class name, then run that repair again.

The setup guide also explains how to remove the example. It adds no fields or
business records.

## Call the endpoint

Download the companion repository. Open a terminal (command window) in its main folder
and run this command. You will need PHP 8.2 or later with the cURL extension
enabled. cURL is the PHP tool that sends web requests. No Composer packages are
needed to run the client. Keep this program outside the Sugar application folder.

```sh
php rest-api/examples/account_summary.php \
  --base https://YOUR-SUGAR/rest/v11 \
  --record YOUR-ACCOUNT-ID \
  --username YOUR-TEST-USER
```

Replace the site address, Account ID, and username with your test values. The
program asks for your password. On macOS and Linux, it hides what you type.
The setup guide explains how to supply a secret on Windows or when hidden input
is not available.

It uses Sugar’s OAuth 2.0 login process to get an access token. A token is a
temporary value that proves the app has logged in. The program sends that value
in the `OAuth-Token` request header. A header is information sent with a web
request. The program uses HTTPS to encrypt the connection, refuses redirects to
another address, and prints only the Account response.

Some organizations use SugarIdentity or single sign-on (SSO), where one login
works across several apps. If your system does not allow the password login used
here, get an access token through your supported login process. Leave out
`--username` and the program will ask for the token instead.

Add `--standard` to the command to call Sugar’s built-in Accounts endpoint with
the same five fields. Compare that response with our summary. Both read the same
record. Our endpoint adds the grouping and the missing-data rule.

Use a separate test user. Repeated logins with the same user and platform name
may end an earlier session.

## Test errors as well as success

Start with a normal user who can view the Account and read all five fields.
Then try these cases:

- A user who cannot view the Account.
- An Account outside the user’s teams.
- A role that denies read access to one source field.
- A required field that is blank.
- A blank website, with all required fields filled in.

Denied record access should return an error. A field left out by Sugar should
remain `null` in our summary. If that field is required, `ready` and
`missing_fields` should also be `null`. A visible but blank required field should
return `ready: false` and name the field in `missing_fields`. A blank or unavailable
website alone should not make `ready` false.

The endpoint uses Sugar’s built-in error classes. An error class tells Sugar
which response to send. A web response includes an HTTP status code: a number
that says whether the request worked.

| Result               | Status | Error class                      |
| -------------------- | ------ | -------------------------------- |
| Account not found    | 404    | `SugarApiExceptionNotFound`      |
| Record access denied | 403    | `SugarApiExceptionNotAuthorized` |

When team security hides an Account from normal retrieval, Sugar returns 404
before the view check runs.
Sugar handles missing or expired login tokens before this method runs.

The Account ID is part of the address. If it is absent, the request does not match
this route and does not reach our method. To test missing authentication, use an
HTTP client, such as Postman, without a token.

## Add unit and integration tests

Moving the rule into a service gives us two clear test levels.

A **unit test** checks the service on its own. We pass it small arrays and check
its answer. There is no database, API login, or Sugar installation involved. We
can test a complete record, blank required fields, an optional website, and values
left out of the data. These tests are fast and help us change the rule safely.

The repository includes the complete
[service unit tests](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/blob/main/rest-api/tests/unit/AccountReadinessServiceTest.php).
With the repository's development tools installed, run:

```sh
vendor/bin/phpunit rest-api/tests/unit
```

An **integration test** checks parts working together. After installing the
example in a Sugar sandbox, the live tests call both the built-in Accounts API
and our endpoint. They check that the summary uses the same allowed values and
that the service's readiness result reaches the response. A second test checks
that Sugar rejects a bad access token.

The [setup guide](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/blob/main/rest-api/README.md)
explains how to supply the test site, Account ID, and access token as environment
variables. These are named settings available to the test program. Use made-up
Account data and keep credentials out of the repository and command history.
Then run:

```sh
vendor/bin/phpunit rest-api/tests/integration
```

Without a configured Sugar test system, those tests are marked skipped. A skipped
test is not a pass. The local endpoint checks use the real service and a stand-in
for Sugar's record API; they do not prove that Sugar's login or permission rules
work in an installed system.

Run the live tests with a normal user and a user with restricted field access.
Also test the team and role cases listed above. The tests read data without
changing records. Keep the test Account unchanged while they run.

The example code supports PHP 8.2 or later. The development tools in this
repository, including its installed PHPUnit test runner, require PHP 8.4.
**A check in a real Sugar test system is still needed before use with business
data.**

## Find all the example files

Start at the [Accounts RestAPI Example](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/tree/main/rest-api)
for setup steps, a download, and direct links to each file. The
[examples index](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/blob/main/EXAMPLES.md)
also lists earlier topics and shows which ones still need code.

What output have you needed from Sugar’s API? Share the input and the JSON
response you wanted, using made-up data. That could become a future example.

Next in the series: SugarQuery, Sugar’s tool for building database queries in PHP.

## Further reading

- [Sugar 26.1 REST API and login](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_26.1/integration/web_services/rest_api/)
- [Add API endpoints](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_26.1/integration/web_services/rest_api/extending_endpoints/)
- [Sugar API errors](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_25.1/integration/web_services/rest_api/api_exceptions/)
