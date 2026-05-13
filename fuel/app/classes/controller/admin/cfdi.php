<?php

/**
 * CONTROLADOR ADMIN_CFDI
 *
 * Auditoria fiscal de CFDI descargados, importados y relacionados con Compras.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Cfdi extends Controller_Adminbase
{
    public function before()
    {
        parent::before();
        $this->require_access('sat.access[view]');
    }

    public function action_index()
    {
        $this->template->title = 'Auditoria SAT';
        $this->template->content = View::forge('admin/cfdi/index');
    }

    public function action_data()
    {
        try {
            $this->assert_schema_ready();
            $filters = $this->filters();

            return $this->json_response([
                'filters' => $filters,
                'stats' => $this->stats($filters),
                'items' => $this->items($filters),
                'details' => $this->details(),
                'payments' => $this->payments(),
                'relations' => $this->relations(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando auditoria SAT: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar auditoria SAT.'], 500);
        }
    }

    public function post_import_xml()
    {
        $this->require_access('sat.access[import]');

        try {
            $file = \Input::file('file');
            if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return $this->json_response(['error' => 'Selecciona un XML valido.'], 422);
            }

            $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
            if ($extension !== 'xml') {
                return $this->json_response(['error' => 'Solo se permiten archivos XML.'], 422);
            }

            $relative_dir = 'assets/uploads/documents/sat/'.date('Y').'/'.date('m');
            $absolute_dir = DOCROOT.$relative_dir;
            if (!is_dir($absolute_dir)) {
                mkdir($absolute_dir, 0755, true);
            }

            $filename = time().'_'.\Str::random('alnum', 10).'.xml';
            $target = $absolute_dir.DS.$filename;
            if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
                return $this->json_response(['error' => 'No se pudo guardar el XML.'], 400);
            }

            $cfdi = (new Service_Core_Sat_Cfdi_Importer())->import_file($target, [
                'xml_path' => str_replace('\\', '/', $relative_dir.'/'.$filename),
                'origin' => 'admin_upload',
            ]);

            return $this->json_response([
                'status' => 'ok',
                'message' => 'CFDI '.$cfdi->uuid.' importado.',
                'cfdi_id' => (int) $cfdi->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error importando XML SAT: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function action_import_xml()
    {
        return $this->post_import_xml();
    }

    protected function filters()
    {
        $month = trim((string) \Input::get('month', date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        return [
            'month' => $month,
            'tab' => trim((string) \Input::get('tab', 'received')) ?: 'received',
            'q' => trim((string) \Input::get('q', '')),
        ];
    }

    protected function items(array $filters)
    {
        $query = \DB::select(
            'id', 'uuid', 'direction', 'voucher_type', 'serie', 'folio',
            'emitter_rfc', 'emitter_name', 'receiver_rfc', 'receiver_name',
            'issued_at', 'stamped_at', 'currency', 'subtotal', 'tax_transferred_total',
            'tax_withheld_total', 'total', 'sat_status', 'missing_xml',
            'has_payment_complement', 'has_waybill', 'sales_status', 'purchase_status',
            'portal_visible_customer', 'portal_visible_supplier', 'origin', 'xml_path'
        )->from('core_sat_cfdi');

        $this->apply_cfdi_scope($query);

        $start = $filters['month'].'-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($filters['month'].'-01'));
        $query->where('issued_at', '>=', $start)->where('issued_at', '<=', $end);

        if (in_array($filters['tab'], ['received', 'issued'], true)) {
            $query->where('direction', '=', $filters['tab']);
        } elseif ($filters['tab'] === 'cancelled') {
            $query->where('sat_status', '=', 'cancelado');
        } elseif ($filters['tab'] === 'payments') {
            $query->where('has_payment_complement', '=', 1);
        }

        if ($filters['q'] !== '') {
            $q = '%'.$filters['q'].'%';
            $query->where_open()
                ->where('uuid', 'like', $q)
                ->or_where('emitter_rfc', 'like', $q)
                ->or_where('receiver_rfc', 'like', $q)
                ->or_where('emitter_name', 'like', $q)
                ->or_where('receiver_name', 'like', $q)
            ->where_close();
        }

        $items = [];
        foreach ($query->order_by('issued_at', 'desc')->limit(300)->execute() as $row) {
            $row['issued_label'] = $row['issued_at'] ? date('d/m/Y H:i', strtotime($row['issued_at'])) : '';
            $row['type_label'] = $this->voucher_label((string) $row['voucher_type']);
            $items[] = $row;
        }

        return $items;
    }

    protected function details()
    {
        $cfdi_id = (int) \Input::get('cfdi_id', 0);
        if ($cfdi_id < 1) {
            return [];
        }

        return \DB::select()->from('core_sat_cfdi_details')
            ->where('cfdi_id', '=', $cfdi_id)
            ->order_by('line_type', 'asc')
            ->order_by('line_number', 'asc')
            ->execute()
            ->as_array();
    }

    protected function payments()
    {
        $query = \DB::select(['p.id', 'id'], ['c.uuid', 'payment_uuid'], ['p.invoice_uuid', 'invoice_uuid'], ['p.series', 'series'], ['p.folio', 'folio'], ['p.currency', 'currency'], ['p.partiality_number', 'partiality_number'], ['p.paid_amount', 'paid_amount'], ['p.remaining_balance', 'remaining_balance'])
            ->from(['core_sat_payment_details', 'p'])
            ->join(['core_sat_cfdi', 'c'], 'left')->on('c.id', '=', 'p.payment_cfdi_id');
        $this->apply_cfdi_scope($query, 'c');

        return $query
            ->order_by('p.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function relations()
    {
        $query = \DB::select(['r.id', 'id'], ['c.uuid', 'uuid'], ['r.related_uuid', 'related_uuid'], ['r.relation_type', 'relation_type'], ['r.exists_in_system', 'exists_in_system'])
            ->from(['core_sat_cfdi_relations', 'r'])
            ->join(['core_sat_cfdi', 'c'], 'left')->on('c.id', '=', 'r.cfdi_id');
        $this->apply_cfdi_scope($query, 'c');

        return $query
            ->order_by('r.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function stats(array $filters)
    {
        $start = $filters['month'].'-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($filters['month'].'-01'));

        return [
            'total_month' => $this->count_month($start, $end),
            'received' => $this->count_month($start, $end, ['direction' => 'received']),
            'issued' => $this->count_month($start, $end, ['direction' => 'issued']),
            'cancelled' => $this->count_month($start, $end, ['sat_status' => 'cancelado']),
            'payments' => $this->count_month($start, $end, ['has_payment_complement' => 1]),
            'relations' => (int) \DB::count_records('core_sat_cfdi_relations'),
            'details' => (int) \DB::count_records('core_sat_cfdi_details'),
        ];
    }

    protected function count_month($start, $end, array $where = [])
    {
        $query = \DB::select()->from('core_sat_cfdi')->where('issued_at', '>=', $start)->where('issued_at', '<=', $end);
        $this->apply_cfdi_scope($query);
        foreach ($where as $field => $value) {
            $query->where($field, '=', $value);
        }
        return (int) $query->execute()->count();
    }

    protected function apply_cfdi_scope($query, $alias = 'core_sat_cfdi')
    {
        if ($this->can_view_all_operational()) {
            return $query;
        }

        $party_ids = $this->scoped_party_ids();
        if (empty($party_ids)) {
            $query->where($alias.'.id', '=', -1);
            return $query;
        }

        $query->where_open()
            ->where($alias.'.customer_party_id', 'in', $party_ids)
            ->or_where($alias.'.supplier_party_id', 'in', $party_ids)
        ->where_close();

        return $query;
    }

    protected function scoped_party_ids()
    {
        $department_id = $this->employee_department_id();
        $query = \DB::select('id')->from('core_parties')->where('active', '=', 1);
        $query->where_open()
            ->where('sales_user_id', '=', (int) $this->user_id)
            ->or_where('buyer_user_id', '=', (int) $this->user_id);
        if ($department_id > 0) {
            $query->or_where('department_id', '=', $department_id);
        }
        $query->where_close();

        $ids = [];
        foreach ($query->execute() as $row) {
            $ids[] = (int) $row['id'];
        }
        return $ids;
    }

    protected function voucher_label($type)
    {
        $labels = [
            'I' => 'Ingreso',
            'E' => 'Egreso',
            'T' => 'Traslado',
            'P' => 'Pago',
            'N' => 'Nomina',
        ];
        return isset($labels[$type]) ? $labels[$type] : $type;
    }

    protected function assert_schema_ready()
    {
        foreach (['core_sat_cfdi', 'core_sat_cfdi_details', 'core_sat_payment_details', 'core_sat_cfdi_relations'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de Auditoria SAT.');
            }
        }
    }
}
