<?php

class Service_Core_Workspace_Widgets_System_Welcome extends \Service_Core_Workspace_Widget
{
    public static function manifest()
    {
        return [
            'code' => 'welcome',
            'title' => 'Bienvenida',
            'description' => 'Mensaje inicial del Workspace.',
            'category' => 'system',
            'type' => 'metric',
            'icon' => 'bi bi-grid',
            'color' => 'primary',
            'permission_key' => 'workspace.access[view]',
            'refresh_time' => 0,
            'dependencies' => [],
            'exportable' => false,
            'configurable' => false,
            'settings_schema' => [],
            'version' => 1,
            'status' => 'active',
        ];
    }

    public function load($context, array $filters = [], array $settings = [])
    {
        $timezone = (string) \Arr::get($context, 'timezone', 'America/Mexico_City');
        $now = new \DateTime('now', new \DateTimeZone($timezone));
        $hour = (int) $now->format('G');
        $name = trim((string) \Auth::get_screen_name());
        $next_action = $this->suggested_action($context);

        return [
            'layout' => 'welcome',
            'greeting' => $this->greeting($hour).($name !== '' ? ', '.$name : ''),
            'user_name' => $name,
            'role_label' => $this->group_label((int) \Arr::get($context, 'group_id', 0)),
            'date_label' => $now->format('d/m/Y'),
            'status_label' => 'Workspace operativo',
            'message' => 'Tu espacio de trabajo está listo para continuar.',
            'suggested_action' => [
                'label' => $next_action['label'],
                'url' => $next_action['url'],
            ],
        ];
    }

    protected function greeting($hour)
    {
        if ($hour < 12) {
            return 'Buenos días';
        }

        if ($hour < 19) {
            return 'Buenas tardes';
        }

        return 'Buenas noches';
    }

    protected function group_label($group_id)
    {
        if ($group_id < 1 || !\DBUtil::table_exists('users_groups')) {
            return 'Usuario';
        }

        try {
            $row = \DB::select('name')
                ->from('users_groups')
                ->where('id', '=', $group_id)
                ->execute()
                ->current();
        } catch (\Exception $e) {
            \Log::warning('Workspace welcome group lookup failed group_id='.$group_id.' message='.$e->getMessage());
            return 'Usuario';
        }

        return $row ? (string) \Arr::get($row, 'name', 'Usuario') : 'Usuario';
    }

    protected function suggested_action(array $context)
    {
        $actions = (new \Service_Core_Workspace_QuickActions())->allowed($context);
        if (empty($actions)) {
            return [
                'label' => 'Revisar Workspace',
                'url' => \Uri::create('admin/workspace'),
            ];
        }

        $first = reset($actions);

        return [
            'label' => 'Siguiente acción: '.(string) \Arr::get($first, 'title', 'Abrir módulo'),
            'url' => \Uri::create((string) \Arr::get($first, 'route', 'admin/workspace')),
        ];
    }
}
