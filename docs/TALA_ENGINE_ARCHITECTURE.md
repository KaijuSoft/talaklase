# TALA Engine Architecture

**Version:** 1.0  
**Status:** Architecture Complete  
**Project:** TalaKlase  
**Framework:** TALA Engine  
**Repository:** KaijuSoft/TalaKlase  
**License:** GPL-3.0
**Repository:**
https://github.com/KaijuSoft/TalaKlase
---

## License

TALA Engine is licensed under the GNU General Public License v3.0 (GPL-3.0).

You are free to use, study, modify, and redistribute this software under the terms of the GPL-3.0 license.

Any distributed derivative works must also remain licensed under GPL-3.0.

See the LICENSE file in the project root for the complete license text.

### Maintained By

**Julius Frederick C. Vendivil**  
Founder & Owner, KaijuSoftware

### Architecture Companion

**Atlas (OpenAI ChatGPT)**

This document was developed collaboratively between the project author and Atlas to capture the design philosophy,
architectural principles, and long-term vision of TALA Engine.
While AI assisted in organizing and documenting the architecture,
all product direction, engineering decisions,
and system vision remain those of the project author.

# 1. Introduction

TALA Engine is the synchronization framework that powers TalaKlase.

Its primary purpose is to safely inspect, analyze, compare, validate, plan, and synchronize databases while preserving data integrity and minimizing destructive operations.

Unlike traditional synchronization systems that immediately execute database changes, TALA Engine follows an **Analyze → Plan → Validate → Execute** philosophy.

Every operation should be understood before it is executed.

TALA Engine was designed specifically for TalaKlase but follows a modular architecture that allows future expansion into a standalone synchronization framework.

---

# 2. Philosophy

TALA Engine follows several core principles.

## 2.1 Read Before Write

Every synchronization operation begins by reading the current state of both databases.

No write operation should occur without understanding the existing state.

---

## 2.2 Plan Before Execute

Synchronization should never be based on assumptions.

The engine first produces a synchronization plan before any execution occurs.

---

## 2.3 Preserve Data

Protecting existing data always has higher priority than forcing synchronization.

When conflicts occur, they should be detected and reported rather than silently overwritten.

---

## 2.4 Small Incremental Changes

The engine evolves through small architectural improvements.

Large rewrites are discouraged.

Backward compatibility is preferred whenever possible.

---

## 2.5 Single Responsibility

Every component should have one clear responsibility.

Inspectors inspect.

Validators validate.

Executors execute.

Resolvers resolve.

The engine orchestrates.

---

# 3. High-Level Architecture

```
                    TALA Engine

                  Health Check
                       │
                       ▼
              Database Snapshot
                       │
                       ▼
        ┌─────────────────────────┐
        │                         │
        ▼                         ▼

 Schema Pipeline            Data Pipeline

        │                         │
 SchemaInspector           DataSnapshot
        │                         │
 SchemaMerger              DataInspector
        │                         │
 MergeValidator       ConflictResolver
        │                         │
ExecutionPlanBuilder      DataMerger
        │                         │
 SchemaExecutor          DataExecutor

              │
              ▼

         Engine Session

              │
              ▼

           API Layer

              │
              ▼

     Synchronization Dashboard
```

---

# 4. Core Components

## TalaEngine

The central orchestrator.

Responsibilities:

- Coordinate engine workflow
- Invoke pipeline components
- Maintain execution order
- Preserve synchronization behavior

The engine itself should not contain specialized business logic.

---

## EngineSession

Stores information about an engine execution.

Tracks:

- session id
- timestamps
- snapshots
- inspection
- merge plan
- execution
- verification
- duration

---

## DatabaseSnapshot

Captures schema information.

Includes:

- tables
- columns
- indexes
- primary keys
- row counts

Read-only.

---

## DataSnapshot

Captures data state for synchronization.

Read-only.

---

# 5. Schema Pipeline

The Schema Pipeline analyzes database structures.

It is responsible for schema synchronization only.

---

## SchemaInspector

Responsibilities

- Compare schemas
- Detect missing tables
- Detect missing columns
- Detect datatype differences
- Detect nullable differences
- Detect default differences
- Detect missing indexes

Produces:

Schema Analysis

---

## SchemaMerger

Transforms schema differences into merge operations.

Produces:

Schema Merge Plan

No SQL execution occurs here.

---

## MergeValidator

Validates merge plans.

Detects:

- duplicate operations
- malformed operations
- invalid actions
- redundant changes

Produces:

Validation Report

---

## ExecutionPlanBuilder

Builds the ordered execution queue.

Responsibilities:

- determine execution order
- normalize dependencies
- prepare execution metadata

Produces:

Execution Plan

---

