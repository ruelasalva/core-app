# Modulo de Ventas

## 1. Proposito de negocio

El modulo de Ventas administra el flujo comercial desde la cotizacion inicial hasta la entrega facturable.

Su objetivo es conectar:

- Cotizaciones comerciales.
- Precotizaciones tipo catalogo.
- Pedidos aprobados por el cliente.
- Entregas desde almacen.
- Facturacion desde entregas.
- Afectacion de inventario.
- Relacion con vendedores y comisiones.
- Operacion offline/local para captura de cotizaciones.

El modulo sigue el flujo ERP definido para ventas:

Cotizacion -> Pedido -> Entrega -> Factura -> Cobranza

En esta etapa el modulo cubre hasta la entrega y la generacion de factura desde entrega. La cobranza queda conectada posteriormente por el modulo de pagos/bancos.

## 2. Flujo de ventas

El flujo principal es:

1. El usuario crea una cotizacion o precotizacion.
2. La cotizacion se guarda en `core_sales_quotes`.
3. Las partidas se guardan en `core_sales_quote_items`.
4. Al aprobar la cotizacion, el sistema genera un pedido en `core_sales_orders`.
5. Las partidas del pedido se generan en `core_sales_order_items`.
6. El pedido puede surtirse total o parcialmente desde almacen.
7. Cada surtido genera una entrega en `core_sales_deliveries`.
8. Las partidas entregadas se guardan en `core_sales_delivery_items`.
9. La entrega genera movimientos de inventario.
10. Una entrega pendiente puede convertirse en factura desde `admin/billing/create_from_delivery`.

## 3. Ciclo de vida de cotizacion

### Estados principales

- `prequote`: precotizacion/catalogo sin precios.
- `requested`: cotizacion solicitada con precios.
- `approved`: cotizacion aprobada.
- `rejected`: cotizacion rechazada.
- `converted`: estado disponible para conversiones futuras.

### Creacion

Endpoint:

- `admin/sales/create_quote`

Metodo del controlador:

- `Controller_Admin_Sales::post_create_quote()`

Permiso requerido:

- `sales.access[create]`

Reglas actuales:

- Una cotizacion normal requiere `party_id`.
- Una precotizacion puede guardarse como catalogo sin precios.
- `quote_mode = prequote` guarda precios en cero.
- `quote_mode = quote` calcula precios desde producto/lista de precios.
- `offline_uuid` evita duplicados cuando una cotizacion se sincroniza desde modo offline.
- Si la cotizacion normal no tiene subtotal valido, se rechaza.

### Precotizacion

La precotizacion permite mostrar catalogo al cliente sin revelar precios.

En la vista se controla con:

- `quoteForm.quote_mode === 'prequote'`
- clase CSS `price-hidden`
- calculo `lineTotal()` devuelve `0` si es precotizacion.

Cuando se cierra una precotizacion:

Endpoint:

- `admin/sales/close_prequote`

Metodo:

- `Controller_Admin_Sales::post_close_prequote()`

Permiso:

- `sales.access[edit]`

Acciones:

- Recalcula precios.
- Asigna cliente.
- Cambia estado a `requested`.
- Actualiza subtotales y total.
- Registra auditoria con evento `sales.prequote_closed`.

### Aprobacion

Endpoint:

- `admin/sales/update_status`

Metodo:

- `Controller_Admin_Sales::post_update_status()`

Permiso:

- `sales.access[edit]`

Cuando el estado cambia a `approved`, el controlador llama:

- `create_order_for_quote()`

Esto genera el pedido relacionado si no existe uno previamente.

## 4. Ciclo de vida de pedido

Los pedidos se generan principalmente desde una cotizacion aprobada.

Endpoint directo:

- `admin/sales/create_order_from_quote`

Metodo:

- `Controller_Admin_Sales::post_create_order_from_quote()`

Permiso:

- `sales.access[edit]`

Tabla principal:

- `core_sales_orders`

Tabla de partidas:

- `core_sales_order_items`

Reglas principales:

- No se duplica pedido si la cotizacion ya tiene uno activo.
- El folio se genera con prefijo `PED`.
- El pedido copia cliente, vendedor, moneda, importes y partidas de la cotizacion.
- Cada partida conserva relacion con `quote_item_id`.
- El pedido queda ligado por `source_quote_id`.

### Sincronizacion automatica

El metodo `sync_approved_quotes_to_orders()` se ejecuta durante `action_data()`.

Objetivo:

- Detectar cotizaciones aprobadas que aun no tienen pedido.
- Crear el pedido faltante.
- Registrar errores en log si no se puede sincronizar.

Esto existe para reparar inconsistencias operativas sin requerir una tarea manual inmediata.

## 5. Ciclo de vida de entrega

Una entrega representa mercancia surtida desde almacen.

Endpoint:

