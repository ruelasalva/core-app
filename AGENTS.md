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
