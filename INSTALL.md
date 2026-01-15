# BookCatalog Installation

## Requirements
- PHP 8.0+
- PHP extensions: pdo, pdo_mysql, json, mbstring, openssl
- Writable directories: `public/uploads`, `public/user-assets`
- MySQL/MariaDB admin credentials for initial setup
- Fedora checklist (if applicable):
  - Web root: `/var/www/library`
  - Apache vhost dir: `/etc/httpd/conf.d/`
  - Ensure SELinux allows Apache to write `public/uploads` and `public/user-assets`

If this is a completely fresh install, jump to the CLI Install section. If the system already exists or has leftover components, do a clean reset first.

## Clean Reset + Restore
Warning: before any reset, ensure you have a backup of `public/uploads/` (covers) and a data export (CSV strongly recommended; SQL dump optional). Restoring from `public/uploads/` after import is what makes covers display correctly.

The quick cleanup path:
```bash
./cleanup.sh
```
Note: `cleanup.sh` uses the current repo path by default. Override with `ROOT=/var/www/library ./cleanup.sh` on Fedora.
Manual cleanup steps (if needed):
1. Drop the local database (default `books`).
2. Remove local data/build/config files:
   - `rm -rf /Users/bajanp/Projects/library/public/uploads`
   - `rm -rf /Users/bajanp/Projects/library/public/user-assets`
   - `rm -rf /Users/bajanp/Projects/library/public/dist`
   - `rm -f /Users/bajanp/Projects/library/config.php`

### Restore options
In case you want to use catalog data from an existing system, please read the restore options before proceeding.
- MariaDB usually accepts Oracle MySQL dumps, but MySQL may reject MariaDB dumps due to stricter FK/type checks.
- Dump restores are safest when source and target use the same database vendor and version.
- If you are crossing vendors, use the CSV import flow below.
Option A: SQL dump restore (same-vendor only)
- Prepare a catalog dump on the existing system using `mysqldump`.
Option B: CSV import (recommended; works across MariaDB/MySQL either direction)
- Do a CSV export as admin on the existing system.

### CLI Install
1. Run the installer to create schema and admin. Follow the required steps and note the catalog admin username and password.
   - `php install.php`
2. Follow the prompts to:
   - Connect to MySQL
   - Create or reuse a database
   - (Optional) create a dedicated DB user
   - Create the initial catalog admin user
3. The installer writes `config.php` in the project root.
4. Point your web server to `public/`.
5. Build the frontend:
   - `cd frontend && npm install && npm run build`
6. Apache config alignment (if using the Apache vhost):
   - `/opt/homebrew/etc/httpd/extra/httpd-library.conf`
   - `SetEnv BOOKCATALOG_CONFIG /Users/bajanp/Projects/library/config.php`
   - On Fedora, use `/etc/httpd/conf.d/` for the vhost and set `DocumentRoot` to `/var/www/library/public`.

## Post-install checks
- Verify `public/uploads` and `public/user-assets` are writable by the web server.
- Log in with the admin user created during installation.

## Restore existing catalog data (optional)
Only needed when you have existing data/backup and are not starting with an empty catalog.

1. Restore the catalog DB content:
   - Option A: import the DB dump into the database.
   - Option B: log in as catalog admin and import the exported CSV into the catalog.
2. Restore the previously prepared covers backup into `public/uploads/`.

## Security notes
- Keep `config.php` readable only by the web server user (mode 600 recommended).
- Prefer a dedicated DB user with limited privileges.
- Keep MySQL admin credentials out of `config.php`.
