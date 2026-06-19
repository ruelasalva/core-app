<?php
namespace Fuel\Tasks;

class Syncimapaccounts
{
    public function run()
    {
        $limit = (int) \Cli::option('limit', 20);
        $account = trim((string) \Cli::option('account', ''));

        try {
            $manager = new \Service_Core_Communications_ImapManager();
            $summary = $manager->sync_accounts([
                'limit' => $limit,
                'account' => $account,
            ]);

            \Cli::write('Sincronizacion IMAP');
            \Cli::write('Cuenta: '.($account !== '' ? $account : 'todas activas'));
            \Cli::write('Limite por carpeta: '.max(1, min(100, $limit)));
            \Cli::write('Encontradas: '.$summary['found']);
            \Cli::write('Procesadas: '.$summary['processed_accounts']);
            \Cli::write('Almacenados: '.$summary['stored']);
            \Cli::write('Duplicados: '.$summary['duplicates']);
            \Cli::write('Omitidos: '.$summary['skipped']);
            \Cli::write('Fallidos: '.$summary['failed']);
            \Cli::write('Resultado: '.(!empty($summary['success']) ? 'OK' : 'ERROR'));

            if (!empty($summary['accounts'])) {
                foreach ($summary['accounts'] as $item) {
                    $code = isset($item['account']['code']) ? $item['account']['code'] : '-';
                    \Cli::write(' - '.$code.': '.$item['status'].' - '.$item['message']);
                }
            }

            if (!empty($summary['errors'])) {
                \Cli::write('Errores:');
                foreach (array_unique($summary['errors']) as $error) {
                    \Cli::write(' - '.$error);
                }
            }

            \Log::info('Syncimapaccounts ejecutado: '.json_encode([
                'account' => $account,
                'limit' => $limit,
                'found' => $summary['found'],
                'stored' => $summary['stored'],
                'duplicates' => $summary['duplicates'],
                'skipped' => $summary['skipped'],
                'failed' => $summary['failed'],
            ]));
        } catch (\Exception $e) {
            \Log::error('Syncimapaccounts: '.$e->getMessage());
            \Cli::write('[ERROR] No se pudo ejecutar sincronizacion IMAP.');
        }
    }
}
