<?php

namespace Fuel\Migrations;

class Create_commission_configuration_platform
{
    public function up()
    {
        $this->commercial_plans();
        $this->versions();
        $this->rule_groups();
        $this->rules();
        $this->stages();
        $this->beneficiaries();
        $this->exclusions();
        $this->catalogs();
    }

    public function down()
    {
        foreach (array(
            'core_commission_config_catalogs',
            'core_commission_config_rule_exclusions',
            'core_commission_config_rule_beneficiaries',
            'core_commission_config_rule_stages',
            'core_commission_config_rules',
            'core_commission_config_rule_groups',
            'core_commission_config_versions',
            'core_commission_config_commercial_plans',
        ) as $table) {
            if (\DBUtil::table_exists($table)) {
                \DBUtil::drop_table($table);
            }
        }
    }

    protected function commercial_plans()
    {
        if (\DBUtil::table_exists('core_commission_config_commercial_plans')) {
            return;
        }

        \DBUtil::create_table('core_commission_config_commercial_plans', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'code' => array('type' => 'varchar', 'constraint' => 60),
            'name' => array('type' => 'varchar', 'constraint' => 180),
            'description' => array('type' => 'text', 'null' => true),
            'status' => array('type' => 'varchar', 'constraint' => 30, 'default' => 'draft'),
            'owner_user_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'created_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'), true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_commission_config_commercial_plans', 'code', 'idx_comm_cfg_plans_code', 'unique');
        \DBUtil::create_index('core_commission_config_commercial_plans', array('status', 'active'), 'idx_comm_cfg_plans_status');
    }

    protected function versions()
    {
        if (\DBUtil::table_exists('core_commission_config_versions')) {
            return;
        }

        \DBUtil::create_table('core_commission_config_versions', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'commercial_plan_id' => array('type' => 'int', 'constraint' => 11),
            'version_number' => array('type' => 'int', 'constraint' => 11, 'default' => 1),
            'code' => array('type' => 'varchar', 'constraint' => 80),
            'name' => array('type' => 'varchar', 'constraint' => 180),
            'status' => array('type' => 'varchar', 'constraint' => 30, 'default' => 'draft'),
            'valid_from' => array('type' => 'varchar', 'constraint' => 10, 'default' => ''),
            'valid_until' => array('type' => 'varchar', 'constraint' => 10, 'default' => ''),
            'notes' => array('type' => 'text', 'null' => true),
            'publish_reason' => array('type' => 'varchar', 'constraint' => 255, 'default' => ''),
            'config_snapshot_json' => array('type' => 'mediumtext', 'null' => true),
            'created_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'approved_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'published_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'approved_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'published_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'archived_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'), true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_commission_config_versions', 'code', 'idx_comm_cfg_versions_code', 'unique');
        \DBUtil::create_index('core_commission_config_versions', array('commercial_plan_id', 'version_number'), 'idx_comm_cfg_versions_plan_number');
        \DBUtil::create_index('core_commission_config_versions', array('status', 'active'), 'idx_comm_cfg_versions_status');
    }

    protected function rule_groups()
    {
        if (\DBUtil::table_exists('core_commission_config_rule_groups')) {
            return;
        }

        \DBUtil::create_table('core_commission_config_rule_groups', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'version_id' => array('type' => 'int', 'constraint' => 11),
            'code' => array('type' => 'varchar', 'constraint' => 80),
            'name' => array('type' => 'varchar', 'constraint' => 180),
            'description' => array('type' => 'text', 'null' => true),
            'priority' => array('type' => 'int', 'constraint' => 11, 'default' => 100),
            'enabled' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'owner_user_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'created_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'), true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_commission_config_rule_groups', 'code', 'idx_comm_cfg_groups_code', 'unique');
        \DBUtil::create_index('core_commission_config_rule_groups', array('version_id', 'priority'), 'idx_comm_cfg_groups_version_priority');
    }

