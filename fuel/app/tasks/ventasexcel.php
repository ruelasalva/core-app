<?php
namespace Fuel\Tasks;

/**
 * TASK VENTASEXCEL
 *
 * Importa el archivo operativo de ventas/rentas a Core-App para pruebas con datos reales.
 *
 * Uso seguro:
 * - php oil r ventasexcel default preview
 * - php oil r ventasexcel default fresh
 *
 * El modo preview solo lee el archivo y muestra conteos.
 * El modo fresh limpia datos operativos de prueba y carga clientes, vendedores,
 * productos, cotizaciones, pedidos, entregas, facturas, pagos y rentas.
 */
class Ventasexcel
{
    protected $default_path = 'C:\\Users\\Sistemas-PC\\Downloads\\Ventas (1).xlsx';
    protected $workbook = [];
    protected $fields = [];
    protected $now = 0;
    protected $cache = [
        'parties' => [],
        'sellers' => [],
        'products' => [],
        'categories' => [],
        'brands' => [],
    ];
    protected $counts = [
        'customers' => 0,
        'sellers' => 0,
        'products' => 0,
        'quotes' => 0,
        'orders' => 0,
        'deliveries' => 0,
        'invoices' => 0,
        'payments' => 0,
        'recurring_profiles' => 0,
        'pending_invoices' => 0,
    ];

    /**
     * Ejecuta la importacion desde Excel.
     *
     * @param string $path Ruta del archivo o "default".
     * @param string $mode preview|fresh.
     */
    public function run($path = 'default', $mode = 'preview')
    {
        try {
            $this->now = time();
            $path = ($path === 'default' || trim((string) $path) === '') ? $this->default_path : $path;
            $mode = strtolower(trim((string) $mode)) ?: 'preview';

            $this->assert_schema_ready();
            $this->workbook = $this->read_workbook($path);

            if ($mode !== 'fresh') {
                $this->print_preview($path);
                return;
            }

            $this->wipe_operational_data();
            $this->import_cost_catalogs();
            $this->import_sales();
            $this->import_rents();
            $this->import_pending_portfolio();

            echo "\n [SUCCESS] Importacion fresca desde Excel terminada.\n";
            foreach ($this->counts as $key => $value) {
                echo ' - '.str_replace('_', ' ', $key).': '.$value."\n";
            }
            echo "\n Puedes revisar: Productos, Clientes, Ventas, Facturacion, Pagos y Rentas.\n";
        } catch (\Exception $e) {
            echo "\n [ERROR] ".$e->getMessage()."\n";
            \Log::error('Fallo ventasexcel: '.$e->getMessage());
        }
    }

    /**
     * Muestra el diagnostico antes de borrar o insertar datos.
     */
    protected function print_preview($path)
    {
        echo "\n [PREVIEW] Archivo: ".$path."\n";
        foreach ($this->workbook as $sheet => $rows) {
            echo ' - '.$sheet.': '.count($rows)." filas utiles\n";
        }

        $ventas = $this->rows('Ventas');
        $rentas = $this->rows('Rentas');
        $cartera = array_merge(
            $this->rows('Cartera general'),
            $this->rows('Cartera Pendiente Bryan'),
            $this->rows('Cartera pendiente Eduardo')
        );

        echo "\n Al ejecutar fresh se limpiaran datos operativos de prueba, no usuarios/permisos/configuracion.\n";
        echo ' - Ventas detectadas: '.count($ventas)."\n";
        echo ' - Rentas detectadas: '.count($rentas)."\n";
        echo ' - Cartera pendiente detectada: '.count($cartera)."\n";
        echo "\n Ejecuta: php oil r ventasexcel default fresh\n";
    }

    /**
     * Limpia informacion operativa para una carga fresca.
     */
    protected function wipe_operational_data()
    {
        $tables = [
            'core_payment_allocations',
            'core_bank_movements',
            'core_payments',
            'core_ar_collection_actions',
            'core_ar_customer_statuses',
            'core_ap_payment_actions',
            'core_ap_supplier_statuses',
            'core_commission_adjustments',
            'core_commission_settlements',
            'core_commission_entries',
            'core_billing_invoice_events',
            'core_billing_invoice_items',
            'core_billing_recurring_runs',
            'core_billing_recurring_items',
            'core_billing_recurring_profiles',
            'core_billing_invoices',
            'core_sales_delivery_items',
            'core_sales_deliveries',
            'core_sales_order_items',
            'core_sales_orders',
            'core_sales_quote_items',
            'core_sales_quotes',
            'core_cart_items',
            'core_cart_carts',
            'core_inventory_movements',
            'core_inventory_stock_balances',
            'core_commerce_product_relations',
            'core_commerce_product_prices',
            'core_commerce_product_images',
            'core_commerce_product_tags',
            'core_commerce_products',
            'core_commerce_subcategories',
            'core_commerce_categories',
            'core_commerce_brands',
            'core_sales_sellers',
        ];

        \DB::query('SET FOREIGN_KEY_CHECKS=0')->execute();
        foreach ($tables as $table) {
            if (!$this->table_exists($table)) {
                continue;
            }
            \DB::query('DELETE FROM `'.$table.'`')->execute();
            \DB::query('ALTER TABLE `'.$table.'` AUTO_INCREMENT = 1')->execute();
        }

        if ($this->table_exists('core_parties')) {
            \DB::delete('core_parties')
                ->where('party_type', 'IN', ['customer', 'prospect'])
                ->or_where('notes', 'LIKE', '%Excel Ventas%')
                ->execute();
        }
        \DB::query('SET FOREIGN_KEY_CHECKS=1')->execute();
    }

