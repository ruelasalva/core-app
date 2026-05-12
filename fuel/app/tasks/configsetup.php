<?php
namespace Fuel\Tasks;

class Configsetup
{
    public function run()
    {
        try {
            $this->assert_schema_ready();
            $this->seed_company();
            $this->seed_departments();
            $this->seed_backends();
            $this->seed_web_integrations();
            $this->seed_legal_documents();
            $this->seed_communications();
            $this->seed_integrations();
            $this->seed_payments();
            $this->seed_billing();
            $this->seed_sat();
            $this->seed_sat_catalogs();
            $this->seed_catalogs();
            $this->seed_commerce();
            $this->seed_parties();
            $this->seed_portals();
            $this->seed_documents();
            $this->seed_helpdesk();
            $this->seed_calendar();
            $this->seed_frontend();
            $this->seed_knowledge();
            $this->sync_groups();
            $this->sync_permissions();
            $this->cleanup_legacy_permissions();

            echo "\n [SUCCESS] Configuracion base preparada.\n";
            echo " - Empresa base\n";
            echo " - Departamentos iniciales\n";
            echo " - Backends iniciales\n";
            echo " - Integraciones web iniciales\n";
            echo " - Documentos legales base\n";
            echo " - Comunicaciones base\n";
            echo " - Integraciones y auditoria base\n";
            echo " - Pagos y bancos base\n";
            echo " - Facturacion base\n";
            echo " - SAT base\n";
            echo " - Catalogos SAT base\n";
            echo " - Catalogos base del ERP\n";
            echo " - Catalogos comerciales base\n";
            echo " - Terceros base\n";
            echo " - Portales externos base\n";
            echo " - Documentos y evidencias base\n";
            echo " - Helpdesk base\n";
            echo " - Calendario y sala de juntas base\n";
            echo " - Frontend administrable base\n";
            echo " - Ayuda y conocimiento base\n";
            echo " - Grupos de acceso recomendados\n";
            echo " - Permisos base\n";
        } catch (\Exception $e) {
            echo "\n [ERROR] ".$e->getMessage()."\n";
            \Log::error('Fallo en configsetup: '.$e->getMessage());
        }
    }

