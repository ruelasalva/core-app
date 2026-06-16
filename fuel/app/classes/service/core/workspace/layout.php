<?php

/**
 * SERVICE CORE_WORKSPACE_LAYOUT
 *
 * Persistencia basica de layouts personales del Workspace.
 */
class Service_Core_Workspace_Layout
{
    public function save_user_layout($user_id, array $widgets)
    {
        if (!$this->schema_ready()) {
            return ['success' => false, 'message' => 'Las tablas del Workspace no estan disponibles.', 'errors' => ['schema_missing']];
        }

        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return ['success' => false, 'message' => 'Usuario invalido.', 'errors' => ['invalid_user']];
        }

        $widgets = $this->normalize_widgets($widgets);
        if (empty($widgets)) {
            return ['success' => false, 'message' => 'No hay widgets validos para guardar.', 'errors' => ['empty_layout']];
        }

        try {
            \DB::start_transaction();
            $layout_id = $this->user_layout_id($user_id);

            \DB::update('core_workspace_widget_instances')
                ->set(['active' => 0, 'updated_at' => time()])
                ->where('layout_id', '=', $layout_id)
                ->execute();

            foreach ($widgets as $index => $widget) {
                $widget['y'] = $index;
                $widget['x'] = 0;
                $this->upsert_instance($layout_id, $widget);
            }

            \DB::update('core_workspace_layouts')
                ->set([
                    'layout_snapshot_json' => json_encode($widgets),
                    'updated_at' => time(),
                ])
                ->where('id', '=', $layout_id)
                ->execute();

            \DB::commit_transaction();

            return [
                'success' => true,
                'message' => 'Layout guardado.',
                'data' => [
                    'layout_id' => $layout_id,
                    'widgets' => $widgets,
                ],
                'errors' => [],
            ];
        } catch (\Exception $e) {
            \DB::rollback_transaction();
            \Log::error('Workspace layout save failed user_id='.$user_id.' message='.$e->getMessage());
            return ['success' => false, 'message' => 'No se pudo guardar el layout.', 'errors' => ['save_failed']];
        }
    }

    public function reset_user_layout($user_id)
    {
        if (!$this->schema_ready()) {
            return ['success' => false, 'message' => 'Las tablas del Workspace no estan disponibles.', 'errors' => ['schema_missing']];
        }

        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return ['success' => false, 'message' => 'Usuario invalido.', 'errors' => ['invalid_user']];
        }

        try {
            \DB::update('core_workspace_layouts')
                ->set(['active' => 0, 'updated_at' => time()])
                ->where('scope_type', '=', 'user')
                ->where('scope_id', '=', $user_id)
                ->execute();

            return [
                'success' => true,
                'message' => 'Workspace restablecido.',
                'data' => [],
                'errors' => [],
            ];
        } catch (\Exception $e) {
            \Log::error('Workspace layout reset failed user_id='.$user_id.' message='.$e->getMessage());
            return ['success' => false, 'message' => 'No se pudo restablecer el Workspace.', 'errors' => ['reset_failed']];
        }
    }

    public function available_widgets($user_id, array $context, array $current_layout)
    {
        $visible = [];
        foreach ((array) \Arr::get($current_layout, 'widgets', []) as $instance) {
            $code = (string) \Arr::get($instance, 'widget_code', '');
            if ($code !== '') {
                $visible[$code] = true;
            }
        }

        $hidden = $this->hidden_user_widget_codes($user_id);
        $items = [];
        foreach ((new \Service_Core_Workspace_WidgetCatalog())->allowed($context) as $widget) {
            $code = (string) \Arr::get($widget, 'code', '');
            if ($code === '') {
                continue;
            }

            $status = 'not_added';
            if (isset($visible[$code])) {
                $status = 'visible';
            } elseif (isset($hidden[$code])) {
                $status = 'hidden';
            }

            $items[] = [
                'code' => $code,
                'title' => (string) \Arr::get($widget, 'title', $code),
                'description' => (string) \Arr::get($widget, 'description', ''),
                'category' => (string) \Arr::get($widget, 'category', 'system'),
                'type' => (string) \Arr::get($widget, 'type', 'list'),
                'icon' => (string) \Arr::get($widget, 'icon', 'bi bi-grid'),
                'color' => (string) \Arr::get($widget, 'color', 'secondary'),
                'permission_key' => (string) \Arr::get($widget, 'permission_key', 'workspace.access[view]'),
                'state' => $status,
            ];
        }

        return $items;
    }

    public function add_widget($user_id, array $context, $widget_code, array $current_layout)
    {
        if (!$this->schema_ready()) {
            return ['success' => false, 'message' => 'Las tablas del Workspace no estan disponibles.', 'errors' => ['schema_missing']];
        }

        $user_id = (int) $user_id;
        $widget_code = trim((string) $widget_code);
        if ($user_id < 1 || $widget_code === '') {
            return ['success' => false, 'message' => 'Widget invalido.', 'errors' => ['invalid_widget']];
        }

        if (!$this->can_access_widget($context, $widget_code)) {
            return ['success' => false, 'message' => 'No tienes permiso para agregar este widget.', 'errors' => ['permission_denied']];
        }

        try {
            \DB::start_transaction();
            $layout_id = $this->user_layout_id_from_current($user_id, $current_layout);

            $existing = \DB::select('id', 'active')
                ->from('core_workspace_widget_instances')
                ->where('layout_id', '=', $layout_id)
                ->where('widget_code', '=', $widget_code)
                ->execute()
                ->current();

            if ($existing && (int) $existing['active'] === 1) {
                \DB::commit_transaction();
                return ['success' => true, 'message' => 'El widget ya esta visible.', 'data' => ['layout_id' => $layout_id], 'errors' => []];
            }

            $next_y = $this->next_y($layout_id);
            $data = [
                'layout_id' => $layout_id,
                'widget_code' => $widget_code,
                'x' => 0,
                'y' => $next_y,
                'w' => 4,
                'h' => 2,
                'collapsed' => 0,
                'favorite' => 0,
                'mobile_hidden' => 0,
                'active' => 1,
                'updated_at' => time(),
            ];

            if ($existing) {
                \DB::update('core_workspace_widget_instances')->set($data)->where('id', '=', (int) $existing['id'])->execute();
            } else {
                $data['created_at'] = time();
                \DB::insert('core_workspace_widget_instances')->set($data)->execute();
            }

            \DB::update('core_workspace_layouts')->set(['updated_at' => time()])->where('id', '=', $layout_id)->execute();
            \DB::commit_transaction();

            return ['success' => true, 'message' => 'Widget agregado.', 'data' => ['layout_id' => $layout_id], 'errors' => []];
        } catch (\Exception $e) {
            \DB::rollback_transaction();
            \Log::error('Workspace add widget failed user_id='.$user_id.' widget='.$widget_code.' message='.$e->getMessage());
            return ['success' => false, 'message' => 'No se pudo agregar el widget.', 'errors' => ['add_failed']];
        }
    }

    protected function user_layout_id($user_id)
    {
        $row = \DB::select('id')
            ->from('core_workspace_layouts')
            ->where('scope_type', '=', 'user')
            ->where('scope_id', '=', (int) $user_id)
            ->where('active', '=', 1)
            ->order_by('is_default', 'desc')
            ->limit(1)
            ->execute()
            ->current();

        $data = [
            'scope_type' => 'user',
            'scope_id' => (int) $user_id,
            'name' => 'Workspace personal',
            'is_default' => 1,
            'layout_version' => 1,
            'schema_version' => 1,
            'profile_code' => 'personal',
            'preset_code' => 'personal',
            'mobile_settings_json' => json_encode(['mode' => 'single_column']),
            'active' => 1,
            'updated_at' => time(),
        ];

        if ($row) {
            \DB::update('core_workspace_layouts')->set($data)->where('id', '=', (int) $row['id'])->execute();
            return (int) $row['id'];
        }

        $data['created_at'] = time();
        list($id) = \DB::insert('core_workspace_layouts')->set($data)->execute();
        return (int) $id;
    }

    protected function user_layout_id_from_current($user_id, array $current_layout)
    {
        $row = \DB::select('id')
            ->from('core_workspace_layouts')
            ->where('scope_type', '=', 'user')
            ->where('scope_id', '=', (int) $user_id)
            ->where('active', '=', 1)
            ->order_by('is_default', 'desc')
            ->limit(1)
            ->execute()
            ->current();

        if ($row) {
            return (int) $row['id'];
        }

        $layout_id = $this->user_layout_id($user_id);
        $widgets = (array) \Arr::get($current_layout, 'widgets', []);
        foreach ($widgets as $index => $widget) {
            if (!is_array($widget)) {
                continue;
            }
            $widget['y'] = $index;
            $widget['x'] = 0;
            $widget['hidden'] = 0;
            $this->upsert_instance($layout_id, $this->normalize_widget_instance($widget));
        }

        return $layout_id;
    }

    protected function upsert_instance($layout_id, array $widget)
    {
        $row = \DB::select('id')
            ->from('core_workspace_widget_instances')
            ->where('layout_id', '=', (int) $layout_id)
            ->where('widget_code', '=', $widget['widget_code'])
            ->execute()
            ->current();

        $data = [
            'layout_id' => (int) $layout_id,
            'widget_code' => $widget['widget_code'],
            'x' => (int) $widget['x'],
            'y' => (int) $widget['y'],
            'w' => (int) $widget['w'],
            'h' => (int) $widget['h'],
            'collapsed' => (int) $widget['collapsed'],
            'favorite' => (int) $widget['favorite'],
            'mobile_hidden' => (int) $widget['mobile_hidden'],
            'active' => empty($widget['hidden']) ? 1 : 0,
            'updated_at' => time(),
        ];

        if ($row) {
            \DB::update('core_workspace_widget_instances')->set($data)->where('id', '=', (int) $row['id'])->execute();
            return;
        }

        $data['created_at'] = time();
        \DB::insert('core_workspace_widget_instances')->set($data)->execute();
    }

    protected function normalize_widgets(array $widgets)
    {
        $valid_codes = array_keys((new \Service_Core_Workspace_WidgetRegistry())->all());
        $seen = [];
        $clean = [];

        foreach ($widgets as $widget) {
            if (!is_array($widget)) {
                continue;
            }

            $code = trim((string) \Arr::get($widget, 'widget_code', \Arr::get($widget, 'code', '')));
            if ($code === '' || !in_array($code, $valid_codes, true) || isset($seen[$code])) {
                continue;
            }

            $seen[$code] = true;
            $clean[] = $this->normalize_widget_instance($widget, count($clean));
        }

        return $clean;
    }

    protected function normalize_widget_instance(array $widget, $default_y = 0)
    {
        $size = $this->size((string) \Arr::get($widget, 'size', ''));
        $w = $size ? $size['w'] : $this->clamp_int(\Arr::get($widget, 'w', 4), 1, 12);
        $h = $size ? $size['h'] : $this->clamp_int(\Arr::get($widget, 'h', 2), 1, 6);

        return [
            'widget_code' => trim((string) \Arr::get($widget, 'widget_code', \Arr::get($widget, 'code', ''))),
            'x' => $this->clamp_int(\Arr::get($widget, 'x', 0), 0, 12),
            'y' => $this->clamp_int(\Arr::get($widget, 'y', $default_y), 0, 999),
            'w' => $w,
            'h' => $h,
            'collapsed' => $this->bool_int(\Arr::get($widget, 'collapsed', 0)),
            'favorite' => $this->bool_int(\Arr::get($widget, 'favorite', 0)),
            'hidden' => $this->bool_int(\Arr::get($widget, 'hidden', 0)),
            'mobile_hidden' => $this->bool_int(\Arr::get($widget, 'mobile_hidden', 0)),
        ];
    }

    protected function hidden_user_widget_codes($user_id)
    {
        $layout_id = $this->active_user_layout_id($user_id);
        if ($layout_id < 1) {
            return [];
        }

        $rows = \DB::select('widget_code')
            ->from('core_workspace_widget_instances')
            ->where('layout_id', '=', $layout_id)
            ->where('active', '=', 0)
            ->execute()
            ->as_array();

        $codes = [];
        foreach ($rows as $row) {
            $codes[(string) $row['widget_code']] = true;
        }

        return $codes;
    }

    protected function active_user_layout_id($user_id)
    {
        if (!$this->schema_ready()) {
            return 0;
        }

        $row = \DB::select('id')
            ->from('core_workspace_layouts')
            ->where('scope_type', '=', 'user')
            ->where('scope_id', '=', (int) $user_id)
            ->where('active', '=', 1)
            ->limit(1)
            ->execute()
            ->current();

        return $row ? (int) $row['id'] : 0;
    }

    protected function next_y($layout_id)
    {
        $row = \DB::select([ \DB::expr('MAX(y)'), 'max_y' ])
            ->from('core_workspace_widget_instances')
            ->where('layout_id', '=', (int) $layout_id)
            ->execute()
            ->current();

        return $row ? ((int) $row['max_y'] + 1) : 0;
    }

    protected function can_access_widget(array $context, $widget_code)
    {
        foreach ((new \Service_Core_Workspace_WidgetCatalog())->allowed($context) as $widget) {
            if ((string) \Arr::get($widget, 'code', '') === $widget_code) {
                return true;
            }
        }

        return false;
    }

    protected function size($size)
    {
        $sizes = [
            'small' => ['w' => 4, 'h' => 2],
            'medium' => ['w' => 6, 'h' => 2],
            'large' => ['w' => 12, 'h' => 3],
        ];

        return isset($sizes[$size]) ? $sizes[$size] : null;
    }

    protected function bool_int($value)
    {
        return in_array($value, [1, '1', true, 'true'], true) ? 1 : 0;
    }

    protected function clamp_int($value, $min, $max)
    {
        $value = (int) $value;
        return max($min, min($max, $value));
    }

    protected function schema_ready()
    {
        return \DBUtil::table_exists('core_workspace_layouts')
            && \DBUtil::table_exists('core_workspace_widget_instances');
    }
}
