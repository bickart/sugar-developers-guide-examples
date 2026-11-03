# Extension Framework

Copy-ready examples from [The Extension Framework Explained](https://sugardevelopersguide.substack.com/p/the-extension-framework-explained).
Run a Quick Repair and Rebuild after deploying files beneath `custom/Extension/`.
Never edit Sugar's compiled `*.ext.php` files.

## Examples

- `custom/Extension/modules/Contacts/Ext/Vardefs/audit_core_field.php` audits the
  core Contacts `phone_office` field.
- `custom/Extension/modules/SchedulersJobs/Ext/LogicHooks/job_failure.php`
  registers a `job_failure` logic hook.
- `custom/modules/SchedulersJobs/LogicHooks/SchedulersJobsLogicHooks.php`
  supplies the autoloaded hook class. Its example implementation logs a final
  failure; replace it with your notification delivery logic.
- `custom/Extension/application/Ext/Include/amaiza_Substack.php` shows module
  registration. It runs only if the module class already exists. This repository
  does not include a complete `amaiza_Substack` module.
- `custom/Extension/modules/Administration/Ext/Administration/amaiza_Substack.php`
  adds an Administration section and external link.
- `custom/Extension/modules/Administration/Ext/Language/en_us.amaiza_Substack.php`
  defines the labels used by the Administration example.
- `package/contact-priority/` is a Module Loadable Package that adds the
  `custom_priority_c` Contact field, its label, and its dropdown options.
- `examples/incorrect-custom-field-vardef.php` is the intentionally invalid
  plain-vardef approach from the article. Do not deploy it.

The hook runs when a job fails for the final time. Its method accepts the job
bean, event name, and arguments array. Despite its method name, this example
only logs a warning; it does not send email. Use `job_failure_retry` to handle
failures that will be retried.

The dropdown definition includes a blank choice that matches the field's empty
default. Its other choices are Low, Normal, and High.
The package uses `required => false` so Sugar 26.1 generates a boolean field
requirement. The older `require_option => 'optional'` form produces a string in
the generated definition and must not be used here.

The Administration link opens the Substack website. It does not publish content,
provide a Sugar integration, or sign the user in. The example uses the documented
four-position link array. Check the icon and link appearance in your target Sugar
release.

After deployment, run Quick Repair and Rebuild. Review any generated database
changes before applying them. A repair does not register a missing custom field
in Studio; use Studio or the package's `custom_fields` definition.

## Build a Module Loadable Package

Build an installable archive from this example's `custom/` sources and Contact
Priority package metadata:

```sh
php bin/pack.php extension-framework 26.7.1
```

The archive is written to `extension-framework/builds/`. Its package version is
the supplied argument and it accepts Sugar 26.x Enterprise, Professional, and
Ultimate installations.

Version 26.7.1 includes the article corrections. The older 26.7.0 archive is
retained for reference. Package compatibility declarations are not a record of
testing on every accepted Sugar release. Validate the package on a development
instance before using it elsewhere.

## Check against Sugar 26.1 source

Run this read-only check with a licensed Sugar 26.1 source tree:

```sh
php extension-framework/verify-sugar-source.php /path/to/Sugar
```

The check uses Sugar's real enum-field conversion, Administration metadata parser,
and logic-hook scanner. It does not connect to a database or change the Sugar
source tree. Theme lookup and log output use test replacements. The hook class is
loaded directly, so this does not test Sugar's class autoloader.

These checks passed against Sugar Enterprise 26.1.0 with PHP 8.4.26 on 2026-11-02.
A configured test instance is still required to verify Module Loader installation,
Quick Repair and Rebuild, Studio, the Administration page, and a final failed job.
