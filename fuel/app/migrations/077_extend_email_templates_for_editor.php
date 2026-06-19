<?php

namespace Fuel\Migrations;

class Extend_email_templates_for_editor
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_email_templates')) {
            return;
        }

        if (!\DBUtil::field_exists('core_email_templates', ['name'])) {
            \DBUtil::add_fields('core_email_templates', [
                'name' => ['type' => 'varchar', 'constraint' => 160, 'default' => '', 'after' => 'code'],
            ]);
        }

        if (!\DBUtil::field_exists('core_email_templates', ['body_text'])) {
            \DBUtil::add_fields('core_email_templates', [
                'body_text' => ['type' => 'text', 'null' => true, 'after' => 'content'],
            ]);
        }
    }

    public function down()
    {
        if (!\DBUtil::table_exists('core_email_templates')) {
            return;
        }

        if (\DBUtil::field_exists('core_email_templates', ['body_text'])) {
            \DBUtil::drop_fields('core_email_templates', ['body_text']);
        }

        if (\DBUtil::field_exists('core_email_templates', ['name'])) {
            \DBUtil::drop_fields('core_email_templates', ['name']);
        }
    }
}