## SchemaExecutor

Executes schema operations.

Supports:

- Dry Run
- Live Execution

Responsible only for execution.

---

# 6. Data Pipeline

The Data Pipeline synchronizes records.

---

## DataSnapshot

Captures source and destination data.

Read-only.

---

## DataInspector

Compares records.

Detects:

- insert
- update
- delete
- unchanged

Produces:

Data Analysis

---

## ConflictResolver

Central location for conflict analysis.

Responsibilities:

- compare rows
- detect changed fields
- normalize conflicts
- determine conflict strategy

Default strategy:

manual_review

ConflictResolver never executes SQL.

---

## DataMerger

Transforms data analysis into merge operations.

Produces:

Data Merge Plan

No SQL execution occurs here.

---

## DataExecutor

Executes data merge operations.

Supports:

- Dry Run
- Live Execution

Responsible only for execution.

---

# 7. Synchronization Workflow

Complete workflow:

Health Check

↓

Database Snapshot

↓

Schema Inspection

↓

Schema Merge

↓

Merge Validation

↓

Execution Plan

↓

Schema Execution

↓

Data Snapshot

↓

Data Inspection

↓

Conflict Resolution

↓

Data Merge

↓

Data Execution

↓

Verification

↓

Engine Session

---

# 8. Engine API

The Engine API acts as the transport layer between the engine and external interfaces.

Responsibilities:

- expose engine functionality
- format responses
- preserve API contracts

The API should not contain business logic.

---

Current Endpoints

- status.php
- analyze.php
- plan.php
- execute.php

---

# 9. Dashboard

The Synchronization Dashboard provides a visual interface.

Responsibilities:

- display engine status
- display plans
- execute synchronization
- display reports

The dashboard should not contain synchronization logic.

---

# 10. Coding Standards

When modifying TALA Engine:

- Review existing code first.
- Preserve backward compatibility.
- Do not rewrite TalaEngine.
- Extend existing architecture.
- Use PHP 8.2 compatible code.
- Produce unit-testable methods.
- Keep classes focused on one responsibility.
- Prefer composition over duplication.
- Validate before executing.
- Document public methods.

---

# 11. Extension Guidelines

Before creating a new component ask:

Can an existing component own this responsibility?

If yes:

Extend the existing component.

If no:

Create a new component.

Avoid duplicate responsibilities.

---

# 12. Architectural Rules

Inspectors

- Never execute SQL.

Validators

- Never modify plans.

Executors

- Never decide conflicts.

Resolvers

- Never execute SQL.

Snapshots

- Never modify databases.

The Engine

- Orchestrates.
- Does not specialize.

---

# 13. Roadmap

## Completed

✔ Health Check

✔ Database Snapshot

✔ Data Snapshot

✔ Schema Inspection

✔ Schema Merge

✔ Merge Validation

✔ Execution Plan Builder

✔ Schema Execution

✔ Data Inspection

✔ Conflict Resolution

✔ Data Merge

✔ Data Execution

✔ Engine API

✔ Synchronization Dashboard

---

## Future Enhancements

- Conflict resolution strategies
    - Source Wins
    - Destination Wins
    - Timestamp Wins
    - Manual Review

- Rollback Engine

- Synchronization Journal

- Background Synchronization

- Distributed Synchronization

- Plugin Architecture

---

# 14. Vision

TALA Engine exists to provide a safe, predictable, and extensible synchronization framework.

Its purpose is not simply to copy data between databases.

Its purpose is to understand differences, protect data integrity, and execute synchronization with confidence.

Every component has a single responsibility.

Every synchronization begins with understanding.

Every execution should be explainable.

Architecture is always more important than shortcuts.

---

# 15. Closing Statement

This document is the authoritative architectural reference for TALA Engine.

All contributors—human or AI—should read this document before modifying the engine.

Architectural consistency takes precedence over feature implementation.

The engine should evolve through deliberate, incremental improvements while preserving the philosophy on which it was built.

---

"Analyze before Execute.
Understand before Synchronize.
Architecture before Features."

This document represents the architectural principles upon which TALA Engine is built.
Future enhancements should preserve these principles while allowing the engine to evolve through thoughtful, 
incremental improvements.

— Julius Frederick C. Vendivil
Creator of TalaKlase & TALA Engine
Founder & Owner, KaijuSoftware

## Acknowledgements

TALA Engine was developed through a collaborative engineering workflow using AI-assisted development tools.

AI assisted with architecture reviews, implementation planning, documentation, code generation, debugging, and testing under the direction of the project creator.

All product vision, architectural decisions, and final implementation approval remain the responsibility of the project creator.

"Good software is not measured by the amount of code it contains,
but by the problems it quietly solves."
