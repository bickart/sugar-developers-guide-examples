# Sugar Developers Guide Examples

Official companion code for the [Sugar Developers Guide](https://sugardevelopersguide.substack.com/).
Start with the [examples catalog](EXAMPLES.md) to find code, setup instructions,
article links, and available downloads. Some topics are placeholders; the catalog
identifies them explicitly.

## Accounts RestAPI Example

When Sugar's standard API does not return the output your integration needs,
build a small endpoint with an explicit response contract.

The endpoint uses a separate PHP service for its readiness rule. The example
includes service unit tests and live integration tests for a Sugar sandbox.

**[Open the Accounts RestAPI Example](rest-api/README.md)** ·
[View the endpoint](rest-api/custom/modules/Accounts/clients/base/api/SdgAccountSummaryApi.php) ·
[Download the installable ZIP](rest-api/builds/sugardevelopersguideaccountsrestapiexample_1.0.1.zip)

## Topics

- [Extension Framework](extension-framework/README.md)
- [Vardefs](vardefs/README.md)
- [Layouts and Views](layouts-and-views/README.md)
- [Logic Hooks](logic-hooks/README.md)
- [Accounts RestAPI Example](rest-api/README.md)
- [Scheduler Jobs](scheduler-jobs/README.md)
- [Installation](installation/README.md)

## Development tools

Guide examples and supporting scripts use PHP. The standalone REST client works
with PHP 8.2 or later and cURL. The development tools below require PHP 8.4.

```sh
composer install
npm install
```

- `composer format` formats PHP with PHP CS Fixer.
- `composer format:check` checks PHP formatting without changing files.
- `npm run format` formats JavaScript, JSON, and Markdown with Prettier.
- `npm run lint` lints JavaScript with ESLint.

## Sugar compatibility

Tested with Sugar Enterprise 26.1.0 through source-level compatibility
validation. Validation covers PHP syntax, Extension Framework paths, logic-hook
event registration, and Module Loadable Package manifest compatibility. It does
not replace installing the package and running Quick Repair and Rebuild in your
target Sugar instance.
