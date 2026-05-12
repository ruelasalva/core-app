<?php

namespace Fuel\Migrations;

class Add_company_configuration_fields
{
    public function up()
    {
        \DBUtil::add_fields('core_companies', [
            'invoice_receive_days' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'invoice_receive_limit_time' => ['type' => 'varchar', 'constraint' => 8, 'default' => ''],
            'payment_days' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'payment_terms_days' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'payment_frequency' => ['type' => 'varchar', 'constraint' => 30, 'default' => ''],
            'payment_days_of_month' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'announcement_message' => ['type' => 'text', 'null' => true],
            'blocked_reception' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'holidays' => ['type' => 'text', 'null' => true],
            'policy_file' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
        ]);
    }

    public function down()
    {
        \DBUtil::drop_fields('core_companies', [
            'invoice_receive_days',
            'invoice_receive_limit_time',
            'payment_days',
            'payment_terms_days',
            'payment_frequency',
            'payment_days_of_month',
            'announcement_message',
            'blocked_reception',
            'holidays',
            'policy_file',
        ]);
    }
}
