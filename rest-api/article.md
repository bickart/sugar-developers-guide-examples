# When Sugar’s REST API Doesn’t Return What You Need

Create a custom Sugar endpoint that returns data in the format your app needs.

I asked on LinkedIn which code examples would help Sugar developers. Anand Tupe
chose REST APIs. I asked whether he wanted to use Sugar’s existing API or build
new endpoints.

He wanted to build endpoints when Sugar’s API does not return the output an app
needs. Thanks, Anand. This post works through that problem from start to finish.

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

## Check the existing API first

You may not need a new endpoint. Sugar’s built-in API can return selected fields,
find records that match a condition, and read linked records. Your app can also
change field names or group the data after it receives a response.

Our example reads five fields: `name`, `phone_office`, `website`,
`billing_address_city`, and `billing_address_country`. Sugar’s existing API can
already return all five.

So why add an endpoint? Suppose several apps need the same account summary. Each
app expects contact and billing details in separate groups. Each also needs to
know whether the account has enough data to pass to the next step in a process.

We can put that format and rule in Sugar. Then each app gets the same answer.
This is a choice about where the rule belongs. It is not a limit on Sugar’s
ability to return the fields.

## Decide what the response should contain

Our endpoint has this address:

```text
/rest/v11_27/SDG/Accounts/{record}/summary
```

The app sends a GET request to this address. GET asks for data; this endpoint does
not change the Account. `SDG` gives our example a separate name, so it does not
replace Sugar’s existing Accounts endpoints.

The `{record}` part is the Account ID. The example uses API version v11_27,
listed in the Sugar 26.1 guide. Use a version supported by your own Sugar system.

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

The `handoff` group says whether the Account can pass to the next step. For this
example, `ready` is true when the name, office phone, billing city, and billing
country are not blank. Spaces at the start and end do not count as data. Website
is optional.

This is a simple check for missing data. It does not check whether a phone number,
web address, or postal address is correct. Change the rule to suit your process.

`missing_fields` lists the Sugar field names that need data. An empty list means
none of the required values are missing. `schema_version` is our response format’s
version number. It is separate from Sugar’s API version. If you change the
response in a way that could break an app, plan how that app will move to the new
format.

## Add the endpoint

Create this file under your Sugar application folder:

`custom/clients/base/api/SdgAccountSummaryApi.php`

The PHP class and file have the same name. A class groups related code. Here,
`SdgAccountSummaryApi` extends Sugar’s `SugarApi` class, so it can use Sugar’s
API system. `registerApiRest()` tells Sugar which address to use and which method
should handle the request. A method is a function inside a class.

This is the full endpoint. The same code is available as a file in the repository.

```php
<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/** Original tutorial example using only stock Accounts fields. */
class SdgAccountSummaryApi extends SugarApi
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
        $record = $args['record'] ?? null;
        if (!is_string($record) || !preg_match(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i',
            $record
        )) {
            throw new SugarApiExceptionInvalidParameter('record must be a Sugar GUID');
        }

        // Keep Sugar's normal row-level security; never bypass it for this endpoint.
        $account = BeanFactory::retrieveBean('Accounts', $record);
        if (empty($account) || empty($account->id)) {
            throw new SugarApiExceptionNotFound('Account not found');
        }
        if (!$account->ACLAccess('view')) {
            throw new SugarApiExceptionNotAuthorized('Account access denied');
        }

        $fields = ['name', 'phone_office', 'website', 'billing_address_city', 'billing_address_country'];
        foreach ($fields as $field) {
            if (!$account->ACLFieldAccess($field, 'read')) {
                // Deny the entire summary before reading values or deriving readiness.
                throw new SugarApiExceptionNotAuthorized('Summary field access denied');
            }
        }

        $values = [];
        foreach ($fields as $field) {
            $values[$field] = trim((string) ($account->$field ?? ''));
        }

        // Tutorial policy: presence only, not phone, URL, or postal validation.
        $required = ['name', 'phone_office', 'billing_address_city', 'billing_address_country'];
        $missing = [];
        foreach ($required as $field) {
            if ($values[$field] === '') {
                $missing[] = $field;
            }
        }

        return [
            'schema_version' => 1,
            'account' => ['id' => $account->id, 'name' => $values['name']],
            'contact' => ['phone' => $values['phone_office'], 'website' => $values['website']],
            'billing' => [
                'city' => $values['billing_address_city'],
                'country' => $values['billing_address_country'],
            ],
            'handoff' => ['ready' => $missing === [], 'missing_fields' => $missing],
        ];
    }
}
```

