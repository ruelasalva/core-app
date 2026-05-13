<?php

namespace Fuel\Migrations;

class Add_cfdi_party_scope_controls
{
    public function up()
    {
        \DBUtil::add_fields('core_parties', [
            'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'party_type'],
            'sales_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'department_id'],
            'buyer_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'sales_user_id'],
        ]);
        \DBUtil::create_index('core_parties', 'department_id', 'idx_core_parties_department');
        \DBUtil::create_index('core_parties', 'sales_user_id', 'idx_core_parties_sales_user');
        \DBUtil::create_index('core_parties', 'buyer_user_id', 'idx_core_parties_buyer_user');

        \DBUtil::add_fields('core_sat_cfdi', [
            'emitter_party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'emitter_rfc'],
            'receiver_party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'receiver_rfc'],
            'customer_party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'receiver_party_id'],
            'supplier_party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'customer_party_id'],
            'sales_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => '', 'after' => 'has_waybill'],
            'purchase_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => '', 'after' => 'sales_status'],
            'portal_visible_customer' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'purchase_status'],
            'portal_visible_supplier' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'after' => 'portal_visible_customer'],
            'reviewed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'portal_visible_supplier'],
            'reviewed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'reviewed_by'],
        ]);
        \DBUtil::create_index('core_sat_cfdi', 'emitter_party_id', 'idx_core_sat_cfdi_emitter_party');
        \DBUtil::create_index('core_sat_cfdi', 'receiver_party_id', 'idx_core_sat_cfdi_receiver_party');
        \DBUtil::create_index('core_sat_cfdi', ['customer_party_id', 'portal_visible_customer'], 'idx_core_sat_cfdi_customer_portal');
        \DBUtil::create_index('core_sat_cfdi', ['supplier_party_id', 'portal_visible_supplier'], 'idx_core_sat_cfdi_supplier_portal');
        \DBUtil::create_index('core_sat_cfdi', ['purchase_status', 'sales_status'], 'idx_core_sat_cfdi_operational_status');
    }

    public function down()
    {
        \DBUtil::drop_fields('core_sat_cfdi', [
            'emitter_party_id',
            'receiver_party_id',
            'customer_party_id',
            'supplier_party_id',
            'sales_status',
            'purchase_status',
            'portal_visible_customer',
            'portal_visible_supplier',
            'reviewed_by',
            'reviewed_at',
        ]);

        \DBUtil::drop_fields('core_parties', [
            'department_id',
            'sales_user_id',
            'buyer_user_id',
        ]);
    }
}
