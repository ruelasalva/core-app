<?php

/**
 * CONTROLADOR ADMIN_VIATICS
 *
 * Gestion de viaticos de viaje para empleados y vendedores.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Viatics extends Controller_Adminbase
{
    protected $manager;

    public function before()
    {
        parent::before();
        $this->require_access('viatics.access[view]');
        $this->manager = new Service_Core_Viatics_Manager();
    }

    public function action_index()
    {
        $this->template->title = 'Viaticos';
        $this->template->content = \View::forge('admin/viatics/index');
    }

    public function action_data()
    {
        try {
            if (!$this->manager->schema_ready()) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'El modulo de viaticos requiere migraciones pendientes.',
                    'data' => [],
                    'errors' => ['viatics_schema_missing'],
                ], 500);
            }

            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => $this->manager->data_for_admin((int) $this->user_id, (int) $this->user_group, (bool) $this->is_super_admin, (int) $this->employee_department_id()),
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando viaticos: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudieron cargar viaticos.',
                'data' => [],
                'errors' => ['viatics_data_error'],
            ], 500);
        }
    }

    public function action_save_trip()
    {
        $this->require_access('viatics.access[create]');
        $val = (array) \Input::json();

        try {
            $trip_id = $this->manager->save_trip($val, (int) $this->user_id);
            \Log::info('Viaticos: viaje guardado id='.$trip_id.' user_id='.$this->user_id);
            return $this->action_data();
        } catch (\InvalidArgumentException $e) {
            return $this->json_response([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'errors' => ['validation_error'],
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error guardando viaje de viaticos: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo guardar el viaje.',
                'data' => [],
                'errors' => ['save_trip_error'],
            ], 500);
        }
    }

    public function action_save_advance()
    {
        $this->require_access('viatics.access[edit]');
        $val = (array) \Input::json();

        try {
            $advance_id = $this->manager->save_advance($val, (int) $this->user_id);
            \Log::info('Viaticos: anticipo guardado id='.$advance_id.' user_id='.$this->user_id);
            return $this->action_data();
        } catch (\InvalidArgumentException $e) {
            return $this->json_response([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'errors' => ['validation_error'],
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error guardando anticipo de viaticos: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo guardar el anticipo.',
                'data' => [],
                'errors' => ['save_advance_error'],
            ], 500);
        }
    }

    public function action_save_expense()
    {
        $this->require_access('viatics.access[edit]');
        $val = (array) \Input::json();

        try {
            $expense_id = $this->manager->save_expense($val, (int) $this->user_id);
            \Log::info('Viaticos: gasto guardado id='.$expense_id.' user_id='.$this->user_id);
            return $this->action_data();
        } catch (\InvalidArgumentException $e) {
            return $this->json_response([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'errors' => ['validation_error'],
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error guardando gasto de viaticos: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo guardar el gasto.',
                'data' => [],
                'errors' => ['save_expense_error'],
            ], 500);
        }
    }

    public function action_settle_trip($trip_id = null)
    {
        $this->require_access('viatics.access[post]');
        $val = (array) \Input::json();

        try {
            $id = (int) $trip_id;
            if ($id < 1) {
                $id = (int) \Arr::get($val, 'trip_id', 0);
            }
            if ($id < 1) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Selecciona el viaje a cerrar.',
                    'data' => [],
                    'errors' => ['invalid_trip'],
                ], 422);
            }

            $settlement_id = $this->manager->settle_trip($id, $val, (int) $this->user_id);
            \Log::info('Viaticos: viaje conciliado trip_id='.$id.' settlement_id='.$settlement_id.' user_id='.$this->user_id);
            return $this->action_data();
        } catch (\InvalidArgumentException $e) {
            return $this->json_response([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'errors' => ['validation_error'],
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error cerrando viaje de viaticos: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo cerrar el viaje.',
                'data' => [],
                'errors' => ['settle_trip_error'],
            ], 500);
        }
    }

    public function action_generate_draft($trip_id = null)
    {
        $this->require_access('viatics.access[post]');
        $val = (array) \Input::json();

        try {
            $id = (int) $trip_id;
            if ($id < 1) {
                $id = (int) \Arr::get($val, 'trip_id', 0);
            }
            if ($id < 1) {
                return $this->json_response([
                    'success' => false,
                    'message' => 'Selecciona el viaje a contabilizar.',
                    'data' => [],
                    'errors' => ['invalid_trip'],
                ], 422);
            }

            $entry_id = $this->manager->generate_accounting_draft($id, (int) $this->user_id);
            \Log::info('Viaticos: borrador contable generado trip_id='.$id.' entry_id='.$entry_id.' user_id='.$this->user_id);
            return $this->action_data();
        } catch (\InvalidArgumentException $e) {
            return $this->json_response([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'errors' => ['validation_error'],
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error generando borrador contable de viaticos: '.$e->getMessage());
            return $this->json_response([
                'success' => false,
                'message' => 'No se pudo generar el borrador contable.',
                'data' => [],
                'errors' => ['generate_draft_error'],
            ], 500);
        }
    }
}