    /**
     * Importa costos base de toners e impresoras antes de las ventas.
     */
    protected function import_cost_catalogs()
    {
        $category_toners = $this->category_id('Toners');
        foreach ($this->rows('Costos Toners') as $row) {
            $model = $this->value($row, 'Modelo');
            if ($model === '') {
                continue;
            }
            $cost = $this->money($this->value($row, 'Total'));
            $this->product_id($model, $model, $category_toners, 0, $cost, max($cost * 1.45, 0), false);
        }

        $category_printers = $this->category_id('Impresoras');
        foreach ($this->rows('Costo Impresoras') as $row) {
            $name = $this->value($row, 'Impresora');
            if ($name === '') {
                continue;
            }
            $cost = $this->money($this->value($row, 'Total Costo'));
            $notes = trim('Ubicacion: '.$this->value($row, 'Ubicacion').' Rentada en: '.$this->value($row, 'Rentada en'));
            $this->product_id($name, $name, $category_printers, 0, $cost, max($cost * 1.35, 0), false, $notes);
        }
    }

    /**
     * Importa la hoja Ventas como flujo completo: cotizacion, pedido, entrega, factura y pago.
     */
    protected function import_sales()
    {
        $groups = [];
        foreach ($this->rows('Ventas') as $row) {
            $invoice = $this->value($row, 'No. Factura');
            $customer = $this->value($row, 'Nombre');
            $product = $this->value($row, 'Producto');
            if ($invoice === '' || $customer === '' || $product === '') {
                continue;
            }
            $groups[$invoice][] = $row;
        }

        foreach ($groups as $invoice => $items) {
            $first = reset($items);
            $party_id = $this->party_id($this->value($first, 'Nombre'), 'customer');
            $seller_id = $this->seller_id($this->value($first, 'Vendedor'));
            $date = $this->date_value($this->value($first, 'Fecha'));
            $status_text = strtolower($this->value($first, 'Estado Factura'));
            $paid = (strpos($status_text, 'pag') !== false);
            $cancelled = (strpos($status_text, 'cancel') !== false);

            $totals = $this->group_totals($items);
            $quote_id = $this->insert('core_sales_quotes', [
                'folio' => $this->unique_folio('COT-XLS-'.$invoice, 'core_sales_quotes'),
                'source' => 'excel_import',
                'offline_uuid' => '',
                'synced_from_offline' => 0,
                'offline_synced_at' => 0,
                'cart_id' => 0,
                'user_id' => 0,
                'party_id' => $party_id,
                'seller_id' => $seller_id,
                'status' => $cancelled ? 'cancelled' : 'approved',
                'currency_code' => 'MXN',
                'subtotal' => $totals['subtotal'],
                'discount_total' => 0,
                'tax_total' => $totals['tax'],
                'total' => $totals['total'],
                'customer_notes' => '',
                'internal_notes' => 'Importado desde Excel Ventas factura '.$invoice,
                'expires_at' => strtotime($date.' +15 days'),
                'created_at' => strtotime($date),
                'updated_at' => $this->now,
            ]);
            $this->counts['quotes']++;

            $order_id = $this->insert('core_sales_orders', [
                'folio' => $this->unique_folio('PED-XLS-'.$invoice, 'core_sales_orders'),
                'source_quote_id' => $quote_id,
                'party_id' => $party_id,
                'seller_id' => $seller_id,
                'status' => $cancelled ? 'cancelled' : ($paid ? 'closed' : 'authorized'),
                'order_date' => $date,
                'currency_code' => 'MXN',
                'subtotal' => $totals['subtotal'],
                'discount_total' => 0,
                'tax_total' => $totals['tax'],
                'total' => $totals['total'],
                'delivered_total' => $cancelled ? 0 : $totals['total'],
                'billed_total' => $cancelled ? 0 : $totals['total'],
                'notes' => 'Importado desde Excel Ventas factura '.$invoice,
                'created_by' => 0,
                'active' => 1,
                'created_at' => strtotime($date),
                'updated_at' => $this->now,
            ]);
            $this->counts['orders']++;

            $delivery_id = $this->insert('core_sales_deliveries', [
                'folio' => $this->unique_folio('ENT-XLS-'.$invoice, 'core_sales_deliveries'),
                'order_id' => $order_id,
                'billing_invoice_id' => 0,
                'party_id' => $party_id,
                'warehouse_id' => 0,
                'status' => $cancelled ? 'cancelled' : 'billed',
                'delivery_date' => $date,
                'currency_code' => 'MXN',
                'total' => $totals['total'],
                'notes' => 'Entrega importada desde Excel Ventas',
                'created_by' => 0,
                'active' => 1,
                'created_at' => strtotime($date),
                'updated_at' => $this->now,
            ]);
            $this->counts['deliveries']++;

            $invoice_id = $this->insert_invoice('FAC-XLS-'.$invoice, $party_id, $date, $totals, [
                'status' => $cancelled ? 'cancelled' : ($paid ? 'paid' : 'stamped'),
                'balance_due' => $paid || $cancelled ? 0 : $totals['total'],
                'source_module' => 'excel_import',
                'source_entity_type' => 'sales_delivery',
                'source_entity_id' => $delivery_id,
                'notes' => 'Factura importada desde Excel Ventas. Estado original: '.$this->value($first, 'Estado Factura'),
                'payment_form' => $this->payment_form_code($this->value($first, 'Forma de Pago')),
            ]);
            $this->counts['invoices']++;

            \DB::update('core_sales_deliveries')->set(['billing_invoice_id' => $invoice_id])->where('id', '=', $delivery_id)->execute();

            $sort = 10;
            foreach ($items as $row) {
                $line = $this->line_values($row);
                if ($line['name'] === '') {
                    continue;
                }
                $category_id = $this->category_id($this->is_rent($line['name']) ? 'Rentas' : 'Toners');
                $product_id = $this->product_id($line['sku'], $line['name'], $category_id, 0, $line['cost'], $line['price'], $this->is_rent($line['name']));

                $quote_item_id = $this->insert('core_sales_quote_items', [
                    'quote_id' => $quote_id,
                    'product_id' => $product_id,
                    'sku' => $line['sku'],
                    'name' => $line['name'],
                    'currency_code' => 'MXN',
                    'unit_price' => $line['price'],
                    'quantity' => $line['quantity'],
                    'line_subtotal' => $line['subtotal'],
                    'line_total' => $line['total'],
                    'sort_order' => $sort,
                    'created_at' => strtotime($date),
                    'updated_at' => $this->now,
                ]);

                $order_item_id = $this->insert('core_sales_order_items', [
                    'order_id' => $order_id,
                    'quote_item_id' => $quote_item_id,
                    'product_id' => $product_id,
                    'sku' => $line['sku'],
                    'name' => $line['name'],
                    'currency_code' => 'MXN',
                    'unit_price' => $line['price'],
                    'quantity' => $line['quantity'],
                    'delivered_quantity' => $line['quantity'],
                    'billed_quantity' => $line['quantity'],
                    'line_total' => $line['total'],
                    'sort_order' => $sort,
                    'created_at' => strtotime($date),
                    'updated_at' => $this->now,
                ]);

                $this->insert('core_sales_delivery_items', [
                    'delivery_id' => $delivery_id,
                    'order_item_id' => $order_item_id,
                    'product_id' => $product_id,
                    'sku' => $line['sku'],
                    'name' => $line['name'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['price'],
                    'line_total' => $line['total'],
                    'sort_order' => $sort,
                    'created_at' => strtotime($date),
                    'updated_at' => $this->now,
                ]);

                $this->insert_invoice_item($invoice_id, $product_id, $line, $sort);
                $sort += 10;
            }

            if ($paid && !$cancelled) {
                $payment_date = $this->date_value($this->value($first, 'Fecha deposito Cheque')) ?: $date;
                $this->insert_payment($invoice, $party_id, $invoice_id, $payment_date, $totals['total'], $this->value($first, 'Forma de Pago'));
            }
        }
    }

    /**
     * Importa rentas como perfiles recurrentes y factura vigente de prueba.
     */
    protected function import_rents()
    {
        $category_id = $this->category_id('Rentas');
        $rent_product_id = $this->product_id('RENTA-MENSUAL', 'Renta mensual', $category_id, 0, 0, 0, true);
        $i = 1;

        foreach ($this->rows('Rentas') as $row) {
            $customer = $this->value($row, 'Cliente');
            if ($customer === '') {
                continue;
            }
            $subtotal = $this->money($this->value($row, 'Subtotal'));
            $tax = $this->money($this->value($row, 'Iva'));
            $total = $this->money($this->value($row, 'Total'));
            if ($total <= 0 && $subtotal <= 0) {
                continue;
            }
            $party_id = $this->party_id($customer, 'customer');
            $day = max(1, min(28, (int) $this->money($this->value($row, 'Dia Factura'))));
            $start_date = date('Y-m-').str_pad($day, 2, '0', STR_PAD_LEFT);
            $folio = 'REN-XLS-'.str_pad($i, 4, '0', STR_PAD_LEFT);

            $profile_id = $this->insert('core_billing_recurring_profiles', [
                'folio' => $this->unique_folio($folio, 'core_billing_recurring_profiles'),
                'name' => 'Renta mensual - '.$customer,
                'party_id' => $party_id,
                'invoice_type' => 'sale',
                'frequency' => 'monthly',
                'start_date' => $start_date,
                'end_date' => '',
                'next_run_date' => $start_date,
                'last_run_at' => 0,
                'auto_stamp' => 0,
                'pac_connection_id' => 0,
                'pac_series_id' => 0,
                'pac_receptor_uid' => 0,
                'currency_code' => 'MXN',
                'exchange_rate' => 1,
                'payment_term_id' => 0,
                'sat_cfdi_use_code' => 'G03',
                'sat_payment_form_code' => $this->payment_form_code($this->value($row, 'Forma de pago')),
                'sat_payment_method_code' => 'PPD',
                'notes' => 'Importado desde Excel Rentas. Agente: '.$this->value($row, 'Agente').' Nota: '.$this->value($row, 'Nota'),
                'status' => 'active',
                'created_by' => 0,
                'active' => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $this->counts['recurring_profiles']++;

            $this->insert('core_billing_recurring_items', [
                'profile_id' => $profile_id,
                'product_id' => $rent_product_id,
                'sat_product_service_code' => '80161801',
                'description' => 'Renta mensual de equipo',
                'quantity' => 1,
                'unit_code' => 'servicio',
                'sat_object_tax_code' => '02',
                'unit_price' => $subtotal,
                'discount_amount' => 0,
                'tax_code' => 'iva_16',
                'tax_factor_type' => 'Tasa',
                'tax_rate' => $subtotal > 0 ? round($tax / $subtotal, 6) : 0.16,
                'retention_tax_code' => '',
                'retention_rate' => 0,
                'retention_amount' => 0,
                'sort_order' => 10,
                'active' => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);

            $invoice_id = $this->insert_invoice('FAC-XLS-REN-'.$i, $party_id, $start_date, [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ], [
                'status' => 'stamped',
                'balance_due' => $total,
                'source_module' => 'excel_rentas',
                'source_entity_type' => 'billing_recurring_profile',
                'source_entity_id' => $profile_id,
                'notes' => 'Factura de prueba creada desde renta recurrente Excel.',
                'payment_form' => $this->payment_form_code($this->value($row, 'Forma de pago')),
            ]);
            $this->counts['invoices']++;

            $this->insert_invoice_item($invoice_id, $rent_product_id, [
                'name' => 'Renta mensual de equipo',
                'quantity' => 1,
                'price' => $subtotal,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ], 10);
            $i++;
        }
    }

    /**
     * Importa hojas de cartera como facturas pendientes para probar CxC.
     */
    protected function import_pending_portfolio()
    {
        $sheets = ['Cartera general', 'Cartera Pendiente Bryan', 'Cartera pendiente Eduardo'];
        foreach ($sheets as $sheet) {
            foreach ($this->rows($sheet) as $row) {
                $invoice = $this->first_value($row, ['Factura', 'No. Factura', 'Folio']);
                $customer = $this->first_value($row, ['Cliente', 'Nombre']);
                $total = $this->money($this->first_value($row, ['Total', 'Importe', 'Saldo']));
                if ($invoice === '' || $customer === '' || $total <= 0) {
                    continue;
                }
                $date = $this->date_value($this->first_value($row, ['Fecha', 'Fecha Factura'])) ?: date('Y-m-d');
                $party_id = $this->party_id($customer, 'customer');
                $this->insert_invoice('FAC-XLS-CAR-'.$invoice, $party_id, $date, [
                    'subtotal' => round($total / 1.16, 2),
                    'tax' => round($total - ($total / 1.16), 2),
                    'total' => $total,
                ], [
                    'status' => 'stamped',
                    'balance_due' => $total,
                    'source_module' => 'excel_cartera',
                    'source_entity_type' => 'portfolio',
                    'source_entity_id' => 0,
                    'notes' => 'Cartera pendiente importada desde hoja '.$sheet,
                    'payment_form' => '99',
                ]);
                $this->counts['pending_invoices']++;
                $this->counts['invoices']++;
            }
        }
    }

    protected function insert_invoice($folio, $party_id, $date, array $totals, array $extra)
    {
        return $this->insert('core_billing_invoices', [
            'folio' => $this->unique_folio($folio, 'core_billing_invoices'),
            'invoice_type' => 'sale',
            'party_id' => $party_id,
            'cfdi_id' => 0,
            'fiscal_document_id' => 0,
            'fiscal_mode' => 'admin',
            'requires_waybill' => 0,
            'pac_provider_code' => '',
            'pac_connection_id' => 0,
            'pac_series_id' => 0,
            'pac_receptor_uid' => 0,
            'pac_uid' => '',
            'uuid' => '',
            'sat_status' => 'vigente',
            'stamped_at' => strtotime($date),
            'cancelled_at' => 0,
            'cancel_motive' => '',
            'cancel_substitute_uuid' => '',
            'pac_request_json' => '',
            'pac_response_json' => '',
            'xml_path' => '',
            'pdf_path' => '',
            'source_module' => $extra['source_module'],
            'source_entity_type' => $extra['source_entity_type'],
            'source_entity_id' => $extra['source_entity_id'],
            'issue_date' => $date,
            'due_date' => date('Y-m-d', strtotime($date.' +30 days')),
            'currency_code' => 'MXN',
            'exchange_rate' => 1,
            'payment_term_id' => 0,
            'sat_cfdi_use_code' => 'G03',
            'sat_payment_form_code' => $extra['payment_form'],
            'sat_payment_method_code' => 'PPD',
            'subtotal' => $totals['subtotal'],
            'discount_total' => 0,
            'tax_total' => $totals['tax'],
            'retention_total' => 0,
            'total' => $totals['total'],
            'balance_due' => $extra['balance_due'],
            'status' => $extra['status'],
            'notes' => $extra['notes'],
            'created_by' => 0,
            'active' => 1,
            'created_at' => strtotime($date),
            'updated_at' => $this->now,
        ]);
    }

    protected function insert_invoice_item($invoice_id, $product_id, array $line, $sort)
    {
        $tax_rate = $line['subtotal'] > 0 ? round($line['tax'] / $line['subtotal'], 6) : 0.16;
        $this->insert('core_billing_invoice_items', [
            'invoice_id' => $invoice_id,
            'product_id' => $product_id,
            'sat_product_service_code' => $this->is_rent($line['name']) ? '80161801' : '44103100',
            'description' => $line['name'],
            'quantity' => $line['quantity'],
            'unit_code' => $this->is_rent($line['name']) ? 'servicio' : 'pieza',
            'sat_object_tax_code' => '02',
            'unit_price' => $line['price'],
            'discount_amount' => 0,
            'tax_code' => 'iva_16',
            'tax_factor_type' => 'Tasa',
            'tax_rate' => $tax_rate,
            'tax_amount' => $line['tax'],
            'retention_amount' => 0,
            'retention_tax_code' => '',
            'retention_rate' => 0,
            'line_total' => $line['total'],
            'sort_order' => $sort,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
    }

    protected function insert_payment($source_invoice, $party_id, $invoice_id, $date, $amount, $form)
    {
        $payment_id = $this->insert('core_payments', [
            'folio' => $this->unique_folio('PAG-XLS-'.$source_invoice, 'core_payments'),
            'payment_type' => 'received',
            'party_id' => $party_id,
            'bank_account_id' => 0,
            'integration_connection_id' => 0,
            'fiscal_document_id' => 0,
            'fiscal_mode' => 'admin',
            'rep_status' => 'not_required',
            'payment_date' => $date,
            'currency_code' => 'MXN',
            'exchange_rate' => 1,
            'amount' => $amount,
            'sat_payment_form_code' => $this->payment_form_code($form),
            'reference' => 'Factura Excel '.$source_invoice,
            'external_id' => 'excel:ventas:'.$source_invoice,
            'status' => 'applied',
            'notes' => 'Pago importado desde Excel Ventas',
            'created_by' => 0,
            'active' => 1,
            'created_at' => strtotime($date),
            'updated_at' => $this->now,
        ]);

        if ($this->table_exists('core_payment_allocations')) {
            $this->insert('core_payment_allocations', [
                'payment_id' => $payment_id,
                'entity_type' => 'billing_invoice',
                'entity_id' => $invoice_id,
                'amount' => $amount,
                'notes' => 'Aplicado automaticamente por importacion Excel',
                'active' => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $this->counts['payments']++;
    }

    protected function group_totals(array $items)
    {
        $totals = ['subtotal' => 0, 'tax' => 0, 'total' => 0];
        foreach ($items as $row) {
            $line = $this->line_values($row);
            $totals['subtotal'] += $line['subtotal'];
            $totals['tax'] += $line['tax'];
            $totals['total'] += $line['total'];
        }
        return array_map(function ($value) {
            return round($value, 2);
        }, $totals);
    }

    protected function line_values(array $row)
    {
        $name = $this->value($row, 'Producto');
        $quantity = max(1, $this->money($this->value($row, 'Cantidad')));
        $price = $this->money($this->value($row, 'Precio'));
        $subtotal = $this->money($this->value($row, 'Subtotal'));
        $tax = $this->money($this->value($row, 'Iva'));
        $total = $this->money($this->value($row, 'Total'));
        if ($subtotal <= 0 && $total > 0) {
            $subtotal = round($total / 1.16, 2);
            $tax = round($total - $subtotal, 2);
        }
        if ($price <= 0 && $quantity > 0) {
            $price = round($subtotal / $quantity, 2);
        }
        $cost_total = $this->money($this->value($row, 'Costo'));
        return [
            'sku' => $this->sku($name),
            'name' => $name,
            'quantity' => $quantity,
            'price' => $price,
            'cost' => $quantity > 0 ? round($cost_total / $quantity, 2) : $cost_total,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    protected function party_id($name, $type)
    {
        $name = trim((string) $name);
        $key = $type.'|'.mb_strtolower($name, 'UTF-8');
        if (isset($this->cache['parties'][$key])) {
            return $this->cache['parties'][$key];
        }

        $exists = \DB::select('id')->from('core_parties')->where('party_type', '=', $type)->where('name', '=', $name)->execute()->current();
        if ($exists) {
            $this->cache['parties'][$key] = (int) $exists['id'];
            return (int) $exists['id'];
        }

        $id = $this->insert('core_parties', [
            'party_type' => $type,
            'code' => $this->unique_party_code($type, $name),
            'name' => $name,
            'legal_name' => $name,
            'rfc' => '',
            'email' => '',
            'phone' => '',
            'department_id' => 0,
            'sales_user_id' => 0,
            'default_seller_id' => 0,
            'buyer_user_id' => 0,
            'price_list_id' => 0,
            'payment_term_id' => 0,
            'sat_cfdi_use_code' => 'G03',
            'sat_tax_regime_code' => '',
            'fiscal_operation_type_id' => 0,
            'shipping_method_id' => 0,
            'credit_limit' => 0,
            'credit_days' => 0,
            'notes' => 'Importado desde Excel Ventas',
            'onboarding_status' => 'approved',
            'onboarding_notes' => '',
            'reviewed_by' => 0,
            'reviewed_at' => 0,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $this->cache['parties'][$key] = $id;
        $this->counts['customers']++;
        return $id;
    }

    protected function seller_id($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 0;
        }
        $key = mb_strtolower($name, 'UTF-8');
        if (isset($this->cache['sellers'][$key])) {
            return $this->cache['sellers'][$key];
        }

        $exists = \DB::select('id')->from('core_sales_sellers')->where('name', '=', $name)->execute()->current();
        if ($exists) {
            $this->cache['sellers'][$key] = (int) $exists['id'];
            return (int) $exists['id'];
        }

        $id = $this->insert('core_sales_sellers', [
            'code' => $this->unique_folio('VEN-'.$this->slug($name), 'core_sales_sellers', 'code'),
            'name' => $name,
            'seller_type' => 'employee',
            'employee_id' => 0,
            'party_id' => 0,
            'user_id' => 0,
            'default_commission_plan_id' => 0,
            'base_commission_percent' => 0,
            'quota_commission_percent' => 0,
            'payment_commission_percent' => 0,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $this->cache['sellers'][$key] = $id;
        $this->counts['sellers']++;
        return $id;
    }

    protected function category_id($name)
    {
        $key = mb_strtolower($name, 'UTF-8');
        if (isset($this->cache['categories'][$key])) {
            return $this->cache['categories'][$key];
        }
        $slug = $this->slug($name);
        $exists = \DB::select('id')->from('core_commerce_categories')->where('slug', '=', $slug)->execute()->current();
        if ($exists) {
            $this->cache['categories'][$key] = (int) $exists['id'];
            return (int) $exists['id'];
        }
        $id = $this->insert('core_commerce_categories', [
            'name' => $name,
            'slug' => $this->unique_slug($slug, 'core_commerce_categories'),
            'description' => 'Categoria creada por importacion Excel',
            'image_path' => '',
            'show_in_home' => 1,
            'sort_order' => count($this->cache['categories']) + 1,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $this->cache['categories'][$key] = $id;
        return $id;
    }

    protected function product_id($sku, $name, $category_id, $brand_id = 0, $cost = 0, $price = 0, $service = false, $description = '')
    {
        $sku = $this->sku($sku ?: $name);
        if (isset($this->cache['products'][$sku])) {
            return $this->cache['products'][$sku];
        }
        $exists = \DB::select('id')->from('core_commerce_products')->where('sku', '=', $sku)->execute()->current();
        if ($exists) {
            $this->cache['products'][$sku] = (int) $exists['id'];
            return (int) $exists['id'];
        }

        $id = $this->insert('core_commerce_products', [
            'sku' => $sku,
            'name' => trim((string) $name) ?: $sku,
            'slug' => $this->unique_slug($this->slug($sku.'-'.$name), 'core_commerce_products'),
            'short_description' => $service ? 'Servicio importado desde Excel' : 'Producto importado desde Excel',
            'description' => $description ?: 'Importado desde Excel Ventas',
            'brand_id' => $brand_id,
            'category_id' => $category_id,
            'subcategory_id' => 0,
            'product_type' => $service ? 'service' : 'product',
            'is_internal_service' => $service ? 1 : 0,
            'unit_code' => $service ? 'servicio' : 'pieza',
            'sat_product_service_code' => $service ? '80161801' : '44103100',
            'sat_unit_code' => $service ? 'E48' : 'H87',
            'sat_object_tax_code' => '02',
            'currency_code' => 'MXN',
            'price' => $price,
            'cost' => $cost,
            'tax_code' => 'iva_16',
            'sat_tax_code' => '002',
            'sat_tax_factor_type' => 'Tasa',
            'sat_tax_rate' => 0.16,
            'stock_quantity' => 0,
            'stock_reserved' => 0,
            'stock_min' => 0,
            'stock_updated_at' => $this->now,
            'main_image_path' => '',
            'show_in_home' => $service ? 0 : 1,
            'featured' => 0,
            'published' => 1,
            'active' => 1,
            'sort_order' => 0,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $this->cache['products'][$sku] = $id;
        $this->counts['products']++;
        return $id;
    }

    protected function insert($table, array $data)
    {
        $fields = $this->table_fields($table);
        $clean = [];
        foreach ($data as $field => $value) {
            if (isset($fields[$field])) {
                $clean[$field] = $value;
            }
        }
        if (!$clean) {
            throw new \Exception('No hay campos validos para insertar en '.$table);
        }
        list($id) = \DB::insert($table)->set($clean)->execute();
        return (int) $id;
    }

    protected function rows($sheet)
    {
        if (empty($this->workbook[$sheet])) {
            return [];
        }
        $rows = $this->workbook[$sheet];
        $header_index = 0;
        foreach ($rows as $i => $row) {
            $joined = implode('|', array_map('strval', $row));
            if (stripos($joined, 'Factura') !== false || stripos($joined, 'Cliente') !== false || stripos($joined, 'Modelo') !== false || stripos($joined, 'Impresora') !== false) {
                $header_index = $i;
                break;
            }
        }
        $headers = array_map([$this, 'normalize_header'], $rows[$header_index]);
        $out = [];
        for ($i = $header_index + 1; $i < count($rows); $i++) {
            $assoc = [];
            $has_value = false;
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $value = isset($rows[$i][$index]) ? trim((string) $rows[$i][$index]) : '';
                $assoc[$header] = $value;
                if ($value !== '') {
                    $has_value = true;
                }
            }
            if ($has_value) {
                $out[] = $assoc;
            }
        }
        return $out;
    }

    protected function read_workbook($path)
    {
        if (!is_file($path)) {
            throw new \Exception('No se encontro el archivo Excel: '.$path);
        }
        if (!class_exists('ZipArchive')) {
            throw new \Exception('PHP no tiene ZipArchive habilitado.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \Exception('No se pudo abrir el Excel.');
        }

        $shared = $this->read_shared_strings($zip);
        $rels = $this->read_workbook_rels($zip);
        $workbook_xml = $zip->getFromName('xl/workbook.xml');
        $workbook = simplexml_load_string($workbook_xml);
        $workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $sheets = [];
        foreach ($workbook->xpath('//m:sheet') as $sheet) {
            $attrs = $sheet->attributes();
            $rel_attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $name = (string) $attrs['name'];
            $rid = (string) $rel_attrs['id'];
            if (!isset($rels[$rid])) {
                continue;
            }
            $target = 'xl/'.ltrim($rels[$rid], '/');
            $sheets[$name] = $this->read_sheet($zip, $target, $shared);
        }
        $zip->close();
        return $sheets;
    }

    protected function read_shared_strings(\ZipArchive $zip)
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $root = simplexml_load_string($xml);
        $root->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];
        foreach ($root->xpath('//m:si') as $si) {
            $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = [];
            foreach ($si->xpath('.//m:t') as $t) {
                $parts[] = (string) $t;
            }
            $strings[] = implode('', $parts);
        }
        return $strings;
    }

    protected function read_workbook_rels(\ZipArchive $zip)
    {
        $xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $root = simplexml_load_string($xml);
        $rels = [];
        foreach ($root->Relationship as $rel) {
            $attrs = $rel->attributes();
            $rels[(string) $attrs['Id']] = (string) $attrs['Target'];
        }
        return $rels;
    }

    protected function read_sheet(\ZipArchive $zip, $target, array $shared)
    {
        $xml = $zip->getFromName($target);
        if ($xml === false) {
            return [];
        }
        $root = simplexml_load_string($xml);
        $root->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        foreach ($root->xpath('//m:sheetData/m:row') as $row) {
            $row->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $cells = [];
            foreach ($row->xpath('m:c') as $cell) {
                $attrs = $cell->attributes();
                $ref = (string) $attrs['r'];
                $index = $this->column_index($ref);
                $type = isset($attrs['t']) ? (string) $attrs['t'] : '';
                $value = '';
                if ($type === 's') {
                    $v = (string) $cell->v;
                    $value = isset($shared[(int) $v]) ? $shared[(int) $v] : '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) $cell->is->t;
                } else {
                    $value = isset($cell->v) ? (string) $cell->v : '';
                }
                $cells[$index] = trim($value);
            }
            if ($cells) {
                $max = max(array_keys($cells));
                $line = [];
                for ($i = 0; $i <= $max; $i++) {
                    $line[$i] = isset($cells[$i]) ? $cells[$i] : '';
                }
                $rows[] = $line;
            }
        }
        return $rows;
    }

    protected function column_index($cell_ref)
    {
        preg_match('/^([A-Z]+)/', $cell_ref, $m);
        $letters = isset($m[1]) ? $m[1] : 'A';
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    protected function value(array $row, $key)
    {
        $key = $this->normalize_header($key);
        return isset($row[$key]) ? trim((string) $row[$key]) : '';
    }

    protected function first_value(array $row, array $keys)
    {
        foreach ($keys as $key) {
            $value = $this->value($row, $key);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    protected function normalize_header($header)
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $header)), 'UTF-8');
    }

    protected function money($value)
    {
        $value = str_replace([',', '$'], '', trim((string) $value));
        return is_numeric($value) ? (float) $value : 0.0;
    }

    protected function date_value($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (is_numeric($value)) {
            return date('Y-m-d', strtotime('1899-12-30 +'.(int) $value.' days'));
        }
        $time = strtotime($value);
        return $time ? date('Y-m-d', $time) : '';
    }

    protected function payment_form_code($text)
    {
        $text = mb_strtolower((string) $text, 'UTF-8');
        if (strpos($text, 'transfer') !== false || strpos($text, 'spei') !== false) {
            return '03';
        }
        if (strpos($text, 'cheque') !== false) {
            return '02';
        }
        if (strpos($text, 'efectivo') !== false) {
            return '01';
        }
        if (strpos($text, 'tarjeta') !== false) {
            return '04';
        }
        return '99';
    }

    protected function is_rent($name)
    {
        return stripos((string) $name, 'renta') !== false;
    }

    protected function sku($value)
    {
        $sku = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', trim((string) $value)));
        return trim($sku, '-') ?: 'SKU-XLS';
    }

    protected function slug($value)
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value);
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', trim($value)));
        return trim($slug, '-') ?: 'excel';
    }

    protected function unique_slug($slug, $table)
    {
        return $this->unique_folio($slug, $table, 'slug');
    }

    protected function unique_party_code($type, $name)
    {
        $prefix = $type === 'supplier' ? 'PROV' : 'CLI';
        return $this->unique_folio($prefix.'-'.$this->slug($name), 'core_parties', 'code');
    }

    protected function unique_folio($folio, $table, $field = 'folio')
    {
        $base = trim(preg_replace('/[^A-Za-z0-9\-_]+/', '-', (string) $folio), '-') ?: 'XLS';
        $candidate = $base;
        $i = 2;
        while (\DB::select('id')->from($table)->where($field, '=', $candidate)->execute()->current()) {
            $candidate = $base.'-'.$i;
            $i++;
        }
        return $candidate;
    }

    protected function table_fields($table)
    {
        if (isset($this->fields[$table])) {
            return $this->fields[$table];
        }
        $fields = [];
        $rows = \DB::query('SHOW COLUMNS FROM `'.$table.'`')->execute();
        foreach ($rows as $row) {
            $fields[$row['Field']] = true;
        }
        $this->fields[$table] = $fields;
        return $fields;
    }

    protected function table_exists($table)
    {
        return \DBUtil::table_exists($table);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_parties', 'core_commerce_products', 'core_sales_quotes', 'core_sales_orders', 'core_sales_deliveries', 'core_billing_invoices', 'core_payments'] as $table) {
            if (!$this->table_exists($table)) {
                throw new \Exception('Falta la tabla '.$table.'. Ejecuta primero: php oil refine migrate');
            }
        }
    }
}
