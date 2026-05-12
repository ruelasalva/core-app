<?php

/**
 * CONTROLADOR LEGAL
 *
 * Endpoints publicos para preferencias de cookies y consentimientos.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_Legal extends Controller
{
    /**
     * COOKIES ACCEPT
     *
     * GUARDA LAS PREFERENCIAS DE COOKIES DEL VISITANTE O USUARIO
     *
     * @access  public
     * @return  Response
     */
    public function post_cookies_accept()
    {
        # SE OBTIENE PAYLOAD JSON O POST
        $payload = (array) \Input::json();
        if (empty($payload)) {
            $payload = \Input::post();
        }

        # SE OBTIENE EL USUARIO SI ESTA LOGUEADO
        $user_id = null;
        if (\Auth::check()) {
            $user_id_data = \Auth::get_user_id();
            $user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : null;
        }

        try {
            # SE GUARDAN LAS PREFERENCIAS
            $model = \Helper_Core_Legal::save_cookie_preferences($payload, $user_id);

            # SE REGRESA RESPUESTA JSON
            return $this->json_response([
                'status' => 'ok',
                'id' => (int) $model->id,
                'user_id' => $model->user_id ? (int) $model->user_id : null,
                'token' => $model->token,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando preferencias de cookies: '.$e->getMessage());
            return $this->json_response(['error' => 'No se pudieron guardar las preferencias.'], 400);
        }
    }

    /**
     * JSON RESPONSE
     *
     * GENERA RESPUESTAS JSON PUBLICAS
     *
     * @access  protected
     * @return  Response
     */
    protected function json_response(array $data, $status = 200)
    {
        return \Response::forge(
            json_encode($data),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
}
