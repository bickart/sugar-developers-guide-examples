# Find an example

Pick a task, open its guide, then use the linked source or topic ZIP. Each topic's
`custom/` directory mirrors paths inside the Sugar application. The repository
download is **not** itself a Module Loader package.

| Task / series installment                   | Guide                                                | Available material                                                                                                                                             |
| ------------------------------------------- | ---------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Customize layouts and views — Month 1       | [Layouts and Views](layouts-and-views/README.md)     | Accounts UI and JobQueue snippets; [topic ZIP](layouts-and-views/builds/sugardevelopersguidelayoutsandviewsexamples_26.7.0.zip)                                |
| Use extension points — Month 2              | [Extension Framework](extension-framework/README.md) | Extension and custom-field snippets; [topic ZIP](extension-framework/builds/sugardevelopersguideextensionframeworkexamples_26.7.0.zip)                         |
| Define and override fields — Month 3        | [Vardefs](vardefs/README.md)                         | Vardef and language snippets; [topic ZIP](vardefs/builds/sugardevelopersguidevardefsexamples_26.7.0.zip)                                                       |
| Respond to saves and other events — Month 4 | [Logic Hooks](logic-hooks/README.md)                 | Topic placeholder; no implementation files yet                                                                                                                 |
| Return a tailored API response — Month 5    | [Custom REST APIs](rest-api/README.md)               | Complete original endpoint, platform registration, client, article, isolated tests; [topic ZIP](rest-api/builds/sugardevelopersguiderestapiexamples_1.0.0.zip) |
| Run background work — Month 7               | [Scheduler Jobs](scheduler-jobs/README.md)           | Topic placeholder; no implementation files yet                                                                                                                 |
| Configure a development installation        | [Installation](installation/README.md)               | Silent-install configuration example                                                                                                                           |

The older topic snippets may require their referenced modules or additional setup;
they are not all standalone tutorials. Existing archives are listed for discovery,
not as a new claim of runtime validation. Month 5 uses only stock Accounts fields.

## Month 5 file map

| Need                                                 | Open                                                                                     |
| ---------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| Installation, request, troubleshooting, verification | [Start here](rest-api/README.md)                                                         |
| Full article                                         | [When Sugar's REST API Doesn't Return What You Need](rest-api/article.md)                |
| Route and handler                                    | [SdgAccountSummaryApi.php](rest-api/custom/clients/base/api/SdgAccountSummaryApi.php)    |
| Dedicated OAuth platform                             | [sdg_examples.php](rest-api/custom/Extension/application/Ext/Platforms/sdg_examples.php) |
| Stock API / custom API comparison client             | [account_summary.py](rest-api/examples/account_summary.py)                               |
| Installable Module Loader package                    | [Download ZIP](rest-api/builds/sugardevelopersguiderestapiexamples_1.0.0.zip)            |
| Offline endpoint checks                              | [run.php](rest-api/tests/run.php)                                                        |
| Offline HTTP client checks                           | [test_client.py](rest-api/tests/test_client.py)                                          |

## How to use the library

1. Follow the topic guide before copying files or installing a ZIP.
2. Use a sandbox and a test user whose roles match the integration.
3. Copy only documented deployment files. Keep clients and tests outside Sugar.
4. Follow each topic's verification notes; passing isolated tests does not verify
   the real instance's routing, authentication, teams, or field security.

For future articles, add every source example under one topic and link it here.
Each topic should include prerequisites, full paths, a request and response,
installation/removal instructions, validation status, and a link back to the
published article once its URL exists. Mark incomplete topics rather than
presenting them as working downloads.
