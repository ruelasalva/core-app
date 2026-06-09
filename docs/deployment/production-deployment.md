# Production Deployment

## Goal

CORE-APP must run locally from `/core-app/public` and in cPanel from domain root without exposing private application folders.

## Localhost Structure

```text
core-app/
  fuel/
  public/
    index.php
    .htaccess
    assets/
    manifest.json
    sw.js
```

Local URL:

```text
http://localhost/core-app/public
```

Development config:

```text
base_url = /core-app/public/
index_file = false
asset path = /core-app/public/assets/
```

## cPanel Structure

```text
home/user/
  fuel/
  public_html/
    index.php
    .htaccess
    assets/
    favicon.ico
    manifest.json
    sw.js
```

Production URL:

```text
https://domain.example/
```

Production config:

```text
base_url = /
index_file = false
asset path = /assets/
```

## public_html Deployment

Upload into `public_html`:

- Contents of `public/`.
- `index.php`.
- `.htaccess`.
- `assets/`.
- `favicon.ico`.
- `manifest.json`.
- `sw.js`.

Do not upload `public/` as a subfolder. Its contents must live directly inside `public_html`.

## fuel Outside Webroot

Upload outside `public_html`:

- `fuel/`
- optional runtime files required by deployment process.

Expected paths from `public_html/index.php`:

- `../fuel/app`
- `../fuel/packages`
- `../fuel/core`

## Required Files

Required public files:

- `index.php`
- `.htaccess`
- `assets/`
- `manifest.json`
- `sw.js`

Required private files:

- `fuel/app`
- `fuel/core`
- `fuel/packages`

## .htaccess Requirements

The public `.htaccess` should:

- Enable rewrite engine.
- Disable directory listing.
- Rewrite non-file and non-directory requests to `index.php`.
- Remove explicit `index.php` from URLs.
- Preserve assets, `sw.js` and `manifest.json` as direct public files.
- Block accidental exposure of private folders and files.

Private examples to block if accidentally copied:

- `fuel/`
- `docs/`
- `.git/`
- `.agents/`
- `.codex/`
- `vendor/`
- `node_modules/`
- `AGENTS.md`
- `composer.json`
- `composer.lock`
- `oil`

## Environment Detection

Priority:

1. `FUEL_ENV` environment variable.
2. Local hosts as development.
3. Public hosts as production.

Recommended cPanel value:

```text
FUEL_ENV=production
```

## Production Checklist

1. Upload `fuel/` outside `public_html`.
2. Upload contents of `public/` into `public_html`.
3. Confirm `public_html/fuel` does not exist.
4. Confirm `public_html/docs` does not exist.
5. Confirm `public_html/assets` exists.
6. Confirm `FUEL_ENV=production` if possible.
7. Test `/`.
8. Test `/admin`.
9. Test `/assets/js/vue.min.js`.
10. Test `/assets/js/jquery.min.js`.
11. Test `/assets/css/adminlte.min.css`.
12. Confirm `/fuel`, `/docs`, `/AGENTS.md`, `/composer.json` are blocked.
13. Confirm login works.
14. Review latest logs after first access.

