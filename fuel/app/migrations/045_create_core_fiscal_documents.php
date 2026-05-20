<?php

namespace Fuel\Migrations;

class Create_core_fiscal_documents
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_fiscal_documents')) {
            \DBUtil::create_table('core_fiscal_documents', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'document_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'invoice'],
                'cfdi_version' => ['type' => 'varchar', 'constraint' => 10, 'default' => '4.0'],
                'voucher_type' => ['type' => 'varchar', 'constraint' => 5, 'default' => 'I'],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'source_module' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'manual'],
                'source_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'source_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'source_folio' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
                'fiscal_mode' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'fiscal_required'],
                'pac_provider_code' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'factura_com'],
                'pac_connection_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'pac_series_id' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
                'pac_uid' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'uuid' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
                'related_uuid' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
                'sat_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
                'workflow_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
                'issue_date' => ['type' => 'varchar', 'constraint' => 10],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'payload_json' => ['type' => 'text', 'null' => true],
                'response_json' => ['type' => 'text', 'null' => true],
                'xml_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'pdf_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_fiscal_documents', 'folio', 'idx_core_fiscal_documents_folio', 'unique');
            \DBUtil::create_index('core_fiscal_documents', ['document_type', 'workflow_status'], 'idx_core_fiscal_documents_type_status');
            \DBUtil::create_index('core_fiscal_documents', ['source_entity_type', 'source_entity_id'], 'idx_core_fiscal_documents_source');
            \DBUtil::create_index('core_fiscal_documents', 'uuid', 'idx_core_fiscal_documents_uuid');
        }

        if (\DBUtil::table_exists('core_billing_invoices') && !\DBUtil::field_exists('core_billing_invoices', ['fiscal_document_id'])) {
            \DBUtil::add_fields('core_billing_invoices', [
                'fiscal_document_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'cfdi_id'],
                'fiscal_mode' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'fiscal_required', 'after' => 'fiscal_document_id'],
                'requires_waybill' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'fiscal_mode'],
            ]);
            \DBUtil::create_index('core_billing_invoices', 'fiscal_document_id', 'idx_core_billing_invoices_fiscal_document');
        }

        if (\DBUtil::table_exists('core_payments') && !\DBUtil::field_exists('core_payments', ['fiscal_document_id'])) {
            \DBUtil::add_fields('core_payments', [
                'fiscal_document_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'integration_connection_id'],
                'fiscal_mode' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'system_only', 'after' => 'fiscal_document_id'],
                'rep_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'not_required', 'after' => 'fiscal_mode'],
            ]);
            \DBUtil::create_index('core_payments', ['fiscal_mode', 'rep_status'], 'idx_core_payments_fiscal_mode');
        }

        if (\DBUtil::table_exists('core_inventory_movements') && !\DBUtil::field_exists('core_inventory_movements', ['fiscal_document_id'])) {
            \DBUtil::add_fields('core_inventory_movements', [
                'fiscal_document_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'related_entity_id'],
                'requires_fiscal_transfer' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'fiscal_document_id'],
            ]);
            \DBUtil::create_index('core_inventory_movements', 'fiscal_document_id', 'idx_core_inventory_movements_fiscal_document');
        }

        $this->seed_help();
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_inventory_movements') && \DBUtil::field_exists('core_inventory_movements', ['fiscal_document_id'])) {
            \DBUtil::drop_fields('core_inventory_movements', ['fiscal_document_id', 'requires_fiscal_transfer']);
        }
        if (\DBUtil::table_exists('core_payments') && \DBUtil::field_exists('core_payments', ['fiscal_document_id'])) {
            \DBUtil::drop_fields('core_payments', ['fiscal_document_id', 'fiscal_mode', 'rep_status']);
        }
        if (\DBUtil::table_exists('core_billing_invoices') && \DBUtil::field_exists('core_billing_invoices', ['fiscal_document_id'])) {
            \DBUtil::drop_fields('core_billing_invoices', ['fiscal_document_id', 'fiscal_mode', 'requires_waybill']);
        }
        \DBUtil::drop_table('core_fiscal_documents');
    }

    protected function seed_help()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            return;
        }
        if (\DB::select('id')->from('core_knowledge_articles')->where('code', '=', 'cfdi_documentos_fiscales_transversales')->execute()->current()) {
            return;
        }

        \DB::insert('core_knowledge_articles')->set([
            'code' => 'cfdi_documentos_fiscales_transversales',
            'title' => 'CFDI transversal: ventas, pagos e inventario',
            'category' => 'SAT',
            'summary' => 'Base para separar operacion ERP de documentos fiscales timbrables o solo internos.',
            'content' => '<h3>Objetivo</h3><p>Core-App separa el documento operativo del documento fiscal. Una venta, pago, nota, traslado o devolucion puede afectar el ERP sin timbrarse, o puede preparar un CFDI para timbrado con PAC.</p><h4>Documentos contemplados</h4><ul><li><strong>Factura</strong>: nace normalmente desde entrega o facturacion directa.</li><li><strong>Complemento de pago REP</strong>: nace desde Pagos y Bancos cuando se cobra una factura PPD.</li><li><strong>Nota de credito/devolucion</strong>: puede timbrarse como egreso o quedar como ajuste interno.</li><li><strong>Carta porte / traslado</strong>: nace desde inventario/logistica cuando el movimiento requiere CFDI de traslado.</li><li><strong>Retencion</strong>: queda preparada para pagos o servicios donde aplique constancia fiscal.</li></ul><h4>Regla clave</h4><p>El campo <code>fiscal_mode</code> decide si el movimiento es <strong>solo sistema</strong>, <strong>fiscal requerido</strong> o <strong>fiscal opcional</strong>. El timbrado real siempre debe pasar por Integraciones/PAC y conservar payload, respuesta, UUID y estado SAT.</p>',
            'sort_order' => 56,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }
}
