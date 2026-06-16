<?php

/**
 * SERVICE CORE_WORKSPACE_WIDGET
 *
 * Clase base para widgets del Workspace.
 */
abstract class Service_Core_Workspace_Widget
{
    const STATE_LOADING = 'loading';
    const STATE_READY = 'ready';
    const STATE_EMPTY = 'empty';
    const STATE_ERROR = 'error';
    const STATE_FORBIDDEN = 'forbidden';
    const STATE_DISABLED = 'disabled';

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
            'cache_level' => Service_Core_Workspace_Cache::LEVEL_NONE,
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
        $payload = is_array($payload) ? $payload : [];
        $html = $this->render_html($payload);
        $payload['html'] = $html;
        $state = $this->is_empty_payload($payload) ? static::STATE_EMPTY : static::STATE_READY;

        return [
            'success' => true,
            'message' => '',
            'state' => $state,
            'payload' => $payload,
            'meta' => $meta,
            'health' => array_merge($this->default_health(), $health),
            'actions' => [],
            'errors' => [],
        ];
    }

    protected function render_html(array $payload)
    {
        $layout = (string) \Arr::get($payload, 'layout', '');
        if ($layout === 'welcome') {
            return $this->render_welcome($payload);
        }

        if ($layout === 'action_grid') {
            return $this->render_action_grid($payload);
        }

        if ($layout === 'empty_state') {
            return $this->render_empty_state($payload);
        }

        if ($layout === 'timeline') {
            return $this->render_timeline($payload);
        }

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
                $icon = (string) \Arr::get($item, 'icon', '');
                $url = (string) \Arr::get($item, 'url', '');
                $prefix = $icon !== '' ? '<i class="'.e($icon).' mr-1"></i> ' : '';
                if ($url !== '' && $url !== '#') {
                    $html .= '<li><a href="'.e($url).'">'.$prefix.$label.'</a></li>';
                } else {
                    $html .= '<li>'.$prefix.$label.'</li>';
                }
            }
            $html .= '</ul>';
        }

        if (!empty($payload['groups']) && is_array($payload['groups'])) {
            foreach ($payload['groups'] as $group) {
                $title = e(\Arr::get($group, 'title', ''));
                if ($title !== '') {
                    $html .= '<div class="small text-uppercase text-muted font-weight-bold mt-2 mb-1">'.$title.'</div>';
                }

                $html .= '<ul class="list-unstyled mb-2">';
                foreach ((array) \Arr::get($group, 'items', []) as $item) {
                    $label = e(\Arr::get($item, 'label', ''));
                    $icon = (string) \Arr::get($item, 'icon', '');
                    $url = (string) \Arr::get($item, 'url', '');
                    $prefix = $icon !== '' ? '<i class="'.e($icon).' mr-1"></i> ' : '';
                    if ($url !== '' && $url !== '#') {
                        $html .= '<li><a href="'.e($url).'">'.$prefix.$label.'</a></li>';
                    } else {
                        $html .= '<li>'.$prefix.$label.'</li>';
                    }
                }
                $html .= '</ul>';
            }
        }

        if (!empty($payload['empty_message'])) {
            $html .= '<div class="text-muted">'.e($payload['empty_message']).'</div>';
        }

        return $html;
    }

    protected function render_welcome(array $payload)
    {
        $action = \Arr::get($payload, 'suggested_action', []);
        $action_url = (string) \Arr::get($action, 'url', '');
        $action_label = (string) \Arr::get($action, 'label', '');

        $html = '<div class="workspace-welcome">';
        $html .= '<div class="workspace-welcome-main">';
        $html .= '<div class="workspace-eyebrow">'.e(\Arr::get($payload, 'date_label', '')).'</div>';
        $html .= '<h4>'.e(\Arr::get($payload, 'greeting', '')).'</h4>';
        $html .= '<p>'.e(\Arr::get($payload, 'message', '')).'</p>';
        $html .= '</div>';
        $html .= '<div class="workspace-welcome-meta">';
        $html .= '<span><i class="bi bi-person-badge mr-1"></i>'.e(\Arr::get($payload, 'role_label', 'Usuario')).'</span>';
        $html .= '<span><i class="bi bi-check-circle mr-1"></i>'.e(\Arr::get($payload, 'status_label', 'Workspace operativo')).'</span>';
        $html .= '</div>';

        if ($action_url !== '' && $action_label !== '') {
            $html .= '<a class="btn btn-primary btn-sm mt-2" href="'.e($action_url).'">';
            $html .= '<i class="bi bi-arrow-right-circle mr-1"></i>'.e($action_label);
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function render_action_grid(array $payload)
    {
        $html = '';

        if (!empty($payload['description'])) {
            $html .= '<div class="text-muted mb-2">'.e($payload['description']).'</div>';
        }

        if (!empty($payload['empty_title']) || !empty($payload['empty_message'])) {
            $html .= $this->render_empty_state($payload);
        }

        if (!empty($payload['section_title'])) {
            $html .= '<div class="workspace-action-group-title">'.e($payload['section_title']).'</div>';
        }

        if (!empty($payload['groups']) && is_array($payload['groups'])) {
            foreach ($payload['groups'] as $group) {
                $title = e(\Arr::get($group, 'title', ''));
                if ($title !== '') {
                    $html .= '<div class="workspace-action-group-title">'.$title.'</div>';
                }
                $html .= $this->render_action_buttons((array) \Arr::get($group, 'items', []));
            }
            return $html;
        }

        return $html.$this->render_action_buttons((array) \Arr::get($payload, 'items', []));
    }

    protected function render_action_buttons(array $items)
    {
        if (empty($items)) {
            return '';
        }

        $html = '<div class="workspace-action-list list-group list-group-flush">';
        foreach ($items as $item) {
            $label = e(\Arr::get($item, 'label', ''));
            $url = (string) \Arr::get($item, 'url', '#');
            $icon = (string) \Arr::get($item, 'icon', 'bi bi-lightning');
            $color = preg_replace('/[^a-z0-9_-]/i', '', (string) \Arr::get($item, 'color', 'secondary'));

            $html .= '<a class="workspace-action-row list-group-item list-group-item-action" href="'.e($url).'">';
            $html .= '<span class="workspace-action-icon text-'.$color.'"><i class="'.e($icon).'"></i></span>';
            $html .= '<span class="workspace-action-title">'.$label.'</span>';
            $html .= '<span class="workspace-action-arrow"><i class="bi bi-chevron-right"></i></span>';
            $html .= '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    protected function render_empty_state(array $payload)
    {
        $title = (string) \Arr::get($payload, 'empty_title', '');
        $message = (string) \Arr::get($payload, 'empty_message', '');
        $icon = (string) \Arr::get($payload, 'empty_icon', 'bi bi-info-circle');

        $html = '<div class="workspace-empty-state">';
        if ($title !== '') {
            $html .= '<strong><span class="workspace-empty-icon"><i class="'.e($icon).'"></i></span>'.e($title).'</strong>';
        }
        if ($message !== '') {
            $html .= '<p>'.e($message).'</p>';
        }
        $html .= '</div>';

        return $html;
    }

    protected function render_timeline(array $payload)
    {
        $items = (array) \Arr::get($payload, 'items', []);
        if (empty($items)) {
            return $this->render_empty_state($payload);
        }

        $html = '<div class="workspace-timeline">';
        foreach ($items as $item) {
            $html .= '<div class="workspace-timeline-item">';
            $html .= '<span class="workspace-timeline-dot"></span>';
            $html .= '<div>';
            $html .= '<div>'.e(\Arr::get($item, 'label', '')).'</div>';
            $created_at = (int) \Arr::get($item, 'created_at', 0);
            if ($created_at > 0) {
                $html .= '<small class="text-muted">'.date('d/m/Y H:i', $created_at).'</small>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    protected function is_empty_payload(array $payload)
    {
        if (!empty($payload['value'])) {
            return false;
        }

        if (!empty($payload['items']) && is_array($payload['items'])) {
            return false;
        }

        if (!empty($payload['rows']) && is_array($payload['rows'])) {
            return false;
        }

        return !empty($payload['empty_message']);
    }

    protected function default_health()
    {
        return [
            'generated_at' => date('c'),
            'cache_until' => null,
            'execution_ms' => 0,
            'cache_hit' => false,
            'stale' => false,
            'warning' => '',
        ];
    }

    public function export($payload, $format = 'csv')
    {
        return null;
    }
}
