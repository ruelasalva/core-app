<?php

/**
 * SERVICE CORE_WORKSPACE_CONTEXT
 *
 * Construye contexto seguro para Workspace, widgets, quick actions y busqueda.
 */
class Service_Core_Workspace_Context
{
    public function build()
    {
        $user_id_data = \Auth::get_user_id();
        $user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;
        $group_id = 0;
        $groups = \Auth::get_groups();

        if (!empty($groups)) {
            $group_data = $groups[0][1];
            $group_id = is_object($group_data) ? (int) $group_data->id : (int) $group_data;
        }

        return [
            'user_id' => $user_id,
            'group_id' => $group_id,
            'company_id' => $this->company_id(),
            'branch_id' => 0,
            'fiscal_period' => null,
            'currency' => 'MXN',
            'locale' => 'es-MX',
            'timezone' => 'America/Mexico_City',
            'permissions' => [],
            'is_super_admin' => ($group_id === 100),
        ];
    }

    protected function company_id()
    {
        if (!\DBUtil::table_exists('core_companies')) {
            return 0;
        }

        $row = \DB::select('id')
            ->from('core_companies')
            ->where('active', '=', 1)
            ->order_by('id', 'asc')
            ->execute()
            ->current();

        return $row ? (int) $row['id'] : 0;
    }
}

