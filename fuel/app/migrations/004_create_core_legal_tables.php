<?php

namespace Fuel\Migrations;

class Create_core_legal_tables
{
    public function up()
    {
        \DBUtil::create_table('core_legal_documents', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'category' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'general'],
            'document_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'otros'],
            'shortcode' => ['type' => 'varchar', 'constraint' => 80],
            'title' => ['type' => 'varchar', 'constraint' => 180],
            'content' => ['type' => 'text', 'null' => true],
            'version' => ['type' => 'varchar', 'constraint' => 20, 'default' => '1.0'],
            'required' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'allow_download' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'valid_from' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'valid_until' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_legal_documents', ['shortcode', 'version'], 'idx_core_legal_documents_shortcode_version', 'unique');
        \DBUtil::create_index('core_legal_documents', 'category', 'idx_core_legal_documents_category');
        \DBUtil::create_index('core_legal_documents', 'active', 'idx_core_legal_documents_active');

        \DBUtil::create_table('core_user_consents', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'user_id' => ['type' => 'int', 'constraint' => 11],
            'document_id' => ['type' => 'int', 'constraint' => 11],
            'version' => ['type' => 'varchar', 'constraint' => 20, 'default' => '1.0'],
            'accepted' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'channel' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'web'],
            'extra_json' => ['type' => 'text', 'null' => true],
            'ip_address' => ['type' => 'varchar', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'accepted_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_user_consents', 'user_id', 'idx_core_user_consents_user_id');
        \DBUtil::create_index('core_user_consents', 'document_id', 'idx_core_user_consents_document_id');
        \DBUtil::create_index('core_user_consents', ['user_id', 'document_id', 'version'], 'idx_core_user_consents_user_doc_version');
    }

    public function down()
    {
        \DBUtil::drop_table('core_user_consents');
        \DBUtil::drop_table('core_legal_documents');
    }
}
