# Database

The MVP uses **MySQL 8 / MariaDB 10.4** with PDO prepared statements.

| File | Purpose |
|---|---|
| `schema.sql` | 8 tables: `account`, `otp_challenge`, `area`, `job_category`, `worker_profile`, `employer_profile`, `job`, `application` |
| `seed.sql` | Demo areas, categories, accounts, jobs and applications |

This is a deliberate subset of the mobile product's 45-table schema. The
remaining tables (KYC, attendance, earnings, payments, ratings, complaints,
notifications, audit) can be imported later from
`C:\Users\kathi\WorkBridge\Backend\prisma\migrations\`.

## Option A — command line (recommended)

```powershell
C:\xampp\php\php.exe bin\setup.php
```

## Option B — phpMyAdmin

1. Open <http://localhost/phpmyadmin>.
2. **Databases** → create `swon_web`, collation `utf8mb4_unicode_ci`.
3. Select `swon_web` → **Import** → choose `schema.sql` → **Go**.
4. **Import** again → choose `seed.sql` → **Go**.
5. Confirm 5 accounts, 9 jobs and 7 applications.

## Conventions

- All ids exposed in URLs are opaque `public_id` strings, never numeric ids.
- Money is stored in **paise** (`BIGINT`), never floats.
- Timestamps are server-local (`Asia/Kolkata`); job dates are `DATE` + `TIME`.
- One application per `(job_id, worker_id)` is enforced by a unique key.
- Foreign keys restrict deletes; accounts cascade only to their own profiles.

## Resetting the demo

`bin/setup.php` is idempotent (it never drops tables), so demo edits survive a
re-run. To start from scratch, drop the database in phpMyAdmin and run setup
again:

```sql
DROP DATABASE swon_web;
```

Nothing here touches the original `workbridge_*` databases.
