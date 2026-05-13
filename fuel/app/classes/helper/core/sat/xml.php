<?php

/**
 * HELPER CORE_SAT_XML
 *
 * Extrae informacion fiscal de XML CFDI 3.3/4.0, conceptos, impuestos,
 * relaciones y complementos de pago sin mezclarlo con Compras o Facturacion.
 */
class Helper_Core_Sat_Xml
{
    public static function parse_file($path)
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException('No existe el XML CFDI: '.$path);
        }

        return self::parse_string((string) file_get_contents($path));
    }

    public static function parse_string($xml_content)
    {
        if (trim((string) $xml_content) === '') {
            throw new \InvalidArgumentException('El XML CFDI esta vacio.');
        }

        $previous_loader = null;
        if (PHP_VERSION_ID < 80000 && function_exists('libxml_disable_entity_loader')) {
            $previous_loader = libxml_disable_entity_loader(true);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        if (!$xml) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            if (PHP_VERSION_ID < 80000 && function_exists('libxml_disable_entity_loader')) {
                libxml_disable_entity_loader($previous_loader);
            }
            throw new \RuntimeException('No se pudo leer XML CFDI: '.json_encode($errors));
        }

        $namespaces = $xml->getNamespaces(true);
        $cfdi_ns = isset($namespaces['cfdi']) ? $namespaces['cfdi'] : 'http://www.sat.gob.mx/cfd/4';
        $xml->registerXPathNamespace('cfdi', $cfdi_ns);
        foreach (['tfd', 'pago10', 'pago20', 'cartaporte20', 'cartaporte30', 'cartaporte31'] as $prefix) {
            if (isset($namespaces[$prefix])) {
                $xml->registerXPathNamespace($prefix, $namespaces[$prefix]);
            }
        }

        $comprobante = self::first($xml->xpath('/cfdi:Comprobante'));
        if (!$comprobante) {
            throw new \RuntimeException('El XML no contiene nodo cfdi:Comprobante.');
        }

        $emisor = self::first($xml->xpath('/cfdi:Comprobante/cfdi:Emisor'));
        $receptor = self::first($xml->xpath('/cfdi:Comprobante/cfdi:Receptor'));
        $timbre = self::first($xml->xpath('//tfd:TimbreFiscalDigital'));
        $uuid = $timbre ? strtoupper(self::attr($timbre, 'UUID')) : '';
        if ($uuid === '') {
            throw new \RuntimeException('El XML no contiene UUID de timbre fiscal.');
        }

        $data = [
            'uuid' => $uuid,
            'version' => self::attr($comprobante, 'Version', 'version'),
            'serie' => self::attr($comprobante, 'Serie', 'serie'),
            'folio' => self::attr($comprobante, 'Folio', 'folio'),
            'issued_at' => self::attr($comprobante, 'Fecha', 'fecha'),
            'stamped_at' => $timbre ? self::attr($timbre, 'FechaTimbrado') : '',
            'subtotal' => self::decimal(self::attr($comprobante, 'SubTotal', 'subTotal')),
            'discount' => self::decimal(self::attr($comprobante, 'Descuento', 'descuento')),
            'total' => self::decimal(self::attr($comprobante, 'Total', 'total')),
            'currency' => self::attr($comprobante, 'Moneda', 'moneda') ?: 'MXN',
            'exchange_rate' => self::decimal(self::attr($comprobante, 'TipoCambio', 'tipoCambio'), null),
            'voucher_type' => self::attr($comprobante, 'TipoDeComprobante', 'tipoDeComprobante'),
            'payment_method' => self::attr($comprobante, 'MetodoPago', 'metodoDePago'),
            'payment_form' => self::attr($comprobante, 'FormaPago', 'formaDePago'),
            'conditions_payment' => self::attr($comprobante, 'CondicionesDePago', 'condicionesDePago'),
            'export_code' => self::attr($comprobante, 'Exportacion'),
            'place_of_issue' => self::attr($comprobante, 'LugarExpedicion'),
            'certificate_number' => self::attr($comprobante, 'NoCertificado', 'noCertificado'),
            'certificate_sat_number' => $timbre ? self::attr($timbre, 'NoCertificadoSAT') : '',
            'pac_rfc' => $timbre ? self::attr($timbre, 'RfcProvCertif') : '',
            'seal_cfdi' => $timbre ? self::attr($timbre, 'SelloCFD') : '',
            'seal_sat' => $timbre ? self::attr($timbre, 'SelloSAT') : '',
            'emitter_rfc' => $emisor ? strtoupper(self::attr($emisor, 'Rfc', 'rfc')) : '',
            'emitter_name' => $emisor ? self::attr($emisor, 'Nombre', 'nombre') : '',
            'emitter_regime' => $emisor ? self::attr($emisor, 'RegimenFiscal') : '',
            'receiver_rfc' => $receptor ? strtoupper(self::attr($receptor, 'Rfc', 'rfc')) : '',
            'receiver_name' => $receptor ? self::attr($receptor, 'Nombre', 'nombre') : '',
            'receiver_regime' => $receptor ? self::attr($receptor, 'RegimenFiscalReceptor', 'RegimenFiscal') : '',
            'receiver_zip' => $receptor ? self::attr($receptor, 'DomicilioFiscalReceptor') : '',
            'cfdi_use' => $receptor ? self::attr($receptor, 'UsoCFDI', 'usoCFDI') : '',
            'tax_transferred_total' => 0,
            'tax_withheld_total' => 0,
            'complements' => [],
            'has_payment_complement' => 0,
            'has_waybill' => 0,
            'concepts' => [],
            'relations' => [],
            'payments' => [],
        ];

        self::parse_global_taxes($xml, $data);
        self::parse_concepts($xml, $data);
        self::parse_relations($xml, $data);
        self::parse_complements($xml, $namespaces, $data);
        self::parse_payments($xml, $data);

        if (PHP_VERSION_ID < 80000 && function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader($previous_loader);
        }

        return $data;
    }

    protected static function parse_global_taxes($xml, array &$data)
    {
        $taxes = self::first($xml->xpath('/cfdi:Comprobante/cfdi:Impuestos'));
        if (!$taxes) {
            return;
        }
        $data['tax_transferred_total'] = self::decimal(self::attr($taxes, 'TotalImpuestosTrasladados'));
        $data['tax_withheld_total'] = self::decimal(self::attr($taxes, 'TotalImpuestosRetenidos'));
    }

    protected static function parse_concepts($xml, array &$data)
    {
        $i = 0;
        foreach ((array) $xml->xpath('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') as $node) {
            $concept = [
                'line_type' => 'concept',
                'line_number' => $i,
                'product_service_code' => self::attr($node, 'ClaveProdServ'),
                'identification_number' => self::attr($node, 'NoIdentificacion'),
                'unit_code' => self::attr($node, 'ClaveUnidad'),
                'unit_name' => self::attr($node, 'Unidad'),
                'description' => self::attr($node, 'Descripcion'),
                'tax_object' => self::attr($node, 'ObjetoImp'),
                'quantity' => self::decimal(self::attr($node, 'Cantidad'), null),
                'unit_value' => self::decimal(self::attr($node, 'ValorUnitario'), null),
                'discount' => self::decimal(self::attr($node, 'Descuento')),
                'amount' => self::decimal(self::attr($node, 'Importe')),
                'vat_amount' => 0,
                'vat_rate' => '',
                'vat_base' => 0,
                'ieps_amount' => 0,
                'ieps_rate' => '',
                'ieps_base' => 0,
                'retention_amount' => 0,
                'ret_vat_amount' => 0,
                'ret_vat_rate' => '',
                'ret_vat_base' => 0,
                'ret_isr_amount' => 0,
                'ret_isr_rate' => '',
                'ret_isr_base' => 0,
                'taxes' => [],
            ];

            foreach ((array) $node->xpath('cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado') as $tax) {
                $code = self::attr($tax, 'Impuesto');
                $amount = self::decimal(self::attr($tax, 'Importe'));
                $rate = self::attr($tax, 'TasaOCuota');
                $base = self::decimal(self::attr($tax, 'Base'));
                if ($code === '002') {
                    $concept['vat_amount'] += $amount;
                    $concept['vat_rate'] = $rate;
                    $concept['vat_base'] += $base;
                } elseif ($code === '003') {
                    $concept['ieps_amount'] += $amount;
                    $concept['ieps_rate'] = $rate;
                    $concept['ieps_base'] += $base;
                }
                $concept['taxes'][] = ['type' => 'transfer', 'code' => $code, 'rate' => $rate, 'base' => $base, 'amount' => $amount];
            }

            foreach ((array) $node->xpath('cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion') as $tax) {
                $code = self::attr($tax, 'Impuesto');
                $amount = self::decimal(self::attr($tax, 'Importe'));
                $rate = self::attr($tax, 'TasaOCuota');
                $base = self::decimal(self::attr($tax, 'Base'));
                $concept['retention_amount'] += $amount;
                if ($code === '002') {
                    $concept['ret_vat_amount'] += $amount;
                    $concept['ret_vat_rate'] = $rate;
                    $concept['ret_vat_base'] += $base;
                } elseif ($code === '001') {
                    $concept['ret_isr_amount'] += $amount;
                    $concept['ret_isr_rate'] = $rate;
                    $concept['ret_isr_base'] += $base;
                }
                $concept['taxes'][] = ['type' => 'withheld', 'code' => $code, 'rate' => $rate, 'base' => $base, 'amount' => $amount];
            }

            $data['concepts'][] = $concept;
            $i++;
        }
    }

    protected static function parse_relations($xml, array &$data)
    {
        $i = 0;
        foreach ((array) $xml->xpath('/cfdi:Comprobante/cfdi:CfdiRelacionados') as $group) {
            $relation_type = self::attr($group, 'TipoRelacion');
            foreach ((array) $group->xpath('cfdi:CfdiRelacionado') as $related) {
                $data['relations'][] = [
                    'line_type' => 'related',
                    'line_number' => $i,
                    'relation_type' => $relation_type,
                    'related_uuid' => strtoupper(self::attr($related, 'UUID')),
                ];
                $i++;
            }
        }
    }

    protected static function parse_complements($xml, array $namespaces, array &$data)
    {
        $complement = self::first($xml->xpath('/cfdi:Comprobante/cfdi:Complemento'));
        if (!$complement) {
            return;
        }
        foreach ($namespaces as $prefix => $uri) {
            foreach ($complement->children($uri) as $child) {
                $name = ($prefix ? $prefix.':' : '').$child->getName();
                if ($name !== 'tfd:TimbreFiscalDigital') {
                    $data['complements'][] = $name;
                }
                if (strpos($name, 'pago') === 0) {
                    $data['has_payment_complement'] = 1;
                }
                if (strpos($name, 'cartaporte') === 0) {
                    $data['has_waybill'] = 1;
                }
            }
        }
        $data['complements'] = array_values(array_unique($data['complements']));
    }

    protected static function parse_payments($xml, array &$data)
    {
        foreach (['pago20', 'pago10'] as $prefix) {
            $payments = $xml->xpath('//'.$prefix.':Pago');
            if (empty($payments)) {
                continue;
            }

            foreach ($payments as $payment) {
                $docs = $payment->xpath('.//'.$prefix.':DoctoRelacionado');
                if (empty($docs)) {
                    continue;
                }

                foreach ($docs as $doc) {
                    $item = [
                        'line_type' => 'payment_doc',
                        'payment_uuid' => strtoupper(self::attr($doc, 'IdDocumento')),
                        'payment_series' => self::attr($doc, 'Serie'),
                        'payment_folio' => self::attr($doc, 'Folio'),
                        'payment_currency' => self::attr($doc, 'MonedaDR'),
                        'payment_equivalence' => self::decimal(self::attr($doc, 'EquivalenciaDR'), null),
                        'payment_method' => self::attr($doc, 'MetodoDePagoDR'),
                        'payment_partiality' => (int) self::decimal(self::attr($doc, 'NumParcialidad')),
                        'payment_previous_balance' => self::decimal(self::attr($doc, 'ImpSaldoAnt')),
                        'payment_amount' => self::decimal(self::attr($doc, 'ImpPagado')),
                        'payment_remaining_balance' => self::decimal(self::attr($doc, 'ImpSaldoInsoluto')),
                        'tax_object' => self::attr($doc, 'ObjetoImpDR'),
                    ];
                    $data['payments'][] = $item;
                }
            }
        }
    }

    protected static function attr($node, $primary, $fallback = null)
    {
        if (!$node) {
            return '';
        }
        if (isset($node[$primary])) {
            return trim((string) $node[$primary]);
        }
        if ($fallback && isset($node[$fallback])) {
            return trim((string) $node[$fallback]);
        }
        return '';
    }

    protected static function decimal($value, $default = 0)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $default;
        }
        return (float) $value;
    }

    protected static function first($items)
    {
        return !empty($items) ? $items[0] : null;
    }
}
