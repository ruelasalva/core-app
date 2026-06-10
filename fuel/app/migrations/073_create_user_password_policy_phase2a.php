<?php

namespace Fuel\Migrations;

class Create_user_password_policy_phase2a
{
    public function up()
    {
        if (!\DBUtil::table_exists('users')) {
            return;
        }

        $fields = [];

        if (!\DBUtil::field_exists('users', ['password_must_change'])) {
            $fields['password_must_change'] = [
                'type' => 'tinyint',
                'constraint' => 1,
                'default' => 0,
                'after' => 'salt',
            ];
        }

        if (!\DBUtil::field_exists('users', ['password_changed_at'])) {
            $fields['password_changed_at'] = [
                'type' => 'int',
                'constraint' => 11,
                'null' => true,
                'after' => 'password_must_change',
            ];
        }

        if (!\DBUtil::field_exists('users', ['password_reset_at'])) {
            $fields['password_reset_at'] = [
                'type' => 'int',
                'constraint' => 11,
                'null' => true,
                'after' => 'password_changed_at',
            ];
        }

        if (!\DBUtil::field_exists('users', ['password_reset_by'])) {
            $fields['password_reset_by'] = [
                'type' => 'int',
                'constraint' => 11,
                'null' => true,
                'after' => 'password_reset_at',
            ];
        }

        if (!empty($fields)) {
            \DBUtil::add_fields('users', $fields);
        }

        try {
            \DBUtil::create_index('users', 'password_must_change', 'idx_users_password_must_change');
        } catch (\Exception $e) {
            // El indice puede existir si la migracion se reintenta.
        }
    }

    public function down()
    {
        if (!\DBUtil::table_exists('users')) {
            return;
        }

        try {
            \DBUtil::drop_index('users', 'idx_users_password_must_change');
        } catch (\Exception $e) {
            // El indice puede no existir.
        }

        $fields = [];
        foreach (['password_reset_by', 'password_reset_at', 'password_changed_at', 'password_must_change'] as $field) {
            if (\DBUtil::field_exists('users', [$field])) {
                $fields[] = $field;
            }
        }

        if (!empty($fields)) {
            \DBUtil::drop_fields('users', $fields);
        }
    }
}
