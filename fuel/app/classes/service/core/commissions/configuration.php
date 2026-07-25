<?php

class Service_Core_Commissions_Configuration
{
    protected $validator;

    public function __construct()
    {
        $this->validator = new \Service_Core_Commissions_ConfigValidator();
    }

    public function data()
    {
        $this->assert_schema_ready();

        return array(
            'stats' => $this->stats(),
            'plans' => $this->plans(),
            'versions' => $this->versions(),
            'groups' => $this->groups(),
            'rules' => $this->rules(),
            'stages' => $this->stages(),
            'beneficiaries' => $this->beneficiaries(),
            'exclusions' => $this->exclusions(),
            'catalogs' => $this->catalogs(),
            'options' => $this->options(),
        );
    }

    public function save_plan(array $input, $user_id)
    {
        $now = time();
        $id = (int) \Arr::get($input, 'id', 0);
        $name = $this->validator->text(\Arr::get($input, 'name', ''), 180);
        if ($name === '') {
            throw new \RuntimeException('El nombre del plan comercial es obligatorio.');
        }

        $data = array(
            'code' => $this->unique_code('core_commission_config_commercial_plans', 'code', $this->validator->code(\Arr::get($input, 'code', ''), 'PLAN'), 'PLAN', $id),
            'name' => $name,
            'description' => $this->validator->long_text(\Arr::get($input, 'description', '')),
            'status' => $this->validator->status(\Arr::get($input, 'status', 'draft')),
            'owner_user_id' => $this->validator->int(\Arr::get($input, 'owner_user_id', 0)),
            'updated_by' => (int) $user_id,
            'updated_at' => $now,
            'active' => $this->validator->bool(\Arr::get($input, 'active', true)),
        );

        if ($id > 0) {
            $this->assert_plan_editable($id);
            \DB::update('core_commission_config_commercial_plans')->set($data)->where('id', '=', $id)->execute();
            $this->audit('update_plan', 'commission_config_plan', $id, $data);
            return $id;
        }

        $data['created_by'] = (int) $user_id;
        $data['created_at'] = $now;
        $result = \DB::insert('core_commission_config_commercial_plans')->set($data)->execute();
        $new_id = (int) $result[0];
        $this->audit('create_plan', 'commission_config_plan', $new_id, $data);
        return $new_id;
    }

    public function save_version(array $input, $user_id)
    {
        $now = time();
        $id = (int) \Arr::get($input, 'id', 0);
        $plan_id = (int) \Arr::get($input, 'commercial_plan_id', 0);
        if ($plan_id < 1) {
            throw new \RuntimeException('Selecciona un plan comercial.');
        }

        $name = $this->validator->text(\Arr::get($input, 'name', ''), 180);
        if ($name === '') {
            throw new \RuntimeException('El nombre de la version es obligatorio.');
        }

        $version_number = $id > 0
            ? $this->current_version_number($id)
            : $this->next_version_number($plan_id);

        $data = array(
            'commercial_plan_id' => $plan_id,
            'version_number' => max(1, $version_number),
            'code' => $this->unique_code('core_commission_config_versions', 'code', $this->validator->code(\Arr::get($input, 'code', ''), 'VER'), 'VER', $id),
            'name' => $name,
            'status' => $this->validator->status(\Arr::get($input, 'status', 'draft')),
            'valid_from' => $this->validator->date(\Arr::get($input, 'valid_from', '')),
            'valid_until' => $this->validator->date(\Arr::get($input, 'valid_until', '')),
            'notes' => $this->validator->long_text(\Arr::get($input, 'notes', '')),
            'updated_by' => (int) $user_id,
            'updated_at' => $now,
            'active' => $this->validator->bool(\Arr::get($input, 'active', true)),
        );

        if ($data['status'] === 'published') {
            throw new \RuntimeException('Publica la version con el flujo de publicacion controlada.');
        }

        if ($id > 0) {
            $this->assert_version_editable($id);
            $this->assert_unique_version_number($plan_id, $version_number, $id);
            \DB::update('core_commission_config_versions')->set($data)->where('id', '=', $id)->execute();
            $this->audit('update_version', 'commission_config_version', $id, $data);
            return $id;
        }

        $this->assert_unique_version_number($plan_id, $version_number);
        $data['created_by'] = (int) $user_id;
        $data['created_at'] = $now;
        $result = \DB::insert('core_commission_config_versions')->set($data)->execute();
        $new_id = (int) $result[0];
        $this->audit('create_version', 'commission_config_version', $new_id, $data);
        return $new_id;
    }

