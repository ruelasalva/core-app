<?php
namespace Fuel\Tasks;

/**
 * TAREA SEEDVIATICSHELP
 *
 * Crea o actualiza la guía interna del módulo de Viáticos.
 *
 * Uso:
 * $env:FUEL_ENV='development'; php oil refine seedviaticshelp
 */
class Seedviaticshelp
{
    public function run()
    {
        try {
            $this->assert_schema_ready();
            $this->upsert_article();
            \Cli::write('Ayuda de viáticos actualizada.');
            \Log::info('Seedviaticshelp ejecutado correctamente.');
        } catch (\Exception $e) {
            \Log::error('Seedviaticshelp: '.$e->getMessage());
            \Cli::write('[ERROR] '.$e->getMessage());
        }
    }

    protected function assert_schema_ready()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            throw new \RuntimeException('Falta tabla core_knowledge_articles.');
        }
    }

    protected function upsert_article()
    {
        $article = [
            'code' => 'viaticos-guia-operativa',
            'title' => 'Viáticos: guía operativa paso a paso',
            'category' => 'Finanzas',
            'summary' => 'Guía simple y visual para crear viajes, registrar anticipos, capturar gastos y cerrar viáticos.',
            'content' => $this->content(),
            'sort_order' => 72,
            'active' => 1,
            'updated_at' => time(),
        ];

        $existing = \DB::select('id')
            ->from('core_knowledge_articles')
            ->where('code', '=', $article['code'])
            ->execute()
            ->current();

        if ($existing) {
            \DB::update('core_knowledge_articles')
                ->set($article)
                ->where('id', '=', (int) $existing['id'])
                ->execute();
            return;
        }

        $article['created_at'] = time();
        \DB::insert('core_knowledge_articles')->set($article)->execute();
    }

    protected function content()
    {
        return '<h3>Objetivo</h3>'
            .'<p>Este módulo permite capturar viáticos de forma simple, guiada y ordenada para vendedores y personal operativo.</p>'
            .'<h3>Uso desde portal (vendedor en campo)</h3>'
            .'<ol>'
            .'<li>Entra a <strong>Portal Revendedores</strong> y abre <strong>Viáticos</strong>.</li>'
            .'<li>En celular usa el botón flotante <strong>+</strong> para abrir acciones rápidas.</li>'
            .'<li>Crea el viaje y valida fechas.</li>'
            .'<li>Registra anticipo cuando te lo autoricen.</li>'
            .'<li>Captura gastos durante el viaje para no perder comprobaciones.</li>'
            .'</ol>'
            .'<h3>Flujo guiado (5 pasos)</h3>'
            .'<ol>'
            .'<li><strong>Nuevo viaje:</strong> captura destino, fechas y motivo.</li>'
            .'<li><strong>Anticipos:</strong> registra monto y cuenta bancaria del anticipo.</li>'
            .'<li><strong>Gastos:</strong> captura concepto, proveedor, cuenta contable y monto.</li>'
            .'<li><strong>Resumen:</strong> revisa totales de anticipos, gastos y saldo.</li>'
            .'<li><strong>Cierre:</strong> genera borrador contable y cierra viaje.</li>'
            .'</ol>'
            .'<h3>Buenas prácticas</h3>'
            .'<ul>'
            .'<li>Un viaje por objetivo comercial/operativo para mantener trazabilidad.</li>'
            .'<li>Captura gastos en cuanto suceden para evitar omisiones.</li>'
            .'<li>Usa cuentas contables correctas por tipo de gasto.</li>'
            .'<li>Cierra el viaje al terminar para dejar saldo claro.</li>'
            .'</ul>'
            .'<h3>Mensajes frecuentes</h3>'
            .'<ul>'
            .'<li><strong>Título y fechas son obligatorios:</strong> completa datos básicos del viaje.</li>'
            .'<li><strong>No tienes un vendedor revendedor activo configurado:</strong> solicita a administración que vincule tu usuario con un vendedor activo.</li>'
            .'<li><strong>El anticipo debe ser mayor a cero:</strong> revisa monto de anticipo.</li>'
            .'<li><strong>Cada gasto debe tener una cuenta contable:</strong> selecciona cuenta en la captura.</li>'
            .'</ul>'
            .'<h3>Contabilidad</h3>'
            .'<p>El módulo genera <strong>borrador</strong> de póliza. La publicación final queda a revisión del área contable.</p>';
    }
}
