# TalaKlase System Architecture

Version: RC3.5
Status: Active
Owner: KaijuSoft
Lead Developer: Julius Frederick C. Vendivil

---

# Project Overview

TalaKlase is a college-oriented Student Information System (SIS) designed to be modular, maintainable, and offline-first.

The project began as an attendance system but has evolved into a full academic records platform supporting enrollment, attendance, grading, reporting, and data synchronization.

---

# Core Design Principles

1. Offline First

The system must continue operating without Internet connectivity.

Primary database:
- Local MariaDB (XAMPP)

Secondary database:
- Remote MySQL (backup/synchronization)

Synchronization is performed by administrators using the TALA Engine.

Production updates are handled separately through GitHub Releases.

---

2. Separation of Concerns

The project follows strict layer separation.

Presentation Layer

↓

Application/API Layer

↓

Business Logic

↓

Database

No layer should bypass another.

---

# Architecture

                Browser
                    │
                    ▼
            pages/*.php
                    │
                    ▼
         assets/js/*.js
                    │
                    ▼
            sync_api.php
                    │
                    ▼
          TALA Engine RC3
                    │
                    ▼
          Local / Remote Database

---

# Folder Responsibilities

assets/

Contains frontend resources.

- JavaScript
- CSS
- Images

No PHP logic.

---

pages/

Contains presentation pages.

Responsibilities:

- HTML
- Forms
- Bootstrap components

Should contain minimal JavaScript.

Business logic is prohibited.

---

assets/js/

Contains all frontend logic.

Examples:

- sync.js
- attendance.js
- students.js

Rules:

One module per file.

---

includes/

Contains reusable backend components.

Authentication

Database

Utilities

Libraries

---

includes/TALA/

Synchronization Engine.

Status:

RC1 Frozen

Do not modify unless fixing:

- Bugs
- Crashes
- Security
- Regressions

---

# TALA Engine

The synchronization engine is completely independent from the UI.

Engine

↓

SyncSession

↓

sync_api.php

↓

Frontend

The engine never communicates directly with HTML or JavaScript.

TALA Engine Rule #001

Every synchronized Master or Transaction table must define a business_key. 
Synchronization decisions must be based on the business identity of a record rather than its auto-increment primary key.

---

# Synchronization Flow

User clicks Smart Merge

↓

Frontend

↓

sync_api.php

↓

TalaEngine

↓

Progress Callback

↓

Server Sent Events

↓

sync.js

↓

UI Update

---

# Release Manager

Production installations use GitHub Releases instead of Git authentication.

Workflow:

1. Check installed version
2. Read the release manifest
3. Compare versions
4. Download the release package
5. Verify the package
6. Apply the update

Developer mode may continue to use Git-based update checks.

---

# Project Philosophy

- Preserve backward compatibility where practical
- Keep engine logic separate from UI and transport code
- Prefer explicit configuration over hard-coded secrets
- Keep TalaKlase offline-first and maintainable

---

# Frontend Architecture

Every JavaScript module follows:

UI Cache

↓

Initialization

↓

API Calls

↓

Event Dispatcher

↓

Event Handlers

↓

DOM Updates

Avoid duplicate DOM queries.

Avoid inline JavaScript.

---

# Database Philosophy

The database is the source of truth.

Business keys are preferred over auto-increment IDs during synchronization.

Relationships must remain intact.

---

# Long-Term Academic Model

Future development centers around Student Subject Enrollment.

Student

↓

Enrollment

↓

Teaching Assignment

↓

Attendance

↓

Grades

Attendance and grading should be driven by enrollment records rather than only section membership.

---

# Coding Standards

One responsibility per function.

One responsibility per file.

Prefer composition over duplication.

Read the entire file before modifying it.

Preserve backward compatibility whenever possible.

---

# Testing Strategy

Every code change must pass:

PHP Syntax

↓

JavaScript Syntax

↓

Playwright Smoke Tests

↓

Module Tests

↓

Manual Validation (if required)

---

# RC1 Frozen Components

Do not modify unless absolutely necessary.

includes/TALA/bootstrap.php

includes/TALA/src/TalaEngine.php

includes/TALA/src/SyncSession.php

sync_api.php

---

# Development Workflow

Read:

AGENTS.md

↓

TESTING.md

↓

ARCHITECTURE.md

↓

Modify Code

↓

Run Tests

↓

Commit

---

# Future Modules

Dashboard

Students

Departments

Courses

Subjects

Sections

Teaching Loads

Student Enrollment

Attendance

Grading

Reports

Synchronization

OJT Portal Integration

---

# Vision

TalaKlase is intended to become a complete academic management platform capable of supporting multiple colleges while remaining lightweight, maintainable, and offline-first.

Every architectural decision should move the project toward that vision.

# Architectural Decision Record (ADR)

## ADR-001

Decision:
Offline-first architecture.

Reason:
Schools may experience unreliable Internet connectivity.

Status:
Accepted.

---

## ADR-002

Decision:
TALA Engine is independent from the frontend.

Reason:
Allows reuse by future KaijuSoft applications.

Status:
Accepted.

---

## ADR-003

Decision:
JavaScript modules are stored in assets/js instead of inline PHP.

Reason:
Improves maintainability and testability.

Status:
Accepted.

---

## ADR-004

Decision:
Attendance and grading will eventually be driven by Student Subject Enrollment instead of section membership.

Reason:
Supports irregular students, cross-enrollees, and future curriculum flexibility.

Status:
Planned.

---

## ADR-005

Decision:
Business keys are used during synchronization instead of relying solely on auto-increment IDs.

Reason:
Ensures consistent synchronization between independent databases.

Status:
Accepted.


---

# RC3.5 Application Architecture Extensions

The RC3.5 line extends the original academic-management architecture with operational safety and administrative lifecycle controls.

## User Lifecycle

User administration now includes:

- Instructor account creation and editing
- Instructor-admin permissions
- Account archiving
- Lifecycle impact analysis
- Instructor-scoped section ownership
- Instructor-scoped student and attendance visibility

The ownership relationship is represented by `section.inst_id`.

## Integrity Layer

Database integrity is now treated as a first-class application concern.

Current integrity components include:

- `IntegrityChecker`
- `ReferenceInspector`
- `DuplicateDetector`
- `IntegrityReport`

The integrity workflow is intended to identify duplicate records, broken or unexpected references, and operational impact before corrective actions are taken.

## Backup and Recovery

Backup selection has been made deterministic for integrity and recovery workflows.

Application release installation also creates a timestamped backup before replacing application files.

## Synchronization Dashboard

The synchronization dashboard remains a presentation layer over `sync_api.php` and the TALA Engine. UI improvements must not move synchronization business logic into the frontend.

## Release Manager

Production application updates are release-based rather than Git-branch-based:

```text
GitHub Release
    ↓
Manifest
    ↓
Version Comparison
    ↓
Download
    ↓
Backup
    ↓
Validation
    ↓
Installation
    ↓
Verification
```

These RC3.5 extensions preserve the project's core separation-of-concerns and offline-first principles.