    public function save_group(array $input, $user_id)
    {
        $id = (int) \Arr::get($input, 'id', 0);
        $version_id = (int) \Arr::get($input, 'version_id', 0);
        $this->assert_version_editable($version_id);
        $name = $this->validator->text(\Arr::get($input, 'name', ''), 180);
        if ($name === '') {
            throw new \RuntimeException('El nombre del grupo es obligatorio.');
        }

        $data = array(
            'version_id' => $version_id,
            'code' => $this->unique_code('core_commission_config_rule_groups', 'code', $this->validator->code(\Arr::get($input, 'code', ''), 'GRUPO'), 'GRUPO', $id),
            'name' => $name,
            'description' => $this->validator->long_text(\Arr::get($input, 'description', '')),
            'priority' => (int) \Arr::get($input, 'priority', 100),
            'enabled' => $this->validator->bool(\Arr::get($input, 'enabled', true)),
            'owner_user_id' => $this->validator->int(\Arr::get($input, 'owner_user_id', 0)),
            'updated_by' => (int) $user_id,
            'active' => $this->validator->bool(\Arr::get($input, 'active', true)),
        );

        return $this->upsert('core_commission_config_rule_groups', $id, $data, $user_id, 'group');
    }

    public function save_rule(array $input, $user_id)
    {
        $id = (int) \Arr::get($input, 'id', 0);
        $version_id = (int) \Arr::get($input, 'version_id', 0);
        $this->assert_version_editable($version_id);
        $name = $this->validator->text(\Arr::get($input, 'name', ''), 180);
        if ($name === '') {
            throw new \RuntimeException('El nombre de la regla es obligatorio.');
        }

        $base = $this->validator->calculation_base(\Arr::get($input, 'calculation_base', 'subtotal'));
        $group_id = $this->validator->int(\Arr::get($input, 'rule_group_id', 0));
        $this->assert_group_belongs_to_version($group_id, $version_id);
        $data = array(
            'version_id' => $version_id,
            'rule_group_id' => $group_id,
            'code' => $this->unique_code('core_commission_config_rules', 'code', $this->validator->code(\Arr::get($input, 'code', ''), 'REGLA'), 'REGLA', $id),
            'name' => $name,
            'description' => $this->validator->long_text(\Arr::get($input, 'description', '')),
            'business_notes' => $this->validator->long_text(\Arr::get($input, 'business_notes', '')),
            'owner_user_id' => $this->validator->int(\Arr::get($input, 'owner_user_id', 0)),
            'priority' => (int) \Arr::get($input, 'priority', 100),
            'enabled' => $this->validator->bool(\Arr::get($input, 'enabled', true)),
            'accumulated' => $this->validator->bool(\Arr::get($input, 'accumulated', true)),
            'exclusive' => $this->validator->bool(\Arr::get($input, 'exclusive', false)),
            'stop_processing' => $this->validator->bool(\Arr::get($input, 'stop_processing', false)),
            'valid_from' => $this->validator->date(\Arr::get($input, 'valid_from', '')),
            'valid_until' => $this->validator->date(\Arr::get($input, 'valid_until', '')),
            'event_code' => $this->validator->event(\Arr::get($input, 'event_code', 'invoice_issued')),
            'calculation_base' => $base,
            'value_type' => $this->validator->value_type(\Arr::get($input, 'value_type', 'percent')),
            'value' => $this->validator->positive_number(\Arr::get($input, 'value', 0)),
            'margin_permission_required' => $base === 'margin' ? 1 : $this->validator->bool(\Arr::get($input, 'margin_permission_required', false)),
            'updated_by' => (int) $user_id,
            'active' => $this->validator->bool(\Arr::get($input, 'active', true)),
        );

        return $this->upsert('core_commission_config_rules', $id, $data, $user_id, 'rule');
    }

