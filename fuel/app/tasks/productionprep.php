<?php
namespace Fuel\Tasks;

/**
 * TASK PRODUCTIONPREP
 *
 * Prepara la instalacion para subir a produccion:
 * - limpia datos operativos/de prueba sin tocar usuarios, permisos ni configuracion base.
 * - siembra el frontend institucional de SET Soluciones TI.
 *
 * Uso:
 * - php oil r productionprep all
 * - php oil r productionprep clean
 * - php oil r productionprep sat_audit
 * - php oil r productionprep frontend
 */
class Productionprep
{
    protected $now = 0;

    /**
     * RUN
     *
     * Ejecuta limpieza, frontend o ambos procesos.
     *
     * @access  public
     * @param   string  $mode
     * @return  void
     */
    public function run($mode = 'all')
    {
        $this->now = time();
        $mode = strtolower(trim((string) $mode)) ?: 'all';

        try {
            if (!in_array($mode, ['all', 'clean', 'sat_audit', 'frontend'], true)) {
                throw new \InvalidArgumentException('Modo no valido. Usa all, clean, sat_audit o frontend.');
            }

            if ($mode === 'all' || $mode === 'clean') {
                $this->clean_operational_data();
            }

            if ($mode === 'sat_audit') {
                $this->clean_sat_audit_data();
            }

            if ($mode === 'all' || $mode === 'frontend') {
                $this->seed_set_frontend();
            }

            echo "\n [SUCCESS] Preparacion de produccion terminada en modo: ".$mode."\n";
        } catch (\Exception $e) {
            echo "\n [ERROR] ".$e->getMessage()."\n";
            \Log::error('Fallo productionprep: '.$e->getMessage());
        }
    }

    /**
     * CLEAN OPERATIONAL DATA
     *
     * Limpia informacion transaccional o importada para dejar la base lista.
     *
     * @access  protected
     * @return  void
     */
    protected function clean_operational_data()
    {
        $tables = [
            'core_notification_recipients',
            'core_notifications',
            'core_email_queue',
            'core_payment_allocations',
            'core_bank_reconciliation_suggestions',
            'core_bank_reconciliations',
            'core_bank_movements',
            'core_bank_statement_imports',
            'core_payments',
            'core_treasury_cashflow_items',
            'core_budget_lines',
            'core_budget_plans',
            'core_ar_collection_actions',
            'core_ar_customer_statuses',
            'core_ap_payment_actions',
            'core_ap_supplier_statuses',
            'core_commission_adjustments',
            'core_commission_settlements',
            'core_commission_entries',
            'core_commission_quotas',
            'core_commission_rules',
            'core_commission_plans',
            'core_billing_invoice_events',
            'core_billing_invoice_items',
            'core_billing_recurring_runs',
            'core_billing_recurring_items',
            'core_billing_recurring_profiles',
            'core_billing_invoices',
            'core_fiscal_documents',
            'core_sales_delivery_items',
            'core_sales_deliveries',
            'core_sales_order_items',
            'core_sales_orders',
            'core_sales_quote_items',
            'core_sales_quotes',
            'core_cart_items',
            'core_cart_carts',
            'core_purchase_cfdi_line_mappings',
            'core_purchase_receipt_items',
            'core_purchase_receipts',
            'core_purchase_order_items',
            'core_purchase_orders',
            'core_purchase_invoices',
            'core_sat_payment_details',
            'core_sat_cfdi_details',
            'core_sat_cfdi_relations',
            'core_sat_cfdi_events',
            'core_sat_packages',
            'core_sat_sync_requests',
            'core_sat_cfdi',
            'core_sat_catalog_sync_logs',
            'core_sat_credentials',
            'core_sat_config',
            'core_audit_logs',
            'core_inventory_movements',
            'core_inventory_stock_balances',
            'core_accounting_journal_lines',
            'core_accounting_journal_entries',
            'core_documents_links',
            'core_document_links',
            'core_documents',
            'core_crm_survey_responses',
            'core_crm_surveys',
            'core_crm_activities',
            'core_crm_opportunities',
            'core_crm_prospect_imports',
            'core_crm_prospects',
            'core_helpdesk_messages',
            'core_helpdesk_tickets',
            'core_party_user_links',
            'core_party_contacts',
            'core_party_addresses',
            'core_party_brandings',
            'core_portal_access',
            'core_commerce_customer_price_lists',
            'core_commerce_product_relations',
            'core_commerce_product_prices',
            'core_commerce_product_images',
            'core_commerce_product_tags',
            'core_commerce_products',
            'core_commerce_subcategories',
            'core_commerce_categories',
            'core_commerce_brands',
            'core_commerce_tags',
            'core_commerce_price_lists',
            'core_sales_sellers',
        ];

        \DB::query('SET FOREIGN_KEY_CHECKS=0')->execute();
        foreach ($tables as $table) {
            if (!$this->table_exists($table)) {
                continue;
            }

            \DB::query('DELETE FROM `'.$table.'`')->execute();
            $this->reset_increment($table);
        }

        if ($this->table_exists('core_parties')) {
            \DB::delete('core_parties')
                ->where('party_type', 'IN', ['customer', 'supplier', 'prospect', 'both'])
                ->execute();
            $this->reset_increment('core_parties');
        }
        \DB::query('SET FOREIGN_KEY_CHECKS=1')->execute();

        echo "\n - Datos operativos limpiados.\n";
    }

