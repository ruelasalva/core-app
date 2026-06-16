<?php

class Service_Core_Workspace_Widgets_Erp_Opentickets extends \Service_Core_Workspace_Widgets_Erp_Base
{
    public static function manifest()
    {
        return [
            'code' => 'open_tickets',
            'title' => 'Tickets abiertos',
            'description' => 'Últimos tickets abiertos de Helpdesk.',
            'category' => 'support',
            'type' => 'tasks',
            'icon' => 'bi bi-life-preserver',
            'color' => 'danger',
            'permission_key' => 'helpdesk.access[view]',
            'refresh_time' => 300,
            'dependencies' => [],
            'exportable' => false,
            'configurable' => false,
            'settings_schema' => [],
            'version' => 1,
            'status' => 'active',
        ];
    }

    public function load($context, array $filters = [], array $settings = [])
    {
        if (!$this->table_exists('core_helpdesk_tickets')) {
            return $this->payload([]);
        }

        try {
            $open_status_ids = $this->open_status_ids();
            if (empty($open_status_ids)) {
                return $this->payload([]);
            }

            $rows = \DB::select('id', 'folio', 'subject', 'party_id', 'status_id', 'created_at')
                ->from('core_helpdesk_tickets')
                ->where('active', '=', 1)
                ->where('status_id', 'in', $open_status_ids)
                ->order_by('created_at', 'desc')
                ->limit(5)
                ->execute()
                ->as_array();
        } catch (\Exception $e) {
            $this->safe_query_error('open_tickets', $e);
            return $this->payload([]);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'folio' => (string) \Arr::get($row, 'folio', 'HD-'.$row['id']),
                'subject' => (string) \Arr::get($row, 'subject', '-'),
                'party' => $this->party_name(\Arr::get($row, 'party_id', 0)),
                'status' => $this->status_name(\Arr::get($row, 'status_id', 0)),
                'date' => $this->date_label(\Arr::get($row, 'created_at', 0)),
            ];
        }

        return $this->payload($items);
    }

    protected function open_status_ids()
    {
        if (!$this->table_exists('core_helpdesk_statuses')) {
            return [];
        }

        $rows = \DB::select('id')
            ->from('core_helpdesk_statuses')
            ->where('active', '=', 1)
            ->where('is_closed', '=', 0)
            ->execute()
            ->as_array();

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) \Arr::get($row, 'id', 0);
        }

        return array_filter($ids);
    }

    protected function status_name($status_id)
    {
        $status_id = (int) $status_id;
        if ($status_id < 1 || !$this->table_exists('core_helpdesk_statuses')) {
            return '-';
        }

        try {
            $row = \DB::select('name')
                ->from('core_helpdesk_statuses')
                ->where('id', '=', $status_id)
                ->execute()
                ->current();
        } catch (\Exception $e) {
            return '-';
        }

        return $row ? (string) \Arr::get($row, 'name', '-') : '-';
    }

    protected function payload(array $rows)
    {
        return $this->compact_table_payload(
            [
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'subject', 'label' => 'Asunto'],
                ['key' => 'party', 'label' => 'Tercero'],
                ['key' => 'status', 'label' => 'Estatus'],
                ['key' => 'date', 'label' => 'Fecha'],
            ],
            $rows,
            [
                'icon' => 'bi bi-life-preserver',
                'title' => 'Sin tickets abiertos.',
                'message' => 'No hay tickets abiertos para mostrar.',
            ],
            [
                'label' => 'Abrir Helpdesk',
                'url' => \Uri::create('admin/helpdesk'),
                'icon' => 'bi bi-arrow-right',
            ]
        );
    }
}
