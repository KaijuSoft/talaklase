# TalaKlase AI Workflow

## ChatGPT

Responsible for:

- Architecture
- Code Review
- QA Decisions
- Database Design
- Release Planning

## Claude

Responsible for:

- Implementation
- Refactoring
- Playwright Test Generation
- Documentation Updates

## Rules

- Read AGENTS.md before coding.
- Read TESTING.md before testing.
- Read ARCHITECTURE.md before refactoring.
- Never modify application code while writing regression tests.
- Never claim success without executing available tests.
- If a regression is found, stop and report it instead of making unrelated fixes.

## Documentation and Security Update Rule

When a refactor changes architecture, security boundaries, or operational behavior, update the affected project documentation in the same change set.

At minimum review:

- `README.md`
- `CHANGES.md`
- `TESTING.md`
- `SECURITY.md`
- `ARCHITECTURE.md`
- `docs/CHANGELOG.md`
- `docs/PROJECT_STATUS.md`
- `docs/REGRESSION_MATRIX.md`
- `docs/TalaKlase_Engineering_Standards.md`

Documentation must distinguish static validation from authenticated functional testing and must never claim production destructive workflows were executed when they were not.