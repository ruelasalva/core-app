# CORE-APP ERP

## Project Overview

CORE-APP ERP is a modular ERP system built with FuelPHP and Vue.js.

CORE-APP ERP is not multitenant by default.
It is installed for one active company at a time.

Fiscal RFC resolution must use the active company configuration first.
SAT credentials are fallback.
Manual RFC input is only for debugging or admin override.

USER INTERFACE LANGUAGE

CORE-APP ERP is a Spanish-language ERP.

All user-facing interface elements must be written in Spanish:

- Menus
- Tabs
- Labels
- Buttons
- Alerts
- Notifications
- Dashboard cards
- Help text

English is reserved for source code only.

The system supports:

* Administration Portal
* Customer Portal
* Supplier Portal
* Reseller Portal
* Partner Portal

The ERP includes:

* Product Catalog
* Inventory
* Purchasing
* Sales
* CRM
* Helpdesk
* SAT Integration
* CFDI
* Accounting
* Accounts Receivable
* Accounts Payable
* Treasury
* Budget Control
* Human Resources
* Calendar
* Knowledge Base
* Dashboard
* Commerce Frontend

---

## Backend Stack

Framework:
FuelPHP 1.9

Authentication:
ORMAuth

Database:
MySQL

Pattern:
MVC

---

## Frontend Stack

Vue.js 2.7.16

AdminLTE

Bootstrap

jQuery

DataTables

Chart.js

CKEditor 5

CodeMirror

GrapesJS

FullCalendar

---

## Folder Structure

Controllers:
fuel/app/classes/controller

Models:
fuel/app/classes/model

Views:
fuel/app/views

---

## User Access Model

Authentication is handled using ORMAuth.

Permissions are action based.

Examples:

users.view

users.create

users.update

users.delete

products.view

products.create

products.update

products.delete

purchases.approve

billing.cfdi.create

billing.cfdi.cancel

sat.download

sat.validate

---

## Main Portals

admin

clientes

proveedores

revendedores

socios

Each portal may have independent menus, permissions and dashboards.

---

## Database Conventions

ERP tables use the prefix:

core_

Examples:

core_companies

core_parties

core_commerce_products

core_purchase_orders

core_sales_quotes

core_billing_invoices

core_sat_cfdi

core_accounting_accounts

---

## Coding Rules

Always generate complete files.

Always include logging.

Always validate permissions.

Always validate user input.

Always respect existing architecture.

Do not replace FuelPHP components with third-party frameworks.

Do not generate Laravel code.

Do not generate Vue 3 code.

Do not introduce breaking architectural changes.

---

## Logging Standard

Use:

Log::info()

Log::warning()

Log::error()

for all critical operations.

---

## JSON Response Standard

{
"success": true,
"message": "",
"data": {},
"errors": []
}

---

## Migration Standard

All schema changes must be generated as Oil migrations.

Never modify existing production tables directly without migrations.

Always provide rollback support.

---

## Development Goal

Maintain a scalable modular ERP platform with long-term compatibility and minimal architectural disruption.

## Production Readiness Rules

CORE-APP ERP is close to production.

All changes must be safe, traceable and documented.

Before editing files, Codex must provide:

1. Technical analysis.
2. Impacted files.
3. Impacted database tables.
4. Impacted business processes.
5. Migration requirements.
6. Data repair requirements.
7. Testing checklist.
8. Risk list.

Codex must not commit or push unless explicitly requested.

---

## Critical Business Modules

The following modules are critical:

- SAT / CFDI
- Billing
- Accounts Receivable
- Accounts Payable
- Payments
- Bank Reconciliation
- Purchases
- Sales
- Inventory
- Accounting
- Treasury

Changes in these modules must be implemented in small phases.

Never change balances, payments, invoices, CFDI links or reconciliation logic without explaining the impact first.

---

## Documentation Standard

Every new module or major change must include documentation.

Documentation must explain:

- Purpose
- Business flow
- Database tables
- Controllers
- Models
- Views
- Permissions
- Commands / tasks
- Common errors
- Repair processes
- Testing checklist

Documentation files should be stored in:

docs/

Recommended structure:

docs/modules/
docs/database/
docs/permissions/
docs/business-flows/
docs/maintenance/
docs/testing/

---

## Testing Standard

For every critical feature, include a test checklist.

Minimum checklist:

- Syntax validation
- Migration validation
- Permission validation
- Empty database scenario
- Existing data scenario
- Duplicate data scenario
- Error handling
- User interface validation
- Audit log validation

---

## Data Repair Rules

If a change affects existing imported data, create a reusable Oil task.

Example:

php oil refine repaircfdisaldos

Repair tasks must:

- Be safe to run multiple times.
- Avoid duplicates.
- Log actions.
- Report totals.
- Explain what was repaired.

