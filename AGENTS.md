# Contributor Instructions

## Language for guide code

Use PHP for Sugar Developers Guide code examples, client programs, tests, and
supporting scripts. New standalone examples must support PHP 8.2 or later unless
the target Sugar version requires a higher version. Do not use Python for this
project unless the user explicitly approves it first. Keep existing Sugar browser
code in its required language; this does not authorize Python.

## Reader-facing writing

Write articles, guides, and the examples index in plain language at a ninth-grade
reading level or below. Readers may live outside the United States and may read
English as a second language. Use short, direct sentences. Avoid idioms and
unneeded jargon. Spell out unfamiliar acronyms and explain needed technical terms
when they first appear. Use neutral examples and clear international formats.
Keep code identifiers exact; explain their meaning in the surrounding prose.

## Copyright and license

Original guide examples and supporting scripts use the repository's MIT license.
New source files should include a short copyright line for Sugar Developers Guide
and `SPDX-License-Identifier: MIT`, adapted to the language's comment syntax.
Do not add proprietary, confidential, or copying-prohibited notices to public
tutorial code. Preserve existing third-party copyright and license notices.

## Required validation for code changes

Before completing any change that adds or modifies PHP, JavaScript, JSON, or
Markdown files, run both formatters from the repository root:

```sh
composer format
npm run format
```

Then verify the result:

```sh
composer format:check
npm run format:check
npm run lint
```

Do not leave formatting changes unreviewed. If a formatter modifies a file,
include those modifications in the requested change.
