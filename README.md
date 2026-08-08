# TalaKlase - PHP Web App

TalaKlase is an offline-first academic management system for student records, attendance, grading, enrollment, teaching loads, and synchronization.

Repository: KaijuSoft/TalaKlase

Developed by KaijuSoft 

Founder & Owner: Julius Frederick C. Vendivil

## Project Overview

The project was converted from VB.NET WinForms to PHP + MySQL and is designed to stay maintainable, modular, and friendly to local-first deployments.

## Features

- Student, section, subject, and instructor management
- Attendance tracking
- Grade encoding
- Academic year management
- Instructor account management
- TALA Engine synchronization
- GitHub Releases-based updates for production

## Screenshots

> Placeholder for future screenshots.

## Requirements

- PHP 8.2+
- PDO and PDO_MySQL
- MySQL 5.7+ or MariaDB
- Apache, Nginx, or XAMPP/WAMP

## Installation

1. Clone or copy the repository into your web root.
2. Copy `includes/config.example.php` to `includes/config.php`.
3. Set your database credentials in `includes/config.php`.
4. Import the TalaKlase database schema.
5. Open the site in your browser.

## Configuration

- `includes/config.php` is the single runtime configuration source.
- `includes/config.example.php` is the safe template.
- `includes/config.php` is ignored by Git and must not be committed.

## Updating

Production installations use GitHub Releases through the Release Manager.
Developer environments may continue using Git-based update checks.

## First Login

The application keeps a first-install administrator bootstrap for clean deployments.
That account is intended only for initial setup. Change the password immediately after deployment.

## Pages & Features

| Page | URL |
|---|---|
| Student Records | `?page=students` |
| Attendance | `?page=attendance` |
| View Attendance | `?page=view_attendance` |
| Print Attendance | `?page=print_attendance` |
| Grading Form | `?page=grades` |
| Departments | `?page=departments` |
| Courses | `?page=courses` |
| Sections | `?page=sections` |
| Subjects | `?page=subjects` |
| Instructors | `?page=instructors` |

## Tech Stack

- Backend: PHP 8.2 with PDO
- Frontend: HTML5, Bootstrap 5.3, Bootstrap Icons
- Database: MySQL or MariaDB

## Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) for the current system design.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

See [LICENSE](LICENSE).

## Credits

- TalaKlase maintainers
- KaijuSoft
- The open-source PHP ecosystem

## Notes

- The database schema remains compatible with the original VB.NET app.

- Online/local database fallback mirrors the legacy sync behavior.

- Smart Sync reads from the Online Database and writes to the Local Database.
- Restore Online Database is the administrative Local-to-Online recovery workflow.

- Print pages use browser printing.


## Current Project Status

The current development branch is in the RC3.5.22 line and corresponds to the v1.0.3 stability release series.

See [`docs/PROJECT_STATUS.md`](docs/PROJECT_STATUS.md) for the consolidated current state of the application and TALA Engine.

Recent work includes:

- Instructor account administration and instructor-scoped access
- User account lifecycle and archiving
- Database integrity inspection and reporting
- Duplicate and reference inspection
- Deterministic backup source selection
- Smart Synchronization dashboard improvements
- TALA Engine schema execution architecture
- GitHub Releases-based production updates
- Release backup, validation, installation, and verification
- Improved administrative PRG workflows

The repository's older RC1/RC2 documents remain useful as historical architecture and development references. Current implementation status should be taken from `docs/PROJECT_STATUS.md` and the latest changelog entries.
