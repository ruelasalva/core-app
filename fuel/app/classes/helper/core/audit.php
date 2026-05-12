<?php

/**
 * HELPER CORE_AUDIT
 *
 * Centraliza el registro de auditoria funcional del ERP.
 */
class Helper_Core_Audit
{
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
            $user_id = 0;
            if (\Auth::check()) {
                $user_data = \Auth::get_user_id();
                $user_id = isset($user_data[1]) ? (int) $user_data[1] : 0;
            }

            $log = Model_Core_Audit_Log::forge([
                'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : $user_id,
                'portal_code' => isset($data['portal_code']) ? (string) $data['portal_code'] : '',
                'backend' => isset($data['backend']) ? (string) $data['backend'] : 'admin',
                'module' => isset($data['module']) ? (string) $data['module'] : '',
                'action' => isset($data['action']) ? (string) $data['action'] : '',
                'entity_type' => isset($data['entity_type']) ? (string) $data['entity_type'] : '',
                'entity_id' => isset($data['entity_id']) ? (int) $data['entity_id'] : 0,
                'summary' => isset($data['summary']) ? (string) $data['summary'] : '',
                'old_values_json' => !empty($data['old_values']) ? json_encode($data['old_values']) : null,
                'new_values_json' => !empty($data['new_values']) ? json_encode($data['new_values']) : null,
                'metadata_json' => !empty($data['metadata']) ? json_encode($data['metadata']) : null,
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
}
