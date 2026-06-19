<?php

/**
 * SERVICE CORE COMMUNICATIONS SMS PROVIDER CONTRACT
 *
 * Contrato futuro para proveedores SMS.
 */
abstract class Service_Core_Communications_SmsProviderContract extends Service_Core_Communications_ProviderContract
{
    public function get_capabilities()
    {
        return array_merge(parent::get_capabilities(), [
            'supports_bulk' => true,
            'supports_tracking' => true,
            'max_recipients' => 100,
            'supported_channels' => ['sms'],
        ]);
    }
}
