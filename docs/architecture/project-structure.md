# CORE-APP Project Structure

## Purpose

This document summarizes the current CORE-APP structure after the latest view and service refactors.

## Main Folders

- `fuel/app/classes/controller`: FuelPHP controllers.
- `fuel/app/classes/model`: ORM models for `core_*` tables.
- `fuel/app/classes/service/core`: business and read services.
- `fuel/app/views`: admin, frontend and portal views.
- `fuel/app/tasks`: Oil tasks.
- `fuel/app/migrations`: database migrations.
- `fuel/app/config`: base and environment configuration.
- `public`: webroot for local development and source for cPanel `public_html`.
- `public/assets`: CSS, JS, vendor assets, uploads and public media.
- `docs`: technical and operational documentation.

## Controllers

Administrative controllers live in `fuel/app/classes/controller/admin`.

Important controllers:

- `sales.php`: quotes, orders, deliveries and sales flow.
- `purchases.php`: purchase orders, supplier invoices, counter receipts and evidence.
- `billing.php`: invoices, recurring billing, stamping and delivery integration.
- `sat.php`: SAT downloads, credentials and SAT catalogs.
- `cfdi.php`: CFDI import, classification and conversion.
- `fiscal.php`: fiscal dashboard, VAT, DIOT preview, ledger and closing center.
- `accounting.php`: chart of accounts, entries, periods and fiscal mappings.
- `documents.php`: controlled admin document downloads and document metadata.
- `frontend.php`: public CMS administration.
- `supplierimport.php`: supplier catalog import and review.
- `contracts.php`: contract lifecycle and document relations.

Portal controllers live under:

- `fuel/app/classes/controller/clientes`
- `fuel/app/classes/controller/proveedores`
- `fuel/app/classes/controller/revendedores`
- `fuel/app/classes/controller/socios`

Portal base logic is concentrated in `portalbase.php` and related portal controllers.

## Services

Services live under `fuel/app/classes/service/core`.

Current service groups:

- `admin/menubuilder.php`: admin menu visibility and permission flags.
- `contracts/manager.php`: contract business support.
- `fiscal/*`: VAT, DIOT preview, ledger detail, REP audit and reconciliation.
- `payments/manager.php`: payment operations.
- `sales/readmodel.php`: read-only sales dashboard data.
- `sales/catalog.php`: read-only product search and media helpers.
- `sat/*`: SAT sync, validation, REP import and SAT catalog sync.
- `sat/cfdi/importer.php`: CFDI XML import.
- `supplierimport/*`: import manager, matcher, normalizer, image manager and product writer.

## Views

Admin views live in `fuel/app/views/admin`.

Large modules now use partials:

- `admin/contracts`
- `admin/frontend`
- `admin/supplierimport`
- `admin/sales`
- `admin/purchases`

Typical view pattern:

- `index.php`: Vue root and partial includes.
- `_summary.php`: KPI cards.
- `_table.php` or module-specific tables.
- `_modals.php`: modal markup.
- `_scripts.php`: Vue 2 Options API logic for phase 1 splits.

## Assets

Public assets live under `public/assets`.

Fuel Asset configuration:

- Development `base_url`: `/core-app/public/`
- Production `base_url`: `/`
- Asset path: `assets/`

Expected local asset URLs:

- `/core-app/public/assets/js/vue.min.js`
- `/core-app/public/assets/js/jquery.min.js`
- `/core-app/public/assets/css/adminlte.min.css`

Expected production asset URLs:

- `/assets/js/vue.min.js`
- `/assets/js/jquery.min.js`
- `/assets/css/adminlte.min.css`

## Public Structure

Local structure:

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

cPanel target structure:

```text
home/user/
  fuel/
  public_html/
    index.php
    .htaccess
    assets/
    manifest.json
    sw.js
```

## Deployment Structure

`public/index.php` resolves:

- `../fuel/app`
- `../fuel/packages`
- `../fuel/core`

This supports both local `core-app/public` and cPanel `public_html`, as long as `fuel/` is a sibling of the webroot.

## Portals

Portal modules include:

- Customer portal.
- Supplier portal.
- Reseller portal.
- Partner portal.

Portal rules:

- Use `$this->portal_link->party_id`.
- Do not trust `party_id` from requests.
- Validate ownership before returning data.

