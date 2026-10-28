# Contributor Instructions

## Reader-facing writing

Write articles, guides, and the examples index in plain language at a ninth-grade
reading level or below. Readers may live outside the United States and may read
English as a second language. Use short, direct sentences. Avoid idioms and
unneeded jargon. Spell out unfamiliar acronyms and explain needed technical terms
when they first appear. Use neutral examples and clear international formats.
Keep code identifiers exact; explain their meaning in the surrounding prose.

## Copyright notice

Every newly created source file must begin with the following copyright notice,
adapted to the language's comment syntax:

```text
Copyright (C) Amaiza LLC. - All Rights Reserved

This source code is proprietary and confidential and protected under
international copyright law. All rights reserved and protected by
the copyright holders. This file is only available to authorized individuals
with the permission of the copyright holders. Unauthorized copying of this file,
via any medium is strictly prohibited. If you encounter this file and do not have
permission, please contact the copyright holders and delete this file.
```

Do not change existing files solely to add this notice unless explicitly asked.

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
