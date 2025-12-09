# Sports Game Information System – Project Overview

_Last updated: 2025-12-07 (evening refresh)_

## 1. System Snapshot
- **Purpose:** Course project for a database principles class, providing both a public information portal and an admin CRUD backend for the 15th National Games.
- **Stack:** PHP (no framework) + SQLite (`data.db`) + vanilla HTML/CSS powered by a shared design system (`assets/ui.css`). Pages are rendered server-side; no build pipeline.
- **Core entities inferred from code:** `Delegation`, `Athlete`, `Category`, `Event`, `Participation`.
- **Entry points:** `index.php` routes users to `public.php` (read-only search) or the secured admin login; legacy `admin.html` now immediately redirects to `admin_login.php`.
- **Language toggle:** Every page renders a CN/EN switch whose preference is stored in the session + cookie; the first load now defaults to English to support reviewers and teammates.
- **Layout refresh:** The landing page and admin dashboard stretch to roughly 95% of the viewport, and the six admin modules sit in a 3×2 grid topped by live stat cards for Athletes, Delegations, and Events.
- **Operational feedback:** All admin CRUD flows share the same session flash → toast + confirmation pattern so destructive actions (delete, switching athlete/event pairs, etc.) always warn the operator.

## 2. File-by-File Breakdown

### Entry & Navigation Pages
| File | Role |
| --- | --- |
| `index.php` | Landing page with two cards (Public vs Admin), a language toggle (default English), and a gradient hero that scales with the viewport. |
| `public.php` | Public query dashboard styled with `ui.css`, linking to each `*_list.php`, with a “Back to Home” button that preserves the selected language. |
| `admin_dashboard.php` | Authenticated control panel with compact stat cards plus a 3×2 grid of module links, along with buttons for Home and Logout. |
| `admin.html` | Legacy file that simply redirects visitors to `admin_login.php` to ensure the login wall is respected. |

### Public Query Interfaces (`*_list.php`)
Each file: creates a SQLite connection, reads GET filters, executes queries via prepared statements, and renders responsive tables reusing the shared card/table styles.

| File | Key Functionality | Notes |
| --- | --- | --- |
| `athlete_list.php` | Search by athlete ID or name (supports LIKE). Lists ID, name, age, gender, delegation with consistent card layout. | Prepared statements, zero-result messaging, shared button styles. |
| `delegation_list.php` | Search by delegation ID/region/address. Lists base info. | Uses shared filter form + card subtitle to display the active keyword. |
| `category_list.php` | Search categories by ID, name, or manager. | Same layout as other lists with total count text under the table. |
| `event_list.php` | Lists events joined with category names; filters by event attributes or category. | JOIN on `Category` table and dynamic WHERE clause, plus new action buttons. |
| `participation_list.php` | Joins `Participation`, `Athlete`, and `Event`. Supports keyword search plus medal filtering (Gold/Silver/Bronze/None). | Medal badges now use reusable `.badge-*` classes defined in `ui.css`; debug tooling unchanged. |

### Admin CRUD Modules
Each admin page follows a pattern: handle DELETE via GET, ADD via POST, optional UPDATE via POST, then render the list plus forms. Each entity has a dedicated edit page (`admin_*_edit.php`) which loads a single record and posts back to the main page.

| Entity | Main File | Edit Form | Highlights |
| --- | --- | --- | --- |
| Delegation | `admin_delegation.php` | `admin_delegation_edit.php` | Add/update/delete fully migrated to prepared statements and the new UI shell. |
| Category | `admin_category.php` | `admin_category_edit.php` | Same treatment as delegations, including inline validation and shared styles. |
| Event | `admin_event.php` | `admin_event_edit.php` | Prepared statements for add/update/delete, category dropdown cached, and consistent card layout. |
| Athlete | `admin_athlete.php` | `admin_athlete_edit.php` | Update branch is now wired into the main page; helpers validate age/gender/delegation, and edit form uses secure lookups. |
| Participation | `admin_participation.php` | `admin_participation_edit.php` | Shares the unified UI/forms/prepared statements, enforces required score/time fields, normalizes medals, applies the unique index, and shows toast/confirmation prompts for every change. |

