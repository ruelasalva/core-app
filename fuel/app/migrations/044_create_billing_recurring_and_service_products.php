<?php

namespace Fuel\Migrations;

class Create_billing_recurring_and_service_products
{
    public function up()
    {
        if (!\DBUtil::field_exists('core_commerce_products', ['product_type'])) {
            \DBUtil::add_fields('core_commerce_products', [
                'product_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'product', 'after' => 'subcategory_id'],
                'is_internal_service' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'product_type'],
            ]);
            \DBUtil::create_index('core_commerce_products', ['product_type', 'is_internal_service', 'published'], 'idx_core_commerce_products_type_public');
        }

        if (!\DBUtil::table_exists('core_billing_recurring_profiles')) {
            \DBUtil::create_table('core_billing_recurring_profiles', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'name' => ['type' => 'varchar', 'constraint' => 180],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'invoice_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'sale'],
                'frequency' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'monthly'],
                'start_date' => ['type' => 'varchar', 'constraint' => 10],
                'end_date' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'next_run_date' => ['type' => 'varchar', 'constraint' => 10],
                'last_run_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'auto_stamp' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'pac_connection_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'pac_series_id' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
                'pac_receptor_uid' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'exchange_rate' => ['type' => 'decimal', 'constraint' => '14,6', 'default' => 1],
                'payment_term_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'sat_cfdi_use_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'G03'],
                'sat_payment_form_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => '99'],
                'sat_payment_method_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => 'PPD'],
                'notes' => ['type' => 'text', 'null' => true],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'active'],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_billing_recurring_profiles', 'folio', 'idx_core_billing_recurring_profiles_folio', 'unique');
            \DBUtil::create_index('core_billing_recurring_profiles', ['next_run_date', 'status'], 'idx_core_billing_recurring_profiles_next');
            \DBUtil::create_index('core_billing_recurring_profiles', 'party_id', 'idx_core_billing_recurring_profiles_party');
        }

        if (!\DBUtil::table_exists('core_billing_recurring_items')) {
            \DBUtil::create_table('core_billing_recurring_items', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'profile_id' => ['type' => 'int', 'constraint' => 11],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'sat_product_service_code' => ['type' => 'varchar', 'constraint' => 20, 'default' => '01010101'],
                'description' => ['type' => 'varchar', 'constraint' => 255],
                'quantity' => ['type' => 'decimal', 'constraint' => '14,4', 'default' => 1],
                'unit_code' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'E48'],
                'sat_object_tax_code' => ['type' => 'varchar', 'constraint' => 5, 'default' => '02'],
                'unit_price' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'discount_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'tax_code' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'iva_16'],
                'tax_factor_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'Tasa'],
                'tax_rate' => ['type' => 'decimal', 'constraint' => '8,6', 'default' => 0],
                'retention_tax_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'retention_rate' => ['type' => 'decimal', 'constraint' => '8,6', 'default' => 0],
                'retention_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_billing_recurring_items', ['profile_id', 'sort_order'], 'idx_core_billing_recurring_items_profile');
        }

        if (!\DBUtil::table_exists('core_billing_recurring_runs')) {
            \DBUtil::create_table('core_billing_recurring_runs', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'profile_id' => ['type' => 'int', 'constraint' => 11],
                'invoice_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'run_date' => ['type' => 'varchar', 'constraint' => 10],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'created'],
                'message' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_billing_recurring_runs', ['profile_id', 'run_date'], 'idx_core_billing_recurring_runs_profile_date');
            \DBUtil::create_index('core_billing_recurring_runs', 'invoice_id', 'idx_core_billing_recurring_runs_invoice');
        }

        $this->seed_help();
    }

    public function down()
    {
        \DBUtil::drop_table('core_billing_recurring_runs');
        \DBUtil::drop_table('core_billing_recurring_items');
        \DBUtil::drop_table('core_billing_recurring_profiles');
        if (\DBUtil::field_exists('core_commerce_products', ['product_type'])) {
            \DBUtil::drop_fields('core_commerce_products', ['product_type', 'is_internal_service']);
        }
    }

    protected function seed_help()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            return;
        }
        if (\DB::select('id')->from('core_knowledge_articles')->where('code', '=', 'facturacion-recurrente')->execute()->current()) {
            return;
        }

        \DB::insert('core_knowledge_articles')->set([
            'code' => 'facturacion-recurrente',
            'title' => 'Facturacion recurrente',
            'category' => 'Facturacion',
            'summary' => 'Programacion de facturas periodicas para rentas, servicios y contratos mensuales.',
            'content' => '<h3>Objetivo</h3><p>Permite programar facturas recurrentes para rentas de equipo, servicios mensuales y contratos. El sistema genera facturas borrador por fecha de ejecucion y puede prepararlas para timbrado.</p><h3>Flujo recomendado</h3><ol><li>Da de alta el servicio en Productos y precios como tipo servicio interno.</li><li>Crea un perfil recurrente en Facturacion con cliente, frecuencia, fechas y datos SAT/PAC.</li><li>Agrega conceptos del servicio al perfil.</li><li>Ejecuta la generacion del periodo para crear la factura borrador.</li><li>Revisa y timbra con Factura.com.</li></ol><h3>Regla importante</h3><p>Los servicios internos no se muestran en el frontend aunque existan en el catalogo comercial.</p>',
            'sort_order' => 55,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }
}
