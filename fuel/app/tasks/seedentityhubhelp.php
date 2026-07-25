<?php
namespace Fuel\Tasks;

class Seedentityhubhelp
{
    protected $created = array();
    protected $updated = array();

    public function run()
    {
        try {
            if (!\DBUtil::table_exists('core_knowledge_articles')) {
                throw new \RuntimeException('Falta la tabla core_knowledge_articles.');
            }

            foreach ($this->articles() as $article) {
                $this->upsert_article($article);
            }
            $this->print_summary();
            \Log::info('Seedentityhubhelp ejecutado.');
        } catch (\Exception $e) {
            \Log::error('Seedentityhubhelp: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function upsert_article(array $article)
    {
        $existing = \DB::select('id')
            ->from('core_knowledge_articles')
            ->where('code', '=', $article['code'])
            ->execute()
            ->current();

        $data = array(
            'code' => $article['code'],
            'title' => $article['title'],
            'category' => $article['category'],
            'summary' => $article['summary'],
            'content' => $this->sanitize_content($article['content']),
            'sort_order' => (int) $article['sort_order'],
            'active' => 1,
            'updated_at' => time(),
        );

        if ($existing) {
            \DB::update('core_knowledge_articles')
                ->set($data)
                ->where('id', '=', (int) $existing['id'])
                ->execute();
            $this->updated[] = $article['title'];
            return;
        }

        $data['created_at'] = time();
        \DB::insert('core_knowledge_articles')->set($data)->execute();
        $this->created[] = $article['title'];
    }

    protected function articles()
    {
        return array(
            $this->foundation_article(),
            $this->relationship_engine_article(),
            $this->timeline_engine_article(),
            $this->customer360_article(),
            $this->supplier360_article(),
        );
    }

    protected function foundation_article()
    {
        return array(
            'code' => 'business-entity-hub-foundation',
            'title' => 'Hub de Entidades',
            'category' => 'Hub de Entidades',
            'summary' => 'Base de normalizacion para consultar entidades, perfiles y relaciones del ERP de forma segura.',
            'sort_order' => 10,
            'content' => $this->content(),
        );
    }

    protected function content()
    {
        return ''
            .'<h3>Objetivo</h3>'
            .'<p>Hub de Entidades es una capa de lectura que normaliza clientes, proveedores, contratos, facturas, pagos, cotizaciones, pedidos, tickets, comunicaciones y documentos bajo un perfil comun. No reemplaza los modulos existentes y no modifica informacion operativa.</p>'
            .'<h4>Normalizacion</h4>'
            .'<p>Cada entidad se presenta con tipo, identificador, codigo logico, nombre, modulo, estado, activo, alcance de visibilidad, responsable, grupo, tercero principal, icono y color. Estos codigos son solo de visualizacion y no cambian folios ni numeradores existentes.</p>'
            .'<h4>Relaciones</h4>'
            .'<p>Las relaciones se leen desde estructuras existentes como enlaces de documentos, relaciones de contratos, conversaciones de comunicaciones y campos transversales de tercero. No existe una tabla universal nueva en esta fase.</p>'
            .'<h4>Seguridad</h4>'
            .'<p>La consulta usa permisos ORMAuth y servicios existentes de alcance como visibilidad de clientes y acceso a buzones. Si el sistema no puede validar una relacion, no la muestra.</p>'
            .'<h4>Uso futuro</h4>'
            .'<p>Esta base prepara modulos 360 para clientes, proveedores, rentas, activos y tableros ejecutivos. Es una fundacion de lectura; las siguientes fases podran agregar pantallas, linea de tiempo y resumenes sin duplicar logica en cada modulo.</p>'
            .'<h4>Validacion</h4>'
            .'<ul><li>Consultar solo usuarios con permiso <code>entityhub.access[view]</code>.</li><li>Verificar que usuarios comerciales solo vean clientes permitidos.</li><li>Verificar que conversaciones respeten cuentas asignadas.</li><li>Confirmar que no se muestran rutas fisicas de documentos.</li></ul>';
    }

    protected function relationship_engine_article()
    {
        return array(
            'code' => 'business-entity-hub-relationship-engine',
            'title' => 'Motor de Relaciones',
            'category' => 'Hub de Entidades',
            'summary' => 'Motor de lectura para descubrir relaciones entre entidades del ERP sin crear una tabla universal.',
            'sort_order' => 20,
            'content' => $this->relationship_engine_content(),
        );
    }

    protected function relationship_engine_content()
    {
        return ''
            .'<h3>Objetivo</h3>'
            .'<p>Motor de Relaciones agrega relaciones existentes entre entidades del ERP para preparar vistas 360 y analisis comerciales. Esta fase es solo lectura.</p>'
            .'<h4>Fuentes de datos</h4>'
            .'<ul><li>Enlaces de documentos.</li><li>Relaciones de contratos.</li><li>Conversaciones y enlaces de comunicaciones.</li><li>Tickets, facturas, pagos, cotizaciones, pedidos, oportunidades y actividades por tercero.</li><li>Perfiles de facturacion recurrente cuando existen.</li><li>Contratos de renta como marcador operativo para fases futuras.</li></ul>'
            .'<h4>Seguridad</h4>'
            .'<p>Cada relacion se valida con permisos ORMAuth y servicios de alcance existentes. Si el usuario no puede ver una relacion, el motor no muestra datos sensibles; solo registra que existe una relacion restringida cuando aplica.</p>'
            .'<h4>Por que no hay tabla universal</h4>'
            .'<p>La fase actual reutiliza las relaciones naturales de cada modulo. Esto evita duplicar informacion y reduce el riesgo de inconsistencias. Una tabla universal solo deberia evaluarse si futuras fases requieren relaciones manuales auditables.</p>'
            .'<h4>Uso futuro</h4>'
            .'<p>El motor sera la base para Vista 360 de Cliente, Vista 360 de Proveedor, rentas, activos, paneles embebidos y linea de tiempo. Las siguientes fases podran construir UI sobre esta capa sin cambiar CRM, Ventas, Contratos o Helpdesk.</p>'
            .'<h4>Validacion operativa</h4>'
            .'<ul><li>Probar <code>admin/entityhub/relationship_engine</code> con una entidad conocida.</li><li>Confirmar que usuarios sin permiso no vean entidades restringidas.</li><li>Confirmar que no se muestran rutas fisicas ni secretos.</li><li>Verificar que no se crean ni modifican relaciones.</li></ul>';
    }

    protected function timeline_engine_article()
    {
        return array(
            'code' => 'business-entity-hub-timeline-engine',
            'title' => 'Linea de Tiempo',
            'category' => 'Hub de Entidades',
            'summary' => 'Motor cronologico de lectura para ver eventos operativos relacionados con una entidad.',
            'sort_order' => 30,
            'content' => $this->timeline_engine_content(),
        );
    }

    protected function timeline_engine_content()
    {
        return ''
            .'<h3>Objetivo</h3>'
            .'<p>Linea de Tiempo construye una vista cronologica de eventos relacionados con una entidad del ERP. Esta fase es solo lectura y no permite editar, borrar ni crear eventos.</p>'
            .'<h4>Fuentes</h4>'
            .'<ul><li>Eventos de contratos.</li><li>Mensajes y conversaciones de comunicaciones.</li><li>Tickets de Helpdesk.</li><li>Documentos vinculados.</li><li>Facturas, pagos, cotizaciones y pedidos.</li><li>Oportunidades y actividades CRM.</li><li>Facturacion recurrente cuando existe.</li><li>Contratos de renta como marcador para fases futuras.</li></ul>'
            .'<h4>Seguridad</h4>'
            .'<p>Cada entrada se valida contra permisos, visibilidad de clientes y acceso a buzones. Si el usuario no puede ver el origen del evento, la entrada se oculta y solo se incrementa el contador de ocultas.</p>'
            .'<h4>No reemplaza auditoria</h4>'
            .'<p>La linea de tiempo muestra contexto operativo para navegacion y analisis. Auditoria sigue siendo el modulo responsable de historial tecnico, cambios de campos y trazabilidad de seguridad.</p>'
            .'<h4>Uso futuro</h4>'
            .'<p>Vista 360 de Cliente y Vista 360 de Proveedor podran usar este motor para mostrar historial sin duplicar consultas en cada modulo. Nuevas fuentes deben agregarse como lecturas seguras, no como cambios de negocio.</p>'
            .'<h4>Validacion</h4>'
            .'<ul><li>Probar <code>admin/entityhub/timeline</code> con una entidad conocida.</li><li>Confirmar que filtros de categoria y fechas funcionan.</li><li>Confirmar que no se exponen rutas fisicas, cuerpos completos de correo ni secretos.</li><li>Confirmar que CRM, Ventas, Contratos y Helpdesk no cambian su flujo.</li></ul>';
    }

    protected function customer360_article()
    {
        return array(
            'code' => 'business-entity-hub-customer-360',
            'title' => 'Vista 360 de Cliente',
            'category' => 'Hub de Entidades',
            'summary' => 'Vista de lectura para consultar resumen comercial, linea de tiempo, documentos, comunicaciones, tickets y contratos de un cliente.',
            'sort_order' => 40,
            'content' => $this->customer360_content(),
        );
    }

    protected function customer360_content()
    {
        return ''
            .'<h3>Objetivo</h3>'
            .'<p>Vista 360 de Cliente concentra informacion operativa de un cliente en una sola vista de lectura. Usa Hub de Entidades, Motor de Relaciones, Linea de Tiempo, CustomerVisibility y el panel embebido de Comunicaciones.</p>'
            .'<h4>Secciones</h4>'
            .'<ul><li>Busqueda de clientes permitidos por nombre, codigo o correo.</li><li>Resumen general del cliente y contacto principal.</li><li>Indicadores comerciales de cotizaciones, pedidos, facturas, pagos y saldos almacenados.</li><li>Linea de Tiempo reciente de eventos visibles.</li><li>Comunicaciones visibles segun cuentas asignadas.</li><li>Documentos con enlaces seguros de descarga.</li><li>Tickets, contratos y marcadores de rentas cuando existen.</li></ul>'
            .'<h4>Permisos</h4>'
            .'<p>Grupo 100 puede consultar todos los clientes. Usuarios comerciales respetan <code>CustomerVisibility</code> y permisos como <code>customers.access[view]</code>, <code>crm.access[view]</code>, <code>business.access[view]</code> o <code>parties.access[view]</code>.</p>'
            .'<h4>Seguridad</h4>'
            .'<p>La vista no modifica datos, no recalcula saldos, no muestra rutas fisicas, no expone secretos y no muestra buzones que el usuario no pueda consultar.</p>'
            .'<h4>Solucion de problemas</h4>'
            .'<ul><li>Si no carga, verificar que el usuario tenga permiso y que el cliente exista como <code>party_type=customer</code> activo.</li><li>Si no aparecen comunicaciones, revisar asignacion de cuentas de correo.</li><li>Si no aparecen documentos, confirmar que existan enlaces activos y que se usen endpoints de descarga.</li><li>Si un usuario comercial no ve un cliente, revisar reglas de visibilidad CRM.</li></ul>';
    }

    protected function supplier360_article()
    {
        return array(
            'code' => 'business-entity-hub-supplier-360',
            'title' => 'Vista 360 de Proveedor',
            'category' => 'Hub de Entidades',
            'summary' => 'Vista de lectura para consultar resumen de compras, linea de tiempo, documentos, comunicaciones, tickets y contratos de un proveedor.',
            'sort_order' => 50,
            'content' => $this->supplier360_content(),
        );
    }

    protected function supplier360_content()
    {
        return ''
            .'<h3>Objetivo</h3>'
            .'<p>Vista 360 de Proveedor concentra informacion operativa de un proveedor en una sola vista de lectura. Usa Hub de Entidades, Motor de Relaciones, Linea de Tiempo, permisos de compras/proveedores y el lector seguro de Comunicaciones.</p>'
            .'<h4>Secciones</h4>'
            .'<ul><li>Busqueda de proveedores permitidos por nombre, codigo o correo.</li><li>Resumen general del proveedor y contacto principal.</li><li>Indicadores de ordenes de compra, facturas proveedor, contrarecibos, pagos salientes y saldos almacenados.</li><li>Linea de Tiempo reciente de eventos visibles.</li><li>Comunicaciones visibles segun cuentas asignadas.</li><li>Documentos con enlaces seguros de descarga.</li><li>Tickets y contratos relacionados con el proveedor.</li></ul>'
            .'<h4>Permisos</h4>'
            .'<p>Grupo 100 puede consultar todos los proveedores. Usuarios normales requieren permisos como <code>purchases.access[view]</code>, <code>suppliers.access[view]</code>, <code>business.access[view]</code> o <code>parties.access[view]</code>.</p>'
            .'<h4>Seguridad</h4>'
            .'<p>La vista no modifica datos, no recalcula compras ni pagos, no muestra rutas fisicas, no expone secretos y no muestra conversaciones de buzones no asignados al usuario.</p>'
            .'<h4>Solucion de problemas</h4>'
            .'<ul><li>Si no carga, verificar que el usuario tenga permiso y que el proveedor exista como <code>party_type=supplier</code> activo.</li><li>Si no aparecen comunicaciones, revisar asignacion de cuentas de correo.</li><li>Si no aparecen documentos, confirmar que existan enlaces activos y que se usen endpoints de descarga.</li><li>Si los saldos aparecen en cero, revisar que compras/facturas/contrarecibos tengan <code>party_id</code> del proveedor.</li></ul>'
            .'<h4>Validacion</h4>'
            .'<ul><li>Consultar un proveedor con datos y uno sin datos.</li><li>Confirmar que los endpoints <code>admin/supplier360/data</code> y <code>admin/supplier360/search</code> devuelvan JSON.</li><li>Confirmar que no se expongan <code>file_path</code>, <code>storage_path</code>, secretos ni cuerpos de correo no autorizados.</li><li>Confirmar que Compras y Portal de Proveedores sigan operando sin cambios.</li></ul>';
    }

    protected function sanitize_content($content)
    {
        $content = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', (string) $content);
        return strip_tags($content, '<h3><h4><p><ul><ol><li><strong><code><em><br>');
    }

    protected function print_summary()
    {
        foreach ($this->created as $item) {
            \Cli::write('[CREADO] '.$item);
        }
        foreach ($this->updated as $item) {
            \Cli::write('[ACTUALIZADO] '.$item);
        }
        if (!$this->created && !$this->updated) {
            \Cli::write('Sin cambios.');
        }
    }
}
