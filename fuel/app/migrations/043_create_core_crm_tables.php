<?php

namespace Fuel\Migrations;

class Create_core_crm_tables
{
    public function up()
    {
        if (!\DBUtil::table_exists('core_crm_opportunities')) {
            \DBUtil::create_table('core_crm_opportunities', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'owner_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'department_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'source' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'manual'],
                'stage' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'new'],
                'title' => ['type' => 'varchar', 'constraint' => 180],
                'description' => ['type' => 'text', 'null' => true],
                'estimated_amount' => ['type' => 'decimal', 'constraint' => '14,2', 'default' => 0],
                'probability' => ['type' => 'tinyint', 'constraint' => 3, 'default' => 0],
                'expected_close_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'next_action_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'lost_reason' => ['type' => 'varchar', 'constraint' => 180, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_crm_opportunities', 'folio', 'idx_core_crm_opportunities_folio', 'unique');
            \DBUtil::create_index('core_crm_opportunities', ['party_id', 'stage'], 'idx_core_crm_opportunities_party_stage');
            \DBUtil::create_index('core_crm_opportunities', ['owner_user_id', 'next_action_at'], 'idx_core_crm_opportunities_owner_next');
        }

        if (!\DBUtil::table_exists('core_crm_activities')) {
            \DBUtil::create_table('core_crm_activities', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'opportunity_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'ticket_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'activity_type' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'note'],
                'subject' => ['type' => 'varchar', 'constraint' => 180],
                'description' => ['type' => 'text', 'null' => true],
                'status' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'open'],
                'priority' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'normal'],
                'assigned_user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'due_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'completed_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_crm_activities', ['party_id', 'status'], 'idx_core_crm_activities_party_status');
            \DBUtil::create_index('core_crm_activities', ['assigned_user_id', 'due_at'], 'idx_core_crm_activities_assigned_due');
        }

        if (!\DBUtil::table_exists('core_crm_surveys')) {
            \DBUtil::create_table('core_crm_surveys', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'code' => ['type' => 'varchar', 'constraint' => 60],
                'name' => ['type' => 'varchar', 'constraint' => 160],
                'audience' => ['type' => 'varchar', 'constraint' => 40, 'default' => 'customers'],
                'description' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'questions_json' => ['type' => 'text', 'null' => true],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_crm_surveys', 'code', 'idx_core_crm_surveys_code', 'unique');
        }

        if (!\DBUtil::table_exists('core_crm_survey_responses')) {
            \DBUtil::create_table('core_crm_survey_responses', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'survey_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'portal_code' => ['type' => 'varchar', 'constraint' => 40, 'default' => ''],
                'score' => ['type' => 'decimal', 'constraint' => '6,2', 'default' => 0],
                'answers_json' => ['type' => 'text', 'null' => true],
                'comments' => ['type' => 'text', 'null' => true],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_crm_survey_responses', ['survey_id', 'party_id'], 'idx_core_crm_survey_responses_survey_party');
        }

        if (!\DBUtil::table_exists('core_crm_cut_calculations')) {
            \DBUtil::create_table('core_crm_cut_calculations', [
                'id' => ['type' => 'int', 'constraint' => 11, 'auto_increment' => true],
                'folio' => ['type' => 'varchar', 'constraint' => 40],
                'party_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'user_id' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'material' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'sheet_width' => ['type' => 'decimal', 'constraint' => '10,2', 'default' => 0],
                'sheet_height' => ['type' => 'decimal', 'constraint' => '10,2', 'default' => 0],
                'piece_width' => ['type' => 'decimal', 'constraint' => '10,2', 'default' => 0],
                'piece_height' => ['type' => 'decimal', 'constraint' => '10,2', 'default' => 0],
                'kerf' => ['type' => 'decimal', 'constraint' => '10,2', 'default' => 0],
                'pieces_x' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'pieces_y' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'total_pieces' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'waste_percent' => ['type' => 'decimal', 'constraint' => '8,2', 'default' => 0],
                'notes' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'active' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            ], ['id'], true, 'InnoDB', 'utf8');
            \DBUtil::create_index('core_crm_cut_calculations', 'folio', 'idx_core_crm_cut_calculations_folio', 'unique');
            \DBUtil::create_index('core_crm_cut_calculations', ['party_id', 'created_at'], 'idx_core_crm_cut_calculations_party');
        }

        $this->seed_surveys();
        $this->seed_permissions();
        $this->seed_help();
    }

    public function down()
    {
        \DBUtil::drop_table('core_crm_cut_calculations');
        \DBUtil::drop_table('core_crm_survey_responses');
        \DBUtil::drop_table('core_crm_surveys');
        \DBUtil::drop_table('core_crm_activities');
        \DBUtil::drop_table('core_crm_opportunities');
    }

    protected function seed_surveys()
    {
        if (\DB::select('id')->from('core_crm_surveys')->where('code', '=', 'satisfaccion_cliente')->execute()->current()) {
            return;
        }

        \DB::insert('core_crm_surveys')->set([
            'code' => 'satisfaccion_cliente',
            'name' => 'Satisfaccion de cliente',
            'audience' => 'customers',
            'description' => 'Encuesta base para medir servicio, tiempos de respuesta y experiencia de compra.',
            'questions_json' => json_encode([
                ['key' => 'servicio', 'label' => 'Servicio recibido', 'type' => 'scale'],
                ['key' => 'tiempo', 'label' => 'Tiempo de respuesta', 'type' => 'scale'],
                ['key' => 'comentarios', 'label' => 'Comentarios', 'type' => 'text'],
            ]),
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }

    protected function seed_permissions()
    {
        if (!\DBUtil::table_exists('users_permissions')) {
            return;
        }

        if (!\DB::select('id')->from('users_permissions')->where('area', '=', 'crm')->where('permission', '=', 'access')->execute()->current()) {
            \DB::insert('users_permissions')->set([
                'area' => 'crm',
                'permission' => 'access',
                'description' => 'Acceso al modulo CRM comercial',
                'user_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();
        }
    }

    protected function seed_help()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            return;
        }
        if (\DB::select('id')->from('core_knowledge_articles')->where('code', '=', 'crm-comercial')->execute()->current()) {
            return;
        }

        \DB::insert('core_knowledge_articles')->set([
            'code' => 'crm-comercial',
            'title' => 'CRM comercial',
            'category' => 'Relaciones',
            'summary' => 'Gestion de clientes, oportunidades, actividades, encuestas y calculadora de corte.',
            'content' => '<h3>Objetivo</h3><p>CRM concentra la relacion comercial con clientes y prospectos. Helpdesk queda para tickets e incidencias de clientes, proveedores, socios y revendedores.</p><h3>Flujo recomendado</h3><ol><li>Registra oportunidades por cliente o prospecto.</li><li>Da seguimiento con actividades: llamada, visita, correo, tarea o nota.</li><li>Consulta tickets de clientes como contexto de servicio.</li><li>Usa encuestas para medir satisfaccion y detectar riesgos.</li><li>Usa la calculadora de corte para apoyar cotizaciones tecnicas.</li></ol><h3>Regla importante</h3><p>Un ticket no reemplaza una oportunidad comercial: si el cliente pide seguimiento de venta, crea oportunidad o actividad relacionada.</p>',
            'sort_order' => 50,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }
}
