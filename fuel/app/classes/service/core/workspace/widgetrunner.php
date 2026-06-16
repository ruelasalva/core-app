<?php

/**
 * SERVICE CORE_WORKSPACE_WIDGETRUNNER
 *
 * Ejecuta widgets registrados validando manifiesto, settings y permisos.
 */
class Service_Core_Workspace_WidgetRunner
{
    protected $required_manifest_fields = [
        'code',
        'title',
        'description',
        'category',
        'type',
        'icon',
        'color',
        'permission_key',
        'refresh_time',
        'dependencies',
        'exportable',
        'configurable',
        'settings_schema',
        'version',
        'status',
    ];

    protected $valid_states = [
        'loading',
        'ready',
        'empty',
        'error',
        'forbidden',
        'disabled',
    ];

    protected $valid_action_types = [
        'route',
        'refresh',
        'modal',
        'export',
    ];

    public function run($code, array $context, array $filters = [], array $settings = [])
    {
        $start = microtime(true);
        $code = trim((string) $code);

        try {
            if (!$this->valid_code($code)) {
                \Log::warning('Workspace widget invalid code code='.(string) $code);
                return $this->error('Widget no disponible.', 'error', ['invalid_widget_code'], $start);
            }

            $class = (new \Service_Core_Workspace_WidgetRegistry())->resolve($code);
            if (!$class) {
                \Log::warning('Workspace widget registry miss code='.$code);
                return $this->error('Widget no disponible.', 'error', ['widget_not_registered'], $start);
            }

            /** @var Service_Core_Workspace_Widget $widget */
            $widget = new $class();
            $manifest = $class::manifest();
            $manifest_errors = $this->validate_manifest($manifest);
            if (!empty($manifest_errors)) {
                \Log::warning('Workspace widget invalid manifest code='.$code.' errors='.json_encode($manifest_errors));
                return $this->error('Widget mal configurado.', 'error', $manifest_errors, $start);
            }

            if ((string) \Arr::get($manifest, 'status', 'active') !== 'active') {
                return $this->error('Widget deshabilitado.', 'disabled', ['widget_disabled'], $start);
            }

            if (!$this->catalog_entry_is_active($code)) {
                return $this->error('Widget deshabilitado.', 'disabled', ['widget_disabled'], $start);
            }

            if (!$widget->authorize($context)) {
                return $this->error('No tienes permiso para ver este widget.', 'forbidden', ['widget_forbidden'], $start);
            }

            $settings = (new \Service_Core_Workspace_WidgetSettingsValidator())->clean($manifest, $settings);
            $payload = $widget->load($context, $filters, $settings);
            $response = $widget->response($payload, [
                'code' => $manifest['code'],
                'title' => $manifest['title'],
                'type' => $manifest['type'],
                'refresh_time' => $widget->refresh_time(),
            ], [
                'execution_ms' => $this->execution_ms($start),
            ]);

            $response['actions'] = $this->normalize_actions($widget->actions($context));

            return $this->normalize($response, $start);
        } catch (\InvalidArgumentException $e) {
            \Log::warning('Workspace widget settings error code='.$code.' message='.$e->getMessage());
            return $this->error('La configuracion del widget no es valida.', 'error', ['invalid_widget_settings'], $start);
        } catch (\Exception $e) {
            \Log::error('Workspace widget error code='.$code.' message='.$e->getMessage());
            return $this->error('No se pudo cargar el widget.', 'error', ['widget_exception'], $start);
        }
    }

