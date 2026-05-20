<?php

/**
 * CONTROLADOR PROVEEDORES_COMPRAS
 *
 * Ordenes, facturas, contrarecibos y evidencias visibles en el portal de proveedores.
 *
 * @package  app
 * @extends  Controller_Proveedores_Base
 */
class Controller_Proveedores_Compras extends Controller_Proveedores_Base
{
    /**
     * INDEX
     *
     * MUESTRA ORDENES, FACTURAS Y CONTRARECIBOS DEL PROVEEDOR LOGUEADO.
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        $this->render_compras();
    }

    /**
     * RENDER COMPRAS
     *
     * CENTRALIZA LA VISTA DE COMPRAS PARA EVITAR RESOLUCION DINAMICA HACIA EL INDEX DEL CONTROLADOR HIJO.
     *
     * @access  protected
     * @return  Void
     */
    protected function render_compras()
    {
        $this->template->title = 'Compras';
        $this->template->content = View::forge('proveedores/compras/index', ['portal_code' => $this->portal_code]);
    }

    /**
     * COMPRAS
     *
     * MANTIENE COMPATIBILIDAD CON LA RUTA /proveedores/compras.
     *
     * @access  public
     * @return  Void
     */
    public function action_compras()
    {
        $this->render_compras();
    }

    /**
     * DATA
     *
     * ENTREGA DOCUMENTOS DE COMPRA FILTRADOS POR TERCERO DEL PORTAL.
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            return $this->purchase_payload($party_id);
        } catch (\Exception $e) {
            \Log::error('Error cargando compras portal proveedores: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar compras.'], 500);
        }
    }

    /**
     * COMPRAS DATA
     *
     * MANTIENE COMPATIBILIDAD CON LA RUTA /proveedores/compras_data.
     *
     * @access  public
     * @return  Response
     */
    public function action_compras_data()
    {
        return $this->action_data();
    }

