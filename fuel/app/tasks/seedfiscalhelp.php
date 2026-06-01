<?php
namespace Fuel\Tasks;

/**
 * TAREA SEEDFISCALHELP
 *
 * Crea o actualiza manuales fiscales dentro del modulo de Ayuda.
 *
 * Uso:
 * php oil refine seedfiscalhelp
 *
 * @package  app
 */
class Seedfiscalhelp
{
    protected $created = [];
    protected $updated = [];
    protected $skipped = [];

    /**
     * RUN
     *
     * INSERTA O ACTUALIZA ARTICULOS FISCALES DE AYUDA.
     *
     * @access  public
     * @return  Void
     */
    public function run()
    {
        try {
            $this->assert_schema_ready();

            foreach ($this->articles() as $article) {
                $this->upsert_article($article);
            }

            $this->print_summary();
            \Log::info('Seedfiscalhelp ejecutado creados='.count($this->created).' actualizados='.count($this->updated).' omitidos='.count($this->skipped));
        } catch (\Exception $e) {
            \Log::error('Seedfiscalhelp: '.$e->getMessage());
            \Cli::write('Error sembrando ayuda fiscal: '.$e->getMessage());
        }
    }

    /**
     * UPSERT ARTICLE
     *
     * ACTUALIZA POR CODE O CREA EL ARTICULO SI NO EXISTE.
     *
     * @access  protected
     * @return  Void
     */
    protected function upsert_article(array $article)
    {
        $existing = \DB::select('id')
            ->from('core_knowledge_articles')
            ->where('code', '=', $article['code'])
            ->execute()
            ->current();

        $data = [
            'code' => $article['code'],
            'title' => $article['title'],
            'category' => $article['category'],
            'summary' => $article['summary'],
            'content' => $article['content'],
            'sort_order' => (int) $article['sort_order'],
            'active' => 1,
            'updated_at' => time(),
        ];

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

    /**
     * ARTICLES
     *
     * DEFINE LOS MANUALES FISCALES A SEMBRAR.
     *
     * @access  protected
     * @return  Array
     */
    protected function articles()
    {
        return [
            [
                'code' => 'configuracion-fiscal-contable',
                'title' => 'Configuracion fiscal-contable',
                'category' => 'Fiscal',
                'summary' => 'Relaciona impuestos fiscales con cuentas contables para revisar IVA e ISR desde el libro fiscal.',
                'sort_order' => 70,
                'content' => '<h3>Objetivo</h3><p>La configuracion fiscal-contable conecta el libro fiscal con el catalogo contable de CORE-APP ERP. Sirve para indicar en que cuenta se debe comparar cada impuesto: IVA trasladado, IVA acreditable, IVA retenido e ISR retenido.</p><h3>Catalogo SAT y catalogo contable</h3><p>El SAT proporciona el Codigo Agrupador para clasificar cuentas en reportes fiscales, pero no entrega un catalogo completo especifico para cada empresa. CORE-APP usa su propio catalogo de cuentas y permite relacionarlo con codigos SAT cuando aplique.</p><h3>Cuentas fiscales base</h3><ul><li><strong>2200 - IVA trasladado por pagar:</strong> IVA cobrado en CFDI emitidos.</li><li><strong>2300 - IVA acreditable:</strong> IVA de CFDI recibidos que puede acreditarse segun revision fiscal.</li><li><strong>2400 - IVA retenido por pagar:</strong> IVA retenido que debe controlarse como obligacion.</li><li><strong>2410 - ISR retenido por pagar:</strong> ISR retenido que debe controlarse por separado.</li></ul><h3>Mapeos recomendados</h3><ul><li><code>002 / transferred / issued</code>: IVA trasladado.</li><li><code>002 / transferred / received</code>: IVA acreditable.</li><li><code>002 / retained</code>: IVA retenido.</li><li><code>001 / retained</code>: ISR retenido.</li></ul><h3>Regla importante</h3><p>Los mapeos existentes capturados por el usuario no deben sobrescribirse automaticamente. Si una cuenta no coincide con la politica contable de la empresa, ajustala en Contabilidad antes de generar borradores fiscales.</p>',
            ],
            [
                'code' => 'borradores-polizas-fiscales',
                'title' => 'Borradores de polizas fiscales',
                'category' => 'Fiscal',
                'summary' => 'Explica como generar, revisar, cancelar y regenerar polizas fiscales preliminares sin afectar polizas contabilizadas.',
                'sort_order' => 71,
                'content' => '<h3>Objetivo</h3><p>Los borradores de polizas fiscales permiten revisar el asiento contable preliminar generado desde el libro fiscal. Se crean en estado borrador y no se contabilizan automaticamente.</p><h3>Generacion</h3><p>Para generar un borrador fiscal usa:</p><pre><code>php oil refine generatefiscaldrafts --rfc=SET180322811 --period=2026-05</code></pre><p>Antes de generar, valida que el periodo fiscal este abierto, que exista libro fiscal y que los mapeos fiscal-contables esten configurados.</p><h3>Revision</h3><ol><li>Abre Contabilidad.</li><li>Busca la poliza marcada como fiscal preliminar.</li><li>Revisa cuentas, cargos, abonos e importes.</li><li>Confirma que el debe y haber esten cuadrados.</li><li>Contabiliza solo cuando el responsable contable lo apruebe.</li></ol><h3>Cancelacion y regeneracion</h3><p>Si el borrador necesita rehacerse, no lo borres. Cancela logicamente el borrador con:</p><pre><code>php oil refine cancelfiscaldraft --rfc=SET180322811 --period=2026-05</code></pre><p>Despues puedes volver a ejecutar la generacion. La cancelacion no elimina partidas, no toca polizas contabilizadas y conserva evidencia para auditoria.</p><h3>Cuenta de cuadre preliminar</h3><p>Las lineas fiscales representan impuestos. Para revisar una poliza cuadrada puede usarse una cuenta configurable de cuadre preliminar, recomendada como <strong>2500 - Impuestos por pagar preliminares</strong>. Esta linea no representa pago definitivo de impuestos.</p><h3>Precauciones</h3><ul><li>No contabilices si hay diferencias sin revisar.</li><li>No modifiques polizas publicadas para regenerar borradores.</li><li>No uses el borrador como declaracion fiscal final sin revision del contador.</li><li>Si falta una cuenta o mapeo, corrige la configuracion antes de regenerar.</li></ul>',
            ],
        ];
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LA BASE DE CONOCIMIENTO EXISTA.
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        if (!\DBUtil::table_exists('core_knowledge_articles')) {
            throw new \RuntimeException('Falta la tabla core_knowledge_articles. Ejecuta migraciones antes de sembrar ayuda fiscal.');
        }
    }

    /**
     * PRINT SUMMARY
     *
     * IMPRIME RESULTADOS DE LA TAREA.
     *
     * @access  protected
     * @return  Void
     */
    protected function print_summary()
    {
        \Cli::write('Ayuda fiscal sembrada.');

        \Cli::write('Creados: '.count($this->created));
        foreach ($this->created as $title) {
            \Cli::write(' - '.$title);
        }

        \Cli::write('Actualizados: '.count($this->updated));
        foreach ($this->updated as $title) {
            \Cli::write(' - '.$title);
        }

        \Cli::write('Omitidos: '.count($this->skipped));
        foreach ($this->skipped as $title) {
            \Cli::write(' - '.$title);
        }
    }
}
