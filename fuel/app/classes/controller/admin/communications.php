<?php

/**
 * CONTROLADOR ADMIN_COMMUNICATIONS
 *
 * Administra eventos, correos y tablero basico de comunicaciones.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Communications extends Controller_Adminbase
{
    /**
     * BEFORE
     *
     * VALIDA SESION ADMIN Y PERMISO DE LECTURA DE COMUNICACIONES
     *
     * @return  Void
     */
    public function before()
    {
        # REQUERIDA PARA EL TEMPLATING Y SESION ADMIN
        parent::before();

        # VALIDAR PERMISO ORM AUTH
        $this->require_access('communications.access[view]');
    }

    /**
     * INDEX
     *
     * MUESTRA EL PANEL DE COMUNICACIONES
     *
     * @access  public
     * @return  Void
     */
    public function action_index()
    {
        # SE CARGA LA VISTA PRINCIPAL
        $this->template->title = 'Comunicaciones';
        $this->template->content = View::forge('admin/communications/index');
    }

    /**
     * DATA
     *
     * ENTREGA EVENTOS Y ESTADISTICAS EN JSON
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE VALIDA QUE LA ESTRUCTURA EXISTA
            $this->assert_schema_ready();

            # SE REGRESA LA INFORMACION PARA VUE
            return $this->json_response([
                'events' => $this->get_events(),
                'stats' => $this->get_stats(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando comunicaciones: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudo cargar comunicaciones.'], 500);
        }
    }

    /**
     * GET EVENTS
     *
     * FORMATEA EVENTOS PARA LA VISTA ADMINISTRATIVA
     *
     * @access  protected
     * @return  Array
     */
    protected function get_events()
    {
        # SE INICIALIZA EL ARREGLO DE RESPUESTA
        $items = [];

        # SE RECORREN LOS EVENTOS
        foreach (Model_Core_Notification_Event::list_for_admin() as $event) {
            $items[] = [
                'id' => (int) $event->id,
                'code' => (string) $event->code,
                'name' => (string) $event->name,
                'description' => (string) $event->description,
                'notify_internal' => (int) $event->notify_internal,
                'notify_email' => (int) $event->notify_email,
                'active' => (int) $event->active,
            ];
        }

        return $items;
    }

    /**
     * GET STATS
     *
     * OBTIENE CONTADORES BASICOS DEL MODULO
     *
     * @access  protected
     * @return  Array
     */
    protected function get_stats()
    {
        # SE REGRESAN CONTADORES AGREGADOS
        return [
            'events' => (int) \DB::count_records('core_notification_events'),
            'notifications' => (int) \DB::count_records('core_notifications'),
            'unread' => (int) \DB::select()->from('core_notification_recipients')->where('status', '=', 'unread')->execute()->count(),
            'emails_pending' => (int) \DB::select()->from('core_email_queue')->where('status', '=', 'pending')->execute()->count(),
            'emails_failed' => (int) \DB::select()->from('core_email_queue')->where('status', '=', 'failed')->execute()->count(),
        ];
    }

    /**
     * ASSERT SCHEMA READY
     *
     * VALIDA QUE LAS TABLAS DE COMUNICACIONES EXISTAN
     *
     * @access  protected
     * @return  Void
     */
    protected function assert_schema_ready()
    {
        # SE VERIFICA CADA TABLA REQUERIDA
        foreach (['core_notifications', 'core_notification_recipients', 'core_email_queue'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \RuntimeException('Falta ejecutar migraciones de comunicaciones.');
            }
        }
    }
}
