# SIAMS – Student Industrial Attachment Management System

Diploma in Information Technology project, Kabarak University (Nakuru Town Campus).
A web system for managing student industrial attachment: placements, logbooks,
supervision and assessment.

Built with PHP 8, MySQL/MariaDB and Bootstrap 5, run locally on XAMPP.

## Setting up on a computer with XAMPP

1. Copy this folder to `C:\xampp\htdocs\siams`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Create the database (this also creates the `siams_app` database account):
   ```
   C:\xampp\mysql\bin\mysql.exe -u root < database\setup.sql
   ```
4. Copy `config\config.example.php` to `config\config.php` and set `DB_PASS`
   to the password used for `siams_app` in `database\setup.sql`.
5. Add the fictitious test accounts and sample data:
   ```
   C:\xampp\php\php.exe database\seed.php
   ```
6. Open http://localhost/siams and log in with an account from
   `database\TEST_ACCOUNTS.md`.

Change the database password and remove the test accounts before using the system on a live server.

## Modules

| Module | Requirements |
|---|---|
| 1. Login and role-based access | FR-01, FR-03, FR-04 |
| 2. Organizations and opportunities | FR-06, FR-12, FR-13, FR-21 |
| 3. Applications and placements | FR-07, FR-09, FR-14, FR-20, FR-22 |
| 4. Supervisor assignment and visits | FR-17, FR-18, FR-23 |
| 5. Electronic logbook | FR-10, FR-15 |
| 6. Assessments and final mark | FR-11, FR-16, FR-19, FR-24 |
| 7. Document uploads | FR-08 |
