<?php

/**
 * CONTROLADOR ADMIN_CRM
 *
 * Central comercial para clientes: oportunidades, actividades, encuestas,
 * calculadora de corte y contexto de tickets de clientes.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Crm extends Controller_Adminbase
{
    public function before()
    {
        parent::before();
        $this->require_access('crm.access[view]');
    }

    public function action_index()
    {
        $this->template->title = 'CRM';
        $this->template->content = View::forge('admin/crm/index');
    }

    public function action_data()
    {
        try {
            $this->assert_schema_ready();
            return $this->json_response([
                'opportunities' => $this->opportunities(),
                'activities' => $this->activities(),
                'customer_tickets' => $this->customer_tickets(),
                'surveys' => $this->surveys(),
                'survey_responses' => $this->survey_responses(),
                'cut_calculations' => $this->cut_calculations(),
                'options' => $this->options(),
                'stats' => $this->stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('CRM: error cargando datos: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar CRM. Revisa migraciones y permisos.'], 500);
        }
    }

    public function action_save_opportunity()
    {
        $this->require_access('crm.access[edit]');
        $val = (array) \Input::json();

        try {
            $title = trim((string) \Arr::get($val, 'title', ''));
            if ($title === '') {
                return $this->json_response(['error' => 'El titulo de la oportunidad es obligatorio.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'owner_user_id' => (int) \Arr::get($val, 'owner_user_id', $this->user_id),
                'department_id' => (int) \Arr::get($val, 'department_id', 0),
                'source' => $this->codeify(\Arr::get($val, 'source', 'manual')),
                'stage' => $this->opportunity_stage(\Arr::get($val, 'stage', 'new')),
                'title' => $title,
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'estimated_amount' => round((float) \Arr::get($val, 'estimated_amount', 0), 2),
                'probability' => max(0, min(100, (int) \Arr::get($val, 'probability', 0))),
                'expected_close_at' => $this->date_to_time(\Arr::get($val, 'expected_close_at_input', \Arr::get($val, 'expected_close_at', ''))),
                'next_action_at' => $this->datetime_to_time(\Arr::get($val, 'next_action_at_input', \Arr::get($val, 'next_action_at', ''))),
                'lost_reason' => trim((string) \Arr::get($val, 'lost_reason', '')),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($id > 0) {
                $opportunity = Model_Core_Crm_Opportunity::find($id);
                if (!$opportunity) {
                    return $this->json_response(['error' => 'Oportunidad no encontrada.'], 404);
                }
                $old = $opportunity->to_array();
                $opportunity->set($data);
            } else {
                $old = [];
                $data['folio'] = $this->next_folio('OPP', 'core_crm_opportunities');
                $opportunity = Model_Core_Crm_Opportunity::forge($data);
            }
            $opportunity->save();

            Helper_Core_Audit::log([
                'module' => 'crm',
                'action' => $id > 0 ? 'update_opportunity' : 'create_opportunity',
                'business_event' => 'crm.opportunity_saved',
                'entity_type' => 'crm_opportunity',
                'entity_id' => (int) $opportunity->id,
                'table_name' => 'core_crm_opportunities',
                'summary' => 'Oportunidad '.$opportunity->folio.' '.$opportunity->title,
                'old_values' => $old,
                'new_values' => $opportunity->to_array(),
            ]);

            return $this->data_ok();
        } catch (\Exception $e) {
            \Log::error('CRM: error guardando oportunidad: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la oportunidad.'], 400);
        }
    }

    public function action_save_activity()
    {
        $this->require_access('crm.access[edit]');
        $val = (array) \Input::json();

        try {
            $subject = trim((string) \Arr::get($val, 'subject', ''));
            if ($subject === '') {
                return $this->json_response(['error' => 'El asunto de la actividad es obligatorio.'], 422);
            }

            $id = (int) \Arr::get($val, 'id', 0);
            $data = [
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'opportunity_id' => (int) \Arr::get($val, 'opportunity_id', 0),
                'ticket_id' => (int) \Arr::get($val, 'ticket_id', 0),
                'activity_type' => $this->activity_type(\Arr::get($val, 'activity_type', 'note')),
                'subject' => $subject,
                'description' => trim((string) \Arr::get($val, 'description', '')),
                'status' => $this->activity_status(\Arr::get($val, 'status', 'open')),
                'priority' => $this->priority(\Arr::get($val, 'priority', 'normal')),
                'assigned_user_id' => (int) \Arr::get($val, 'assigned_user_id', $this->user_id),
                'due_at' => $this->datetime_to_time(\Arr::get($val, 'due_at_input', \Arr::get($val, 'due_at', ''))),
                'completed_at' => $this->activity_status(\Arr::get($val, 'status', 'open')) === 'done' ? time() : 0,
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ];

            if ($id > 0) {
                $activity = Model_Core_Crm_Activity::find($id);
                if (!$activity) {
                    return $this->json_response(['error' => 'Actividad no encontrada.'], 404);
                }
                $activity->set($data);
            } else {
                $activity = Model_Core_Crm_Activity::forge($data);
            }
            $activity->save();

            Helper_Core_Audit::log([
                'module' => 'crm',
                'action' => $id > 0 ? 'update_activity' : 'create_activity',
                'business_event' => 'crm.activity_saved',
                'entity_type' => 'crm_activity',
                'entity_id' => (int) $activity->id,
                'table_name' => 'core_crm_activities',
                'summary' => 'Actividad CRM '.$activity->subject,
                'new_values' => $activity->to_array(),
            ]);

            return $this->data_ok();
        } catch (\Exception $e) {
            \Log::error('CRM: error guardando actividad: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la actividad.'], 400);
        }
    }

    public function action_save_survey_response()
    {
        $this->require_access('crm.access[edit]');
        $val = (array) \Input::json();

        try {
            $survey_id = (int) \Arr::get($val, 'survey_id', 0);
            if ($survey_id < 1) {
                return $this->json_response(['error' => 'Selecciona una encuesta.'], 422);
            }

            Model_Core_Crm_Survey_Response::forge([
                'survey_id' => $survey_id,
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'portal_code' => $this->codeify(\Arr::get($val, 'portal_code', 'admin')),
                'score' => round((float) \Arr::get($val, 'score', 0), 2),
                'answers_json' => json_encode((array) \Arr::get($val, 'answers', [])),
                'comments' => trim((string) \Arr::get($val, 'comments', '')),
            ])->save();

            return $this->data_ok();
        } catch (\Exception $e) {
            \Log::error('CRM: error guardando respuesta de encuesta: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar la respuesta.'], 400);
        }
    }

    public function action_save_cut_calculation()
    {
        $this->require_access('crm.access[edit]');
        $val = (array) \Input::json();

        try {
            $sheet_width = (float) \Arr::get($val, 'sheet_width', 0);
            $sheet_height = (float) \Arr::get($val, 'sheet_height', 0);
            $piece_width = (float) \Arr::get($val, 'piece_width', 0);
            $piece_height = (float) \Arr::get($val, 'piece_height', 0);
            $kerf = max(0, (float) \Arr::get($val, 'kerf', 0));

            if ($sheet_width <= 0 || $sheet_height <= 0 || $piece_width <= 0 || $piece_height <= 0) {
                return $this->json_response(['error' => 'Captura medidas mayores a cero.'], 422);
            }

            $pieces_x = (int) floor(($sheet_width + $kerf) / ($piece_width + $kerf));
            $pieces_y = (int) floor(($sheet_height + $kerf) / ($piece_height + $kerf));
            $total = max(0, $pieces_x * $pieces_y);
            $sheet_area = $sheet_width * $sheet_height;
            $used_area = $total * $piece_width * $piece_height;
            $waste = $sheet_area > 0 ? max(0, (($sheet_area - $used_area) / $sheet_area) * 100) : 0;

            Model_Core_Crm_Cut_Calculation::forge([
                'folio' => $this->next_folio('CUT', 'core_crm_cut_calculations'),
                'party_id' => (int) \Arr::get($val, 'party_id', 0),
                'user_id' => $this->user_id,
                'material' => trim((string) \Arr::get($val, 'material', '')),
                'sheet_width' => round($sheet_width, 2),
                'sheet_height' => round($sheet_height, 2),
                'piece_width' => round($piece_width, 2),
                'piece_height' => round($piece_height, 2),
                'kerf' => round($kerf, 2),
                'pieces_x' => $pieces_x,
                'pieces_y' => $pieces_y,
                'total_pieces' => $total,
                'waste_percent' => round($waste, 2),
                'notes' => trim((string) \Arr::get($val, 'notes', '')),
                'active' => 1,
            ])->save();

            return $this->data_ok();
        } catch (\Exception $e) {
            \Log::error('CRM: error guardando calculo de corte: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el calculo.'], 400);
        }
    }

    protected function data_ok()
    {
        return $this->json_response([
            'status' => 'ok',
            'opportunities' => $this->opportunities(),
            'activities' => $this->activities(),
            'survey_responses' => $this->survey_responses(),
            'cut_calculations' => $this->cut_calculations(),
            'stats' => $this->stats(),
        ]);
    }

    protected function opportunities()
    {
        $query = \DB::select(['o.id', 'id'], ['o.folio', 'folio'], ['o.party_id', 'party_id'], ['p.name', 'party_name'], ['o.owner_user_id', 'owner_user_id'], ['u.username', 'owner_name'], ['o.stage', 'stage'], ['o.source', 'source'], ['o.title', 'title'], ['o.description', 'description'], ['o.estimated_amount', 'estimated_amount'], ['o.probability', 'probability'], ['o.expected_close_at', 'expected_close_at'], ['o.next_action_at', 'next_action_at'], ['o.lost_reason', 'lost_reason'], ['o.active', 'active'])
            ->from(['core_crm_opportunities', 'o'])
            ->join(['core_parties', 'p'], 'left')->on('o.party_id', '=', 'p.id')
            ->join(['users', 'u'], 'left')->on('o.owner_user_id', '=', 'u.id')
            ->where('o.active', '=', 1)
            ->order_by('o.id', 'desc')
            ->limit(200);

        foreach ($query->execute() as $row) {
            $rows[] = $this->format_dates($row);
        }

        return isset($rows) ? $rows : [];
    }

    protected function activities()
    {
        $rows = [];
        foreach (\DB::select(['a.id', 'id'], ['a.party_id', 'party_id'], ['p.name', 'party_name'], ['a.opportunity_id', 'opportunity_id'], ['o.folio', 'opportunity_folio'], ['a.ticket_id', 'ticket_id'], ['t.folio', 'ticket_folio'], ['a.activity_type', 'activity_type'], ['a.subject', 'subject'], ['a.description', 'description'], ['a.status', 'status'], ['a.priority', 'priority'], ['a.assigned_user_id', 'assigned_user_id'], ['u.username', 'assigned_name'], ['a.due_at', 'due_at'], ['a.completed_at', 'completed_at'])
            ->from(['core_crm_activities', 'a'])
            ->join(['core_parties', 'p'], 'left')->on('a.party_id', '=', 'p.id')
            ->join(['core_crm_opportunities', 'o'], 'left')->on('a.opportunity_id', '=', 'o.id')
            ->join(['core_helpdesk_tickets', 't'], 'left')->on('a.ticket_id', '=', 't.id')
            ->join(['users', 'u'], 'left')->on('a.assigned_user_id', '=', 'u.id')
            ->where('a.active', '=', 1)
            ->order_by('a.id', 'desc')
            ->limit(200)
            ->execute() as $row) {
            $rows[] = $this->format_dates($row);
        }
        return $rows;
    }

    protected function customer_tickets()
    {
        if (!\DBUtil::table_exists('core_helpdesk_tickets')) {
            return [];
        }

        return \DB::select(['t.id', 'id'], ['t.folio', 'folio'], ['t.subject', 'subject'], ['t.party_id', 'party_id'], ['p.name', 'party_name'], ['t.priority', 'priority'], ['s.name', 'status_name'], ['s.color', 'status_color'], ['t.created_at', 'created_at'], ['t.last_message_at', 'last_message_at'])
            ->from(['core_helpdesk_tickets', 't'])
            ->join(['core_parties', 'p'], 'left')->on('t.party_id', '=', 'p.id')
            ->join(['core_helpdesk_statuses', 's'], 'left')->on('t.status_id', '=', 's.id')
            ->where('t.portal_code', '=', 'clientes')
            ->or_where('t.source', '=', 'clientes')
            ->order_by('t.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function surveys()
    {
        return \DB::select('*')->from('core_crm_surveys')->where('active', '=', 1)->order_by('name', 'asc')->execute()->as_array();
    }

    protected function survey_responses()
    {
        return \DB::select(['r.id', 'id'], ['s.name', 'survey_name'], ['r.party_id', 'party_id'], ['p.name', 'party_name'], ['r.portal_code', 'portal_code'], ['r.score', 'score'], ['r.comments', 'comments'], ['r.created_at', 'created_at'])
            ->from(['core_crm_survey_responses', 'r'])
            ->join(['core_crm_surveys', 's'], 'left')->on('r.survey_id', '=', 's.id')
            ->join(['core_parties', 'p'], 'left')->on('r.party_id', '=', 'p.id')
            ->order_by('r.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function cut_calculations()
    {
        return \DB::select(['c.id', 'id'], ['c.folio', 'folio'], ['c.party_id', 'party_id'], ['p.name', 'party_name'], ['c.material', 'material'], ['c.sheet_width', 'sheet_width'], ['c.sheet_height', 'sheet_height'], ['c.piece_width', 'piece_width'], ['c.piece_height', 'piece_height'], ['c.kerf', 'kerf'], ['c.pieces_x', 'pieces_x'], ['c.pieces_y', 'pieces_y'], ['c.total_pieces', 'total_pieces'], ['c.waste_percent', 'waste_percent'], ['c.notes', 'notes'], ['c.created_at', 'created_at'])
            ->from(['core_crm_cut_calculations', 'c'])
            ->join(['core_parties', 'p'], 'left')->on('c.party_id', '=', 'p.id')
            ->where('c.active', '=', 1)
            ->order_by('c.id', 'desc')
            ->limit(100)
            ->execute()
            ->as_array();
    }

    protected function options()
    {
        return [
            'parties' => $this->party_options(),
            'users' => $this->user_options(),
            'surveys' => $this->survey_options(),
        ];
    }

    protected function stats()
    {
        return [
            'opportunities' => (int) \DB::select()->from('core_crm_opportunities')->where('active', '=', 1)->execute()->count(),
            'open_activities' => (int) \DB::select()->from('core_crm_activities')->where('active', '=', 1)->where('status', '!=', 'done')->execute()->count(),
            'customer_tickets' => \DBUtil::table_exists('core_helpdesk_tickets') ? (int) \DB::select()->from('core_helpdesk_tickets')->where('portal_code', '=', 'clientes')->execute()->count() : 0,
            'surveys' => (int) \DB::select()->from('core_crm_surveys')->where('active', '=', 1)->execute()->count(),
        ];
    }

    protected function party_options()
    {
        $rows = [];
        foreach (\DB::select('id', 'name', 'rfc', 'party_type')->from('core_parties')->where('active', '=', 1)->order_by('name', 'asc')->execute() as $row) {
            $rows[] = ['value' => (string) $row['id'], 'label' => $row['name'].' ('.$row['party_type'].')'.($row['rfc'] ? ' - '.$row['rfc'] : '')];
        }
        return $rows;
    }

    protected function user_options()
    {
        $rows = [];
        foreach (\DB::select('id', 'username')->from('users')->order_by('username', 'asc')->execute() as $row) {
            $rows[] = ['value' => (string) $row['id'], 'label' => (string) $row['username']];
        }
        return $rows;
    }

    protected function survey_options()
    {
        $rows = [];
        foreach (\DB::select('id', 'name')->from('core_crm_surveys')->where('active', '=', 1)->order_by('name', 'asc')->execute() as $row) {
            $rows[] = ['value' => (string) $row['id'], 'label' => (string) $row['name']];
        }
        return $rows;
    }

    protected function format_dates($row)
    {
        foreach (['expected_close_at', 'next_action_at', 'due_at', 'completed_at'] as $field) {
            if (isset($row[$field])) {
                $row[$field.'_label'] = (int) $row[$field] > 0 ? date('d/m/Y H:i', (int) $row[$field]) : '';
                $row[$field.'_input'] = (int) $row[$field] > 0 ? date('Y-m-d\TH:i', (int) $row[$field]) : '';
            }
        }
        return $row;
    }

    protected function assert_schema_ready()
    {
        foreach (['core_crm_opportunities', 'core_crm_activities', 'core_crm_surveys', 'core_crm_survey_responses', 'core_crm_cut_calculations'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de CRM.');
            }
        }
    }

    protected function next_folio($prefix, $table)
    {
        return $prefix.'-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records($table) + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function date_to_time($value)
    {
        $value = trim((string) $value);
        if ($value === '' || ctype_digit($value)) {
            return (int) $value;
        }
        return strtotime($value.' 00:00:00') ?: 0;
    }

    protected function datetime_to_time($value)
    {
        $value = trim((string) $value);
        if ($value === '' || ctype_digit($value)) {
            return (int) $value;
        }
        return strtotime(str_replace('T', ' ', $value)) ?: 0;
    }

    protected function opportunity_stage($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['new', 'qualified', 'quoted', 'won', 'lost'], true) ? $value : 'new';
    }

    protected function activity_type($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['call', 'visit', 'email', 'task', 'note', 'survey', 'cut'], true) ? $value : 'note';
    }

    protected function activity_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['open', 'scheduled', 'done', 'cancelled'], true) ? $value : 'open';
    }

    protected function priority($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['low', 'normal', 'high', 'urgent'], true) ? $value : 'normal';
    }

    protected function codeify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
