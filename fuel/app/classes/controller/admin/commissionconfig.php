<?php

/**
 * CONTROLADOR ADMIN_COMMISSIONCONFIG
 *
 * Plataforma de configuracion futura de comisiones. No calcula ni libera comisiones.
 */
class Controller_Admin_Commissionconfig extends Controller_Adminbase
{
    public function before()
    {
        if ($this->is_json_action()) {
            $this->auto_render = false;
            if (\Auth::check()) {
                $this->prepare_json_context();
            }
            return;
        }

        parent::before();
        $this->require_access('commissions.access[view]');
    }

    public function action_index()
    {
        $this->template->title = 'Configuración de Comisiones';
        $this->template->content = \View::forge('admin/commissionconfig/index');
    }

    public function action_data()
    {
        if ($response = $this->json_guard('view')) {
            return $response;
        }

        try {
            return $this->json_response(array(
                'success' => true,
                'message' => '',
                'data' => (new \Service_Core_Commissions_Configuration())->data(),
                'errors' => array(),
            ));
        } catch (\Exception $e) {
            \Log::error('Error cargando configuracion de comisiones: '.$e->getMessage());
            return $this->json_response(array(
                'success' => false,
                'message' => 'No se pudo cargar la configuración de comisiones.',
                'data' => array(),
                'errors' => array('commission_config_load_failed'),
            ), 500);
        }
    }

    public function action_simulate()
    {
        if ($response = $this->json_guard('view')) {
            return $response;
        }

        try {
            $result = (new \Service_Core_Commissions_Simulator())->simulate(\Input::get());
            return $this->json_response($result, !empty($result['success']) ? 200 : 422);
        } catch (\Exception $e) {
            \Log::warning('No se pudo simular comisiones: '.$e->getMessage());
            return $this->json_response(array(
                'success' => false,
                'message' => 'No se pudo simular la configuracion de comisiones.',
                'data' => array(),
                'errors' => array('simulation_failed'),
            ), 500);
        }
    }

    public function action_save_plan()
    {
        return $this->post_save_plan();
    }

    public function post_save_plan()
    {
        return $this->save('plan');
    }

    public function action_save_version()
    {
        return $this->post_save_version();
    }

    public function post_save_version()
    {
        return $this->save('version');
    }

    public function action_save_group()
    {
        return $this->post_save_group();
    }

    public function post_save_group()
    {
        return $this->save('group');
    }

    public function action_save_rule()
    {
        return $this->post_save_rule();
    }

    public function post_save_rule()
    {
        return $this->save('rule');
    }

    public function action_save_stage()
    {
        return $this->post_save_stage();
    }

    public function post_save_stage()
    {
        return $this->save('stage');
    }

    public function action_save_beneficiary()
    {
        return $this->post_save_beneficiary();
    }

    public function post_save_beneficiary()
    {
        return $this->save('beneficiary');
    }

    public function action_save_exclusion()
    {
        return $this->post_save_exclusion();
    }

    public function post_save_exclusion()
    {
        return $this->save('exclusion');
    }

    public function action_save_catalog()
    {
        return $this->post_save_catalog();
    }

    public function post_save_catalog()
    {
        return $this->save('catalog');
    }

    public function action_publish_version()
    {
        return $this->post_publish_version();
    }

    public function post_publish_version()
    {
        if ($response = $this->json_guard('authorize')) {
            return $response;
        }
        if ($response = $this->require_explicit_json_csrf_token()) {
            return $response;
        }

        try {
            $payload = $this->payload();
            $version_id = (int) \Arr::get($payload, 'version_id', 0);
            $reason = trim((string) \Arr::get($payload, 'reason', ''));

            if ($version_id < 1 || $reason === '') {
                return $this->json_response(array(
                    'success' => false,
                'message' => 'Selecciona versión y captura el motivo de publicación.',
                    'data' => array(),
                    'errors' => array('invalid_publish_request'),
                ), 422);
            }

            (new \Service_Core_Commissions_Configuration())->publish_version($version_id, $this->user_id, $reason);
            return $this->json_response(array(
                'success' => true,
                'message' => 'Versión publicada. La configuración queda inmutable.',
                'data' => (new \Service_Core_Commissions_Configuration())->data(),
                'errors' => array(),
            ));
        } catch (\Exception $e) {
            \Log::warning('No se pudo publicar version de comisiones: '.$e->getMessage());
            return $this->json_response(array(
                'success' => false,
                'message' => $this->safe_exception_message($e),
                'data' => array(),
                'errors' => array('publish_failed'),
            ), 400);
        }
    }

