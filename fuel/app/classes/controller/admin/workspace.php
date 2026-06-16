<?php

/**
 * CONTROLADOR ADMIN_WORKSPACE
 *
 * Base minima del Workspace operativo. No reemplaza el dashboard existente.
 */
class Controller_Admin_Workspace extends Controller_Adminbase
{
    public function before()
    {
        if ($this->is_workspace_json_action()) {
            $this->auto_render = false;
            if (\Auth::check()) {
                $this->prepare_json_admin_context();
            }
            return;
        }

        parent::before();
        $this->require_workspace_access();
    }

    public function action_index()
    {
        \Log::info('Workspace abierto por usuario '.\Auth::get_screen_name().' user_id='.$this->user_id);

        $this->template->title = 'Workspace';
        $this->template->content = \View::forge('admin/workspace/index', [
            'title' => 'Workspace',
        ]);
    }

    public function action_data()
    {
        if ($response = $this->workspace_json_guard()) {
            return $response;
        }

        $container = new \Service_Core_Workspace_Container();
        $context = $container->context();

        return $this->json_response([
            'success' => true,
            'message' => '',
            'data' => [
                'context' => [
                    'user_id' => (int) $context['user_id'],
                    'group_id' => (int) $context['group_id'],
                    'is_super_admin' => (bool) $context['is_super_admin'],
                    'can_admin_workspace' => (bool) ($context['is_super_admin'] || \Auth::has_access('workspace.access[admin]')),
                    'can_edit_workspace' => (bool) ($context['is_super_admin'] || \Auth::has_access('workspace.access[edit]')),
                    'locale' => $context['locale'],
                    'timezone' => $context['timezone'],
                ],
                'layout' => $this->workspace_layout($context),
                'widgets' => $container->catalog()->allowed($context),
                'quick_actions' => $container->quick_actions()->allowed($context),
                'preferences' => $container->preferences()->for_user($this->user_id),
            ],
            'errors' => [],
        ]);
    }

    public function action_widget($code = null)
    {
        if ($response = $this->workspace_json_guard()) {
            return $response;
        }

        try {
            $container = new \Service_Core_Workspace_Container();
            $response = $container->runner()->run($code, $container->context());
        } catch (\Exception $e) {
            \Log::error('Workspace widget endpoint error code='.(string) $code.' message='.$e->getMessage());
            $response = [
                'success' => false,
                'message' => 'No se pudo cargar el widget.',
                'state' => 'error',
                'payload' => ['html' => ''],
                'meta' => [],
                'health' => [
                    'generated_at' => date('c'),
                    'cache_until' => null,
                    'execution_ms' => 0,
                    'cache_hit' => false,
                    'stale' => false,
                    'warning' => '',
                ],
                'actions' => [],
                'errors' => ['widget_endpoint_error'],
            ];
        }

        return $this->json_response($response, 200);
    }

    public function action_quick_actions()
    {
        if ($response = $this->workspace_json_guard()) {
            return $response;
        }

        $container = new \Service_Core_Workspace_Container();

        return $this->json_response([
            'success' => true,
            'message' => '',
            'data' => [
                'quick_actions' => $container->quick_actions()->allowed($container->context()),
            ],
            'errors' => [],
        ]);
    }

    public function action_command_palette()
    {
        if ($response = $this->workspace_json_guard()) {
            return $response;
        }

        $query = trim((string) \Input::get('q', ''));
        $container = new \Service_Core_Workspace_Container();
        $results = (new \Service_Core_Workspace_CommandPalette())->search($container->context(), $query);

        return $this->json_response([
            'success' => true,
            'message' => '',
            'data' => [
                'query' => $query,
                'results' => $results,
            ],
            'errors' => [],
        ]);
    }

    public function action_available_widgets()
    {
        if ($response = $this->workspace_json_guard()) {
            return $response;
        }

        $container = new \Service_Core_Workspace_Container();
        $context = $container->context();
        $layout = $this->workspace_layout($context);

        return $this->json_response([
            'success' => true,
            'message' => '',
            'data' => [
                'widgets' => $container->layout()->available_widgets($this->user_id, $context, $layout),
            ],
            'errors' => [],
        ]);
    }

    public function action_add_widget()
    {
        return $this->post_add_widget();
    }

    public function post_add_widget()
    {
        if ($response = $this->workspace_json_edit_guard()) {
            return $response;
        }

        $widget_code = trim((string) \Input::post('widget_code', \Input::json('widget_code', '')));
        $container = new \Service_Core_Workspace_Container();
        $context = $container->context();
        $result = $container->layout()->add_widget($this->user_id, $context, $widget_code, $this->workspace_layout($context));

        if (!empty($result['success'])) {
            $result['data']['layout'] = $this->workspace_layout($context);
        }

        return $this->json_response($result, !empty($result['success']) ? 200 : 400);
    }

    public function action_save_layout()
    {
        return $this->post_save_layout();
    }

    public function post_save_layout()
    {
        if ($response = $this->workspace_json_edit_guard()) {
            return $response;
        }

        $items = $this->posted_widgets();
        if (!is_array($items)) {
            return $this->json_response(['success' => false, 'message' => 'Layout invalido.', 'errors' => ['invalid_layout']], 400);
        }

        $result = (new \Service_Core_Workspace_Layout())->save_user_layout($this->user_id, $items);
        return $this->json_response($result, !empty($result['success']) ? 200 : 400);
    }

