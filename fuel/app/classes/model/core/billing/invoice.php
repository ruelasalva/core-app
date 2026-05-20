<?php

class Model_Core_Billing_Invoice extends \Orm\Model
{
    protected static $_table_name = 'core_billing_invoices';
    protected static $_primary_key = ['id'];

    protected static $_properties = [
        'id', 'folio', 'invoice_type', 'party_id', 'cfdi_id', 'fiscal_document_id', 'fiscal_mode', 'requires_waybill', 'pac_provider_code', 'pac_connection_id',
        'pac_series_id', 'pac_receptor_uid', 'pac_uid', 'uuid', 'sat_status', 'stamped_at', 'cancelled_at',
        'cancel_motive', 'cancel_substitute_uuid', 'pac_request_json', 'pac_response_json', 'xml_path',
        'pdf_path', 'source_module', 'source_entity_type', 'source_entity_id', 'issue_date', 'due_date',
        'currency_code', 'exchange_rate', 'payment_term_id',
        'sat_cfdi_use_code', 'sat_payment_form_code', 'sat_payment_method_code', 'subtotal', 'discount_total',
        'tax_total', 'retention_total', 'total', 'balance_due', 'status', 'notes', 'created_by', 'active',
        'created_at', 'updated_at',
    ];

    protected static $_observers = [
        'Orm\Observer_CreatedAt' => ['events' => ['before_insert'], 'property' => 'created_at', 'mysql_timestamp' => false],
        'Orm\Observer_UpdatedAt' => ['events' => ['before_save'], 'property' => 'updated_at', 'mysql_timestamp' => false],
    ];
}