    protected function save($type)
    {
        if ($response = $this->json_guard('edit')) {
            return $response;
        }
        if ($response = $this->require_explicit_json_csrf_token()) {
            return $response;
        }

        try {
            $service = new \Service_Core_Commissions_Configuration();
            $method = 'save_'.$type;
            if (!method_exists($service, $method)) {
                return $this->json_response(array(
                    'success' => false,
                    'message' => 'Operación no soportada.',
                    'data' => array(),
                    'errors' => array('unsupported_operation'),
                ), 404);
            }

            $service->{$method}($this->payload(), $this->user_id);
            return $this->json_response(array(
                'success' => true,
                'message' => 'Configuración guardada.',
                'data' => $service->data(),
                'errors' => array(),
            ));
        } catch (\Exception $e) {
            \Log::warning('No se pudo guardar configuracion de comisiones type='.$type.' error='.$e->getMessage());
            return $this->json_response(array(
                'success' => false,
                'message' => $this->safe_exception_message($e),
                'data' => array(),
                'errors' => array('save_failed'),
            ), 400);
        }
    }

    protected function is_json_action()
    {
        $action = \Request::active() ? (string) \Request::active()->action : '';
        return in_array($action, array(
            'data',
            'simulate',
            'save_plan',
            'save_version',
            'save_group',
            'save_rule',
            'save_stage',
            'save_beneficiary',
            'save_exclusion',
            'save_catalog',
            'publish_version',
        ), true);
    }

    protected function prepare_json_context()
    {
        $user_id_data = \Auth::get_user_id();
        $this->user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;

        $groups = \Auth::get_groups();
        if (!empty($groups)) {
            $group_data = $groups[0][1];
            $this->user_group = is_object($group_data) ? (int) $group_data->id : (int) $group_data;
        }

        $this->is_super_admin = ($this->user_group === 100);
    }

    protected function json_guard($action)
    {
        if (!\Auth::check()) {
            return $this->json_response(array(
                'success' => false,
                'message' => 'Sesión requerida.',
                'data' => array(),
                'errors' => array('auth_required'),
            ), 401);
        }

        $permission = $action === 'view' ? 'commissions.access[view]' : ($action === 'authorize' ? 'commissions.access[authorize]' : 'commissions.access[edit]');
        if (!$this->is_super_admin && !\Auth::has_access($permission)) {
            return $this->json_response(array(
                'success' => false,
                'message' => 'No tienes permiso para configurar comisiones.',
                'data' => array(),
                'errors' => array('permission_denied'),
            ), 403);
        }

        return null;
    }

    protected function payload()
    {
        $json = \Input::json();
        if (is_array($json) && !empty($json)) {
            return $json;
        }

        return \Input::post();
    }

    protected function require_explicit_json_csrf_token()
    {
        $key = \Config::get('security.csrf_token_key', 'fuel_csrf_token');
        $payload = (array) \Input::json();
        $token = (string) \Input::headers('X-CSRF-Token', '');

        if ($token === '') {
            $token = (string) \Arr::get($payload, $key, '');
        }

        if ($token === '') {
            $token = (string) \Input::post($key, '');
        }

        $expected = (string) \Security::fetch_token();
        $valid = function_exists('hash_equals')
            ? hash_equals($expected, $token)
            : $expected === $token;

        if ($token === '' || !$valid) {
            return $this->json_response(array(
                'success' => false,
                'message' => 'Token de seguridad inválido o expirado.',
                'data' => array(),
                'errors' => array('csrf_invalid'),
            ), 403);
        }

        return null;
    }

    protected function safe_exception_message(\Exception $e)
    {
        $message = trim($e->getMessage());
        if ($message === '') {
            return 'No se pudo procesar la solicitud.';
        }
        return $message;
    }
}
