<?php

abstract class Service_Core_Email_TransportInterface extends Service_Core_Communications_EmailProviderContract
{
    abstract public function send(array $message, array $settings = []);

    public function test(array $settings = [])
    {
        return $this->test_connection($settings);
    }

    public function test_connection(array $config)
    {
        return $this->standard_response([
            'success' => true,
            'message' => 'Contrato de transporte disponible.',
            'health' => $this->get_health($config),
        ]);
    }
}
