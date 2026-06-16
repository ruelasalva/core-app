<?php

/**
 * SERVICE CORE_WORKSPACE_COMMANDPALETTE
 *
 * Busqueda segura para el Command Palette del Workspace.
 */
class Service_Core_Workspace_CommandPalette
{
    const LIMIT = 20;

    public function search(array $context, $query = '')
    {
        $query = trim((string) $query);
        $normalized_query = $this->normalize($query);
        $results = [];

        foreach ($this->quick_action_results($context) as $item) {
            $this->append_if_match($results, $item, $normalized_query);
        }

        foreach ($this->menu_results($context) as $item) {
            $this->append_if_match($results, $item, $normalized_query);
        }

        foreach ($this->widget_results($context) as $item) {
            $this->append_if_match($results, $item, $normalized_query);
        }

        usort($results, function ($a, $b) {
            if ($a['_score'] === $b['_score']) {
                return strcmp($a['title'], $b['title']);
            }

            return ($a['_score'] > $b['_score']) ? -1 : 1;
        });

        $results = array_slice($results, 0, self::LIMIT);
        foreach ($results as &$result) {
            unset($result['_score']);
        }

        return $results;
    }

    protected function append_if_match(array &$results, array $item, $normalized_query)
    {
        $score = $this->score($item, $normalized_query);
        if ($score < 1) {
            return;
        }

        $item['_score'] = $score;
        $results[] = $item;
    }

    protected function quick_action_results(array $context)
    {
        $items = [];
        foreach ((new \Service_Core_Workspace_QuickActions())->allowed($context) as $action) {
            $route = (string) \Arr::get($action, 'route', '');
            if ($route === '') {
                continue;
            }

            $items[] = [
                'type' => 'quick_action',
                'code' => (string) \Arr::get($action, 'code', ''),
                'title' => (string) \Arr::get($action, 'title', ''),
                'description' => 'Accion rapida del Workspace',
                'icon' => (string) \Arr::get($action, 'icon', 'bi bi-lightning'),
                'url' => $this->url($route),
                'permission_key' => (string) \Arr::get($action, 'permission_key', ''),
                'category' => (string) \Arr::get($action, 'category', 'system'),
            ];
        }

        return $items;
    }

    protected function widget_results(array $context)
    {
        $items = [];
        foreach ((new \Service_Core_Workspace_WidgetCatalog())->allowed($context) as $widget) {
            $items[] = [
                'type' => 'widget',
                'code' => (string) \Arr::get($widget, 'code', ''),
                'title' => (string) \Arr::get($widget, 'title', ''),
                'description' => (string) \Arr::get($widget, 'description', ''),
                'icon' => (string) \Arr::get($widget, 'icon', 'bi bi-grid'),
                'url' => $this->url('admin/workspace'),
                'permission_key' => (string) \Arr::get($widget, 'permission_key', 'workspace.access[view]'),
                'category' => (string) \Arr::get($widget, 'category', 'system'),
            ];
        }

        return $items;
    }

    protected function menu_results(array $context)
    {
        $items = [];
        foreach ($this->menu_catalog() as $item) {
            $permission = (string) \Arr::get($item, 'permission_key', '');
            if (!$this->allowed($permission, $context)) {
                continue;
            }

            $item['url'] = $this->url($item['route']);
            unset($item['route']);
            $items[] = $item;
        }

        return $items;
    }

    protected function allowed($permission, array $context)
    {
        if (!empty($context['is_super_admin'])) {
            return true;
        }

        if ($permission === '') {
            return true;
        }

        return \Auth::has_access($permission);
    }

