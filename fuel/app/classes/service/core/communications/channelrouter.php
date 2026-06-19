<?php

/**
 * SERVICE CORE COMMUNICATIONS CHANNEL ROUTER
 *
 * Contrato futuro para decidir canales y proveedores segun evento, regla,
 * destinatario y capacidades disponibles.
 */
class Service_Core_Communications_ChannelRouter
{
    /**
     * ROUTE
     *
     * Contrato para calcular rutas de comunicacion sin enviarlas.
     *
     * @param   string  $event_code
     * @param   array   $payload
     * @param   array   $context
     * @return  array
     */
    public function route($event_code, array $payload = [], array $context = [])
    {
        return [
            'success' => false,
            'message' => 'Channel Router pendiente de implementacion.',
            'data' => [
                'event_code' => (string) $event_code,
                'payload' => $payload,
                'context' => $context,
                'routes' => [],
            ],
            'errors' => [],
        ];
    }

    /**
     * SUPPORTED CHANNELS
     *
     * Canales futuros reconocidos por el CAP.
     *
     * @return  array
     */
    public function supported_channels()
    {
        return ['internal', 'email', 'whatsapp', 'sms', 'push', 'webhook'];
    }

    /**
     * TODO:
     * - Aplicar reglas de canal por evento.
     * - Respetar preferencias de destinatarios.
     * - Delegar resolucion de proveedores a ProviderFactory.
     */
}
