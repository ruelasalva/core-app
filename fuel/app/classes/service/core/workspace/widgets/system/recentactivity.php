<?php

class Service_Core_Workspace_Widgets_System_Recentactivity extends \Service_Core_Workspace_Widget
{
    public static function manifest()
    {
        return [
            'code' => 'recent_activity',
            'title' => 'Actividad reciente',
            'description' => 'Resumen seguro de actividad reciente del sistema.',
            'category' => 'system',
            'type' => 'timeline',
            'icon' => 'bi bi-clock-history',
            'color' => 'secondary',
            'permission_key' => 'workspace.access[view]',
            'refresh_time' => 300,
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
        $items = $this->safe_activity_items();
        if (empty($items)) {
            return [
                'layout' => 'timeline',
                'items' => [],
                'empty_icon' => 'bi bi-clock-history',
                'empty_title' => 'Aún no hay actividad reciente disponible.',
                'empty_message' => 'La actividad aparecerá cuando existan eventos seguros para mostrar.',
            ];
        }

        return [
            'layout' => 'timeline',
            'items' => $items,
        ];
    }

    protected function safe_activity_items()
    {
        if (!\DBUtil::table_exists('core_workspace_activity')) {
            return [];
        }

        if (
            !\DBUtil::field_exists('core_workspace_activity', ['title']) ||
            !\DBUtil::field_exists('core_workspace_activity', ['created_at'])
        ) {
            return [];
        }

        try {
            $rows = \DB::select('title', 'created_at')
                ->from('core_workspace_activity')
                ->order_by('created_at', 'desc')
                ->limit(5)
                ->execute()
                ->as_array();
        } catch (\Exception $e) {
            \Log::warning('Workspace recent_activity safe query failed message='.$e->getMessage());
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $title = trim((string) \Arr::get($row, 'title', ''));
            if ($title === '') {
                continue;
            }

            $items[] = [
                'label' => $title,
                'url' => '#',
                'icon' => 'bi bi-dot',
                'created_at' => (int) \Arr::get($row, 'created_at', 0),
            ];
        }

        return $items;
    }
}
