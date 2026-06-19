<?php
namespace Fuel\Tasks;

class Testemail
{
    public function run()
    {
        $provider = trim((string) \Cli::option('provider', 'disabled_default'));
        $to = trim((string) \Cli::option('to', 'test@example.com'));

        try {
            $manager = new \Service_Core_Email_Manager();
            $result = $manager->test_send(
                $provider,
                $to,
                'Prueba del Centro de Comunicaciones',
                'Este mensaje valida la cola de correo de CORE-APP.'
            );

            \Cli::write('Prueba de correo');
            \Cli::write('Proveedor: '.$provider);
            \Cli::write('Destinatario: '.$to);
            \Cli::write('Resultado: '.(!empty($result['success']) ? 'OK' : 'ERROR'));
            \Cli::write('Mensaje: '.(string) \Arr::get($result, 'message', ''));

            if (!empty($result['queue_id'])) {
                \Cli::write('Queue ID: '.$result['queue_id']);
            }
            if (!empty($result['processed'])) {
                \Cli::write('Procesamiento: '.json_encode($result['processed']));
            }
        } catch (\Exception $e) {
            \Log::error('Testemail: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }
}
