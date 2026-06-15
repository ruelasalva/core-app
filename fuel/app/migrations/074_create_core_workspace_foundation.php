<?php

namespace Fuel\Migrations;

class Create_core_workspace_foundation
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_workspace_widget_catalog')) {
            \DBUtil::create_table('core_workspace_widget_catalog', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 80],
                'title' => ['type' => 'varchar', 'constraint' => 160],
                'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'category' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'system'],
                'type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'metric'],
                'icon' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'bi bi-grid'],
                'color' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'primary'],
                'permission_key' => ['type' => 'varchar', 'constraint' => 120, 'default' => 'workspace.access[view]'],
                'endpoint' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
                'refresh_time' => ['type' => 'int', 'constraint' => 11, 'default' => 300],
                'default_w' => ['type' => 'int', 'constraint' => 11, 'default' => 4],
                'default_h' => ['type' => 'int', 'constraint' => 11, 'default' => 2],
                'min_w' => ['type' => 'int', 'constraint' => 11, 'default' => 2],
                'min_h' => ['type' => 'int', 'constraint' => 11, 'default' => 1],
                'max_w' => ['type' => 'int', 'constraint' => 11, 'default' => 12],
                'max_h' => ['type' => 'int', 'constraint' => 11, 'default' => 8],
                'priority' => ['type' => 'int', 'constraint' => 11, 'default' => 50],
                'criticality' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'low'],
                'first_screen' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'lazy_load' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'refresh_policy' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'manual'],
                'feature_flag' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'active'],
                'manifest_version' => ['type' => 'int', 'constraint' => 11, 'default' => 1],
                'capabilities_json' => ['type' => 'text', 'null' => true],
                'tags_json' => ['type' => 'text', 'null' => true],
                'dependencies_json' => ['type' => 'text', 'null' => true],
                'settings_json' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_workspace_widget_catalog', 'code', 'idx_workspace_widget_code', 'unique');
            \DBUtil::create_index('core_workspace_widget_catalog', 'category', 'idx_workspace_widget_category');
            \DBUtil::create_index('core_workspace_widget_catalog', 'type', 'idx_workspace_widget_type');
        }

        if (!\DBUtil::table_exists('core_workspace_layouts')) {
            \DBUtil::create_table('core_workspace_layouts', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'scope_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'template'],
                'scope_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'is_default' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'layout_version' => ['type' => 'int', 'constraint' => 11, 'default' => 1],
                'schema_version' => ['type' => 'int', 'constraint' => 11, 'default' => 1],
                'profile_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'generic'],
                'preset_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'generic'],
                'filters_json' => ['type' => 'text', 'null' => true],
                'mobile_settings_json' => ['type' => 'text', 'null' => true],
                'layout_snapshot_json' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_workspace_layouts', ['scope_type', 'scope_id'], 'idx_workspace_layout_scope');
            \DBUtil::create_index('core_workspace_layouts', ['scope_type', 'scope_id', 'is_default'], 'idx_workspace_layout_default');
        }

        if (!\DBUtil::table_exists('core_workspace_widget_instances')) {
            \DBUtil::create_table('core_workspace_widget_instances', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'layout_id' => ['type' => 'int', 'constraint' => 11],
                'widget_code' => ['type' => 'varchar', 'constraint' => 80],
                'catalog_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'x' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'y' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'w' => ['type' => 'int', 'constraint' => 11, 'default' => 4],
                'h' => ['type' => 'int', 'constraint' => 11, 'default' => 2],
                'title_override' => ['type' => 'varchar', 'constraint' => 160, 'default' => ''],
                'collapsed' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'favorite' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'refresh_time' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'priority_override' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'mobile_hidden' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'mobile_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'last_error' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'last_loaded_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'settings_json' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_workspace_widget_instances', 'layout_id', 'idx_workspace_instance_layout');
            \DBUtil::create_index('core_workspace_widget_instances', 'widget_code', 'idx_workspace_instance_widget');
        }

        if (!\DBUtil::table_exists('core_workspace_quick_actions')) {
            \DBUtil::create_table('core_workspace_quick_actions', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 80],
                'title' => ['type' => 'varchar', 'constraint' => 160],
                'icon' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'bi bi-lightning'],
                'route' => ['type' => 'varchar', 'constraint' => 160],
                'permission_key' => ['type' => 'varchar', 'constraint' => 120, 'default' => 'workspace.access[view]'],
                'category' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'system'],
                'color' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'primary'],
                'execution_type' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'route'],
                'requires_confirmation' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'opens_modal' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'keywords' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'sort_order' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_workspace_quick_actions', 'code', 'idx_workspace_action_code', 'unique');
            \DBUtil::create_index('core_workspace_quick_actions', 'category', 'idx_workspace_action_category');
        }

        if (!\DBUtil::table_exists('core_workspace_user_preferences')) {
            \DBUtil::create_table('core_workspace_user_preferences', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'user_id' => ['type' => 'int', 'constraint' => 11],
                'default_layout_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'compact_mode' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
                'active_profile_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'generic'],
                'active_preset_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'generic'],
                'favorite_widgets_json' => ['type' => 'text', 'null' => true],
                'favorite_actions_json' => ['type' => 'text', 'null' => true],
                'hidden_widgets_json' => ['type' => 'text', 'null' => true],
                'filters_json' => ['type' => 'text', 'null' => true],
                'recent_commands_json' => ['type' => 'text', 'null' => true],
                'recent_searches_json' => ['type' => 'text', 'null' => true],
                'mobile_preferences_json' => ['type' => 'text', 'null' => true],
                'settings_json' => ['type' => 'text', 'null' => true],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_workspace_user_preferences', 'user_id', 'idx_workspace_preference_user', 'unique');
        }
    }

    public function down()
    {
        foreach ([
            'core_workspace_user_preferences',
            'core_workspace_quick_actions',
            'core_workspace_widget_instances',
            'core_workspace_layouts',
            'core_workspace_widget_catalog',
        ] as $table) {
            if (\DBUtil::table_exists($table)) {
                \DBUtil::drop_table($table);
            }
        }
    }
}

