<?php

/**
 * Puente de planes de comisiones.
 *
 * Unifica planes legacy y versiones configurables publicadas para uso
 * de pantallas administrativas sin mezclar esquemas ni modificar motores.
 */
class Service_Core_Commissions_PlanBridge
{
    public function legacy_plans()
    {
        if (!\DBUtil::table_exists('core_commission_plans')) {
            return array();
        }

        $rows = \DB::select('id', 'code', 'name', 'valid_from', 'valid_until', 'active')
            ->from('core_commission_plans')
            ->where('active', '=', 1)
            ->order_by('name', 'asc')
            ->execute()
            ->as_array();

        $options = array();
        foreach ($rows as $row) {
            $options[] = array(
                'value' => 'legacy:'.(int) $row['id'],
                'label' => '[Legacy] '.(string) $row['name'],
                'source' => 'legacy',
                'plan_id' => (int) $row['id'],
                'version_id' => 0,
                'status' => 'active',
                'published_at' => 0,
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'valid_from' => (string) $row['valid_from'],
                'valid_until' => (string) $row['valid_until'],
            );
        }

        return $options;
    }

    public function published_config_plans()
    {
        if (!$this->config_tables_ready()) {
            return array();
        }

        $rows = \DB::select(
                array('p.id', 'plan_id'),
                array('p.code', 'plan_code'),
                array('p.name', 'plan_name'),
                array('v.id', 'version_id'),
                array('v.version_number', 'version_number'),
                array('v.code', 'version_code'),
                array('v.name', 'version_name'),
                array('v.status', 'status'),
                array('v.valid_from', 'valid_from'),
                array('v.valid_until', 'valid_until'),
                array('v.published_at', 'published_at')
            )
            ->from(array('core_commission_config_versions', 'v'))
            ->join(array('core_commission_config_commercial_plans', 'p'), 'inner')
                ->on('v.commercial_plan_id', '=', 'p.id')
            ->where('v.status', '=', 'published')
            ->where('v.active', '=', 1)
            ->where('p.active', '=', 1)
            ->order_by('p.name', 'asc')
            ->order_by('v.version_number', 'desc')
            ->execute()
            ->as_array();

        $options = array();
        foreach ($rows as $row) {
            $options[] = array(
                'value' => 'config:'.(int) $row['version_id'],
                'label' => '[Configurable] '.(string) $row['plan_name'].' - v'.(int) $row['version_number'].' publicada',
                'source' => 'config',
                'plan_id' => (int) $row['plan_id'],
                'version_id' => (int) $row['version_id'],
                'status' => (string) $row['status'],
                'published_at' => (int) $row['published_at'],
                'code' => (string) $row['plan_code'],
                'name' => (string) $row['plan_name'],
                'version_code' => (string) $row['version_code'],
                'version_name' => (string) $row['version_name'],
                'version_number' => (int) $row['version_number'],
                'valid_from' => (string) $row['valid_from'],
                'valid_until' => (string) $row['valid_until'],
            );
        }

        return $options;
    }

    public function unified_options()
    {
        return array_merge($this->legacy_plans(), $this->published_config_plans());
    }

    public function grouped_options()
    {
        return array(
            'legacy' => $this->legacy_plans(),
            'config' => $this->published_config_plans(),
        );
    }

    public function legacy_select_options()
    {
        $options = array();
        foreach ($this->legacy_plans() as $plan) {
            $options[] = array(
                'value' => (int) $plan['plan_id'],
                'label' => (string) $plan['name'],
            );
        }
        return $options;
    }

    protected function config_tables_ready()
    {
        return \DBUtil::table_exists('core_commission_config_commercial_plans')
            && \DBUtil::table_exists('core_commission_config_versions');
    }
}
