<?php

class Service_Core_Workspace_Widgets_System_Favorites extends \Service_Core_Workspace_Widget
{
    public static function manifest()
    {
        return [
            'code' => 'favorites',
            'title' => 'Favoritos',
            'description' => 'Acciones favoritas o recomendadas para el usuario.',
            'category' => 'system',
            'type' => 'quick_links',
            'icon' => 'bi bi-star',
            'color' => 'primary',
            'permission_key' => 'workspace.access[view]',
            'refresh_time' => 0,
            'dependencies' => [],
            'exportable' => false,
            'configurable' => true,
            'settings_schema' => [],
            'version' => 1,
            'status' => 'active',
        ];
    }

    public function load($context, array $filters = [], array $settings = [])
    {
        $actions = (new \Service_Core_Workspace_QuickActions())->allowed($context);
        $favorite_codes = $this->favorite_action_codes((int) \Arr::get($context, 'user_id', 0));
        $items = [];

        if (!empty($favorite_codes)) {
            foreach ($actions as $action) {
                if (in_array((string) \Arr::get($action, 'code', ''), $favorite_codes, true)) {
                    $items[] = $this->action_item($action);
                }
            }
        }

        $using_recommended = false;
        if (empty($items)) {
            $using_recommended = true;
            foreach (array_slice($actions, 0, 5) as $action) {
                $items[] = $this->action_item($action);
            }
        }

        if (empty($items)) {
            return [
                'layout' => 'empty_state',
                'empty_icon' => 'bi bi-star',
                'empty_title' => 'Aún no tienes favoritos.',
                'empty_message' => 'Puedes usar acciones recomendadas para empezar.',
            ];
        }

        return [
            'layout' => 'action_grid',
            'description' => $using_recommended ? 'Aún no tienes favoritos.' : 'Tus accesos favoritos.',
            'section_title' => $using_recommended ? 'Acciones recomendadas' : '',
            'items' => $items,
        ];
    }

    protected function favorite_action_codes($user_id)
    {
        if ($user_id < 1 || !\DBUtil::table_exists('core_workspace_user_preferences')) {
            return [];
        }

        $row = \DB::select('favorite_actions_json')
            ->from('core_workspace_user_preferences')
            ->where('user_id', '=', $user_id)
            ->execute()
            ->current();

        $raw = $row ? (string) \Arr::get($row, 'favorite_actions_json', '') : '';
        $codes = $raw !== '' ? json_decode($raw, true) : [];
        if (!is_array($codes)) {
            return [];
        }

        $clean = [];
        foreach ($codes as $code) {
            $code = trim((string) $code);
            if ($code !== '') {
                $clean[] = $code;
            }
        }

        return $clean;
    }

    protected function action_item(array $action)
    {
        $category = (string) \Arr::get($action, 'category', 'system');

        return [
            'label' => (string) \Arr::get($action, 'title', ''),
            'url' => \Uri::create((string) \Arr::get($action, 'route', '#')),
            'icon' => (string) \Arr::get($action, 'icon', 'bi bi-lightning'),
            'color' => (string) \Arr::get($action, 'color', 'secondary'),
            'category_label' => $this->category_label($category),
            'code' => (string) \Arr::get($action, 'code', ''),
        ];
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
