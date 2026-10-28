# When Sugar’s REST API Doesn’t Return What You Need

Build a custom SugarApi endpoint with a response your integration can use.

_Sugar Developers Guide — Month 5 of the customization series_

When I asked on LinkedIn which working examples would save Sugar developers time,
Anand Tupe picked REST APIs. I asked a follow-up: consuming Sugar’s existing API,
or building custom endpoints?

His answer was specific: building and customizing endpoints when the standard API
doesn’t provide the output you need. Thanks, Anand. That’s a useful problem to
build this installment around.

Let’s say an onboarding tool needs an account summary. It expects contact details
in one object, billing details in another, and a flag telling it whether the
account has the minimum information for a handoff. Sugar already stores the
fields. Our job is to define that small contract and enforce the same access
rules the user should have in Sugar.

**[Get the complete example and installation guide](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/tree/main/rest-api)**

The repository includes the full endpoint, platform registration, a runnable
Python client, isolated tests, and an installable topic ZIP. The example uses the standard Accounts module and stock fields, so you can
follow along in a sandbox without installing a custom module.

## First: do you need a custom endpoint?

Start with Sugar’s existing API. If you need fewer fields, use field selection.
If you need a subset of records, use filters. Related-record APIs and bulk
requests may also cover your requirement. Renaming a few keys in a client is a
perfectly reasonable option.

For our Account, the existing single-record endpoint can retrieve `name`,
`phone_office`, `website`, `billing_address_city`, and `billing_address_country`.
The companion client’s `--standard` option makes that request so you can compare
it with the custom response.

A custom endpoint earns its keep when you want Sugar to own a shared response
contract or business rule. Here, the handoff rule and nested output belong in one
place, rather than being reimplemented by every consumer. This is a design
choice, not a missing ability to retrieve the underlying fields.

## Define the response before writing PHP

Our new route is a read-only GET:

```text
/rest/v11_27/SDG/Accounts/{record}/summary
```

`SDG` gives the example its own route prefix. We leave the stock Accounts routes
alone. The example uses API v11_27, documented for Sugar 26.1; choose an API
version supported by your instance.

An illustrative response looks like this:

```json
{
  "schema_version": 1,
  "account": {
    "id": "11111111-2222-3333-4444-555555555555",
    "name": "Example Account"
  },
  "contact": {
    "phone": "+1 202-555-0147",
    "website": "https://example.com"
  },
  "billing": { "city": "Portland", "country": "USA" },
  "handoff": { "ready": true, "missing_fields": [] }
}
```

The ID is illustrative. You’ll use an existing Account from your sandbox.

For this tutorial, “ready” means name, office phone, billing city, and billing
country are nonblank after trimming. Website is optional. This checks presence;
it does not establish that a phone number, URL, or address is valid. Replace the
policy with your own explicit requirements when adapting the example.

The version number describes our response contract, separately from Sugar’s API
version. If you later make an incompatible change, introduce a new contract and
migration plan rather than silently changing what existing clients receive.

## Register the route and implement the handler

Create this file under your Sugar application root:

`custom/clients/base/api/SdgAccountSummaryApi.php`

The file and class names match. The complete implementation is below; the
repository contains the same implementation as a downloadable file.

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

There are four useful details here.

First, `noLoginRequired` is explicitly false. Sugar handles authentication before
this handler runs. The endpoint doesn’t parse passwords or manufacture its own
session system.

Second, the route maps only the wildcard to `record`. We accept a hyphenated Sugar
GUID and reject malformed values before retrieval. We also hard-code Accounts:
this is not an arbitrary-module data reader.

Third, `BeanFactory::retrieveBean()` uses normal retrieval security. We do not
turn off row-level security. If retrieval finds no visible record, the handler
returns a not-found error. The separate `ACLAccess('view')` check enforces record
view permission on a retrieved bean.

Fourth, we check every source field before reading values. A user who cannot
read billing country must not learn about its contents through `handoff.ready`
or `missing_fields`.

This example rejects the entire summary if any source field is restricted,
including the optional website. That keeps one fixed success contract. Another
design could omit restricted fields, but then it must define partial responses
and protect derived information too. Decide that policy deliberately.