    protected function rules()
    {
        if (\DBUtil::table_exists('core_commission_config_rules')) {
            return;
        }

        \DBUtil::create_table('core_commission_config_rules', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'version_id' => array('type' => 'int', 'constraint' => 11),
            'rule_group_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'code' => array('type' => 'varchar', 'constraint' => 80),
            'name' => array('type' => 'varchar', 'constraint' => 180),
            'description' => array('type' => 'text', 'null' => true),
            'business_notes' => array('type' => 'text', 'null' => true),
            'owner_user_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'priority' => array('type' => 'int', 'constraint' => 11, 'default' => 100),
            'enabled' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'accumulated' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'exclusive' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 0),
            'stop_processing' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 0),
            'valid_from' => array('type' => 'varchar', 'constraint' => 10, 'default' => ''),
            'valid_until' => array('type' => 'varchar', 'constraint' => 10, 'default' => ''),
            'event_code' => array('type' => 'varchar', 'constraint' => 60, 'default' => 'invoice_issued'),
            'calculation_base' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'subtotal'),
            'value_type' => array('type' => 'varchar', 'constraint' => 30, 'default' => 'percent'),
            'value' => array('type' => 'decimal', 'constraint' => '14,4', 'default' => 0),
            'margin_permission_required' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 0),
            'created_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'approved_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'approved_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'), true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_commission_config_rules', 'code', 'idx_comm_cfg_rules_code', 'unique');
        \DBUtil::create_index('core_commission_config_rules', array('version_id', 'priority'), 'idx_comm_cfg_rules_version_priority');
        \DBUtil::create_index('core_commission_config_rules', array('event_code', 'enabled'), 'idx_comm_cfg_rules_event');
    }

    protected function stages()
    {
        if (\DBUtil::table_exists('core_commission_config_rule_stages')) {
            return;
        }

        \DBUtil::create_table('core_commission_config_rule_stages', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'rule_id' => array('type' => 'int', 'constraint' => 11),
            'code' => array('type' => 'varchar', 'constraint' => 80),
            'name' => array('type' => 'varchar', 'constraint' => 180),
            'trigger_event' => array('type' => 'varchar', 'constraint' => 60, 'default' => 'invoice_issued'),
            'release_percent' => array('type' => 'decimal', 'constraint' => '8,4', 'default' => 100),
            'sort_order' => array('type' => 'int', 'constraint' => 11, 'default' => 100),
            'conditions_json' => array('type' => 'text', 'null' => true),
            'enabled' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'), true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_commission_config_rule_stages', array('rule_id', 'sort_order'), 'idx_comm_cfg_stages_rule_sort');
    }

    protected function beneficiaries()
    {
        if (\DBUtil::table_exists('core_commission_config_rule_beneficiaries')) {
            return;
        }

        \DBUtil::create_table('core_commission_config_rule_beneficiaries', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'rule_id' => array('type' => 'int', 'constraint' => 11),
            'beneficiary_type' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'salesperson'),
            'seller_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'user_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'party_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'percentage' => array('type' => 'decimal', 'constraint' => '8,4', 'default' => 100),
            'fixed_amount' => array('type' => 'decimal', 'constraint' => '14,2', 'default' => 0),
            'sort_order' => array('type' => 'int', 'constraint' => 11, 'default' => 100),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'), true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_commission_config_rule_beneficiaries', array('rule_id', 'sort_order'), 'idx_comm_cfg_beneficiaries_rule');
    }

    protected function exclusions()
    {
        if (\DBUtil::table_exists('core_commission_config_rule_exclusions')) {
            return;
        }

        \DBUtil::create_table('core_commission_config_rule_exclusions', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'rule_id' => array('type' => 'int', 'constraint' => 11),
            'exclusion_type' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'product'),
            'entity_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'entity_code' => array('type' => 'varchar', 'constraint' => 120, 'default' => ''),
            'behavior' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'skip_rule'),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'), true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_commission_config_rule_exclusions', array('rule_id', 'exclusion_type'), 'idx_comm_cfg_exclusions_rule');
    }

    protected function catalogs()
    {
        if (\DBUtil::table_exists('core_commission_config_catalogs')) {
            return;
        }

        \DBUtil::create_table('core_commission_config_catalogs', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'catalog_type' => array('type' => 'varchar', 'constraint' => 60),
            'code' => array('type' => 'varchar', 'constraint' => 80),
            'name' => array('type' => 'varchar', 'constraint' => 180),
            'description' => array('type' => 'text', 'null' => true),
            'sort_order' => array('type' => 'int', 'constraint' => 11, 'default' => 100),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'), true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_commission_config_catalogs', array('catalog_type', 'code'), 'idx_comm_cfg_catalog_type_code', 'unique');
    }
}
