# Technical Debt

## Scope

This document lists architecture debt observed after recent refactors.

## Large Controllers

High priority:

- `Controller_Admin_Cfdi`: large controller with import, conversion, mappings and materialization.
- `Controller_Admin_Fiscal`: large controller with dashboard, ledger, VAT, DIOT, REP audit and closing data.
- `Controller_Admin_Sales`: read logic partially extracted, but write flow and inventory logic remain.

Medium priority:

- `Controller_Admin_Purchases`: purchase orders, authorization, invoices, receipts and documents remain mixed.
- `Controller_Admin_Billing`: invoice, recurring billing, REP and stamping flows remain broad.
- `Controller_Admin_Sat`: credential handling, catalog sync and download operations still share one controller.
- `Controller_Admin_Accounting`: chart, posting, periods and fiscal mappings remain together.

## Remaining Service Extractions

Sales:

- `Service_Core_Sales_Quote`
- `Service_Core_Sales_Order`
- `Service_Core_Sales_Delivery`
- `Service_Core_Sales_InventoryBridge`

Purchases:

- `Service_Core_Purchases_Order`
- `Service_Core_Purchases_Authorization`
- `Service_Core_Purchases_Invoice`
- `Service_Core_Purchases_Receipt`
- `Service_Core_Purchases_Documents`

Billing:

- `Service_Core_Billing_Invoice`
- `Service_Core_Billing_Recurring`
- `Service_Core_Billing_Stamping`
- `Service_Core_Billing_Cancellation`
- `Service_Core_Billing_Rep`

Fiscal:

- `Service_Core_Fiscal_Dashboard`
- `Service_Core_Fiscal_Closing`
- `Service_Core_Fiscal_Validation`

Documents:

- `Service_Core_Documents_Manager`
- `Service_Core_Documents_DownloadGuard`

## Remaining View Refactors

High priority:

- Review portal supplier purchase document views for path exposure.

Medium priority:

- Split large `_scripts.php` files after behavior is stable:
  - `admin/sales/_scripts.php`
  - `admin/contracts/_scripts.php`
  - `admin/frontend/_scripts.php`
  - `admin/supplierimport/index/_scripts.php`

Low priority:

- Normalize table export controls across admin list views.

## Permissions Debt

High priority:

- Complete granular enforcement in critical controllers.
- Remove temporary fallback permissions after seed and role validation.

Medium priority:

- Add role test matrix:
  - Super admin.
  - Gerente comercial.
  - Fiscal.
  - Contabilidad.
  - Compras.
  - Almacen.

## Security Debt

High priority:

- Remove any JSON/UI exposure of document `file_path`.
- Review SAT credential response fields.

Medium priority:

- Add security checklist for every portal endpoint.
- Add download endpoint tests.

## Roadmap

First month:

1. Fix remaining document exposure risks.
2. Extract purchase authorization service.
3. Extract sales delivery/inventory service.

Second month:

1. Extract billing stamping/REP services.
2. Extract fiscal closing service.
3. Add role validation checklist.

Third month:

1. Split large Vue scripts where stable.
2. Add automated smoke tests for ERP flows.
3. Complete documentation for payments, inventory and accounting.