    protected function assert_schema_ready()
    {
        foreach (['core_companies', 'core_departments', 'core_backends'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        if (!\DBUtil::field_exists('core_companies', ['invoice_receive_days', 'blocked_reception'])) {
            throw new \Exception('Primero ejecuta: php oil refine migrate');
        }

        foreach (['core_web_integrations', 'core_web_cookie_preferences'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_legal_documents', 'core_user_consents'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_notifications', 'core_notification_recipients', 'core_email_roles', 'core_email_templates', 'core_email_queue'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_integration_providers', 'core_integration_connections', 'core_integration_webhooks', 'core_integration_events', 'core_audit_logs'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_payments', 'core_payment_allocations', 'core_bank_movements', 'core_bank_reconciliations'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_billing_invoices', 'core_billing_invoice_items', 'core_billing_invoice_events'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_sat_config', 'core_sat_credentials', 'core_sat_sync_requests', 'core_sat_cfdi'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        if (!\DBUtil::field_exists('core_sat_cfdi', ['missing_xml', 'last_validated_at', 'sat_status_code'])) {
            throw new \Exception('Primero ejecuta: php oil refine migrate');
        }

        if (!\DBUtil::field_exists('core_audit_logs', ['table_name', 'record_pk', 'business_event', 'changed_fields_json'])) {
            throw new \Exception('Primero ejecuta: php oil refine migrate');
        }

        foreach (['core_sat_payment_forms', 'core_sat_payment_methods', 'core_sat_cfdi_uses', 'core_sat_tax_regimes', 'core_sat_unit_keys', 'core_sat_taxes'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_catalog_currencies', 'core_catalog_banks', 'core_catalog_taxes', 'core_catalog_units', 'core_catalog_payment_terms'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_catalog_shipping_carriers', 'core_catalog_shipping_zones', 'core_catalog_shipping_methods', 'core_catalog_carrier_services', 'core_catalog_shipment_statuses', 'core_catalog_fiscal_operation_types', 'core_catalog_fiscal_document_rules'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_commerce_brands', 'core_commerce_categories', 'core_commerce_subcategories', 'core_commerce_tags', 'core_commerce_products'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_commerce_price_lists', 'core_commerce_product_prices', 'core_commerce_customer_price_lists'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_parties', 'core_party_addresses', 'core_party_contacts'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_portal_profiles', 'core_party_user_links', 'core_party_branding'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_documents', 'core_document_links'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_helpdesk_categories', 'core_helpdesk_statuses', 'core_helpdesk_tickets', 'core_helpdesk_messages'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_calendar_resources', 'core_calendar_events'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        if (!\DBUtil::field_exists('core_helpdesk_tickets', ['due_at', 'scheduled_start_at', 'scheduled_end_at'])) {
            throw new \Exception('Primero ejecuta: php oil refine migrate');
        }

        foreach (['core_frontend_pages', 'core_frontend_sections', 'core_frontend_sliders', 'core_frontend_banners', 'core_frontend_menus', 'core_frontend_blocks', 'core_frontend_themes'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }

        foreach (['core_knowledge_articles'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }
    }

    protected function seed_company()
    {
        $exists = \DB::select('id')->from('core_companies')->execute()->current();
        if ($exists) {
            return;
        }

        \DB::insert('core_companies')->set([
            'name' => 'Core-App',
            'legal_name' => '',
            'rfc' => '',
            'postal_code' => '',
            'contact_email' => '',
            'contact_phone' => '',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }

    protected function seed_departments()
    {
        $departments = [
            'Sistemas',
            'Compras',
            'Ventas',
            'Finanzas',
            'Logistica',
            'Recursos Humanos',
            'Direccion',
            'Marketing',
            'Administracion',
        ];

        foreach ($departments as $name) {
            $slug = $this->slugify($name);
            $this->insert_if_missing('core_departments', 'slug', $slug, [
                'name'        => $name,
                'slug'        => $slug,
                'description' => '',
                'active'      => 1,
                'created_at'  => time(),
                'updated_at'  => time(),
            ]);
        }
    }

    protected function seed_backends()
    {
        $backends = [
            ['admin', 'Admin General', 'admin'],
            ['compras', 'Compras', 'compras'],
            ['proveedores', 'Proveedores', 'proveedores'],
            ['ventas', 'Ventas', 'ventas'],
            ['clientes', 'Clientes', 'clientes'],
            ['socios', 'Socios', 'socios'],
            ['revendedores', 'Revendedores', 'revendedores'],
            ['crm', 'CRM', 'crm'],
            ['helpdesk', 'Helpdesk', 'helpdesk'],
            ['finanzas', 'Finanzas', 'finanzas'],
        ];

        foreach ($backends as $backend) {
            $this->insert_if_missing('core_backends', 'code', $backend[0], [
                'code'        => $backend[0],
                'name'        => $backend[1],
                'description' => '',
                'base_route'  => $backend[2],
                'active'      => 1,
                'created_at'  => time(),
                'updated_at'  => time(),
            ]);
        }
    }

    protected function seed_web_integrations()
    {
        $integrations = [
            ['google_analytics', 'Google Analytics 4', 'Google', 'analytics', 'analytics', 10],
            ['google_tag_manager', 'Google Tag Manager', 'Google', 'tag_manager', 'marketing', 20],
            ['meta_pixel', 'Meta Pixel', 'Meta', 'pixel', 'marketing', 30],
            ['google_recaptcha', 'Google reCAPTCHA', 'Google', 'captcha', 'necessary', 40],
        ];

        foreach ($integrations as $integration) {
            $this->insert_if_missing('core_web_integrations', 'code', $integration[0], [
                'code' => $integration[0],
                'name' => $integration[1],
                'provider' => $integration[2],
                'integration_type' => $integration[3],
                'environment' => 'production',
                'public_key' => '',
                'public_value' => '',
                'secret_value' => '',
                'settings_json' => '',
                'enabled' => 0,
                'load_in_frontend' => 1,
                'load_in_admin' => 0,
                'requires_consent' => $integration[4] === 'necessary' ? 0 : 1,
                'consent_category' => $integration[4],
                'sort_order' => $integration[5],
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }

    protected function seed_legal_documents()
    {
        $documents = [
            [
                'aviso_privacidad',
                'Aviso de Privacidad',
                'general',
                'aviso_privacidad',
                'Documento base de aviso de privacidad. Actualiza el contenido antes de publicar.',
                1,
            ],
            [
                'terminos_condiciones',
                'Terminos y Condiciones',
                'general',
                'terminos',
                'Documento base de terminos y condiciones. Actualiza el contenido antes de publicar.',
                1,
            ],
            [
                'politica_cookies',
                'Politica de Cookies',
                'general',
                'cookies',
                'Documento base de politica de cookies. Actualiza el contenido antes de publicar.',
                1,
            ],
        ];

        foreach ($documents as $document) {
            $this->insert_if_missing('core_legal_documents', 'shortcode', $document[0], [
                'shortcode' => $document[0],
                'title' => $document[1],
                'category' => $document[2],
                'document_type' => $document[3],
                'content' => $document[4],
                'version' => '1.0',
                'required' => $document[5],
                'active' => 1,
                'allow_download' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }

    protected function seed_communications()
    {
        $this->insert_if_missing('core_email_roles', 'code', 'system', [
            'code' => 'system',
            'name' => 'Sistema',
            'from_email' => 'no-reply@coreapp.local',
            'from_name' => 'Core-App',
            'reply_to_email' => '',
            'reply_to_name' => '',
            'to_emails' => '',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_email_templates', 'code', 'system_notification', [
            'code' => 'system_notification',
            'email_role' => 'system',
            'subject' => '{{title}}',
            'view_path' => '',
            'content' => '{{message}}',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_notification_events', 'code', 'system.test', [
            'code' => 'system.test',
            'name' => 'Prueba del sistema',
            'description' => 'Evento base para validar notificaciones internas.',
            'title_template' => '{{title}}',
            'message_template' => '{{message}}',
            'url_template' => 'admin',
            'icon' => 'bi bi-bell',
            'priority' => 1,
            'notify_internal' => 1,
            'notify_email' => 0,
            'email_role' => 'system',
            'email_template_code' => 'system_notification',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    /**
     * SEED INTEGRATIONS
     *
     * CREA PROVEEDORES BASE PARA INTEGRACIONES EXTERNAS SIN CREDENCIALES REALES
     *
     * @access  protected
     * @return  Void
     */
    protected function seed_integrations()
    {
        $providers = [
            ['mercado_pago', 'Mercado Pago', 'payment_gateway', 'Pasarela de pago. Requiere revisar SDK/API actual del proveedor antes de activar.', 'https://www.mercadopago.com.mx/developers', 'Adapter_Payment_MercadoPago', 1, 10],
            ['stripe', 'Stripe', 'payment_gateway', 'Pasarela de pago internacional. Requiere SDK/API oficial vigente.', 'https://stripe.com/docs', 'Adapter_Payment_Stripe', 1, 20],
            ['paypal', 'PayPal', 'payment_gateway', 'Pasarela de pago. Requiere credenciales REST y configuracion de webhooks.', 'https://developer.paypal.com/', 'Adapter_Payment_PayPal', 1, 30],
            ['openpay', 'Openpay', 'payment_gateway', 'Pasarela de pago usada en Mexico. Requiere validar SDK/API vigente.', 'https://www.openpay.mx/docs/', 'Adapter_Payment_Openpay', 1, 40],
            ['conekta', 'Conekta', 'payment_gateway', 'Pasarela de pago. Requiere validar SDK/API vigente.', 'https://developers.conekta.com/', 'Adapter_Payment_Conekta', 1, 50],
            ['bank_transfer', 'Transferencia bancaria', 'payment_manual', 'Metodo manual para pagos por transferencia y conciliacion bancaria.', '', '', 0, 60],
            ['sat', 'SAT', 'tax_authority', 'Integracion fiscal para CFDI, descargas, validaciones y eventos fiscales.', 'https://www.sat.gob.mx/', 'Adapter_Tax_Sat', 1, 70],
            ['whatsapp_business', 'WhatsApp Business', 'messaging', 'Mensajeria y notificaciones externas. Requiere proveedor/API autorizado.', 'https://developers.facebook.com/docs/whatsapp', 'Adapter_Messaging_WhatsApp', 1, 80],
        ];

        foreach ($providers as $provider) {
            $this->upsert_seed('core_integration_providers', 'code', $provider[0], [
                'code' => $provider[0],
                'name' => $provider[1],
                'category' => $provider[2],
                'description' => $provider[3],
                'website_url' => $provider[4],
                'adapter_class' => $provider[5],
                'requires_install' => $provider[6],
                'install_notes' => 'No activar sin revisar documentacion oficial vigente, ambiente sandbox, webhooks y manejo de errores.',
                'config_schema_json' => '{"environment":"sandbox|production","webhooks":true,"requires_secret":true}',
                'sort_order' => $provider[7],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }

    /**
     * SEED PAYMENTS
     *
     * PREPARA BASE DE PAGOS Y BANCOS SIN GENERAR MOVIMIENTOS CONTABLES REALES
     *
     * @access  protected
     * @return  Void
     */
    protected function seed_payments()
    {
        # ESTE MODULO ARRANCA SIN REGISTROS OPERATIVOS; LAS TABLAS Y PERMISOS SON LA BASE
        return;
    }

    /**
     * SEED BILLING
     *
     * PREPARA BASE DE FACTURACION SIN TIMBRAR CFDI REALES
     *
     * @access  protected
     * @return  Void
     */
    protected function seed_billing()
    {
        # ESTE MODULO ARRANCA SIN FACTURAS; EL TIMBRADO SE CONECTARA DESPUES CON SAT/INTEGRACIONES
        return;
    }

    protected function seed_sat()
    {
        $exists = \DB::select('id')->from('core_sat_config')->execute()->current();
        if ($exists) {
            return;
        }

        \DB::insert('core_sat_config')->set([
            'mode' => 'test',
            'enabled' => 0,
            'storage_path' => 'fuel/app/storage/sat',
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
    }

    protected function seed_sat_catalogs()
    {
        $payment_forms = [
            ['01', 'Efectivo', 0],
            ['02', 'Cheque nominativo', 1],
            ['03', 'Transferencia electronica de fondos', 1],
            ['04', 'Tarjeta de credito', 1],
            ['28', 'Tarjeta de debito', 1],
            ['99', 'Por definir', 0],
        ];

        foreach ($payment_forms as $item) {
            $this->insert_if_missing('core_sat_payment_forms', 'code', $item[0], [
                'code' => $item[0],
                'name' => $item[1],
                'banked' => $item[2],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $payment_methods = [
            ['PUE', 'Pago en una sola exhibicion', 'Liquidado al emitir el CFDI'],
            ['PPD', 'Pago en parcialidades o diferido', 'Requiere complemento de pago'],
        ];

        foreach ($payment_methods as $item) {
            $this->insert_if_missing('core_sat_payment_methods', 'code', $item[0], [
                'code' => $item[0],
                'name' => $item[1],
                'description' => $item[2],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $cfdi_uses = [
            ['G01', 'Adquisicion de mercancias', 1, 1],
            ['G03', 'Gastos en general', 1, 1],
            ['I01', 'Construcciones', 1, 1],
            ['P01', 'Por definir', 1, 1],
            ['S01', 'Sin efectos fiscales', 1, 1],
        ];

        foreach ($cfdi_uses as $item) {
            $this->insert_if_missing('core_sat_cfdi_uses', 'code', $item[0], [
                'code' => $item[0],
                'name' => $item[1],
                'applies_person' => $item[2],
                'applies_company' => $item[3],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $tax_regimes = [
            ['601', 'General de Ley Personas Morales', 0, 1],
            ['603', 'Personas Morales con Fines no Lucrativos', 0, 1],
            ['605', 'Sueldos y Salarios e Ingresos Asimilados a Salarios', 1, 0],
            ['612', 'Personas Fisicas con Actividades Empresariales y Profesionales', 1, 0],
            ['616', 'Sin obligaciones fiscales', 1, 0],
            ['626', 'Regimen Simplificado de Confianza', 1, 1],
        ];

        foreach ($tax_regimes as $item) {
            $this->insert_if_missing('core_sat_tax_regimes', 'code', $item[0], [
                'code' => $item[0],
                'name' => $item[1],
                'applies_person' => $item[2],
                'applies_company' => $item[3],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $unit_keys = [
            ['H87', 'Pieza', ''],
            ['E48', 'Unidad de servicio', ''],
            ['KGM', 'Kilogramo', 'kg'],
            ['MTR', 'Metro', 'm'],
            ['LTR', 'Litro', 'l'],
        ];

        foreach ($unit_keys as $item) {
            $this->insert_if_missing('core_sat_unit_keys', 'code', $item[0], [
                'code' => $item[0],
                'name' => $item[1],
                'symbol' => $item[2],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $taxes = [
            ['001', 'ISR', 'retencion', 'Tasa', 0.100000],
            ['002', 'IVA', 'traslado', 'Tasa', 0.160000],
            ['003', 'IEPS', 'traslado', 'Tasa', 0.000000],
        ];

        foreach ($taxes as $item) {
            $this->insert_if_missing('core_sat_taxes', 'code', $item[0], [
                'code' => $item[0],
                'name' => $item[1],
                'tax_type' => $item[2],
                'factor_type' => $item[3],
                'default_rate' => $item[4],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }

    protected function seed_catalogs()
    {
        $this->insert_if_missing('core_catalog_currencies', 'code', 'MXN', [
            'code' => 'MXN',
            'name' => 'Peso Mexicano',
            'symbol' => '$',
            'decimals' => 2,
            'is_base' => 1,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_catalog_currencies', 'code', 'USD', [
            'code' => 'USD',
            'name' => 'Dolar Americano',
            'symbol' => 'USD',
            'decimals' => 2,
            'is_base' => 0,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $banks = [
            ['bbva', 'BBVA Mexico', '012'],
            ['banamex', 'Citibanamex', '002'],
            ['santander', 'Santander', '014'],
            ['banorte', 'Banorte', '072'],
        ];

        foreach ($banks as $bank) {
            $this->insert_if_missing('core_catalog_banks', 'code', $bank[0], [
                'code' => $bank[0],
                'name' => $bank[1],
                'sat_code' => $bank[2],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $taxes = [
            ['iva_16', 'IVA 16%', 0.160000, '002'],
            ['iva_0', 'IVA 0%', 0.000000, '002'],
        ];

        foreach ($taxes as $tax) {
            $this->insert_if_missing('core_catalog_taxes', 'code', $tax[0], [
                'code' => $tax[0],
                'name' => $tax[1],
                'rate' => $tax[2],
                'sat_tax_code' => $tax[3],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $retentions = [
            ['ret_iva', 'Retencion IVA', 0.106667, '002'],
            ['ret_isr', 'Retencion ISR', 0.100000, '001'],
        ];

        foreach ($retentions as $retention) {
            $this->insert_if_missing('core_catalog_retentions', 'code', $retention[0], [
                'code' => $retention[0],
                'name' => $retention[1],
                'rate' => $retention[2],
                'sat_tax_code' => $retention[3],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $this->insert_if_missing('core_catalog_discounts', 'code', 'sin_descuento', [
            'code' => 'sin_descuento',
            'name' => 'Sin descuento',
            'discount_type' => 'percent',
            'value' => 0,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $units = [
            ['pieza', 'Pieza', 'H87'],
            ['servicio', 'Servicio', 'E48'],
            ['kilogramo', 'Kilogramo', 'KGM'],
        ];

        foreach ($units as $unit) {
            $this->insert_if_missing('core_catalog_units', 'code', $unit[0], [
                'code' => $unit[0],
                'name' => $unit[1],
                'sat_unit_code' => $unit[2],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $document_types = [
            ['orden_compra', 'Orden de compra', 'compras', 0, 0],
            ['factura_compra', 'Factura de compra', 'compras', 0, 1],
            ['factura_venta', 'Factura de venta', 'ventas', 0, 1],
        ];

        foreach ($document_types as $document_type) {
            $this->insert_if_missing('core_catalog_document_types', 'code', $document_type[0], [
                'code' => $document_type[0],
                'name' => $document_type[1],
                'module' => $document_type[2],
                'affects_inventory' => $document_type[3],
                'affects_accounting' => $document_type[4],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $payment_terms = [
            ['contado', 'Contado', 0, 0],
            ['credito_15', 'Credito 15 dias', 15, 1],
            ['credito_30', 'Credito 30 dias', 30, 1],
        ];

        foreach ($payment_terms as $term) {
            $this->insert_if_missing('core_catalog_payment_terms', 'code', $term[0], [
                'code' => $term[0],
                'name' => $term[1],
                'days' => $term[2],
                'requires_credit' => $term[3],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $carriers = [
            ['dhl', 'DHL', 'https://www.dhl.com/mx-es/home/tracking.html?tracking-id={tracking}', 1],
            ['fedex', 'FedEx', 'https://www.fedex.com/fedextrack/?trknbr={tracking}', 1],
            ['estafeta', 'Estafeta', 'https://www.estafeta.com/Herramientas/Rastreo', 1],
            ['entrega_local', 'Entrega local', '', 0],
        ];

        foreach ($carriers as $carrier) {
            $this->insert_if_missing('core_catalog_shipping_carriers', 'code', $carrier[0], [
                'code' => $carrier[0],
                'name' => $carrier[1],
                'tracking_url' => $carrier[2],
                'requires_account' => $carrier[3],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $zones = [
            ['local', 'Local', 'MX', '', ''],
            ['nacional', 'Nacional', 'MX', '', ''],
            ['internacional', 'Internacional', '', '', ''],
        ];

        foreach ($zones as $zone) {
            $this->insert_if_missing('core_catalog_shipping_zones', 'code', $zone[0], [
                'code' => $zone[0],
                'name' => $zone[1],
                'country_code' => $zone[2],
                'state_codes' => $zone[3],
                'postal_codes' => $zone[4],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $methods = [
            ['paqueteria', 'Paqueteria', 'parcel', 1],
            ['entrega_local', 'Entrega local', 'local_delivery', 1],
            ['recoge_cliente', 'Recoge cliente', 'pickup', 0],
            ['digital', 'Entrega digital', 'digital', 0],
        ];

        foreach ($methods as $method) {
            $this->insert_if_missing('core_catalog_shipping_methods', 'code', $method[0], [
                'code' => $method[0],
                'name' => $method[1],
                'delivery_type' => $method[2],
                'requires_address' => $method[3],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $parcel_method = \DB::select('id')->from('core_catalog_shipping_methods')->where('code', '=', 'paqueteria')->execute()->current();
        $local_method = \DB::select('id')->from('core_catalog_shipping_methods')->where('code', '=', 'entrega_local')->execute()->current();
        $service_method_id = $parcel_method ? (int) $parcel_method['id'] : 0;
        $local_method_id = $local_method ? (int) $local_method['id'] : 0;

        $carrier_services = [
            ['dhl', $service_method_id, 'express', 'Express', 1],
            ['fedex', $service_method_id, 'standard', 'Standard', 3],
            ['estafeta', $service_method_id, 'terrestre', 'Terrestre', 5],
            ['entrega_local', $local_method_id, 'ruta_local', 'Ruta local', 1],
        ];

        foreach ($carrier_services as $service) {
            $carrier = \DB::select('id')->from('core_catalog_shipping_carriers')->where('code', '=', $service[0])->execute()->current();
            if (!$carrier) {
                continue;
            }

            $this->insert_if_missing('core_catalog_carrier_services', 'code', $service[2], [
                'carrier_id' => (int) $carrier['id'],
                'shipping_method_id' => $service[1],
                'code' => $service[2],
                'name' => $service[3],
                'estimated_days' => $service[4],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $shipment_statuses = [
            ['pendiente', 'Pendiente', 'secondary', 0],
            ['preparacion', 'En preparacion', 'info', 0],
            ['en_transito', 'En transito', 'warning', 0],
            ['entregado', 'Entregado', 'success', 1],
            ['cancelado', 'Cancelado', 'danger', 1],
        ];

        foreach ($shipment_statuses as $status) {
            $this->insert_if_missing('core_catalog_shipment_statuses', 'code', $status[0], [
                'code' => $status[0],
                'name' => $status[1],
                'color' => $status[2],
                'is_final' => $status[3],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $operation_types = [
            ['venta_facturable', 'Venta facturable', 'sales', 1],
            ['venta_publico_general', 'Venta publico general', 'sales', 1],
            ['compra_deducible', 'Compra deducible', 'purchases', 1],
            ['pago_complemento', 'Pago con complemento', 'payments', 1],
            ['devolucion', 'Devolucion', 'returns', 1],
        ];

        foreach ($operation_types as $operation) {
            $this->insert_if_missing('core_catalog_fiscal_operation_types', 'code', $operation[0], [
                'code' => $operation[0],
                'name' => $operation[1],
                'operation_scope' => $operation[2],
                'requires_cfdi' => $operation[3],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $invoice_doc = \DB::select('id')->from('core_catalog_document_types')->where('code', '=', 'factura_venta')->execute()->current();
        $purchase_doc = \DB::select('id')->from('core_catalog_document_types')->where('code', '=', 'factura_compra')->execute()->current();
        $sale_operation = \DB::select('id')->from('core_catalog_fiscal_operation_types')->where('code', '=', 'venta_facturable')->execute()->current();
        $purchase_operation = \DB::select('id')->from('core_catalog_fiscal_operation_types')->where('code', '=', 'compra_deducible')->execute()->current();

        $fiscal_rules = [
            ['factura_venta_general', 'Factura de venta general', $invoice_doc, $sale_operation, 'G03', '99', 'PPD', '601', 1, 1],
            ['factura_compra_general', 'Factura de compra general', $purchase_doc, $purchase_operation, 'G03', '99', 'PPD', '601', 1, 1],
        ];

        foreach ($fiscal_rules as $rule) {
            $this->insert_if_missing('core_catalog_fiscal_document_rules', 'code', $rule[0], [
                'code' => $rule[0],
                'name' => $rule[1],
                'document_type_id' => $rule[2] ? (int) $rule[2]['id'] : 0,
                'operation_type_id' => $rule[3] ? (int) $rule[3]['id'] : 0,
                'sat_cfdi_use_code' => $rule[4],
                'sat_payment_form_code' => $rule[5],
                'sat_payment_method_code' => $rule[6],
                'sat_tax_regime_code' => $rule[7],
                'requires_rfc' => $rule[8],
                'requires_fiscal_address' => $rule[9],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }

    protected function seed_commerce()
    {
        $this->insert_if_missing('core_commerce_price_lists', 'code', 'publico_general', [
            'code' => 'publico_general',
            'name' => 'Publico General',
            'description' => 'Lista de precios predeterminada para venta general.',
            'currency_code' => 'MXN',
            'is_default' => 1,
            'priority' => 100,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_commerce_price_lists', 'code', 'mayoreo', [
            'code' => 'mayoreo',
            'name' => 'Mayoreo',
            'description' => 'Lista base para precios de mayoreo o clientes con condiciones especiales.',
            'currency_code' => 'MXN',
            'is_default' => 0,
            'priority' => 80,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_commerce_brands', 'slug', 'general', [
            'name' => 'General',
            'slug' => 'general',
            'description' => 'Marca base para productos pendientes de clasificar.',
            'logo_path' => '',
            'show_in_home' => 0,
            'sort_order' => 0,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_commerce_categories', 'slug', 'general', [
            'name' => 'General',
            'slug' => 'general',
            'description' => 'Categoria base para productos pendientes de clasificar.',
            'image_path' => '',
            'show_in_home' => 0,
            'sort_order' => 0,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $category = \DB::select('id')->from('core_commerce_categories')->where('slug', '=', 'general')->execute()->current();
        $category_id = $category ? (int) $category['id'] : 0;

        if ($category_id > 0) {
            $this->insert_if_missing('core_commerce_subcategories', 'slug', 'general', [
                'category_id' => $category_id,
                'name' => 'General',
                'slug' => 'general',
                'description' => 'Subcategoria base para productos pendientes de clasificar.',
                'image_path' => '',
                'show_in_home' => 0,
                'sort_order' => 0,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $tags = [
            ['nuevo', 'Nuevo', 'status', '#28a745'],
            ['destacado', 'Destacado', 'status', '#ffc107'],
            ['inicio', 'Inicio', 'frontend', '#007bff'],
        ];

        foreach ($tags as $tag) {
            $this->insert_if_missing('core_commerce_tags', 'slug', $tag[0], [
                'name' => $tag[1],
                'slug' => $tag[0],
                'tag_type' => $tag[2],
                'color' => $tag[3],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }

    /**
     * SEED PARTIES
     *
     * CREA TERCEROS BASE PARA VALIDAR CLIENTES, PROVEEDORES, CONTACTOS Y DIRECCIONES
     *
     * @access  protected
     * @return  Void
     */
    protected function seed_parties()
    {
        $price_list = \DB::select('id')->from('core_commerce_price_lists')->where('code', '=', 'publico_general')->execute()->current();
        $payment_term = \DB::select('id')->from('core_catalog_payment_terms')->where('code', '=', 'contado')->execute()->current();
        $shipping_method = \DB::select('id')->from('core_catalog_shipping_methods')->where('code', '=', 'paqueteria')->execute()->current();
        $fiscal_operation = \DB::select('id')->from('core_catalog_fiscal_operation_types')->where('code', '=', 'venta_facturable')->execute()->current();

        $this->insert_if_missing('core_parties', 'code', 'publico_general', [
            'party_type' => 'customer',
            'code' => 'publico_general',
            'name' => 'Publico General',
            'legal_name' => '',
            'rfc' => 'XAXX010101000',
            'email' => '',
            'phone' => '',
            'price_list_id' => $price_list ? (int) $price_list['id'] : 0,
            'payment_term_id' => $payment_term ? (int) $payment_term['id'] : 0,
            'sat_cfdi_use_code' => 'S01',
            'sat_tax_regime_code' => '616',
            'fiscal_operation_type_id' => $fiscal_operation ? (int) $fiscal_operation['id'] : 0,
            'shipping_method_id' => $shipping_method ? (int) $shipping_method['id'] : 0,
            'credit_limit' => 0,
            'credit_days' => 0,
            'notes' => 'Cliente base para ventas de mostrador o publico general.',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $supplier_operation = \DB::select('id')->from('core_catalog_fiscal_operation_types')->where('code', '=', 'compra_deducible')->execute()->current();

        $this->insert_if_missing('core_parties', 'code', 'proveedor_general', [
            'party_type' => 'supplier',
            'code' => 'proveedor_general',
            'name' => 'Proveedor General',
            'legal_name' => '',
            'rfc' => '',
            'email' => '',
            'phone' => '',
            'price_list_id' => 0,
            'payment_term_id' => $payment_term ? (int) $payment_term['id'] : 0,
            'sat_cfdi_use_code' => 'G03',
            'sat_tax_regime_code' => '601',
            'fiscal_operation_type_id' => $supplier_operation ? (int) $supplier_operation['id'] : 0,
            'shipping_method_id' => 0,
            'credit_limit' => 0,
            'credit_days' => 0,
            'notes' => 'Proveedor base para pruebas iniciales.',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    /**
     * SEED PORTALS
     *
     * CREA LA BASE MULTIPORTAL PARA ACCESOS EXTERNOS Y BRANDING
     *
     * @access  protected
     * @return  Void
     */
    protected function seed_portals()
    {
        $portals = [
            ['clientes', 'clientes', 'Portal clientes', 'Consulta de documentos, pedidos, facturas y estado de cuenta.', 'clientes/login', 'clientes', 1, 'customer,both'],
            ['socios', 'socios', 'Portal socios', 'Portal para socios o clientes con condiciones especiales.', 'socios/login', 'socios', 1, 'customer,both'],
            ['proveedores', 'proveedores', 'Portal proveedores', 'Recepcion de facturas, documentos y seguimiento de pagos.', 'proveedores/login', 'proveedores', 1, 'supplier,both'],
            ['revendedores', 'revendedores', 'Portal revendedores', 'Cotizaciones y ventas con branding y listas de precio propias.', 'revendedores/login', 'revendedores', 1, 'customer,both'],
        ];

        foreach ($portals as $portal) {
            $this->insert_if_missing('core_portal_profiles', 'code', $portal[0], [
                'code' => $portal[0],
                'backend_code' => $portal[1],
                'name' => $portal[2],
                'description' => $portal[3],
                'login_route' => $portal[4],
                'dashboard_route' => $portal[5],
                'requires_party' => $portal[6],
                'allowed_party_types' => $portal[7],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $admin_user = \DB::select('id')->from('users')->where('username', '=', 'admin')->execute()->current();
        $customer = \DB::select('id')->from('core_parties')->where('code', '=', 'publico_general')->execute()->current();
        $supplier = \DB::select('id')->from('core_parties')->where('code', '=', 'proveedor_general')->execute()->current();

        if ($admin_user && $customer) {
            foreach (['clientes', 'socios', 'revendedores'] as $portal_code) {
                $this->insert_if_missing('core_party_user_links', 'portal_code', $portal_code, [
                    'user_id' => (int) $admin_user['id'],
                    'party_id' => (int) $customer['id'],
                    'portal_code' => $portal_code,
                    'role_code' => 'owner',
                    'scope_json' => '{"scope":"all"}',
                    'can_manage_users' => 1,
                    'active' => 1,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }
        }

        if ($admin_user && $supplier) {
            $this->insert_if_missing('core_party_user_links', 'portal_code', 'proveedores', [
                'user_id' => (int) $admin_user['id'],
                'party_id' => (int) $supplier['id'],
                'portal_code' => 'proveedores',
                'role_code' => 'owner',
                'scope_json' => '{"scope":"all"}',
                'can_manage_users' => 1,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        if ($customer) {
            $this->insert_if_missing('core_party_branding', 'portal_code', 'revendedores', [
                'party_id' => (int) $customer['id'],
                'portal_code' => 'revendedores',
                'display_name' => 'Publico General',
                'logo_path' => '',
                'primary_color' => '#0d6efd',
                'secondary_color' => '#343a40',
                'quote_footer' => 'Cotizacion generada desde Core-App.',
                'custom_css' => '',
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }
    }

    /**
     * SEED DOCUMENTS
     *
     * PREPARA LA BASE DOCUMENTAL TRANSVERSAL SIN ARCHIVOS FISICOS INICIALES
     *
     * @access  protected
     * @return  Void
     */
    protected function seed_documents()
    {
        # NO SE CREAN ARCHIVOS FISICOS DESDE SEED; EL MODULO QUEDA LISTO PARA CARGA ADMINISTRADA.
        return;
    }

    /**
     * SEED HELPDESK
     *
     * PREPARA ESTADOS, CATEGORIAS Y EVENTOS BASE PARA TICKETS TRANSVERSALES
     *
     * @access  protected
     * @return  Void
     */
    protected function seed_helpdesk()
    {
        # ESTADOS BASE DEL FLUJO DE ATENCION
        $statuses = [
            ['nuevo', 'Nuevo', 'primary', 0, 10],
            ['en_revision', 'En revision', 'info', 0, 20],
            ['esperando_usuario', 'Esperando usuario', 'warning', 0, 30],
            ['resuelto', 'Resuelto', 'success', 1, 80],
            ['cerrado', 'Cerrado', 'secondary', 1, 90],
            ['cancelado', 'Cancelado', 'dark', 1, 100],
        ];

        foreach ($statuses as $status) {
            $this->upsert_seed('core_helpdesk_statuses', 'code', $status[0], [
                'code' => $status[0],
                'name' => $status[1],
                'color' => $status[2],
                'is_closed' => $status[3],
                'sort_order' => $status[4],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        # CATEGORIAS BASE VINCULADAS A DEPARTAMENTOS CUANDO EXISTAN
        $categories = [
            ['soporte_general', 'Soporte general', 'Solicitudes generales de ayuda.', 'Sistemas'],
            ['facturacion', 'Facturacion', 'Dudas fiscales, CFDI, pagos o comprobantes.', 'Finanzas'],
            ['pedidos', 'Pedidos y ventas', 'Seguimiento comercial, cotizaciones o pedidos.', 'Ventas'],
            ['compras', 'Compras', 'Seguimiento con compras y proveedores.', 'Compras'],
            ['proveedores', 'Proveedores', 'Atencion a proveedores, documentos y facturas.', 'Compras'],
            ['logistica', 'Logistica', 'Entregas, rutas, evidencias o incidencias de envio.', 'Logistica'],
            ['sistemas', 'Sistemas', 'Accesos, errores tecnicos o mejora de plataforma.', 'Sistemas'],
        ];

        foreach ($categories as $category) {
            $department = \DB::select('id')
                ->from('core_departments')
                ->where('name', '=', $category[3])
                ->execute()
                ->current();

            $this->upsert_seed('core_helpdesk_categories', 'code', $category[0], [
                'code' => $category[0],
                'name' => $category[1],
                'description' => $category[2],
                'department_id' => $department ? (int) $department['id'] : 0,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        # EVENTOS DE NOTIFICACION PARA HELPDesK
        $events = [
            ['helpdesk.ticket_created', 'Nuevo ticket', 'Se creo un nuevo ticket de soporte.', 'Nuevo ticket {{folio}}', '{{subject}}', 'helpdesk_ticket_notification'],
            ['helpdesk.ticket_replied', 'Respuesta en ticket', 'Se agrego una respuesta o nota a un ticket.', 'Respuesta en ticket {{folio}}', '{{message}}', 'helpdesk_ticket_notification'],
            ['helpdesk.ticket_closed', 'Ticket cerrado', 'Se cerro o resolvio un ticket de soporte.', 'Ticket cerrado {{folio}}', '{{subject}}', 'helpdesk_ticket_notification'],
        ];

        foreach ($events as $event) {
            $this->upsert_seed('core_notification_events', 'code', $event[0], [
                'code' => $event[0],
                'name' => $event[1],
                'description' => $event[2],
                'title_template' => $event[3],
                'message_template' => $event[4],
                'url_template' => 'admin/helpdesk',
                'icon' => 'bi bi-life-preserver',
                'priority' => 2,
                'notify_internal' => 1,
                'notify_email' => 0,
                'email_role' => 'helpdesk',
                'email_template_code' => $event[5],
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $this->upsert_seed('core_email_roles', 'code', 'helpdesk', [
            'code' => 'helpdesk',
            'name' => 'Helpdesk y soporte',
            'from_email' => '',
            'from_name' => 'Core-App Helpdesk',
            'reply_to_email' => '',
            'reply_to_name' => '',
            'to_emails' => '',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_email_templates', 'code', 'helpdesk_ticket_notification', [
            'code' => 'helpdesk_ticket_notification',
            'email_role' => 'helpdesk',
            'subject' => '{{title}}',
            'view_path' => '',
            'content' => '<p>Hola,</p><p>{{message}}</p><p>Consulta el seguimiento dentro de Core-App.</p>',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    /**
     * SEED CALENDAR
     *
     * PREPARA RECURSOS Y EVENTOS BASE PARA CALENDARIO TRANSVERSAL
     *
     * @access  protected
     * @return  Void
     */
    protected function seed_calendar()
    {
        # RECURSO INICIAL PARA RESERVAS INTERNAS
        $this->upsert_seed('core_calendar_resources', 'code', 'sala_juntas', [
            'code' => 'sala_juntas',
            'name' => 'Sala de juntas',
            'resource_type' => 'meeting_room',
            'location' => '',
            'capacity' => 8,
            'color' => '#0d6efd',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        # EVENTOS DE NOTIFICACION PARA CALENDARIO
        $events = [
            ['calendar.event_created', 'Evento creado', 'Se creo un evento o reserva en calendario.', 'Evento {{title}}', '{{message}}'],
            ['calendar.event_updated', 'Evento actualizado', 'Se actualizo un evento o reserva en calendario.', 'Evento actualizado {{title}}', '{{message}}'],
            ['calendar.resource_conflict', 'Conflicto de reserva', 'Se detecto intento de reserva en horario ocupado.', 'Conflicto de reserva', '{{message}}'],
        ];

        foreach ($events as $event) {
            $this->upsert_seed('core_notification_events', 'code', $event[0], [
                'code' => $event[0],
                'name' => $event[1],
                'description' => $event[2],
                'title_template' => $event[3],
                'message_template' => $event[4],
                'url_template' => 'admin/calendar',
                'icon' => 'bi bi-calendar3',
                'priority' => 2,
                'notify_internal' => 1,
                'notify_email' => 0,
                'email_role' => 'calendar',
                'email_template_code' => 'calendar_event_notification',
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $this->upsert_seed('core_email_roles', 'code', 'calendar', [
            'code' => 'calendar',
            'name' => 'Calendario y sala de juntas',
            'from_email' => '',
            'from_name' => 'Core-App Calendario',
            'reply_to_email' => '',
            'reply_to_name' => '',
            'to_emails' => '',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_email_templates', 'code', 'calendar_event_notification', [
            'code' => 'calendar_event_notification',
            'email_role' => 'calendar',
            'subject' => '{{title}}',
            'view_path' => '',
            'content' => '<p>Hola,</p><p>{{message}}</p><p>Consulta el calendario dentro de Core-App.</p>',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    protected function seed_frontend()
    {
        $this->insert_if_missing('core_frontend_themes', 'code', 'core_default', [
            'code' => 'core_default',
            'name' => 'Core default',
            'layout_key' => 'commerce_default',
            'color_primary' => '#0f766e',
            'color_secondary' => '#172033',
            'color_accent' => '#b7791f',
            'color_background' => '#ffffff',
            'color_surface' => '#f4f7fa',
            'color_text' => '#172033',
            'color_muted' => '#657084',
            'font_family' => 'Arial, Helvetica, sans-serif',
            'heading_font_family' => 'Arial, Helvetica, sans-serif',
            'logo_path' => '',
            'favicon_path' => '',
            'header_style' => 'standard',
            'footer_style' => 'standard',
            'custom_css' => '',
            'is_active' => 1,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_frontend_pages', 'slug', 'inicio', [
            'title' => 'Inicio',
            'slug' => 'inicio',
            'page_type' => 'home',
            'template_key' => 'home',
            'seo_title' => 'Core-App',
            'seo_description' => 'Pagina de inicio administrable.',
            'published' => 1,
            'is_home' => 1,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_frontend_pages', 'slug', 'empresa', [
            'title' => 'Empresa',
            'slug' => 'empresa',
            'page_type' => 'content',
            'template_key' => 'corporate',
            'seo_title' => 'Empresa',
            'seo_description' => 'Informacion institucional de la empresa.',
            'published' => 1,
            'is_home' => 0,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_frontend_pages', 'slug', 'distribucion', [
            'title' => 'Distribucion',
            'slug' => 'distribucion',
            'page_type' => 'content',
            'template_key' => 'corporate',
            'seo_title' => 'Distribucion',
            'seo_description' => 'Cobertura, entrega y canales de distribucion.',
            'published' => 1,
            'is_home' => 0,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_frontend_pages', 'slug', 'contacto', [
            'title' => 'Contacto',
            'slug' => 'contacto',
            'page_type' => 'content',
            'template_key' => 'contact',
            'seo_title' => 'Contacto',
            'seo_description' => 'Medios de contacto y atencion.',
            'published' => 1,
            'is_home' => 0,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $page = \DB::select('id')->from('core_frontend_pages')->where('slug', '=', 'inicio')->execute()->current();
        $page_id = $page ? (int) $page['id'] : 0;

        if ($page_id > 0) {
            $this->insert_if_missing('core_frontend_sections', 'section_key', 'hero', [
                'page_id' => $page_id,
                'section_key' => 'hero',
                'section_type' => 'content',
                'title' => 'Bienvenido a Core-App',
                'subtitle' => 'Base administrable para tu ERP.',
                'content' => 'Actualiza este contenido desde el modulo Frontend.',
                'media_path' => '',
                'target_type' => 'none',
                'target_id' => 0,
                'settings_json' => '',
                'sort_order' => 10,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            $this->insert_if_missing('core_frontend_sections', 'section_key', 'home_brands', [
                'page_id' => $page_id,
                'section_key' => 'home_brands',
                'section_type' => 'brands',
                'title' => 'Nuestras marcas',
                'subtitle' => '',
                'content' => 'Marcas activas marcadas para mostrarse en inicio.',
                'media_path' => '',
                'target_type' => 'brand',
                'target_id' => 0,
                'settings_json' => '{"source":"show_in_home","limit":12}',
                'sort_order' => 20,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            $this->insert_if_missing('core_frontend_sections', 'section_key', 'home_downloads', [
                'page_id' => $page_id,
                'section_key' => 'home_downloads',
                'section_type' => 'download_cards',
                'title' => 'Catalogos descargables',
                'subtitle' => 'Material de consulta para clientes y distribuidores.',
                'content' => 'Configura aqui catalogos, fichas o documentos descargables.',
                'media_path' => '',
                'target_type' => 'none',
                'target_id' => 0,
                'settings_json' => '{"items":[{"title":"Catalogo general","url":"assets/catalogos/catalogo-general.pdf"},{"title":"Catalogo de productos","url":"assets/catalogos/productos.pdf"}]}',
                'sort_order' => 40,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        }

        $this->seed_frontend_page_sections();

        $this->insert_if_missing('core_frontend_sliders', 'code', 'home_main', [
            'code' => 'home_main',
            'name' => 'Slider principal',
            'location' => 'home',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_frontend_menus', 'code', 'main_menu', [
            'code' => 'main_menu',
            'name' => 'Menu principal',
            'location' => 'header',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $menu = \DB::select('id')->from('core_frontend_menus')->where('code', '=', 'main_menu')->execute()->current();
        if ($menu) {
            $this->insert_if_missing('core_frontend_menu_items', 'label', 'Inicio', [
                'menu_id' => (int) $menu['id'],
                'parent_id' => 0,
                'label' => 'Inicio',
                'url' => '/',
                'target_type' => 'page',
                'target_id' => $page_id,
                'sort_order' => 10,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            $this->insert_if_missing('core_frontend_menu_items', 'label', 'Productos', [
                'menu_id' => (int) $menu['id'],
                'parent_id' => 0,
                'label' => 'Productos',
                'url' => 'productos',
                'target_type' => 'catalog',
                'target_id' => 0,
                'sort_order' => 20,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            $this->insert_if_missing('core_frontend_menu_items', 'label', 'Empresa', [
                'menu_id' => (int) $menu['id'],
                'parent_id' => 0,
                'label' => 'Empresa',
                'url' => 'empresa',
                'target_type' => 'page',
                'target_id' => 0,
                'sort_order' => 30,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            $this->insert_if_missing('core_frontend_menu_items', 'label', 'Distribucion', [
                'menu_id' => (int) $menu['id'],
                'parent_id' => 0,
                'label' => 'Distribucion',
                'url' => 'distribucion',
                'target_type' => 'page',
                'target_id' => 0,
                'sort_order' => 35,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            $this->insert_if_missing('core_frontend_menu_items', 'label', 'Contacto', [
                'menu_id' => (int) $menu['id'],
                'parent_id' => 0,
                'label' => 'Contacto',
                'url' => 'contacto',
                'target_type' => 'page',
                'target_id' => 0,
                'sort_order' => 40,
                'active' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            \DB::update('core_frontend_menu_items')
                ->set(['url' => 'empresa', 'updated_at' => time()])
                ->where('menu_id', '=', (int) $menu['id'])
                ->where('label', '=', 'Empresa')
                ->execute();

            \DB::update('core_frontend_menu_items')
                ->set(['url' => 'contacto', 'updated_at' => time()])
                ->where('menu_id', '=', (int) $menu['id'])
                ->where('label', '=', 'Contacto')
                ->execute();
        }

        $this->insert_if_missing('core_frontend_footer_columns', 'title', 'Core-App', [
            'title' => 'Core-App',
            'content' => 'Footer administrable desde Core-App.',
            'sort_order' => 10,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert_if_missing('core_frontend_blocks', 'code', 'home_products', [
            'code' => 'home_products',
            'name' => 'Productos destacados',
            'block_type' => 'products',
            'content' => '',
            'settings_json' => '{"source":"featured","limit":8}',
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    /**
     * SEED FRONTEND PAGE SECTIONS
     *
     * CREA SECCIONES BASE EDITABLES PARA LAS PAGINAS PUBLICAS
     *
     * @access  protected
     * @return  Void
     */
    protected function seed_frontend_page_sections()
    {
        $pages = [
            'empresa' => [
                ['empresa_intro', 'content_image', 'Quienes somos', 'Somos una empresa preparada para administrar contenido institucional desde Core-App.', 'Usa esta seccion para describir historia, propuesta de valor, experiencia y enfoque de servicio.', 10],
                ['empresa_valores', 'feature_grid', 'Valores', 'Principios de operacion y cultura.', 'Honestidad|Lealtad|Respeto|Compromiso|Calidad|Servicio', 20],
                ['empresa_cta', 'cta', 'Siempre tu mejor opcion', 'Conecta esta seccion con contacto, catalogo o cotizacion.', 'Configura el texto, imagen y enlaces desde el modulo Frontend.', 30],
            ],
            'distribucion' => [
                ['distribucion_cobertura', 'content_image', 'Cobertura y entrega', 'Explica zonas de entrega, paqueterias, tiempos y condiciones.', 'Esta pagina queda administrable por secciones para ajustar contenido, imagenes y mensajes sin modificar codigo.', 10],
                ['distribucion_servicios', 'feature_grid', 'Servicios de distribucion', 'Define ventajas logisticas y servicios disponibles.', 'Entrega local|Envios nacionales|Seguimiento|Atencion personalizada', 20],
            ],
            'contacto' => [
                ['contacto_info', 'contact_info', 'Contactanos', 'Medios de contacto y horarios de atencion.', 'Direccion: configura la direccion principal. Telefono: configura el telefono de atencion. Email: configura el correo de contacto.', 10],
                ['contacto_cta', 'cta', 'Necesitas ayuda con un producto?', 'Prepara este bloque para conectar con CRM o cotizaciones.', 'Mas adelante este componente podra disparar eventos y notificaciones.', 20],
            ],
        ];

        foreach ($pages as $slug => $sections) {
            $page = \DB::select('id')->from('core_frontend_pages')->where('slug', '=', $slug)->execute()->current();
            if (!$page) {
                continue;
            }

            foreach ($sections as $section) {
                $this->insert_if_missing('core_frontend_sections', 'section_key', $section[0], [
                    'page_id' => (int) $page['id'],
                    'section_key' => $section[0],
                    'section_type' => $section[1],
                    'title' => $section[2],
                    'subtitle' => $section[3],
                    'content' => $section[4],
                    'media_path' => '',
                    'target_type' => 'none',
                    'target_id' => 0,
                    'settings_json' => '',
                    'sort_order' => $section[5],
                    'active' => 1,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }
        }
    }

    /**
     * SEED KNOWLEDGE
     *
     * CREA MANUALES INICIALES PARA OPERACION Y CRECIMIENTO DEL SISTEMA
     *
     * @access  protected
     * @return  Void
     */
    protected function seed_knowledge()
    {
        $legacy_article = \DB::select('id')->from('core_knowledge_articles')->where('code', '=', 'catalogos_brecha_'.'sajor')->execute()->current();
        $current_article = \DB::select('id')->from('core_knowledge_articles')->where('code', '=', 'estructura_core_app_modulos')->execute()->current();
        if ($legacy_article && !$current_article) {
            \DB::update('core_knowledge_articles')
                ->set(['code' => 'estructura_core_app_modulos', 'updated_at' => time()])
                ->where('id', '=', (int) $legacy_article['id'])
                ->execute();
        } elseif ($legacy_article) {
            \DB::update('core_knowledge_articles')
                ->set(['active' => 0, 'updated_at' => time()])
                ->where('id', '=', (int) $legacy_article['id'])
                ->execute();
        }

        $this->upsert_seed('core_knowledge_articles', 'code', 'frontend_editar_paginas', [
            'code' => 'frontend_editar_paginas',
            'title' => 'Como editar paginas y mostrarlas en el frontend',
            'category' => 'Frontend',
            'summary' => 'Manual operativo para crear paginas, agregar secciones, publicarlas en menu y validar que aparezcan en el sitio publico.',
            'content' => '<h3>Objetivo</h3><p>Este manual explica como crear o editar una pagina administrable y hacer que se vea en el frontend publico. La idea no es editar archivos PHP para cada pagina, sino administrar contenido desde el panel.</p><h4>Conceptos base</h4><ul><li><strong>Apariencia</strong>: define tema activo, logo, colores, tipografias y CSS controlado.</li><li><strong>Pagina</strong>: define URL, titulo, SEO y estado publicado.</li><li><strong>Seccion</strong>: cada bloque visual dentro de una pagina, por ejemplo hero, texto con imagen, productos, marcas, descargas o contacto.</li><li><strong>Menu</strong>: permite que una pagina aparezca en la navegacion publica.</li><li><strong>Bloque reutilizable</strong>: contenido que puede usarse en varias paginas sin duplicarlo manualmente.</li></ul><h4>Crear o editar una pagina</h4><ol><li>Entra a <strong>Admin &gt; Frontend</strong>.</li><li>Abre el apartado <strong>Paginas</strong>.</li><li>Para una pagina nueva usa <strong>Nuevo</strong>; para modificar una existente usa el boton de editar.</li><li>Captura <strong>Titulo</strong>. El <strong>slug</strong> sera la URL publica, por ejemplo <code>empresa</code> se vera como <code>/empresa</code> o <code>/pagina/empresa</code> segun la ruta configurada.</li><li>Activa la pagina con <strong>Publicado</strong> o <strong>Activo</strong>. Si queda inactiva no debe mostrarse publicamente.</li><li>Captura SEO: titulo, descripcion y palabras clave cuando aplique.</li><li>Guarda la pagina.</li></ol><h4>Agregar contenido con secciones</h4><ol><li>Entra a <strong>Frontend &gt; Secciones</strong>.</li><li>Crea una seccion y selecciona la pagina a la que pertenece.</li><li>Define <strong>section_key</strong> con un codigo unico, por ejemplo <code>empresa_historia</code>.</li><li>Elige el <strong>tipo de seccion</strong>. Los mas utiles al inicio son contenido, contenido con imagen, CTA, productos, marcas, descargas y contacto.</li><li>Captura titulo, subtitulo, contenido e imagen si aplica.</li><li>Usa <strong>Orden</strong> para acomodar la seccion. Numeros menores aparecen primero.</li><li>Cuando el tipo de seccion tenga opciones especiales, usa <strong>Configuracion del componente</strong> en vez de escribir JSON manualmente.</li><li>Guarda y revisa el frontend.</li></ol><h4>Publicar en el menu</h4><ol><li>Entra a <strong>Frontend &gt; Menus</strong> y confirma que exista el menu principal.</li><li>Entra a <strong>Items menu</strong>.</li><li>Crea un item con la etiqueta visible, por ejemplo <code>Empresa</code>.</li><li>Selecciona el destino. Si es pagina interna, apunta al slug de la pagina; si es URL personalizada, captura la ruta.</li><li>Activa el item y define orden.</li><li>Guarda y revisa que aparezca en la navegacion publica.</li></ol><h4>Ver la pagina en el frontend</h4><ul><li>Pagina de inicio: abre <code>/</code>.</li><li>Pagina por slug: abre <code>/pagina/slug</code>.</li><li>Alias principales: si existe ruta dedicada como <code>/empresa</code>, tambien puede abrirse directo.</li><li>Productos: abre <code>/productos</code> y revisa que productos activos se vean con precio publico base.</li></ul><h4>Checklist antes de darla por lista</h4><ul><li>La pagina esta activa.</li><li>Tiene slug limpio y sin espacios.</li><li>Tiene al menos una seccion activa.</li><li>Las secciones tienen orden correcto.</li><li>Las imagenes cargan desde una ruta publica valida.</li><li>El menu apunta a la pagina correcta.</li><li>La vista publica no muestra textos de prueba.</li></ul>',
            'sort_order' => 10,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'estructura_core_app_modulos', [
            'code' => 'estructura_core_app_modulos',
            'title' => 'Estructura actual de Core-App',
            'category' => 'Arquitectura',
            'summary' => 'Mapa operativo de los modulos implementados, su proposito y donde se administran.',
            'content' => '<h3>Objetivo</h3><p>Este manual resume la estructura actual de Core-App para que cualquier persona del equipo entienda que administra cada modulo y como se conectan.</p><h4>Base administrativa</h4><ul><li><strong>Configuracion</strong>: empresa, departamentos, empleados, backends y parametros generales.</li><li><strong>Usuarios</strong>: cuentas que inician sesion con OrmAuth.</li><li><strong>Grupos y Permisos</strong>: permisos por modulo y accion usando OrmAuth.</li><li><strong>Ayuda</strong>: base de conocimiento operativa del sistema.</li></ul><h4>Base operativa</h4><ul><li><strong>Catalogos</strong>: monedas, bancos, fiscales, logisticos y datos transversales del ERP.</li><li><strong>Comercial</strong>: marcas, categorias, productos, tags y listas de precio.</li><li><strong>Terceros</strong>: clientes, proveedores, socios, revendedores, direcciones y contactos.</li><li><strong>Portales</strong>: perfiles de portal, accesos usuario-tercero-portal y branding externo.</li><li><strong>Documentos</strong>: repositorio transversal de documentos y evidencias.</li><li><strong>Helpdesk</strong>: tickets internos y externos con seguimiento y evidencias.</li><li><strong>Calendario</strong>: sala de juntas, recursos reservables, tareas y eventos transversales.</li><li><strong>Pagos y Bancos</strong>: cobros, pagos, movimientos bancarios y conciliaciones base.</li><li><strong>Facturacion</strong>: borradores, conceptos, importes y preparacion de CFDI.</li></ul><h4>Base publica y cumplimiento</h4><ul><li><strong>Frontend</strong>: paginas, secciones, menus, banners, sliders, footer y apariencia.</li><li><strong>Web</strong>: analytics, pixeles, captcha, scripts y cookies.</li><li><strong>Legal</strong>: documentos legales, consentimientos y preferencias.</li><li><strong>SAT</strong>: configuracion fiscal, credenciales, CFDI y catalogos SAT.</li><li><strong>Comunicaciones</strong>: correos, eventos, plantillas y notificaciones.</li><li><strong>Integraciones</strong>: proveedores externos, pasarelas, webhooks y conexiones.</li><li><strong>Auditoria</strong>: historial funcional de acciones criticas.</li></ul><h4>Regla de crecimiento</h4><p>Cada modulo nuevo debe agregar migracion, modelo, controlador, vista, permiso OrmAuth, entrada de menu si aplica y manual de ayuda. Si el modulo maneja archivos, debe usar Documentos y Evidencias en vez de inventar otra forma de adjuntos.</p>',
            'sort_order' => 20,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'permisos_ormauth_modulos', [
            'code' => 'permisos_ormauth_modulos',
            'title' => 'Como funcionan permisos y modulos',
            'category' => 'Seguridad',
            'summary' => 'Guia para entender como se conectan modulos, permisos OrmAuth, menu y acciones.',
            'content' => '<h3>Objetivo</h3><p>Core-App usa OrmAuth para controlar que puede ver o modificar cada grupo. Ningun modulo administrativo debe depender solo de que el menu este visible.</p><h4>Regla tecnica</h4><ul><li>El menu consulta permisos con <code>Auth::has_access(area.access[view])</code>.</li><li>Cada controlador administrativo valida lectura en <code>before()</code> con <code>require_access(area.access[view])</code>.</li><li>Las acciones que guardan datos validan <code>area.access[create]</code> o <code>area.access[edit]</code> segun corresponda.</li><li>Los permisos base se sincronizan desde <strong>configsetup</strong>.</li></ul><h4>Donde asignar permisos</h4><ol><li>Entra a <strong>Admin &gt; Administracion &gt; Grupos y Permisos</strong>.</li><li>Selecciona el grupo.</li><li>Activa las acciones necesarias por modulo.</li><li>Guarda cambios.</li><li>El usuario debe volver a iniciar sesion si tenia permisos cacheados.</li></ol><h4>Modulos actuales</h4><p>Los modulos administrativos actuales son: usuarios, permisos, configuracion, web, legal, comunicaciones, integraciones, pagos, facturacion, auditoria, SAT, catalogos, comercial, terceros, portales, documentos, helpdesk, calendario, frontend y ayuda.</p>',
            'sort_order' => 25,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'portales_accesos_terceros', [
            'code' => 'portales_accesos_terceros',
            'title' => 'Portales, terceros y accesos externos',
            'category' => 'Portales',
            'summary' => 'Explica como relacionar usuarios con terceros y portales externos.',
            'content' => '<h3>Objetivo</h3><p>Los portales externos separan la experiencia de clientes, socios, proveedores y revendedores sin duplicar usuarios ni terceros.</p><h4>Conceptos</h4><ul><li><strong>Usuario</strong>: cuenta que inicia sesion con OrmAuth.</li><li><strong>Tercero</strong>: cliente, proveedor, socio, revendedor o entidad comercial.</li><li><strong>Portal</strong>: entrada externa como clientes, socios, proveedores o revendedores.</li><li><strong>Acceso</strong>: vinculo usuario + tercero + portal.</li><li><strong>Branding</strong>: logo, colores y textos visibles para un tercero en un portal.</li></ul><h4>Dar acceso a un portal</h4><ol><li>Crea o valida el usuario en <strong>Usuarios</strong>.</li><li>Crea o valida el tercero en <strong>Terceros</strong>.</li><li>Entra a <strong>Portales &gt; Accesos</strong>.</li><li>Selecciona usuario, tercero, portal y rol.</li><li>Define scopes si el usuario solo debe ver una parte de la informacion.</li><li>Activa el acceso.</li></ol><h4>Validacion</h4><p>El usuario solo puede entrar a <code>/clientes</code>, <code>/socios</code>, <code>/proveedores</code> o <code>/revendedores</code> si existe un vinculo activo para ese portal.</p>',
            'sort_order' => 35,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'documentos_evidencias_transversales', [
            'code' => 'documentos_evidencias_transversales',
            'title' => 'Documentos y evidencias transversales',
            'category' => 'Documentos',
            'summary' => 'Uso del repositorio documental para adjuntos, evidencias y archivos reutilizables.',
            'content' => '<h3>Objetivo</h3><p>El modulo Documentos centraliza archivos y evidencias para no repetir logica de adjuntos en proveedores, clientes, tickets, facturas, cotizaciones u ordenes.</p><h4>Que se guarda</h4><ul><li>Archivo fisico y metadatos.</li><li>Tipo de documento.</li><li>Visibilidad: interna, portal, publica o privada.</li><li>Indicador de evidencia.</li><li>Vinculos a entidades del ERP.</li></ul><h4>Como usarlo</h4><ol><li>Entra a <strong>Documentos</strong>.</li><li>Sube el archivo.</li><li>Clasifica el tipo y la visibilidad.</li><li>Marca como evidencia si aplica.</li><li>Vincula el archivo a un tercero o a una entidad futura como ticket, factura, cotizacion u orden.</li></ol><h4>Regla de implementacion</h4><p>Todo modulo nuevo que necesite adjuntos debe usar <strong>core_documents</strong> y <strong>core_document_links</strong>. No debe crear su propia tabla de archivos salvo que exista una razon funcional fuerte.</p>',
            'sort_order' => 40,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'helpdesk_tickets_soporte', [
            'code' => 'helpdesk_tickets_soporte',
            'title' => 'Helpdesk y tickets de soporte',
            'category' => 'Helpdesk',
            'summary' => 'Uso del modulo de tickets para soporte interno, portales externos y seguimiento con evidencias.',
            'content' => '<h3>Objetivo</h3><p>Helpdesk centraliza solicitudes de soporte internas y externas para que administradores, clientes, proveedores, socios o revendedores puedan dar seguimiento sin perder historial.</p><h4>Que administra</h4><ul><li>Tickets con folio unico.</li><li>Solicitante, tercero, portal de origen y responsable asignado.</li><li>Categoria, departamento, estado y prioridad.</li><li>Conversacion con respuestas y notas internas.</li><li>Evidencias vinculadas desde el modulo Documentos.</li><li>Notificaciones internas para responsables.</li></ul><h4>Crear un ticket desde admin</h4><ol><li>Entra a <strong>Admin &gt; Helpdesk</strong>.</li><li>Presiona <strong>Nuevo ticket</strong>.</li><li>Captura tercero si aplica, categoria, prioridad, asunto y descripcion.</li><li>Asigna un responsable si ya sabes quien debe atenderlo.</li><li>Guarda el ticket.</li></ol><h4>Crear un ticket desde portal externo</h4><ol><li>El usuario entra a su portal: <code>/clientes</code>, <code>/socios</code>, <code>/proveedores</code> o <code>/revendedores</code>.</li><li>Abre <strong>Helpdesk</strong>.</li><li>Presiona <strong>Nuevo ticket</strong>.</li><li>Captura asunto, categoria, prioridad y descripcion.</li><li>El sistema guarda automaticamente el tercero, usuario solicitante y portal de origen.</li><li>El equipo administrativo recibe notificacion interna.</li></ol><h4>Dar seguimiento</h4><ol><li>Abre el ticket en Helpdesk.</li><li>Usa <strong>Responder</strong> para agregar seguimiento visible.</li><li>Activa <strong>Nota interna</strong> cuando el comentario sea solo para administradores.</li><li>Cambia estado o prioridad desde la accion de editar.</li><li>Cuando el estado sea resuelto o cerrado, el ticket queda marcado como cerrado.</li></ol><h4>Adjuntar evidencias</h4><ol><li>Abre el seguimiento del ticket.</li><li>En <strong>Adjuntos y evidencias</strong> selecciona el archivo.</li><li>Captura un titulo si quieres identificarlo mejor.</li><li>Presiona <strong>Adjuntar</strong>.</li><li>El archivo queda guardado en <strong>Documentos</strong> y vinculado al ticket con <code>entity_type = ticket</code>.</li></ol><h4>Regla de crecimiento</h4><p>Los portales externos deben reutilizar este mismo modulo para crear tickets. No se debe crear un helpdesk separado por portal; el portal solo define origen, tercero y visibilidad. Todo adjunto debe usar <strong>core_documents</strong> y <strong>core_document_links</strong>.</p>',
            'sort_order' => 45,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'calendario_sala_juntas_tareas', [
            'code' => 'calendario_sala_juntas_tareas',
            'title' => 'Calendario, sala de juntas y tareas',
            'category' => 'Calendario',
            'summary' => 'Uso del calendario central para reservar salas, registrar eventos internos y preparar fechas de tickets o tareas.',
            'content' => '<h3>Objetivo</h3><p>Calendario centraliza reservas y eventos para que no exista una agenda distinta por cada modulo. La sala de juntas es el primer recurso reservable, pero la misma base sirve para equipos, espacios, tareas, helpdesk y eventos futuros de portales.</p><h4>Conceptos</h4><ul><li><strong>Evento</strong>: cita, reunion, tarea, recordatorio o actividad relacionada con un modulo.</li><li><strong>Recurso</strong>: sala, equipo, vehiculo o espacio que puede reservarse.</li><li><strong>Asignado</strong>: usuario responsable del evento.</li><li><strong>Relacionado</strong>: modulo o registro de origen, por ejemplo un ticket de helpdesk.</li></ul><h4>Reservar sala de juntas</h4><ol><li>Entra a <strong>Admin &gt; Calendario</strong>.</li><li>Presiona <strong>Evento</strong>.</li><li>Captura titulo, inicio, fin y selecciona <strong>Sala de juntas</strong>.</li><li>Asigna responsable si aplica.</li><li>Guarda. El sistema evita reservas traslapadas sobre el mismo recurso.</li></ol><h4>Crear recursos</h4><ol><li>Presiona <strong>Recurso</strong>.</li><li>Captura nombre, codigo, tipo, ubicacion, capacidad y color.</li><li>Activalo para que aparezca como reservable.</li></ol><h4>Relacion con Helpdesk</h4><p>La migracion ya prepara campos de fecha en tickets para que despues podamos mostrar vencimientos, citas o tareas de soporte en el calendario sin crear otra agenda. Cuando se implemente esa parte, el ticket debe crear o relacionar eventos con <code>related_entity_type = helpdesk_ticket</code>.</p><h4>FullCalendar</h4><p>El modulo expone <code>admin/calendar/events_feed</code> en formato compatible con FullCalendar. La vista actual funciona sin dependencia externa; si se instala la libreria, se puede reemplazar la agenda visual usando el mismo controlador y las mismas tablas.</p><h4>Regla de crecimiento</h4><p>Cualquier modulo que necesite fechas debe integrarse con calendario o crear eventos relacionados. No conviene crear calendarios separados para compras, helpdesk, RRHH o salas.</p>',
            'sort_order' => 47,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'integraciones_pasarelas_seguras', [
            'code' => 'integraciones_pasarelas_seguras',
            'title' => 'Integraciones, pasarelas y proveedores externos',
            'category' => 'Integraciones',
            'summary' => 'Base para configurar pasarelas de pago, APIs externas, webhooks y proveedores sin amarrar el ERP a un solo servicio.',
            'content' => '<h3>Objetivo</h3><p>El modulo Integraciones prepara Core-App para conectarse con pasarelas de pago, SAT, mensajeria, paqueterias y APIs externas sin mezclar credenciales ni logica de terceros dentro de los modulos operativos.</p><h4>Conceptos</h4><ul><li><strong>Proveedor</strong>: servicio externo como Mercado Pago, Stripe, PayPal, Openpay, Conekta, SAT o WhatsApp Business.</li><li><strong>Conexion</strong>: credenciales y configuracion por ambiente sandbox o produccion.</li><li><strong>Webhook</strong>: endpoint por donde el proveedor avisa eventos.</li><li><strong>Evento</strong>: payload recibido o enviado a una integracion.</li><li><strong>Adaptador</strong>: clase de codigo que implementa la comunicacion real con el proveedor.</li></ul><h4>Reglas de seguridad</h4><ul><li>No guardar credenciales en templates ni controladores de negocio.</li><li>Usar sandbox antes de produccion.</li><li>Validar firmas de webhooks cuando el proveedor lo permita.</li><li>No activar una pasarela sin revisar documentacion oficial vigente.</li><li>No exponer secretos en vistas, logs ni auditoria.</li></ul><h4>Como configurar</h4><ol><li>Entra a <strong>Admin &gt; Integraciones</strong>.</li><li>Valida que exista el proveedor.</li><li>Crea una conexion por ambiente.</li><li>Captura public key y secretos.</li><li>Configura JSON adicional si el adaptador lo requiere.</li><li>Activa la conexion solo cuando el adaptador este probado.</li></ol><h4>Regla de crecimiento</h4><p>Cada pasarela nueva debe tener proveedor, conexion, webhook/eventos, manual y adaptador aislado. Los modulos de bancos, pagos, CFDI o ventas deben consumir la capa de integraciones, no hablar directo con cada proveedor.</p>',
            'sort_order' => 50,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'pagos_bancos_conciliacion_base', [
            'code' => 'pagos_bancos_conciliacion_base',
            'title' => 'Pagos, bancos y conciliacion base',
            'category' => 'Finanzas',
            'summary' => 'Uso inicial del modulo para registrar cobros, pagos, movimientos bancarios y preparar conciliacion.',
            'content' => '<h3>Objetivo</h3><p>Pagos y Bancos concentra la base financiera operativa antes de CFDI, ventas, compras y reportes. El modulo registra cobros o pagos, permite vincularlos con terceros y prepara movimientos bancarios para conciliacion.</p><h4>Conceptos</h4><ul><li><strong>Pago recibido</strong>: dinero que entra por cliente, socio, revendedor u otra entidad.</li><li><strong>Pago enviado</strong>: dinero que sale hacia proveedor, colaborador u otra entidad.</li><li><strong>Movimiento bancario</strong>: deposito, retiro, cargo, comision, ajuste o transferencia visto desde una cuenta bancaria.</li><li><strong>Asignacion</strong>: relacion futura entre un pago y una factura, orden, cotizacion u otro documento.</li><li><strong>Conciliacion</strong>: validacion de movimientos contra el estado de cuenta.</li></ul><h4>Registrar un pago</h4><ol><li>Entra a <strong>Admin &gt; Pagos y Bancos</strong>.</li><li>Presiona <strong>Nuevo pago</strong>.</li><li>Elige si es recibido o enviado.</li><li>Selecciona tercero, cuenta bancaria, moneda, forma de pago SAT y fecha.</li><li>Captura importe, referencia y notas.</li><li>Guarda. El sistema genera un folio <code>PAY-AAAAMMDD-00001</code> y audita el cambio.</li></ol><h4>Registrar movimiento bancario</h4><ol><li>Abre la pestaña de movimientos.</li><li>Presiona <strong>Nuevo movimiento</strong>.</li><li>Selecciona cuenta bancaria, fecha, tipo de movimiento, moneda e importe.</li><li>Captura referencia y descripcion.</li><li>Guarda el movimiento.</li></ol><h4>Reglas de seguridad y crecimiento</h4><ul><li>No conectar una pasarela real directo desde este modulo; debe pasar por <strong>Integraciones</strong>.</li><li>No guardar claves, tokens ni secretos en pagos o movimientos.</li><li>Todo cambio relevante debe quedar en <strong>Auditoria</strong>.</li><li>CFDI, ventas y compras deberan relacionarse por asignaciones, no duplicando pagos.</li><li>Las cuentas bancarias se administran desde Catalogos financieros.</li></ul>',
            'sort_order' => 52,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'facturacion_cfdi_base', [
            'code' => 'facturacion_cfdi_base',
            'title' => 'Facturacion y preparacion CFDI',
            'category' => 'Finanzas',
            'summary' => 'Manual base para crear facturas, capturar conceptos, calcular importes y preparar timbrado futuro.',
            'content' => '<h3>Objetivo</h3><p>Facturacion administra el documento operativo antes del timbrado. SAT conserva catalogos, credenciales y CFDI fiscales; Facturacion prepara tercero, conceptos, importes, uso CFDI, forma y metodo de pago.</p><h4>Crear una factura</h4><ol><li>Entra a <strong>Admin &gt; Facturacion</strong>.</li><li>Presiona <strong>Nueva factura</strong>.</li><li>Selecciona tipo: venta, compra, nota de credito o complemento de pago.</li><li>Selecciona tercero, fechas, moneda, condicion de pago y datos SAT.</li><li>Guarda la factura. El sistema genera folio interno <code>FAC-AAAAMMDD-00001</code>.</li></ol><h4>Agregar conceptos</h4><ol><li>Selecciona la factura.</li><li>Presiona <strong>Concepto</strong>.</li><li>Selecciona producto o captura concepto manual.</li><li>Captura cantidad, precio, descuento, unidad SAT, clave producto SAT y tasa de impuesto.</li><li>Guarda. El sistema recalcula subtotal, impuestos, retenciones, total y saldo.</li></ol><h4>Relacion con otros modulos</h4><ul><li><strong>Terceros</strong> aporta cliente/proveedor, RFC, regimen fiscal y uso CFDI sugerido.</li><li><strong>Catalogos</strong> aporta monedas, condiciones de pago, unidades e impuestos.</li><li><strong>SAT</strong> aporta catalogos fiscales, CFDI final y credenciales.</li><li><strong>Pagos y Bancos</strong> debera liquidar facturas por asignaciones.</li><li><strong>Documentos</strong> debera almacenar XML/PDF y evidencias relacionadas.</li></ul><h4>Reglas de seguridad</h4><ul><li>No timbrar sin validar credenciales CSD y proveedor PAC.</li><li>No guardar secretos fiscales en facturas.</li><li>Todo cambio de factura o concepto debe quedar auditado.</li><li>El estado <strong>Lista para timbrar</strong> no significa CFDI emitido; solo indica que esta lista para conectar el proceso fiscal.</li></ul>',
            'sort_order' => 53,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'sat_cfdi_descarga_validacion', [
            'code' => 'sat_cfdi_descarga_validacion',
            'title' => 'SAT CFDI: descarga, metadata y validacion',
            'category' => 'SAT',
            'summary' => 'Base conceptual para usar SAT como auditor fiscal: XML descargados, metadata, faltantes, cancelaciones y validacion SOAP.',
            'content' => '<h3>Objetivo</h3><p>El modulo SAT/CFDI no es donde se captura una factura operativa. Su funcion es comprobar informacion fiscal contra el SAT: descargar XML, procesar metadata, detectar CFDI faltantes, validar vigencia/cancelacion y alimentar alertas.</p><h4>Flujo recomendado</h4><ol><li><strong>Solicitar XML</strong>: crea una solicitud para recibidos o emitidos en un rango de fechas.</li><li><strong>Verificar solicitud</strong>: consulta si el SAT ya preparo paquetes.</li><li><strong>Descargar paquetes</strong>: guarda ZIP/XML en una ruta controlada.</li><li><strong>Importar CFDI</strong>: parsea XML y crea o actualiza <code>core_sat_cfdi</code>.</li><li><strong>Solicitar metadata</strong>: descarga metadatos para comparar contra lo que si tiene XML.</li><li><strong>Comparar</strong>: marca CFDI sin XML con <code>missing_xml = 1</code>.</li><li><strong>Validar SOAP</strong>: consulta estado vigente/cancelado y actualiza <code>sat_status</code>, <code>sat_status_code</code>, <code>last_validated_at</code> y <code>cancelled_at</code>.</li></ol><h4>Tareas Oil disponibles</h4><ul><li><code>php oil r satsync:status</code>: muestra resumen de solicitudes y CFDI.</li><li><code>php oil r satsync:request metadata received 2026-05-01 2026-05-12</code>: crea solicitud local de metadata recibida.</li><li><code>php oil r satsync:request xml issued 2026-05-01 2026-05-12</code>: crea solicitud local de XML emitidos.</li><li><code>php oil r satsync:submit</code>: prepara solicitudes pendientes. En test genera folio local; en produccion queda bloqueado hasta conectar el adaptador real.</li><li><code>php oil r satsync:verify</code>: verifica solicitudes. En test termina sin paquetes reales.</li><li><code>php oil r satsync:compare</code>: marca CFDI de metadata sin XML y contabiliza cancelados.</li></ul><h4>Reglas importantes</h4><ul><li>Metadata no debe sobrescribir un XML existente; solo complementa o detecta faltantes.</li><li>XML y metadata deben coexistir sin duplicar UUID.</li><li>Las solicitudes SAT deben auditarse.</li><li>No guardar certificados ni llaves en rutas publicas.</li><li>La conexion real con SAT se implementara mediante servicio aislado y cron, no desde la vista.</li></ul><h4>Relacion con Facturacion</h4><p><strong>Facturacion</strong> prepara documentos operativos. <strong>SAT/CFDI</strong> comprueba documentos fiscales reales descargados o validados contra SAT. Cuando exista timbrado/PAC, ambos se relacionaran por UUID o <code>cfdi_id</code>, pero no deben mezclarse.</p>',
            'sort_order' => 54,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'auditoria_funcional_core_app', [
            'code' => 'auditoria_funcional_core_app',
            'title' => 'Auditoria funcional del sistema',
            'category' => 'Seguridad',
            'summary' => 'Uso de auditoria para rastrear cambios relevantes antes de crear modulos criticos como bancos, CFDI, pagos y RRHH.',
            'content' => '<h3>Objetivo</h3><p>Auditoria registra acciones funcionales importantes del ERP: quien hizo que, cuando, desde donde, sobre que tabla/registro y que valores cambiaron. No reemplaza los logs tecnicos; complementa la seguridad operativa y prepara la base para reportes, CFDI, bancos, pagos y RRHH.</p><h4>Que debe auditarse</h4><ul><li>Creacion, edicion, eliminacion, activacion y cancelacion de registros.</li><li>Cambios de configuracion.</li><li>Integraciones y credenciales, sin exponer secretos.</li><li>Pagos, bancos y conciliaciones.</li><li>CFDI, metadata, descargas SAT, validaciones y cancelaciones.</li><li>Usuarios, permisos, accesos y portales.</li><li>Cambios de estados en flujos criticos.</li></ul><h4>Campos clave</h4><ul><li><strong>module/action</strong>: modulo y accion tecnica.</li><li><strong>business_event</strong>: evento de negocio, por ejemplo <code>sat.create_sync_request</code>.</li><li><strong>table_name/record_pk</strong>: tabla y registro afectado.</li><li><strong>old_values/new_values</strong>: valores anterior y nuevo.</li><li><strong>changed_fields</strong>: campos modificados calculados automaticamente.</li><li><strong>backend/portal/ip/user_agent</strong>: contexto de acceso.</li></ul><h4>Como consultar</h4><ol><li>Entra a <strong>Admin &gt; Auditoria</strong>.</li><li>Filtra por modulo, tabla, registro, operacion, severidad, portal o fechas.</li><li>Abre el detalle para comparar valores anteriores y nuevos.</li><li>Usa IP, user agent y ruta para investigar origen del cambio.</li></ol><h4>Regla de implementacion</h4><p>Cada modulo nuevo debe llamar a <code>Helper_Core_Audit::log()</code> cuando cree, edite, elimine, cambie estado, active, desactive o procese informacion sensible. El helper redacta campos sensibles como password, secret, token, key o api_key; aun asi, los controladores no deben enviar secretos innecesarios a auditoria.</p>',
            'sort_order' => 55,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->upsert_seed('core_knowledge_articles', 'code', 'crear_manuales_ayuda', [
            'code' => 'crear_manuales_ayuda',
            'title' => 'Como crear manuales de ayuda',
            'category' => 'Ayuda',
            'summary' => 'Pasos para documentar procesos internos desde el panel de Ayuda.',
            'content' => '<h3>Objetivo</h3><p>La base de conocimiento sirve para documentar procesos de Core-App sin modificar codigo. Cada manual debe explicar una tarea concreta, con pasos claros y criterios para validar que quedo bien.</p><h4>Crear un manual</h4><ol><li>Entra a <strong>Admin &gt; Ayuda</strong>.</li><li>Presiona <strong>Nuevo manual</strong>.</li><li>Captura un titulo claro, por ejemplo <code>Como crear una pagina de empresa</code>.</li><li>Elige una categoria. Recomendadas: Arquitectura, Seguridad, Frontend, Portales, Documentos, Catalogos, SAT, Usuarios, Ventas, Compras.</li><li>Agrega un resumen corto para saber de que trata sin abrirlo.</li><li>Escribe el contenido con pasos numerados, advertencias y checklist final.</li><li>Deja el manual activo para que aparezca en la lista.</li><li>Guarda y vuelve a abrirlo para validar que se entiende.</li></ol><h4>Estructura recomendada</h4><ul><li><strong>Objetivo</strong>: que problema resuelve el manual.</li><li><strong>Antes de empezar</strong>: permisos, datos o configuraciones necesarias.</li><li><strong>Pasos</strong>: instrucciones concretas en orden.</li><li><strong>Validacion</strong>: como confirmar que funciono.</li><li><strong>Errores comunes</strong>: que revisar si algo no aparece.</li></ul><h4>Reglas de documentacion</h4><ul><li>Usar nombres del menu tal como aparecen en pantalla.</li><li>No mezclar varios procesos grandes en un solo manual.</li><li>Actualizar el manual cuando cambie el flujo del sistema.</li><li>Cada modulo nuevo debe agregar o actualizar su manual de uso.</li></ul>',
            'sort_order' => 30,
            'active' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    protected function sync_groups()
    {
        $groups = [
            5   => 'Consulta',
            15  => 'Portal Externo',
            25  => 'Operador',
            40  => 'Supervisor',
            50  => 'Gerente',
            60  => 'Administrador de Ventas',
            70  => 'Administrador de Compras',
            80  => 'Administrador de Finanzas',
            90  => 'Administrador de Configuracion',
            100 => 'Administrador General',
        ];

        foreach ($groups as $id => $name) {
            $exists = \DB::select('id')->from('users_groups')->where('id', '=', $id)->execute()->current();
            if ($exists) {
                \DB::update('users_groups')->set(['name' => $name, 'updated_at' => time()])->where('id', '=', $id)->execute();
                continue;
            }

            \DB::insert('users_groups')->set([
                'id'         => $id,
                'name'       => $name,
                'user_id'    => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();
        }
    }

    protected function sync_permissions()
    {
        $actions = ['view', 'create', 'edit', 'delete', 'import', 'export'];
        $permissions = [
            'admin_dashboard' => 'Panel de control principal',
            'user' => 'Gestion de usuarios',
            'permissions' => 'Gestion de roles y permisos',
            'config' => 'Configuracion del sistema',
            'web' => 'Gestion web, integraciones y privacidad',
            'legal' => 'Gestion legal, consentimientos y cookies',
            'communications' => 'Gestion de correos, eventos y notificaciones',
            'integrations' => 'Gestion de proveedores externos, pasarelas, conexiones y webhooks',
            'payments' => 'Gestion de pagos, bancos, movimientos y conciliaciones',
            'billing' => 'Gestion de facturacion, conceptos y preparacion CFDI',
            'audit' => 'Consulta de auditoria funcional del sistema',
            'sat' => 'Gestion SAT, CFDI y sincronizacion fiscal',
            'catalogs' => 'Gestion de catalogos base del ERP',
            'commerce' => 'Gestion comercial, marcas, categorias y productos',
            'parties' => 'Gestion de clientes, proveedores, direcciones y contactos',
            'portals' => 'Gestion de portales externos, accesos y branding',
            'documents' => 'Gestion transversal de documentos y evidencias',
            'helpdesk' => 'Gestion de tickets, soporte y seguimiento con evidencias',
            'calendar' => 'Gestion de calendario, sala de juntas, recursos y tareas',
            'frontend' => 'Gestion de paginas, banners, menus y frontend administrable',
            'help' => 'Ayuda, manuales y conocimiento operativo',
        ];

        foreach ($permissions as $area => $description) {
            $exists = \DB::select('id')
                ->from('users_permissions')
                ->where('area', '=', $area)
                ->where('permission', '=', 'access')
                ->execute()
                ->current();

            if ($exists) {
                \DB::update('users_permissions')
                    ->set([
                        'description' => $description,
                        'actions' => serialize($actions),
                        'updated_at' => time(),
                    ])
                    ->where('id', '=', $exists['id'])
                    ->execute();
                continue;
            }

            \DB::insert('users_permissions')->set([
                'area' => $area,
                'permission' => 'access',
                'description' => $description,
                'actions' => serialize($actions),
                'user_id' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ])->execute();
        }
    }

    /**
     * CLEANUP LEGACY PERMISSIONS
     *
     * LIMPIA PERMISOS OBSOLETOS QUE PUEDEN CONFUNDIR LA PANTALLA DE GRUPOS
     *
     * @access  protected
     * @return  Void
     */
    protected function cleanup_legacy_permissions()
    {
        $legacy = array_merge(
            \DB::select('id')
                ->from('users_permissions')
                ->where('area', '=', 'config_auth')
                ->execute()
                ->as_array(),
            \DB::select('id')
                ->from('users_permissions')
                ->where('area', '=', 'user')
                ->where('permission', '!=', 'access')
                ->execute()
                ->as_array()
        );

        foreach ($legacy as $permission) {
            \DB::delete('users_group_permissions')
                ->where('perms_id', '=', (int) $permission['id'])
                ->execute();

            \DB::delete('users_permissions')
                ->where('id', '=', (int) $permission['id'])
                ->execute();
        }
    }

    protected function insert_if_missing($table, $field, $value, array $data)
    {
        $exists = \DB::select('id')->from($table)->where($field, '=', $value)->execute()->current();
        if (!$exists) {
            \DB::insert($table)->set($data)->execute();
        }
    }

    protected function upsert_seed($table, $field, $value, array $data)
    {
        $exists = \DB::select('id')->from($table)->where($field, '=', $value)->execute()->current();
        if ($exists) {
            \DB::update($table)
                ->set($data)
                ->where('id', '=', (int) $exists['id'])
                ->execute();
            return;
        }

        \DB::insert($table)->set($data)->execute();
    }

    protected function slugify($value)
    {
        $value = strtolower(trim((string) $value));
        if (function_exists('iconv')) {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }
}
