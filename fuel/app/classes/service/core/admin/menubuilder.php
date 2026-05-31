<?php

/**
 * SERVICE CORE_ADMIN_MENUBUILDER
 *
 * Construye la estructura del menu lateral administrativo.
 *
 * @package  app
 */
class Service_Core_Admin_MenuBuilder
{
    /**
     * BUILD
     *
     * GENERA NODOS DEL SIDEBAR CONSERVANDO PERMISOS Y ESTADO ACTIVO.
     *
     * @access  public
     * @return  Array
     */
    public static function build(array $menu)
    {
        $builder = new static();
        return $builder->items($builder->normalize_menu($menu));
    }

    /**
     * ITEMS
     *
     * DEFINE EL MENU ADMINISTRATIVO POR AREAS ERP.
     *
     * @access  protected
     * @return  Array
     */
    protected function items(array $menu)
    {
        $segment = (string) \Uri::segment(2);
        $subsegment = (string) \Uri::segment(3);
        $sales_view = \Input::get('view', 'quotes');
        $portal_section = \Input::get('section', 'user_links');
        $catalog_group = \Input::get('group', 'general');

        $sales_open = $segment === 'sales';
        $portal_open = $segment === 'portals';
        $catalog_open = $segment === 'catalogs';

        return [
            $this->header('INICIO'),
            $this->item('Inicio', 'bi bi-speedometer2', \Uri::create('admin'), $segment === ''),

            $this->header('COMERCIAL', $menu['commerce'] || $menu['sales'] || $menu['crm'] || $menu['commissions']),
            $this->item('Productos y precios', 'bi bi-box-seam', \Uri::create('admin/commerce'), $segment === 'commerce', $menu['commerce']),
            $this->tree('Ventas', 'bi bi-receipt', $sales_open, $menu['sales'], [
                $this->item('Cotizaciones', 'bi bi-circle', \Uri::create('admin/sales', [], ['view' => 'quotes']), $segment === 'sales' && $sales_view === 'quotes', $menu['sales']),
                $this->item('Pedidos', 'bi bi-circle', \Uri::create('admin/sales', [], ['view' => 'orders']), $segment === 'sales' && $sales_view === 'orders', $menu['sales']),
                $this->item('Entregas', 'bi bi-circle', \Uri::create('admin/sales', [], ['view' => 'deliveries']), $segment === 'sales' && $sales_view === 'deliveries', $menu['sales']),
            ], false, 'bi bi-chevron-left'),
            $this->item('CRM comercial', 'bi bi-people', \Uri::create('admin/crm'), $segment === 'crm', $menu['crm']),
            $this->item('Vendedores y comisiones', 'bi bi-cash-coin', \Uri::create('admin/commissions'), $segment === 'commissions', $menu['commissions']),

            $this->header('OPERACI&Oacute;N', $menu['inventory'] || $menu['purchases'] || $menu['documents'] || $menu['helpdesk'] || $menu['calendar']),
            $this->item('Inventario', 'bi bi-boxes', \Uri::create('admin/inventory'), $segment === 'inventory', $menu['inventory']),
            $this->item('Compras', 'bi bi-cart-check', \Uri::create('admin/purchases'), $segment === 'purchases', $menu['purchases']),
            $this->item('Documentos', 'bi bi-folder2-open', \Uri::create('admin/documents'), $segment === 'documents', $menu['documents']),
            $this->item('Helpdesk', 'bi bi-life-preserver', \Uri::create('admin/helpdesk'), $segment === 'helpdesk', $menu['helpdesk']),
            $this->item('Calendario', 'bi bi-calendar3', \Uri::create('admin/calendar'), $segment === 'calendar', $menu['calendar']),

            $this->header('RECURSOS HUMANOS', $menu['hr']),
            $this->item('Empleados y n&oacute;mina', 'bi bi-person-badge', \Uri::create('admin/hr'), $segment === 'hr', $menu['hr']),

            $this->header('FINANZAS', $menu['billing'] || $menu['receivables'] || $menu['payables'] || $menu['payments'] || $menu['treasury'] || $menu['budgets']),
            $this->item('Facturaci&oacute;n CFDI', 'bi bi-receipt-cutoff', \Uri::create('admin/billing'), $segment === 'billing', $menu['billing']),
            $this->item('Cuentas por cobrar', 'bi bi-wallet2', \Uri::create('admin/receivables'), $segment === 'receivables', $menu['receivables']),
            $this->item('Cuentas por pagar', 'bi bi-receipt-cutoff', \Uri::create('admin/payables'), $segment === 'payables', $menu['payables']),
            $this->item('Bancos y pagos', 'bi bi-bank', \Uri::create('admin/payments'), $segment === 'payments', $menu['payments']),
            $this->item('Tesorer&iacute;a', 'bi bi-graph-up-arrow', \Uri::create('admin/treasury'), $segment === 'treasury', $menu['treasury']),
            $this->item('Presupuestos', 'bi bi-clipboard-data', \Uri::create('admin/budgets'), $segment === 'budgets', $menu['budgets']),

            $this->header('FISCAL', $menu['fiscal'] || $menu['sat'] || $menu['catalogs']),
            $this->item('Panel fiscal', 'bi bi-speedometer', \Uri::create('admin/fiscal'), $segment === 'fiscal' && $subsegment === '', $menu['fiscal']),
            $this->item('IVA mensual', 'bi bi-percent', \Uri::create('admin/fiscal/vat'), $segment === 'fiscal' && $subsegment === 'vat', $menu['fiscal']),
            $this->item('Preparacion DIOT', 'bi bi-file-earmark-spreadsheet', \Uri::create('admin/fiscal/diot'), $segment === 'fiscal' && $subsegment === 'diot', $menu['fiscal']),
            $this->item('SAT y CFDI', 'bi bi-receipt', \Uri::create('admin/sat'), $segment === 'sat' && $subsegment === '', $menu['sat']),
            $this->item('Auditoria SAT', 'bi bi-search', \Uri::create('admin/cfdi'), $segment === 'cfdi', $menu['sat']),
            $this->item('Catalogos SAT', 'bi bi-collection', \Uri::create('admin/sat/catalogs'), $segment === 'sat' && $subsegment === 'catalogs', $menu['catalogs']),

            $this->header('CONTABILIDAD', $menu['accounting'] || $menu['catalogs']),
            $this->item('Contabilidad', 'bi bi-journal-check', \Uri::create('admin/accounting'), $segment === 'accounting', $menu['accounting']),
            $this->tree('Catalogos base', 'bi bi-collection', $catalog_open, $menu['catalogs'], [
                $this->item('Generales', 'bi bi-grid', \Uri::create('admin/catalogs').'?group=general', $catalog_open && $catalog_group === 'general'),
                $this->item('Bancos y monedas', 'bi bi-currency-exchange', \Uri::create('admin/catalogs').'?group=financial', $catalog_open && $catalog_group === 'financial'),
                $this->item('Impuestos y retenciones', 'bi bi-percent', \Uri::create('admin/catalogs').'?group=fiscal', $catalog_open && $catalog_group === 'fiscal'),
                $this->item('Logistica', 'bi bi-truck', \Uri::create('admin/catalogs').'?group=logistics', $catalog_open && $catalog_group === 'logistics'),
            ], true),

            $this->header('RELACIONES Y PORTALES', $menu['parties'] || $menu['portals']),
            $this->item('Clientes y proveedores', 'bi bi-person-vcard', \Uri::create('admin/parties'), $segment === 'parties', $menu['parties']),
            $this->tree('Portales', 'bi bi-door-open', $portal_open, $menu['portals'], [
                $this->item('Accesos', 'bi bi-person-lock', \Uri::create('admin/portals').'?section=user_links', $portal_open && $portal_section === 'user_links'),
                $this->item('Perfiles', 'bi bi-door-open', \Uri::create('admin/portals').'?section=profiles', $portal_open && $portal_section === 'profiles'),
                $this->item('Branding', 'bi bi-palette', \Uri::create('admin/portals').'?section=branding', $portal_open && $portal_section === 'branding'),
            ], true),

            $this->header('SITIO E INTEGRACIONES', $menu['frontend'] || $menu['web'] || $menu['legal'] || $menu['communications'] || $menu['integrations']),
            $this->item('Sitio publico', 'bi bi-window', \Uri::create('admin/frontend'), $segment === 'frontend', $menu['frontend']),
            $this->item('Web y tracking', 'bi bi-globe2', \Uri::create('admin/web'), $segment === 'web', $menu['web']),
            $this->item('Legal y privacidad', 'bi bi-file-earmark-check', \Uri::create('admin/legal'), $segment === 'legal', $menu['legal']),
            $this->item('Correos y avisos', 'bi bi-chat-square-dots', \Uri::create('admin/communications'), $segment === 'communications', $menu['communications']),
            $this->item('Integraciones', 'bi bi-plug', \Uri::create('admin/integrations'), $segment === 'integrations', $menu['integrations']),

            $this->header('ADMINISTRACI&Oacute;N', $menu['users'] || $menu['acl'] || $menu['config'] || $menu['audit'] || $menu['help']),
            $this->item('Usuarios', 'bi bi-people', \Uri::create('admin/users'), $segment === 'users', $menu['users']),
            $this->item('Grupos y Permisos', 'bi bi-key text-danger', \Uri::create('admin/permissions'), $segment === 'permissions', $menu['acl']),
            $this->item('Configuraci&oacute;n', 'bi bi-gear', \Uri::create('admin/config'), $segment === 'config', $menu['config']),
            $this->item('Auditoria', 'bi bi-shield-check', \Uri::create('admin/audit'), $segment === 'audit', $menu['audit']),
            $this->item('Ayuda', 'bi bi-question-circle', \Uri::create('admin/help'), $segment === 'help', $menu['help']),
        ];
    }

