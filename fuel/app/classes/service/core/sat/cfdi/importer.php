<?php

/**
 * SERVICE CORE_SAT_CFDI_IMPORTER
 *
 * Guarda XML CFDI en el indice fiscal de Core-App y sus subdetalles para
 * auditoria SAT, Compras, Pagos y exportaciones a SAP.
 */
class Service_Core_Sat_Cfdi_Importer
{
    public function import_file($path, array $options = [])
    {
        $data = Helper_Core_Sat_Xml::parse_file($path);
        $data['xml_path'] = (string) \Arr::get($options, 'xml_path', $path);
        $data['origin'] = (string) \Arr::get($options, 'origin', 'manual');

        return $this->import_data($data);
    }

    public function import_data(array $data)
    {
        \DB::start_transaction();
        try {
            $cfdi = $this->save_cfdi($data);
            $this->replace_details($cfdi, $data);
            $this->replace_relations($cfdi, $data);
            $this->replace_payment_details($cfdi, $data);
            $this->sync_purchase_invoice($cfdi);

            \DB::commit_transaction();

            Helper_Core_Audit::log([
                'module' => 'sat',
                'action' => 'import_cfdi_xml',
                'entity_type' => 'sat_cfdi',
                'entity_id' => (int) $cfdi->id,
                'summary' => 'CFDI '.$cfdi->uuid.' importado desde XML.',
                'new_values' => $cfdi->to_array(),
            ]);

            return $cfdi;
        } catch (\Exception $e) {
            \DB::rollback_transaction();
            \Log::error('[SAT_CFDI_IMPORTER] Error importando CFDI: '.$e->getMessage());
            throw $e;
        }
    }

    protected function save_cfdi(array $data)
    {
        $uuid = strtoupper((string) \Arr::get($data, 'uuid', ''));
        if ($uuid === '') {
            throw new \RuntimeException('CFDI sin UUID.');
        }

        $cfdi = Model_Core_Sat_Cfdi::query()->where('uuid', '=', $uuid)->get_one();
        if (!$cfdi) {
            $cfdi = Model_Core_Sat_Cfdi::forge(['uuid' => $uuid]);
        }

        $cfdi->set([
            'uuid' => $uuid,
            'direction' => $this->direction($data),
            'version' => (string) \Arr::get($data, 'version', ''),
            'serie' => (string) \Arr::get($data, 'serie', ''),
            'folio' => (string) \Arr::get($data, 'folio', ''),
            'emitter_rfc' => strtoupper((string) \Arr::get($data, 'emitter_rfc', '')),
            'emitter_name' => (string) \Arr::get($data, 'emitter_name', ''),
            'emitter_regime' => (string) \Arr::get($data, 'emitter_regime', ''),
            'receiver_rfc' => strtoupper((string) \Arr::get($data, 'receiver_rfc', '')),
            'receiver_name' => (string) \Arr::get($data, 'receiver_name', ''),
            'receiver_regime' => (string) \Arr::get($data, 'receiver_regime', ''),
            'receiver_zip' => (string) \Arr::get($data, 'receiver_zip', ''),
            'issued_at' => $this->datetime((string) \Arr::get($data, 'issued_at', '')),
            'stamped_at' => $this->nullable_datetime((string) \Arr::get($data, 'stamped_at', '')),
            'total' => (float) \Arr::get($data, 'total', 0),
            'subtotal' => (float) \Arr::get($data, 'subtotal', 0),
            'discount' => (float) \Arr::get($data, 'discount', 0),
            'tax_transferred_total' => (float) \Arr::get($data, 'tax_transferred_total', 0),
            'tax_withheld_total' => (float) \Arr::get($data, 'tax_withheld_total', 0),
            'currency' => (string) \Arr::get($data, 'currency', 'MXN'),
            'exchange_rate' => \Arr::get($data, 'exchange_rate', null),
            'voucher_type' => (string) \Arr::get($data, 'voucher_type', ''),
            'export_code' => (string) \Arr::get($data, 'export_code', ''),
            'place_of_issue' => (string) \Arr::get($data, 'place_of_issue', ''),
            'payment_method' => (string) \Arr::get($data, 'payment_method', ''),
            'payment_form' => (string) \Arr::get($data, 'payment_form', ''),
            'conditions_payment' => (string) \Arr::get($data, 'conditions_payment', ''),
            'certificate_number' => (string) \Arr::get($data, 'certificate_number', ''),
            'certificate_sat_number' => (string) \Arr::get($data, 'certificate_sat_number', ''),
            'pac_rfc' => (string) \Arr::get($data, 'pac_rfc', ''),
            'seal_cfdi' => (string) \Arr::get($data, 'seal_cfdi', ''),
            'seal_sat' => (string) \Arr::get($data, 'seal_sat', ''),
            'cfdi_use' => (string) \Arr::get($data, 'cfdi_use', ''),
            'sat_status' => (string) \Arr::get($data, 'sat_status', 'vigente'),
            'sat_status_code' => (string) \Arr::get($data, 'sat_status_code', ''),
            'sat_status_message' => (string) \Arr::get($data, 'sat_status_message', ''),
            'cancelled_at' => (int) \Arr::get($data, 'cancelled_at', 0),
            'last_validated_at' => (int) \Arr::get($data, 'last_validated_at', 0),
            'metadata_seen_at' => (int) \Arr::get($data, 'metadata_seen_at', 0),
            'origin' => (string) \Arr::get($data, 'origin', 'manual'),
            'processed' => 0,
            'accounted' => 0,
            'xml_path' => (string) \Arr::get($data, 'xml_path', ''),
            'complements_json' => !empty($data['complements']) ? json_encode($data['complements']) : null,
            'has_payment_complement' => (int) \Arr::get($data, 'has_payment_complement', 0),
            'has_waybill' => (int) \Arr::get($data, 'has_waybill', 0),
            'missing_xml' => 0,
        ]);
        $cfdi->save();

        Model_Core_Sat_Cfdi_Event::forge([
            'cfdi_id' => (int) $cfdi->id,
            'event_type' => 'xml_imported',
            'payload_json' => json_encode([
                'origin' => (string) \Arr::get($data, 'origin', 'manual'),
                'xml_path' => (string) \Arr::get($data, 'xml_path', ''),
                'concepts' => count((array) \Arr::get($data, 'concepts', [])),
                'payments' => count((array) \Arr::get($data, 'payments', [])),
            ]),
        ])->save();

        return $cfdi;
    }

