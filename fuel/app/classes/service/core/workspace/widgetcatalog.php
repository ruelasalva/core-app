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
        $catalog_status = $this->catalog_status_by_code();
        foreach ((new \Service_Core_Workspace_WidgetRegistry())->manifests() as $code => $manifest) {
            if (isset($catalog_status[$code]) && !$catalog_status[$code]) {
                continue;
            }

            $permission = (string) \Arr::get($manifest, 'permission_key', 'workspace.access[view]');
            if (empty($context['is_super_admin']) && !\Auth::has_access($permission)) {
                continue;
            }

            $items[] = $manifest;
        }

        return $items;
    }

    protected function catalog_status_by_code()
    {
        if (!\DBUtil::table_exists('core_workspace_widget_catalog')) {
            return [];
        }

        $status = [];
        try {
            $rows = \DB::select('code', 'active', 'status')
                ->from('core_workspace_widget_catalog')
                ->execute()
                ->as_array();
        } catch (\Exception $e) {
            \Log::warning('Workspace widget catalog list status failed message='.$e->getMessage());
            return [];
        }

        foreach ($rows as $row) {
            $code = (string) \Arr::get($row, 'code', '');
            if ($code === '') {
                continue;
            }

            $status[$code] = (int) \Arr::get($row, 'active', 1) === 1
                && (string) \Arr::get($row, 'status', 'active') === 'active';
        }

        return $status;
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
