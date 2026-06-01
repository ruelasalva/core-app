<?php

namespace Fuel\Migrations;

/**
 * MIGRACION 069
 *
 * Ajusta los indices del libro fiscal para permitir reconstrucciones
 * controladas conservando historico inactivo.
 */
class Adjust_fiscal_ledger_source_hash_for_rebuilds
{
    protected $table = 'core_fiscal_ledger_lines';

    public function up()
    {
        if (!\DBUtil::table_exists($this->table)) {
            \Log::warning('Migracion 069: tabla '.$this->table.' no existe; no se ajustaron indices.');
            return;
        }

        $this->drop_index_if_exists('uidx_core_fiscal_ledger_source_hash');
        $this->create_index_if_missing('idx_core_fiscal_ledger_source_hash', ['source_hash']);
        $this->create_index_if_missing('idx_core_fiscal_ledger_rfc_period_active_hash', [
            'taxpayer_rfc',
            'fiscal_period',
            'active',
            'source_hash',
        ]);

        \Log::info('Migracion 069: indices de core_fiscal_ledger_lines ajustados para rebuild fiscal.');
    }

    public function down()
    {
        if (!\DBUtil::table_exists($this->table)) {
            return;
        }

        $this->drop_index_if_exists('idx_core_fiscal_ledger_rfc_period_active_hash');
        $this->drop_index_if_exists('idx_core_fiscal_ledger_source_hash');

        if (!$this->has_duplicate_source_hashes()) {
            $this->create_index_if_missing('uidx_core_fiscal_ledger_source_hash', ['source_hash'], 'unique');
        } else {
            \Log::warning('Migracion 069 down: no se recreo indice unico source_hash porque existen hashes duplicados.');
        }
    }

    protected function create_index_if_missing($index, array $columns, $type = null)
    {
        if ($this->index_exists($index)) {
            return;
        }

        \DBUtil::create_index($this->table, $columns, $index, $type);
    }

    protected function drop_index_if_exists($index)
    {
        if (!$this->index_exists($index)) {
            return;
        }

        \DB::query('ALTER TABLE `'.$this->table.'` DROP INDEX `'.$index.'`')->execute();
    }

    protected function index_exists($index)
    {
        $row = \DB::query('SHOW INDEX FROM `'.$this->table.'` WHERE Key_name = '.$this->sql($index))
            ->execute()
            ->current();

        return (bool) $row;
    }

    protected function has_duplicate_source_hashes()
    {
        $row = \DB::query("
            SELECT source_hash, COUNT(*) AS total
            FROM `".$this->table."`
            GROUP BY source_hash
            HAVING COUNT(*) > 1
            LIMIT 1
        ")->execute()->current();

        return (bool) $row;
    }

    protected function sql($value)
    {
        return \DB::quote((string) $value);
    }
}