## Install it without hunting for dependencies

The [topic guide](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/blob/main/rest-api/README.md)
links to an installable ZIP. Upload that topic ZIP through Admin → Module Loader,
then run Quick Repair and Rebuild. The ZIP contains the endpoint and a small
Platforms extension registering `sdg_examples` for the companion client.

For on-site development, you can instead copy those two source files into their
matching paths. Choose one installation method. Don’t deploy the article, client,
or test files into Sugar’s public application directory.

Open your instance’s `/rest/v11_27/help` page and look for the SDG summary route.
If it is missing, use Rebuild API Metadata in Admin → Repair where available.
The guide includes removal instructions as well; this example creates no fields
or business records.

## Make the first request

Clone or download the companion repository. From its root, run:

```sh
python3 rest-api/examples/account_summary.py \
  --base https://YOUR-SUGAR/rest/v11_27 \
  --record YOUR-ACCOUNT-GUID \
  --username YOUR-TEST-USER
```

Replace the URL, record ID, and username with your sandbox values. The client
requires Python 3 and no additional libraries. It prompts for your password,
requests an OAuth token using the registered `sdg_examples` platform, then calls
the endpoint with Sugar’s `OAuth-Token` header. It requires HTTPS, refuses
redirects, and prints the record response rather than the token response.

If your organization uses SugarIdentity/SSO or does not allow that password flow,
obtain a token through your supported authentication flow and omit `--username`.
The client will prompt for an existing access token instead.

Add `--standard` to the same command to retrieve the source fields through
Sugar’s existing Accounts API. That comparison is the point: the standard API
gets the data; our endpoint applies the tutorial’s response contract and handoff
rule.

Use a dedicated test user. A separate registered platform helps avoid conflicts
with the normal browser client, but repeated logins with the same user and
platform may still invalidate an earlier session.

## Test the unhappy paths, too

A successful request as an administrator is the beginning of testing.

Try a normal user who can view the Account and all five fields. Then try a user
who cannot view that record, an Account outside the user’s teams, and a role that
denies one of the source fields. None of the denied cases should return account
values or a readiness result.

Blank a required field on a disposable sandbox Account. The response should
remain successful, with `ready` false and that Sugar field name in
`missing_fields`. Blank only website and readiness should stay true if the
required fields are present.

The handler uses Sugar’s stock exceptions: invalid GUID maps to
`SugarApiExceptionInvalidParameter` (422), missing record to
`SugarApiExceptionNotFound` (404), and denied access to
`SugarApiExceptionNotAuthorized` (403). Team-restricted retrieval may produce 404
before the view check. Missing or expired authentication is rejected by Sugar,
not handled by our method. Use an HTTP client to exercise malformed IDs and
missing tokens; the Python client validates IDs locally and always asks for
authentication.

The repository’s isolated tests cover our validation, permission checks, response
shape, and presence rule. They also inspect the client’s requests without making
network calls. They do not exercise a real Sugar installation’s OAuth, routing,
team security, or Module Loader. Live sandbox verification is still required;
this example has not yet been run against a live Sugar instance.

## Keep the example close to the article

Every executable example in this installment is in the
[Month 5 folder](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/tree/main/rest-api).
Start at its README for setup and direct file links. The repository-wide
[examples catalog](https://github.com/sugardevelopersguide/sugar-developers-guide-examples/blob/main/EXAMPLES.md)
helps you find earlier topics and identifies entries that still need code.

What response have you needed that Sugar’s standard API didn’t give you? Share
the input and the JSON shape you wanted, using fictional data. That would make a
useful next example.

Next in the series: SugarQuery—querying Sugar data without turning every
integration into a collection of SQL strings.

## Further reading

- [Sugar 26.1 REST API and authentication](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_26.1/integration/web_services/rest_api/)
- [Extending endpoints](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_26.1/integration/web_services/rest_api/extending_endpoints/)
- [Stock API exceptions](https://support.sugarai.com/documentation/sugar_developer/sugar_developer_guide_25.1/integration/web_services/rest_api/api_exceptions/)
