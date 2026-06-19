<?php

/**
 * SERVICE CORE COMMUNICATIONS IMAP PROVIDER CONTRACT
 *
 * Contrato futuro para proveedores entrantes IMAP.
 */
abstract class Service_Core_Communications_ImapProviderContract extends Service_Core_Communications_ProviderContract
{
    public function fetch_messages(array $config, array $options = [])
    {
        return $this->unsupported_response('IMAP fetch no implementado.');
    }

    public function get_capabilities()
    {
        return array_merge(parent::get_capabilities(), [
            'supports_incoming' => true,
            'supports_reply' => true,
            'supported_channels' => ['email', 'imap'],
        ]);
    }
}
