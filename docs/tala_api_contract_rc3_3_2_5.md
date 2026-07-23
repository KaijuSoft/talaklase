# TALA Engine API Contract RC3.3.2.5

This document describes the standardized API envelope used by the TALA Engine
API layer.

## Standard Envelope

All API endpoints return:

```json
{
  "success": true,
  "summary": {},
  "data": {},
  "warnings": [],
  "errors": [],
  "meta": {}
}
```

## Field Semantics

- `success`: Boolean transport status.
- `summary`: Normalized, endpoint-agnostic high-level values.
- `data`: Endpoint-specific payload. Legacy compatibility fields may remain
  here temporarily during RC3.3.2.5.
- `warnings`: Non-fatal warnings.
- `errors`: Validation or execution errors.
- `meta`: Endpoint metadata such as contract version or verification flags.

## Endpoint Payloads

### `status.php`

Normalized summary fields:

- `summary.status`
- `summary.operation_count`
- `summary.duration_ms`

Compatibility data fields:

- `source_connection`
- `destination_connection`
- `engine_status`
- `last_synchronization`
- `pending_operations`
- `duration_ms`

### `analyze.php`

Normalized summary fields:

- `summary.operation_count`
- `summary.duration_ms`
- `summary.status`

Compatibility data fields:

- `summary.operations_found`
- `operations`
- `errors`

### `plan.php`

Normalized summary fields:

- `summary.operation_count`
- `summary.status`

Compatibility data fields:

- `operations`
- `status`

### `execute.php`

Normalized summary fields:

- `summary.execution.executed`
- `summary.execution.skipped`
- `summary.execution.failed`
- `summary.operation_count`
- `summary.duration_ms`

Compatibility data fields:

- `executed`
- `skipped`
- `failed`
- `duration_ms`
- `remaining_operations`
- `operations`

## Workflow

The API is a transport layer over the current TALA Engine pipeline:

Analyze
↓
Merge Plan
↓
Validate
↓
Build Execution Plan
↓
Execute Dry Run

`execute.php` performs a verification analysis after execution so the dashboard
can report remaining operations.
