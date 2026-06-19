<?php

class Service_Core_Email_Transports_Smtp extends Service_Core_Email_TransportInterface
{
    public function send(array $message, array $settings = [])
    {
        if (!empty($settings['simulation_mode'])) {
            return $this->standard_response([
                'success' => true,
                'simulated' => true,
                'provider_code' => (string) \Arr::get($settings, 'code', ''),
                'transport' => 'smtp',
                'provider_message_id' => 'smtp-simulated-'.time(),
                'response_code' => 'SIMULATED',
                'message' => 'SMTP en modo simulacion.',
                'health' => array_merge($this->get_health($settings), [
                    'status' => 'simulated',
                    'healthy' => true,
                    'simulation' => true,
                    'transport' => 'smtp',
                    'provider_code' => (string) \Arr::get($settings, 'code', ''),
                ]),
            ]);
        }

        try {
            \Package::load('email');
        } catch (\Exception $e) {
            return $this->standard_response([
                'success' => false,
                'provider_code' => (string) \Arr::get($settings, 'code', ''),
                'transport' => 'smtp',
                'response_code' => 'EMAIL_PACKAGE_MISSING',
                'message' => 'El paquete Email de FuelPHP no esta disponible.',
                'errors' => ['email_package_missing'],
            ]);
        }

        if (!class_exists('Email')) {
            return $this->standard_response([
                'success' => false,
                'provider_code' => (string) \Arr::get($settings, 'code', ''),
                'transport' => 'smtp',
                'response_code' => 'EMAIL_CLASS_MISSING',
                'message' => 'La clase Email de FuelPHP no esta disponible.',
                'errors' => ['email_class_missing'],
            ]);
        }

        try {
            $email = \Email::forge([
                'driver' => 'smtp',
                'smtp' => [
                    'host' => (string) \Arr::get($settings, 'host', ''),
                    'port' => (int) \Arr::get($settings, 'port', 587),
                    'username' => (string) \Arr::get($settings, 'username', ''),
                    'password' => (string) \Arr::get($settings, 'password', ''),
                    'timeout' => (int) \Arr::get($settings, 'timeout_seconds', 20),
                ],
            ]);

            $from = (string) \Arr::get($message, 'from_email', '');
            if ($from !== '') {
                $email->from($from, (string) \Arr::get($message, 'from_name', ''));
            }
            $email->to((string) \Arr::get($message, 'to_email', ''), (string) \Arr::get($message, 'to_name', ''));
            foreach ((array) \Arr::get($message, 'cc', []) as $recipient) {
                $recipient = (array) $recipient;
                $cc_email = (string) \Arr::get($recipient, 'email', '');
                if (filter_var($cc_email, FILTER_VALIDATE_EMAIL)) {
                    $email->cc($cc_email, (string) \Arr::get($recipient, 'name', ''));
                }
            }
            foreach ((array) \Arr::get($message, 'bcc', []) as $recipient) {
                $recipient = (array) $recipient;
                $bcc_email = (string) \Arr::get($recipient, 'email', '');
                if (filter_var($bcc_email, FILTER_VALIDATE_EMAIL)) {
                    $email->bcc($bcc_email, (string) \Arr::get($recipient, 'name', ''));
                }
            }
            $email->subject((string) \Arr::get($message, 'subject', ''));
            $email->html_body((string) \Arr::get($message, 'body', ''));
            $email->send();

            return $this->standard_response([
                'success' => true,
                'provider_code' => (string) \Arr::get($settings, 'code', ''),
                'transport' => 'smtp',
                'provider_message_id' => '',
                'response_code' => 'OK',
                'message' => 'Correo enviado por SMTP.',
                'health' => array_merge($this->get_health($settings), [
                    'status' => 'ok',
                    'healthy' => true,
                    'transport' => 'smtp',
                    'provider_code' => (string) \Arr::get($settings, 'code', ''),
                ]),
            ]);
        } catch (\Exception $e) {
            return $this->standard_response([
                'success' => false,
                'provider_code' => (string) \Arr::get($settings, 'code', ''),
                'transport' => 'smtp',
                'response_code' => 'SMTP_ERROR',
                'message' => $e->getMessage(),
                'health' => array_merge($this->get_health($settings), [
                    'status' => 'error',
                    'healthy' => false,
                    'last_error' => 'SMTP_ERROR',
                    'transport' => 'smtp',
                    'provider_code' => (string) \Arr::get($settings, 'code', ''),
                ]),
                'errors' => ['smtp_error'],
            ]);
        }
    }
}
