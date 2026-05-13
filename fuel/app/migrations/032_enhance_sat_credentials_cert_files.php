<?php

namespace Fuel\Migrations;

class Enhance_sat_credentials_cert_files
{
    public function up()
    {
        \DBUtil::add_fields('core_sat_credentials', [
            'cer_original_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => '', 'after' => 'cer_path'],
            'key_original_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => '', 'after' => 'key_path'],
            'certificate_serial' => ['type' => 'varchar', 'constraint' => 80, 'default' => '', 'after' => 'password_encrypted'],
            'certificate_subject' => ['type' => 'text', 'null' => true, 'after' => 'certificate_serial'],
            'certificate_issuer' => ['type' => 'text', 'null' => true, 'after' => 'certificate_subject'],
        ]);
        \DBUtil::create_index('core_sat_credentials', 'certificate_serial', 'idx_core_sat_credentials_serial');
    }

    public function down()
    {
        \DBUtil::drop_fields('core_sat_credentials', [
            'cer_original_name',
            'key_original_name',
            'certificate_serial',
            'certificate_subject',
            'certificate_issuer',
        ]);
    }
}
