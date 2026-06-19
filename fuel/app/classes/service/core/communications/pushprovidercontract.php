<?php

/**
 * SERVICE CORE COMMUNICATIONS PUSH PROVIDER CONTRACT
 *
 * Contrato futuro para notificaciones push.
 */
abstract class Service_Core_Communications_PushProviderContract extends Service_Core_Communications_ProviderContract
{
    public function get_capabilities()
    {
        return array_merge(parent::get_capabilities(), [
            'supports_bulk' => true,
            'supports_tracking' => true,
            'supported_channels' => ['push'],
        ]);
    }
}
