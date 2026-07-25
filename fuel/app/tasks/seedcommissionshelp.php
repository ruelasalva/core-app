<?php
namespace Fuel\Tasks;

/**
 * TAREA SEEDCOMMISSIONSHELP
 *
 * Crea o actualiza la ayuda funcional del motor de comisiones.
 *
 * Uso PowerShell:
 * $env:FUEL_ENV='development'; php oil refine seedcommissionshelp
 */
class Seedcommissionshelp
{
    protected $created = array();
    protected $updated = array();

    public function run()
    {
        try {
            $this->assert_schema_ready();
            $this->upsert_article($this->article());
            $this->print_summary();
            \Log::info('Seedcommissionshelp ejecutado.');
        } catch (\Exception $e) {
            \Log::error('Seedcommissionshelp: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function article()
    {
        return array(
            'code' => 'commission-engine-functional-architecture',
            'title' => 'Commission Engine Functional Architecture',
            'category' => 'Commercial',
            'summary' => 'Especificacion funcional para evolucionar el motor de comisiones sin cambiar la logica actual.',
            'sort_order' => 80,
            'content' => $this->article_content(),
        );
    }

    protected function article_content()
    {
        return ''
            .'<h3>Objetivo</h3>'
            .'<p>El motor de comisiones debe calcular, explicar, liberar, aprobar, liquidar y auditar comisiones comerciales sin acoplarse directamente a Ventas, Facturacion, Pagos, Contratos o Contabilidad. Esta guia describe la arquitectura funcional futura; no cambia el motor actual.</p>'

            .'<h4>Estructura actual</h4>'
            .'<ul>'
            .'<li><code>core_sales_sellers</code>: vendedores internos, revendedores o externos.</li>'
            .'<li><code>core_commission_plans</code>: planes de comision.</li>'
            .'<li><code>core_commission_rules</code>: reglas por alcance, evento, base y valor.</li>'
            .'<li><code>core_commission_entries</code>: movimientos de comision.</li>'
            .'<li><code>core_commission_settlements</code>: liquidaciones.</li>'
            .'<li><code>core_commission_adjustments</code>: ajustes.</li>'
            .'</ul>'

            .'<h4>Eventos funcionales</h4>'
            .'<p>Los planes futuros no deben depender solo de facturas. Deben soportar eventos configurables como cotizacion creada, cotizacion aprobada, pedido creado, entrega, factura emitida, pago parcial, factura pagada, contrato firmado, contrato activado, pago recurrente de contrato y evento manual.</p>'

            .'<h4>Comisiones por etapas</h4>'
            .'<p>Una comision puede liberarse en etapas ilimitadas. Ejemplos: 30% al facturar y 70% al cobrar; o 20% al aprobar cotizacion, 30% al entregar y 50% al cobrar.</p>'

            .'<h4>Bases de calculo</h4>'
            .'<ul>'
            .'<li>Subtotal.</li>'
            .'<li>Total, solo si la politica comercial lo autoriza.</li>'
            .'<li>Margen, con permiso especial porque puede exponer costos.</li>'
            .'<li>Cantidad.</li>'
            .'<li>Monto fijo.</li>'
            .'<li>Monto recurrente.</li>'
            .'<li>Formula personalizada futura, sin usar ejecucion dinamica insegura.</li>'
            .'</ul>'

            .'<h4>Exclusiones y prioridad</h4>'
            .'<p>El motor debe soportar exclusiones por producto, marca, categoria, subcategoria, cliente, vendedor, proveedor, contrato, campana o canal. Las reglas deben evaluarse por prioridad, acumulacion, exclusividad, stop processing y regla fallback.</p>'

            .'<h4>Snapshot historico</h4>'
            .'<p>Cada comision debe conservar plan, regla, evento, base, porcentaje, monto, vendedor, cliente, producto, fecha, version de configuracion, advertencias y calculo aplicado. Cambiar una regla futura no debe alterar comisiones ya generadas.</p>'

            .'<h4>Liberacion por pago parcial</h4>'
            .'<p>La liberacion debe ser proporcional a pagos aplicados. Si una factura de 10,000 genera comision de 500 y se paga 4,000, se libera 40%, es decir 200. Debe soportar multiples pagos, reversas, notas de credito, cancelaciones y saldo pendiente.</p>'

            .'<h4>Beneficiarios multiples</h4>'
            .'<p>Una operacion puede generar comisiones independientes para vendedor, supervisor, gerente comercial, socio, agente externo o revendedor. Cada beneficiario requiere su propia entrada y snapshot.</p>'

            .'<h4>Ajustes y aprobacion</h4>'
            .'<p>Los ajustes deben separar bonos, penalizaciones, correcciones, anticipos, prestamos, recuperaciones, ajustes manuales y automaticos. Todo ajuste requiere motivo y autorizacion.</p>'
            .'<p>Estados recomendados: pending, earned, approved, settled, paid, cancelled, reversed y held.</p>'

            .'<h4>Simulador y explicacion</h4>'
            .'<p>El simulador debe calcular sin guardar datos y mostrar reglas evaluadas, reglas ignoradas, prioridad, base, porcentajes, monto final y advertencias. Cada entrada debe explicar por que se genero, que regla coincidio y cuanto queda liberado o pendiente.</p>'

            .'<h4>Reportes</h4>'
            .'<p>Reportes requeridos: comisiones por vendedor, cliente, factura, pago, pendientes, ganadas, aprobadas, liquidaciones, bonos, ajustes, reversas, recurrentes y KPIs para Administracion Comercial.</p>'

            .'<h4>Eventos futuros</h4>'
            .'<p>Eventos recomendados: <code>commissions.entry.generated</code>, <code>commissions.entry.released</code>, <code>commissions.entry.approved</code>, <code>commissions.entry.reversed</code>, <code>commissions.adjustment.created</code>, <code>commissions.settlement.created</code>, <code>commissions.settlement.approved</code> y <code>commissions.settlement.paid</code>.</p>'

            .'<h4>Roadmap</h4>'
            .'<ol>'
            .'<li>Phase 3B: servicios base del motor.</li>'
            .'<li>Phase 3C: generacion por factura y eventos.</li>'
            .'<li>Phase 3D: liberacion proporcional por pago.</li>'
            .'<li>Phase 3E: liquidaciones y aprobacion.</li>'
            .'<li>Phase 3F: reportes.</li>'
            .'<li>Phase 3G: simulador.</li>'
            .'<li>Phase 3H: KPIs en Administracion Comercial.</li>'
            .'</ol>'

            .'<h4>Reglas de seguridad</h4>'
            .'<ul>'
            .'<li>No exponer costos ni margen sin permiso especifico.</li>'
            .'<li>No modificar comisiones pagadas; usar reversas o ajustes auditados.</li>'
            .'<li>No recalcular historicos sin snapshot y trazabilidad.</li>'
            .'<li>No enviar secretos, rutas fisicas ni datos sensibles por Event Bus.</li>'
            .'<li>No ejecutar migraciones o seeds sin <code>FUEL_ENV</code> explicito.</li>'
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
        \Cli::write('Ayuda de comisiones sembrada.');
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