    protected function normalize_menu(array $menu)
    {
        $has_fiscal_permission = array_key_exists('fiscal', $menu);

        $keys = [
            'commerce', 'sales', 'commissions', 'inventory', 'purchases',
            'billing', 'receivables', 'payables', 'payments', 'treasury', 'budgets', 'accounting', 'hr',
            'parties', 'crm', 'portals', 'documents', 'helpdesk', 'calendar',
            'fiscal', 'sat', 'catalogs', 'web', 'legal', 'communications', 'integrations', 'frontend',
            'audit', 'help', 'users', 'acl', 'config',
        ];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $menu)) {
                $menu[$key] = false;
            }
            $menu[$key] = (bool) $menu[$key];
        }

        if (!$has_fiscal_permission) {
            $menu['fiscal'] = $menu['sat'];
        }

        return $menu;
    }

    protected function header($label, $visible = true)
    {
        return [
            'type' => 'header',
            'label' => $label,
            'visible' => (bool) $visible,
        ];
    }

    protected function item($label, $icon, $url, $active = false, $visible = true)
    {
        return [
            'type' => 'item',
            'label' => $label,
            'icon' => $icon,
            'url' => $url,
            'active' => (bool) $active,
            'visible' => (bool) $visible,
        ];
    }

    protected function tree($label, $icon, $open, $visible, array $children, $force_display_style = false, $right_icon = 'fas fa-angle-left')
    {
        return [
            'type' => 'tree',
            'label' => $label,
            'icon' => $icon,
            'open' => (bool) $open,
            'active' => (bool) $open,
            'visible' => (bool) $visible,
            'children' => $children,
            'force_display_style' => (bool) $force_display_style,
            'right_icon' => $right_icon,
        ];
    }
}
