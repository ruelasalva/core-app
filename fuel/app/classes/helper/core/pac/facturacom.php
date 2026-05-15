<?php

class Helper_Core_Pac_FacturaCom
{
    const DEFAULT_PLUGIN = '9d4095c8f7ed5785cb14c0e3b033eeb8252416ed';

    protected $connection;
    protected $config = [];

    public function __construct(Model_Core_Integration_Connection $connection)
    {
        $this->connection = $connection;
        $this->config = json_decode((string) $connection->config_json, true) ?: [];
    }

    public function stamp(array $payload)
    {
        return $this->request('POST', '/v4/cfdi40/create', $payload);
    }

    public function cancel($cfdi_uid, $motive, $substitute_uuid = '')
    {
        return $this->request('POST', '/v4/cfdi40/'.rawurlencode($cfdi_uid).'/cancel', [
            'motivo' => $motive,
            'folioSustituto' => $substitute_uuid,
        ]);
    }

    protected function request($method, $path, array $payload)
    {
        $api_key = trim((string) $this->connection->public_key);
        $secret_key = trim((string) $this->connection->secret_value);
        if ($api_key === '' || $secret_key === '') {
            throw new \RuntimeException('Configura F-Api-Key y F-Secret-Key en Integraciones > Factura.com PAC.');
        }
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('La extension PHP cURL es requerida para conectar con Factura.com.');
        }

        $host = $this->host();
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $host.$path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'F-PLUGIN: '.$this->plugin(),
                'F-Api-Key: '.$api_key,
                'F-Secret-Key: '.$secret_key,
            ],
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException('Error conectando con Factura.com: '.$error);
        }

        $decoded = json_decode((string) $body, true);
        return [
            'http_code' => $http_code,
            'raw' => (string) $body,
            'json' => is_array($decoded) ? $decoded : null,
            'success' => $http_code >= 200 && $http_code < 300,
        ];
    }

    protected function host()
    {
        if (!empty($this->config['host'])) {
            return rtrim((string) $this->config['host'], '/');
        }
        return $this->connection->environment === 'production'
            ? 'https://api.factura.com'
            : 'https://sandbox.factura.com/api';
    }

    protected function plugin()
    {
        return trim((string) \Arr::get($this->config, 'plugin', self::DEFAULT_PLUGIN)) ?: self::DEFAULT_PLUGIN;
    }
}
