<?php

/**
 * HELPER CORE_AUDIT
 *
 * Centraliza el registro de auditoria funcional del ERP.
 */
class Helper_Core_Audit
{
    protected static $sensitive_keys = [
        'password',
        'password_encrypted',
        'secret',
        'secret_value',
        'webhook_secret',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'key',
        'private_key',
        'cer_path',
        'key_path',
    ];

    /**
     * LOG
     *
     * REGISTRA UN EVENTO DE AUDITORIA CON VALORES ANTERIORES Y NUEVOS
     *
     * @access  public
     * @return  Model_Core_Audit_Log|null
     */
    public static function log(array $data)
    {
        try {
            # SE OBTIENE USUARIO ACTUAL
            $user_id = 0;
            if (\Auth::check()) {
                $user_data = \Auth::get_user_id();
                $user_id = isset($user_data[1]) ? (int) $user_data[1] : 0;
            }

            # SE NORMALIZAN VALORES Y DIFERENCIAS
            $old_values = self::redact_array(isset($data['old_values']) && is_array($data['old_values']) ? $data['old_values'] : []);
            $new_values = self::redact_array(isset($data['new_values']) && is_array($data['new_values']) ? $data['new_values'] : []);
            $changed_fields = isset($data['changed_fields']) && is_array($data['changed_fields'])
                ? $data['changed_fields']
                : self::changed_fields($old_values, $new_values);

            # SE INFIEREN DATOS DE REGISTRO
            $entity_type = isset($data['entity_type']) ? (string) $data['entity_type'] : '';
            $entity_id = isset($data['entity_id']) ? (int) $data['entity_id'] : 0;
            $table_name = isset($data['table_name']) ? (string) $data['table_name'] : self::table_from_entity($entity_type);
            $record_pk = isset($data['record_pk']) ? (string) $data['record_pk'] : ($entity_id > 0 ? (string) $entity_id : '');
            $action = isset($data['action']) ? (string) $data['action'] : '';

            # SE CREA REGISTRO DE AUDITORIA
            $log = Model_Core_Audit_Log::forge([
                'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : $user_id,
                'portal_code' => isset($data['portal_code']) ? (string) $data['portal_code'] : '',
                'backend' => isset($data['backend']) ? (string) $data['backend'] : 'admin',
                'module' => isset($data['module']) ? (string) $data['module'] : '',
                'action' => $action,
                'event_code' => isset($data['event_code']) ? (string) $data['event_code'] : self::event_code($data, $action),
                'business_event' => isset($data['business_event']) ? (string) $data['business_event'] : self::business_event($data, $action),
                'operation' => isset($data['operation']) ? (string) $data['operation'] : self::operation_from_action($action),
                'table_name' => $table_name,
                'record_pk' => $record_pk,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'summary' => isset($data['summary']) ? (string) $data['summary'] : '',
                'old_values_json' => !empty($old_values) ? json_encode($old_values) : null,
                'new_values_json' => !empty($new_values) ? json_encode($new_values) : null,
                'changed_fields_json' => !empty($changed_fields) ? json_encode(array_values($changed_fields)) : null,
                'metadata_json' => !empty($data['metadata']) ? json_encode(self::redact_array($data['metadata'])) : null,
                'route' => isset($data['route']) ? (string) $data['route'] : (string) \Uri::string(),
                'http_method' => isset($data['http_method']) ? (string) $data['http_method'] : (string) \Input::method(),
                'request_id' => isset($data['request_id']) ? (string) $data['request_id'] : self::request_id(),
                'session_id' => isset($data['session_id']) ? (string) $data['session_id'] : self::session_id(),
                'severity' => isset($data['severity']) ? (string) $data['severity'] : self::severity_from_action($action),
                'ip' => \Input::ip(),
                'user_agent' => substr((string) \Input::user_agent(), 0, 255),
            ]);
            $log->save();

            return $log;
        } catch (\Exception $e) {
            \Log::error('Error registrando auditoria: '.$e->getMessage());
            return null;
        }
    }

    protected static function changed_fields(array $old_values, array $new_values)
    {
        $fields = [];
        foreach (array_unique(array_merge(array_keys($old_values), array_keys($new_values))) as $field) {
            $old = array_key_exists($field, $old_values) ? $old_values[$field] : null;
            $new = array_key_exists($field, $new_values) ? $new_values[$field] : null;
            if ($old != $new) {
                $fields[] = $field;
            }
        }
        return $fields;
    }

    protected static function redact_array(array $values)
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = self::redact_array($value);
                continue;
            }

            foreach (self::$sensitive_keys as $sensitive) {
                if (stripos((string) $key, $sensitive) !== false) {
                    $values[$key] = '[REDACTED]';
                    break;
                }
            }
        }
        return $values;
    }

    protected static function table_from_entity($entity_type)
    {
        $entity_type = trim((string) $entity_type);
        if ($entity_type === '') {
            return '';
        }

        if (strpos($entity_type, 'core_') === 0) {
            return $entity_type;
        }

        return 'core_'.str_replace('-', '_', $entity_type).'s';
    }

    protected static function event_code(array $data, $action)
    {
        if (!empty($data['module']) && $action !== '') {
            return $data['module'].'.'.$action;
        }
        return $action;
    }

    protected static function business_event(array $data, $action)
    {
        if (!empty($data['business_event'])) {
            return (string) $data['business_event'];
        }
        if (!empty($data['module']) && $action !== '') {
            return $data['module'].'.'.$action;
        }
        return $action;
    }

    protected static function operation_from_action($action)
    {
        $action = strtolower((string) $action);
        if (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
            return 'delete';
        }
        if (strpos($action, 'create') !== false || strpos($action, 'insert') !== false) {
            return 'create';
        }
        if (strpos($action, 'update') !== false || strpos($action, 'edit') !== false || strpos($action, 'save') !== false) {
            return 'update';
        }
        if (strpos($action, 'login') !== false || strpos($action, 'access') !== false) {
            return 'access';
        }
        return $action !== '' ? 'event' : '';
    }

    protected static function severity_from_action($action)
    {
        $action = strtolower((string) $action);
        if (strpos($action, 'delete') !== false || strpos($action, 'cancel') !== false || strpos($action, 'blocked') !== false) {
            return 'warning';
        }
        if (strpos($action, 'error') !== false || strpos($action, 'fail') !== false) {
            return 'danger';
        }
        return 'info';
    }

    protected static function request_id()
    {
        if (!empty($_SERVER['HTTP_X_REQUEST_ID'])) {
            return substr((string) $_SERVER['HTTP_X_REQUEST_ID'], 0, 80);
        }
        return substr(sha1(uniqid('', true)), 0, 20);
    }

    protected static function session_id()
    {
        return function_exists('session_id') ? substr((string) session_id(), 0, 80) : '';
    }
}
