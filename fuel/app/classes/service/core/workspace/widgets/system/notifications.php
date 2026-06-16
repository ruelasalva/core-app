<?php

class Service_Core_Workspace_Widgets_System_Notifications extends \Service_Core_Workspace_Widget
{
    public static function manifest()
    {
        return [
            'code' => 'notifications',
            'title' => 'Notificaciones',
            'description' => 'Centro seguro para futuras notificaciones del Workspace.',
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
            'layout' => 'empty_state',
            'unread_count' => 0,
            'critical_count' => 0,
            'items' => [],
            'empty_icon' => 'bi bi-bell',
            'empty_title' => 'Sin notificaciones pendientes.',
            'empty_message' => 'Cuando haya avisos importantes aparecerán aquí.',
        ];
    }
}
