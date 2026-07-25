# CORE-APP ERP

## Project Overview

CORE-APP ERP is a modular enterprise platform built with FuelPHP.

The platform includes:

- ERP
- CRM
- SAT Integration
- CFDI Management
- Fiscal Compliance
- Accounting
- Treasury
- Accounts Receivable
- Accounts Payable
- Purchasing
- Sales
- Inventory
- Human Resources
- Contracts Management
- Document Management
- Helpdesk
- Knowledge Base
- Customer Portal
- Supplier Portal
- Reseller Portal
- Partner Portal
- Commerce Frontend
- CMS
- Web Conversion Tracking

CORE-APP is designed for long-term maintainability, scalability and business continuity.

The system is not multitenant by default.

One active company is managed per installation.

---

# User Interface Language

CORE-APP ERP is a Spanish-language application.

All user-facing elements must be written in Spanish:

- Menus
- Labels
- Buttons
- Tabs
- Notifications
- Alerts
- Dashboard cards
- Help text
- Validation messages

English is reserved for:

- Source code
- Classes
- Methods
- Variables
- Services
- Documentation

---

# Technology Stack

## Backend

Framework:
FuelPHP 1.9.x

Authentication:
ORMAuth

Database:
MySQL

Architecture:
MVC + Services

---

## Frontend

Vue.js 2.7.x

Options API only.

Libraries:

- Bootstrap
- AdminLTE
- jQuery
- DataTables
- Chart.js
- CKEditor 5
- GrapesJS
- FullCalendar
- CodeMirror

Do not generate:

- Vue 3
- Composition API
- Nuxt
- Laravel
- React
- Angular

unless explicitly requested.

---

# Folder Structure

Controllers

fuel/app/classes/controller

Models

fuel/app/classes/model

Services

fuel/app/classes/service/core

Views

fuel/app/views

Tasks

fuel/app/tasks

Migrations

fuel/app/migrations

Documentation

docs/

---

# Human Maintainability Rules

Human maintainability is a first-class requirement.

The ERP must remain understandable by future developers.

Before introducing abstractions:

- Prefer readability.
- Prefer consistency.
- Prefer existing architecture.

A developer must be able to locate a feature in less than two minutes.

Controllers should remain thin.

Business logic belongs in Services.

Avoid placing large business processes directly inside controllers.

Avoid duplicating logic across modules.

Prefer reusable services.

---

# Service Architecture

Business logic belongs inside:

fuel/app/classes/service/core

Examples:

service/core/sat
service/core/fiscal
service/core/accounting
service/core/payments
service/core/contracts
service/core/purchases
service/core/sales
service/core/inventory
service/core/helpdesk
service/core/crm

Controllers should orchestrate.

Services should execute business rules.

---

# Database Conventions

ERP tables use prefix:

core_

Examples:

core_companies
core_parties
core_documents
core_sales_quotes
core_sales_orders
core_purchase_orders
core_billing_invoices
core_payments
core_sat_cfdi
core_accounting_accounts
core_contracts

Never create duplicate tables for existing business entities.

Always reuse existing structures when possible.

---

# Authentication and Permissions

Authentication uses ORMAuth.

Permissions are action based.

Examples:

users.access[view]
users.access[create]
users.access[edit]
users.access[delete]

sales.access[view]
sales.access[create]
sales.access[edit]

contracts.access[view]
contracts.access[create]
contracts.access[edit]

sat.access[view]
sat.access[download]

Never bypass permission validation.

All administrative actions must validate permissions.

---

# Portal Security Rules

Applicable to:

- clientes
- proveedores
- revendedores
- socios

Always use:

$this->portal_link->party_id

Never accept:

party_id
customer_id
supplier_id

from portal requests.

Portal ownership must always be validated.

Every portal query must validate:

- active portal link
- active party
- allowed party type
- portal ownership

before returning data.

---

# Document Security Rules

Never expose:

- file_path
- storage_path
- physical paths
- upload directories