- `admin/sales/create_delivery_from_order`

Metodo:

- `Controller_Admin_Sales::post_create_delivery_from_order()`

Permiso:

- `sales.access[edit]`

Tablas:

- `core_sales_deliveries`
- `core_sales_delivery_items`
- `core_sales_order_items`
- `core_inventory_movements`
- `core_inventory_stock_balances` cuando existe
- `core_commerce_products` como respaldo de stock cuando no existe balance por almacen

Reglas principales:

- La entrega se crea desde un pedido.
- No se puede surtir un pedido `closed`, `delivered` o `billed`.
- Permite surtido parcial.
- Si queda cantidad pendiente, el pedido queda como `partial`.
- Si no queda pendiente, el pedido queda como `delivered`.
- Cada partida entregada actualiza `delivered_quantity`.
- El folio de entrega usa prefijo `ENT`.
- Cada entrega registra salida de inventario con movimiento `delivery_out`.

### Inventario negativo

La configuracion `allow_negative_inventory_sales` determina si se permite vender/surtir con inventario negativo.

Si no se permite inventario negativo:

- Se valida existencia disponible por almacen.
- Si no hay existencia suficiente, se bloquea la entrega.

Si se permite inventario negativo:

- Se registra la salida aun cuando el stock quede negativo.

### Auditoria

Al crear entrega se registra evento mediante:

- `audit_flow('create_delivery_from_order', ...)`

Tambien se registran logs informativos y errores con `Log::info()` y `Log::error()`.

## 6. Integracion con facturacion

La facturacion de ventas desde entrega se realiza desde el modulo Billing.

Endpoint usado por la vista de ventas:

- `admin/billing/create_from_delivery`

Metodo en Billing:

- `Controller_Admin_Billing::action_create_from_delivery()`

Permiso en Billing:

- `billing.access[create]`
- En modo compatibilidad puede aplicar fallback de `billing.access[edit]`, segun la politica vigente del controlador Billing.

Flujo:

1. La vista de ventas ejecuta `invoiceDelivery(delivery)`.
2. Se envia `delivery_id`.
3. Billing busca la entrega activa en `core_sales_deliveries`.
4. Si la entrega ya tiene `billing_invoice_id`, se bloquea duplicado.
5. Billing crea factura en `core_billing_invoices`.
6. Billing crea conceptos en `core_billing_invoice_items`.
7. Billing actualiza la entrega:
   - `billing_invoice_id`
   - `status = billed`
8. Billing actualiza estado de pedido mediante `refresh_sales_order_billing()`.
9. Billing registra evento de factura.

Ventas no timbra directamente. Solo solicita la creacion de factura desde entrega. El timbrado fiscal corresponde al modulo de Facturacion/CFDI.

## 7. Permisos usados

Permisos actuales en `Controller_Admin_Sales`:

- `sales.access[view]`
  - Requerido en `before()`.
  - Permite entrar al modulo y consultar datos.

- `sales.access[create]`
  - Requerido para `action_create()`.
  - Requerido para `post_create_quote()`.
  - Permite crear cotizaciones y precotizaciones.

- `sales.access[edit]`
  - Requerido para cambiar estado de cotizacion.
  - Requerido para cerrar precotizacion.
  - Requerido para crear pedido desde cotizacion.
  - Requerido para crear entrega desde pedido.

Permisos relacionados fuera de Ventas:

- `billing.access[create]`
  - Requerido por Billing para crear factura desde entrega.

Riesgo actual:

- `sales.access[edit]` concentra aprobacion, conversion a pedido y creacion de entrega. A futuro conviene separar permisos granulares.

Permisos granulares recomendados a futuro:

- `sales.access[quote_create]`
- `sales.access[quote_approve]`
- `sales.access[order_create]`
- `sales.access[delivery_create]`
- `sales.access[invoice_request]`
- `sales.access[offline_sync]`

## 8. Endpoints principales

### Pantallas

- `GET admin/sales`
  - Carga el modulo de ventas.

- `GET admin/sales/create`
  - Abre captura de cotizacion.

- `GET admin/sales/create?mode=prequote`
  - Abre vista cliente/catalogo para precotizacion.

### Datos

- `GET admin/sales/data`
  - Retorna cotizaciones, pedidos, entregas, estadisticas, filtros y opciones.

- `GET admin/sales/product_search`
  - Busca productos para cotizacion/catalogo.

### Cotizaciones

- `POST admin/sales/create_quote`
  - Crea cotizacion o precotizacion.

- `POST admin/sales/update_status`
  - Cambia estado de cotizacion.

- `POST admin/sales/close_prequote`
  - Convierte precotizacion en cotizacion solicitada con precios.

### Pedidos

- `POST admin/sales/create_order_from_quote`
  - Crea pedido desde cotizacion.

### Entregas