    public function save_stage(array $input, $user_id)
    {
        $id = (int) \Arr::get($input, 'id', 0);
        $rule_id = (int) \Arr::get($input, 'rule_id', 0);
        $this->assert_rule_editable($rule_id);
        $name = $this->validator->text(\Arr::get($input, 'name', ''), 180);
        if ($name === '') {
            throw new \RuntimeException('El nombre de la etapa es obligatorio.');
        }

        $release_percent = $this->validator->positive_number(\Arr::get($input, 'release_percent', 100), 100);
        $this->assert_stage_total_within_limit($rule_id, $release_percent, $id);

        $data = array(
            'rule_id' => $rule_id,
            'code' => $this->unique_code('core_commission_config_rule_stages', 'code', $this->validator->code(\Arr::get($input, 'code', ''), 'ETAPA'), 'ETAPA', $id),
            'name' => $name,
            'trigger_event' => $this->validator->event(\Arr::get($input, 'trigger_event', 'invoice_issued')),
            'release_percent' => $release_percent,
            'sort_order' => (int) \Arr::get($input, 'sort_order', 100),
            'conditions_json' => $this->safe_json(\Arr::get($input, 'conditions_json', '')),
            'enabled' => $this->validator->bool(\Arr::get($input, 'enabled', true)),
            'active' => $this->validator->bool(\Arr::get($input, 'active', true)),
        );

        return $this->upsert('core_commission_config_rule_stages', $id, $data, $user_id, 'stage');
    }

    public function save_beneficiary(array $input, $user_id)
    {
        $id = (int) \Arr::get($input, 'id', 0);
        $rule_id = (int) \Arr::get($input, 'rule_id', 0);
        $this->assert_rule_editable($rule_id);

        $data = array(
            'rule_id' => $rule_id,
            'beneficiary_type' => $this->validator->beneficiary_type(\Arr::get($input, 'beneficiary_type', 'salesperson')),
            'seller_id' => $this->validator->int(\Arr::get($input, 'seller_id', 0)),
            'user_id' => $this->validator->int(\Arr::get($input, 'user_id', 0)),
            'party_id' => $this->validator->int(\Arr::get($input, 'party_id', 0)),
            'percentage' => $this->validator->positive_number(\Arr::get($input, 'percentage', 100), 1000),
            'fixed_amount' => $this->validator->positive_number(\Arr::get($input, 'fixed_amount', 0)),
            'sort_order' => (int) \Arr::get($input, 'sort_order', 100),
            'active' => $this->validator->bool(\Arr::get($input, 'active', true)),
        );

        return $this->upsert('core_commission_config_rule_beneficiaries', $id, $data, $user_id, 'beneficiary');
    }

    public function save_exclusion(array $input, $user_id)
    {
        $id = (int) \Arr::get($input, 'id', 0);
        $rule_id = (int) \Arr::get($input, 'rule_id', 0);
        $this->assert_rule_editable($rule_id);

        $data = array(
            'rule_id' => $rule_id,
            'exclusion_type' => $this->validator->exclusion_type(\Arr::get($input, 'exclusion_type', 'product')),
            'entity_id' => $this->validator->int(\Arr::get($input, 'entity_id', 0)),
            'entity_code' => $this->validator->text(\Arr::get($input, 'entity_code', ''), 120),
            'behavior' => $this->validator->behavior(\Arr::get($input, 'behavior', 'skip_rule')),
            'active' => $this->validator->bool(\Arr::get($input, 'active', true)),
        );

        return $this->upsert('core_commission_config_rule_exclusions', $id, $data, $user_id, 'exclusion');
    }

    public function save_catalog(array $input, $user_id)
    {
        $id = (int) \Arr::get($input, 'id', 0);
        $type = strtolower($this->validator->code(\Arr::get($input, 'catalog_type', 'general'), 'general'));
        $name = $this->validator->text(\Arr::get($input, 'name', ''), 180);
        if ($name === '') {
            throw new \RuntimeException('El nombre del catalogo es obligatorio.');
        }

        $data = array(
            'catalog_type' => $type,
            'code' => $this->unique_code('core_commission_config_catalogs', 'code', $this->validator->code(\Arr::get($input, 'code', ''), 'CAT'), 'CAT', $id, array('catalog_type' => $type)),
            'name' => $name,
            'description' => $this->validator->long_text(\Arr::get($input, 'description', '')),
            'sort_order' => (int) \Arr::get($input, 'sort_order', 100),
            'active' => $this->validator->bool(\Arr::get($input, 'active', true)),
        );

        return $this->upsert('core_commission_config_catalogs', $id, $data, $user_id, 'catalog');
    }

    public function publish_version($version_id, $user_id, $reason)
    {
        $this->assert_schema_ready();
        $this->assert_version_publishable((int) $version_id);
        return (new \Service_Core_Commissions_ConfigPublisher())->publish($version_id, $user_id, $reason);
    }