    protected function menu_catalog()
    {
        return [
            $this->menu_item('workspace', 'Workspace', 'Centro de trabajo personal', 'bi bi-grid', 'admin/workspace', 'workspace.access[view]', 'system'),
            $this->menu_item('dashboard', 'Inicio', 'Panel administrativo principal', 'bi bi-speedometer2', 'admin', '', 'system'),
            $this->menu_item('sales', 'Ventas', 'Cotizaciones, pedidos y entregas', 'bi bi-receipt', 'admin/sales', 'sales.access[view]', 'commercial'),
            $this->menu_item('customers', 'Clientes', 'Directorio y gestion comercial', 'bi bi-people', 'admin/parties?section=customers', 'customers.access[view]', 'commercial'),
            $this->menu_item('crm', 'CRM', 'Seguimiento comercial y oportunidades', 'bi bi-kanban', 'admin/crm', 'crm.access[view]', 'commercial'),
            $this->menu_item('commerce', 'Productos', 'Catalogo comercial y productos', 'bi bi-box-seam', 'admin/commerce', 'commerce.access[view]', 'commercial'),
            $this->menu_item('supplierimport', 'Importacion proveedor', 'Carga y revision de catalogos de proveedor', 'bi bi-upload', 'admin/supplierimport', 'supplierimport.access[view]', 'commercial'),
            $this->menu_item('purchases', 'Compras', 'Ordenes de compra, facturas y recepciones', 'bi bi-cart-check', 'admin/purchases', 'purchases.access[view]', 'operation'),
            $this->menu_item('inventory', 'Inventario', 'Existencias y movimientos', 'bi bi-boxes', 'admin/inventory', 'inventory.access[view]', 'operation'),
            $this->menu_item('documents', 'Documentos', 'Gestion documental administrada', 'bi bi-file-earmark-text', 'admin/documents', 'documents.access[view]', 'system'),
            $this->menu_item('contracts', 'Contratos', 'Contratos, documentos y eventos', 'bi bi-journal-text', 'admin/contracts', 'contracts.access[view]', 'system'),
            $this->menu_item('helpdesk', 'Tickets', 'Mesa de ayuda y soporte', 'bi bi-life-preserver', 'admin/helpdesk', 'helpdesk.access[view]', 'support'),
            $this->menu_item('calendar', 'Calendario', 'Agenda operativa', 'bi bi-calendar-event', 'admin/calendar', 'calendar.access[view]', 'system'),
            $this->menu_item('billing', 'Facturacion', 'Facturas, timbrado y complementos', 'bi bi-file-earmark-check', 'admin/billing', 'billing.access[view]', 'finance'),
            $this->menu_item('sat', 'SAT', 'Panel SAT y descargas', 'bi bi-receipt', 'admin/sat', 'sat.access[view]', 'fiscal'),
            $this->menu_item('cfdi', 'CFDI', 'Clasificacion y auditoria CFDI', 'bi bi-filetype-xml', 'admin/cfdi', 'cfdi.access[view]', 'fiscal'),
            $this->menu_item('fiscal', 'Fiscal', 'IVA, DIOT y cierre fiscal', 'bi bi-bank', 'admin/fiscal', 'fiscal.access[view]', 'fiscal'),
            $this->menu_item('accounting', 'Contabilidad', 'Catalogo, polizas y periodos', 'bi bi-calculator', 'admin/accounting', 'accounting.access[view]', 'finance'),
            $this->menu_item('frontend', 'Frontend CMS', 'Contenido del sitio publico', 'bi bi-window', 'admin/frontend', 'frontend.access[view]', 'web'),
            $this->menu_item('users', 'Usuarios', 'Usuarios, grupos y permisos', 'bi bi-person-gear', 'admin/users', 'user.access[view]', 'system'),
        ];
    }

    protected function menu_item($code, $title, $description, $icon, $route, $permission, $category)
    {
        return [
            'type' => 'menu',
            'code' => $code,
            'title' => $title,
            'description' => $description,
            'icon' => $icon,
            'route' => $route,
            'permission_key' => $permission,
            'category' => $category,
        ];
    }

    protected function score(array $item, $normalized_query)
    {
        if ($normalized_query === '') {
            return $this->base_type_score($item);
        }

        $title = $this->normalize((string) \Arr::get($item, 'title', ''));
        $code = $this->normalize((string) \Arr::get($item, 'code', ''));
        $category = $this->normalize((string) \Arr::get($item, 'category', ''));
        $description = $this->normalize((string) \Arr::get($item, 'description', ''));
        $score = 0;

        if ($title === $normalized_query || $code === $normalized_query) {
            $score += 100;
        }
        if ($this->starts_with($title, $normalized_query) || $this->starts_with($code, $normalized_query)) {
            $score += 70;
        }
        if ($this->contains($title, $normalized_query) || $this->contains($code, $normalized_query)) {
            $score += 40;
        }
        if ($this->contains($category, $normalized_query)) {
            $score += 20;
        }
        if ($this->contains($description, $normalized_query)) {
            $score += 10;
        }

        return $score + ($score > 0 ? $this->base_type_score($item) : 0);
    }

    protected function base_type_score(array $item)
    {
        $type = (string) \Arr::get($item, 'type', '');
        if ($type === 'quick_action') {
            return 3;
        }
        if ($type === 'menu') {
            return 2;
        }

        return 1;
    }

    protected function normalize($value)
    {
        $value = trim((string) $value);
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);

        return strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
        ]);
    }

    protected function starts_with($value, $needle)
    {
        return $needle !== '' && strpos($value, $needle) === 0;
    }

    protected function contains($value, $needle)
    {
        return $needle !== '' && strpos($value, $needle) !== false;
    }

    protected function url($route)
    {
        $route = trim((string) $route);
        if ($route === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $route)) {
            return $route;
        }

        return rtrim(\Uri::base(false), '/').'/'.ltrim($route, '/');
    }
}
