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
        try {
            $container = new \Service_Core_Workspace_Container();
            $response = $container->runner()->run($code, $container->context());
        } catch (\Exception $e) {
            \Log::error('Workspace widget endpoint error code='.(string) $code.' message='.$e->getMessage());
            $response = [
                'success' => false,
                'message' => 'No se pudo cargar el widget.',
                'html' => '',
                'refresh_time' => 0,
            ];
        }

        return $this->json_response($response, 200);
    }

    public function action_quick_actions()
    {
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

    public function post_save_layout()
    {
        $items = \Input::post('widgets', []);
        if (!is_array($items)) {
            return $this->json_response(['success' => false, 'message' => 'Layout invalido.', 'errors' => ['invalid_layout']], 400);
        }

        return $this->json_response([
            'success' => true,
            'message' => 'Layout recibido. El guardado persistente se completara en la siguiente fase.',
            'data' => ['widgets' => $items],
            'errors' => [],
        ]);
    }

    public function post_save_preferences()
    {
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
                ['widget_code' => 'welcome', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 2, 'collapsed' => 0, 'favorite' => 0],
                ['widget_code' => 'quick_links', 'x' => 4, 'y' => 0, 'w' => 4, 'h' => 2, 'collapsed' => 0, 'favorite' => 0],
                ['widget_code' => 'notifications_placeholder', 'x' => 8, 'y' => 0, 'w' => 4, 'h' => 2, 'collapsed' => 0, 'favorite' => 0],
            ],
        ];
    }
}
