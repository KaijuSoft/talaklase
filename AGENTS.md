# TalaKlase Development Rules

## Required Reading

Before making any changes:

1. Read AGENTS.md
2. Read TESTING.md
3. Read the entire file(s) to be modified

## Read First

Always read the ENTIRE file before making changes.

Never modify code without understanding the whole function.

Review related files before refactoring.

## RC1 Freeze

Do not modify these files unless fixing bugs.

includes/TALA/bootstrap.php

includes/TALA/src/TalaEngine.php

includes/TALA/src/SyncSession.php

sync_api.php

## Frontend

Move JavaScript into assets/js.

Avoid inline JavaScript.

Reuse components.

Do not duplicate networking code.

## Architecture

Engine

↓

API

↓

Frontend

Never bypass sync_api.php.

## Coding

Prefer readable code.

One responsibility per function.

One file one responsibility.

Avoid duplicate logic.

## Testing

After every change verify

- Smart Merge
- Push Local→Online
- Push Online→Local

Do not ship untested code.

## Verification

Never claim a feature works unless it has been executed.

Syntax checks are not functional tests.

If database operations cannot be executed safely, state that clearly.

## Regression Policy

Before implementing new features:

1. Run existing regression tests.
2. Make the requested change.
3. Run relevant regression tests again.
4. Report any new failures.

Do not ignore or disable failing tests.

Treat every regression as a release blocker until resolved.