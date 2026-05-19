<?php

/**
 * CONTROLADOR REVENDEDORES_CLIENTES
 *
 * Alta de clientes propios para cotizacion dentro del portal de revendedores.
 *
 * @package  app
 * @extends  Controller_Revendedores_Base
 */
class Controller_Revendedores_Clientes extends Controller_Revendedores_Base
{
    /**
     * CLIENTE CREATE
     *
     * PERMITE AL REVENDEDOR DAR DE ALTA CLIENTES PROPIOS PARA COTIZAR.
     *
     * @access  public
     * @return  Response
     */
    public function post_cliente_create()
    {
        $val = (array) \Input::json();

        try {
            $name = trim((string) \Arr::get($val, 'name', ''));
            $email = trim((string) \Arr::get($val, 'email', ''));
            if ($name === '') {
                return $this->json_response(['error' => 'Nombre del cliente obligatorio.'], 422);
            }

            $party_id = (int) $this->portal_link->party_id;
            $rfc = strtoupper(trim((string) \Arr::get($val, 'rfc', '')));
            $code_base = $rfc !== '' ? $rfc : $name;

            $customer = Model_Core_Party::forge([
                'party_type' => 'customer',
                'code' => $this->unique_customer_code($code_base),
                'name' => $name,
                'legal_name' => trim((string) \Arr::get($val, 'legal_name', $name)),
                'rfc' => $rfc,
                'email' => $email,
                'phone' => trim((string) \Arr::get($val, 'phone', '')),
                'department_id' => 0,
                'sales_user_id' => 0,
                'buyer_user_id' => 0,
                'price_list_id' => 0,
                'payment_term_id' => 0,
                'sat_cfdi_use_code' => trim((string) \Arr::get($val, 'sat_cfdi_use_code', 'G03')) ?: 'G03',
                'sat_tax_regime_code' => trim((string) \Arr::get($val, 'sat_tax_regime_code', '601')) ?: '601',
                'fiscal_operation_type_id' => 0,
                'shipping_method_id' => 0,
                'credit_limit' => 0,
                'credit_days' => 0,
                'notes' => 'Cliente capturado desde portal revendedores. Revendedor party_id='.$party_id."\n".trim((string) \Arr::get($val, 'notes', '')),
                'onboarding_status' => '',
                'onboarding_notes' => '',
                'reviewed_by' => 0,
                'reviewed_at' => 0,
                'active' => 1,
            ]);
            $customer->save();

            Helper_Core_Audit::log([
                'module' => 'portals',
                'action' => 'reseller_customer_create',
                'business_event' => 'portals.reseller_customer_create',
                'entity_type' => 'party',
                'entity_id' => (int) $customer->id,
                'table_name' => 'core_parties',
                'portal_code' => $this->portal_code,
                'backend' => 'portal',
                'summary' => 'Cliente creado por revendedor '.$party_id,
                'new_values' => $customer->to_array(),
            ]);

            return $this->json_response($this->portal_profile_payload(['status' => 'ok', 'message' => 'Cliente creado.']));
        } catch (\Exception $e) {
            \Log::error('Error creando cliente revendedor: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo crear el cliente.'], 400);
        }
    }

    public function action_cliente_create()
    {
        return $this->post_cliente_create();
    }

    protected function unique_customer_code($value)
    {
        $base = $this->codeify($value) ?: 'cliente_revendedor';
        $code = $base;
        $i = 1;
        while (\DB::select('id')->from('core_parties')->where('code', '=', $code)->execute()->current()) {
            $i++;
            $code = $base.'_'.$i;
        }
        return $code;
    }
}