Public downloads must use controlled endpoints.

Every document download must validate:

- authenticated user
- active ownership
- active document
- active relationship

before returning a file.

---

# Mandatory Production Security Requirements

## Session and Cookies

- Session cookie must be HttpOnly.
- Session cookie must use SameSite=Lax or stricter.
- Session cookie must be Secure in production.
- Do not weaken fuel/app/config/session.php.
- Do not introduce persistent login cookies without explicit approval.

## HTTPS and Headers

- Production must redirect HTTP to HTTPS.
- Keep security headers in public/.htaccess:
  - X-Content-Type-Options
  - X-Frame-Options
  - Referrer-Policy
  - Permissions-Policy
- Do not add CSP enforcement without report-only testing first.
- Do not add HSTS until HTTPS is validated in production.

## Document Security

- Never expose file_path, storage_path or physical paths in portal/admin JSON.
- Use download_url for user-facing downloads.
- All downloads must validate:
  - logged-in user
  - permission or portal session
  - ownership
  - active document
  - active relation/link
- Direct access to uploaded private documents must remain blocked.

## Portal Ownership

- Never trust party_id from request in portals.
- Always derive portal ownership from portal_link->party_id.
- Customer A must never access Customer B data.
- Supplier A must never access Supplier B data.

## Admin Permissions

- Every admin controller action must have permission validation.
- Menu visibility must match backend permissions.
- Group 100 may have bypass, but non-super roles must work through Auth::has_access().
- users_permissions.actions must remain associative maps:
  - Correct: ['view' => 'view']
  - Incorrect: ['view']
- Any permission seed/task must preserve ORMAuth-compatible action format.

## Public Frontend Security

- Do not expose costs, margins or internal pricing.
- Do not expose internal IDs unless already public and safe.
- Public price/customer price rules must be explicit and reviewed.

## CORS

- Do not add Access-Control-Allow-Origin: * to authenticated routes.
- Any CORS change requires explicit review.

## Validation Required After Security-Affecting Changes

- php -l modified PHP files.
- curl -I for headers.
- Check Set-Cookie flags.
- Check /docs and /AGENTS.md blocked.
- Check /assets/uploads/documents direct access blocked.
- Check portal cross-access where applicable.

## Production Deployment

- public/ is the only webroot.
- fuel/, docs/, AGENTS.md, migrations, tasks and config must not be publicly accessible.
- If using cPanel public_html, copy contents of public/ into public_html and keep fuel/ outside webroot.

## Reporting

Every implementation summary must state:

- whether security-sensitive files were modified
- whether file_path/storage_path exposure was checked
- whether permissions were changed
- whether new endpoints were protected

---

# ERP Workflow Rules

## Sales

Quote
→ Order
→ Delivery
→ Invoice
→ Collection

## Purchasing

Request
→ Approval
→ Purchase Order
→ Receipt
→ Supplier Invoice
→ Counter Receipt
→ Payment

## Helpdesk

Ticket
→ Assignment
→ Resolution
→ Closure

## Contracts

Contract
→ Documents
→ Relations
→ Events
→ Expiration
→ Renewal

---

# SAT Rules

SAT modules are critical.

Includes:

- CFDI
- REP
- DIOT
- Fiscal Ledger
- SAT Downloads
- Validation

Before modifying SAT logic:

Provide:

1. Technical analysis
2. Business impact
3. Affected tables
4. Risks
5. Testing plan

Never change SAT behavior blindly.

---

# Accounting Rules

Accounting modules are critical.

Includes:

- Journal Entries
- Fiscal Ledger
- Accounts Receivable
- Accounts Payable
- Treasury
- Tax Calculations

Never modify:

- balances
- reconciliations
- allocations
- accounting postings

without impact analysis.

---

# Contracts Rules

Contracts support:

- Customers
- Suppliers
- Employees
- Partners
- Resellers

Documents must use:

core_documents
core_document_links

Relations must use:

