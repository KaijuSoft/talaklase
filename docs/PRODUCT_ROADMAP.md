# TalaKlase Product Roadmap

**Current Version:** 1.0.3
**Current RC Line:** RC3.5.22
**Owner:** KaijuSoft
**Status:** Active Development

---

# Vision

TalaKlase aims to become a complete college-oriented Student Information System capable of managing the student academic lifecycle while remaining lightweight, maintainable, reliable, and offline-first.

The original attendance-system scope has expanded into academic records, enrollment, teaching loads, synchronization, integrity, reporting, and controlled software updates.

---

# Completed Development Areas

## Academic Core

- Authentication and user management
- Departments
- Courses
- Subjects
- Sections
- Teaching loads / assignments
- Student records
- Student enrollment
- Attendance
- Attendance printing and reporting
- Grading

## Instructor Access

- Instructor accounts
- Instructor-admin role
- Instructor-scoped sections
- Instructor-scoped student records
- Instructor-scoped attendance
- Account lifecycle and archiving

## Platform Infrastructure

- Offline-first local database operation
- Centralized runtime configuration
- Online/local database fallback
- GitHub Releases-based production updater
- Automated backup during application updates
- Version and release manifest handling


## Synchronization and Integrity

- TALA Engine schema inspection
- Schema merge planning
- Merge validation
- Deterministic execution planning
- Handler-based schema execution
- Synchronization API
- Smart Synchronization dashboard
- Database integrity inspection
- Reference inspection
- Duplicate detection
- Integrity reporting
- Deterministic backup source selection

---

# Current RC3.5 Direction

RC3.5 focuses on stability and operational safety around the academic system.

Current completed work includes:

- v1.0.3 stability release
- Release Manager verification
- Smart Synchronization dashboard improvements
- Database integrity center
- User lifecycle management
- Account archiving
- Impact analysis
- PRG workflow improvements
- Instructor ownership and visibility rules

The TALA Engine should remain stable while application-level workflows continue to improve.


---

# RC4 Roadmap

## Objective

Improve the day-to-day experience of instructors, administrators, and students without destabilizing the synchronization engine.

## Priority Areas

### User Experience

- Interface polish
- Clearer workflows
- Consistent feedback and error states
- Better navigation

### Instructor Productivity

- Faster student and section workflows
- Better teaching-load workflows
- Improved attendance workflows
- Improved grading workflows
- Better search and filtering

### Student Experience

- Clearer academic information
- Improved enrollment visibility
- Better attendance and grade presentation

### Platform Quality

- Performance improvements
- Responsive design
- Continued regression coverage
- Database integrity improvements
- Backup and recovery improvements


---

# Long-Term Academic Model

The target academic model remains:

```text
Student
  ↓
Enrollment
  ↓
Teaching Assignment
  ↓
Attendance
  ↓
Grades
  ↓
Reports
```

Attendance and grading should ultimately be driven by Student Subject Enrollment rather than section membership alone.

This supports irregular students, cross-enrollees, and future curriculum flexibility.

---

# Long-Term TALA Engine Direction

Future engine capabilities may include:

- Additional conflict-resolution policies
- Rollback and recovery
- Synchronization journal / audit history
- Background synchronization
- Distributed synchronization
- Plugin architecture
- Broader data synchronization support

These should be implemented incrementally and without violating the Analyze → Plan → Validate → Execute architecture.

---

# Out of Scope Until Deliberately Prioritized

The following remain future considerations rather than current commitments:

- Native mobile application
- Parent portal
- Student self-service portal
- SMS gateway
- AI-assisted grading
- Cloud-only deployment
