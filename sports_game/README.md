# Sports Game Information System

A lightweight PHP + SQLite web application built for a database principles course. It provides a public-facing information portal and an authenticated management backend for the 15th National Games dataset.

## Table of Contents
- [Sports Game Information System](#sports-game-information-system)
  - [Table of Contents](#table-of-contents)
  - [Architecture](#architecture)
  - [Features](#features)
  - [Project Structure](#project-structure)
  - [Prerequisites](#prerequisites)
  - [Setup \& Run](#setup--run)
  - [Default Credentials](#default-credentials)
  - [Security Notes](#security-notes)
  - [Data Integrity](#data-integrity)
  - [Troubleshooting](#troubleshooting)
  - [Documentation](#documentation)

## Architecture
- **Frontend:** Plain HTML/CSS pages (`index.php`, `public.php`, `admin_dashboard.php`) styled via the shared `assets/ui.css` theme so public and admin experiences stay consistent.
- **Backend:** Procedural PHP scripts (`admin_*.php`, `*_list.php`) using the built-in `SQLite3` extension.
- **Database:** Single-file SQLite database (`data.db`).
- **Auth:** Session-based admin login (`admin_login.php`, `auth_guard.php`, `admin_users.php`).

## Features
- Public read-only search for athletes, delegations, categories, events, and participation records (all tables now share the same responsive layout, badge styles, and filters).
- Admin CRUD for each entity plus participation management with medal tracking, validation, and server-side prepared statements for every add/update/delete operation completed so far.
- Admin user management (create, reset password, delete) with duplicate checks and numeric-password policy enforcement.
- Security hardening: all admin pages require login, redirects are sanitized, and the seeded credential is rotated to an eight-digit numeric password stored as a bcrypt hash.
- Operation auditing and UX feedback: every admin CRUD action now emits a session-based success/failure toast and dangerous actions (deletes, changing athlete/event pairs) display confirm dialogs.
- Participation module integrity guardrails: score/time fields are mandatory, medal values are normalized to `Gold/Silver/Bronze/None`, and a unique SQLite index blocks duplicate (AthleteID, EventID, Medal) tuples.
- Unified UI kit (`assets/ui.css`) reused by both public dashboards and the authenticated backend, including consistent buttons for returning to the homepage.
- Bilingual interface with a persistent Chinese/English toggle (stored via session + cookie) rendered on every page; the default landing language is now English to help graders and teammates.
- Responsive landing + dashboard shells that expand to ~95% of the viewport (public) and display admin entry cards in a balanced 3×2 grid with live stats for athletes/delegations/events.

## Project Structure
```
sports_game/
├── admin_login.php          # Admin authentication entry point (expanded hero layout)
├── admin_dashboard.php      # Authenticated landing page with live stats + 3×2 module grid
├── admin_users.php          # Admin account management UI
├── admin_*.php              # CRUD pages for each entity
├── *_list.php               # Public query pages
├── auth_guard.php           # Session gatekeeper for admin pages
├── db_schema.php            # Helper to enforce SQLite constraints (e.g., participation uniqueness)
├── admin_security.php       # Helper for AdminUsers table + seeding
├── logout.php
├── data.db                  # SQLite database file
├── assets/ui.css            # Shared design system for public/admin pages
├── docs/                    # Project overview, reports, tasks
└── README.md                # This file
```

## Prerequisites
1. **XAMPP / PHP runtime**
   - Apache + PHP 8.1+ (tested with PHP 8.2.12 via XAMPP on Windows).
   - SQLite3 extension enabled (default in PHP 7+).
2. **Folder placement**
   - Copy the entire `sports_game` directory into `XAMPP/htdocs/` (or the document root of your web server).
3. **Browser**
   - Use a modern browser (Chrome/Edge/Firefox). When testing authenticated flows, open the page directly via the browser address bar—avoid embedding the site inside third-party preview iframes that may block cookies.

## Setup & Run
1. **Start services**
   - Launch the XAMPP control panel and start **Apache** (MySQL is not used).
2. **Verify PHP CLI (optional but recommended)**
   ```powershell
   cd /d d:\softwares\XMAPP\htdocs\sports_game
   & "d:\softwares\XMAPP\php\php.exe" -v
   ```
   You should see the PHP version output. Use the same binary for linting:
   ```powershell
   & "d:\softwares\XMAPP\php\php.exe" -l admin_login.php
   ```
3. **Access the public site**
   - Navigate to `http://localhost/sports_game/index.php` for the landing page.
   - Choose "Public System" to explore the public portal; all sub-pages inherit the same language preference (English is the default, use the toggle to switch to Chinese if needed).
4. **Access the admin site**
   - Visit `http://localhost/sports_game/admin_login.php` (language toggle is available in the header of every admin page).
   - Sign in using the default credentials below.
   - After login you will be redirected to `admin_dashboard.php`, where each card links to a CRUD module.
5. **Manage data**
   - Use the forms on each `admin_*.php` page to add/update/delete records (confirm dialogs + bilingual toasts will guide you through each mutation).
   - Participation records (`admin_participation.php`) support medal tagging, datalist athlete search, and compound-key deletes.
6. **Maintain admin users**
   - From the dashboard, open "Manage Admin Users" to create additional accounts or reset passwords.
7. **Log out**
   - Use the "Log out" link in the dashboard header, or visit `logout.php` directly (every logout button now shows a confirmation dialog).

## Default Credentials
| Username | Password  | Notes |
| -------- | --------- | ----- |
| `admin`  | `12345678` | Auto-seeded at first launch; update or create more accounts via `admin_users.php` (passwords must be 8 digits). Language defaults to English after login but the toggle persists across all modules.

## Security Notes
- All admin pages call `auth_guard.php`, which enforces active sessions, sanitizes redirect targets, and blocks anonymous access.
- Passwords are stored as bcrypt hashes (`password_hash`), and admin passwords must be exactly eight digits (the UI enforces and validates this rule).
- Every CRUD page now relies on prepared statements to eliminate SQL injection risks in inserts, updates, and deletes.
- Redirect parameters are URL-decoded and validated to prevent open redirects or loops back to the login page.
- The SQLite database lives alongside the PHP files—ensure file permissions prevent download in production deployments.

## Data Integrity
- `db_schema.php` normalizes medal values (empty strings/NULL become `None`) and creates the `idx_participation_unique (AthleteID, EventID, Medal)` index to block duplicate medal entries.
- `admin_participation.php` / `admin_participation_edit.php` require score/time fields, run updates inside transactions, and perform delete-then-insert to preserve compound-key semantics.
- Public `participation_list.php`, the admin tables, and exports all read medals via `COALESCE(NULLIF(...))` so every surface shows the standardized value.

## Troubleshooting
| Symptom | Possible Cause | Resolution |
| ------- | -------------- | ---------- |
| Login succeeds but you return to the login page | Browser blocked session cookies because the page was loaded inside Dreamweaver Device Preview or another iframe | Open the site directly in the browser (`http://localhost/...`) or allow third-party cookies for `127.0.0.1`. |
| "php" not recognized when linting | PHP executable is not in `PATH` | Use the fully qualified path (e.g., `"d:\softwares\XMAPP\php\php.exe"`). |
| SQLite cannot open file | The web server user lacks write permission | Ensure the `data.db` file and folder are writable by Apache/PHP. |
| Changes to CSS/HTML not reflecting | Browser cache | Hard-refresh (`Ctrl+F5`) or clear cache. |

## Documentation
- `docs/project_overview.md`: English architecture + UI evolution summary (updated with responsive landing & dashboard info).
- `docs/project_report_cn.md`: Chinese narrative report capturing the latest bilingual and layout changes.
- `docs/task.md`: Security/feature backlog and status tracking.
- `docs/contribution_summary.md`: Contribution summary.

Feel free to extend the documentation with ER diagrams, deployment notes, or test cases as the project evolves.
