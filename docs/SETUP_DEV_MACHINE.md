# TalaKlase Development Machine Setup

Version: RC2
Last Updated: July 2026

---

# Purpose

This guide prepares a fresh Windows development machine for TalaKlase.

Following this guide will install:

- Git
- GitHub
- XAMPP
- PHP
- Composer
- Node.js
- Playwright
- Required PHP Extensions
- TalaKlase Dependencies

---

# Requirements

Windows 10/11

Internet Connection

Administrator Rights

GitHub Access

---

# 1. Install Git

Download:

https://git-scm.com/downloads

Verify

```bash
git --version
```

Expected

```
git version 2.x
```

---

# 2. Configure Git

```bash
git config --global user.name "Your Name"

git config --global user.email "your@email.com"
```

Recommended editor (Notepad++):

```bash
git config --global core.editor "\"C:/Program Files/Notepad++/notepad++.exe\" -multiInst -notabbar -nosession"
```

Verify

```bash
git config --global --list
```

---

# 3. Clone TalaKlase

```bash
git clone https://github.com/SunriseRaven/talaklase.git
```

Switch to development

```bash
git checkout development
```

---

# 4. Install XAMPP

Download

https://www.apachefriends.org/

Install

Recommended path

```
C:\xampp
```

Modules required

- Apache
- MariaDB
- PHP
- phpMyAdmin

---

# 5. Add PHP to PATH

Add

```
C:\xampp\php
```

Restart terminal.

Verify

```bash
php -v
```

---

# 6. Enable PHP Extensions

Edit

```
C:\xampp\php\php.ini
```

Ensure the following are enabled.

```
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip
extension=dom
extension=xml
extension=xmlreader
extension=xmlwriter
extension=curl
```

Restart terminal.

Verify

```bash
php -m
```

Required modules include

- gd
- zip
- fileinfo
- openssl
- mbstring
- pdo_mysql
- dom
- xml
- xmlreader
- xmlwriter

---

# 7. Install Composer

Download

https://getcomposer.org/

Composer should detect

```
C:\xampp\php\php.exe
```

Verify

```bash
composer --version
```

---

# 8. Install PHP Dependencies

Inside TalaKlase

```bash
composer install
```

Expected

```
Generating autoload files
```

---

# 9. Install Node.js

Download

https://nodejs.org/

Verify

```bash
node -v

npm -v
```

---

# 10. Install JavaScript Packages

```bash
npm install
```

---

# 11. Install Playwright

```bash
npx playwright install
```

Downloads

- Chromium
- Firefox
- WebKit

---

# 12. Verify Playwright

```bash
npx playwright test
```

or

```bash
npx playwright test tests/smoke
```

---

# 13. Database

Import

```
talaklasedb.sql
```

using phpMyAdmin.

Verify

- Administrator account exists
- Academic Year exists
- Sample data imports correctly

---

# 14. Start XAMPP

Start

- Apache
- MariaDB

---

# 15. Run TalaKlase

Open

```
http://localhost/talaklase
```

Login.

Verify

- Dashboard
- Students
- Sections
- Teaching Loads
- Attendance
- Grades
- Sync

---

# 16. Verify TALA Engine

Run

```bash
php -l includes/TALA/src/TalaEngine.php
```

Expected

```
No syntax errors detected
```

---

# 17. Git Workflow

Daily workflow

```text
git pull

Develop

Test

git add .

git commit -m "Describe changes"

git push
```

Never edit directly on multiple machines.

Always

Laptop

↓

GitHub

↓

Development Machine

---

# 18. Branch Strategy

```
development
```

Main development branch.

```
release/1.0
```

Stable release.

```
feature/*
```

New features.

Examples

```
feature/updater

feature/smart-merge

feature/schema-executor

bugfix/importer

bugfix/permissions
```

Merge feature branches into development after testing.

---

# 19. Troubleshooting

## PHP not found

Verify

```bash
php -v
```

If not found

Check PATH

```
C:\xampp\php
```

---

## Composer cannot install

Usually caused by missing extensions.

Verify

```bash
php -m
```

Required

- gd
- zip
- fileinfo

---

## Git asks for author identity

Configure

```bash
git config --global user.name "Your Name"

git config --global user.email "your@email.com"
```

---

## Playwright cannot launch

Install browsers

```bash
npx playwright install
```

---

## XAMPP Apache won't start

Usually caused by:

- IIS
- Skype
- Another Apache instance
- Port 80 in use

Change Apache ports or stop conflicting services.

---

## MariaDB won't start

Check

```
mysql_error.log
```

inside

```
C:\xampp\mysql\data
```

---

# Development Standards

Always

✅ Pull before coding

✅ Commit frequently

✅ Push after testing

✅ Use feature branches

✅ Validate PHP syntax

```bash
php -l filename.php
```

✅ Run Playwright before merging

---

# RC2 Notes

Current architecture

Attendance

↓

Teaching Assignments

↓

Student Assignments

↓

Grades

↓

Reports

Student Assignments are the primary enrollment mechanism.

Do not revert to legacy student_section logic.

---

Happy Coding!