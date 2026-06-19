<?php

namespace Fuel\Migrations;

class Create_communications_center_foundation
{
    public function up()
    {
        $this->create_providers_table();
        $this->create_channels_table();
        $this->create_layouts_table();
        $this->create_queue_attempts_table();
        $this->extend_email_queue();
    }

    public function down()
    {
        if (\DBUtil::table_exists('core_email_queue')) {
            $fields = [];
            foreach ([
                'provider_code',
                'channel_code',
                'priority',
                'locked_at',
                'locked_by',
                'next_retry_at',
                'provider_message_id',
                'payload_json',
                'error_json',
                'simulation_mode',
            ] as $field) {
                if ($this->field_exists('core_email_queue', $field)) {
                    $fields[] = $field;
                }
            }

            if (!empty($fields)) {
                \DBUtil::drop_fields('core_email_queue', $fields);
            }
        }

        foreach ([
            'core_email_queue_attempts',
            'core_email_layouts',
            'core_communication_channels',
            'core_communication_providers',
        ] as $table) {
            if (\DBUtil::table_exists($table)) {
                \DBUtil::drop_table($table);
            }
        }
    }

    protected function create_providers_table()
    {
        if (\DBUtil::table_exists('core_communication_providers')) {
            return;
        }

        \DBUtil::create_table('core_communication_providers', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'disabled'],
            'transport' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'disabled'],
            'host' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'port' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'username' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'password_encrypted' => ['type' => 'text', 'null' => true],
            'api_key_encrypted' => ['type' => 'text', 'null' => true],
            'api_base_url' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'encryption' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
            'timeout_seconds' => ['type' => 'int', 'constraint' => 11, 'default' => 20],
            'verify_tls' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'from_email' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'from_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'reply_to_email' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'daily_limit' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'hourly_limit' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'simulation_mode' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'last_test_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'last_test_status' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_communication_providers', 'code', 'idx_communication_providers_code', 'unique');
        \DBUtil::create_index('core_communication_providers', 'type', 'idx_communication_providers_type');
        \DBUtil::create_index('core_communication_providers', 'active', 'idx_communication_providers_active');
    }

    protected function create_channels_table()
    {
        if (\DBUtil::table_exists('core_communication_channels')) {
            return;
        }

        \DBUtil::create_table('core_communication_channels', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 80],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'internal'],
            'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_communication_channels', 'code', 'idx_communication_channels_code', 'unique');
        \DBUtil::create_index('core_communication_channels', 'type', 'idx_communication_channels_type');
        \DBUtil::create_index('core_communication_channels', 'active', 'idx_communication_channels_active');
    }

    protected function create_layouts_table()
    {
        if (\DBUtil::table_exists('core_email_layouts')) {
            return;
        }

        \DBUtil::create_table('core_email_layouts', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'code' => ['type' => 'varchar', 'constraint' => 100],
            'name' => ['type' => 'varchar', 'constraint' => 160],
            'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'html_layout' => ['type' => 'text', 'null' => true],
            'text_layout' => ['type' => 'text', 'null' => true],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'version' => ['type' => 'int', 'constraint' => 11, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_email_layouts', 'code', 'idx_email_layouts_code', 'unique');
        \DBUtil::create_index('core_email_layouts', 'active', 'idx_email_layouts_active');
    }

    protected function create_queue_attempts_table()
    {
        if (\DBUtil::table_exists('core_email_queue_attempts')) {
            return;
        }

        \DBUtil::create_table('core_email_queue_attempts', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'queue_id' => ['type' => 'int', 'constraint' => 11],
            'attempt_number' => ['type' => 'int', 'constraint' => 11, 'default' => 1],
            'transport' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'disabled'],
            'provider_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'pending'],
            'response_code' => ['type' => 'varchar', 'constraint' => 60, 'default' => ''],
            'response_message' => ['type' => 'text', 'null' => true],
            'attempted_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');

        \DBUtil::create_index('core_email_queue_attempts', 'queue_id', 'idx_email_attempts_queue');
        \DBUtil::create_index('core_email_queue_attempts', 'status', 'idx_email_attempts_status');
        \DBUtil::create_index('core_email_queue_attempts', 'attempted_at', 'idx_email_attempts_attempted');
    }

    protected function extend_email_queue()
    {
        if (!\DBUtil::table_exists('core_email_queue')) {
            return;
        }

        $fields = [];
        $definitions = [
            'provider_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'channel_code' => ['type' => 'varchar', 'constraint' => 80, 'default' => 'email'],
            'priority' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'normal'],
            'locked_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'locked_by' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'next_retry_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'provider_message_id' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'payload_json' => ['type' => 'text', 'null' => true],
            'error_json' => ['type' => 'text', 'null' => true],
            'simulation_mode' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
        ];

        foreach ($definitions as $field => $definition) {
            if (!$this->field_exists('core_email_queue', $field)) {
                $fields[$field] = $definition;
            }
        }

        if (!empty($fields)) {
            \DBUtil::add_fields('core_email_queue', $fields);
        }

        $this->create_index_if_missing('core_email_queue', 'idx_core_email_queue_provider_code', 'provider_code');
        $this->create_index_if_missing('core_email_queue', 'idx_core_email_queue_channel_code', 'channel_code');
        $this->create_index_if_missing('core_email_queue', 'idx_core_email_queue_priority', 'priority');
        $this->create_index_if_missing('core_email_queue', 'idx_core_email_queue_next_retry', 'next_retry_at');
    }

    protected function field_exists($table, $field)
    {
        return \DBUtil::field_exists($table, [$field]);
    }

    protected function create_index_if_missing($table, $index, $field)
    {
        if (!$this->index_exists($table, $index) && $this->field_exists($table, $field)) {
            \DBUtil::create_index($table, $field, $index);
        }
    }

    protected function index_exists($table, $index)
    {
        $rows = \DB::query('SHOW INDEX FROM `'.$table.'` WHERE Key_name = '.$this->quote($index))->execute()->as_array();
        return !empty($rows);
    }

    protected function quote($value)
    {
        return \DB::quote((string) $value);
    }
}
