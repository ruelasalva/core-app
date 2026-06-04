<?php

/**
 * SERVICE CORE_CONTRACTS_MANAGER
 *
 * Centraliza reglas operativas del modulo de contratos.
 *
 * @package  app
 */
class Service_Core_Contracts_Manager
{
    protected $allowed_transitions = [
        'draft' => ['pending_signature', 'active', 'cancelled'],
        'pending_signature' => ['active', 'cancelled'],
        'active' => ['renewal_pending', 'expired', 'terminated', 'cancelled'],
        'renewal_pending' => ['active', 'expired', 'terminated', 'cancelled'],
        'expired' => ['archived'],
        'terminated' => ['archived'],
        'cancelled' => ['archived'],
        'archived' => [],
    ];

    /**
     * NEXT CONTRACT NUMBER
     *
     * GENERA EL SIGUIENTE FOLIO INTERNO DE CONTRATO.
     *
     * @access  public
     * @return  String
     */
    public function next_contract_number()
    {
        $prefix = 'CON-'.date('Ymd').'-';
        $row = \DB::select(['contract_number', 'contract_number'])
            ->from('core_contracts')
            ->where('contract_number', 'like', $prefix.'%')
            ->order_by('contract_number', 'desc')
            ->limit(1)
            ->execute()
            ->current();

        $next = 1;
        if ($row && preg_match('/(\d+)$/', (string) $row['contract_number'], $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * IS DUPLICATE NUMBER ERROR
     *
     * DETECTA SI UNA EXCEPCION PROVIENE DE UN UNIQUE KEY DUPLICADO.
     *
     * @access  public
     * @return  Boolean
     */
    public function is_duplicate_number_error(\Exception $e)
    {
        $message = strtolower($e->getMessage());

        return strpos($message, 'duplicate') !== false
            || strpos($message, '1062') !== false
            || strpos($message, 'uidx_core_contracts_number') !== false
            || strpos($message, 'contract_number') !== false;
    }

    /**
     * VALIDATE STATUS TRANSITION
     *
     * VALIDA SI EL CAMBIO DE ESTADO ESTA PERMITIDO.
     *
     * @access  public
     * @return  Boolean
     */
    public function validate_status_transition($from, $to)
    {
        $from = $this->codeify($from ?: 'draft');
        $to = $this->codeify($to);

        if ($from === $to) {
            return true;
        }

        return isset($this->allowed_transitions[$from]) && in_array($to, $this->allowed_transitions[$from], true);
    }

    /**
     * CREATE EVENT
     *
     * REGISTRA UN EVENTO OPERATIVO DEL CONTRATO.
     *
     * @access  public
     * @return  Model_Core_Contract_Event
     */
    public function create_event($contract_id, $event_type, $old_status, $new_status, $message, array $payload = [], $created_by = 0)
    {
        $event = \Model_Core_Contract_Event::forge([
            'contract_id' => (int) $contract_id,
            'event_type' => $this->codeify($event_type ?: 'note'),
            'old_status' => $this->codeify($old_status),
            'new_status' => $this->codeify($new_status),
            'message' => trim((string) $message),
            'payload_json' => !empty($payload) ? json_encode($payload) : null,
            'created_by' => (int) $created_by,
        ]);
        $event->save();

        return $event;
    }

    /**
     * CALCULATE EXPIRATION STATUS
     *
     * CALCULA EL ESTADO DE VENCIMIENTO PARA REPORTES Y ALERTAS.
     *
     * @access  public
     * @return  String
     */
    public function calculate_expiration_status($end_date)
    {
        $end_date = trim((string) $end_date);
        if ($end_date === '') {
            return 'no_end_date';
        }

        $end = strtotime($end_date.' 23:59:59');
        if (!$end) {
            return 'invalid_date';
        }

        $days = (int) floor(($end - time()) / 86400);
        if ($days < 0) {
            return 'expired';
        }
        if ($days <= 30) {
            return 'expiring_30';
        }
        if ($days <= 60) {
            return 'expiring_60';
        }
        if ($days <= 90) {
            return 'expiring_90';
        }

        return 'ok';
    }

    protected function codeify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }
}
