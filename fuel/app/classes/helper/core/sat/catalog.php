<?php

class Helper_Core_Sat_Catalog
{
    public static function options($table, $value_field = 'code', $label_field = 'name')
    {
        if (!\DBUtil::table_exists($table)) {
            return [];
        }

        $rows = \DB::select($value_field, $label_field)
            ->from($table)
            ->where('active', '=', 1)
            ->order_by($value_field, 'asc')
            ->execute();

        $options = [];
        foreach ($rows as $row) {
            $value = (string) $row[$value_field];
            $label = trim($value.' - '.(string) $row[$label_field]);
            $options[] = [
                'value' => $value,
                'label' => $label,
                'name' => (string) $row[$label_field],
            ];
        }

        return $options;
    }

    public static function label($table, $code, $empty = '-')
    {
        $code = trim((string) $code);
        if ($code === '' || !\DBUtil::table_exists($table)) {
            return $empty;
        }

        $row = \DB::select('name')->from($table)->where('code', '=', $code)->execute()->current();
        return $row ? $code.' - '.$row['name'] : $code;
    }
}
