<?php
namespace Fuel\Tasks;

/**
 * TAREA SEEDCOMMUNICATIONSHELP
 *
 * Crea o actualiza la guia interna del Centro de Comunicaciones en Ayuda.
 *
 * Uso:
 * php oil refine seedcommunicationshelp
 *
 * @package  app
 */
class Seedcommunicationshelp
{
    protected $created = [];
    protected $updated = [];

    /**
     * RUN
     *
     * INSERTA O ACTUALIZA LA DOCUMENTACION DE COMUNICACIONES.
     *
     * @access  public
     * @return  Void
     */
    public function run()
    {
        try {
            $this->assert_schema_ready();
            $this->upsert_article($this->article());

            $this->print_summary();
            \Log::info('Seedcommunicationshelp ejecutado creados='.count($this->created).' actualizados='.count($this->updated));
        } catch (\Exception $e) {
            \Log::error('Seedcommunicationshelp: '.$e->getMessage());
            \Cli::write('Error sembrando ayuda de comunicaciones: '.$e->getMessage());
        }
    }

    /**
     * UPSERT ARTICLE
     *
     * ACTUALIZA POR CODE O CREA EL ARTICULO SI NO EXISTE.
     *
     * @access  protected
     * @param   Array  $article
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
            'content' => $this->sanitize_content($article['content']),
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
     * ARTICLE
     *
     * DEFINE LA GUIA DE COMUNICACIONES PARA EL MODULO DE AYUDA.
     *
     * @access  protected
     * @return  Array
     */
    protected function article()
    {
        return [
            'code' => 'centro-de-comunicaciones',
            'title' => 'Centro de Comunicaciones',
            'category' => 'Comunicaciones',
            'summary' => 'Guia para configurar Comunicaciones: SMTP, PHP Mail, Disabled, IMAP, Event Bus, plantillas, cola, bandejas y seguridad.',
            'sort_order' => 30,
            'content' => $this->article_content(),
        ];
    }

