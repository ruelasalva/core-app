<?php

/**
 * SERVICE CORE COMMUNICATIONS UNSUPPORTED PROVIDER
 *
 * Provider seguro para transportes definidos pero aun no implementados.
 */
class Service_Core_Communications_UnsupportedProvider extends Service_Core_Communications_ProviderContract
{
    protected $reason = '';

    public function __construct($provider_code = '', $transport = '', $reason = '')
    {
        parent::__construct($provider_code, $transport);
        $this->reason = (string) $reason;
    }

    public function send(array $message, array $options = [])
    {
        return $this->unsupported_response($this->reason ?: 'Proveedor no soportado.');
    }

    public function validate_configuration(array $config)
    {
        return $this->unsupported_response($this->reason ?: 'Configuracion de proveedor no soportada.');
    }

    public function test_connection(array $config)
    {
        return $this->unsupported_response($this->reason ?: 'Prueba no disponible para proveedor no soportado.');
    }
}