    protected function upsert($table, $id, array $data, $user_id, $type)
    {
        $now = time();
        if (\DBUtil::field_exists($table, array('updated_at'))) {
            $data['updated_at'] = $now;
        }

        if ($id > 0) {
            \DB::update($table)->set($data)->where('id', '=', (int) $id)->execute();
            $this->audit('update_'.$type, 'commission_config_'.$type, $id, $data);
            return $id;
        }

        if (\DBUtil::field_exists($table, array('created_by'))) {
            $data['created_by'] = (int) $user_id;
        }
        if (\DBUtil::field_exists($table, array('created_at'))) {
            $data['created_at'] = $now;
        }
        $result = \DB::insert($table)->set($data)->execute();
        $new_id = (int) $result[0];
        $this->audit('create_'.$type, 'commission_config_'.$type, $new_id, $data);
        return $new_id;
    }

    protected function assert_plan_editable($plan_id)
    {
        $published = \DB::select('id')
            ->from('core_commission_config_versions')
            ->where('commercial_plan_id', '=', (int) $plan_id)
            ->where('status', '=', 'published')
            ->where('active', '=', 1)
            ->execute()
            ->current();

        if ($published) {
            throw new \RuntimeException('El plan tiene una version publicada. Crea una nueva version para cambios futuros.');
        }
    }

    protected function assert_rule_editable($rule_id)
    {
        $rule = \DB::select('version_id')->from('core_commission_config_rules')->where('id', '=', (int) $rule_id)->execute()->current();
        if (!$rule) {
            throw new \RuntimeException('Regla no encontrada.');
        }
        $this->assert_version_editable((int) $rule['version_id']);
    }

