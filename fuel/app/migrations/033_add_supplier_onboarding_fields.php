<?php

namespace Fuel\Migrations;

class Add_supplier_onboarding_fields
{
    public function up()
    {
        \DBUtil::add_fields('core_parties', [
            'onboarding_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'approved', 'after' => 'notes'],
            'onboarding_notes' => ['type' => 'text', 'null' => true, 'after' => 'onboarding_status'],
            'reviewed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'onboarding_notes'],
            'reviewed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'reviewed_by'],
        ]);
        \DBUtil::create_index('core_parties', ['party_type', 'onboarding_status', 'active'], 'idx_core_parties_onboarding');
    }

    public function down()
    {
        \DBUtil::drop_fields('core_parties', [
            'onboarding_status',
            'onboarding_notes',
            'reviewed_by',
            'reviewed_at',
        ]);
    }
}