    /**
     * FACTURA
     *
     * PERMITE AL PROVEEDOR REGISTRAR UNA FACTURA CONTRA UNA ORDEN.
     *
     * @access  public
     * @return  Response
     */
    public function post_invoice()
    {
        $val = (array) \Input::json();

        try {
            $party_id = (int) $this->portal_link->party_id;
            $order_id = (int) \Arr::get($val, 'order_id', 0);
            if ($order_id > 0 && !$this->portal_order($order_id, $party_id)) {
                return $this->json_response(['error' => 'Orden no encontrada.'], 404);
            }

            $subtotal = max(0, (float) \Arr::get($val, 'subtotal', 0));
            $tax_total = max(0, (float) \Arr::get($val, 'tax_total', 0));
            $retention_total = max(0, (float) \Arr::get($val, 'retention_total', 0));
            $total = max(0, (float) \Arr::get($val, 'total', $subtotal + $tax_total - $retention_total));
            if ($total <= 0) {
                return $this->json_response(['error' => 'Captura el total de la factura.'], 422);
            }

            $invoice = Model_Core_Purchase_Invoice::forge([
                'folio' => $this->next_purchase_folio('FCP', 'core_purchase_invoices'),
                'party_id' => $party_id,
                'order_id' => $order_id,
                'uuid' => strtoupper(trim((string) \Arr::get($val, 'uuid', ''))),
                'invoice_date' => trim((string) \Arr::get($val, 'invoice_date', date('Y-m-d'))),
                'due_date' => trim((string) \Arr::get($val, 'due_date', '')),
                'currency_code' => 'MXN',
                'subtotal' => $subtotal,
                'tax_total' => $tax_total,
                'retention_total' => $retention_total,
                'total' => $total,
                'balance_due' => $total,
                'status' => 'submitted',
                'validation_status' => 'pending',
                'message' => trim((string) \Arr::get($val, 'message', '')),
                'created_by' => $this->user_id,
                'active' => 1,
            ]);
            $invoice->save();

            Helper_Core_Audit::log([
                'module' => 'purchases',
                'action' => 'portal_create_invoice',
                'business_event' => 'purchases.portal_create_invoice',
                'entity_type' => 'purchase_invoice',
                'entity_id' => (int) $invoice->id,
                'table_name' => 'core_purchase_invoices',
                'portal_code' => $this->portal_code,
                'backend' => 'portal',
                'summary' => 'Factura proveedor '.$invoice->folio.' creada desde portal',
                'new_values' => $invoice->to_array(),
            ]);

            return $this->purchase_payload($party_id, ['status' => 'ok']);
        } catch (\Exception $e) {
            \Log::error('Error creando factura portal proveedor: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo crear la factura.'], 400);
        }
    }

    public function action_invoice()
    {
        return $this->post_invoice();
    }

    public function post_compras_invoice()
    {
        return $this->post_invoice();
    }

    public function action_compras_invoice()
    {
        return $this->post_invoice();
    }

    /**
     * UPLOAD
     *
     * ADJUNTA PDF, XML O EVIDENCIA A UNA ORDEN O FACTURA DEL PROVEEDOR.
     *
     * @access  public
     * @return  Response
     */
    public function post_upload()
    {
        try {
            $party_id = (int) $this->portal_link->party_id;
            $entity_type = $this->purchase_entity_type((string) \Input::post('entity_type', ''));
            $entity_id = (int) \Input::post('entity_id', 0);
            if (!$this->can_access_purchase_entity($entity_type, $entity_id, $party_id)) {
                return $this->json_response(['error' => 'Registro no encontrado.'], 404);
            }

            $document = $this->store_purchase_document($entity_type, $entity_id);
            Helper_Core_Audit::log([
                'module' => 'purchases',
                'action' => 'portal_upload_document',
                'business_event' => 'purchases.portal_upload_document',
                'entity_type' => 'document',
                'entity_id' => (int) $document->id,
                'portal_code' => $this->portal_code,
                'backend' => 'portal',
                'summary' => 'Documento de compras adjuntado desde portal',
                'new_values' => $document->to_array(),
            ]);

            return $this->purchase_payload($party_id, ['status' => 'ok']);
        } catch (\Exception $e) {
            \Log::error('Error adjuntando compras portal proveedor: '.$e->getMessage());
            return $this->json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function action_upload()
    {
        return $this->post_upload();
    }

    public function post_compras_upload()
    {
        return $this->post_upload();
    }

    public function action_compras_upload()
    {
        return $this->post_upload();
    }

    protected function purchase_payload($party_id, array $extra = [])
    {
        return $this->json_response(array_merge([
            'orders' => $this->purchase_orders($party_id),
            'invoices' => $this->purchase_invoices($party_id),
            'receipts' => $this->purchase_receipts($party_id),
            'documents' => $this->purchase_documents($party_id),
        ], $extra));
    }

    protected function purchase_orders($party_id)
    {
        $rows = \DB::select('id', 'folio', 'order_date', 'expected_date', 'currency_code', 'total', 'invoiced_total', 'balance_total', 'status', 'notes')
            ->from('core_purchase_orders')
            ->where('party_id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
        foreach ($rows as &$row) {
            $row['items'] = \DB::select('description', 'quantity', 'unit_price', 'line_total')
                ->from('core_purchase_order_items')
                ->where('order_id', '=', (int) $row['id'])
                ->where('active', '=', 1)
                ->order_by('sort_order', 'asc')
                ->execute()
                ->as_array();
        }
        return $rows;
    }

    protected function purchase_invoices($party_id)
    {
        return \DB::select(['i.id', 'id'], ['i.folio', 'folio'], ['i.order_id', 'order_id'], ['o.folio', 'order_folio'], ['i.uuid', 'uuid'], ['i.invoice_date', 'invoice_date'], ['i.due_date', 'due_date'], ['i.currency_code', 'currency_code'], ['i.total', 'total'], ['i.balance_due', 'balance_due'], ['i.status', 'status'], ['i.validation_status', 'validation_status'], ['i.message', 'message'])
            ->from(['core_purchase_invoices', 'i'])
            ->join(['core_purchase_orders', 'o'], 'left')->on('i.order_id', '=', 'o.id')
            ->where('i.party_id', '=', (int) $party_id)
            ->where('i.active', '=', 1)
            ->order_by('i.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function purchase_receipts($party_id)
    {
        return \DB::select('id', 'folio', 'issue_date', 'scheduled_payment_date', 'currency_code', 'total', 'status', 'notes')
            ->from('core_purchase_receipts')
            ->where('party_id', '=', (int) $party_id)
            ->where('active', '=', 1)
            ->order_by('id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function purchase_documents($party_id)
    {
        $order_ids = array_map(function ($row) { return (int) $row['id']; }, $this->purchase_orders($party_id));
        $invoice_ids = array_map(function ($row) { return (int) $row['id']; }, $this->purchase_invoices($party_id));
        $receipt_ids = array_map(function ($row) { return (int) $row['id']; }, $this->purchase_receipts($party_id));
        $parts = [];
        if (!empty($order_ids)) {
            $parts[] = ['purchase_order', $order_ids];
        }
        if (!empty($invoice_ids)) {
            $parts[] = ['purchase_invoice', $invoice_ids];
        }
        if (!empty($receipt_ids)) {
            $parts[] = ['purchase_receipt', $receipt_ids];
        }
        if (empty($parts)) {
            return [];
        }

        $documents = [];
        foreach ($parts as $part) {
            $rows = \DB::select(['d.id', 'id'], ['l.entity_type', 'entity_type'], ['l.entity_id', 'entity_id'], ['l.relation_type', 'relation_type'], ['l.notes', 'link_notes'], ['d.document_type', 'document_type'], ['d.title', 'title'], ['d.description', 'description'], ['d.original_name', 'original_name'], ['d.file_path', 'file_path'], ['d.file_extension', 'file_extension'], ['d.file_size', 'file_size'], ['d.is_evidence', 'is_evidence'], ['d.created_at', 'created_at'])
                ->from(['core_document_links', 'l'])
                ->join(['core_documents', 'd'], 'inner')->on('d.id', '=', 'l.document_id')
                ->where('l.entity_type', '=', $part[0])
                ->where('l.entity_id', 'in', $part[1])
                ->where('l.active', '=', 1)
                ->where('d.active', '=', 1)
                ->where('d.visibility', 'in', ['portal', 'public'])
                ->order_by('d.id', 'desc')
                ->execute()
                ->as_array();
            $documents = array_merge($documents, $rows);
        }
        return $documents;
    }

    protected function portal_order($order_id, $party_id)
    {
        return \DB::select('id')->from('core_purchase_orders')->where('id', '=', (int) $order_id)->where('party_id', '=', (int) $party_id)->where('active', '=', 1)->execute()->current();
    }

    protected function can_access_purchase_entity($entity_type, $entity_id, $party_id)
    {
        if ($entity_type === 'purchase_order') {
            return (bool) $this->portal_order($entity_id, $party_id);
        }
        if ($entity_type === 'purchase_invoice') {
            return (bool) \DB::select('id')->from('core_purchase_invoices')->where('id', '=', (int) $entity_id)->where('party_id', '=', (int) $party_id)->where('active', '=', 1)->execute()->current();
        }
        if ($entity_type === 'purchase_receipt') {
            return (bool) \DB::select('id')->from('core_purchase_receipts')->where('id', '=', (int) $entity_id)->where('party_id', '=', (int) $party_id)->where('active', '=', 1)->execute()->current();
        }
        return false;
    }

    protected function purchase_entity_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['purchase_order', 'purchase_invoice', 'purchase_receipt'], true) ? $value : '';
    }

    protected function store_purchase_document($entity_type, $entity_id)
    {
        $file = \Input::file('file');
        if (!$file || (int) \Arr::get($file, 'error', UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Selecciona un archivo valido.');
        }
        $extension = strtolower(pathinfo((string) \Arr::get($file, 'name', ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'], true)) {
            throw new \RuntimeException('Tipo de archivo no permitido.');
        }
        if ((int) \Arr::get($file, 'size', 0) > 15728640) {
            throw new \RuntimeException('El archivo no puede superar 15 MB.');
        }
        $relative_dir = 'assets/uploads/documents/purchases/'.date('Y').'/'.date('m');
        $absolute_dir = DOCROOT.$relative_dir;
        if (!is_dir($absolute_dir)) {
            mkdir($absolute_dir, 0755, true);
        }
        $base_name = pathinfo((string) \Arr::get($file, 'name', 'documento'), PATHINFO_FILENAME);
        $filename = time().'_'.\Str::random('alnum', 12).'_'.$this->codeify($base_name).'.'.$extension;
        $target = $absolute_dir.DS.$filename;
        if (!@move_uploaded_file((string) \Arr::get($file, 'tmp_name', ''), $target)) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }

        $document = Model_Core_Document::forge([
            'document_type' => $this->document_type((string) \Input::post('document_type', $entity_type)),
            'title' => trim((string) \Input::post('title', '')) ?: $base_name,
            'description' => trim((string) \Input::post('description', '')),
            'file_path' => str_replace('\\', '/', $relative_dir.'/'.$filename),
            'original_name' => (string) \Arr::get($file, 'name', ''),
            'mime_type' => (string) \Arr::get($file, 'type', ''),
            'file_extension' => $extension,
            'file_size' => (int) \Arr::get($file, 'size', 0),
            'checksum' => is_file($target) ? hash_file('sha256', $target) : '',
            'visibility' => 'portal',
            'is_evidence' => 1,
            'uploaded_by' => $this->user_id,
            'active' => 1,
        ]);
        $document->save();

        Model_Core_Document_Link::forge([
            'document_id' => (int) $document->id,
            'entity_type' => $entity_type,
            'entity_id' => (int) $entity_id,
            'relation_type' => $this->document_relation((string) \Input::post('relation_type', 'evidence')),
            'notes' => trim((string) \Input::post('notes', '')),
            'created_by' => $this->user_id,
            'active' => 1,
        ])->save();

        return $document;
    }

    protected function document_type($value)
    {
        $value = $this->codeify($value);
        $allowed = ['purchase_order', 'purchase_invoice', 'purchase_receipt', 'delivery_evidence', 'payment_evidence', 'tax_document', 'other_evidence'];
        return in_array($value, $allowed, true) ? $value : 'other_evidence';
    }

    protected function document_relation($value)
    {
        $value = $this->codeify($value);
        $allowed = ['attachment', 'evidence', 'invoice_file', 'xml_file', 'delivery_proof', 'payment_proof'];
        return in_array($value, $allowed, true) ? $value : 'evidence';
    }

    protected function next_purchase_folio($prefix, $table)
    {
        $base = $prefix.'-'.date('Ymd').'-';
        $row = \DB::select(\DB::expr('COUNT(*) as total'))->from($table)->where('folio', 'like', $base.'%')->execute()->current();
        return $base.str_pad(((int) $row['total']) + 1, 5, '0', STR_PAD_LEFT);
    }
}