    /**
     * ARTICLE CONTENT
     *
     * CONTENIDO HTML SEGURO PARA MOSTRAR EN ADMIN/HELP.
     *
     * @access  protected
     * @return  String
     */
    protected function article_content()
    {
        return '<h3>1. Vista general</h3>'
            .'<p>El <strong>Centro de Comunicaciones</strong> centraliza correos, notificaciones internas, eventos del ERP, cuentas de correo, plantillas, layouts, cola de envio y conversaciones. Su objetivo es que CRM, Helpdesk, Ventas y Portales puedan comunicarse sin duplicar logica en cada modulo.</p>'
            .'<p>Los modulos deben disparar eventos con <code>Helper_Core_Event::fire()</code>. El Event Bus entrega el evento al despachador de comunicaciones, y el despachador decide si debe crear notificacion interna, encolar correo u omitir canales segun configuracion.</p>'

            .'<h3>2. Configuracion inicial</h3>'
            .'<ol>'
            .'<li>Configura un proveedor: <strong>Disabled</strong>, <strong>PHP Mail</strong> o <strong>SMTP</strong>.</li>'
            .'<li>Configura una cuenta de correo para sistema, ventas, soporte o area compartida.</li>'
            .'<li>Asigna la cuenta a usuarios o grupos con permisos de ver, enviar, recibir, sincronizar o administrar.</li>'
            .'<li>Configura destinatarios por evento y canal.</li>'
            .'<li>Configura plantillas y layouts de correo.</li>'
            .'<li>Ejecuta una prueba de envio desde Comunicaciones.</li>'
            .'<li>Procesa la cola manualmente o mediante cron.</li>'
            .'</ol>'

            .'<h3>3. Proveedores</h3>'
            .'<ul>'
            .'<li><strong>disabled_default:</strong> proveedor seguro para pruebas. Simula el envio sin entregar correos reales.</li>'
            .'<li><strong>php_mail_default:</strong> usa el mecanismo de correo de PHP cuando el servidor ya esta preparado.</li>'
            .'<li><strong>smtp_default:</strong> usa host, puerto, usuario, cifrado y remitente configurados en el admin.</li>'
            .'<li><strong>simulation_mode:</strong> mantiene el flujo de cola sin enviar correos reales.</li>'
            .'<li><strong>last_test_status:</strong> muestra el ultimo resultado de prueba sin exponer credenciales.</li>'
            .'</ul>'
            .'<p>Los passwords y API keys no se muestran en pantallas, JSON ni logs. Si el campo indica <strong>Configurado</strong>, el secreto existe pero no es visible.</p>'

            .'<h3>4. Cuentas de correo</h3>'
            .'<p>Una cuenta puede ser de sistema, personal o compartida. Una cuenta de sistema sirve para avisos generales; una cuenta personal representa a un usuario; una cuenta compartida sirve para areas como soporte o ventas.</p>'
            .'<p>Las asignaciones a usuario o grupo controlan lo que cada persona puede hacer:</p>'
            .'<ul>'
            .'<li><strong>Ver:</strong> consultar conversaciones de la cuenta.</li>'
            .'<li><strong>Enviar:</strong> redactar o responder desde la cuenta.</li>'
            .'<li><strong>Recibir:</strong> ver mensajes sincronizados.</li>'
            .'<li><strong>Sincronizar:</strong> ejecutar o administrar sincronizacion IMAP si aplica.</li>'
            .'<li><strong>Administrar:</strong> modificar configuracion de la cuenta.</li>'
            .'<li><strong>Remitente predeterminado:</strong> usar esa cuenta como salida preferida.</li>'
            .'</ul>'

            .'<h3>5. Ruteo de eventos</h3>'
            .'<p>Un evento define que ocurrio en el ERP. Cada evento puede tener canales como <strong>internal</strong> o <strong>email</strong>, y reglas de destinatarios por usuario, grupo, rol o correo manual.</p>'
            .'<p>Ejemplos:</p>'
            .'<ul>'
            .'<li><code>helpdesk.ticket.created</code> hacia el grupo <strong>Sistemas</strong> por canal interno.</li>'
            .'<li><code>sales.quote.created</code> hacia el equipo de <strong>Ventas</strong> cuando el evento este habilitado.</li>'
            .'</ul>'
            .'<p>Las reglas pueden incluir o excluir destinatarios y el sistema elimina duplicados antes de enviar o notificar.</p>'

            .'<h3>6. Plantillas y layouts</h3>'
            .'<p>El layout es la estructura base del correo. La plantilla define asunto, contenido HTML y, cuando existe, contenido de texto. Las variables se reemplazan al renderizar.</p>'
            .'<p>Variables globales frecuentes: <code>company_name</code>, <code>company_email</code>, <code>company_phone</code>, <code>company_website</code>, <code>current_date</code>, <code>current_year</code> y <code>user_name</code>.</p>'
            .'<p>La vista previa permite revisar HTML y texto sin enviar correo. No se permiten scripts, codigo PHP, valores secretos ni contenido inseguro.</p>'

            .'<h3>7. Cola de correo</h3>'
            .'<p>Los correos se encolan para que el proceso sea controlado. Estados comunes:</p>'
            .'<ul>'
            .'<li><strong>pending:</strong> pendiente de procesar.</li>'
            .'<li><strong>processing:</strong> tomado por el procesador.</li>'
            .'<li><strong>sent:</strong> enviado correctamente.</li>'
            .'<li><strong>failed:</strong> fallo despues de intentar.</li>'
            .'<li><strong>simulated:</strong> procesado por un proveedor en modo simulacion.</li>'
            .'</ul>'
            .'<p>Comandos utiles:</p>'
            .'<pre><code>php oil refine processemailqueue --limit=50</code></pre>'
            .'<pre><code>php oil refine processemailqueue --recover-stale=1 --stale-minutes=30 --limit=10</code></pre>'

            .'<h3>8. IMAP</h3>'
            .'<p>Las cuentas entrantes requieren configuracion IMAP, <code>sync_enabled</code>, carpetas autorizadas y la extension PHP IMAP instalada. La sincronizacion puede ejecutarse de forma manual o por cron.</p>'
            .'<p>Si la version actual no almacena adjuntos binarios entrantes, se debe tratar la metadata como referencia informativa y no como descarga final.</p>'

            .'<h3>9. Centro de conversaciones</h3>'
            .'<p><strong>Mi bandeja</strong> muestra conversaciones de las cuentas asignadas al usuario. Un super administrador puede ver todas las cuentas; usuarios normales solo ven cuentas asignadas.</p>'
            .'<p>Las conversaciones pueden consultarse por cuenta, asunto, participante, direccion, estado y ultima fecha. CRM y Helpdesk pueden mostrar paneles embebidos con las conversaciones relacionadas sin salir del modulo.</p>'

            .'<h3>10. Redactar y responder</h3>'
            .'<p>Solo las cuentas con permiso <code>can_send</code> permiten nuevo correo o respuesta. Al redactar o responder, el mensaje queda como <strong>queued</strong> hasta que el procesador de cola lo envie o simule.</p>'
            .'<p>Los adjuntos permitidos se validan por tipo, nombre seguro y limite de tamano. No se aceptan archivos ejecutables ni contenido HTML/JS peligroso.</p>'

            .'<h3>11. Seguridad</h3>'
            .'<ul>'
            .'<li>No se exponen passwords, API keys ni tokens.</li>'
            .'<li>No se exponen <code>file_path</code>, <code>storage_path</code> ni rutas fisicas.</li>'
            .'<li>Las acciones administrativas requieren permisos <code>communications.access</code>.</li>'
            .'<li>La visibilidad de conversaciones depende de cuentas asignadas.</li>'
            .'<li>Grupo 100 puede operar como super administrador.</li>'
            .'<li>Las acciones POST deben conservar CSRF.</li>'
            .'<li>Los adjuntos se validan antes de guardarse o encolarse.</li>'
            .'</ul>'

            .'<h3>12. Solucion de problemas</h3>'
            .'<ul>'
            .'<li><strong>La pagina queda cargando:</strong> revisa consola, respuesta JSON y logs de FuelPHP.</li>'
            .'<li><strong>Un endpoint devuelve HTML:</strong> normalmente falta sesion o permisos; debe responder JSON 401/403 en endpoints AJAX.</li>'
            .'<li><strong>La cola queda en processing:</strong> usa recuperacion stale antes de reintentar.</li>'
            .'<li><strong>No veo conversaciones:</strong> valida asignacion de cuenta y permiso <code>communications.access[view]</code>.</li>'
            .'<li><strong>No veo clientes en CRM:</strong> valida reglas de visibilidad comercial y que el cliente tenga correo valido cuando se use como destinatario.</li>'
            .'<li><strong>No aparece cuenta asignada:</strong> revisa asignacion usuario/grupo y permisos de la cuenta.</li>'
            .'<li><strong>Falta IMAP:</strong> instala o habilita la extension PHP IMAP y reinicia el servicio.</li>'
            .'<li><strong>El correo no sale:</strong> revisa proveedor activo, modo simulacion, cola y logs.</li>'
            .'<li><strong>La prueba SMTP falla:</strong> revisa host, puerto, cifrado, usuario, password nuevo, TLS y restricciones del proveedor.</li>'
            .'</ul>'

            .'<h3>13. Comandos CLI</h3>'
            .'<ul>'
            .'<li><code>php oil refine seedcommunications</code></li>'
            .'<li><code>php oil refine diagnosticscommunications</code></li>'
            .'<li><code>php oil refine testemail</code></li>'
            .'<li><code>php oil refine processemailqueue --limit=50</code></li>'
            .'<li><code>php oil refine syncimapaccounts</code></li>'
            .'<li><code>php oil refine testeventbus --event=helpdesk.ticket.created</code></li>'
            .'<li><code>php oil refine seedsystemsgroup</code></li>'
            .'<li><code>php oil refine seedcommunicationshelp</code></li>'
            .'</ul>'

            .'<h3>14. Checklist de produccion</h3>'
            .'<ol>'
            .'<li>Configurar SMTP real y probar envio.</li>'
            .'<li>Validar DNS: SPF, DKIM y DMARC.</li>'
            .'<li>Configurar cron para procesar cola.</li>'
            .'<li>Configurar cron para IMAP si se usara recepcion.</li>'
            .'<li>Crear grupo Sistemas si se usara para Helpdesk.</li>'
            .'<li>Asignar cuentas a usuarios o grupos.</li>'
            .'<li>Probar eventos principales.</li>'
            .'<li>Probar nuevo correo y respuesta desde cuentas permitidas.</li>'
            .'<li>Revisar logs y errores recientes.</li>'
            .'</ol>';
    }

    /**
     * SANITIZE CONTENT
     *
     * APLICA LA MISMA IDEA DE SEGURIDAD DEL EDITOR DE AYUDA.
     *
     * @access  protected
     * @param   String  $html
     * @return  String
     */
    protected function sanitize_content($html)
    {
        $html = preg_replace('#<(script|iframe|object|embed|style)[^>]*>.*?</\1>#is', '', (string) $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html);
        $html = preg_replace('/javascript\s*:/is', '', $html);

        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4><h5><blockquote><code><pre><a><hr><table><thead><tbody><tr><th><td>');
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
            throw new \RuntimeException('Falta la tabla core_knowledge_articles. Ejecuta migraciones antes de sembrar ayuda de comunicaciones.');
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
        \Cli::write('Ayuda de comunicaciones sembrada.');
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
