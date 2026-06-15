<?php

class Service_Core_Workspace_Widgets_System_Notificationsplaceholder extends \Service_Core_Workspace_Widget
{
    public static function manifest()
    {
        return [
            'code' => 'notifications_placeholder',
            'title' => 'Notificaciones',
            'description' => 'Placeholder seguro para futuras notificaciones.',
            'category' => 'system',
            'type' => 'notifications',
            'icon' => 'bi bi-bell',
            'color' => 'warning',
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
        return [
            'items' => [],
            'empty_message' => 'Sin notificaciones pendientes.',
        ];
    }
}

