# Sugar Developers Guide Examples

Official companion code for the [Sugar Developers Guide](https://sugardevelopersguide.substack.com/).
Start with the [examples catalog](EXAMPLES.md) to find code, setup instructions,
article links, and available downloads. Some topics are placeholders; the catalog
identifies them explicitly.

## Latest: Month 5 — Custom REST APIs

When Sugar's standard API does not return the output your integration needs,
build a small endpoint with an explicit response contract.

**[Start the account-summary tutorial](rest-api/README.md)** ·
[View the endpoint](rest-api/custom/clients/base/api/SdgAccountSummaryApi.php) ·
[Download the installable ZIP](rest-api/builds/sugardevelopersguiderestapiexamples_1.0.0.zip)

## Topics

- [Extension Framework](extension-framework/README.md)
- [Vardefs](vardefs/README.md)
- [Layouts and Views](layouts-and-views/README.md)
- [Logic Hooks](logic-hooks/README.md)
- [Custom REST APIs — account summary](rest-api/README.md)
- [Scheduler Jobs](scheduler-jobs/README.md)
- [Installation](installation/README.md)

## Development tools

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
