# Purchases Module

## Purpose

The purchases module manages:

```text
Request -> Approval -> Purchase Order -> Receipt -> Supplier Invoice -> Counter Receipt -> Payment
```

Current implementation focuses on purchase orders, invoices, receipts and evidence.

## Controller

Main controller:

- `fuel/app/classes/controller/admin/purchases.php`

Main actions:

- `action_index`
- `action_data`
- `action_save_order`
- `action_submit_order`
- `action_authorize_order`
- `action_reject_order`
- `action_cancel_order`
- `action_close_order`
- `action_save_invoice`
- `action_save_receipt`
- `post_upload_document`
- `action_upload_document`

## Purchase Order Flow

1. User creates purchase order.
2. Order can be submitted.
3. Authorization rules are evaluated.
4. Authorized users approve or reject.
5. Order can be closed or cancelled.

Key tables:

- `core_purchase_orders`
- `core_purchase_order_items`
- `core_purchase_approval_rules`

## Supplier Invoice Flow

1. Supplier invoice is captured or converted from CFDI.
2. Invoice can relate to purchase flow.
3. Invoice supports payment options and fiscal relation.

Key tables:

- `core_purchase_invoices`
- Related CFDI tables when converted from SAT data.

## Receipt Flow

1. Receipt is registered against purchase flow.
2. Receipt items are tracked separately.
3. Inventory integration should be kept controlled and service-based.

Key tables:

- `core_purchase_receipts`
- `core_purchase_receipt_items`

## Evidence Flow

Documents and evidences are attached through:

- `core_documents`
- `core_document_links`

Admin purchase documents return `download_url` and should not expose physical paths in the UI.

Important method:

- `documents()`
- `store_document()`

## Views

View directory:

- `fuel/app/views/admin/purchases`

Partials:

- `_summary.php`
- `_filters.php`
- `_tabs.php`
- `_orders_table.php`
- `_invoices_table.php`
- `_receipts_table.php`
- `_documents_table.php`
- `_order_modal.php`
- `_order_items.php`
- `_invoice_modal.php`
- `_receipt_modal.php`
- `_scripts.php`

## Known Risks

High:

- Purchase authorization logic still lives in controller.
- Document storage logic still lives in controller.

Medium:

- Supplier invoice and receipt logic should move to services.
- Payment relation should be reviewed against treasury/accounts payable.

Low:

- View split is in place and reduces UI maintenance cost.

## Recommended Next Steps

1. Extract `Service_Core_Purchases_Order`.
2. Extract `Service_Core_Purchases_Authorization`.
3. Extract `Service_Core_Purchases_Documents`.
4. Add tests for approval thresholds and department rules.

