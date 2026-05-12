<?php

/**
 * CONTROLADOR ADMIN_BILLING
 *
 * Administra facturacion base y preparacion de CFDI.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Billing extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE FACTURACION
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('billing.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA PANEL DE FACTURACION
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Facturacion CFDI';
        $this->template->content = View::forge('admin/billing/index');
    }

    /**
     * DATA
     *
     * ENTREGA FACTURAS, CONCEPTOS Y OPCIONES
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA ESTRUCTURA
            $this->assert_schema_ready();

            # SE REGRESA INFORMACION PARA VUE
            return $this->json_response([
                'invoices' => $this->get_invoices(),
                'items' => $this->get_items((int) \Input::get('invoice_id', 0)),
                'options' => $this->get_options(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando facturacion: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar facturacion.'], 500);
        }
    }

    /**
     * SAVE INVOICE
     *
     * CREA O ACTUALIZA UNA FACTURA BASE
     *
     * @access  public
     * @return  Response
     */
    public function action_save_invoice()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('billing.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # VALIDACIONES MINIMAS
            if ((int) \Arr::get($val, 'party_id', 0) < 1) {
                return $this->json_response(['error' => 'El tercero es obligatorio.'], 422);
            }

            # SE PREPARAN DATOS
            $data = [
                'invoice_type' => $this->codeify(\Arr::get($val, 'invoice_type', 'sale')),
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'cfdi_id' => (int) \Arr::get($val, 'cfdi_id', 0),
                'source_module' => $this->codeify(\Arr::get($val, 'source_module', 'manual')),
                'source_entity_type' => trim((string) \Arr::get($val, 'source_entity_type', '')),
                'source_entity_id' => (int) \Arr::get($val, 'source_entity_id', 0),
                'issue_date' => trim((string) \Arr::get($val, 'issue_date', date('Y-m-d'))),
                'due_date' => trim((string) \Arr::get($val, 'due_date', '')),
                'currency_code' => strtoupper(substr((string) \Arr::get($val, 'currency_code', 'MXN'), 0, 3)),
                'exchange_rate' => (float) \Arr::get($val, 'exchange_rate', 1),
                'payment_term_id' => (int) \Arr::get($val, 'payment_term_id', 0),
                'sat_cfdi_use_code' => trim((string) \Arr::get($val, 'sat_cfdi_use_code', 'G03')),
                'sat_payment_form_code' => trim((string) \Arr::get($val, 'sat_payment_form_code', '99')),
                'sat_payment_method_code' => trim((string) \Arr::get($val, 'sat_payment_method_code', 'PPD')),
                'status' => $this->codeify(\Arr::get($val, 'status', 'draft')),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE CREA O ACTUALIZA
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $invoice = Model_Core_Billing_Invoice::find($id);
                if (!$invoice) {
                    return $this->json_response(['error' => 'Factura no encontrada.'], 404);
                }
                $old = $invoice->to_array();
                $invoice->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_invoice_folio();
                $data['created_by'] = $this->user_id;
                $invoice = Model_Core_Billing_Invoice::forge($data);
            }
            $invoice->save();
            $this->recalculate_invoice((int) $invoice->id);

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'billing',
                'action' => $id > 0 ? 'update_invoice' : 'create_invoice',
                'entity_type' => 'billing_invoice',
                'entity_id' => (int) $invoice->id,
                'summary' => 'Factura '.$invoice->folio.' estado '.$invoice->status,
                'old_values' => $old,
                'new_values' => $invoice->to_array(),
            ]);

            return $this->json_response([
                'status' => 'ok',
                'invoices' => $this->get_invoices(),
                'items' => $this->get_items((int) $invoice->id),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando factura: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la factura.'], 400);
        }
    }

    /**
     * SAVE ITEM
     *
     * CREA O ACTUALIZA UN CONCEPTO DE FACTURA
     *
     * @access  public
     * @return  Response
     */
    public function action_save_item()
    {
        # VALIDAR PERMISO PARA EDITAR
        $this->require_access('billing.access[edit]');

        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();

        try {
            # VALIDACIONES MINIMAS
            $invoice_id = (int) \Arr::get($val, 'invoice_id', 0);
            if ($invoice_id < 1 || !Model_Core_Billing_Invoice::find($invoice_id)) {
                return $this->json_response(['error' => 'Factura invalida.'], 422);
            }

            if (trim((string) \Arr::get($val, 'description', '')) === '') {
                return $this->json_response(['error' => 'La descripcion es obligatoria.'], 422);
            }

            # SE CALCULAN IMPORTES
            $quantity = max(0, (float) \Arr::get($val, 'quantity', 1));
            $unit_price = max(0, (float) \Arr::get($val, 'unit_price', 0));
            $discount = max(0, (float) \Arr::get($val, 'discount_amount', 0));
            $tax_rate = max(0, (float) \Arr::get($val, 'tax_rate', 0));
            $base = max(0, ($quantity * $unit_price) - $discount);
            $tax_amount = round($base * $tax_rate, 2);
            $retention = max(0, (float) \Arr::get($val, 'retention_amount', 0));

            # SE PREPARAN DATOS
            $data = [
                'invoice_id' => $invoice_id,
                'product_id' => (int) \Arr::get($val, 'product_id', 0),
                'sat_product_service_code' => trim((string) \Arr::get($val, 'sat_product_service_code', '01010101')),
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'quantity' => $quantity,
                'unit_code' => trim((string) \Arr::get($val, 'unit_code', 'H87')),
                'unit_price' => $unit_price,
                'discount_amount' => $discount,
                'tax_code' => trim((string) \Arr::get($val, 'tax_code', 'iva_16')),
                'tax_rate' => $tax_rate,
                'tax_amount' => $tax_amount,
                'retention_amount' => $retention,
                'line_total' => round($base + $tax_amount - $retention, 2),
                'sort_order' => (int) \Arr::get($val, 'sort_order', 0),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            # SE CREA O ACTUALIZA
            $id = (int) \Arr::get($val, 'id', 0);
            if ($id > 0) {
                $item = Model_Core_Billing_Invoice_Item::find($id);
                if (!$item) {
                    return $this->json_response(['error' => 'Concepto no encontrado.'], 404);
                }
                $old = $item->to_array();
                $item->set($data);
            } else {
                $old = [];
                $item = Model_Core_Billing_Invoice_Item::forge($data);
            }
            $item->save();
            $invoice = $this->recalculate_invoice($invoice_id);

            # SE AUDITA CAMBIO
            Helper_Core_Audit::log([
                'module' => 'billing',
                'action' => $id > 0 ? 'update_invoice_item' : 'create_invoice_item',
                'entity_type' => 'billing_invoice_item',
                'entity_id' => (int) $item->id,
                'summary' => 'Concepto en factura '.$invoice->folio,
                'old_values' => $old,
                'new_values' => $item->to_array(),
            ]);

            return $this->json_response([
                'status' => 'ok',
                'invoices' => $this->get_invoices(),
                'items' => $this->get_items($invoice_id),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando concepto de factura: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el concepto.'], 400);
        }
    }

    /**
     * GET INVOICES
     *
     * FORMATEA FACTURAS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_invoices()
    {
        # SE CONSULTAN FACTURAS RECIENTES
        $items = [];
        $rows = Model_Core_Billing_Invoice::query()->order_by('id', 'desc')->limit(200)->get();

        foreach ($rows as $invoice) {
            $row = $invoice->to_array();
            $party = $invoice->party_id ? Model_Core_Party::find((int) $invoice->party_id) : null;
            $row['party_name'] = $party ? (string) $party->name : '';
            $row['created_at'] = $invoice->created_at ? date('d/m/Y H:i', $invoice->created_at) : '';
            $items[] = $row;
        }

        return $items;
    }

    /**
     * GET ITEMS
     *
     * OBTIENE CONCEPTOS DE UNA FACTURA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_items($invoice_id)
    {
        # SE VALIDA FACTURA
        if ($invoice_id < 1) {
            return [];
        }

        # SE CONSULTAN CONCEPTOS
        $items = [];
        $rows = Model_Core_Billing_Invoice_Item::query()
            ->where('invoice_id', '=', $invoice_id)
            ->order_by('sort_order', 'asc')
            ->order_by('id', 'asc')
            ->get();

        foreach ($rows as $item) {
            $items[] = $item->to_array();
        }

        return $items;
    }

    /**
     * GET OPTIONS
     *
     * ENTREGA OPCIONES PARA FORMULARIOS
     *
     * @access  protected
     * @return  Array
     */
    protected function get_options()
    {
        # SE REGRESAN CATALOGOS RELACIONADOS
        return [
            'parties' => $this->select_options('core_parties', 'id', 'name'),
            'products' => $this->select_options('core_commerce_products', 'id', 'name'),
            'currencies' => $this->select_options('core_catalog_currencies', 'code', 'name'),
            'payment_terms' => $this->select_options('core_catalog_payment_terms', 'id', 'name'),
            'sat_cfdi_uses' => $this->select_options('core_sat_cfdi_uses', 'code', 'name'),
            'sat_payment_forms' => $this->select_options('core_sat_payment_forms', 'code', 'name'),
            'sat_payment_methods' => $this->select_options('core_sat_payment_methods', 'code', 'name'),
            'units' => $this->select_options('core_catalog_units', 'sat_unit_code', 'name'),
            'taxes' => $this->select_options('core_catalog_taxes', 'code', 'name'),
        ];
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES DE FACTURACION
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        # SE REGRESAN CONTADORES AGREGADOS
        return [
            'invoices' => (int) \DB::count_records('core_billing_invoices'),
            'draft' => (int) \DB::select()->from('core_billing_invoices')->where('status', '=', 'draft')->execute()->count(),
            'ready' => (int) \DB::select()->from('core_billing_invoices')->where('status', '=', 'ready')->execute()->count(),
            'stamped' => (int) \DB::select()->from('core_billing_invoices')->where('status', '=', 'stamped')->execute()->count(),
        ];
    }

    /**
     * RECALCULATE INVOICE
     *
     * RECALCULA TOTALES DESDE LOS CONCEPTOS ACTIVOS
     *
     * @access  protected
     * @return  Model_Core_Billing_Invoice
     */
    protected function recalculate_invoice($invoice_id)
    {
        # SE OBTIENE FACTURA
        $invoice = Model_Core_Billing_Invoice::find($invoice_id);
        if (!$invoice) {
            throw new \RuntimeException('Factura no encontrada.');
        }

        # SE SUMAN CONCEPTOS ACTIVOS
        $subtotal = 0;
        $discount = 0;
        $tax = 0;
        $retention = 0;
        $total = 0;
        foreach (Model_Core_Billing_Invoice_Item::query()->where('invoice_id', '=', $invoice_id)->where('active', '=', 1)->get() as $item) {
            $subtotal += ((float) $item->quantity * (float) $item->unit_price);
            $discount += (float) $item->discount_amount;
            $tax += (float) $item->tax_amount;
            $retention += (float) $item->retention_amount;
            $total += (float) $item->line_total;
        }

        # SE ACTUALIZAN TOTALES
        $invoice->set([
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discount, 2),
            'tax_total' => round($tax, 2),
            'retention_total' => round($retention, 2),
            'total' => round($total, 2),
            'balance_due' => round($total, 2),
        ]);
        $invoice->save();

        return $invoice;
    }

    protected function select_options($table, $value_field, $label_field)
    {
        $rows = \DB::select($value_field, $label_field)->from($table)->where('active', '=', 1)->order_by($label_field, 'asc')->execute();
        $options = [];
        foreach ($rows as $row) {
            $options[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
        }
        return $options;
    }

    protected function next_invoice_folio()
    {
        return 'FAC-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_billing_invoices') + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function assert_schema_ready()
    {
        foreach (['core_billing_invoices', 'core_billing_invoice_items', 'core_billing_invoice_events'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de facturacion.');
            }
        }
    }

    protected function codeify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