- `POST admin/sales/create_delivery_from_order`
  - Crea entrega desde pedido y afecta inventario.

### Facturacion relacionada

- `POST admin/billing/create_from_delivery`
  - Crea factura desde entrega.

## 9. Estructura Vue

La vista administrativa esta separada en parciales bajo:

- `fuel/app/views/admin/sales/`

Archivos principales:

- `index.php`
  - Orquestador.
  - Define `$capture_page`, `$initial_view`, `$capture_mode` y `$no_image_svg`.
  - Contiene `#app-sales`.

- `_summary.php`
  - Tarjetas de conteo.

- `_toolbar.php`
  - Botones, modo online/offline, filtros y tabs.

- `_quotes_table.php`
  - Tabla de cotizaciones.

- `_orders_table.php`
  - Tabla de pedidos.

- `_deliveries_table.php`
  - Tabla de entregas.

- `_quote_form_modal.php`
  - Modal principal de cotizacion/precotizacion.

- `_quote_header_fields.php`
  - Cliente, vendedor, modo, referencia y vigencia.

- `_product_capture.php`
  - Busqueda de producto, imagen, stock, precio y cantidades.

- `_catalog_capture.php`
  - Catalogo en mosaico para precotizacion sin precios.

- `_quote_items_table.php`
  - Partidas y total.

- `_quote_detail_modal.php`
  - Detalle de cotizacion, flujo comercial, pedido y entrega relacionada.

- `_fulfillment_modal.php`
  - Surtido de pedido.

- `_scripts.php`
  - Instancia Vue 2 Options API completa.

### Datos Vue principales

- `quotes`
- `orders`
- `deliveries`
- `viewMode`
- `selected`
- `selectedOrder`
- `stats`
- `periodFilters`
- `options`
- `quoteForm`
- `lineForm`
- `closeForm`
- `deliveryForm`
- `offline`

### Computed principales

- `filteredProducts`
- `productSearchResults`
- `selectedProduct`
- `quoteTotal`
- `quoteCurrency`

### Metodos principales

- `loadData()`
- `newQuote()`
- `newPrequote()`
- `saveQuote()`
- `setStatus()`
- `closePrequote()`
- `createOrderFromQuote()`
- `openFulfillment()`
- `createDeliveryFromOrder()`
- `invoiceDelivery()`
- `searchProducts()`
- `refreshCatalog()`
- `quickAdd()`
- `quickAddRange()`
- `addBrandProducts()`
- `addCategoryProducts()`
- `syncDrafts()`

## 10. Flujo de datos

### Carga inicial

1. La vista carga `#app-sales`.
2. Vue ejecuta `loadData()`.
3. Se llama `admin/sales/data`.
4. El controlador ejecuta `sync_approved_quotes_to_orders()`.
5. Se devuelven:
   - `quotes`
   - `orders`
   - `deliveries`
   - `stats`
   - `period_filters`
   - `options`

### Creacion de cotizacion

1. Usuario abre nueva cotizacion.
2. Selecciona cliente y productos.
3. Vue arma `quoteForm`.
4. Se envia a `admin/sales/create_quote`.
5. El controlador crea `core_sales_quotes`.
6. El controlador crea `core_sales_quote_items`.
7. Se recalculan totales.
8. La vista recarga cotizaciones, pedidos y entregas.

### Modo offline

La vista usa `window.CoreOffline` cuando existe.

Funciones:

- Crear UUID local.
- Guardar borrador local.
- Listar borradores.
- Recuperar borrador.
- Eliminar borrador.
- Sincronizar borradores cuando vuelve conexion.

Tablas relacionadas:

- `core_sales_quotes.offline_uuid`
- `core_offline_sync_logs`

### Aprobacion y pedido

1. Usuario aprueba una cotizacion.
2. Se llama `admin/sales/update_status`.
3. El controlador guarda estado `approved`.
4. Se ejecuta `create_order_for_quote()`.
5. Se crea `core_sales_orders`.
6. Se crean `core_sales_order_items`.

### Entrega e inventario

1. Usuario abre surtido de pedido.
2. Selecciona almacen.
3. Captura cantidades a surtir.
4. Se llama `admin/sales/create_delivery_from_order`.
5. El controlador valida stock si aplica.
6. Se crea entrega.
7. Se crean partidas de entrega.
8. Se actualizan cantidades surtidas del pedido.
9. Se registra salida de inventario.
10. Se actualiza estado del pedido.

### Facturacion

1. Usuario presiona `Facturar` en una entrega.
2. Vue llama `admin/billing/create_from_delivery`.
3. Billing crea factura.
4. Billing enlaza `core_sales_deliveries.billing_invoice_id`.
5. Billing actualiza estado de entrega y pedido.

## 11. Tablas involucradas

### Ventas

