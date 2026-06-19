<?php

class Service_Core_Email_TemplateRenderer
{
    public function render($content, array $variables = [], array &$warnings = [])
    {
        $content = (string) $content;
        $variables = array_merge($this->global_variables(), $variables);
        $warnings = is_array($warnings) ? $warnings : [];

        foreach ($variables as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $safe_key = preg_replace('/[^a-zA-Z0-9_\.\-]/', '', (string) $key);
            if ($safe_key === '') {
                continue;
            }

            $content = str_replace('{{'.$safe_key.'}}', e((string) $value), $content);
        }

        $missing = $this->missing_variables($content);
        foreach ($missing as $variable) {
            $warnings[] = 'Variable sin valor: '.$variable;
        }

        return $content;
    }

    public function global_variables()
    {
        $company = $this->company_data();

        return [
            'app_name' => 'CORE-APP ERP',
            'company_name' => $company['name'],
            'company_email' => $company['email'],
            'company_phone' => $company['phone'],
            'company_website' => $company['website'],
            'current_date' => date('Y-m-d'),
            'current_year' => date('Y'),
            'user_name' => '',
        ];
    }

    public function available_global_variables()
    {
        return array_keys($this->global_variables());
    }

    public function extract_variables($content)
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_\.\-]+)\s*}}/', (string) $content, $matches);
        $variables = isset($matches[1]) ? $matches[1] : [];

        return array_values(array_unique($variables));
    }

    public function missing_variables($content)
    {
        return $this->extract_variables($content);
    }

    protected function company_data()
    {
        $data = [
            'name' => 'CORE-APP ERP',
            'email' => '',
            'phone' => '',
            'website' => '',
        ];

        if (!\DBUtil::table_exists('core_companies')) {
            return $data;
        }

        try {
            $row = \DB::select()
                ->from('core_companies')
                ->where('active', '=', 1)
                ->order_by('id', 'asc')
                ->execute()
                ->current();

            if (!$row) {
                return $data;
            }

            foreach (['name', 'email', 'phone', 'website'] as $field) {
                if (array_key_exists($field, $row) && trim((string) $row[$field]) !== '') {
                    $data[$field] = (string) $row[$field];
                }
            }
        } catch (\Exception $e) {
            \Log::warning('No se pudieron leer variables globales de empresa: '.$e->getMessage());
        }

        return $data;
    }
}
