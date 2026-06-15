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
        return [
            'title' => 'Workspace CORE-APP',
            'value' => 'Listo',
            'description' => 'Base operativa preparada para futuros widgets.',
        ];
    }
}

