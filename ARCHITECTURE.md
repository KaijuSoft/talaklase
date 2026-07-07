# TalaKlase System Architecture

Version: RC1
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

Synchronization is performed manually by administrators using the TALA Engine.

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
          TALA Engine RC1
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