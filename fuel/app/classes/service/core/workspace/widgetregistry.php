<?php

/**
 * SERVICE CORE_WORKSPACE_WIDGETREGISTRY
 *
 * Mapea codigos de widgets a clases PHP seguras.
 */
class Service_Core_Workspace_WidgetRegistry
{
    protected $map = [
        'welcome' => 'Service_Core_Workspace_Widgets_System_Welcome',
        'favorites' => 'Service_Core_Workspace_Widgets_System_Favorites',
        'recent_activity' => 'Service_Core_Workspace_Widgets_System_Recentactivity',
        'notifications' => 'Service_Core_Workspace_Widgets_System_Notifications',
        'quick_actions' => 'Service_Core_Workspace_Widgets_System_Quickactions',
        'quick_links' => 'Service_Core_Workspace_Widgets_System_Quicklinks',
        'notifications_placeholder' => 'Service_Core_Workspace_Widgets_System_Notificationsplaceholder',
        'pending_quotes' => 'Service_Core_Workspace_Widgets_Erp_Pendingquotes',
        'orders_pending_delivery' => 'Service_Core_Workspace_Widgets_Erp_Orderspendingdelivery',
        'low_stock' => 'Service_Core_Workspace_Widgets_Erp_Lowstock',
        'open_tickets' => 'Service_Core_Workspace_Widgets_Erp_Opentickets',
        'recent_documents' => 'Service_Core_Workspace_Widgets_Erp_Recentdocuments',
    ];

    public function all()
    {
        return $this->map;
    }

    public function resolve($code)
    {
        $code = trim((string) $code);
        if ($code === '' || !isset($this->map[$code])) {
            return null;
        }

        $class = $this->map[$code];
        if (!class_exists($class) || !is_subclass_of($class, 'Service_Core_Workspace_Widget')) {
            return null;
        }

        return $class;
    }

    public function manifests()
    {
        $manifests = [];
        foreach ($this->map as $code => $class) {
            if (class_exists($class) && is_subclass_of($class, 'Service_Core_Workspace_Widget')) {
                $manifests[$code] = $class::manifest();
            }
        }

        return $manifests;
    }
}
