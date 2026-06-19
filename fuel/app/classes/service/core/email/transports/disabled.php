<?php

class Service_Core_Email_Transports_Disabled extends Service_Core_Email_TransportInterface
{
    public function send(array $message, array $settings = [])
    {
        return $this->standard_response([
            'success' => true,
            'simulated' => true,
            'provider_code' => (string) \Arr::get($settings, 'code', ''),
            'transport' => 'disabled',
            'provider_message_id' => 'simulated-'.time(),
            'response_code' => 'SIMULATED',
            'message' => 'Envio simulado. No se envio correo real.',
            'health' => array_merge($this->get_health($settings), [
                'status' => 'simulated',
                'healthy' => true,
                'simulation' => true,
                'transport' => 'disabled',
                'provider_code' => (string) \Arr::get($settings, 'code', ''),
            ]),
        ]);
    }
}
