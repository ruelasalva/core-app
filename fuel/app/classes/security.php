<?php

class Security extends \Fuel\Core\Security
{
    public static function check_token($value = null)
    {
        if (parent::check_token($value)) {
            return true;
        }

        $header = \Input::headers('X-CSRF-Token', '');
        if ($header !== '' && $header === static::$csrf_old_token) {
            return true;
        }

        $uri = trim((string) \Input::uri(), '/');
        $content_type = (string) \Input::headers('Content-Type', '');
        $is_json = stripos($content_type, 'application/json') !== false;
        $is_portal_ajax = $is_json && preg_match('#^(admin|clientes|proveedores|socios|revendedores)/#', $uri);

        if ($is_portal_ajax && class_exists('Auth') && \Auth::check()) {
            \Log::warning('CSRF JSON fallback autenticado para '.$uri);
            return true;
        }

        return false;
    }
}
