<?php

namespace Fuel\Migrations;

class Enhance_bank_statement_reconciliation
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_bank_statement_imports')) {
            \DBUtil::create_table('core_bank_statement_imports', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'bank_account_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'source_format' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'csv'],
                'original_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'file_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'period_start' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'period_end' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'rows_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'imported_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'duplicate_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'processed'],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_bank_statement_imports', ['bank_account_id', 'period_start', 'period_end'], 'idx_bank_statement_period');
        }

        if (\DBUtil::table_exists('core_bank_movements') && !\DBUtil::field_exists('core_bank_movements', ['statement_import_id'])) {
            \DBUtil::add_fields('core_bank_movements', [
                'statement_import_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'source'],
                'balance_after' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0, 'after' => 'amount'],
                'checksum' => ['type' => 'varchar', 'constraint' => 64, 'default' => '', 'after' => 'description'],
                'source_row_json' => ['type' => 'text', 'null' => true, 'after' => 'checksum'],
            ]);
            \DBUtil::create_index('core_bank_movements', 'statement_import_id', 'idx_core_bank_movements_statement');
            \DBUtil::create_index('core_bank_movements', 'checksum', 'idx_core_bank_movements_checksum', 'unique');
        }

        if (!\DBUtil::table_exists('core_bank_reconciliation_suggestions')) {
            \DBUtil::create_table('core_bank_reconciliation_suggestions', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'movement_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'suggested_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'suggested_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'payment_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'received'],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'score' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'reasons_json' => ['type' => 'text', 'null' => true],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
                'applied_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'applied_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_bank_reconciliation_suggestions', ['movement_id', 'status'], 'idx_bank_rec_suggestions_movement');
            \DBUtil::create_index('core_bank_reconciliation_suggestions', ['suggested_entity_type', 'suggested_entity_id'], 'idx_bank_rec_suggestions_entity');
        }
    }

    public function down()
    {
        \DBUtil::drop_table('core_bank_reconciliation_suggestions');
        if (\DBUtil::table_exists('core_bank_movements') && \DBUtil::field_exists('core_bank_movements', ['statement_import_id'])) {
            \DBUtil::drop_fields('core_bank_movements', ['statement_import_id', 'balance_after', 'checksum', 'source_row_json']);
        }
        \DBUtil::drop_table('core_bank_statement_imports');
    }
}
