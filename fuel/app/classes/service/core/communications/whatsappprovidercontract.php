<?php

/**
 * SERVICE CORE COMMUNICATIONS WHATSAPP PROVIDER CONTRACT
 *
 * Contrato futuro para proveedores WhatsApp.
 */
abstract class Service_Core_Communications_WhatsappProviderContract extends Service_Core_Communications_ProviderContract
{
    public function get_capabilities()
    {
        return array_merge(parent::get_capabilities(), [
            'supports_attachments' => true,
            'supports_templates' => true,
            'supports_tracking' => true,
            'supports_incoming' => true,
            'supports_reply' => true,
            'supports_webhooks' => true,
            'max_attachment_size' => 10485760,
            'max_recipients' => 1,
            'supported_channels' => ['whatsapp'],
        ]);
    }
}
