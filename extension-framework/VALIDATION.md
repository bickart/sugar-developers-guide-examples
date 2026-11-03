# Extension Framework validation

Date: 2026-11-02. Installed package: 26.7.1. Corrected package: 26.7.2.

## Passed

- Read-only source checks against Sugar Enterprise 26.1.0 with PHP 8.4.26.
- Sugar's enum-field conversion produces `required => false`, disables mass
  update, and keeps the custom-field source and dropdown key.
- The dropdown includes the empty default choice.
- Sugar's Administration parser keeps the external URL and resolves all four
  labels. The source test supplies a missing-theme result to check the fallback
  icon; it does not render a browser icon.
- Sugar's logic-hook scanner finds the registration. The directly loaded class
  writes the expected warning when called with a synthetic job.
- Composer and npm formatting checks, ESLint, and PHP syntax checks.
- The user-provided QA instance reports Sugar Enterprise 26.1.0, build 319, on
  its About page after administrator sign-in.
- Module Loader installed package 26.7.1. The installed-package row shows
  Uninstall and Disable controls, confirming that the package is enabled.
- The Administration section displays its heading, description, and Open
  Substack link. Activating the link opens `https://substack.com/`.
- Contacts Studio lists `custom_priority_c` as Support Priority, type DropDown.
  Required Field and Mass Update are both unchecked. Its dropdown list is
  `contact_priority_list`, with a blank default and blank, Low, Normal, and High
  choices.
- An isolated inactive scheduler using a reserved `.invalid` URL was launched
  once through the QA job launcher. It reached failure, and the Sugar log
  contained the hook's exact final-failure message. This confirms that the
  installed hook class loaded and ran.

## Logger correction found during live testing

The 26.7.1 hook called `warning()` on Sugar's legacy logger. This is not a legacy
logger level, so Sugar used its configured level instead; QA recorded the test
message as Fatal. Version 26.7.2 changes the call to `warn()`.

The source check now uses Sugar's real legacy logger dispatch with an isolated
output sink. It failed with `warning()` and passed with `warn()`. QA's logger is
currently set to Fatal, which filters out a correct Warn message. Live validation
of 26.7.2 requires installing the correction and temporarily using a Warn
threshold, then restoring Fatal.

## Live tests still pending

The initial empty embedded pages were a loading delay, not a confirmed Module
Loader or Studio failure. Module Loader later loaded and installation completed.

Quick Repair and Rebuild reported Token Expired and returned to the login form
at 81%. The same result occurred after signing in again and making one further
repair attempt. No more repair attempts were made. Its completion and database
comparison results have not been confirmed. No generated SQL was manually
executed. The checks below remain open; do not repeat repair solely to rerun
the field and logging checks:

1. Inspect the Quick Repair and Rebuild result and any proposed database changes.
2. Install 26.7.2 and confirm a final failed job writes at Warn severity, with
   an appropriate log threshold. Restore the original threshold afterward.
3. Confirm the guarded module fragment does not register a missing module.

The test scheduler remains inactive. No existing scheduler was launched or edited.

The source checks do not replace these live tests. The pull request remains a
draft until the live-test blocker is resolved.
