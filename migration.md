# Migration: PHPStorm → Projects

Goal: move the repo from `~/PHPStorm/BookCatalog` to `~/Projects/BookCatalog` with minimal breakage.

## 1) Move the repo

```bash
mkdir -p ~/Projects
mv ~/PHPStorm/BookCatalog ~/Projects/BookCatalog
```

## 2) Update Apache (macOS)

Update your `httpd.conf` or vhost config:

```
DocumentRoot "/Users/bajanp/Projects/BookCatalog/public"
Alias /bookcatalog /Users/bajanp/Projects/BookCatalog/public
```

Restart Apache after changes.

## 3) Update PHP config (if path-dependent)

Check `php.ini` or pool configs for any old path references.

## 4) Update cron jobs, scripts, aliases

Search in your shell config (`~/.zshrc`, `~/.bashrc`, `~/.bash_profile`, etc.)
and any cron files for `/Users/bajanp/PHPStorm/BookCatalog`.

Example cron using the new path:

```
BASE_URL=http://localhost OUTDIR=$HOME/Backups/BookCatalog PROJECT_ROOT=/Users/bajanp/Projects/BookCatalog \
  "$PROJECT_ROOT/00-basedata/scripts/backup_full.sh" >> $HOME/Backups/BookCatalog/backup.log 2>&1
```

## 5) Optional: set a default project root for scripts

The pipeline now reads `PROJECT_ROOT` automatically (from script location),
but you can override it if needed:

```bash
export PROJECT_ROOT=~/Projects/BookCatalog
```

## 6) Quick smoke check

- Run: `00-basedata/scripts/run_all.sh covers`
- Login to the app, list books, upload a cover.

