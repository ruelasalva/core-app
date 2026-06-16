<?php

class Service_Core_Workspace_Widgets_Erp_Orderspendingdelivery extends \Service_Core_Workspace_Widgets_Erp_Base
{
    public static function manifest()
    {
        return [
            'code' => 'orders_pending_delivery',
            'title' => 'Pedidos por entregar',
            'description' => 'Pedidos abiertos o parcialmente surtidos.',
            'category' => 'commercial',
            'type' => 'list',
            'icon' => 'bi bi-truck',
            'color' => 'success',
            'permission_key' => 'sales.access[view]',
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
        $table = 'core_sales_orders';
        if (!$this->table_exists($table)) {
            return $this->payload([]);
        }

        $folio = $this->field($table, ['folio', 'order_number', 'code']);
        $party = $this->field($table, ['party_id', 'customer_id']);
        $total = $this->field($table, ['total']);
        $status = $this->field($table, ['status']);
        $date = $this->field($table, ['order_date', 'created_at']);

        if (!$folio || !$status) {
            return $this->payload([]);
        }

        try {
            $query = \DB::select('id', $folio, $status);
            foreach ([$party, $total, $date] as $field) {
                if ($field) {
                    $query->select($field);
                }
            }
            $this->active_filter($query->from($table), $table);
            $query->order_by($date ?: 'id', 'desc');
            $rows = $query->limit(25)->execute()->as_array();
        } catch (\Exception $e) {
            $this->safe_query_error('orders_pending_delivery', $e);
            return $this->payload([]);
        }

        $open_statuses = ['open', 'pending', 'pending_delivery', 'partial', 'approved'];
        $items = [];
        foreach ($rows as $row) {
            $status_value = strtolower((string) \Arr::get($row, $status, ''));
            if (!in_array($status_value, $open_statuses, true)) {
                continue;
            }

            $items[] = [
                'folio' => (string) \Arr::get($row, $folio, '-'),
                'customer' => $party ? $this->party_name(\Arr::get($row, $party, 0)) : 'Sin tercero',
                'total' => $total ? $this->money(\Arr::get($row, $total, 0)) : '-',
                'status' => $status_value,
                'date' => $date ? $this->date_label(\Arr::get($row, $date, '')) : '-',
            ];

            if (count($items) >= 5) {
                break;
            }
        }

        return $this->payload($items);
    }

    protected function payload(array $rows)
    {
        return $this->compact_table_payload(
            [
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'customer', 'label' => 'Cliente'],
                ['key' => 'total', 'label' => 'Total'],
                ['key' => 'status', 'label' => 'Estatus'],
                ['key' => 'date', 'label' => 'Fecha'],
            ],
            $rows,
            [
                'icon' => 'bi bi-truck',
                'title' => 'Sin pedidos pendientes de entrega.',
                'message' => 'No hay pedidos abiertos o parcialmente surtidos para mostrar.',
            ],
            [
                'label' => 'Abrir Ventas',
                'url' => \Uri::create('admin/sales?view=orders'),
                'icon' => 'bi bi-arrow-right',
            ]
        );
    }
}