## Check access before returning data

The code follows four steps.

First, Sugar checks the user’s login. Setting `noLoginRequired` to false means
this endpoint requires authentication: proof of who is making the request.
The code does not build a second login system.

Second, it checks the Account ID. We accept a Sugar GUID (globally unique
identifier): an ID in the letter-and-number format shown in the sample response.
A value in the wrong format is rejected before Sugar looks for the record.
The code always reads Accounts; the caller cannot choose another module.

Third, it loads the Account through `BeanFactory::retrieveBean()`. A bean is
Sugar’s code object for a record. This method keeps Sugar’s normal team rules in
place. We then check whether the user may view that record.

Fourth, it checks read access to all five fields before reading their values.
The method names contain ACL, which means access control list. These checks use
Sugar’s permission rules for records and fields.

Why check the fields before calculating `ready`? Suppose a user cannot read
billing country. A result based on that field could still reveal information
about it. The checks protect both the field values and results based on them.

In this example, a denied field blocks the whole summary. That includes website,
even though website is optional in the missing-data rule. This keeps the success
response the same for every allowed request. You could choose to leave out
restricted fields instead, but then you must define how partial responses and
calculated values should work.

## Install it in a test copy of Sugar

The [setup guide](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/blob/main/rest-api/README.md)
links to the ZIP file. Upload that file through Admin → Module Loader. Then run
Admin → Repair → Quick Repair and Rebuild.

The package adds two files: the endpoint and a file that registers `sdg_examples`
as an API platform. In this case, a platform is a name used to identify the app
when it logs in. A separate name helps avoid conflicts with your browser login.

If you manage Sugar on your own server, you can copy the two files into the paths
listed in the guide instead. Choose one install method. Keep the article, client
program, and tests outside Sugar’s public application folder.

Open `/rest/v11_27/help` on your Sugar site and look for the SDG summary address.
If it is missing, use Rebuild API Metadata in Admin → Repair, where available.
That rebuild updates Sugar’s list of API endpoints.

The setup guide also explains how to remove the example. It adds no fields or
business records.

## Call the endpoint

Download the companion repository. Open a terminal (command window) in its main folder
and run this command. You will need PHP 8.2 or later with the cURL extension
enabled. cURL is the PHP tool that sends web requests. No Composer packages are
needed to run the client. Keep this program outside the Sugar application folder.

```sh
php rest-api/examples/account_summary.php \
  --base https://YOUR-SUGAR/rest/v11_27 \
  --record YOUR-ACCOUNT-GUID \
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

Denied access should return no Account data and no `ready` result. A blank
required field should return `ready: false` and name the field in `missing_fields`.
A blank website alone should not make `ready` false.

The endpoint uses Sugar’s built-in error classes. An error class tells Sugar
which response to send. A web response includes an HTTP status code: a number
that says whether the request worked.

| Result                        | Status | Error class                         |
| ----------------------------- | ------ | ----------------------------------- |
| ID has the wrong format       | 422    | `SugarApiExceptionInvalidParameter` |
| Account not found             | 404    | `SugarApiExceptionNotFound`         |
| Record or field access denied | 403    | `SugarApiExceptionNotAuthorized`    |

An Account hidden by team rules may return 404 before the view check runs.
Sugar handles missing or expired login tokens before this method runs.

Use an HTTP client, such as Postman, to test missing tokens and IDs in the wrong
format. The PHP program checks ID format before sending a request and always
asks for a login or token.

The repository includes tests for the example’s input checks, permission checks,
response format, and missing-data rule. It also checks requests from the PHP client without
connecting to a server. These tests use stand-ins for Sugar. They do not prove
that installation, login, or team rules work in a real Sugar system. **The example
still needs a check in a real Sugar test system before use with business data.** It has not yet been
run against a live Sugar instance.

## Find all the example files

Start at the [Month 5 guide](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/tree/main/rest-api)
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
