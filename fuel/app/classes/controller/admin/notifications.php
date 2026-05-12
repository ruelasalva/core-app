<?php

/**
 * CONTROLADOR ADMIN_NOTIFICATIONS
 *
 * API administrativa para campana y lectura de notificaciones.
 *
 * @package  app
 * @extends  Controller_Adminbase
 */
class Controller_Admin_Notifications extends Controller_Adminbase
{
    /**
     * DATA
     *
     * ENTREGA CONTADOR Y LISTADO DE NOTIFICACIONES NO LEIDAS
     *
     * @access  public
     * @return  Response
     */
    public function action_data()
    {
        try {
            # SE REGRESA INFORMACION PARA EL TEMPLATE
            return $this->json_response([
                'count' => Helper_Core_Notification::unread_count($this->user_id),
                'items' => Helper_Core_Notification::unread_for_user($this->user_id, 8),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cargando notificaciones: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron cargar las notificaciones.'], 500);
        }
    }

    /**
     * MARK READ
     *
     * MARCA UNA NOTIFICACION COMO LEIDA
     *
     * @access  public
     * @return  Response
     */
    public function post_mark_read()
    {
        # SE OBTIENE PAYLOAD JSON
        $val = (array) \Input::json();
        $recipient_id = (int) \Arr::get($val, 'recipient_id', 0);

        # VALIDACIONES MINIMAS
        if ($recipient_id < 1) {
            return $this->json_response(['error' => 'Notificacion invalida.'], 422);
        }

        # SE MARCA COMO LEIDA
        $ok = Helper_Core_Notification::mark_read($recipient_id, $this->user_id);

        return $this->json_response(['status' => $ok ? 'ok' : 'not_found']);
    }
}
