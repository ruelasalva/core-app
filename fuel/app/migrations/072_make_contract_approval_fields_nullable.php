<?php

namespace Fuel\Migrations;

/**
 * MIGRACION 072
 *
 * Permite que contratos en borrador no tengan aprobacion ni firma.
 * No modifica created_by, updated_by, status ni active porque son campos
 * requeridos para trazabilidad y control operativo.
 */
class Make_contract_approval_fields_nullable
{
    protected $table = 'core_contracts';

    public function up()
    {
        if (!\DBUtil::table_exists($this->table)) {
            \Log::warning('Migracion 072: tabla '.$this->table.' no existe; no se ajustaron campos de aprobacion.');
            return;
        }

        \DBUtil::modify_fields($this->table, [
            'approved_by' => ['type' => 'int', 'constraint' => 11, 'null' => true, 'default' => null],
            'approved_at' => ['type' => 'int', 'constraint' => 11, 'null' => true, 'default' => null],
            'signed_at' => ['type' => 'int', 'constraint' => 11, 'null' => true, 'default' => null],
        ]);

        \Log::info('Migracion 072: approved_by, approved_at y signed_at ahora permiten NULL en core_contracts.');
    }

    public function down()
    {
        if (!\DBUtil::table_exists($this->table)) {
            return;
        }

        \DB::query("
            UPDATE `".$this->table."`
            SET
                `approved_by` = IFNULL(`approved_by`, 0),
                `approved_at` = IFNULL(`approved_at`, 0),
                `signed_at` = IFNULL(`signed_at`, 0)
            WHERE `approved_by` IS NULL
               OR `approved_at` IS NULL
               OR `signed_at` IS NULL
        ")->execute();

        \DBUtil::modify_fields($this->table, [
            'approved_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'approved_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'signed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ]);

        \Log::info('Migracion 072 down: approved_by, approved_at y signed_at regresaron a default 0.');
    }
}