core_contract_relations

Events must use:

core_contract_events

Never create parallel contract systems.

---

# Frontend Rules

Frontend must remain commercially focused.

Priority:

1. Lead generation
2. Product discovery
3. Conversion
4. Contact acquisition

Frontend CMS must remain editable by non-technical users.

Avoid requiring code changes for content updates.

---

# View Structure Rules

Small modules:

index.php

Medium modules:

index.php
_list.php
_detail.php
_modals.php
_scripts.php

Large modules:

index.php
_tabs.php
_documents.php
_relations.php
_events.php
_scripts.php

Avoid extremely large view files whenever practical.

---

# Logging Standard

Use:

Log::info()
Log::warning()
Log::error()

for critical operations.

Log:

- creation
- updates
- approvals
- cancellations
- imports
- fiscal actions

when applicable.

---

# JSON Response Standard

{
    "success": true,
    "message": "",
    "data": {},
    "errors": []
}

All AJAX endpoints should follow this structure.

---

# Migration Standard

Schema changes must use Oil migrations.

Never modify production schema manually.

Every migration must support rollback.

If a migration was already executed:

Create a new corrective migration.

Never modify old executed migrations.

---

# Repair Tasks

When existing data may require correction:

Create reusable Oil tasks.

Example:

php oil refine repaircfdisaldos

Tasks must:

- be idempotent
- avoid duplicates
- log actions
- report totals
- explain repairs

---

# Documentation Standard

Every major module must include documentation.

Store documentation in:

docs/

Recommended structure:

docs/modules/
docs/database/
docs/business-flows/
docs/permissions/
docs/testing/
docs/maintenance/

Documentation should include:

- Purpose
- Business Flow
- Controllers
- Models
- Services
- Views
- Tables
- Permissions
- Common Errors
- Repair Procedures
- Testing Checklist

---

# Testing Standard

For critical features include:

- Syntax validation
- Permission validation
- Empty data scenario
- Existing data scenario
- Duplicate data scenario
- Error handling
- UI validation
- Audit validation

---

# Production Readiness Rules

CORE-APP ERP is production-oriented.

Before modifying files, always provide:

1. Technical analysis
2. Impacted files
3. Impacted database tables
4. Business impact
5. Migration requirements
6. Data repair requirements
7. Testing checklist
8. Risk list

Do not implement immediately.

Wait for approval before modifying files.

---

# Commit Rules

Never commit automatically.

Never push automatically.

Wait for explicit approval.

---

# Final Rule

When architecture and maintainability conflict:

