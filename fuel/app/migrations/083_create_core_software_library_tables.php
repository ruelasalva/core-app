<?php
namespace Fuel\Migrations;
class Create_core_software_library_tables
{
    public function up()
    {
        $this->categories();
        $this->items();
        $this->versions();
        $this->assets();
        $this->access_rules();
        $this->download_logs();
        $this->upload_sessions();
    }

    public function down()
    {
        foreach (array(
            'core_software_library_upload_sessions',
            'core_software_library_download_logs',
            'core_software_library_access_rules',
            'core_software_library_assets',
            'core_software_library_versions',
            'core_software_library_items',
            'core_software_library_categories',
        ) as $table) {
            if (\DBUtil::table_exists($table)) {
                \DBUtil::drop_table($table);
            }
        }
    }

    protected function categories()
    {
        if (\DBUtil::table_exists('core_software_library_categories')) {
            return;
        }

        \DBUtil::create_table('core_software_library_categories', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'code' => array('type' => 'varchar', 'constraint' => 80),
            'name' => array('type' => 'varchar', 'constraint' => 160),
            'description' => array('type' => 'text', 'null' => true),
            'sort_order' => array('type' => 'int', 'constraint' => 11, 'default' => 100),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'));
        \DBUtil::create_index('core_software_library_categories', 'code', 'idx_swlib_categories_code', 'unique');
        \DBUtil::create_index('core_software_library_categories', 'active', 'idx_swlib_categories_active');
    }

    protected function items()
    {
        if (\DBUtil::table_exists('core_software_library_items')) {
            return;
        }

        \DBUtil::create_table('core_software_library_items', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'code' => array('type' => 'varchar', 'constraint' => 80),
            'name' => array('type' => 'varchar', 'constraint' => 180),
            'description' => array('type' => 'text', 'null' => true),
            'category_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'vendor' => array('type' => 'varchar', 'constraint' => 120, 'default' => ''),
            'software_type' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'installer'),
            'platform' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'windows'),
            'architecture' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'x64'),
            'license_mode' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'none'),
            'support_notes' => array('type' => 'text', 'null' => true),
            'status' => array('type' => 'varchar', 'constraint' => 30, 'default' => 'draft'),
            'visibility' => array('type' => 'varchar', 'constraint' => 30, 'default' => 'internal'),
            'created_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'));
        \DBUtil::create_index('core_software_library_items', 'code', 'idx_swlib_items_code', 'unique');
        \DBUtil::create_index('core_software_library_items', array('category_id', 'active'), 'idx_swlib_items_category_active');
        \DBUtil::create_index('core_software_library_items', array('status', 'visibility', 'active'), 'idx_swlib_items_status_visibility');
    }

    protected function versions()
    {
        if (\DBUtil::table_exists('core_software_library_versions')) {
            return;
        }

        \DBUtil::create_table('core_software_library_versions', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'software_id' => array('type' => 'int', 'constraint' => 11),
            'version' => array('type' => 'varchar', 'constraint' => 80),
            'release_date' => array('type' => 'varchar', 'constraint' => 10, 'default' => ''),
            'changelog' => array('type' => 'text', 'null' => true),
            'is_latest' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 0),
            'status' => array('type' => 'varchar', 'constraint' => 30, 'default' => 'draft'),
            'created_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'));
        \DBUtil::create_index('core_software_library_versions', array('software_id', 'version'), 'idx_swlib_versions_item_version');
        \DBUtil::create_index('core_software_library_versions', array('software_id', 'is_latest', 'active'), 'idx_swlib_versions_latest');
    }

    protected function assets()
    {
        if (\DBUtil::table_exists('core_software_library_assets')) {
            return;
        }

        \DBUtil::create_table('core_software_library_assets', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'software_id' => array('type' => 'int', 'constraint' => 11),
            'version_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'asset_type' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'file'),
            'title' => array('type' => 'varchar', 'constraint' => 180),
            'filename' => array('type' => 'varchar', 'constraint' => 190, 'default' => ''),
            'mime_type' => array('type' => 'varchar', 'constraint' => 120, 'default' => ''),
            'size_bytes' => array('type' => 'bigint', 'constraint' => 20, 'default' => 0),
            'storage_ref' => array('type' => 'varchar', 'constraint' => 255, 'default' => ''),
            'external_url' => array('type' => 'text', 'null' => true),
            'checksum_sha256' => array('type' => 'char', 'constraint' => 64, 'default' => ''),
            'requires_login' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'expires_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'download_count' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'status' => array('type' => 'varchar', 'constraint' => 30, 'default' => 'active'),
            'created_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'));
        \DBUtil::create_index('core_software_library_assets', array('software_id', 'version_id', 'active'), 'idx_swlib_assets_item_version');
        \DBUtil::create_index('core_software_library_assets', array('asset_type', 'status', 'active'), 'idx_swlib_assets_type_status');
    }

    protected function access_rules()
    {
        if (\DBUtil::table_exists('core_software_library_access_rules')) {
            return;
        }

        \DBUtil::create_table('core_software_library_access_rules', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'software_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'asset_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'scope_type' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'admin'),
            'scope_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'portal_code' => array('type' => 'varchar', 'constraint' => 40, 'default' => ''),
            'party_type' => array('type' => 'varchar', 'constraint' => 40, 'default' => ''),
            'permission' => array('type' => 'varchar', 'constraint' => 40, 'default' => 'download'),
            'valid_from' => array('type' => 'varchar', 'constraint' => 10, 'default' => ''),
            'valid_until' => array('type' => 'varchar', 'constraint' => 10, 'default' => ''),
            'notes' => array('type' => 'text', 'null' => true),
            'created_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'active' => array('type' => 'tinyint', 'constraint' => 1, 'default' => 1),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'));
        \DBUtil::create_index('core_software_library_access_rules', array('asset_id', 'scope_type', 'scope_id'), 'idx_swlib_access_asset_scope');
        \DBUtil::create_index('core_software_library_access_rules', array('software_id', 'portal_code', 'party_type'), 'idx_swlib_access_portal');
    }

    protected function download_logs()
    {
        if (\DBUtil::table_exists('core_software_library_download_logs')) {
            return;
        }

        \DBUtil::create_table('core_software_library_download_logs', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'asset_id' => array('type' => 'int', 'constraint' => 11),
            'software_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'version_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'user_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'party_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'portal_code' => array('type' => 'varchar', 'constraint' => 40, 'default' => ''),
            'status' => array('type' => 'varchar', 'constraint' => 30, 'default' => 'success'),
            'ip_hash' => array('type' => 'char', 'constraint' => 64, 'default' => ''),
            'user_agent_hash' => array('type' => 'char', 'constraint' => 64, 'default' => ''),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'));
        \DBUtil::create_index('core_software_library_download_logs', array('asset_id', 'created_at'), 'idx_swlib_logs_asset_date');
        \DBUtil::create_index('core_software_library_download_logs', array('party_id', 'portal_code'), 'idx_swlib_logs_party_portal');
    }

    protected function upload_sessions()
    {
        if (\DBUtil::table_exists('core_software_library_upload_sessions')) {
            return;
        }

        \DBUtil::create_table('core_software_library_upload_sessions', array(
            'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
            'upload_code' => array('type' => 'varchar', 'constraint' => 80),
            'software_id' => array('type' => 'int', 'constraint' => 11),
            'version_id' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'title' => array('type' => 'varchar', 'constraint' => 180),
            'filename' => array('type' => 'varchar', 'constraint' => 190),
            'mime_type' => array('type' => 'varchar', 'constraint' => 120, 'default' => ''),
            'size_bytes' => array('type' => 'bigint', 'constraint' => 20, 'default' => 0),
            'chunk_size' => array('type' => 'int', 'constraint' => 11, 'default' => 5242880),
            'total_chunks' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'received_chunks' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'checksum_sha256' => array('type' => 'char', 'constraint' => 64, 'default' => ''),
            'status' => array('type' => 'varchar', 'constraint' => 30, 'default' => 'uploading'),
            'created_by' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
            'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
        ), array('id'));
        \DBUtil::create_index('core_software_library_upload_sessions', 'upload_code', 'idx_swlib_upload_code', 'unique');
        \DBUtil::create_index('core_software_library_upload_sessions', array('created_by', 'status'), 'idx_swlib_upload_user_status');
    }
}
