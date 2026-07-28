# TalaKlase Installation Guide

## 1. Clone the repository

Place the project in your web server directory, for example:

- `C:\xampp\htdocs\talaklase`
- `/var/www/html/talaklase`

## 2. Create the runtime configuration

Copy:

- `includes/config.example.php`

to:

- `includes/config.php`

Then fill in your database credentials.

`includes/config.php` is the only runtime configuration file the application reads.

## 3. Configure the database

Create or select the TalaKlase database, then import the SQL schema used by your deployment.

## 4. Configure the web server

Make sure the document root points to the TalaKlase project directory and that PHP 8.2 with PDO MySQL is enabled.

## 5. Configure permissions

Ensure the web server user can read the application files and write to any runtime directories used for uploads, logs, cache, or backups.

## 6. First login

Open the application in your browser and sign in with your administrator account.

If the default administrator account is enabled in your environment, change its password immediately after first login.
