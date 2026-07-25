<?php
namespace Fuel\Tasks;

/**
 * Crea o actualiza la ayuda de la plataforma de configuracion de comisiones.
 *
 * Uso PowerShell:
 * $env:FUEL_ENV='development'; php oil refine seedcommissionconfighelp
 */
class Seedcommissionconfighelp
{
    protected $created = array();
    protected $updated = array();

    public function run()
    {
        try {
            $this->assert_schema_ready();
            foreach ($this->articles() as $article) {
                $this->upsert_article($article);
            }
            $this->print_summary();
            \Log::info('Seedcommissionconfighelp ejecutado.');
        } catch (\Exception $e) {
            \Log::error('Seedcommissionconfighelp: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function articles()
    {
        return array(
            array(
                'code' => 'commission-configuration-platform',
                'title' => 'Configuración de Comisiones',
                'category' => 'Commercial',
                'summary' => 'Administración de planes, versiones y reglas para el futuro motor de comisiones.',
                'sort_order' => 90,
                'content' => $this->configuration_content(),
            ),
            array(
                'code' => 'commission-commercial-plans',
                'title' => 'Planes Comerciales',
                'category' => 'Commercial',
                'summary' => 'Cómo estructurar planes comerciales versionados.',
                'sort_order' => 91,
                'content' => $this->plans_content(),
            ),
            array(
                'code' => 'commission-rules',
                'title' => 'Reglas de Comisión',
                'category' => 'Commercial',
                'summary' => 'Configuración de reglas, prioridades, bases y beneficiarios.',
                'sort_order' => 92,
                'content' => $this->rules_content(),
            ),
            array(
                'code' => 'commission-versioning',
                'title' => 'Versionamiento de Comisiones',
                'category' => 'Commercial',
                'summary' => 'Estados de versiones y protección de configuraciones publicadas.',
                'sort_order' => 93,
                'content' => $this->versioning_content(),
            ),
            array(
                'code' => 'commission-publication-workflow',
                'title' => 'Flujo de Publicación de Comisiones',
                'category' => 'Commercial',
                'summary' => 'Publicación controlada e inmutabilidad de versiones.',
                'sort_order' => 94,
                'content' => $this->publication_content(),
            ),
            array(
                'code' => 'commission-simulator',
                'title' => 'Simulador de Comisiones',
                'category' => 'Commercial',
                'summary' => 'Simulación read-only de reglas publicadas sin crear movimientos.',
                'sort_order' => 95,
                'content' => $this->simulator_content(),
            ),
            array(
                'code' => 'commission-pending-generation',
                'title' => 'Generación de Comisiones Pendientes',
                'category' => 'Commercial',
                'summary' => 'Provisión controlada de comisiones desde facturas timbradas.',
                'sort_order' => 96,
                'content' => $this->pending_generation_content(),
            ),
            array(
                'code' => 'commission-plan-bridge-sellers',
                'title' => 'Planes de Comisiones en Vendedores',
                'category' => 'Commercial',
                'summary' => 'Diferencia entre planes legacy y planes configurables publicados al administrar vendedores.',
                'sort_order' => 97,
                'content' => $this->plan_bridge_content(),
            ),
        );
    }

    protected function configuration_content()
    {
        return ''
            .'<h3>Objetivo</h3>'
            .'<p>Configuración de Comisiones prepara reglas comerciales para el futuro motor de comisiones. Esta pantalla no calcula, no libera y no paga comisiones.</p>'
            .'<h4>Qué puedes configurar</h4>'
            .'<ul>'
            .'<li>Planes comerciales.</li>'
            .'<li>Versiones.</li>'
            .'<li>Grupos de reglas.</li>'
            .'<li>Reglas, etapas, beneficiarios y exclusiones.</li>'
            .'<li>Catálogos auxiliares.</li>'
            .'</ul>'
            .'<h4>Permisos</h4>'
            .'<p>Para consultar se requiere <code>commissions.access[view]</code>. Para editar se requiere <code>commissions.access[edit]</code>. Para publicar se requiere <code>commissions.access[authorize]</code>.</p>'
            .'<h4>Validación</h4>'
            .'<p>Antes de publicar revisa que el plan, versión, reglas, etapas y beneficiarios correspondan a la política comercial aprobada.</p>';
    }

    protected function plans_content()
    {
        return ''
            .'<h3>Planes Comerciales</h3>'
            .'<p>Un plan comercial agrupa versiones de configuración. Ejemplo: Plan Comercial 2026.</p>'
            .'<h4>Recomendación</h4>'
            .'<p>Crea un plan por periodo o estrategia comercial. Evita mezclar políticas de años distintos dentro del mismo nombre operativo.</p>'
            .'<h4>Cambios</h4>'
            .'<p>Si un plan ya tiene una versión publicada, crea una nueva versión para ajustes futuros.</p>';
    }

    protected function rules_content()
    {
        return ''
            .'<h3>Reglas de Comisión</h3>'
            .'<p>Las reglas describen cuándo una operación futura podría generar comisión y bajo qué base de cálculo.</p>'
            .'<h4>Campos importantes</h4>'
            .'<ul>'
            .'<li>Prioridad: define orden de evaluación.</li>'
            .'<li>Acumulada: permite sumar con otras reglas.</li>'
            .'<li>Exclusiva: marca que la regla no debe combinarse libremente.</li>'
            .'<li>Detener procesamiento: impide evaluar reglas posteriores en el futuro motor.</li>'
            .'<li>Base de cálculo: subtotal, total, margen, monto fijo, recurrente o cantidad.</li>'
            .'</ul>'
            .'<h4>Seguridad</h4>'
            .'<p>La base margen debe tratarse como sensible porque puede exponer costos. El motor futuro deberá validar permisos antes de usarla o mostrarla.</p>';
    }

    protected function versioning_content()
    {
        return ''
            .'<h3>Versionamiento</h3>'
            .'<p>Cada plan comercial puede tener varias versiones. Las versiones permiten modificar políticas sin alterar configuraciones históricas.</p>'
            .'<h4>Estados</h4>'
            .'<ul>'
            .'<li>Borrador: editable.</li>'
            .'<li>Pruebas: editable para validación.</li>'
            .'<li>Publicado: inmutable.</li>'
            .'<li>Archivado: no editable.</li>'
            .'</ul>'
            .'<p>Las versiones publicadas nunca deben modificarse. Para cambios se crea una nueva versión.</p>';
    }

    protected function publication_content()
    {
        return ''
            .'<h3>Flujo de Publicación</h3>'
            .'<p>Publicar una versión confirma que la configuración queda lista para consumo futuro del motor de comisiones.</p>'
            .'<h4>Antes de publicar</h4>'
            .'<ul>'
            .'<li>Confirma vigencia.</li>'
            .'<li>Revisa reglas, etapas, beneficiarios y exclusiones.</li>'
            .'<li>Captura el motivo de publicación.</li>'
            .'</ul>'
            .'<h4>Efecto</h4>'
            .'<p>La publicación guarda un snapshot de configuración y bloquea cambios directos. Cualquier ajuste futuro deberá hacerse en una nueva versión.</p>';
    }

    protected function simulator_content()
    {
        return ''
            .'<h3>Simulador de Comisiones</h3>'
            .'<p>El simulador evalúa versiones publicadas y muestra reglas coincidentes, reglas ignoradas, base usada, valor aplicado, monto estimado y advertencias.</p>'
            .'<h4>Alcance</h4>'
            .'<ul>'
            .'<li>No crea entradas de comisión.</li>'
            .'<li>No libera pagos.</li>'
            .'<li>No genera liquidaciones.</li>'
            .'<li>No modifica ventas, facturación, pagos, SAT, fiscal, contabilidad ni contratos.</li>'
            .'</ul>'
            .'<h4>Bases soportadas</h4>'
            .'<p>Subtotal, total, monto fijo, cantidad y monto recurrente cuando el dato está disponible.</p>'
            .'<h4>Margen</h4>'
            .'<p>La simulación por margen permanece deshabilitada hasta contar con permiso explícito y reglas de exposición de costos.</p>';
    }

    protected function pending_generation_content()
    {
        return ''
            .'<h3>Generación de Comisiones Pendientes</h3>'
            .'<p>El generador evalúa facturas de venta timbradas contra versiones publicadas de la Plataforma de Configuración de Comisiones.</p>'
            .'<h4>Alcance</h4>'
            .'<ul>'
            .'<li>Crea entradas con estado <code>pending</code>.</li>'
            .'<li>No marca comisiones como ganadas.</li>'
            .'<li>No libera comisiones por pagos.</li>'
            .'<li>No crea liquidaciones ni pagos.</li>'
            .'<li>No modifica facturas, cobranza, SAT, fiscal ni contabilidad.</li>'
            .'</ul>'
            .'<h4>Prueba sin escritura</h4>'
            .'<pre>$env:FUEL_ENV=\'development\'; php oil refine generatecommissions --invoice=ID --dry-run=1</pre>'
            .'<h4>Aplicación controlada</h4>'
            .'<pre>$env:FUEL_ENV=\'development\'; php oil refine generatecommissions --invoice=ID --apply=1</pre>'
            .'<p>La segunda ejecución de la misma factura omite duplicados mediante <code>source_hash</code>.</p>'
            .'<h4>Requisitos</h4>'
            .'<ul>'
            .'<li>Factura activa, de venta y con estado <code>stamped</code>.</li>'
            .'<li>Vendedor resoluble.</li>'
            .'<li>Versión publicada y regla coincidente.</li>'
            .'<li>Beneficiario vinculado a un vendedor activo.</li>'
            .'</ul>'
            .'<h4>Problemas comunes</h4>'
            .'<p>Si no se crean entradas, revisa vendedor, vigencia, evento <code>invoice_issued</code>, exclusiones y beneficiarios. Las facturas canceladas o no timbradas se omiten.</p>';
    }

    protected function plan_bridge_content()
    {
        return ''
            .'<h3>Planes de Comisiones en Vendedores</h3>'
            .'<p>El modulo de vendedores conserva el campo legacy <code>default_commission_plan_id</code>. Por compatibilidad, ese campo solo puede guardar planes de <code>core_commission_plans</code>.</p>'
            .'<h4>Planes legacy</h4>'
            .'<p>Los planes legacy siguen disponibles para el motor historico y para las reglas, cuotas y generacion desde pedidos existentes.</p>'
            .'<h4>Planes configurables publicados</h4>'
            .'<p>Las versiones publicadas de la Plataforma de Configuracion de Comisiones se muestran en el selector para consulta y contexto operativo.</p>'
            .'<p>La asignacion persistente de versiones configurables a vendedores se habilitara en una fase posterior, con un campo dedicado para evitar mezclar identificadores legacy con versiones configurables.</p>'
            .'<h4>Regla operativa</h4>'
            .'<ul>'
            .'<li>Selecciona un plan legacy si necesitas guardar el vendedor hoy.</li>'
            .'<li>Consulta los planes configurables publicados para validar que configuracion usara el motor nuevo.</li>'
            .'<li>No se deben forzar IDs de versiones configurables dentro de campos legacy.</li>'
            .'</ul>';
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

    protected function sanitize_content($html)
    {
        $html = preg_replace('#<(script|iframe|object|embed|style)[^>]*>.*?</\1>#is', '', (string) $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html);
        $html = preg_replace('/javascript\s*:/is', '', $html);

        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4><h5><blockquote><code><pre><a><hr>');
    }

    protected function assert_schema_ready()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            throw new \RuntimeException('Falta la tabla core_knowledge_articles.');
        }
    }

    protected function print_summary()
    {
        \Cli::write('Ayuda de Configuración de Comisiones sembrada.');
        \Cli::write('Creados: '.count($this->created));
        foreach ($this->created as $title) {
            \Cli::write(' - '.$title);
        }

        \Cli::write('Actualizados: '.count($this->updated));
        foreach ($this->updated as $title) {
            \Cli::write(' - '.$title);
        }
    }
}
