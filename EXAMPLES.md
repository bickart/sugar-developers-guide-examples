# Find an example

Choose a task below. Open its guide. Then open the code or download its ZIP file.
Files in `custom/` use the same folder paths as Sugar. A ZIP of this whole
repository cannot be installed through Module Loader.

| Task                                 | Guide                                                | Available material                                                                                                                                              |
| ------------------------------------ | ---------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Customize layouts and views          | [Layouts and Views](layouts-and-views/README.md)     | Accounts UI and JobQueue snippets; [topic ZIP](layouts-and-views/builds/sugardevelopersguidelayoutsandviewsexamples_26.7.0.zip)                                 |
| Use extension points                 | [Extension Framework](extension-framework/README.md) | Extension and custom-field snippets; [topic ZIP](extension-framework/builds/sugardevelopersguideextensionframeworkexamples_26.7.0.zip)                          |
| Define and override fields           | [Vardefs](vardefs/README.md)                         | Vardef and language snippets; [topic ZIP](vardefs/builds/sugardevelopersguidevardefsexamples_26.7.0.zip)                                                        |
| Respond to saves and other events    | [Logic Hooks](logic-hooks/README.md)                 | Topic placeholder; no implementation files yet                                                                                                                  |
| Return a tailored API response       | [Accounts RestAPI Example](rest-api/README.md)       | Endpoint, readiness service, PHP client, article, unit and integration tests; [topic ZIP](rest-api/builds/sugardevelopersguideaccountsrestapiexample_1.0.1.zip) |
| Run background work                  | [Scheduler Jobs](scheduler-jobs/README.md)           | Topic placeholder; no implementation files yet                                                                                                                  |
| Configure a development installation | [Installation](installation/README.md)               | Silent-install configuration example                                                                                                                            |

Older examples are short pieces of code. Some need extra modules or setup.
These links do not mean the older examples have been tested again.
The Accounts RestAPI Example needs only the built-in Accounts module and its fields.

## Accounts RestAPI Example file map

| Need                                                 | Open                                                                                                   |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| Installation, request, troubleshooting, verification | [Start here](rest-api/README.md)                                                                       |
| Full article                                         | [When Sugar's REST API Doesn't Return What You Need](rest-api/article.md)                              |
| Route and handler                                    | [SdgAccountSummaryApi.php](rest-api/custom/modules/Accounts/clients/base/api/SdgAccountSummaryApi.php) |
| Field definitions and readiness rule                 | [AccountReadinessService.php](rest-api/custom/modules/Accounts/Services/AccountReadinessService.php)   |
| Dedicated OAuth platform                             | [sdg_examples.php](rest-api/custom/Extension/application/Ext/Platforms/sdg_examples.php)               |
| Stock API / custom API comparison client             | [account_summary.php](rest-api/examples/account_summary.php)                                           |
| Installable Module Loader package                    | [Download ZIP](rest-api/builds/sugardevelopersguideaccountsrestapiexample_1.0.1.zip)                   |
| Offline endpoint checks                              | [run.php](rest-api/tests/run.php)                                                                      |
| Service unit tests                                   | [AccountReadinessServiceTest.php](rest-api/tests/unit/AccountReadinessServiceTest.php)                 |
| Live integration tests                               | [AccountSummaryEndpointTest.php](rest-api/tests/integration/AccountSummaryEndpointTest.php)            |
| Offline HTTP client checks                           | [client.php](rest-api/tests/client.php)                                                                |

## How to use the library

1. Read the guide before you copy files or install a ZIP.
2. Use a test copy of Sugar. Give the test user the same roles as your app's user.
3. Copy only the files listed in the guide. Keep client programs and tests outside Sugar.
4. Follow the test steps in the guide. Local tests do not prove that a real Sugar
   system will handle login, API addresses, teams, or field access as expected.

For each new article, put its code in one topic folder. Link to that folder here.
State what the reader needs and where each file goes. Show a request and response.
Explain how to install, test, and remove the example. State which tests have run.
Add a link to the article once it is published. Mark topics that still need code.