    protected function assert_version_editable($version_id)
    {
        if ((int) $version_id < 1) {
            throw new \RuntimeException('Selecciona una version.');
        }

        $version = \DB::select('status')
            ->from('core_commission_config_versions')
            ->where('id', '=', (int) $version_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        if (!$version) {
            throw new \RuntimeException('Version no encontrada.');
        }
        if ((string) $version['status'] === 'published') {
            throw new \RuntimeException('La version publicada es inmutable. Crea una nueva version para modificar reglas.');
        }
        if ((string) $version['status'] === 'archived') {
            throw new \RuntimeException('La version archivada no se puede modificar.');
        }
    }

    protected function next_version_number($plan_id)
    {
        $row = \DB::select(array(\DB::expr('COALESCE(MAX(version_number),0)'), 'max_version'))
            ->from('core_commission_config_versions')
            ->where('commercial_plan_id', '=', (int) $plan_id)
            ->execute()
            ->current();

        return ((int) \Arr::get($row, 'max_version', 0)) + 1;
    }

    protected function current_version_number($version_id)
    {
        $row = \DB::select('version_number')
            ->from('core_commission_config_versions')
            ->where('id', '=', (int) $version_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        if (!$row) {
            throw new \RuntimeException('Versión no encontrada.');
        }

        return max(1, (int) $row['version_number']);
    }

    protected function assert_unique_version_number($plan_id, $version_number, $ignore_id = 0)
    {
        $query = \DB::select('id')
            ->from('core_commission_config_versions')
            ->where('commercial_plan_id', '=', (int) $plan_id)
            ->where('version_number', '=', (int) $version_number)
            ->where('active', '=', 1);

        if ((int) $ignore_id > 0) {
            $query->where('id', '!=', (int) $ignore_id);
        }

        if ($query->execute()->current()) {
            throw new \RuntimeException('Ya existe una versión con ese número dentro del plan.');
        }
    }

    protected function assert_group_belongs_to_version($group_id, $version_id)
    {
        if ((int) $group_id < 1) {
            throw new \RuntimeException('Selecciona un grupo de reglas.');
        }

        $group = \DB::select('id')
            ->from('core_commission_config_rule_groups')
            ->where('id', '=', (int) $group_id)
            ->where('version_id', '=', (int) $version_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        if (!$group) {
            throw new \RuntimeException('El grupo seleccionado no pertenece a la versión.');
        }
    }

    protected function assert_stage_total_within_limit($rule_id, $release_percent, $ignore_id = 0)
    {
        $query = \DB::select(array(\DB::expr('COALESCE(SUM(release_percent),0)'), 'total'))
            ->from('core_commission_config_rule_stages')
            ->where('rule_id', '=', (int) $rule_id)
            ->where('active', '=', 1);

        if ((int) $ignore_id > 0) {
            $query->where('id', '!=', (int) $ignore_id);
        }

        $row = $query->execute()->current();
        $total = (float) \Arr::get($row, 'total', 0) + (float) $release_percent;
        if ($total > 100) {
            throw new \RuntimeException('La suma de etapas no puede superar 100%.');
        }
    }

    protected function assert_version_publishable($version_id)
    {
        $this->assert_version_editable($version_id);

        $rules = \DB::select()
            ->from('core_commission_config_rules')
            ->where('version_id', '=', (int) $version_id)
            ->where('active', '=', 1)
            ->where('enabled', '=', 1)
            ->execute()
            ->as_array();

        if (empty($rules)) {
            throw new \RuntimeException('No se puede publicar una versión sin reglas activas.');
        }

        $valid_rules = 0;
        foreach ($rules as $rule) {
            $this->assert_publishable_rule($rule);
            $valid_rules++;
        }

        if ($valid_rules < 1) {
            throw new \RuntimeException('No se encontró una regla válida para publicar.');
        }
    }

    protected function assert_publishable_rule(array $rule)
    {
        $this->assert_group_belongs_to_version((int) $rule['rule_group_id'], (int) $rule['version_id']);

        if ($this->validator->event($rule['event_code']) !== $rule['event_code']) {
            throw new \RuntimeException('La regla '.$rule['name'].' tiene un evento inválido.');
        }
        if ($this->validator->calculation_base($rule['calculation_base']) !== $rule['calculation_base']) {
            throw new \RuntimeException('La regla '.$rule['name'].' tiene una base inválida.');
        }

        $stage_total = $this->sum_rule_field('core_commission_config_rule_stages', (int) $rule['id'], 'release_percent');
        if ($stage_total <= 0 || $stage_total > 100) {
            throw new \RuntimeException('La regla '.$rule['name'].' debe tener etapas con total mayor a 0% y máximo 100%.');
        }

        $beneficiaries = \DB::select()
            ->from('core_commission_config_rule_beneficiaries')
            ->where('rule_id', '=', (int) $rule['id'])
            ->where('active', '=', 1)
            ->execute()
            ->as_array();

        if (empty($beneficiaries)) {
            throw new \RuntimeException('La regla '.$rule['name'].' debe tener al menos un beneficiario.');
        }

        foreach ($beneficiaries as $beneficiary) {
            if ($this->validator->beneficiary_type($beneficiary['beneficiary_type']) !== $beneficiary['beneficiary_type']) {
                throw new \RuntimeException('La regla '.$rule['name'].' tiene un beneficiario inválido.');
            }
            if ((float) $beneficiary['percentage'] <= 0 && (float) $beneficiary['fixed_amount'] <= 0) {
                throw new \RuntimeException('La regla '.$rule['name'].' tiene un beneficiario sin porcentaje ni monto.');
            }
        }
    }

    protected function sum_rule_field($table, $rule_id, $field)
    {
        $row = \DB::select(array(\DB::expr('COALESCE(SUM('.$field.'),0)'), 'total'))
            ->from($table)
            ->where('rule_id', '=', (int) $rule_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        return (float) \Arr::get($row, 'total', 0);
    }

    protected function safe_json($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $value : '';
    }

    protected function stats()
    {
        return array(
            'published_plans' => $this->count('core_commission_config_versions', array('status' => 'published')),
            'draft_plans' => $this->count('core_commission_config_versions', array('status' => 'draft')),
            'rules' => $this->count('core_commission_config_rules'),
            'active_rules' => $this->count('core_commission_config_rules', array('enabled' => 1)),
            'expiring_rules' => $this->expiring_rules(),
            'upcoming_changes' => $this->upcoming_changes(),
        );
    }

    protected function plans()
    {
        return \DB::select(\DB::expr('p.*'), array('u.username', 'owner_name'))
            ->from(array('core_commission_config_commercial_plans', 'p'))
            ->join(array('users', 'u'), 'left')->on('p.owner_user_id', '=', 'u.id')
            ->where('p.active', '=', 1)
            ->order_by('p.id', 'desc')
            ->execute()
            ->as_array();
    }

    protected function versions()
    {
        return \DB::select(\DB::expr('v.*'), array('p.name', 'plan_name'))
            ->from(array('core_commission_config_versions', 'v'))
            ->join(array('core_commission_config_commercial_plans', 'p'), 'left')->on('v.commercial_plan_id', '=', 'p.id')
            ->where('v.active', '=', 1)
            ->order_by('v.id', 'desc')
            ->execute()
            ->as_array();
    }

    protected function groups()
    {
        return \DB::select(\DB::expr('g.*'), array('v.name', 'version_name'))
            ->from(array('core_commission_config_rule_groups', 'g'))
            ->join(array('core_commission_config_versions', 'v'), 'left')->on('g.version_id', '=', 'v.id')
            ->where('g.active', '=', 1)
            ->order_by('g.version_id', 'desc')
            ->order_by('g.priority', 'asc')
            ->execute()
            ->as_array();
    }

    protected function rules()
    {
        return \DB::select(\DB::expr('r.*'), array('v.name', 'version_name'), array('g.name', 'group_name'), array('u.username', 'owner_name'))
            ->from(array('core_commission_config_rules', 'r'))
            ->join(array('core_commission_config_versions', 'v'), 'left')->on('r.version_id', '=', 'v.id')
            ->join(array('core_commission_config_rule_groups', 'g'), 'left')->on('r.rule_group_id', '=', 'g.id')
            ->join(array('users', 'u'), 'left')->on('r.owner_user_id', '=', 'u.id')
            ->where('r.active', '=', 1)
            ->order_by('r.version_id', 'desc')
            ->order_by('r.priority', 'asc')
            ->execute()
            ->as_array();
    }

    protected function stages()
    {
        return \DB::select(\DB::expr('s.*'), array('r.name', 'rule_name'))
            ->from(array('core_commission_config_rule_stages', 's'))
            ->join(array('core_commission_config_rules', 'r'), 'left')->on('s.rule_id', '=', 'r.id')
            ->where('s.active', '=', 1)
            ->order_by('s.rule_id', 'asc')
            ->order_by('s.sort_order', 'asc')
            ->execute()
            ->as_array();
    }

    protected function beneficiaries()
    {
        return \DB::select(\DB::expr('b.*'), array('r.name', 'rule_name'), array('s.name', 'seller_name'), array('u.username', 'user_name'), array('p.name', 'party_name'))
            ->from(array('core_commission_config_rule_beneficiaries', 'b'))
            ->join(array('core_commission_config_rules', 'r'), 'left')->on('b.rule_id', '=', 'r.id')
            ->join(array('core_sales_sellers', 's'), 'left')->on('b.seller_id', '=', 's.id')
            ->join(array('users', 'u'), 'left')->on('b.user_id', '=', 'u.id')
            ->join(array('core_parties', 'p'), 'left')->on('b.party_id', '=', 'p.id')
            ->where('b.active', '=', 1)
            ->order_by('b.rule_id', 'asc')
            ->order_by('b.sort_order', 'asc')
            ->execute()
            ->as_array();
    }

    protected function exclusions()
    {
        return \DB::select(\DB::expr('e.*'), array('r.name', 'rule_name'))
            ->from(array('core_commission_config_rule_exclusions', 'e'))
            ->join(array('core_commission_config_rules', 'r'), 'left')->on('e.rule_id', '=', 'r.id')
            ->where('e.active', '=', 1)
            ->order_by('e.rule_id', 'asc')
            ->execute()
            ->as_array();
    }

    protected function catalogs()
    {
        return \DB::select()
            ->from('core_commission_config_catalogs')
            ->where('active', '=', 1)
            ->order_by('catalog_type', 'asc')
            ->order_by('sort_order', 'asc')
            ->execute()
            ->as_array();
    }

    protected function options()
    {
        return array_merge($this->validator->options(), array(
            'users' => $this->select_options('users', 'id', 'username', false),
            'sellers' => $this->select_options('core_sales_sellers', 'id', 'name'),
            'products' => $this->select_options('core_commerce_products', 'id', 'name'),
            'brands' => $this->select_options('core_commerce_brands', 'id', 'name'),
            'categories' => $this->select_options('core_commerce_categories', 'id', 'name'),
            'customers' => $this->party_options(array('customer', 'both')),
            'suppliers' => $this->party_options(array('supplier', 'both')),
            'contracts' => $this->select_options('core_contracts', 'id', 'contract_number'),
        ));
    }

    protected function select_options($table, $value_field, $label_field, $active = true)
    {
        if (!\DBUtil::table_exists($table) || !\DBUtil::field_exists($table, array($value_field, $label_field))) {
            return array();
        }

        $query = \DB::select($value_field, $label_field)->from($table);
        if ($active && \DBUtil::field_exists($table, array('active'))) {
            $query->where('active', '=', 1);
        }

        $rows = array();
        foreach ($query->order_by($label_field, 'asc')->limit(500)->execute() as $row) {
            $rows[] = array('value' => (string) $row[$value_field], 'label' => (string) $row[$label_field]);
        }

        return $rows;
    }

    protected function party_options(array $types)
    {
        if (!\DBUtil::table_exists('core_parties')) {
            return array();
        }

        $rows = array();
        foreach (\DB::select('id', 'name', 'party_type')->from('core_parties')->where('party_type', 'in', $types)->where('active', '=', 1)->order_by('name', 'asc')->limit(500)->execute() as $row) {
            $rows[] = array('value' => (string) $row['id'], 'label' => $row['name'].' ('.$row['party_type'].')');
        }

        return $rows;
    }

    protected function count($table, array $filters = array())
    {
        if (!\DBUtil::table_exists($table)) {
            return 0;
        }
        $query = \DB::select()->from($table);
        if (\DBUtil::field_exists($table, array('active'))) {
            $query->where('active', '=', 1);
        }
        foreach ($filters as $field => $value) {
            $query->where($field, '=', $value);
        }
        return (int) $query->execute()->count();
    }

    protected function expiring_rules()
    {
        $today = date('Y-m-d');
        $soon = date('Y-m-d', strtotime('+30 days'));
        return (int) \DB::select()
            ->from('core_commission_config_rules')
            ->where('active', '=', 1)
            ->where('enabled', '=', 1)
            ->where('valid_until', '>=', $today)
            ->where('valid_until', '<=', $soon)
            ->where('valid_until', '!=', '')
            ->execute()
            ->count();
    }

    protected function upcoming_changes()
    {
        $today = date('Y-m-d');
        return (int) \DB::select()
            ->from('core_commission_config_versions')
            ->where('active', '=', 1)
            ->where('status', 'in', array('draft', 'testing'))
            ->where('valid_from', '>=', $today)
            ->where('valid_from', '!=', '')
            ->execute()
            ->count();
    }

    protected function unique_code($table, $field, $value, $prefix, $id = 0, array $extra = array())
    {
        $value = $value ?: $this->next_code($table, $field, $prefix);
        $query = \DB::select('id')->from($table)->where($field, '=', $value);
        foreach ($extra as $extra_field => $extra_value) {
            $query->where($extra_field, '=', $extra_value);
        }
        if ((int) $id > 0) {
            $query->where('id', '!=', (int) $id);
        }
        if ($query->execute()->current()) {
            return $this->next_code($table, $field, $prefix);
        }
        return $value;
    }

    protected function next_code($table, $field, $prefix)
    {
        $base = strtoupper($prefix).'-'.date('Ymd').'-';
        $row = \DB::select(array(\DB::expr('COUNT(*)'), 'total'))->from($table)->where($field, 'like', $base.'%')->execute()->current();
        return $base.str_pad(((int) \Arr::get($row, 'total', 0)) + 1, 5, '0', STR_PAD_LEFT);
    }

    protected function audit($action, $entity_type, $entity_id, array $data)
    {
        \Helper_Core_Audit::log(array(
            'module' => 'commissions',
            'action' => $action,
            'business_event' => 'commission.configuration.'.$action,
            'entity_type' => $entity_type,
            'entity_id' => (int) $entity_id,
            'summary' => 'Configuracion de comisiones: '.$action,
            'new_values' => $this->safe_audit_values($data),
        ));
    }

    protected function safe_audit_values(array $data)
    {
        foreach (array('config_snapshot_json', 'conditions_json') as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[json]';
            }
        }
        return $data;
    }

    public function assert_schema_ready()
    {
        foreach (array(
            'core_commission_config_commercial_plans',
            'core_commission_config_versions',
            'core_commission_config_rule_groups',
            'core_commission_config_rules',
            'core_commission_config_rule_stages',
            'core_commission_config_rule_beneficiaries',
            'core_commission_config_rule_exclusions',
            'core_commission_config_catalogs',
        ) as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migracion de configuracion de comisiones.');
            }
        }
    }
}
