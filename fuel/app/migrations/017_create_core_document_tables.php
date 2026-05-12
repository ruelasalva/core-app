<?php

namespace Fuel\Migrations;

class Create_core_document_tables
{
    public function up()
    {
        \DBUtil::create_table('core_documents', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'document_type' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'general'],
            'title' => ['type' => 'varchar', 'constraint' => 180],
            'description' => ['type' => 'text', 'null' => true],
            'file_path' => ['type' => 'varchar', 'constraint' => 255],
            'original_name' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
            'mime_type' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'file_extension' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
            'file_size' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'checksum' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'visibility' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'internal'],
            'is_evidence' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'uploaded_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_documents', ['document_type', 'active'], 'idx_core_documents_type_active');
        \DBUtil::create_index('core_documents', 'uploaded_by', 'idx_core_documents_uploaded_by');

        \DBUtil::create_table('core_document_links', [
            'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
            'document_id' => ['type' => 'int', 'constraint' => 11],
            'entity_type' => ['type' => 'varchar', 'constraint' => 80],
            'entity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'relation_type' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'attachment'],
            'notes' => ['type' => 'text', 'null' => true],
            'created_by' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ], ['id'], true, 'InnoDB', 'utf8');
        \DBUtil::create_index('core_document_links', 'document_id', 'idx_core_document_links_document_id');
        \DBUtil::create_index('core_document_links', ['entity_type', 'entity_id'], 'idx_core_document_links_entity');
    }

    public function down()
    {
        \DBUtil::drop_table('core_document_links');
        \DBUtil::drop_table('core_documents');
    }
}
