<?php

/**
 * SERVICE CORE_SAT_VALIDATION
 *
 * Normaliza datos y estados de validacion CFDI contra SAT.
 *
 * @package  app
 */
class Service_Core_Sat_Validation
{
    /**
     * BUILD PRINTED EXPRESSION
     *
     * CONSTRUYE EXPRESION QR REQUERIDA POR CONSULTA CFDI SAT
     *
     * @access  public
     * @return  String
     */
    public function build_printed_expression($uuid, $emitter_rfc, $receiver_rfc, $total)
    {
        return '?re='.strtoupper(trim($receiver_rfc))
            .'&rr='.strtoupper(trim($emitter_rfc))
            .'&tt='.(string) $total
            .'&id='.strtoupper(trim($uuid));
    }

    /**
     * NORMALIZE STATUS
     *
     * NORMALIZA EL ESTADO DEVUELTO POR SAT O METADATA
     *
     * @access  public
     * @return  Array
     */
    public function normalize_status($status, $code = '', $message = '')
    {
        # SE NORMALIZA TEXTO
        $raw = strtolower(trim((string) $status));
        $normalized = 'unknown';

        if ($raw === 'vigente') {
            $normalized = 'vigente';
        } elseif ($raw === 'cancelado') {
            $normalized = 'cancelado';
        } elseif (strpos($raw, 'no encontrado') !== false) {
            $normalized = 'not_found';
        }

        return [
            'status' => $normalized,
            'code' => trim((string) $code),
            'message' => trim((string) $message),
        ];
    }

    /**
     * APPLY STATUS
     *
     * APLICA RESULTADO DE VALIDACION A UN CFDI
     *
     * @access  public
     * @return  Model_Core_Sat_Cfdi
     */
    public function apply_status(Model_Core_Sat_Cfdi $cfdi, array $result)
    {
        # SE GUARDAN VALORES ANTERIORES
        $old = $cfdi->to_array();

        # SE APLICA ESTADO
        $cfdi->sat_status = \Arr::get($result, 'status', 'unknown');
        $cfdi->sat_status_code = \Arr::get($result, 'code', '');
        $cfdi->sat_status_message = \Arr::get($result, 'message', '');
        $cfdi->last_validated_at = time();
        if ($cfdi->sat_status === 'cancelado' && (int) $cfdi->cancelled_at === 0) {
            $cfdi->cancelled_at = time();
        }
        $cfdi->save();

        # SE REGISTRA EVENTO
        Model_Core_Sat_Cfdi_Event::forge([
            'cfdi_id' => (int) $cfdi->id,
            'event_type' => 'status_validated',
            'payload_json' => json_encode($result),
        ])->save();

        # SE AUDITA CAMBIO
        Helper_Core_Audit::log([
            'module' => 'sat',
            'action' => 'validate_cfdi_status',
            'entity_type' => 'sat_cfdi',
            'entity_id' => (int) $cfdi->id,
            'summary' => 'CFDI '.$cfdi->uuid.' validado como '.$cfdi->sat_status,
            'old_values' => $old,
            'new_values' => $cfdi->to_array(),
        ]);

        return $cfdi;
    }
}
