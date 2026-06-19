<?php

/**
 * SERVICE CORE COMMUNICATIONS PROVIDER CONTRACT
 *
 * Contrato base para proveedores de comunicaciones CAP.
 * No contiene integraciones externas ni expone secretos.
 */
abstract class Service_Core_Communications_ProviderContract
{
    protected $provider_code = '';
    protected $transport = '';

    public function __construct($provider_code = '', $transport = '')
    {
        $this->provider_code = (string) $provider_code;
        $this->transport = (string) $transport;
    }

    /**
     * SEND
     *
     * Envia o simula un mensaje segun el proveedor concreto.
     *
     * @param   array  $message
     * @param   array  $options
     * @return  array
     */
    abstract public function send(array $message, array $options = []);

    /**
     * VALIDATE CONFIGURATION
     *
     * Valida configuracion del proveedor sin exponer secretos.
     *
     * @param   array  $config
     * @return  array
     */
    public function validate_configuration(array $config)
    {
        return $this->standard_response([
            'success' => true,
            'message' => 'Configuracion aceptada por contrato base.',
            'health' => $this->get_health($config),
        ]);
    }

    /**
     * TEST CONNECTION
     *
     * Prueba segura de conectividad. Por defecto no conecta.
     *
     * @param   array  $config
     * @return  array
     */
    public function test_connection(array $config)
    {
        return $this->standard_response([
            'success' => false,
            'message' => 'Prueba de conexion no implementada para este proveedor.',
            'health' => $this->get_health($config),
            'errors' => ['provider_test_not_implemented'],
        ]);
    }

    /**
     * SUPPORTS CHANNEL
     *
     * @param   string  $channel_code
     * @return  bool
     */
    public function supports_channel($channel_code)
    {
        return in_array((string) $channel_code, (array) \Arr::get($this->get_capabilities(), 'supported_channels', []), true);
    }

    /**
     * GET CAPABILITIES
     *
     * @return  array
     */
    public function get_capabilities()
    {
        return [
            'supports_html' => false,
            'supports_attachments' => false,
            'supports_inline_images' => false,
            'supports_tracking' => false,
            'supports_templates' => false,
            'supports_queue' => false,
            'supports_bulk' => false,
            'supports_incoming' => false,
            'supports_reply' => false,
            'supports_webhooks' => false,
            'max_attachment_size' => 0,
            'max_recipients' => 1,
            'supported_channels' => [],
        ];
    }

    /**
     * GET HEALTH
     *
     * @param   array  $config
     * @return  array
     */
    public function get_health(array $config = [])
    {
        return [
            'status' => 'unknown',
            'healthy' => false,
            'simulation' => !empty($config['simulation_mode']),
            'last_test' => (string) \Arr::get($config, 'last_test_at', ''),
            'last_error' => '',
            'version' => 'contract-1',
            'transport' => $this->transport(),
            'provider_code' => $this->provider_code($config),
        ];
    }

    /**
     * STANDARD RESPONSE
     *
     * Normaliza respuestas de providers.
     *
     * @param   array  $overrides
     * @return  array
     */
    protected function standard_response(array $overrides = [])
    {
        $response = [
            'success' => false,
            'message' => '',
            'provider_code' => $this->provider_code,
            'transport' => $this->transport,
            'provider_message_id' => '',
            'capabilities' => $this->get_capabilities(),
            'health' => $this->get_health(),
            'errors' => [],
        ];

        return array_merge($response, $overrides);
    }

    protected function unsupported_response($message)
    {
        return $this->standard_response([
            'success' => false,
            'message' => (string) $message,
            'errors' => ['unsupported_provider'],
            'health' => array_merge($this->get_health(), [
                'status' => 'unsupported',
                'healthy' => false,
            ]),
        ]);
    }

    protected function provider_code(array $config = [])
    {
        return $this->provider_code !== '' ? $this->provider_code : (string) \Arr::get($config, 'code', '');
    }

    protected function transport()
    {
        return $this->transport;
    }

    protected function sanitize_config(array $config)
    {
        unset($config['password'], $config['password_encrypted'], $config['token'], $config['secret'], $config['api_key'], $config['client_secret']);
        return $config;
    }
}
