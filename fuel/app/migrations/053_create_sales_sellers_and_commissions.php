<?php

namespace Fuel\Migrations;

class Create_sales_sellers_and_commissions
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_sales_sellers')) {
            \DBUtil::create_table('core_sales_sellers', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 40],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'seller_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'employee'],
                'employee_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'default_commission_plan_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'base_commission_percent' => ['type' => 'decimal', 'constraint' => '8,4', 'default' => 0],
                'quota_commission_percent' => ['type' => 'decimal', 'constraint' => '8,4', 'default' => 0],
                'payment_commission_percent' => ['type' => 'decimal', 'constraint' => '8,4', 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_sales_sellers', 'code', 'idx_core_sales_sellers_code', 'unique');
            \DBUtil::create_index('core_sales_sellers', ['seller_type', 'active'], 'idx_core_sales_sellers_type');
            \DBUtil::create_index('core_sales_sellers', ['employee_id', 'party_id', 'user_id'], 'idx_core_sales_sellers_links');
        }

        if (\DBUtil::table_exists('core_sales_quotes') && !\DBUtil::field_exists('core_sales_quotes', ['seller_id'])) {
            \DBUtil::add_fields('core_sales_quotes', [
                'seller_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'party_id'],
            ]);
            \DBUtil::create_index('core_sales_quotes', ['seller_id', 'status'], 'idx_core_sales_quotes_seller_status');
        }

        if (\DBUtil::table_exists('core_sales_orders') && !\DBUtil::field_exists('core_sales_orders', ['seller_id'])) {
            \DBUtil::add_fields('core_sales_orders', [
                'seller_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'party_id'],
            ]);
            \DBUtil::create_index('core_sales_orders', ['seller_id', 'status'], 'idx_core_sales_orders_seller_status');
        }

        if (\DBUtil::table_exists('core_parties') && !\DBUtil::field_exists('core_parties', ['default_seller_id'])) {
            \DBUtil::add_fields('core_parties', [
                'default_seller_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'sales_user_id'],
            ]);
            \DBUtil::create_index('core_parties', 'default_seller_id', 'idx_core_parties_default_seller');
        }

        if (!\DBUtil::table_exists('core_commission_plans')) {
            \DBUtil::create_table('core_commission_plans', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 40],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'applies_to' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'all'],
                'valid_from' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'valid_until' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'description' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_commission_plans', 'code', 'idx_core_commission_plans_code', 'unique');
        }

        if (!\DBUtil::table_exists('core_commission_rules')) {
            \DBUtil::create_table('core_commission_rules', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'plan_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'code' => ['type' => 'varchar', 'constraint' => 40],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'rule_scope' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'general'],
                'seller_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'brand_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'category_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'subcategory_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'trigger_event' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'sale'],
                'calculation_base' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'line_total'],
                'value_type' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'percent'],
                'value' => ['type' => 'decimal', 'constraint' => '12,4', 'default' => 0],
                'min_quantity' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'min_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'priority' => ['type' => 'int', 'constraint' => 11, 'default' => 100],
                'stackable' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'valid_from' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'valid_until' => ['type' => 'varchar', 'constraint' => 10, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_commission_rules', 'code', 'idx_core_commission_rules_code', 'unique');
            \DBUtil::create_index('core_commission_rules', ['plan_id', 'trigger_event', 'active'], 'idx_core_commission_rules_plan_event');
            \DBUtil::create_index('core_commission_rules', ['seller_id', 'party_id', 'product_id'], 'idx_core_commission_rules_scope');
        }

        if (!\DBUtil::table_exists('core_commission_quotas')) {
            \DBUtil::create_table('core_commission_quotas', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'seller_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'plan_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'period_code' => ['type' => 'varchar', 'constraint' => 20],
                'date_from' => ['type' => 'varchar', 'constraint' => 10],
                'date_to' => ['type' => 'varchar', 'constraint' => 10],
                'target_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'target_quantity' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'bonus_percent' => ['type' => 'decimal', 'constraint' => '8,4', 'default' => 0],
                'bonus_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'open'],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_commission_quotas', ['seller_id', 'period_code'], 'idx_core_commission_quotas_seller_period');
        }

        if (!\DBUtil::table_exists('core_commission_entries')) {
            \DBUtil::create_table('core_commission_entries', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'seller_id' => ['type' => 'int', 'constraint' => 11],
                'plan_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'rule_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'quota_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'trigger_event' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'sale'],
                'source_module' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'sales'],
                'source_entity_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'sales_order'],
                'source_entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'source_item_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'product_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'base_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'commission_percent' => ['type' => 'decimal', 'constraint' => '8,4', 'default' => 0],
                'commission_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
                'earned_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'settlement_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_commission_entries', ['seller_id', 'status'], 'idx_core_commission_entries_seller_status');
            \DBUtil::create_index('core_commission_entries', ['source_entity_type', 'source_entity_id', 'source_item_id', 'trigger_event'], 'idx_core_commission_entries_source');
            \DBUtil::create_index('core_commission_entries', 'settlement_id', 'idx_core_commission_entries_settlement');
        }

        if (!\DBUtil::table_exists('core_commission_settlements')) {
            \DBUtil::create_table('core_commission_settlements', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'seller_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'date_from' => ['type' => 'varchar', 'constraint' => 10],
                'date_to' => ['type' => 'varchar', 'constraint' => 10],
                'currency_code' => ['type' => 'varchar', 'constraint' => 3, 'default' => 'MXN'],
                'subtotal' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'adjustment_total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'total' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'draft'],
                'payment_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'notes' => ['type' => 'text', 'null' => true],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_commission_settlements', 'folio', 'idx_core_commission_settlements_folio', 'unique');
            \DBUtil::create_index('core_commission_settlements', ['seller_id', 'status'], 'idx_core_commission_settlements_seller_status');
        }

        if (!\DBUtil::table_exists('core_commission_adjustments')) {
            \DBUtil::create_table('core_commission_adjustments', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'entry_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'settlement_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'seller_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'adjustment_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'manual'],
                'amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'reason' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_commission_adjustments', ['seller_id', 'settlement_id'], 'idx_core_commission_adjustments_seller');
        }
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_commission_adjustments')) {
            \DBUtil::drop_table('core_commission_adjustments');
        }
        if (\DBUtil::table_exists('core_commission_settlements')) {
            \DBUtil::drop_table('core_commission_settlements');
        }
        if (\DBUtil::table_exists('core_commission_entries')) {
            \DBUtil::drop_table('core_commission_entries');
        }
        if (\DBUtil::table_exists('core_commission_quotas')) {
            \DBUtil::drop_table('core_commission_quotas');
        }
        if (\DBUtil::table_exists('core_commission_rules')) {
            \DBUtil::drop_table('core_commission_rules');
        }
        if (\DBUtil::table_exists('core_commission_plans')) {
            \DBUtil::drop_table('core_commission_plans');
        }
        if (\DBUtil::table_exists('core_parties') && \DBUtil::field_exists('core_parties', ['default_seller_id'])) {
            \DBUtil::drop_fields('core_parties', ['default_seller_id']);
        }
        if (\DBUtil::table_exists('core_sales_orders') && \DBUtil::field_exists('core_sales_orders', ['seller_id'])) {
            \DBUtil::drop_fields('core_sales_orders', ['seller_id']);
        }
        if (\DBUtil::table_exists('core_sales_quotes') && \DBUtil::field_exists('core_sales_quotes', ['seller_id'])) {
            \DBUtil::drop_fields('core_sales_quotes', ['seller_id']);
        }
        if (\DBUtil::table_exists('core_sales_sellers')) {
            \DBUtil::drop_table('core_sales_sellers');
        }
    }
}
