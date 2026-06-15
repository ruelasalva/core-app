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
        'quick_links' => 'Service_Core_Workspace_Widgets_System_Quicklinks',
        'notifications_placeholder' => 'Service_Core_Workspace_Widgets_System_Notificationsplaceholder',
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

