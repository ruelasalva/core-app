<?php

class Service_Core_Email_Transports_PhpMail extends Service_Core_Email_TransportInterface
{
    public function send(array $message, array $settings = [])
    {
        if (!empty($settings['simulation_mode'])) {
            return $this->standard_response([
                'success' => true,
                'simulated' => true,
                'provider_code' => (string) \Arr::get($settings, 'code', ''),
                'transport' => 'php_mail',
                'provider_message_id' => 'phpmail-simulated-'.time(),
                'response_code' => 'SIMULATED',
                'message' => 'PHP mail en modo simulacion.',
                'health' => array_merge($this->get_health($settings), [
                    'status' => 'simulated',
                    'healthy' => true,
                    'simulation' => true,
                    'transport' => 'php_mail',
                    'provider_code' => (string) \Arr::get($settings, 'code', ''),
                ]),
            ]);
        }

        $to = (string) \Arr::get($message, 'to_email', '');
        $subject = (string) \Arr::get($message, 'subject', '');
        $body = (string) \Arr::get($message, 'body', '');
        $from = (string) \Arr::get($message, 'from_email', '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $this->standard_response([
                'success' => false,
                'provider_code' => (string) \Arr::get($settings, 'code', ''),
                'transport' => 'php_mail',
                'response_code' => 'INVALID_RECIPIENT',
                'message' => 'Destinatario invalido.',
                'errors' => ['invalid_recipient'],
            ]);
        }

        $headers = [];
        if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'From: '.((string) \Arr::get($message, 'from_name', '')).' <'.$from.'>';
        }
        $cc = $this->recipient_header((array) \Arr::get($message, 'cc', []));
        if ($cc !== '') {
            $headers[] = 'Cc: '.$cc;
        }
        $bcc = $this->recipient_header((array) \Arr::get($message, 'bcc', []));
        if ($bcc !== '') {
            $headers[] = 'Bcc: '.$bcc;
        }
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $sent = @mail($to, $subject, $body, implode("\r\n", $headers));

        return $this->standard_response([
            'success' => (bool) $sent,
            'provider_code' => (string) \Arr::get($settings, 'code', ''),
            'transport' => 'php_mail',
            'provider_message_id' => '',
            'response_code' => $sent ? 'OK' : 'PHP_MAIL_ERROR',
            'message' => $sent ? 'Correo enviado por PHP mail.' : 'PHP mail no pudo enviar el correo.',
            'health' => array_merge($this->get_health($settings), [
                'status' => $sent ? 'ok' : 'error',
                'healthy' => (bool) $sent,
                'transport' => 'php_mail',
                'provider_code' => (string) \Arr::get($settings, 'code', ''),
            ]),
            'errors' => $sent ? [] : ['php_mail_error'],
        ]);
    }

    protected function recipient_header(array $items)
    {
        $headers = [];
        foreach ($items as $item) {
            $item = (array) $item;
            $email = trim((string) \Arr::get($item, 'email', ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $name = trim(strip_tags((string) \Arr::get($item, 'name', '')));
            $headers[] = $name !== '' ? $name.' <'.$email.'>' : $email;
        }

        return implode(', ', $headers);
    }
}
