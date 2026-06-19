<?php
namespace Fuel\Tasks;

class Processemailqueue
{
    public function run()
    {
        $limit = (int) \Cli::option('limit', 50);
        $provider = trim((string) \Cli::option('provider', ''));
        $recover_stale = (int) \Cli::option('recover-stale', 0) === 1 ? 1 : 0;
        $stale_minutes = max(1, min(1440, (int) \Cli::option('stale-minutes', 30)));

        try {
            $processor = new \Service_Core_Email_QueueProcessor();
            $summary = $processor->process($limit, $provider, [
                'recover_stale' => $recover_stale,
                'stale_minutes' => $stale_minutes,
            ]);

            \Cli::write('Procesamiento de cola de correo');
            \Cli::write('Recuperar processing vencidos: '.($recover_stale ? 'si' : 'no'));
            \Cli::write('Umbral stale minutos: '.$stale_minutes);
            \Cli::write('Processing vencidos: '.$summary['stale_processing_count']);
            \Cli::write('Mayor edad processing vencido segundos: '.$summary['oldest_processing_age']);
            \Cli::write('Recuperables: '.$summary['recoverable_count']);
            \Cli::write('Recuperados a pending: '.$summary['recovered_pending']);
            \Cli::write('Recuperados a failed: '.$summary['recovered_failed']);
            \Cli::write('Encontrados: '.$summary['found']);
            \Cli::write('Enviados: '.$summary['sent']);
            \Cli::write('Simulados: '.$summary['simulated']);
            \Cli::write('Omitidos: '.$summary['skipped']);
            \Cli::write('Fallidos: '.$summary['failed']);

            if (!empty($summary['errors'])) {
                \Cli::write('Errores:');
                foreach ($summary['errors'] as $error) {
                    \Cli::write(' - '.$error);
                }
            }

            \Log::info('Processemailqueue ejecutado: '.json_encode($summary));
        } catch (\Exception $e) {
            \Log::error('Processemailqueue: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }
}
