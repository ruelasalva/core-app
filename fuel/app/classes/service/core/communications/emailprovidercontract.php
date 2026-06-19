<?php

/**
 * SERVICE CORE COMMUNICATIONS EMAIL PROVIDER CONTRACT
 *
 * Contrato especializado para proveedores de email.
 */
abstract class Service_Core_Communications_EmailProviderContract extends Service_Core_Communications_ProviderContract
{
    public function get_capabilities()
    {
        return array_merge(parent::get_capabilities(), [
            'supports_html' => true,
            'supports_attachments' => false,
            'supports_inline_images' => false,
            'supports_templates' => true,
            'supports_queue' => true,
            'supports_reply' => true,
            'max_attachment_size' => 0,
            'max_recipients' => 1,
            'supported_channels' => ['email'],
        ]);
    }

    public function validate_configuration(array $config)
    {
        return $this->standard_response([
            'success' => true,
            'message' => 'Configuracion de email aceptada.',
            'health' => $this->get_health($config),
        ]);
    }
}
