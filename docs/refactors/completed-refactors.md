# Completed Refactors

## Scope

This document lists completed maintainability refactors after the latest architecture pass.

## Contracts Split

Module:

- `fuel/app/views/admin/contracts`

Created partials:

- `_styles.php`
- `_summary.php`
- `_list.php`
- `_detail.php`
- `_documents.php`
- `_relations.php`
- `_events.php`
- `_modals.php`
- `_scripts.php`

Result:

- `index.php` is now a small Vue root wrapper.
- Contract documents, relations and events are easier to locate.
- Vue remains in `_scripts.php` for phase 1 compatibility.

## Frontend CMS Split

Module:

- `fuel/app/views/admin/frontend`

Created partials:

- `_styles.php`
- `_summary.php`
- `_main_card.php`
- `_toolbar.php`
- `_sections.php`
- `_table.php`
- `_modals.php`
- `_form_fields.php`
- `_section_settings.php`
- `_footer_builder.php`
- `_scripts.php`

Result:

- CMS page, section, menu, footer and theme UI are separated.
- CKEditor and CodeMirror behavior remains in the view script phase.

## Supplier Import Split

Modules:

- `fuel/app/views/admin/supplierimport/index.php`
- `fuel/app/views/admin/supplierimport/review.php`

Index partials:

- `index/_styles.php`
- `index/_header.php`
- `index/_alerts.php`
- `index/_upload_response.php`
- `index/_upload_result.php`
- `index/_summary.php`
- `index/_runs_table.php`
- `index/_staging_table.php`
- `index/_help.php`
- `index/_upload_modal.php`
- `index/_scripts.php`

Review partials:

- `review/_styles.php`
- `review/_header_actions.php`
- `review/_alerts.php`
- `review/_apply_result.php`
- `review/_image_result.php`
- `review/_filters.php`
- `review/_staging_table.php`
- `review/_detail_modal.php`
- `review/_scripts.php`

Result:

- CSV upload, staging review and image download flows are separated.
- Vue remains in partial scripts.

## Sales Split

Module:

- `fuel/app/views/admin/sales`

Created partials:

- `_styles.php`
- `_summary.php`
- `_toolbar.php`
- `_quotes_table.php`
- `_orders_table.php`
- `_deliveries_table.php`
- `_quote_form_modal.php`
- `_quote_header_fields.php`
- `_product_capture.php`
- `_catalog_capture.php`
- `_quote_items_table.php`
- `_quote_detail_modal.php`
- `_fulfillment_modal.php`
- `_scripts.php`

Result:

- Quote, order, delivery and product capture UI is separated.
- `_scripts.php` remains large and should be a later extraction candidate.

## Purchases Split

Module:

- `fuel/app/views/admin/purchases`

Created partials:

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

Result:

- Purchase orders, supplier invoices, receipts and evidence tables are separated.
- Existing payloads and `download_url` usage were preserved.

## Sales ReadModel Extraction

Service:

- `fuel/app/classes/service/core/sales/readmodel.php`

Class:

- `Service_Core_Sales_ReadModel`

Extracted read-only responsibilities:

- Sales dashboard data.
- Quote list and quote items.
- Order list and order items.
- Delivery list.
- Sales stats.
- Select options.
- Commercial scope filtering.

Controller remains responsible for:

- Write actions.
- Quote creation.
- Status changes.
- Order creation.
- Delivery creation.
- Inventory movements.
- Billing integration.

## Sales Catalog Extraction

Service:

- `fuel/app/classes/service/core/sales/catalog.php`

Class:

- `Service_Core_Sales_Catalog`

Extracted read-only responsibilities:

- Product search.
- Price ranges for product capture.
- Media URL helper.

Not moved yet:

- Quote price calculation used for saving.
- Customer price list resolution.
- Product row lookup used by write flow.

## Remaining Refactor Pattern

Recommended next refactors:

1. Move sales write flow into quote, order and delivery services.
2. Move purchases authorization and document storage into services.
3. Split large `_scripts.php` files only after behavior is stable.

