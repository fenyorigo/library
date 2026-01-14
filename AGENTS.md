# Repository Guidelines

## Project Structure & Module Organization
- `public/` hosts the PHP application (auth, CRUD endpoints, backups) and the web entrypoint `index.php`.
- `frontend/` contains the Vue 2 SPA source; production builds output to `public/dist/`.
- `public/uploads/` stores cover images and thumbnails and is treated as data (always backed up).
- `00-basedata/` contains SQL helpers and operational scripts for rebuilds, image mapping, and QC.
- `downloaded-covers/` is a local staging area for images and is not a build artifact.

## Build, Test, and Development Commands
- `cd frontend && npm install`: install SPA dependencies.
- `cd frontend && npm run serve`: run the Vue dev server for local UI work.
- `cd frontend && npm run build`: build the SPA into `public/dist/`.
- `php public/create_user.php 'name' 'password' admin`: create an admin account (or `reader`).
- `php public/generate_thumbs.php --height=240`: generate cover thumbnails.
- `00-basedata/scripts/run_all.sh full`: full data rebuild + image mapping + QC (pipeline mode).

## Coding Style & Naming Conventions
- PHP: 4-space indentation, `declare(strict_types=1);`, braces on the same line, short helper names in `public/functions.php`.
- PHP variables: use `snake_case`; JSON keys remain `camelCase`.
- JS/Vue: 2-space indentation, double quotes, semicolons; follow ESLint/Prettier rules.
- Filenames and endpoints are lowercase with underscores (e.g., `public/list_books.php`).

## Testing Guidelines
- No automated test suite is present; rely on manual checks and linters.
- Frontend linting: `cd frontend && npm run lint`.
- Manual smoke tests: log in, list books, add/update/delete, upload a cover, export CSV/JSON.

## Commit & Pull Request Guidelines
- Follow the existing commit style: `feat: ...`, `fix: ...`, `docs: ...` (lowercase type + short summary).
- PRs should include a brief summary, testing notes, and UI screenshots for visual changes.
- Link related issues or describe the context if no issue exists.

## Security & Configuration Tips
- `config.php` holds DB credentials; never commit real secrets. Runtime loads from `~/.config/config.php` or `BOOKCATALOG_CONFIG`.
- Ensure the web server can read/write `public/uploads/` and that MySQL `local_infile` is enabled for data loads.
