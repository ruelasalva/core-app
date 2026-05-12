<?php

namespace Fuel\Migrations;

class Create_core_payment_bank_tables
{
    public function up()
    {
        \DBUtil::create_table('core_payments', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'folio' => ['type' => 'varchar', 'constraint' => 40],
            'payment_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'received'],
            'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'bank_account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'integration_connection_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'payment_date' => ['type' => 'varchar', 'constraint' => 10],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'exchange_rate' => ['type' => 'decimal', 'constraint' => '14,6', 'default' => 1],
            'amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'sat_payment_form_code' => ['type' => 'varchar', 'constraint' => 10, 'default' => '99'],
            'reference' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'external_id' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
            'notes' => ['type' => 'text', 'null' => true],
            'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_payments', 'folio', 'idx_core_payments_folio', 'unique');
        \DBUtil::create_index('core_payments', ['party_id', 'status'], 'idx_core_payments_party_status');
        \DBUtil::create_index('core_payments', ['bank_account_id', 'payment_date'], 'idx_core_payments_account_date');

        \DBUtil::create_table('core_payment_allocations', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'payment_id' => ['type' => 'int', 'constraint' => 11],
            'entity_type' => ['type' => 'varchar', 'constraint' => 80],
            'entity_id' => ['type' => 'int', 'constraint' => 11],
            'amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_payment_allocations', 'payment_id', 'idx_core_payment_allocations_payment');
        \DBUtil::create_index('core_payment_allocations', ['entity_type', 'entity_id'], 'idx_core_payment_allocations_entity');

        \DBUtil::create_table('core_bank_movements', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'bank_account_id' => ['type' => 'int', 'constraint' => 11],
            'movement_date' => ['type' => 'varchar', 'constraint' => 10],
            'movement_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'deposit'],
            'amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
            'reference' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'source' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'manual'],
            'payment_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'reconciled' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_bank_movements', ['bank_account_id', 'movement_date'], 'idx_core_bank_movements_account_date');
        \DBUtil::create_index('core_bank_movements', 'payment_id', 'idx_core_bank_movements_payment');

        \DBUtil::create_table('core_bank_reconciliations', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'bank_account_id' => ['type' => 'int', 'constraint' => 11],
            'period_start' => ['type' => 'varchar', 'constraint' => 10],
            'period_end' => ['type' => 'varchar', 'constraint' => 10],
            'opening_balance' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'closing_balance' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
            'notes' => ['type' => 'text', 'null' => true],
            'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'closed_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'closed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_bank_reconciliations', ['bank_account_id', 'period_start', 'period_end'], 'idx_core_bank_reconciliations_period');
    }

    public function down()
    {
        \DBUtil::drop_table('core_bank_reconciliations');
        \DBUtil::drop_table('core_bank_movements');
        \DBUtil::drop_table('core_payment_allocations');
        \DBUtil::drop_table('core_payments');
    }
}