    protected function replace_details(Model_Core_Sat_Cfdi $cfdi, array $data)
    {
        \DB::delete('core_sat_cfdi_details')->where('cfdi_id', '=', (int) $cfdi->id)->execute();

        foreach ((array) \Arr::get($data, 'concepts', []) as $line) {
            $line['cfdi_id'] = (int) $cfdi->id;
            $line['metadata_json'] = !empty($line['taxes']) ? json_encode($line['taxes']) : null;
            unset($line['taxes']);
            Model_Core_Sat_Cfdi_Detail::forge($this->detail_data($line))->save();
        }

        foreach ((array) \Arr::get($data, 'relations', []) as $line) {
            Model_Core_Sat_Cfdi_Detail::forge($this->detail_data([
                'cfdi_id' => (int) $cfdi->id,
                'line_type' => 'related',
                'line_number' => (int) \Arr::get($line, 'line_number', 0),
                'related_uuid' => (string) \Arr::get($line, 'related_uuid', ''),
                'relation_type' => (string) \Arr::get($line, 'relation_type', ''),
            ]))->save();
        }

        foreach ((array) \Arr::get($data, 'payments', []) as $index => $line) {
            $line['cfdi_id'] = (int) $cfdi->id;
            $line['line_number'] = $index;
            Model_Core_Sat_Cfdi_Detail::forge($this->detail_data($line))->save();
        }
    }

    protected function detail_data(array $line)
    {
        return array_merge([
            'line_type' => 'concept',
            'line_number' => 0,
            'product_service_code' => '',
            'identification_number' => '',
            'unit_code' => '',
            'unit_name' => '',
            'description' => '',
            'tax_object' => '',
            'quantity' => null,
            'unit_value' => null,
            'discount' => 0,
            'amount' => 0,
            'vat_amount' => 0,
            'vat_rate' => '',
            'vat_base' => 0,
            'ieps_amount' => 0,
            'ieps_rate' => '',
            'ieps_base' => 0,
            'retention_amount' => 0,
            'ret_vat_amount' => 0,
            'ret_vat_rate' => '',
            'ret_vat_base' => 0,
            'ret_isr_amount' => 0,
            'ret_isr_rate' => '',
            'ret_isr_base' => 0,
            'related_uuid' => '',
            'relation_type' => '',
            'payment_uuid' => '',
            'payment_series' => '',
            'payment_folio' => '',
            'payment_currency' => '',
            'payment_equivalence' => null,
            'payment_method' => '',
            'payment_partiality' => 0,
            'payment_previous_balance' => 0,
            'payment_amount' => 0,
            'payment_remaining_balance' => 0,
            'metadata_json' => null,
        ], $line);
    }

