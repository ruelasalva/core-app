# Sales Module

## Purpose

The sales module manages the operational flow:

```text
Quote -> Order -> Delivery -> Invoice -> Collection
```

## Controller

Main controller:

- `fuel/app/classes/controller/admin/sales.php`

Main actions:

- `action_index`
- `action_create`
- `action_data`
- `action_product_search`
- `action_create_quote`
- `action_update_status`
- `action_close_prequote`
- `action_create_order_from_quote`
- `action_create_delivery_from_order`

## Services

Read services:

- `Service_Core_Sales_ReadModel`
- `Service_Core_Sales_Catalog`

ReadModel responsibilities:

- Dashboard data.
- Quotes.
- Quote items.
- Orders.
- Order items.
- Deliveries.
- Stats.
- Options.
- Scope filtering.

Catalog responsibilities:

- Product search.
- Product price ranges.
- Media URL resolution.

## Endpoints

Admin endpoints:

- `admin/sales`
- `admin/sales/data`
- `admin/sales/product_search`
- `admin/sales/create_quote`
- `admin/sales/update_status`
- `admin/sales/close_prequote`
- `admin/sales/create_order_from_quote`
- `admin/sales/create_delivery_from_order`

Billing integration:

- `admin/billing/create_from_delivery`

## Quote Flow

1. User creates quote.
2. Quote can be price quote or prequote/catalog mode.
3. Products are selected from the catalog.
4. Quote can be approved.
5. Approved quote can become sales order.

Key tables:

- `core_sales_quotes`
- `core_sales_quote_items`

## Order Flow

1. Quote approval leads to order creation.
2. Order keeps pending and delivered quantities.
3. Order can be partially fulfilled.
4. Remaining quantity becomes backorder.

Key tables:

- `core_sales_orders`
- `core_sales_order_items`

## Delivery Flow

1. Order is fulfilled from one warehouse.
2. Delivery affects inventory.
3. Partial deliveries are allowed.
4. Negative inventory depends on configuration.

Key tables:

- `core_sales_deliveries`
- `core_sales_delivery_items`
- `core_inventory_movements`
- `core_inventory_stock_balances`

## Billing Integration

Delivery can be converted into invoice through billing.

Expected flow:

```text
Delivery -> Billing invoice draft -> Stamp if fiscal flow applies
```

Key tables:

- `core_billing_invoices`
- `core_billing_invoice_items`

## Permissions

Observed permission scope:

- `sales.access[view]`
- `sales.access[create]`
- `sales.access[edit]`

Related permissions:

- `billing.access[view]`
- `billing.access[create]`

## Vue Structure

View directory:

- `fuel/app/views/admin/sales`

Important partials:

- `_quotes_table.php`
- `_orders_table.php`
- `_deliveries_table.php`
- `_quote_form_modal.php`
- `_product_capture.php`
- `_catalog_capture.php`
- `_fulfillment_modal.php`
- `_scripts.php`

## Known Risks

High:

- `Controller_Admin_Sales` still contains write flow and inventory logic.
- Inventory delivery logic should move to service before adding more warehouse rules.

Medium:

- `_scripts.php` is still large.
- Product price calculation remains in controller because it affects quote writes.

Low:

- View split is complete enough for current maintenance.

## Future Improvements

1. Extract `Service_Core_Sales_Quote`.
2. Extract `Service_Core_Sales_Order`.
3. Extract `Service_Core_Sales_Delivery`.
4. Move inventory output to inventory service.
5. Add automated validation for quote to order to delivery to billing flow.

