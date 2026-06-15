<?php

/**
 * SERVICE CORE_WORKSPACE_WIDGET
 *
 * Clase base para widgets del Workspace.
 */
abstract class Service_Core_Workspace_Widget
{
    public static function manifest()
    {
        return [
            'code' => '',
            'title' => '',
            'description' => '',
            'category' => 'system',
            'type' => 'metric',
            'icon' => 'bi bi-grid',
            'color' => 'primary',
            'permission_key' => 'workspace.access[view]',
            'refresh_time' => 300,
            'dependencies' => [],
            'exportable' => false,
            'configurable' => false,
            'settings_schema' => [],
            'version' => 1,
            'status' => 'active',
        ];
    }

    abstract public function load($context, array $filters = [], array $settings = []);

    public function authorize($context)
    {
        $manifest = static::manifest();
        $permission = isset($manifest['permission_key']) ? (string) $manifest['permission_key'] : 'workspace.access[view]';

        return !empty($context['is_super_admin']) || \Auth::has_access($permission);
    }

    public function cache_key($context, array $filters = [], array $settings = [])
    {
        $manifest = static::manifest();
        return 'workspace.widget.'.(string) $manifest['code'].'.'.(int) \Arr::get($context, 'user_id', 0).'.'.md5(json_encode([$filters, $settings]));
    }

    public function refresh_time()
    {
        $manifest = static::manifest();
        return (int) \Arr::get($manifest, 'refresh_time', 300);
    }

    public function actions($context)
    {
        return [];
    }

    public function response($payload, array $meta = [], array $health = [])
    {
        $html = $this->render_html(is_array($payload) ? $payload : []);

        return [
            'success' => true,
            'html' => $html,
            'message' => '',
            'refresh_time' => $this->refresh_time(),
        ];
    }

    protected function render_html(array $payload)
    {
        $html = '';

        if (!empty($payload['description'])) {
            $html .= '<div class="text-muted mb-2">'.e($payload['description']).'</div>';
        }

        if (!empty($payload['value'])) {
            $html .= '<h4>'.e($payload['value']).'</h4>';
        }

        if (!empty($payload['items']) && is_array($payload['items'])) {
            $html .= '<ul class="list-unstyled mb-0">';
            foreach ($payload['items'] as $item) {
                $label = e(\Arr::get($item, 'label', ''));
                $url = (string) \Arr::get($item, 'url', '');
                if ($url !== '' && $url !== '#') {
                    $html .= '<li><a href="'.e($url).'">'.$label.'</a></li>';
                } else {
                    $html .= '<li>'.$label.'</li>';
                }
            }
            $html .= '</ul>';
        }

        if (!empty($payload['empty_message'])) {
            $html .= '<div class="text-muted">'.e($payload['empty_message']).'</div>';
        }

        return $html;
    }

    public function export($payload, $format = 'csv')
    {
        return null;
    }
}
