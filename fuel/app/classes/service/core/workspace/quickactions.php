<?php

/**
 * SERVICE CORE_WORKSPACE_QUICKACTIONS
 *
 * Lista acciones rapidas visibles por permisos.
 */
class Service_Core_Workspace_QuickActions
{
    public function allowed(array $context)
    {
        if (!\DBUtil::table_exists('core_workspace_quick_actions')) {
            return $this->fallback($context);
        }

        $rows = \DB::select()
            ->from('core_workspace_quick_actions')
            ->where('active', '=', 1)
            ->order_by('sort_order', 'asc')
            ->order_by('title', 'asc')
            ->execute()
            ->as_array();

        return $this->filter($rows, $context);
    }

    protected function filter(array $rows, array $context)
    {
        $allowed = [];
        foreach ($rows as $row) {
            $permission = (string) \Arr::get($row, 'permission_key', 'workspace.access[view]');
            if (empty($context['is_super_admin']) && !\Auth::has_access($permission)) {
                continue;
            }
            $allowed[] = $row;
        }

        return $allowed;
    }

    protected function fallback(array $context)
    {
        $rows = [
            ['code' => 'workspace_home', 'title' => 'Workspace', 'icon' => 'bi bi-grid', 'route' => 'admin/workspace', 'permission_key' => 'workspace.access[view]', 'category' => 'system', 'color' => 'primary'],
        ];

        return $this->filter($rows, $context);
    }
}

