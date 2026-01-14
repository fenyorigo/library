# BookCatalog Installation

## Requirements
- PHP 8.0+
- PHP extensions: pdo, pdo_mysql, json, mbstring, openssl
- Writable directories: `public/uploads`, `public/user-assets`
- MySQL/MariaDB admin credentials for initial setup

## CLI Install (preferred)
1. From the project root, run:
   ```bash
   php install.php
   ```
2. Follow the prompts to:
   - Connect to MySQL
   - Create or reuse a database
   - (Optional) create a dedicated DB user
   - Create the initial BookCatalog admin user
3. The installer writes `config.php` in the project root.

## Build the SPA
```bash
cd frontend
npm install
npm run build
```

## Web Server
Point your web server to `public/`.

## Post-install checks
- Verify `public/uploads` and `public/user-assets` are writable by the web server.
- Log in with the admin user created during installation.

## Security notes
- Keep `config.php` readable only by the web server user (mode 600 recommended).
- Prefer a dedicated DB user with limited privileges.
- Keep MySQL admin credentials out of `config.php`.
