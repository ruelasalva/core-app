<?php

/**
 * SERVICE CORE COMMUNICATIONS PROVIDER FACTORY
 *
 * Resuelve proveedores CAP sin acoplar modulos ERP a clases concretas.
 */
class Service_Core_Communications_ProviderFactory
{
    protected $supported_transports = [
        'disabled',
        'php_mail',
        'smtp',
        'imap',
        'api',
        'sendgrid',
        'mailgun',
        'ses',
        'brevo',
        'whatsapp',
        'sms',
        'push',
    ];

    /**
     * MAKE
     *
     * Conserva compatibilidad con el skeleton anterior.
     *
     * @param   string  $provider_type
     * @param   array   $config
     * @return  Service_Core_Communications_ProviderContract
     */
    public function make($provider_type, array $config = [])
    {
        return $this->resolve_by_transport($provider_type, $config);
    }

    /**
     * RESOLVE BY PROVIDER CODE
     *
     * @param   string  $provider_code
     * @return  Service_Core_Communications_ProviderContract
     */
    public function resolve_by_provider_code($provider_code)
    {
        $provider_code = trim((string) $provider_code);
        if ($provider_code === '') {
            return $this->resolve_by_transport('disabled', ['code' => 'disabled_default']);
        }

        if (!class_exists('Model_Core_Communication_Provider') || !\DBUtil::table_exists('core_communication_providers')) {
            return $this->unsupported($provider_code, '', 'Tabla o modelo de proveedores no disponible.');
        }

        $provider = \Model_Core_Communication_Provider::active_by_code($provider_code);
        if (!$provider) {
            return $this->unsupported($provider_code, '', 'Proveedor no encontrado o inactivo.');
        }

        return $this->resolve_by_transport((string) $provider->transport, $this->config_from_provider($provider));
    }

    /**
     * RESOLVE BY TRANSPORT
     *
     * @param   string  $transport
     * @param   array   $config
     * @return  Service_Core_Communications_ProviderContract
     */
    public function resolve_by_transport($transport, array $config = [])
    {
        $transport = trim((string) $transport);
        $provider_code = (string) \Arr::get($config, 'code', '');

        switch ($transport) {
            case 'disabled':
            case '':
                return new \Service_Core_Email_Transports_Disabled($provider_code, 'disabled');

            case 'php_mail':
                return new \Service_Core_Email_Transports_PhpMail($provider_code, 'php_mail');

            case 'smtp':
                return new \Service_Core_Email_Transports_Smtp($provider_code, 'smtp');

            case 'imap':
            case 'api':
            case 'sendgrid':
            case 'mailgun':
            case 'ses':
            case 'brevo':
            case 'whatsapp':
            case 'sms':
            case 'push':
                return $this->unsupported($provider_code, $transport, 'Transporte '.$transport.' definido pero aun no implementado.');

            default:
                return $this->unsupported($provider_code, $transport, 'Transporte no soportado.');
        }
    }

    /**
     * RESOLVE BY CHANNEL
     *
     * @param   string  $channel_code
     * @param   array   $config
     * @return  Service_Core_Communications_ProviderContract
     */
    public function resolve_by_channel($channel_code, array $config = [])
    {
        $channel_code = trim((string) $channel_code);

        if ($channel_code === 'email') {
            $provider_code = trim((string) \Arr::get($config, 'provider_code', ''));
            if ($provider_code !== '') {
                return $this->resolve_by_provider_code($provider_code);
            }

            return $this->resolve_by_provider_code('disabled_default');
        }

        if ($channel_code === 'internal') {
            return $this->unsupported('', 'internal', 'Canal interno usa Notification Manager, no provider externo.');
        }

        return $this->resolve_by_transport($channel_code, $config);
    }

    /**
     * UNSUPPORTED RESPONSE
     *
     * Ejecuta una operacion segura contra un provider unsupported.
     *
     * @param   string  $transport
     * @param   array   $message
     * @param   array   $options
     * @return  array
     */
    public function unsupported_result($transport, array $message = [], array $options = [])
    {
        return $this->resolve_by_transport($transport, $options)->send($message, $options);
    }

    /**
     * SUPPORTED TRANSPORTS
     *
     * @return  array
     */
    public function supported_transports()
    {
        return $this->supported_transports;
    }

    /**
     * DRIVER CONTRACT
     *
     * @return  array
     */
    public function driver_contract()
    {
        return [
            'send(array $message, array $options = array())',
            'validate_configuration(array $config)',
            'test_connection(array $config)',
            'supports_channel($channel_code)',
            'get_capabilities()',
            'get_health(array $config = array())',
        ];
    }

    protected function unsupported($provider_code, $transport, $reason)
    {
        return new \Service_Core_Communications_UnsupportedProvider((string) $provider_code, (string) $transport, (string) $reason);
    }

    protected function config_from_provider(\Model_Core_Communication_Provider $provider)
    {
        return [
            'code' => (string) $provider->code,
            'transport' => (string) $provider->transport,
            'host' => (string) $provider->host,
            'port' => (int) $provider->port,
            'username' => (string) $provider->username,
            'password_encrypted' => '',
            'encryption' => (string) $provider->encryption,
            'timeout_seconds' => (int) $provider->timeout_seconds,
            'verify_tls' => (int) $provider->verify_tls,
            'simulation_mode' => (int) $provider->simulation_mode,
            'last_test_at' => (string) $provider->last_test_at,
            'last_test_status' => (string) $provider->last_test_status,
        ];
    }
}
