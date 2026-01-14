# frontend

This template should help get you started developing with Vue 2 in Vite.

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
  - [Turn on Custom Object Formatter in Chrome DevTools](http://bit.ly/object-formatters)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)
  - [Turn on Custom Object Formatter in Firefox DevTools](https://fxdx.dev/firefox-devtools-custom-object-formatters/)

## Type Support for `.vue` Imports in TS

TypeScript cannot handle type information for `.vue` imports by default, so we replace the `tsc` CLI with `vue-tsc` for type checking. In editors, we need [Volar](https://marketplace.visualstudio.com/items?itemName=Vue.volar) to make the TypeScript language service aware of `.vue` types.

## Customize configuration

See [Vite Configuration Reference](https://vite.dev/config/).

## Project Setup

```sh
npm install
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

#### Dev proxy (PHP backend + cookies)

The Vite dev server proxies requests to the PHP app under `/bookcatalog`.
This keeps session cookies working during local development and avoids proxying
the built assets.

```
GET http://localhost:5173/bookcatalog/...  ->  http://bookcatalogv2.local/bookcatalog/...
```

#### Local PHP host setup

Make sure your local web server serves the PHP app at `http://bookcatalogv2.local/bookcatalog/`.
Typical steps:

- Add a hosts entry: `127.0.0.1 bookcatalogv2.local`
- Configure your web server/vhost to point the `/bookcatalog` path at the project `public/` directory

### Type-Check, Compile and Minify for Production

```sh
npm run build
```

#### Production deploy notes

- Vite builds to `public/dist/`.
- The app is served from the site root, so asset URLs are `/dist/assets/...`.
- Apache should serve `/var/www/bookcatalog2/public/index.php` (or `dist/index.html`)
  at `/`, with `/dist/` and `/uploads/` excluded from rewrites.

### Run Unit Tests with [Vitest](https://vitest.dev/)

```sh
npm run test:unit
```

### Run End-to-End Tests with [Playwright](https://playwright.dev)

```sh
# Install browsers for the first run
npx playwright install

# When testing on CI, must build the project first
npm run build

# Runs the end-to-end tests
npm run test:e2e
# Runs the tests only on Chromium
npm run test:e2e -- --project=chromium
# Runs the tests of a specific file
npm run test:e2e -- tests/example.spec.ts
# Runs the tests in debug mode
npm run test:e2e -- --debug
```

### Lint with [ESLint](https://eslint.org/)

```sh
npm run lint
```
