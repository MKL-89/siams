# SIAMS — Student Industrial Attachment Management System

A web-based system that manages the full industrial attachment cycle for the School of Science, Engineering & Technology (SSET), Kabarak University, Nakuru Town Campus. Host organizations post opportunities, students apply, the attachment office approves placements and assigns supervisors, students keep an electronic logbook, and the system calculates each student's final attachment mark.

| | |
|---|---|
| **Project** | Diploma in Information Technology final project |
| **Institution** | Kabarak University — Nakuru Town Campus, Department of Computer Science |
| **Author** | Malon Kiprono (DIT/N/4026/09/25) |
| **Supervisor** | Mr. Victor Rotich |
| **Status** | All modules built and system-tested locally; user acceptance testing and hosting pending |

---

## Contents

1. [Features](#features)
2. [User roles](#user-roles)
3. [Technology stack](#technology-stack)
4. [System architecture](#system-architecture)
5. [Project structure](#project-structure)
6. [Installation](#installation)
7. [Running the system](#running-the-system)
8. [Working on the code in VS Code](#working-on-the-code-in-vs-code)
9. [Configuration](#configuration)
10. [Database](#database)
11. [How the final mark is calculated](#how-the-final-mark-is-calculated)
12. [Security](#security)
13. [Testing](#testing)
14. [Known limitations](#known-limitations)
15. [Deploying to a live server](#deploying-to-a-live-server)
16. [Troubleshooting](#troubleshooting)
17. [Requirements traceability](#requirements-traceability)
18. [License](#license)

---

## Features

- **Public landing page**: explains what SIAMS does, shows live figures (open opportunities, slots, organizations, counties) and the six newest opportunities.
- **Public opportunity browsing**: anyone can search, filter and read opportunities without an account. An account is needed only to apply: **Log in to apply** brings a student straight back to that opportunity after logging in.
- **Secure login** with role-based access for five user groups, account lockout after repeated failed attempts, and automatic logout after inactivity.
- **Student account creation**: students create their own account with their registration number. The account is active at once and the student is logged in straight away.
- **Host organization registration**: organizations sign up online and can post opportunities immediately; students see them straight away. The coordinator can still reject an organization, and the administrator can deactivate any account.
- **Show password**: every password field has an eye button to show or hide what was typed.
- **Several accounts in one browser**: different users can be logged in at the same time in different tabs (for example a student in one tab and the coordinator in another). Each extra account gets its own session and address (`/siams/u/1/…`, `/siams/u/2/…`, up to 9), so logging out in one tab does not log out the others.
- **Attachment opportunities**: organizations post vacancies; students search and filter them by keyword, sector and county, sort them (closing soonest, newest, title) and browse them 12 per page.
- **Applications and placements**: students apply, organizations accept applicants, and the coordinator approves the placement.
- **Supervisor assignment**: the coordinator assigns a university supervisor to each placed student, and supervisors record visits.
- **Electronic logbook**: students write weekly entries, and the industrial supervisor confirms them.
- **Assessment**: the industrial supervisor, university supervisor and logbook are each scored out of 100, and the system calculates the weighted final mark.
- **Document uploads**: acceptance letters, final reports, insurance cover and other PDFs, stored securely and downloadable only by authorised users.
- **Reports**: placed and unplaced students, placements by organization and county, supervisor workload, and assessment results. Each report can be printed or exported to CSV for Excel.
- **Administration**: user accounts, password resets, an activity (audit) log, and a one-click database backup.

---

## User roles

| Role | Main tasks |
|---|---|
| **Administrator** | Creates and deactivates user accounts, resets passwords, views the activity log, downloads database backups. |
| **Attachment Coordinator** (Office of Industrial Linkages & Attachment) | Views student accounts and organizations (and can reject them), manages attachment periods, approves placements, assigns university supervisors, views results and documents, produces reports. |
| **University Supervisor** (lecturer) | Views assigned students, records supervision visits, reads logbooks, submits the university assessment. |
| **Industrial Supervisor** (host organization) | Posts opportunities, accepts or rejects applicants, confirms logbook entries, submits the industrial assessment. |
| **Student** | Creates an account, searches and applies for opportunities, tracks applications, keeps the weekly logbook, uploads documents, views results. |

Click your name in the menu bar for **My account** (change password), **Use another account** and **Log out**.

### Using several accounts at once

1. Log in as the first user, for example a student.
2. Click your name → **Use another account (new tab)**, or open a new tab and type the address, and log in as the second user.
3. The second account opens at `http://localhost/siams/u/1/…` in its own session. The first account stays logged in at `http://localhost/siams/…`.
4. Logging out ends only the account of that tab. Tabs that show the same account share its session, so logging out of that account logs out all of its tabs; those tabs refresh at once to show the login page.

A tab opened by typing the address (or from a bookmark) gets its own login session if another open tab already uses that account. Links opened in a new tab from a SIAMS page keep the same account.

Up to 10 accounts can be logged in at once in one browser.

---

## Technology stack

| Layer | Technology |
|---|---|
| Front end | HTML5, CSS3, Bootstrap 5.3.3, Bootstrap Icons 1.11.3, JavaScript |
| Back end | PHP 8.3 with PDO (prepared statements) |
| Database | MariaDB 10.11 (MySQL-compatible) |
| Web server | Apache (XAMPP) |
| Version control | Git and GitHub |

Bootstrap and Bootstrap Icons are loaded from the jsDelivr CDN, so the computer needs an internet connection for the pages to be fully styled.

---

## System architecture

SIAMS uses a three-tier architecture:

```
 Web browser (HTML, Bootstrap, JavaScript)
          │  HTTP requests / HTML pages
          ▼
 Apache + PHP 8.3  ── checks login and role, validates every form, applies the business rules
          │  PDO prepared statements
          ▼
 MariaDB database (siams_db) ── stores users, placements, logbooks, assessments and documents
```

The application connects to the database with its own limited account (`siams_app`), which can only read and change data, never alter or drop tables.

---

## Project structure

```
siams/
├── index.php              Public landing page (logged-in users go to their dashboard)
├── opportunities.php      Public list of open opportunities (search, filters, pages)
├── opportunity.php        Public details of one opportunity, with "Log in to apply"
├── login.php / logout.php Login and logout (logout ends only that tab's account)
├── .htaccess              Maps /siams/u/1/… to /siams/u/9/… to the same pages, one login session each
├── register.php           Public "Create a student account" form (account active at once)
├── register_host.php      Public registration form for host organizations
├── account.php            "My account": profile and password change for every user
├── download.php           Serves uploaded files after an access check
├── 403.php                "Access denied" page
│
├── admin/                 Administrator pages (users, password reset, activity log, backup)
├── coordinator/           Coordinator pages (organizations, periods, placements, assignment, results, documents, reports)
├── lecturer/              University supervisor pages (students, visits, logbooks, assessment)
├── industrial/            Industrial supervisor pages (opportunities, applicants, students, logbooks, assessment)
├── student/               Student pages (opportunities, applications, logbook, documents, results, profile)
│
├── includes/              Shared code — not reachable from the browser
│   ├── auth.php           Sessions, login, CSRF tokens, role checks
│   ├── helpers.php        Escaping, flash messages, audit logging, validation, county and sector lists
│   ├── header.php / footer.php   Page layout, role menus and the visitor menu
│   ├── opportunities.php  Opportunity rules and the shared search (public and student pages)
│   ├── opportunity_list.php      Search form, cards and page links used by both opportunity lists
│   ├── placements.php     Placement and application rules
│   ├── logbook.php        Logbook rules
│   ├── assessment.php     Scores and final-mark calculation
│   ├── documents.php      Upload checks and storage
│   └── ...                Dashboard and organization helpers
│
├── config/                Settings — not reachable from the browser
│   ├── config.example.php Template for config.php
│   ├── config.php         Local settings with the database password (not in Git)
│   └── db.php             Database connection
│
├── database/              Not reachable from the browser
│   ├── setup.sql          Creates the database, the app account and all 17 tables
│   ├── seed.php           Adds fictitious test accounts and sample data
│   ├── sample_opportunities.php  Adds 14 fictitious organizations and 32 opportunities (run by seed.php)
│   ├── test_password.php  Shared password for the test accounts
│   └── TEST_ACCOUNTS.md   Test logins for development
│
├── uploads/               Uploaded PDFs — not reachable directly; served through download.php
├── assets/css/style.css   Custom styles, including the landing page and print styles for reports
└── assets/js/app.js       Show/hide password button on every password field
```

---

## Installation

### Requirements

- Windows 10/11 (or any system that runs XAMPP)
- [XAMPP](https://www.apachefriends.org) with PHP 8.1 or later (developed on PHP 8.3 and MariaDB 10.11)
- A modern web browser (developed and tested on Microsoft Edge)
- Git (optional, to clone the repository)

### Steps

1. **Get the code** into XAMPP's web folder:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/MKL-89/siams.git
   ```
   or copy the `siams` folder to `C:\xampp\htdocs\siams`.

2. **Start Apache and MySQL** in the XAMPP Control Panel.

3. **Create the database.** This creates `siams_db`, the `siams_app` database account and all tables:
   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\siams\database\setup.sql
   ```
   > **Warning:** `setup.sql` drops and recreates `siams_db`. Running it again deletes all existing data.

4. **Create the settings file.** Copy `config\config.example.php` to `config\config.php` and set `DB_PASS` to the password given to `siams_app` in `database\setup.sql`.

5. **Add the test data** (fictitious accounts, 16 organizations, an attachment period and 34 opportunities):
   ```bash
   C:\xampp\php\php.exe C:\xampp\htdocs\siams\database\seed.php
   ```

6. Open **http://localhost/siams**. The landing page opens; click **Log in** and use one of the accounts in `database/TEST_ACCOUNTS.md`.

---

## Running the system

1. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Go to **http://localhost/siams** in your browser. Visitors see the landing page and can browse opportunities without an account.
3. Click **Log in** (or **Create account**). After logging in, each role lands on its own dashboard.

### How a student applies

1. Open an opportunity from the landing page or **Opportunities**, and click **Log in to apply**.
2. A student without an account clicks **Create a student account** instead. The account is ready at once.
3. After logging in (or creating the account), the student is taken straight back to the opportunity and clicks **Apply for this opportunity**.
4. The application then appears under **My applications** on the student's dashboard.

To see the database directly, open **http://localhost/phpmyadmin** and select `siams_db`.

---

## Working on the code in VS Code

1. Open the folder: **File → Open Folder… → `C:\xampp\htdocs\siams`**, or from a terminal:
   ```bash
   code C:\xampp\htdocs\siams
   ```
2. Keep Apache and MySQL running in XAMPP.
3. Edit a file, save it (**Ctrl+S**) and refresh the browser (**F5**). PHP needs no build step.

Recommended extension: **PHP Intelephense** (code completion and error highlighting).

> **Do not run the project with `php -S`, "PHP Server" or "Live Server".** These bypass Apache, so the `.htaccess` rules that protect `config/`, `includes/`, `database/` and `uploads/` stop working. Always use XAMPP at http://localhost/siams.

### Saving changes to GitHub

```bash
git add .
git commit -m "Short description of the change"
git push
```

---

## Configuration

Settings live in `config/config.php` (copied from `config/config.example.php`):

| Setting | Default | Purpose |
|---|---|---|
| `DB_HOST` | `127.0.0.1` | Database server |
| `DB_NAME` | `siams_db` | Database name |
| `DB_USER` | `siams_app` | Limited database account used by the app |
| `DB_PASS` | — | Password for `DB_USER` |
| `BASE_PATH` | `/siams` | Path the system is served from (extra accounts use `BASE_PATH/u/1` … `/u/9`) |
| `MAX_LOGIN_ATTEMPTS` | `5` | Failed logins allowed … |
| `LOCKOUT_MINUTES` | `15` | … within this many minutes before the account is locked |
| `SESSION_TIMEOUT_MINUTES` | `15` | Automatic logout after this much inactivity |

The mark weights and the records retention period are stored in the `settings` table:

| Key | Value |
|---|---|
| `weight_industrial` | 40 (% of final mark) |
| `weight_university` | 40 (% of final mark) |
| `weight_logbook` | 20 (% of final mark) |
| `records_retention_years` | 5 |

---

## Database

`database/setup.sql` creates 17 InnoDB tables (utf8mb4):

| Group | Tables |
|---|---|
| Users and security | `users`, `login_attempts`, `audit_log` |
| Profiles | `students`, `coordinators`, `lecturers`, `ind_supervisors` |
| Organizations and vacancies | `organizations`, `attachment_periods`, `opportunities` |
| Placement process | `applications`, `placements`, `supervision_visits` |
| Monitoring and assessment | `logbook_entries`, `evaluations`, `documents` |
| Configuration | `settings` |

Business rules are also enforced by the database, for example:

- each placement has at most one score per component (`UNIQUE (placement_id, component)`);
- every score must be between 0 and 100 (`CHECK` constraint);
- foreign keys link every placement to a student, an opportunity and an attachment period.

---

## How the final mark is calculated

Each placement receives three scores out of 100:

```
Final mark = (Industrial × 40%) + (University × 40%) + (Logbook × 20%)
```

The final mark is shown only when all three scores have been entered. The weights come from the `settings` table, so they can be changed without editing the code.

---

## Security

| Measure | How it is implemented |
|---|---|
| Password storage | `password_hash()` / `password_verify()` (bcrypt); passwords are never stored in plain text |
| Account lockout | 5 failed attempts within 15 minutes locks the account |
| Session security | Session ID regenerated at login; automatic logout after 15 minutes of inactivity; a separate session cookie for each account logged in on the same browser |
| Role-based access control | Every page calls `require_role()`; users only see the pages and records for their role |
| CSRF protection | Every form carries a token that is checked on submission |
| SQL injection | All queries use PDO prepared statements |
| Cross-site scripting (XSS) | All output is escaped with `e()` (`htmlspecialchars`) |
| File uploads | PDF only (checked by content type and `%PDF-` header), 5 MB maximum, saved under random names, downloaded only through `download.php` after an access check |
| Protected folders | `.htaccess` (`Require all denied`) on `config/`, `includes/`, `database/` and `uploads/` |
| Caching | Pages are sent with `Cache-Control: no-store`, so logged-out pages cannot be reopened from the browser cache |
| Least privilege | The app's database account cannot create, alter or drop tables |
| Audit trail | Logins and failed logins, organization and placement decisions, supervisor assignments, report exports, password changes and administrative actions are recorded in `audit_log` |

---

## Testing

The system was tested with automated scripts that send real requests to the running system and check the responses.

| Module | Checks |
|---|---|
| 1. Login and role-based access | 16 |
| 2. Organizations and opportunities | 22 |
| 3. Applications and placements | 25 |
| 4. Supervisor assignment and visits | 22 |
| 5. Electronic logbook | 20 |
| 6. Assessments and final mark | 18 |
| 7. Document uploads | 21 |
| 8. Reports and administration | 33 |
| 9. Student account creation and opportunity browsing | 35 |
| 10. Landing page, public opportunities, "log in to apply", instant accounts and show password | 39 |
| 11. Several accounts in one browser (separate session per account) | 28 |
| **Total** | **279 — all passed (final run, 4 November 2026)** |

On the development computer, pages responded in 6–23 ms on average, and 50 simultaneous requests all succeeded within 500 ms. Full results, test cases and the defects found and fixed are in Chapter Four of the project report.

**Still to do:** user acceptance testing with real students and supervisors, including the System Usability Scale (SUS) questionnaire.

All names, organizations and records used in development and testing are fictitious.

---

## Known limitations

- Runs locally only; not yet hosted, so HTTPS and performance over a mobile network are untested.
- No email or SMS: forgotten passwords are reset by the administrator, and users are not notified automatically.
- Each assessment is one overall score out of 100 with comments, not the university's detailed assessment form criteria.
- Placements are not moved to *Ongoing* or *Completed* automatically.
- Tested on Microsoft Edge (desktop and phone width) only; Firefox and Android devices are untested.
- Not yet tested at the full scale of about 200 students.

---

## Deploying to a live server

Before putting SIAMS on a real server:

1. Use a host with **PHP 8.1+**, **MySQL/MariaDB** and **HTTPS**.
2. Create the database and a database user with a **new, strong password**; update `setup.sql` (or create the user in the hosting control panel) and `config/config.php`.
3. **Do not run `seed.php` or `sample_opportunities.php`.** Remove `database/TEST_ACCOUNTS.md`, `database/test_password.php` and any test accounts.
4. Create the first administrator account, then create the other users from the admin pages.
5. Make sure the host honours `.htaccess` files (Apache), or block `config/`, `includes/`, `database/` and `uploads/` in the server configuration.
6. Set `BASE_PATH` in `config/config.php` if the system is not served from `/siams`. The host must have Apache's `mod_rewrite` turned on for the extra-account addresses (`/u/1/…`).
7. Download a backup regularly from **Administrator → Backup**.

---

## Troubleshooting

| Problem | Solution |
|---|---|
| Page does not load at http://localhost/siams | Apache is not running — start it in XAMPP |
| "Database connection failed" | MySQL is not running, or `DB_PASS` in `config/config.php` does not match `setup.sql` |
| Apache will not start | Port 80 is in use (often Skype or IIS) — close that program or change Apache's port |
| Pages look unstyled | No internet connection — Bootstrap is loaded from a CDN |
| "Your form expired. Please try again." | The page was open too long or the session timed out; refresh the page and submit again |
| Account locked | Wait 15 minutes, or ask the administrator to reset the password |
| Upload rejected | Only PDF files up to 5 MB are accepted |

---

## Requirements traceability

| Module | Functional requirements |
|---|---|
| 1. Login and role-based access | FR-01, FR-03, FR-04 |
| 2. Organizations and opportunities | FR-06, FR-12, FR-13, FR-21 |
| 3. Applications and placements | FR-07, FR-09, FR-14, FR-20, FR-22 |
| 4. Supervisor assignment and visits | FR-17, FR-18, FR-23 |
| 5. Electronic logbook | FR-10, FR-15 |
| 6. Assessments and final mark | FR-11, FR-16, FR-19, FR-24 |
| 7. Document uploads | FR-08 |
| 8. Reports and administration | FR-02, FR-05, FR-25, FR-26, FR-27, FR-28 |

FR-02 (forgotten password) is met through an administrator password reset, because the system does not send email. The full requirements are listed in Section 3.7 of the project report.

---

## License

© 2026 Malon Kiprono. All rights reserved.
Developed as an academic project for Kabarak University. No part of this project may be reproduced or distributed without the written permission of the author or Kabarak University.