Prefer the solution that remains understandable for future developers while preserving ERP integrity, security and long-term maintainability.
```
Documentation Security Rule:

Technical documentation must be stored outside DOCROOT/public.

Recommended location:
docs/

Never store technical documentation under:
public/
public/docs/
public/assets/docs/

Documentation must not include:
- passwords
- API keys
- SAT private keys
- PAC credentials
- database credentials
- real customer personal data
- real RFC certificates
- production tokens
- server secrets

If the production server exposes the project root instead of only public/, docs/ must be excluded from deployment or blocked by web server rules.
Add a mandatory section:

"Documentation and Environment Safety Requirements"

Include these rules:

1. Help documentation requirement
Whenever a task creates or significantly modifies a user-facing module, admin screen, portal screen, workflow, configuration area, or operational process, the implementer must evaluate whether internal Help documentation is required.

If required, add or update help content using the existing Help module pattern:
- Prefer idempotent seed tasks when the project stores help articles in DB.
- Prefer docs/help/*.md only when no DB help pattern exists.
- Do not hardcode long help text in controllers.
- Include setup, usage, permissions, troubleshooting and validation.
- Report whether help documentation was added, updated, deferred or not needed.

This applies especially to:
- Communications
- Workspace
- CRM
- Helpdesk
- Sales
- Purchases
- Portals
- SAT/CFDI
- Fiscal
- Security/configuration screens

2. Migration and seed environment safety
Before running any migration, rollback, seed, repair task, or data-changing Oil command, the implementer must explicitly confirm the intended environment.

Rules:
- Never run `php oil refine migrate` without `FUEL_ENV`.
- Never run data-changing Oil tasks without `FUEL_ENV`.
- Use explicit commands:
  - `FUEL_ENV=development php oil refine migrate`
  - `FUEL_ENV=development php oil refine seed...`
  - `FUEL_ENV=production php oil refine ...` only when production is explicitly approved.
- If `FUEL_ENV` is missing, stop and report the exact command that should be used.
- If a command accidentally attempts production/local credentials and fails, do not retry blindly.
- Never modify `fuel/app/config/production/db.php` to make a local command work.
- For Windows PowerShell, use:
  - `$env:FUEL_ENV='development'; php oil refine migrate`
  - `$env:FUEL_ENV='development'; php oil refine seed...`
- For cmd.exe, use:
  - `set FUEL_ENV=development && php oil refine migrate`

3. Reporting requirement
Every implementation report must include:
- Help documentation: added / updated / deferred / not needed.
- Environment used for migrations/seeds/tasks.
- Exact commands run.
- Whether any command failed due to wrong DB credentials.
- Confirmation that no production DB/config was modified unless explicitly approved.

Do not modify PHP, migrations, seeds or docs other than AGENTS.md.
Do not commit.
Do not push.

Return:
- AGENTS.md sections added
- exact rules added
- validation: not applicable or markdown-only

---

# API Client Standard

CORE-APP uses a single API client for frontend communication.

Never call:

response.json()
res.json()

directly.

Always use:

CoreApiClient

Reasons:

- Detect login HTML.
- Detect PHP fatal pages.
- Detect HTML 404 responses.
- Normalize JSON responses.
- Normalize CSRF handling.
- Normalize authentication errors.
- Prevent infinite loading states.

If CoreApiClient already solves the problem,
reuse it.

Do not duplicate JSON parsers.

Do not create module-specific fetch wrappers unless explicitly approved.
---

# JSON Endpoint Standard

All AJAX endpoints must always return JSON.

Never return HTML.

Never redirect to login.

Use controlled responses.

Authentication:

401
auth_required

Authorization:

403
permission_denied

Missing endpoint:

404
endpoint_not_found

Unexpected server error:

500
internal_error

Every JSON endpoint must:

- validate session
- validate permissions
- validate CSRF when applicable
- return the standard JSON structure

Never depend on HTML redirects.
---

# Frontend Async Standard

Every asynchronous operation must use:

try

catch

finally

Loading indicators must always be cleared inside finally.

Never leave the interface permanently loading.

Never render raw HTML returned by failed requests.

Display controlled error messages.

All frontend AJAX modules must use CoreApiClient.
---

# Module Architecture Standard

Large modules should follow a common architecture.

Prefer:

Controller

↓

Service

↓

Model

↓

View

↓

CoreApiClient

Do not duplicate:

- AJAX helpers
- JSON parsers
- business logic
- permission validation

Business rules belong inside Services.
---

# Embedded Panel Standard

Embedded panels are reusable read-only components.

Examples:

CRM

Helpdesk

Sales

Purchases

Portals

Communications

Embedded panels must:

- reuse Services
- reuse CoreApiClient
- reuse permission validation
- avoid duplicated mailbox logic

Do not create independent mailbox implementations.

Use:

MailboxAccess

ConversationManager

MessageStore

EmbeddedPanel

Communications endpoints.
---

# Event Bus Standard

Business modules must never send emails directly.

Business modules must never create notifications directly.

Always use:

Helper_Core_Event::fire()

The Event Bus decides:

- notifications
- email
- automation
- workflows
- future integrations

Controllers must not know how messages are delivered.

Never couple ERP modules to email providers.
---

# Queue Standard

Every queue processor must support:

pending

processing

sent

failed

retry

stale recovery

diagnostics

Workers must be restart-safe.

Never leave processing rows permanently locked.

Provide repair tasks whenever recovery may be required.
---

# Communications Standard

Communications is the central messaging platform.

The following modules must never implement independent email logic:

CRM

Helpdesk

Sales

Purchases

Contracts

Portals

Password Reset

Notifications

Event Bus

Use:

Communications Manager

Email Manager

Notification Manager

Provider Factory

MailboxAccess

MessageStore

ConversationManager
---

# IMAP Standard

IMAP is used only for:

incoming messages

conversation synchronization

sent synchronization

history

message recovery

Never use IMAP to send email.

Outgoing email belongs to SMTP or API providers.

IMAP synchronization must never block ERP operations.

---

# Message Store Standard

Every message belongs to:

Conversation

↓

Account

↓

ERP Entity (optional)

Attachments must use:

storage_ref

Never:

file_path

storage_path

physical paths

Only metadata should be exposed.

HTML must always be sanitized before rendering.
---

# Read-Only First Rule

When integrating existing ERP modules:

Phase 1

Read-only

Validation

Preview

Embedded panel

Phase 2

Compose

Reply

Create

Automation

Phase 3

Workflow

Synchronization

Actions

Never begin with destructive operations.
---

# Backward Compatibility

Never replace an existing module immediately.

Prefer:

Adapters

Wrappers

New Services

New Endpoints

New UI

Maintain legacy behavior until migration is complete.

Never break production behavior during refactoring.
---

# UTF-8 Rule

Views must remain UTF-8 without BOM.

Before finishing any sprint verify:

No mojibake.

Search for:

Ã

Â

â€

Replace any corrupted encoding before considering the sprint complete.
---

# Controller Size Rule

Controllers should orchestrate.

Controllers should not become business engines.

When a controller grows significantly or mixes multiple business processes:

Move logic into Services.

Prefer:

Thin Controllers

Rich Services

Reusable Models
---

# Reuse First Rule

Before creating:

Service

Model

Migration

Task

Helper

Endpoint

Vue Component

View

Search the project first.

If an equivalent implementation exists:

Reuse it.

Extend it.

Document why it cannot be reused.

Avoid duplicate architecture.
---

# UI Consistency Rule

New modules should follow the AdminLTE visual language.

Avoid oversized hero sections unless justified.

Prefer:

compact cards

consistent spacing

responsive layouts

professional tables

clear empty states

technical inspectors hidden by default

User interfaces must prioritize productivity over decoration.
---

# Help Module Rule

Whenever a production feature becomes usable by end users,
evaluate whether Help documentation should also be created.

Help documentation should explain:

Purpose

Configuration

Permissions

Workflow

Troubleshooting

Validation

Production checklist

Prefer idempotent seed tasks when Help content is stored in the database.
---

# Final Validation Checklist

Before considering any sprint complete verify:

✓ Technical analysis completed

✓ Business impact reviewed

✓ Permissions validated

✓ php -l executed

✓ JSON responses validated

✓ CoreApiClient used

✓ Loading handled with finally

✓ No raw HTML rendered

✓ No file_path/storage_path exposed

✓ UTF-8 verified

✓ Documentation updated

✓ Help article evaluated

✓ Diagnostics task created when applicable

✓ Repair task created when applicable

✓ FUEL_ENV explicitly used

✓ Development environment confirmed

✓ Production configuration untouched unless explicitly approved

✓ Security review completed

✓ No duplicated code introduced

✓ No automatic commit

✓ No automatic push
---

# CORE-APP Engineering Principles

The ERP must evolve without losing consistency.

Always prefer:

One source of truth.

One implementation for each responsibility.

Reusable Services.

Reusable APIs.

Reusable UI components.

Event-driven architecture.

Backward compatibility.

Security by default.

Read-only integration before write operations.

Documentation before deployment.

Long-term maintainability over short-term convenience.

When multiple solutions are technically valid,
choose the one that future developers will understand more easily.