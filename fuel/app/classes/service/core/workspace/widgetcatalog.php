<?php

/**
 * SERVICE CORE_WORKSPACE_WIDGETCATALOG
 *
 * Lista widgets disponibles del catalogo y registry.
 */
class Service_Core_Workspace_WidgetCatalog
{
    public function allowed(array $context)
    {
        $items = [];
        foreach ((new \Service_Core_Workspace_WidgetRegistry())->manifests() as $code => $manifest) {
            $permission = (string) \Arr::get($manifest, 'permission_key', 'workspace.access[view]');
            if (empty($context['is_super_admin']) && !\Auth::has_access($permission)) {
                continue;
            }

            $items[] = $manifest;
        }

        return $items;
    }

    public function rows()
    {
        if (!\DBUtil::table_exists('core_workspace_widget_catalog')) {
            return [];
        }

        return \DB::select()
            ->from('core_workspace_widget_catalog')
            ->where('active', '=', 1)
            ->order_by('priority', 'asc')
            ->order_by('title', 'asc')
            ->execute()
            ->as_array();
    }
}