- `core_sales_quotes`
- `core_sales_quote_items`
- `core_sales_orders`
- `core_sales_order_items`
- `core_sales_deliveries`
- `core_sales_delivery_items`
- `core_sales_sellers`

### Clientes y catalogo

- `core_parties`
- `core_commerce_products`
- `core_commerce_brands`
- `core_commerce_categories`
- `core_commerce_product_prices`
- `core_commerce_customer_price_lists`

### Inventario

- `core_inventory_warehouses`
- `core_inventory_movements`
- `core_inventory_stock_balances`

### Facturacion

- `core_billing_invoices`
- `core_billing_invoice_items`
- `core_billing_invoice_events`

### Configuracion y auditoria

- `core_settings`
- `core_offline_sync_logs`
- tablas utilizadas por `Helper_Core_Audit`

## 12. Riesgos conocidos

### Concentracion de logica en controlador

`Controller_Admin_Sales` contiene reglas de negocio importantes:

- Creacion de cotizacion.
- Cierre de precotizacion.
- Creacion de pedido.
- Creacion de entrega.
- Validacion de stock.
- Ajustes de inventario.
- Sincronizacion de cotizaciones aprobadas.

Esto funciona, pero a futuro deberia moverse gradualmente a servicios.

Servicios recomendados:

- `Service_Core_Sales_Quote`
- `Service_Core_Sales_Order`
- `Service_Core_Sales_Delivery`
- `Service_Core_Sales_Inventory`
- `Service_Core_Sales_OfflineSync`

### Permiso `sales.access[edit]` demasiado amplio

Actualmente `sales.access[edit]` permite acciones criticas:

- Aprobar cotizaciones.
- Crear pedidos.
- Crear entregas.
- Afectar inventario.

Se recomienda separar permisos antes de crear roles comerciales finos.

### Entregas afectan inventario

`create_delivery_from_order` descuenta inventario.

Riesgos:

- Producto sin costo.
- Existencia insuficiente.
- Configuracion de inventario negativo.
- Diferencias entre `core_inventory_stock_balances` y `core_commerce_products`.

Debe mantenerse auditoria y logs en cualquier ajuste futuro.

### Factura desde entrega depende de Billing

Ventas no debe duplicar logica fiscal ni de timbrado.

La factura desde entrega depende de:

- `billing.access[create]`
- `core_billing_invoices`
- `core_billing_invoice_items`
- reglas fiscales del modulo Billing

### Modo precotizacion sin precios

El modo precotizacion debe preservar:

- No mostrar precios en catalogo.
- No guardar importes de venta en partidas.
- No permitir aprobacion directa sin cerrar con precios.

### Modo offline

El modo offline depende de:

- `window.CoreOffline`
- `offline_uuid`
- `core_offline_sync_logs`

Riesgos:

- Duplicados si no se conserva `offline_uuid`.
- Borradores locales desactualizados.
- Errores CSRF al sincronizar despues de sesion expirada.

### Sincronizacion automatica de cotizaciones aprobadas

`sync_approved_quotes_to_orders()` repara cotizaciones aprobadas sin pedido.

Riesgos:

- Puede ocultar fallas de flujo si no se revisan logs.
- Debe conservarse idempotente.
- No debe crear pedidos duplicados.

## 13. Mejoras futuras

### Servicios de negocio

Mover gradualmente reglas a servicios:

- Crear cotizacion.
- Cerrar precotizacion.
- Aprobar cotizacion.
- Crear pedido.
- Surtir pedido.
- Crear entrega.
- Afectar inventario.
- Solicitar factura.

### Permisos granulares

Separar:

- Ver ventas.
- Crear cotizacion.
- Aprobar cotizacion.
- Crear pedido.
- Surtir pedido.
- Facturar entrega.
- Sincronizar offline.

### Tareas de reparacion

Crear tareas Oil idempotentes:

- Reparar cotizaciones aprobadas sin pedido.
- Recalcular entregado por pedido.
- Recalcular facturado por pedido.
- Reconciliar entregas contra inventario.
- Reconciliar entregas facturadas contra `billing_invoice_id`.

### Mejoras de UI

- Filtros avanzados por cliente, vendedor, estado y almacen.
- Exportacion de tablas.
- Vista kanban por flujo comercial.
- Indicadores de backorder.
- Historial de acciones por cotizacion/pedido/entrega.

### Integracion fiscal

- Validar reglas SAT antes de facturar.
- Mostrar si la factura fue timbrada, cancelada o pendiente.
- Mostrar CFDI relacionado desde Billing/CFDI.

### Inventario

- Mostrar stock disponible por almacen antes de surtir.
- Separar stock fisico, reservado y disponible.
- Permitir entregas parciales con bitacora clara.

### Cobranza

Despues de facturar:

- Enviar factura a cuentas por cobrar.
- Controlar PUE/PPD.
- Generar REP cuando aplique.
- Mostrar saldo pendiente por cliente.