    public function action_reset_layout()
    {
        return $this->post_reset_layout();
    }

    public function post_reset_layout()
    {
        if ($response = $this->workspace_json_edit_guard()) {
            return $response;
        }

        $result = (new \Service_Core_Workspace_Layout())->reset_user_layout($this->user_id);
        return $this->json_response($result, !empty($result['success']) ? 200 : 400);
    }

    public function action_save_preferences()
    {
        return $this->post_save_preferences();
    }

    public function post_save_preferences()
    {
        if ($response = $this->workspace_json_guard()) {
            return $response;
        }

        $settings = \Input::post('settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }

        (new \Service_Core_Workspace_Preferences())->save($this->user_id, $settings);

        return $this->json_response([
            'success' => true,
            'message' => 'Preferencias guardadas.',
            'data' => [],
            'errors' => [],
        ]);
    }

    protected function require_workspace_access()
    {
        if (!$this->is_super_admin && !\Auth::has_access('workspace.access[view]')) {
            throw new \HttpNoAccessException;
        }
    }

    protected function is_workspace_json_action()
    {
        $action = \Request::active() ? (string) \Request::active()->action : '';

        return in_array($action, [
            'data',
            'widget',
            'quick_actions',
            'command_palette',
            'available_widgets',
            'add_widget',
            'save_layout',
            'save_preferences',
            'reset_layout',
        ], true);
    }

    protected function prepare_json_admin_context()
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

    protected function workspace_json_guard()
    {
        if (!\Auth::check()) {
            return $this->workspace_json_forbidden('Sesión requerida.', ['auth_required'], 401);
        }

        if (!$this->is_super_admin && !\Auth::has_access('workspace.access[view]')) {
            return $this->workspace_json_forbidden('No tienes permiso para acceder al Workspace.', ['permission_denied'], 403);
        }

        return null;
    }

    protected function workspace_json_forbidden($message, array $errors, $status)
    {
        return $this->json_response([
            'success' => false,
            'state' => 'forbidden',
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function workspace_json_edit_guard()
    {
        if ($response = $this->workspace_json_guard()) {
            return $response;
        }

        if (!$this->is_super_admin && !\Auth::has_access('workspace.access[edit]')) {
            return $this->workspace_json_forbidden('No tienes permiso para editar tu Workspace.', ['permission_denied'], 403);
        }

        return null;
    }

    protected function posted_widgets()
    {
        $items = \Input::post('widgets', []);
        if (empty($items)) {
            $items = \Input::json('widgets', []);
        }

        if (is_string($items)) {
            $decoded = json_decode($items, true);
            return is_array($decoded) ? $decoded : null;
        }

        return $items;
    }

    protected function workspace_layout(array $context)
    {
        if (!\DBUtil::table_exists('core_workspace_layouts') || !\DBUtil::table_exists('core_workspace_widget_instances')) {
            return $this->fallback_layout();
        }

        $layout = \DB::select()
            ->from('core_workspace_layouts')
            ->where('scope_type', '=', 'user')
            ->where('scope_id', '=', (int) $context['user_id'])
            ->where('active', '=', 1)
            ->order_by('is_default', 'desc')
            ->limit(1)
            ->execute()
            ->current();

        if (!$layout) {
            $layout = \DB::select()
                ->from('core_workspace_layouts')
                ->where('scope_type', '=', 'group')
                ->where('scope_id', '=', (int) $context['group_id'])
                ->where('active', '=', 1)
                ->order_by('is_default', 'desc')
                ->limit(1)
                ->execute()
                ->current();
        }

        if (!$layout) {
            $layout = \DB::select()
                ->from('core_workspace_layouts')
                ->where('scope_type', '=', 'template')
                ->where('active', '=', 1)
                ->order_by('is_default', 'desc')
                ->limit(1)
                ->execute()
                ->current();
        }

        if (!$layout) {
            return $this->fallback_layout();
        }

        $widgets = \DB::select()
            ->from('core_workspace_widget_instances')
            ->where('layout_id', '=', (int) $layout['id'])
            ->where('active', '=', 1)
            ->order_by('y', 'asc')
            ->order_by('x', 'asc')
            ->execute()
            ->as_array();

        $layout['widgets'] = $widgets;
        return $layout;
    }

    protected function fallback_layout()
    {
        return [
            'id' => 0,
            'name' => 'Workspace generico',
            'scope_type' => 'fallback',
            'scope_id' => 0,
            'profile_code' => 'generic',
            'preset_code' => 'generic',
            'widgets' => [
                ['widget_code' => 'welcome', 'x' => 0, 'y' => 0, 'w' => 6, 'h' => 2, 'collapsed' => 0, 'favorite' => 0],
                ['widget_code' => 'quick_actions', 'x' => 6, 'y' => 0, 'w' => 6, 'h' => 3, 'collapsed' => 0, 'favorite' => 0],
                ['widget_code' => 'favorites', 'x' => 0, 'y' => 2, 'w' => 4, 'h' => 2, 'collapsed' => 0, 'favorite' => 0],
                ['widget_code' => 'notifications', 'x' => 4, 'y' => 2, 'w' => 4, 'h' => 2, 'collapsed' => 0, 'favorite' => 0],
                ['widget_code' => 'recent_activity', 'x' => 8, 'y' => 2, 'w' => 4, 'h' => 3, 'collapsed' => 0, 'favorite' => 0],
            ],
        ];
    }
}