    protected function normalize(array $response, $start = null)
    {
        $state = isset($response['state']) ? (string) $response['state'] : 'ready';
        if (!in_array($state, $this->valid_states, true)) {
            $state = 'error';
        }

        $payload = isset($response['payload']) && is_array($response['payload']) ? $response['payload'] : [];
        if (!isset($payload['html'])) {
            $payload['html'] = '';
        }

        $health = isset($response['health']) && is_array($response['health']) ? $response['health'] : [];
        $health = array_merge($this->default_health($start), $health);

        return [
            'success' => isset($response['success']) ? (bool) $response['success'] : true,
            'message' => isset($response['message']) ? (string) $response['message'] : '',
            'state' => $state,
            'payload' => $payload,
            'meta' => isset($response['meta']) && is_array($response['meta']) ? $response['meta'] : [],
            'health' => $health,
            'actions' => isset($response['actions']) && is_array($response['actions']) ? $response['actions'] : [],
            'errors' => isset($response['errors']) && is_array($response['errors']) ? $response['errors'] : [],
        ];
    }

    protected function error($message, $state = 'error', array $errors = [], $start = null)
    {
        return $this->normalize([
            'success' => false,
            'message' => $message,
            'state' => $state,
            'payload' => ['html' => ''],
            'meta' => [],
            'health' => $this->default_health($start),
            'actions' => [],
            'errors' => $errors,
        ], $start);
    }

    protected function validate_manifest(array $manifest)
    {
        $errors = [];
        foreach ($this->required_manifest_fields as $field) {
            if (!array_key_exists($field, $manifest)) {
                $errors[] = 'manifest_missing_'.$field;
            }
        }

        if (isset($manifest['code']) && !$this->valid_code($manifest['code'])) {
            $errors[] = 'manifest_invalid_code';
        }

        if (isset($manifest['dependencies']) && !is_array($manifest['dependencies'])) {
            $errors[] = 'manifest_invalid_dependencies';
        }

        if (isset($manifest['settings_schema']) && !is_array($manifest['settings_schema'])) {
            $errors[] = 'manifest_invalid_settings_schema';
        }

        return $errors;
    }

    protected function catalog_entry_is_active($code)
    {
        if (!\DBUtil::table_exists('core_workspace_widget_catalog')) {
            return true;
        }

        try {
            $row = \DB::select('active', 'status')
                ->from('core_workspace_widget_catalog')
                ->where('code', '=', (string) $code)
                ->limit(1)
                ->execute()
                ->current();
        } catch (\Exception $e) {
            \Log::warning('Workspace widget catalog status check failed code='.$code.' message='.$e->getMessage());
            return true;
        }

        if (!$row) {
            return true;
        }

        return (int) \Arr::get($row, 'active', 1) === 1
            && (string) \Arr::get($row, 'status', 'active') === 'active';
    }

    protected function normalize_actions(array $actions)
    {
        $clean = [];
        foreach ($actions as $action) {
            if (!is_array($action)) {
                continue;
            }

            $type = isset($action['type']) ? (string) $action['type'] : 'route';
            if (!in_array($type, $this->valid_action_types, true)) {
                continue;
            }

            $clean[] = [
                'code' => (string) \Arr::get($action, 'code', ''),
                'title' => (string) \Arr::get($action, 'title', ''),
                'icon' => (string) \Arr::get($action, 'icon', 'bi bi-arrow-right'),
                'type' => $type,
                'route' => (string) \Arr::get($action, 'route', ''),
                'permission_key' => (string) \Arr::get($action, 'permission_key', ''),
                'requires_confirmation' => (bool) \Arr::get($action, 'requires_confirmation', false),
                'color' => (string) \Arr::get($action, 'color', 'secondary'),
            ];
        }

        return $clean;
    }

    protected function default_health($start = null)
    {
        return [
            'generated_at' => date('c'),
            'cache_until' => null,
            'execution_ms' => $this->execution_ms($start),
            'cache_hit' => false,
            'stale' => false,
            'warning' => '',
        ];
    }

    protected function execution_ms($start)
    {
        if (!$start) {
            return 0;
        }

        return round((microtime(true) - $start) * 1000, 2);
    }

    protected function valid_code($code)
    {
        return (bool) preg_match('/^[a-z0-9_\\-]+$/', (string) $code);
    }
}
