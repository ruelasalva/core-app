<?php

namespace Fuel\Migrations;

class Extend_commission_entries_for_config_engine
{
    protected $table = 'core_commission_entries';

    public function up()
    {
        if (!\DBUtil::table_exists($this->table)) {
            throw new \RuntimeException('Falta la tabla core_commission_entries. Ejecuta primero la migracion 053.');
        }

        $fields = array(
            'config_plan_id' => array('type' => 'int', 'constraint' => 11, 'null' => true),
            'config_version_id' => array('type' => 'int', 'constraint' => 11, 'null' => true),
            'config_rule_id' => array('type' => 'int', 'constraint' => 11, 'null' => true),
            'config_rule_stage_id' => array('type' => 'int', 'constraint' => 11, 'null' => true),
            'config_beneficiary_id' => array('type' => 'int', 'constraint' => 11, 'null' => true),
            'beneficiary_type' => array('type' => 'varchar', 'constraint' => 50, 'null' => true),
            'beneficiary_id' => array('type' => 'int', 'constraint' => 11, 'null' => true),
            'release_event' => array('type' => 'varchar', 'constraint' => 100, 'null' => true),
            'calculation_base' => array('type' => 'varchar', 'constraint' => 50, 'null' => true),
            'calculation_rate' => array('type' => 'decimal', 'constraint' => '18,6', 'null' => true),
            'calculated_amount' => array('type' => 'decimal', 'constraint' => '18,6', 'null' => true),
            'released_amount' => array('type' => 'decimal', 'constraint' => '18,6', 'default' => 0),
            'released_percent' => array('type' => 'decimal', 'constraint' => '9,6', 'default' => 0),
            'source_hash' => array('type' => 'char', 'constraint' => 64, 'null' => true),
            'calculation_snapshot_json' => array('type' => 'text', 'null' => true),
            'generated_by_engine' => array('type' => 'varchar', 'constraint' => 50, 'null' => true),
            'generated_at' => array('type' => 'int', 'constraint' => 11, 'null' => true),
            'approved_by' => array('type' => 'int', 'constraint' => 11, 'null' => true),
            'approved_at' => array('type' => 'int', 'constraint' => 11, 'null' => true),
            'paid_at' => array('type' => 'int', 'constraint' => 11, 'null' => true),
            'reversed_entry_id' => array('type' => 'int', 'constraint' => 11, 'null' => true),
        );

        $missing = array();
        foreach ($fields as $name => $definition) {
            if (!\DBUtil::field_exists($this->table, array($name))) {
                $missing[$name] = $definition;
            }
        }
        if (!empty($missing)) {
            \DBUtil::add_fields($this->table, $missing);
        }

        // trigger_event pertenece al esquema legacy; se reutiliza y nunca se elimina en rollback.
        if (!\DBUtil::field_exists($this->table, array('trigger_event'))) {
            \DBUtil::add_fields($this->table, array(
                'trigger_event' => array('type' => 'varchar', 'constraint' => 100, 'null' => true),
            ));
        }

        $this->create_index_if_missing(
            'idx_comm_entries_config_version_rule',
            array('config_version_id', 'config_rule_id')
        );
        $this->create_index_if_missing(
            'idx_comm_entries_config_beneficiary',
            array('config_beneficiary_id', 'beneficiary_type', 'beneficiary_id')
        );
        $this->create_index_if_missing(
            'idx_comm_entries_engine_status',
            array('generated_by_engine', 'status', 'active')
        );

        $this->create_source_hash_index();
    }

    public function down()
    {
        if (!\DBUtil::table_exists($this->table)) {
            return;
        }

        foreach (array(
            'uidx_comm_entries_source_hash',
            'idx_comm_entries_source_hash',
            'idx_comm_entries_engine_status',
            'idx_comm_entries_config_beneficiary',
            'idx_comm_entries_config_version_rule',
        ) as $index) {
            if ($this->index_exists($index)) {
                \DBUtil::drop_index($this->table, $index);
            }
        }

        $fields = array(
            'config_plan_id', 'config_version_id', 'config_rule_id', 'config_rule_stage_id',
            'config_beneficiary_id', 'beneficiary_type', 'beneficiary_id', 'release_event',
            'calculation_base', 'calculation_rate', 'calculated_amount', 'released_amount',
            'released_percent', 'source_hash', 'calculation_snapshot_json', 'generated_by_engine',
            'generated_at', 'approved_by', 'approved_at', 'paid_at', 'reversed_entry_id',
        );

        $existing = array();
        foreach ($fields as $field) {
            if (\DBUtil::field_exists($this->table, array($field))) {
                $existing[] = $field;
            }
        }
        if (!empty($existing)) {
            \DBUtil::drop_fields($this->table, $existing);
        }
    }

    protected function create_source_hash_index()
    {
        if ($this->index_exists('uidx_comm_entries_source_hash')) {
            return;
        }

        if (!$this->has_duplicate_source_hashes()) {
            try {
                \DBUtil::create_index(
                    $this->table,
                    'source_hash',
                    'uidx_comm_entries_source_hash',
                    'unique'
                );
                return;
            } catch (\Exception $e) {
                \Log::warning('No se pudo crear indice unico de source_hash: '.$e->getMessage());
            }
        }

        $this->create_index_if_missing('idx_comm_entries_source_hash', array('source_hash'));
        \Log::warning('core_commission_entries usa indice no unico de source_hash por duplicados existentes o incompatibilidad del motor MySQL.');
    }

    protected function create_index_if_missing($name, array $fields)
    {
        if (!$this->index_exists($name)) {
            \DBUtil::create_index($this->table, $fields, $name);
        }
    }

    protected function index_exists($name)
    {
        $sql = 'SHOW INDEX FROM `'.$this->table.'` WHERE Key_name = '.\DB::quote((string) $name);
        return (bool) \DB::query($sql)->execute()->current();
    }

    protected function has_duplicate_source_hashes()
    {
        $sql = "SELECT source_hash FROM `{$this->table}` "
            ."WHERE source_hash IS NOT NULL AND source_hash <> '' "
            .'GROUP BY source_hash HAVING COUNT(*) > 1 LIMIT 1';
        return (bool) \DB::query($sql)->execute()->current();
    }
}