    /**
     * CLEAN SAT AUDIT DATA
     *
     * Limpia CFDI importados, solicitudes SAT, credenciales fiscales y auditoria
     * funcional para poder cargar la informacion de una empresa nueva.
     *
     * @access  protected
     * @return  void
     */
    protected function clean_sat_audit_data()
    {
        $tables = [
            'core_purchase_cfdi_line_mappings',
            'core_sat_payment_details',
            'core_sat_cfdi_details',
            'core_sat_cfdi_relations',
            'core_sat_cfdi_events',
            'core_sat_packages',
            'core_sat_sync_requests',
            'core_sat_cfdi',
            'core_fiscal_documents',
            'core_sat_catalog_sync_logs',
            'core_sat_credentials',
            'core_sat_config',
            'core_audit_logs',
        ];

        \DB::query('SET FOREIGN_KEY_CHECKS=0')->execute();
        foreach ($tables as $table) {
            if (!$this->table_exists($table)) {
                continue;
            }

            \DB::query('DELETE FROM `'.$table.'`')->execute();
            $this->reset_increment($table);
        }
        \DB::query('SET FOREIGN_KEY_CHECKS=1')->execute();

        echo "\n - Datos SAT/CFDI y auditoria limpiados.\n";
    }

    /**
     * SEED SET FRONTEND
     *
     * Siembra contenido publico basado en setsolucionesti.com.
     *
     * @access  protected
     * @return  void
     */
    protected function seed_set_frontend()
    {
        $this->assert_frontend_schema();
        $this->clear_frontend_content();

        $theme_id = $this->insert('core_frontend_themes', [
            'code' => 'set_soluciones_ti',
            'name' => 'SET Soluciones TI',
            'layout_key' => 'set_soluciones_ti',
            'color_primary' => '#ff822b',
            'color_secondary' => '#333333',
            'color_accent' => '#ff822b',
            'color_background' => '#ffffff',
            'color_surface' => '#f7f7f7',
            'color_text' => '#333333',
            'color_muted' => '#777777',
            'font_family' => 'Ubuntu, Arial, Helvetica, sans-serif',
            'heading_font_family' => 'Ubuntu, Arial, Helvetica, sans-serif',
            'logo_path' => 'assets/uploads/frontend/set/set-logo.png',
            'favicon_path' => 'assets/uploads/frontend/set/set-favicon.png',
            'header_style' => 'standard',
            'footer_style' => 'standard',
            'custom_css' => $this->set_custom_css(),
            'site_name' => 'SET Soluciones TI',
            'seo_title_suffix' => 'SET Soluciones TI',
            'default_seo_description' => 'Soluciones tecnologicas, soporte tecnico, diseno web y renta de impresoras en Jalisco.',
            'og_image_path' => 'assets/uploads/frontend/set/set-logo.png',
            'robots' => 'index,follow',
            'is_active' => 1,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $home_id = $this->page('Soluciones tecnologicas para tu empresa', 'inicio', 'home', 'SET Soluciones TI', 'Soluciones tecnologicas, soporte tecnico, diseno web y renta de impresoras.', 1);
        $about_id = $this->page('Nosotros', 'empresa', 'content', 'Nosotros | SET Soluciones TI', 'Empresa enfocada en innovacion, excelencia y soluciones tecnologicas.', 0);
        $services_id = $this->page('Servicios', 'servicios', 'content', 'Servicios | SET Soluciones TI', 'Diseno web, soporte tecnico y renta de impresoras.', 0);
        $contact_id = $this->page('Contacto', 'contacto', 'content', 'Contacto | SET Soluciones TI', 'Contacta a SET Soluciones TI en San Pedro Tlaquepaque, Jalisco.', 0);

        $slider_id = $this->insert('core_frontend_sliders', [
            'code' => 'set_home_main',
            'name' => 'SET inicio',
            'location' => 'home',
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->insert('core_frontend_slider_items', [
            'slider_id' => $slider_id,
            'title' => 'SET Soluciones TI',
            'subtitle' => 'Tecnologia, soporte y servicios para que tu negocio opere con confianza.',
            'image_path' => '',
            'button_text' => 'Conoce nuestros servicios',
            'button_url' => \Uri::create('servicios'),
            'sort_order' => 10,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->section($home_id, 'home_intro', 'content', 'Soluciones tecnologicas con enfoque empresarial', 'Innovacion, servicio y continuidad operativa.', '<p>Ayudamos a empresas y equipos de trabajo a resolver necesidades de tecnologia con soporte tecnico, servicios web y renta de impresoras. Nuestro enfoque es claro: soluciones practicas, atencion cercana y herramientas que si aportan valor al negocio.</p>', '', 10);
        $this->section($home_id, 'home_services', 'feature_grid', 'Servicios principales', 'Tres lineas base para cubrir operacion, presencia digital e impresion.', 'Diseno Web|Soporte Tecnico|Renta de Impresoras', '', 20);
        $this->section($home_id, 'home_cta', 'cta', 'Listo para mejorar tu infraestructura tecnologica', 'Cuentanos que necesitas y te orientamos.', '<p>Podemos ayudarte a elegir la mejor solucion para tu empresa, desde soporte puntual hasta servicios recurrentes.</p>', '', 30, [
            'button_text' => 'Contactar',
            'button_url' => \Uri::create('contacto'),
        ]);

        $this->section($about_id, 'about_content', 'content', 'Nosotros', 'Pasion por la innovacion y compromiso con la excelencia.', $this->about_content(), '', 10);
        $this->section($about_id, 'about_values', 'feature_grid', 'Nuestros valores', 'La forma en que trabajamos con clientes y aliados.', 'Innovacion|Integridad|Excelencia|Colaboracion|Responsabilidad social y ambiental|Orientacion al cliente', '', 20);

        $this->section($services_id, 'services_cards', 'download_cards', 'Servicios', 'Soluciones para presencia digital, soporte y operacion de impresion.', '<p>Elige el servicio que mejor se adapte a tu etapa actual. Podemos iniciar con una necesidad puntual y crecer hacia un esquema recurrente.</p>', '', 10, [
            'items' => [
                ['title' => 'Diseno Web', 'url' => '#diseno-web', 'description' => 'Sitios atractivos, funcionales y listos para crecer con tu negocio.', 'label' => 'Ver servicio'],
                ['title' => 'Soporte Tecnico', 'url' => '#soporte-tecnico', 'description' => 'Atencion tecnica para mantener tus equipos trabajando correctamente.', 'label' => 'Ver servicio'],
                ['title' => 'Renta de Impresoras', 'url' => '#renta-impresoras', 'description' => 'Equipos confiables, toners y asesoria para elegir la mejor opcion.', 'label' => 'Ver servicio'],
            ],
        ]);
        $this->section($services_id, 'services_detail', 'content', 'Diseno Web, Soporte Tecnico y Renta de Impresoras', 'Servicios inspirados en la pagina actual, listos para administrarse desde Core-App.', $this->services_content(), '', 20);

        $this->section($contact_id, 'contact_info', 'contact_info', 'Contacto', 'Para preguntas, comentarios e inquietudes, completa el formulario.', $this->contact_content(), '', 10);

        $menu_id = $this->insert('core_frontend_menus', [
            'code' => 'set_main_menu',
            'name' => 'Menu SET',
            'location' => 'header',
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $this->menu_item($menu_id, 'Inicio', '/', 'page', $home_id, 10);
        $this->menu_item($menu_id, 'Nosotros', '/empresa', 'page', $about_id, 20);
        $this->menu_item($menu_id, 'Servicios', '/servicios', 'page', $services_id, 30);
        $this->menu_item($menu_id, 'Contacto', '/contacto', 'page', $contact_id, 40);

        $this->footer_columns();

        echo "\n - Frontend SET sembrado. Tema #".$theme_id."\n";
    }

    protected function page($title, $slug, $type, $seo_title, $seo_description, $is_home)
    {
        return $this->insert('core_frontend_pages', [
            'title' => $title,
            'slug' => $slug,
            'page_type' => $type,
            'template_key' => 'default',
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'published' => 1,
            'is_home' => $is_home,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
    }

    protected function section($page_id, $key, $type, $title, $subtitle, $content, $media, $sort, array $settings = [])
    {
        return $this->insert('core_frontend_sections', [
            'page_id' => $page_id,
            'section_key' => $key,
            'section_type' => $type,
            'title' => $title,
            'subtitle' => $subtitle,
            'content' => $content,
            'media_path' => $media,
            'target_type' => '',
            'target_id' => 0,
            'settings_json' => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'sort_order' => $sort,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
    }

    protected function menu_item($menu_id, $label, $url, $type, $target_id, $sort)
    {
        return $this->insert('core_frontend_menu_items', [
            'menu_id' => $menu_id,
            'parent_id' => 0,
            'label' => $label,
            'url' => $url,
            'target_type' => $type,
            'target_id' => $target_id,
            'sort_order' => $sort,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
    }

    protected function footer_columns()
    {
        $this->insert('core_frontend_footer_columns', [
            'title' => 'SET Soluciones TI',
            'column_type' => 'text',
            'icon' => '',
            'url' => '',
            'content' => 'Soluciones tecnologicas, soporte tecnico, diseno web y renta de impresoras para empresas.',
            'settings_json' => '[]',
            'sort_order' => 10,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->insert('core_frontend_footer_columns', [
            'title' => 'Secciones',
            'column_type' => 'links',
            'icon' => '',
            'url' => '',
            'content' => '',
            'settings_json' => json_encode(['items' => [
                ['label' => 'Nosotros', 'url' => '/empresa'],
                ['label' => 'Servicios', 'url' => '/servicios'],
                ['label' => 'Contacto', 'url' => '/contacto'],
            ]], JSON_UNESCAPED_UNICODE),
            'sort_order' => 20,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->insert('core_frontend_footer_columns', [
            'title' => 'Contacto',
            'column_type' => 'contact',
            'icon' => '',
            'url' => '',
            'content' => '',
            'settings_json' => json_encode(['items' => [
                ['label' => 'San Pedro Tlaquepaque, Jalisco', 'icon' => 'bi bi-geo-alt', 'url' => ''],
                ['label' => 'ventasenlinea@setsolucionesti.com', 'icon' => 'bi bi-envelope', 'url' => 'mailto:ventasenlinea@setsolucionesti.com'],
                ['label' => 'contacto@setsolucionesti.com', 'icon' => 'bi bi-envelope', 'url' => 'mailto:contacto@setsolucionesti.com'],
            ]], JSON_UNESCAPED_UNICODE),
            'sort_order' => 30,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->insert('core_frontend_footer_columns', [
            'title' => 'Redes',
            'column_type' => 'social',
            'icon' => '',
            'url' => '',
            'content' => '',
            'settings_json' => json_encode(['items' => [
                ['label' => 'Facebook', 'icon' => 'fab fa-facebook-f', 'url' => 'https://www.facebook.com/setsolucionesti'],
                ['label' => 'Instagram', 'icon' => 'fab fa-instagram', 'url' => 'https://www.instagram.com/setsolucionesti/'],
            ]], JSON_UNESCAPED_UNICODE),
            'sort_order' => 40,
            'active' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
    }

    protected function clear_frontend_content()
    {
        \DB::query('SET FOREIGN_KEY_CHECKS=0')->execute();
        foreach (['core_frontend_footer_columns', 'core_frontend_menu_items', 'core_frontend_menus', 'core_frontend_banners', 'core_frontend_slider_items', 'core_frontend_sliders', 'core_frontend_sections', 'core_frontend_pages', 'core_frontend_themes'] as $table) {
            if ($this->table_exists($table)) {
                \DB::query('DELETE FROM `'.$table.'`')->execute();
                $this->reset_increment($table);
            }
        }
        \DB::query('SET FOREIGN_KEY_CHECKS=1')->execute();
    }

    protected function about_content()
    {
        return '<p>En nuestra empresa nos definimos por nuestra pasion por la innovacion y el compromiso con la excelencia en todo lo que hacemos.</p>'
            .'<p>Nuestra mision es proporcionar soluciones tecnologicas innovadoras y de vanguardia que satisfagan las necesidades de nuestros clientes y superen sus expectativas. Buscamos construir relaciones duraderas basadas en confianza, servicio y resultados.</p>'
            .'<p>Queremos ser reconocidos como una primera opcion para empresas que necesitan productos y servicios de calidad superior, capaces de mejorar y transformar positivamente sus operaciones.</p>'
            .'<p><strong>Innovacion:</strong> buscamos constantemente nuevas formas de mejorar y ofrecer soluciones tecnologicas avanzadas.</p>'
            .'<p><strong>Integridad:</strong> actuamos con honestidad, transparencia y etica en nuestras relaciones comerciales.</p>'
            .'<p><strong>Excelencia:</strong> cuidamos la calidad de nuestros servicios y la atencion al cliente en cada interaccion.</p>'
            .'<p><strong>Colaboracion:</strong> fomentamos el trabajo en equipo y valoramos las ideas que ayudan a lograr mejores resultados.</p>'
            .'<p><strong>Responsabilidad social y ambiental:</strong> buscamos operar de forma responsable y contribuir positivamente a nuestro entorno.</p>'
            .'<p><strong>Orientacion al cliente:</strong> ponemos las necesidades de nuestros clientes en el centro de cada decision.</p>';
    }

    protected function services_content()
    {
        return '<div id="diseno-web"><h3>Diseno Web</h3><p>Si quieres destacar en linea con una presencia web profesional, podemos ayudarte a crear una pagina atractiva, funcional y alineada con la esencia de tu negocio.</p></div>'
            .'<div id="soporte-tecnico"><h3>Soporte Tecnico</h3><p>Te ayudamos a maximizar el rendimiento de tus equipos y mantener la operacion sin interrupciones mediante soporte tecnico y atencion especializada.</p></div>'
            .'<div id="renta-impresoras"><h3>Renta de Impresoras</h3><p>Ofrecemos renta de impresoras para oficina, seleccionando equipos y consumibles de acuerdo con tus necesidades operativas.</p></div>';
    }

    protected function contact_content()
    {
        return '<h3>Preguntas</h3><p>Para todo tipo de preguntas, comentarios e inquietudes, por favor completa el formulario.</p>'
            .'<h3>Matriz</h3><p><strong>SET Soluciones TI</strong><br>San Pedro Tlaquepaque, Jalisco</p>'
            .'<h3>Correo</h3><p><a href="mailto:ventasenlinea@setsolucionesti.com">ventasenlinea@setsolucionesti.com</a><br><a href="mailto:contacto@setsolucionesti.com">contacto@setsolucionesti.com</a></p>';
    }

    protected function set_custom_css()
    {
        return '.layout-set_soluciones_ti .site-header{position:relative;background:#fff;border-bottom:0;box-shadow:none;}'
            .'.layout-set_soluciones_ti .site-nav{min-height:106px;align-items:center;}'
            .'.layout-set_soluciones_ti .brand img{max-height:74px;}'
            .'.layout-set_soluciones_ti .menu a{font-weight:700;color:#555;text-transform:none;}'
            .'.layout-set_soluciones_ti .menu a:hover{color:#ff822b;}'
            .'.layout-set_soluciones_ti .account-menu .cart-link{display:none;}'
            .'.layout-set_soluciones_ti .account-menu a{border-color:#e5e7eb;color:#555;}'
            .'.layout-set_soluciones_ti .account-menu a.primary{background:#ff822b;border-color:#ff822b;color:#fff;}'
            .'.layout-set_soluciones_ti .front-hero{min-height:420px;background:linear-gradient(135deg,#3f3f46,#111827);}'
            .'.layout-set_soluciones_ti .front-hero:after{background:linear-gradient(90deg,rgba(0,0,0,.74),rgba(0,0,0,.2));}'
            .'.layout-set_soluciones_ti .front-hero h1{color:#fff;}'
            .'.layout-set_soluciones_ti .front-hero .button,.layout-set_soluciones_ti .section-link,.layout-set_soluciones_ti .contact-form button{background:#ff822b;border-color:#ff822b;color:#fff;}'
            .'.layout-set_soluciones_ti h2,.layout-set_soluciones_ti h3{color:#ff822b;}'
            .'.layout-set_soluciones_ti .section-copy h2{display:inline-block;border-bottom:5px solid #ff822b;padding-bottom:8px;color:#666;}'
            .'.layout-set_soluciones_ti .section-band{background:#fff;}'
            .'.layout-set_soluciones_ti .feature-grid,.layout-set_soluciones_ti .download-grid{grid-template-columns:repeat(auto-fit,minmax(260px,1fr));}'
            .'.layout-set_soluciones_ti .feature-item,.layout-set_soluciones_ti .download-item,.layout-set_soluciones_ti .contact-card{border:0;border-radius:12px;box-shadow:0 0 15px rgba(0,0,0,.15);}'
            .'.layout-set_soluciones_ti .download-item:hover{outline:1px solid #ff822b;}'
            .'.layout-set_soluciones_ti .site-footer{border-top-color:#ff822b;}'
            .'.layout-set_soluciones_ti .footer-contact-item i{color:#ff822b;}';
    }

    protected function assert_frontend_schema()
    {
        foreach (['core_frontend_themes', 'core_frontend_pages', 'core_frontend_sections', 'core_frontend_sliders', 'core_frontend_slider_items', 'core_frontend_menus', 'core_frontend_menu_items', 'core_frontend_footer_columns'] as $table) {
            if (!$this->table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones frontend: '.$table);
            }
        }
    }

    protected function insert($table, array $data)
    {
        list($id) = \DB::insert($table)->set($data)->execute();
        return (int) $id;
    }

    protected function table_exists($table)
    {
        return \DBUtil::table_exists($table);
    }

    protected function reset_increment($table)
    {
        try {
            \DB::query('ALTER TABLE `'.$table.'` AUTO_INCREMENT = 1')->execute();
        } catch (\Exception $e) {
            // Algunas tablas o motores pueden no permitir reiniciar contador; no bloquea la limpieza.
        }
    }
}
