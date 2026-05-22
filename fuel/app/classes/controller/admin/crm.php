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
                'prospects' => $this->prospects(),
                'prospect_imports' => $this->prospect_imports(),
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
                'prospect_id' => (int) \Arr::get($val, 'prospect_id', 0),
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
                'prospect_id' => (int) \Arr::get($val, 'prospect_id', 0),
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

    /**
     * DENUE SEARCH
     *
     * CONSULTA LA API OFICIAL DENUE PARA PREVISUALIZAR PROSPECTOS.
     *
     * @access  public
     * @return  Response
     */
    public function action_denue_search()
    {
        $this->require_access('crm.access[import]');
        $payload = (array) \Input::json();

        try {
            $this->assert_schema_ready();
            $connection = $this->denue_connection();
            if (!$connection) {
                return $this->json_response(['error' => 'Configura y activa la conexion INEGI DENUE en Integraciones. Captura el Token DENUE INEGI.'], 422);
            }

            $rows = $this->denue_request($connection, $payload);
            return $this->json_response(['status' => 'ok', 'results' => $this->normalize_denue_rows($rows)]);
        } catch (\Exception $e) {
            \Log::error('CRM DENUE: error buscando prospectos: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo consultar DENUE: '.$e->getMessage()], 400);
        }
    }

    /**
     * DENUE IMPORT
     *
     * IMPORTA RESULTADOS SELECCIONADOS COMO PROSPECTOS CRM.
     *
     * @access  public
     * @return  Response
     */
    public function action_denue_import()
    {
        $this->require_access('crm.access[import]');
        $payload = (array) \Input::json();

        try {
            $this->assert_schema_ready();
            $source_id = $this->denue_source_id();
            $connection = $this->denue_connection();
            $items = (array) \Arr::get($payload, 'items', []);
            if (empty($items)) {
                return $this->json_response(['error' => 'Selecciona al menos un resultado para importar.'], 422);
            }

            $import = \Model_Core_Crm_Prospect_Import::forge([
                'source_id' => $source_id,
                'connection_id' => $connection ? (int) $connection->id : 0,
                'folio' => $this->next_folio('DENUE', 'core_crm_prospect_imports'),
                'query_type' => 'denue_api',
                'query_json' => json_encode((array) \Arr::get($payload, 'query', [])),
                'results_count' => count($items),
                'imported_count' => 0,
                'skipped_count' => 0,
                'status' => 'completed',
                'created_by' => $this->user_id,
                'active' => 1,
            ]);
            $import->save();

            $imported = 0;
            $skipped = 0;
            foreach ($items as $item) {
                $data = $this->prospect_data_from_denue((array) $item, $source_id, (int) $import->id);
                if ($data['external_id'] !== '') {
                    $exists = \DB::select('id')
                        ->from('core_crm_prospects')
                        ->where('source_id', '=', $source_id)
                        ->where('external_id', '=', $data['external_id'])
                        ->execute()
                        ->current();
                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                }

                \Model_Core_Crm_Prospect::forge($data)->save();
                $imported++;
            }

            $import->imported_count = $imported;
            $import->skipped_count = $skipped;
            $import->save();

            \Helper_Core_Audit::log([
                'module' => 'crm',
                'action' => 'denue_import',
                'business_event' => 'crm.denue_import',
                'entity_type' => 'crm_prospect_import',
                'entity_id' => (int) $import->id,
                'summary' => 'Importacion DENUE '.$import->folio,
                'new_values' => $import->to_array(),
            ]);

            return $this->data_ok();
        } catch (\Exception $e) {
            \Log::error('CRM DENUE: error importando prospectos: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron importar prospectos.'], 400);
        }
    }

    /**
     * SAVE PROSPECT
     *
     * ACTUALIZA ESTATUS, RESPONSABLE Y NOTAS DE UN PROSPECTO.
     *
     * @access  public
     * @return  Response
     */
    public function action_save_prospect()
    {
        $this->require_access('crm.access[edit]');
        $val = (array) \Input::json();

        try {
            $id = (int) \Arr::get($val, 'id', 0);
            $prospect = $id > 0 ? \Model_Core_Crm_Prospect::find($id) : null;
            if (!$prospect) {
                return $this->json_response(['error' => 'Prospecto no encontrado.'], 404);
            }

            $old = $prospect->to_array();
            $prospect->set([
                'owner_user_id' => (int) \Arr::get($val, 'owner_user_id', $prospect->owner_user_id),
                'seller_id' => (int) \Arr::get($val, 'seller_id', $prospect->seller_id),
                'status' => $this->prospect_status(\Arr::get($val, 'status', $prospect->status)),
                'priority' => $this->priority(\Arr::get($val, 'priority', $prospect->priority)),
                'next_action_at' => $this->datetime_to_time(\Arr::get($val, 'next_action_at_input', \Arr::get($val, 'next_action_at', $prospect->next_action_at))),
                'notes' => trim((string) \Arr::get($val, 'notes', $prospect->notes)),
                'active' => (int) (bool) \Arr::get($val, 'active', true),
            ]);
            $prospect->save();

            \Helper_Core_Audit::log([
                'module' => 'crm',
                'action' => 'update_prospect',
                'business_event' => 'crm.prospect_updated',
                'entity_type' => 'crm_prospect',
                'entity_id' => (int) $prospect->id,
                'summary' => 'Prospecto '.$prospect->name.' actualizado',
                'old_values' => $old,
                'new_values' => $prospect->to_array(),
            ]);

            return $this->data_ok();
        } catch (\Exception $e) {
            \Log::error('CRM: error guardando prospecto: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo guardar el prospecto.'], 400);
        }
    }

    /**
     * CONVERT PROSPECT
     *
     * CONVIERTE UN PROSPECTO EN TERCERO CLIENTE Y OPORTUNIDAD.
     *
     * @access  public
     * @return  Response
     */
    public function action_convert_prospect()
    {
        $this->require_access('crm.access[create]');
        $val = (array) \Input::json();

        try {
            $prospect = \Model_Core_Crm_Prospect::find((int) \Arr::get($val, 'id', 0));
            if (!$prospect) {
                return $this->json_response(['error' => 'Prospecto no encontrado.'], 404);
            }
            if ((int) $prospect->converted_party_id > 0) {
                return $this->json_response(['error' => 'Este prospecto ya fue convertido.'], 422);
            }

            $party = \Model_Core_Party::forge([
                'party_type' => 'customer',
                'code' => $this->next_party_code(),
                'name' => (string) $prospect->name,
                'legal_name' => (string) $prospect->legal_name,
                'rfc' => '',
                'email' => (string) $prospect->email,
                'phone' => (string) $prospect->phone,
                'department_id' => 0,
                'sales_user_id' => (int) $prospect->owner_user_id,
                'default_seller_id' => (int) $prospect->seller_id,
                'buyer_user_id' => 0,
                'price_list_id' => 0,
                'payment_term_id' => 0,
                'sat_cfdi_use_code' => 'G03',
                'sat_tax_regime_code' => '601',
                'fiscal_operation_type_id' => 0,
                'shipping_method_id' => 0,
                'credit_limit' => 0,
                'credit_days' => 0,
                'notes' => 'Convertido desde prospecto DENUE #'.(int) $prospect->id."\n".(string) $prospect->notes,
                'onboarding_status' => 'approved',
                'onboarding_notes' => '',
                'active' => 1,
            ]);
            $party->save();

            if (\DBUtil::table_exists('core_party_addresses') && trim((string) $prospect->full_address) !== '') {
                \DB::insert('core_party_addresses')->set([
                    'party_id' => (int) $party->id,
                    'address_type' => 'main',
                    'name' => 'Direccion DENUE',
                    'street' => (string) $prospect->street,
                    'exterior_number' => (string) $prospect->external_number,
                    'interior_number' => '',
                    'neighborhood' => (string) $prospect->neighborhood,
                    'city' => (string) $prospect->municipality,
                    'state' => (string) $prospect->state,
                    'country_code' => 'MX',
                    'postal_code' => (string) $prospect->postal_code,
                    'is_default' => 1,
                    'active' => 1,
                    'created_at' => time(),
                    'updated_at' => time(),
                ])->execute();
            }

            $prospect->converted_party_id = (int) $party->id;
            $prospect->converted_at = time();
            $prospect->status = 'converted';
            $prospect->save();

            if ((int) \Arr::get($val, 'create_opportunity', 1) === 1) {
                \Model_Core_Crm_Opportunity::forge([
                    'folio' => $this->next_folio('OPP', 'core_crm_opportunities'),
                    'party_id' => (int) $party->id,
                    'prospect_id' => (int) $prospect->id,
                    'owner_user_id' => (int) $prospect->owner_user_id ?: $this->user_id,
                    'department_id' => 0,
                    'source' => 'denue',
                    'stage' => 'new',
                    'title' => 'Seguimiento '.$prospect->name,
                    'description' => (string) $prospect->activity,
                    'estimated_amount' => 0,
                    'probability' => 0,
                    'expected_close_at' => 0,
                    'next_action_at' => (int) $prospect->next_action_at,
                    'lost_reason' => '',
                    'active' => 1,
                ])->save();
            }

            \Helper_Core_Audit::log([
                'module' => 'crm',
                'action' => 'convert_prospect',
                'business_event' => 'crm.prospect_converted',
                'entity_type' => 'crm_prospect',
                'entity_id' => (int) $prospect->id,
                'summary' => 'Prospecto convertido a cliente '.$party->name,
                'new_values' => ['party_id' => (int) $party->id],
            ]);

            return $this->data_ok();
        } catch (\Exception $e) {
            \Log::error('CRM: error convirtiendo prospecto: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo convertir el prospecto.'], 400);
        }
    }

    protected function data_ok()
    {
        return $this->json_response([
            'status' => 'ok',
            'opportunities' => $this->opportunities(),
            'activities' => $this->activities(),
            'prospects' => $this->prospects(),
            'prospect_imports' => $this->prospect_imports(),
            'survey_responses' => $this->survey_responses(),
            'cut_calculations' => $this->cut_calculations(),
            'options' => $this->options(),
            'stats' => $this->stats(),
        ]);
    }

    protected function opportunities()
    {
        $query = \DB::select(['o.id', 'id'], ['o.folio', 'folio'], ['o.party_id', 'party_id'], ['p.name', 'party_name'], ['o.prospect_id', 'prospect_id'], ['pr.name', 'prospect_name'], ['o.owner_user_id', 'owner_user_id'], ['u.username', 'owner_name'], ['o.stage', 'stage'], ['o.source', 'source'], ['o.title', 'title'], ['o.description', 'description'], ['o.estimated_amount', 'estimated_amount'], ['o.probability', 'probability'], ['o.expected_close_at', 'expected_close_at'], ['o.next_action_at', 'next_action_at'], ['o.lost_reason', 'lost_reason'], ['o.active', 'active'])
            ->from(['core_crm_opportunities', 'o'])
            ->join(['core_parties', 'p'], 'left')->on('o.party_id', '=', 'p.id')
            ->join(['core_crm_prospects', 'pr'], 'left')->on('o.prospect_id', '=', 'pr.id')
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
        foreach (\DB::select(['a.id', 'id'], ['a.party_id', 'party_id'], ['p.name', 'party_name'], ['a.prospect_id', 'prospect_id'], ['pr.name', 'prospect_name'], ['a.opportunity_id', 'opportunity_id'], ['o.folio', 'opportunity_folio'], ['a.ticket_id', 'ticket_id'], ['t.folio', 'ticket_folio'], ['a.activity_type', 'activity_type'], ['a.subject', 'subject'], ['a.description', 'description'], ['a.status', 'status'], ['a.priority', 'priority'], ['a.assigned_user_id', 'assigned_user_id'], ['u.username', 'assigned_name'], ['a.due_at', 'due_at'], ['a.completed_at', 'completed_at'])
            ->from(['core_crm_activities', 'a'])
            ->join(['core_parties', 'p'], 'left')->on('a.party_id', '=', 'p.id')
            ->join(['core_crm_prospects', 'pr'], 'left')->on('a.prospect_id', '=', 'pr.id')
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

    protected function prospects()
    {
        $rows = [];
        foreach (\DB::select(['p.id', 'id'], ['p.source_id', 'source_id'], ['s.name', 'source_name'], ['p.external_id', 'external_id'], ['p.external_clee', 'external_clee'], ['p.name', 'name'], ['p.legal_name', 'legal_name'], ['p.activity', 'activity'], ['p.size_range', 'size_range'], ['p.phone', 'phone'], ['p.email', 'email'], ['p.website', 'website'], ['p.state', 'state'], ['p.municipality', 'municipality'], ['p.locality', 'locality'], ['p.neighborhood', 'neighborhood'], ['p.postal_code', 'postal_code'], ['p.full_address', 'full_address'], ['p.latitude', 'latitude'], ['p.longitude', 'longitude'], ['p.owner_user_id', 'owner_user_id'], ['u.username', 'owner_name'], ['p.seller_id', 'seller_id'], ['sl.name', 'seller_name'], ['p.status', 'status'], ['p.priority', 'priority'], ['p.next_action_at', 'next_action_at'], ['p.converted_party_id', 'converted_party_id'], ['cp.name', 'converted_party_name'], ['p.notes', 'notes'], ['p.created_at', 'created_at'])
            ->from(['core_crm_prospects', 'p'])
            ->join(['core_crm_external_sources', 's'], 'left')->on('p.source_id', '=', 's.id')
            ->join(['users', 'u'], 'left')->on('p.owner_user_id', '=', 'u.id')
            ->join(['core_sales_sellers', 'sl'], 'left')->on('p.seller_id', '=', 'sl.id')
            ->join(['core_parties', 'cp'], 'left')->on('p.converted_party_id', '=', 'cp.id')
            ->where('p.active', '=', 1)
            ->order_by('p.id', 'desc')
            ->limit(300)
            ->execute() as $row) {
            $rows[] = $this->format_dates($row);
        }
        return $rows;
    }

    protected function prospect_imports()
    {
        return \DB::select(['i.id', 'id'], ['i.folio', 'folio'], ['s.name', 'source_name'], ['i.query_type', 'query_type'], ['i.results_count', 'results_count'], ['i.imported_count', 'imported_count'], ['i.skipped_count', 'skipped_count'], ['i.status', 'status'], ['i.error_message', 'error_message'], ['u.username', 'created_by_name'], ['i.created_at', 'created_at'])
            ->from(['core_crm_prospect_imports', 'i'])
            ->join(['core_crm_external_sources', 's'], 'left')->on('i.source_id', '=', 's.id')
            ->join(['users', 'u'], 'left')->on('i.created_by', '=', 'u.id')
            ->where('i.active', '=', 1)
            ->order_by('i.id', 'desc')
            ->limit(50)
            ->execute()
            ->as_array();
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
            'prospects' => $this->prospect_options(),
            'users' => $this->user_options(),
            'surveys' => $this->survey_options(),
            'sellers' => \DBUtil::table_exists('core_sales_sellers') ? $this->select_options('core_sales_sellers', 'id', 'name') : [],
            'denue_connection_ready' => $this->denue_connection() ? 1 : 0,
        ];
    }

    protected function stats()
    {
        return [
            'opportunities' => (int) \DB::select()->from('core_crm_opportunities')->where('active', '=', 1)->execute()->count(),
            'prospects' => (int) \DB::select()->from('core_crm_prospects')->where('active', '=', 1)->execute()->count(),
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

    protected function prospect_options()
    {
        $rows = [];
        foreach (\DB::select('id', 'name', 'municipality', 'state', 'status')->from('core_crm_prospects')->where('active', '=', 1)->where('converted_party_id', '=', 0)->order_by('name', 'asc')->execute() as $row) {
            $rows[] = ['value' => (string) $row['id'], 'label' => $row['name'].' - '.$row['municipality'].', '.$row['state'].' ('.$row['status'].')'];
        }
        return $rows;
    }

    protected function select_options($table, $value_field, $label_field)
    {
        $rows = [];
        if (!\DBUtil::table_exists($table)) {
            return $rows;
        }
        foreach (\DB::select($value_field, $label_field)->from($table)->where('active', '=', 1)->order_by($label_field, 'asc')->execute() as $row) {
            $rows[] = ['value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]];
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
        foreach (['core_crm_opportunities', 'core_crm_activities', 'core_crm_surveys', 'core_crm_survey_responses', 'core_crm_cut_calculations', 'core_crm_external_sources', 'core_crm_prospect_imports', 'core_crm_prospects'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de CRM.');
            }
        }
        if (!\DBUtil::field_exists('core_crm_opportunities', ['prospect_id']) || !\DBUtil::field_exists('core_crm_activities', ['prospect_id'])) {
            throw new \RuntimeException('Falta actualizar CRM para prospectos.');
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

    protected function prospect_status($value)
    {
        $value = $this->codeify($value);
        return in_array($value, ['new', 'assigned', 'contacted', 'interested', 'not_interested', 'converted', 'discarded'], true) ? $value : 'new';
    }

    protected function denue_connection()
    {
        if (!\DBUtil::table_exists('core_integration_providers') || !\DBUtil::table_exists('core_integration_connections')) {
            return null;
        }
        $provider = \DB::select('id')->from('core_integration_providers')->where('code', '=', 'inegi_denue')->execute()->current();
        if (!$provider) {
            return null;
        }
        return \Model_Core_Integration_Connection::query()
            ->where('provider_id', (int) $provider['id'])
            ->where('enabled', 1)
            ->where('active', 1)
            ->order_by('id', 'desc')
            ->get_one();
    }

    protected function denue_source_id()
    {
        $source = \DB::select('id')->from('core_crm_external_sources')->where('code', '=', 'denue')->execute()->current();
        if ($source) {
            return (int) $source['id'];
        }

        list($id) = \DB::insert('core_crm_external_sources')->set([
            'code' => 'denue',
            'name' => 'DENUE INEGI',
            'provider_code' => 'inegi_denue',
            'description' => 'Directorio Estadistico Nacional de Unidades Economicas.',
            'website_url' => 'https://www.inegi.org.mx/servicios/api_denue.html',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
        return (int) $id;
    }

    protected function denue_request(\Model_Core_Integration_Connection $connection, array $payload)
    {
        $token = $this->decode_secret((string) $connection->secret_value);
        if ($token === '') {
            $token = trim((string) $connection->public_key);
        }
        if ($token === '') {
            throw new \RuntimeException('La conexion DENUE no tiene token.');
        }
        $token = rawurlencode($token);

        $keyword = rawurlencode(trim((string) \Arr::get($payload, 'keyword', '')));
        $state = trim((string) \Arr::get($payload, 'state_code', ''));
        $lat = trim((string) \Arr::get($payload, 'latitude', ''));
        $lng = trim((string) \Arr::get($payload, 'longitude', ''));
        $radius = max(50, min(5000, (int) \Arr::get($payload, 'radius', 500)));

        if ($keyword === '') {
            throw new \RuntimeException('Captura palabra clave o giro a buscar.');
        }

        if ($lat !== '' && $lng !== '') {
            $url = 'https://www.inegi.org.mx/app/api/denue/v1/consulta/Buscar/'.$keyword.'/'.$lat.','.$lng.'/'.$radius.'/'.$token;
        } elseif ($state !== '') {
            $start = max(1, (int) \Arr::get($payload, 'start_record', 1));
            $end = min(200, max($start, (int) \Arr::get($payload, 'end_record', 50)));
            $url = 'https://www.inegi.org.mx/app/api/denue/v1/consulta/BuscarEntidad/'.$keyword.'/'.$state.'/'.$start.'/'.$end.'/'.$token;
        } else {
            throw new \RuntimeException('Captura estado o coordenadas con radio.');
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Core-App CRM DENUE\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false || trim($body) === '') {
            throw new \RuntimeException('DENUE no respondio. Revisa token, red y filtros.');
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new \RuntimeException('DENUE regreso una respuesta no valida.');
        }

        \DB::insert('core_integration_events')->set([
            'provider_code' => 'inegi_denue',
            'connection_id' => (int) $connection->id,
            'event_type' => 'denue.search',
            'external_id' => '',
            'direction' => 'outgoing',
            'status' => 'completed',
            'payload_json' => json_encode($payload),
            'response_json' => json_encode(['count' => count($json)]),
            'received_at' => time(),
            'processed_at' => time(),
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        return $json;
    }

    protected function normalize_denue_rows(array $rows)
    {
        $items = [];
        foreach (array_slice($rows, 0, 200) as $row) {
            $items[] = $this->prospect_data_from_denue((array) $row, 0, 0);
        }
        return $items;
    }

    protected function prospect_data_from_denue(array $row, $source_id, $import_id)
    {
        $name = $this->denue_value($row, ['Nombre', 'nombre', 'nom_estab']);
        $street = $this->denue_value($row, ['Calle', 'calle']);
        $number = $this->denue_value($row, ['Num_Exterior', 'num_ext', 'numero_ext']);
        $neighborhood = $this->denue_value($row, ['Colonia', 'colonia']);
        $municipality = $this->denue_value($row, ['Municipio', 'municipio']);
        $state = $this->denue_value($row, ['Entidad', 'entidad']);
        $postal = $this->denue_value($row, ['CP', 'cp', 'codigo_postal']);
        $full_address = trim($this->denue_value($row, ['Ubicacion', 'ubicacion']));
        if ($full_address === '') {
            $full_address = trim($street.' '.$number.', '.$neighborhood.', '.$municipality.', '.$state.', '.$postal, ' ,');
        }

        return [
            'source_id' => (int) $source_id,
            'import_id' => (int) $import_id,
            'external_id' => $this->denue_value($row, ['Id', 'id']),
            'external_clee' => $this->denue_value($row, ['CLEE', 'clee']),
            'name' => $name !== '' ? $name : 'Prospecto DENUE',
            'legal_name' => $this->denue_value($row, ['Razon_social', 'razon_social']),
            'activity' => $this->denue_value($row, ['Clase_actividad', 'clase_actividad']),
            'activity_code' => $this->denue_value($row, ['Codigo_act', 'codigo_act']),
            'size_range' => $this->denue_value($row, ['Estrato', 'estrato']),
            'phone' => $this->denue_value($row, ['Telefono', 'telefono']),
            'email' => $this->denue_value($row, ['Correo_e', 'correo_e']),
            'website' => $this->denue_value($row, ['Sitio_internet', 'sitio_internet']),
            'state' => $state,
            'municipality' => $municipality,
            'locality' => $this->denue_value($row, ['Localidad', 'localidad']),
            'neighborhood' => $neighborhood,
            'postal_code' => $postal,
            'street' => $street,
            'external_number' => $number,
            'full_address' => $full_address,
            'latitude' => (float) $this->denue_value($row, ['Latitud', 'latitud']),
            'longitude' => (float) $this->denue_value($row, ['Longitud', 'longitud']),
            'owner_user_id' => $this->user_id,
            'seller_id' => 0,
            'status' => 'new',
            'priority' => 'normal',
            'next_action_at' => 0,
            'converted_party_id' => 0,
            'converted_at' => 0,
            'raw_json' => json_encode($row),
            'notes' => '',
            'active' => 1,
        ];
    }

    protected function denue_value(array $row, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null) {
                return trim((string) $row[$key]);
            }
        }
        return '';
    }

    protected function decode_secret($value)
    {
        if (trim($value) === '') {
            return '';
        }
        try {
            return \Crypt::decode($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    protected function next_party_code()
    {
        return 'CLI-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_parties') + 1), 5, '0', STR_PAD_LEFT);
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