    protected function replace_relations(Model_Core_Sat_Cfdi $cfdi, array $data)
    {
        \DB::delete('core_sat_cfdi_relations')->where('cfdi_id', '=', (int) $cfdi->id)->execute();

        foreach ((array) \Arr::get($data, 'relations', []) as $line) {
            $uuid = strtoupper((string) \Arr::get($line, 'related_uuid', ''));
            if ($uuid === '') {
                continue;
            }
            $related = Model_Core_Sat_Cfdi::query()->where('uuid', '=', $uuid)->get_one();
            Model_Core_Sat_Cfdi_Relation::forge([
                'cfdi_id' => (int) $cfdi->id,
                'related_uuid' => $uuid,
                'relation_type' => (string) \Arr::get($line, 'relation_type', ''),
                'related_cfdi_id' => $related ? (int) $related->id : null,
                'exists_in_system' => $related ? 1 : 0,
            ])->save();
        }
    }

    protected function replace_payment_details(Model_Core_Sat_Cfdi $cfdi, array $data)
    {
        \DB::delete('core_sat_payment_details')->where('payment_cfdi_id', '=', (int) $cfdi->id)->execute();

        foreach ((array) \Arr::get($data, 'payments', []) as $line) {
            $uuid = strtoupper((string) \Arr::get($line, 'payment_uuid', ''));
            if ($uuid === '') {
                continue;
            }
            $invoice = Model_Core_Sat_Cfdi::query()->where('uuid', '=', $uuid)->get_one();
            Model_Core_Sat_Payment_Detail::forge([
                'payment_cfdi_id' => (int) $cfdi->id,
                'invoice_cfdi_id' => $invoice ? (int) $invoice->id : 0,
                'invoice_uuid' => $uuid,
                'series' => (string) \Arr::get($line, 'payment_series', ''),
                'folio' => (string) \Arr::get($line, 'payment_folio', ''),
                'currency' => (string) \Arr::get($line, 'payment_currency', ''),
                'equivalence' => \Arr::get($line, 'payment_equivalence', null),
                'partiality_number' => (int) \Arr::get($line, 'payment_partiality', 0),
                'previous_balance' => (float) \Arr::get($line, 'payment_previous_balance', 0),
                'paid_amount' => (float) \Arr::get($line, 'payment_amount', 0),
                'remaining_balance' => (float) \Arr::get($line, 'payment_remaining_balance', 0),
                'tax_object' => (string) \Arr::get($line, 'tax_object', ''),
            ])->save();
        }
    }

    protected function sync_purchase_invoice(Model_Core_Sat_Cfdi $cfdi)
    {
        if (!\DBUtil::table_exists('core_purchase_invoices')) {
            return;
        }

        $invoice = Model_Core_Purchase_Invoice::query()->where('uuid', '=', (string) $cfdi->uuid)->get_one();
        if (!$invoice) {
            return;
        }

        $invoice->cfdi_id = (int) $cfdi->id;
        $invoice->sat_status = (string) $cfdi->sat_status;
        if ((float) $invoice->total <= 0) {
            $invoice->subtotal = (float) $cfdi->subtotal;
            $invoice->tax_total = (float) $cfdi->tax_transferred_total;
            $invoice->retention_total = (float) $cfdi->tax_withheld_total;
            $invoice->total = (float) $cfdi->total;
            $invoice->balance_due = (float) $cfdi->total;
        }
        $invoice->save();
    }

    protected function direction(array $data)
    {
        $row = \DB::select('rfc')->from('core_companies')->order_by('id', 'asc')->execute()->current();
        $company_rfc = strtoupper((string) \Arr::get($row ?: [], 'rfc', ''));
        if ($company_rfc !== '' && strtoupper((string) \Arr::get($data, 'emitter_rfc', '')) === $company_rfc) {
            return 'issued';
        }
        return 'received';
    }

    protected function datetime($value)
    {
        $time = strtotime($value);
        return $time ? date('Y-m-d H:i:s', $time) : date('Y-m-d H:i:s');
    }

    protected function nullable_datetime($value)
    {
        $time = strtotime($value);
        return $time ? date('Y-m-d H:i:s', $time) : null;
    }
}
