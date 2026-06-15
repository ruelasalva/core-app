<?php

class Service_Core_Workspace_Widgets_System_Quicklinks extends \Service_Core_Workspace_Widget
{
    public static function manifest()
    {
        return [
            'code' => 'quick_links',
            'title' => 'Accesos rápidos',
            'description' => 'Placeholder de accesos rápidos del Workspace.',
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
        return [
            'items' => [
                ['label' => 'Personaliza este espacio', 'url' => '#'],
                ['label' => 'Agrega widgets en próximas fases', 'url' => '#'],
            ],
        ];
    }
}

