<?php

/**
 * Controlador admin para Biblioteca de Software.
 *
 * Modulo transversal para publicar instaladores, herramientas, demos, licencias
 * y enlaces de soporte a distintos backends y portales mediante reglas de acceso.
 */
class Controller_Admin_Softwarelibrary extends Controller_Adminbase
{
    protected $library;

    public function before()
    {
        if ($this->is_json_action()) {
            $this->auto_render = false;
            if (\Auth::check()) {
                $this->prepare_json_admin_context();
            }
            return;
        }

        parent::before();
        $this->require_access('software_library.access[view]');
        $this->library = new \Service_Core_Software_Library();
    }

    public function action_index()
    {
        $this->template->title = 'Biblioteca de Software';
        $this->template->content = \View::forge('admin/softwarelibrary/index');
    }

    public function action_data()
    {
        if ($response = $this->json_guard('view')) {
            return $response;
        }

        try {
            return $this->json_response([
                'success' => true,
                'message' => '',
                'data' => $this->service()->dashboard_data(),
                'errors' => [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Biblioteca de Software data: '.$e->getMessage());
            return $this->json_error('No se pudo cargar la biblioteca.', ['load_error'], 500);
        }
    }

    public function action_save_category()
    {
        return $this->post_save_category();
    }

    public function post_save_category()
    {
        if ($response = $this->json_guard('edit')) {
            return $response;
        }
        if ($response = $this->require_csrf()) {
            return $response;
        }

        return $this->service_response($this->service()->save_category($this->payload()));
    }

    public function action_save_item()
    {
        return $this->post_save_item();
    }

    public function post_save_item()
    {
        if ($response = $this->json_guard('edit')) {
            return $response;
        }
        if ($response = $this->require_csrf()) {
            return $response;
        }

        return $this->service_response($this->service()->save_item($this->payload(), $this->user_id));
    }

    public function action_save_version()
    {
        return $this->post_save_version();
    }

    public function post_save_version()
    {
        if ($response = $this->json_guard('edit')) {
            return $response;
        }
        if ($response = $this->require_csrf()) {
            return $response;
        }

        return $this->service_response($this->service()->save_version($this->payload(), $this->user_id));
    }

    public function action_save_asset()
    {
        return $this->post_save_asset();
    }

    public function post_save_asset()
    {
        if ($response = $this->json_guard('edit')) {
            return $response;
        }
        if ($response = $this->require_csrf()) {
            return $response;
        }

        return $this->service_response($this->service()->save_asset($this->payload(), $this->user_id));
    }

    public function action_save_access_rule()
    {
        return $this->post_save_access_rule();
    }

    public function post_save_access_rule()
    {
        if ($response = $this->json_guard('edit')) {
            return $response;
        }
        if ($response = $this->require_csrf()) {
            return $response;
        }

        return $this->service_response($this->service()->save_access_rule($this->payload(), $this->user_id));
    }

    public function action_upload_start()
    {
        return $this->post_upload_start();
    }

    public function post_upload_start()
    {
        if ($response = $this->json_guard('upload')) {
            return $response;
        }
        if ($response = $this->require_csrf()) {
            return $response;
        }

        return $this->service_response($this->service()->start_upload($this->payload(), $this->user_id));
    }

    public function action_upload_chunk()
    {
        return $this->post_upload_chunk();
    }

    public function post_upload_chunk()
    {
        if ($response = $this->json_guard('upload')) {
            return $response;
        }
        if ($response = $this->require_csrf()) {
            return $response;
        }

        $result = $this->service()->save_chunk(
            (string) \Input::post('upload_code', ''),
            (int) \Input::post('chunk_index', 0),
            (array) \Input::file('chunk'),
            $this->user_id
        );

        return $this->service_response($result);
    }

    public function action_upload_finish()
    {
        return $this->post_upload_finish();
    }

    public function post_upload_finish()
    {
        if ($response = $this->json_guard('upload')) {
            return $response;
        }
        if ($response = $this->require_csrf()) {
            return $response;
        }

        $payload = $this->payload();
        return $this->service_response($this->service()->finish_upload((string) \Arr::get($payload, 'upload_code', ''), $this->user_id));
    }

    public function action_download($asset_id = null)
    {
        $asset_id = (int) $asset_id;
        if ($asset_id < 1) {
            return new \Response('Recurso no valido.', 404);
        }

        try {
            $this->require_access('software_library.access[download]');
            $this->service()->assert_schema_ready();

            $result = $this->service()->asset_for_download($asset_id, [
                'user_id' => $this->user_id,
                'admin_access' => true,
                'is_super_admin' => $this->is_super_admin,
            ]);

            if (empty($result['success'])) {
                return new \Response((string) $result['message'], (int) \Arr::get($result, 'data.status', 403));
            }

            $asset = (array) $result['data'];
            if ((string) $asset['asset_type'] === 'link') {
                return \Response::redirect((string) $asset['external_url']);
            }

            $absolute = $this->service()->absolute_from_asset($asset);
            if ($absolute === '' || !is_file($absolute) || !is_readable($absolute)) {
                \Log::warning('Biblioteca de Software: archivo privado no disponible asset='.$asset_id.' user='.$this->user_id);
                return new \Response('Archivo no disponible.', 404);
            }

            $this->stream_file($absolute, (string) $asset['filename'], (string) $asset['mime_type']);
        } catch (\Exception $e) {
            \Log::error('Biblioteca de Software download: '.$e->getMessage());
            return new \Response('No se pudo descargar el recurso.', 500);
        }
    }

    protected function stream_file($absolute, $filename, $mime_type)
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        header('Content-Type: '.($mime_type ?: 'application/octet-stream'));
        header('Content-Length: '.filesize($absolute));
        header('Content-Disposition: attachment; filename="'.str_replace('"', '', $filename).'"');
        header('X-Content-Type-Options: nosniff');

        $handle = fopen($absolute, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 1048576);
            flush();
        }
        fclose($handle);
        exit;
    }

    protected function service_response(array $result)
    {
        if (!empty($result['success'])) {
            $result['data']['library'] = $this->service()->dashboard_data();
        }

        return $this->json_response($result, !empty($result['success']) ? 200 : (int) \Arr::get($result, 'data.status', 400));
    }

    protected function is_json_action()
    {
        return in_array((string) \Request::active()->action, [
            'data',
            'save_category',
            'save_item',
            'save_version',
            'save_asset',
            'save_access_rule',
            'upload_start',
            'upload_chunk',
            'upload_finish',
        ], true);
    }

    protected function prepare_json_admin_context()
    {
        $user_id_data = \Auth::get_user_id();
        $this->user_id = isset($user_id_data[1]) ? (int) $user_id_data[1] : 0;

        $groups = \Auth::get_groups();
        if (!empty($groups)) {
            $group_data = $groups[0][1];
            $this->user_group = is_object($group_data) ? (int) $group_data->id : (int) $group_data;
        }

        $this->is_super_admin = ($this->user_group === 100);
    }

    protected function json_guard($action)
    {
        if (!\Auth::check()) {
            return $this->json_error('Sesion requerida.', ['auth_required'], 401);
        }

        $permission = 'software_library.access['.$action.']';
        if ($action === 'edit') {
            $permission = 'software_library.access[edit]';
        }
        if (!$this->is_super_admin && !\Auth::has_access($permission)) {
            return $this->json_error('No tienes permiso para usar la Biblioteca de Software.', ['permission_denied'], 403);
        }

        return null;
    }

    protected function require_csrf()
    {
        $key = \Config::get('security.csrf_token_key', 'fuel_csrf_token');
        $payload = (array) \Input::json();
        $token = (string) \Input::headers('X-CSRF-Token', '');

        if ($token === '') {
            $token = (string) \Arr::get($payload, $key, '');
        }
        if ($token === '') {
            $token = (string) \Input::post($key, '');
        }

        $expected = (string) \Security::fetch_token();
        $valid = function_exists('hash_equals') ? hash_equals($expected, $token) : $expected === $token;

        if ($token === '' || !$valid) {
            return $this->json_error('Token de seguridad invalido o expirado.', ['csrf_invalid'], 403);
        }

        return null;
    }

    protected function payload()
    {
        $json = \Input::json();
        if (is_array($json) && !empty($json)) {
            return $json;
        }

        return \Input::post();
    }

    protected function json_error($message, array $errors, $status)
    {
        return $this->json_response([
            'success' => false,
            'message' => $message,
            'data' => [],
            'errors' => $errors,
        ], $status);
    }

    protected function service()
    {
        if (!$this->library) {
            $this->library = new \Service_Core_Software_Library();
        }
        return $this->library;
    }
}
