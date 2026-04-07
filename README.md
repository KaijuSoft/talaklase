# TalaKlase – PHP Web App
Converted from VB.NET WinForms to PHP + MySQL

---

## Requirements
- PHP 7.4+ (with PDO and PDO_MySQL extensions)
- MySQL 5.7+ or MariaDB
- A web server: Apache (XAMPP/WAMP) or Nginx

---

## Setup Instructions

### 1. Copy files
Place the entire `talaklase/` folder inside your web server root:
- XAMPP: `C:/xampp/htdocs/talaklase/`
- WAMP:  `C:/wamp64/www/talaklase/`
- Linux: `/var/www/html/talaklase/`

### 2. Configure the database
Open `includes/db.php` and update the connection details:

```php
// Online DB (optional)
$onlineDSN  = "mysql:host=sql12.freesqldatabase.com;port=3306;dbname=sql12817970;charset=utf8";
$onlineUser = "sql12817970";
$onlinePass = "N9dIfCwPRj";

// Local DB (fallback)
$localDSN  = "mysql:host=localhost;dbname=talaklasedb;charset=utf8";
$localUser = "root";
$localPass = "";
```

The app uses your **existing TalaKlase MySQL database** — the same one used by the VB.NET app. No changes to the database schema are needed.

### 3. Open in browser
Visit: `http://localhost/talaklase/`

---

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

---

## Features
- ✅ Add / Edit / Delete students with duplicate checking
- ✅ Paginated student list with search
- ✅ Attendance tracking (Present / Absent / Late) with bulk actions
- ✅ Attendance history view with filters
- ✅ Printable attendance summary report
- ✅ Grade encoding (Participation, Written, Performance, Exam)
- ✅ Manage Departments, Courses, Sections, Subjects, Instructors
- ✅ Online DB + Local DB fallback (same logic as VB.NET app)
- ✅ Mobile-friendly responsive layout
- ✅ Dark sidebar navigation

---

## Tech Stack
- **Backend:** PHP 7.4+ with PDO
- **Frontend:** HTML5, Bootstrap 5.3, Bootstrap Icons
- **Database:** MySQL (same schema as original VB.NET app)
- **No framework required** — works on any basic PHP host

---

## Notes
- The database schema is unchanged from the original VB.NET app
- The online/local DB fallback mirrors the original VB.NET sync logic
- The print attendance page uses browser print (`Ctrl+P`) — sidebar and filters are hidden automatically
