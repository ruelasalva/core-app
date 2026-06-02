<?php

/**
 * SERVICE CORE SUPPLIERIMPORT NORMALIZER
 *
 * Normaliza filas de proveedores a una estructura comun.
 * No escribe en base de datos.
 */
class Service_Core_SupplierImport_Normalizer
{
    public function normalize(array $row, array $context = [])
    {
        $provider_code = $this->text(\Arr::get($context, 'provider_code', \Arr::get($row, 'provider_code', '')));
        $supplier_sku = $this->code(\Arr::get($row, 'supplier_sku', \Arr::get($row, 'sku', '')));
        $supplier_model = $this->code(\Arr::get($row, 'supplier_model', \Arr::get($row, 'model', '')));
        $external_id = $this->text(\Arr::get($row, 'external_id', ''));
        $supplier_name = $this->text(\Arr::get($row, 'supplier_name', \Arr::get($row, 'name', '')));
        $supplier_brand = $this->text(\Arr::get($row, 'supplier_brand', \Arr::get($row, 'brand', '')));
        $source_url = $this->text(\Arr::get($row, 'source_url', \Arr::get($row, 'url', '')));
        $supplier_price = $this->money(\Arr::get($row, 'supplier_price', \Arr::get($row, 'price', 0)));
        $selling_price = $supplier_price > 0 ? round($supplier_price * 1.23, 6) : 0;
        $warnings = [];

        if ($supplier_price <= 0) {
            $warnings[] = 'Precio de proveedor no informado.';
        }

        return [
            'provider_code' => $provider_code,
            'external_id' => $external_id,
            'source_url' => $source_url,
            'supplier_sku' => $supplier_sku,
            'supplier_model' => $supplier_model,
            'supplier_name' => $supplier_name,
            'supplier_brand' => $supplier_brand,
            'supplier_category' => $this->text(\Arr::get($row, 'supplier_category', \Arr::get($row, 'category', ''))),
            'supplier_description' => $this->text(\Arr::get($row, 'supplier_description', \Arr::get($row, 'description', ''))),
            'supplier_compatibility' => $this->text(\Arr::get($row, 'supplier_compatibility', \Arr::get($row, 'compatibility', ''))),
            'supplier_currency' => $this->currency(\Arr::get($row, 'supplier_currency', \Arr::get($row, 'currency', 'MXN'))),
            'supplier_cost' => $this->money(\Arr::get($row, 'supplier_cost', \Arr::get($row, 'cost', 0))),
            'supplier_price' => $supplier_price,
            'supplier_stock' => $this->money(\Arr::get($row, 'supplier_stock', \Arr::get($row, 'stock', 0))),
            'selling_price' => $selling_price,
            'image_url' => $this->text(\Arr::get($row, 'image_url', \Arr::get($row, 'image', ''))),
            'source_hash' => $this->source_hash($provider_code, $supplier_sku, $supplier_model, $supplier_name, $supplier_price, $source_url),
            'raw_json' => json_encode($row),
            'warnings' => $warnings,
        ];
    }

    public function source_hash($provider_code, $supplier_sku, $supplier_model, $supplier_name, $supplier_price, $source_url)
    {
        return hash('sha256', implode('|', [
            'supplier_import',
            trim((string) $provider_code),
            trim((string) $supplier_sku),
            trim((string) $supplier_model),
            trim((string) $supplier_name),
            number_format((float) $supplier_price, 6, '.', ''),
            trim((string) $source_url),
        ]));
    }

    protected function text($value)
    {
        return trim((string) $value);
    }

    protected function code($value)
    {
        return strtoupper(trim((string) $value));
    }

    protected function currency($value)
    {
        $value = strtoupper(substr(preg_replace('/[^A-Z0-9]+/i', '', (string) $value), 0, 5));
        return $value !== '' ? $value : 'MXN';
    }

    protected function money($value)
    {
        if (is_string($value)) {
            $value = trim($value);
            $value = str_replace(['$', ' ', "\t"], '', $value);

            if (preg_match('/^\d{1,3}(\.\d{3})+,\d+$/', $value)) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        }

        return max(0, (float) $value);
    }
}
