<?php

/**
 * SERVICE CORE_WORKSPACE_WIDGETS_ERP_BASE
 *
 * Utilidades read-only para widgets ERP del Workspace.
 */
abstract class Service_Core_Workspace_Widgets_Erp_Base extends \Service_Core_Workspace_Widget
{
    protected function compact_table_payload(array $columns, array $rows, array $empty, array $action = [])
    {
        return [
            'render' => 'compact_table',
            'columns' => $columns,
            'rows' => array_slice($rows, 0, 5),
            'empty_icon' => \Arr::get($empty, 'icon', 'bi bi-info-circle'),
            'empty_title' => \Arr::get($empty, 'title', 'Sin datos disponibles.'),
            'empty_message' => \Arr::get($empty, 'message', 'No hay información para mostrar.'),
            'action' => $action,
        ];
    }

    protected function table_exists($table)
    {
        return \DBUtil::table_exists($table);
    }

    protected function field($table, array $candidates)
    {
        if (!$this->table_exists($table)) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if (\DBUtil::field_exists($table, [$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    protected function active_filter($query, $table)
    {
        if ($this->field($table, ['active'])) {
            $query->where('active', '=', 1);
        }

        return $query;
    }

    protected function party_name($party_id)
    {
        $party_id = (int) $party_id;
        if ($party_id < 1 || !$this->table_exists('core_parties')) {
            return 'Sin tercero';
        }

        $name_field = $this->field('core_parties', ['business_name', 'commercial_name', 'name', 'legal_name']);
        if (!$name_field) {
            return 'Tercero #'.$party_id;
        }

        try {
            $row = \DB::select($name_field)
                ->from('core_parties')
                ->where('id', '=', $party_id)
                ->execute()
                ->current();
        } catch (\Exception $e) {
            \Log::warning('Workspace ERP party lookup failed party_id='.$party_id);
            return 'Tercero #'.$party_id;
        }

        $name = trim((string) \Arr::get($row ?: [], $name_field, ''));
        return $name !== '' ? $name : 'Tercero #'.$party_id;
    }

    protected function money($value)
    {
        return '$'.number_format((float) $value, 2, '.', ',');
    }

    protected function decimal($value)
    {
        return number_format((float) $value, 2, '.', ',');
    }

    protected function date_label($value)
    {
        if (is_numeric($value) && (int) $value > 0) {
            return date('d/m/Y', (int) $value);
        }

        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('d/m/Y', $timestamp) : $value;
    }

    protected function safe_query_error($widget_code, \Exception $e)
    {
        \Log::warning('Workspace widget query failed code='.$widget_code.' message='.$e->getMessage());
    }
}
