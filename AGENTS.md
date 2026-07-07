# TalaKlase Development Rules

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