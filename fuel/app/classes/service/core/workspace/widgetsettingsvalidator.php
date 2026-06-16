<?php

/**
 * SERVICE CORE_WORKSPACE_WIDGETSETTINGSVALIDATOR
 *
 * Valida settings de widgets contra su manifest sin permitir elevacion de permisos.
 */
class Service_Core_Workspace_WidgetSettingsValidator
{
    protected $unsafe_keys = [
        'permission_key',
        'permissions',
        'is_super_admin',
        'user_id',
        'group_id',
        'company_id',
        'file_path',
        'storage_path',
        'physical_path',
        'sql',
        'query',
        'class',
        'callback',
        'callable',
    ];

    public function clean(array $manifest, array $settings)
    {
        foreach ($settings as $key => $value) {
            if ($this->is_unsafe_key($key)) {
                throw new \InvalidArgumentException('Widget setting no permitido: '.$key);
            }
        }

        $schema = isset($manifest['settings_schema']) && is_array($manifest['settings_schema'])
            ? $manifest['settings_schema']
            : [];

        if (empty($schema)) {
            return [];
        }

        $clean = [];
        foreach ($schema as $key => $definition) {
            if ($this->is_unsafe_key($key)) {
                throw new \InvalidArgumentException('Widget settings_schema contiene una llave no permitida: '.$key);
            }

            if (!is_array($definition)) {
                $definition = ['type' => 'string'];
            }

            $type = isset($definition['type']) ? (string) $definition['type'] : 'string';
            $default = array_key_exists('default', $definition) ? $definition['default'] : null;
            $value = array_key_exists($key, $settings) ? $settings[$key] : $default;

            if ($value === null && !empty($definition['required'])) {
                throw new \InvalidArgumentException('Widget setting requerido faltante: '.$key);
            }

            $clean[$key] = $this->cast_value($value, $type, $key);
        }

        return $clean;
    }

    protected function cast_value($value, $type, $key)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;

            case 'float':
            case 'number':
                return (float) $value;

            case 'bool':
            case 'boolean':
                return (bool) $value;

            case 'array':
                return is_array($value) ? $value : [];

            case 'string':
            default:
                if (is_array($value) || is_object($value)) {
                    throw new \InvalidArgumentException('Widget setting invalido: '.$key);
                }

                return trim((string) $value);
        }
    }

    protected function is_unsafe_key($key)
    {
        $key = strtolower(trim((string) $key));
        return in_array($key, $this->unsafe_keys, true);
    }
}
