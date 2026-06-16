<?php

class Service_Core_Workspace_Widgets_System_Quickactions extends \Service_Core_Workspace_Widget
{
    public static function manifest()
    {
        return [
            'code' => 'quick_actions',
            'title' => 'Acciones rápidas',
            'description' => 'Acciones permitidas para navegar a módulos de trabajo.',
            'category' => 'system',
            'type' => 'quick_links',
            'icon' => 'bi bi-lightning',
            'color' => 'info',
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
        $actions = (new \Service_Core_Workspace_QuickActions())->allowed($context);
        if (empty($actions)) {
            return [
                'layout' => 'empty_state',
                'empty_icon' => 'bi bi-lightning',
                'empty_title' => 'Sin acciones rápidas disponibles.',
                'empty_message' => 'Tu perfil no tiene acciones rápidas configuradas.',
            ];
        }

        return [
            'layout' => 'action_grid',
            'groups' => $this->group_actions($actions),
        ];
    }

    protected function group_actions(array $actions)
    {
        $groups = [];
        foreach ($actions as $action) {
            $category = (string) \Arr::get($action, 'category', 'system');
            if (!isset($groups[$category])) {
                $groups[$category] = [
                    'title' => $this->category_label($category),
                    'items' => [],
                ];
            }

            $groups[$category]['items'][] = [
                'label' => (string) \Arr::get($action, 'title', ''),
                'url' => \Uri::create((string) \Arr::get($action, 'route', '#')),
                'icon' => (string) \Arr::get($action, 'icon', 'bi bi-lightning'),
                'color' => (string) \Arr::get($action, 'color', 'secondary'),
                'category_label' => $this->category_label($category),
                'code' => (string) \Arr::get($action, 'code', ''),
            ];
        }

        return array_values($groups);
    }

    protected function category_label($category)
    {
        $labels = [
            'system' => 'Sistema',
            'commercial' => 'Comercial',
            'operation' => 'Operación',
            'support' => 'Soporte',
            'fiscal' => 'Fiscal',
        ];

        return isset($labels[$category]) ? $labels[$category] : ucfirst((string) $category);
    }
}
