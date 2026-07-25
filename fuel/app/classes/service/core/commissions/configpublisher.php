<?php

class Service_Core_Commissions_ConfigPublisher
{
    public function publish($version_id, $user_id, $reason = '')
    {
        $version = $this->version($version_id);
        if (!$version) {
            throw new \RuntimeException('Version no encontrada.');
        }
        if ((string) $version['status'] === 'published') {
            return $version;
        }
        if ((string) $version['status'] === 'archived') {
            throw new \RuntimeException('No se puede publicar una version archivada.');
        }

        $snapshot = $this->snapshot((int) $version_id);
        $now = time();
        if (isset($snapshot['version']) && is_array($snapshot['version'])) {
            $snapshot['version']['status'] = 'published';
            $snapshot['version']['publish_reason'] = trim((string) $reason);
            $snapshot['version']['published_by'] = (int) $user_id;
            $snapshot['version']['published_at'] = $now;
        }

        \DB::update('core_commission_config_versions')
            ->set(array(
                'status' => 'published',
                'publish_reason' => trim((string) $reason),
                'config_snapshot_json' => json_encode($snapshot),
                'published_by' => (int) $user_id,
                'published_at' => $now,
                'updated_by' => (int) $user_id,
                'updated_at' => $now,
            ))
            ->where('id', '=', (int) $version_id)
            ->execute();

        \DB::update('core_commission_config_commercial_plans')
            ->set(array('status' => 'published', 'updated_by' => (int) $user_id, 'updated_at' => $now))
            ->where('id', '=', (int) $version['commercial_plan_id'])
            ->execute();

        \Helper_Core_Audit::log(array(
            'module' => 'commissions',
            'action' => 'publish_configuration_version',
            'business_event' => 'commission.plan.published',
            'entity_type' => 'commission_config_version',
            'entity_id' => (int) $version_id,
            'summary' => 'Version de configuracion de comisiones publicada',
            'new_values' => array('version_id' => (int) $version_id, 'reason' => trim((string) $reason)),
        ));

        return $this->version($version_id);
    }

    public function snapshot($version_id)
    {
        $version_id = (int) $version_id;
        return array(
            'version' => $this->version($version_id),
            'groups' => $this->rows('core_commission_config_rule_groups', 'version_id', $version_id),
            'rules' => $this->rows('core_commission_config_rules', 'version_id', $version_id),
            'stages' => $this->child_rows('core_commission_config_rule_stages', $version_id),
            'beneficiaries' => $this->child_rows('core_commission_config_rule_beneficiaries', $version_id),
            'exclusions' => $this->child_rows('core_commission_config_rule_exclusions', $version_id),
            'generated_at' => time(),
        );
    }

    protected function version($version_id)
    {
        return \DB::select()
            ->from('core_commission_config_versions')
            ->where('id', '=', (int) $version_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();
    }

    protected function rows($table, $field, $value)
    {
        if (!\DBUtil::table_exists($table)) {
            return array();
        }
        return \DB::select()->from($table)->where($field, '=', (int) $value)->where('active', '=', 1)->execute()->as_array();
    }

    protected function child_rows($table, $version_id)
    {
        if (!\DBUtil::table_exists($table)) {
            return array();
        }

        return \DB::select(\DB::expr('c.*'))
            ->from(array($table, 'c'))
            ->join(array('core_commission_config_rules', 'r'), 'inner')
            ->on('c.rule_id', '=', 'r.id')
            ->where('r.version_id', '=', (int) $version_id)
            ->where('c.active', '=', 1)
            ->execute()
            ->as_array();
    }
}