### Security & Authentication Layer
| File | Purpose | Status |
| --- | --- | --- |
| `admin_login.php` | Handles credential checks, seeds default admin (`admin/12345678`), enforces form hints, and now inherits shared styling. | ✅ Live |
| `admin_dashboard.php` | Displays live stats + 3×2 module cards, shows signed-in user, and offers home/logout (with confirm). | ✅ Live |
| `auth_guard.php` | Lightweight middleware included by every `admin_*.php` page to enforce active sessions. | ✅ Live |
| `admin_security.php` | Helper to create the `AdminUsers` table, seed credentials, and stamp timestamps. | ✅ Live |
| `admin_users.php` | Manages admin accounts with duplicate detection and password policy checks. | ✅ Live |
| `logout.php` | Clears the session and sends the user back to the login page. | ✅ Live |
| `admin.html` | Redirect shim to `admin_login.php`. | Legacy |
| `db_schema.php` | Normalizes medal values and creates the unique index that protects Participation data integrity. | ✅ Live |

### Participation Module Details
- Primary and edit pages now share the unified `app-shell` layout; every mutation uses prepared statements inside transactions (updates delete the old record before inserting the new one).
- Score/time fields are required, medals are mapped to `Gold/Silver/Bronze/None`, and `db_schema.php` enforces the `idx_participation_unique` index to prevent duplicates.
- All add/update/delete flows enqueue session flashes before redirecting so the UI always shows toast notifications, and confirmation prompts guard athlete/event switches or deletions.

### Miscellaneous
- `data.db`: SQLite database storing all entities. Not opened here but schemas inferred from SQL usage.
- `docs/`: Previously empty; now hosts this overview. Future reports should live here per user request.

## 3. Data Relationships (Inferred)
- `Delegation (Delegation_id, Region, Address)` provides regions for athletes.
- `Athlete (Athlete_id, Name, Age, Gender, DelegationID)` references delegations.
- `Category (Category_id, Category_name, Manager)` groups events.
- `Event (EventID, CategoryID, EventName, Level)` references categories.
- `Participation (AthleteID, EventID, Time, Medal)` references both athlete and event; Medal may be NULL/empty.

## 4. Navigation & User Journeys
1. **Visitors:** `index.php → public.php → {athlete|delegation|category|event|participation}_list.php` (language preference persists throughout the journey). All read-only.
2. **Admins:** `index.php → admin_login.php → admin_* pages`. Each page offers inline forms for create/delete and links to edit pages for updates, plus embedded language toggles.
3. **Edit Flow:** `admin_*` list → click “Edit” → `admin_*_edit.php` pre-populates form → POST back to main file for processing → redirect to list.

## 5. Current Gaps & Observations
- Participation admin UI is still using ad-hoc scripts and unprepared SQL; refactor to the new helpers is the top CRUD priority.
- Some modules (events, delegations, categories, athletes) already adopted the shared header/footer; participation + admin user pages should follow to keep UX uniform.
- Database dictionary / ERD is not yet documented—needed for the final report.
- Tests/linters are manual; lightweight regression scripts could help (even basic `php -l`).
- Deployment instructions still assume local XAMPP; documenting containerized or portable setup could aid grading.

## 6. Immediate Next Steps (Suggested)
1. Produce a formal data dictionary / ER diagram under `docs/` and reference it from the README for grading.
2. Extract common layout pieces (or introduce a tiny `layout.php`) so `admin_users.php` and other CRUD pages reuse consistent components.
3. Add persistent operation auditing (file or DB logs) in addition to toast feedback for sensitive actions.
4. Ship one-click scripts for initialization/backup (batch `php -l`, SQLite dump/restore) to simplify regression runs.
5. Enrich the public portal with medal leaderboards or visualizations to showcase statistics.

Refer back to this document whenever you need the big picture or want to onboard a teammate quickly. All future reports should also live under `docs/` per project guidelines.
