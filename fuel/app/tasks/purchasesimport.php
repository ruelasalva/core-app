<?php
namespace Fuel\Tasks;

/**
 * TASK PURCHASESIMPORT
 *
 * Importa datos de ejemplo desde la base local Sajor hacia el modulo limpio de Compras.
 *
 * Uso: php oil r purchasesimport
 */
class Purchasesimport
{
    protected $sajor;

    public function run()
    {
        try {
            $this->assert_schema_ready();
            $this->sajor = new \PDO('mysql:host=localhost;dbname=sajor;charset=utf8', 'root', '');
            $this->sajor->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $provider_map = $this->import_suppliers();
            $order_map = $this->import_orders($provider_map);
            $invoice_count = $this->import_invoices($provider_map, $order_map);

            echo "\n [SUCCESS] Importacion de compras desde Sajor terminada.\n";
            echo " - Proveedores vinculados/importados: ".count($provider_map)."\n";
            echo " - Ordenes importadas: ".count($order_map)."\n";
            echo " - Facturas importadas: ".$invoice_count."\n";
        } catch (\Exception $e) {
            echo "\n [ERROR] ".$e->getMessage()."\n";
            \Log::error('Fallo purchasesimport: '.$e->getMessage());
        }
    }

    protected function import_suppliers()
    {
        $map = [];
        $rows = $this->sajor->query('SELECT id, name, code_sap, rfc, payment_terms_id, created_at, updated_at FROM providers ORDER BY id ASC LIMIT 500')->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $code = trim((string) $row['code_sap']) ?: 'SAJOR-PROV-'.$row['id'];
            $exists = \DB::select('id')->from('core_parties')->where('code', '=', $code)->execute()->current();
            if ($exists) {
                $map[(int) $row['id']] = (int) $exists['id'];
                continue;
            }

            list($id) = \DB::insert('core_parties')->set([
                'party_type' => 'supplier',
                'code' => $code,
                'name' => trim((string) $row['name']) ?: 'Proveedor Sajor '.$row['id'],
                'legal_name' => trim((string) $row['name']),
                'rfc' => trim((string) $row['rfc']),
                'email' => '',
                'phone' => '',
                'payment_term_id' => 0,
                'notes' => 'Importado desde Sajor provider_id='.$row['id'],
                'active' => 1,
                'created_at' => (int) $row['created_at'] ?: time(),
                'updated_at' => (int) $row['updated_at'] ?: time(),
            ])->execute();
            $map[(int) $row['id']] = (int) $id;
        }
        return $map;
    }

    protected function import_orders(array $provider_map)
    {
        $map = [];
        $sql = 'SELECT id, provider_id, department_id, code_order, date_order, payment_date, subtotal, iva, retencion, total, status, notes, authorized_at, authorized_by, invoiced_total, balance_total, created_by, created_at, updated_at FROM providers_orders WHERE deleted = 0 ORDER BY id DESC LIMIT 300';
        $rows = $this->sajor->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if (empty($provider_map[(int) $row['provider_id']])) {
                continue;
            }
            $folio = trim((string) $row['code_order']) ?: 'OC-SAJOR-'.$row['id'];
            $exists = \DB::select('id')->from('core_purchase_orders')->where('external_reference', '=', 'sajor:providers_orders:'.$row['id'])->execute()->current();
            if ($exists) {
                $map[(int) $row['id']] = (int) $exists['id'];
                continue;
            }

            list($id) = \DB::insert('core_purchase_orders')->set([
                'folio' => $this->unique_folio($folio, 'core_purchase_orders'),
                'source' => 'sajor_import',
                'party_id' => $provider_map[(int) $row['provider_id']],
                'department_id' => (int) $row['department_id'],
                'requested_by' => (int) $row['created_by'],
                'authorized_by' => (int) $row['authorized_by'],
                'authorized_at' => (int) $row['authorized_at'],
                'order_date' => $this->date_value($row['date_order']),
                'expected_date' => $this->date_value($row['payment_date']),
                'currency_code' => 'MXN',
                'subtotal' => (float) $row['subtotal'],
                'tax_total' => (float) $row['iva'],
                'retention_total' => (float) $row['retencion'],
                'total' => (float) $row['total'],
                'invoiced_total' => (float) $row['invoiced_total'],
                'balance_total' => (float) $row['balance_total'],
                'status' => $this->order_status((int) $row['status']),
                'notes' => (string) $row['notes'],
                'external_reference' => 'sajor:providers_orders:'.$row['id'],
                'created_by' => (int) $row['created_by'],
                'active' => 1,
                'created_at' => (int) $row['created_at'] ?: time(),
                'updated_at' => (int) $row['updated_at'] ?: time(),
            ])->execute();
            $map[(int) $row['id']] = (int) $id;
            $this->import_order_items((int) $row['id'], (int) $id);
        }
        return $map;
    }

    protected function import_order_items($sajor_order_id, $core_order_id)
    {
        $stmt = $this->sajor->prepare('SELECT product_id, code_product, description, quantity, unit_price, subtotal, iva, retencion, total, tax_id, currency_id, created_at, updated_at FROM providers_orders_details WHERE order_id = ? AND deleted = 0 ORDER BY id ASC');
        $stmt->execute([$sajor_order_id]);
        $sort = 10;
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            \DB::insert('core_purchase_order_items')->set([
                'order_id' => $core_order_id,
                'product_id' => 0,
                'sku' => (string) $row['code_product'],
                'description' => trim((string) $row['description']) ?: 'Concepto Sajor',
                'quantity' => (float) $row['quantity'],
                'unit_price' => (float) $row['unit_price'],
                'tax_rate' => ((float) $row['subtotal'] > 0) ? round(((float) $row['iva'] / (float) $row['subtotal']), 6) : 0,
                'tax_amount' => (float) $row['iva'],
                'retention_amount' => (float) $row['retencion'],
                'line_total' => (float) $row['total'],
                'sort_order' => $sort,
                'active' => 1,
                'created_at' => (int) $row['created_at'] ?: time(),
                'updated_at' => (int) $row['updated_at'] ?: time(),
            ])->execute();
            $sort += 10;
        }
    }

    protected function import_invoices(array $provider_map, array $order_map)
    {
        $count = 0;
        $sql = 'SELECT id, provider_id, order_id, uuid, invoice_date, payment_date, subtotal, iva, retencion, total, status, estatus_sat, mensaje_sat, message, created_by, created_at, updated_at FROM providers_bills WHERE deleted = 0 ORDER BY id DESC LIMIT 300';
        $rows = $this->sajor->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if (empty($provider_map[(int) $row['provider_id']])) {
                continue;
            }
            $exists = \DB::select('id')->from('core_purchase_invoices')->where('folio', '=', 'FCP-SAJOR-'.$row['id'])->execute()->current();
            if ($exists) {
                continue;
            }
            \DB::insert('core_purchase_invoices')->set([
                'folio' => 'FCP-SAJOR-'.$row['id'],
                'party_id' => $provider_map[(int) $row['provider_id']],
                'order_id' => !empty($order_map[(int) $row['order_id']]) ? $order_map[(int) $row['order_id']] : 0,
                'uuid' => strtoupper(trim((string) $row['uuid'])),
                'invoice_date' => $this->date_value($row['invoice_date']),
                'due_date' => $this->date_value($row['payment_date']),
                'currency_code' => 'MXN',
                'subtotal' => (float) $row['subtotal'],
                'tax_total' => (float) $row['iva'],
                'retention_total' => (float) $row['retencion'],
                'total' => (float) $row['total'],
                'balance_due' => (float) $row['total'],
                'status' => $this->invoice_status((int) $row['status']),
                'validation_status' => $this->invoice_validation_status((int) $row['status']),
                'sat_status' => (string) $row['estatus_sat'],
                'message' => trim((string) $row['message'].' '.(string) $row['mensaje_sat']),
                'created_by' => (int) $row['created_by'],
                'active' => 1,
                'created_at' => (int) $row['created_at'] ?: time(),
                'updated_at' => (int) $row['updated_at'] ?: time(),
            ])->execute();
            $count++;
        }
        return $count;
    }

    protected function unique_folio($folio, $table)
    {
        $base = preg_replace('/[^A-Za-z0-9\-_]+/', '-', trim((string) $folio)) ?: 'SAJOR';
        $candidate = $base;
        $i = 2;
        while (\DB::select('id')->from($table)->where('folio', '=', $candidate)->execute()->current()) {
            $candidate = $base.'-'.$i;
            $i++;
        }
        return $candidate;
    }

    protected function date_value($value)
    {
        if (is_numeric($value) && (int) $value > 0) {
            return date('Y-m-d', (int) $value);
        }
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}/', $value) ? substr($value, 0, 10) : date('Y-m-d');
    }

    protected function order_status($status)
    {
        if (in_array($status, [4, 99], true)) {
            return 'cancelled';
        }
        if (in_array($status, [3, 9], true)) {
            return 'closed';
        }
        if ($status === 2) {
            return 'partial';
        }
        if ($status === 1) {
            return 'authorized';
        }
        return 'draft';
    }

    protected function invoice_status($status)
    {
        if (in_array($status, [9, 10], true)) {
            return 'paid';
        }
        if (in_array($status, [4, 99], true)) {
            return 'cancelled';
        }
        if (in_array($status, [2, 3], true)) {
            return 'in_review';
        }
        return 'submitted';
    }

    protected function invoice_validation_status($status)
    {
        if (in_array($status, [3, 9, 10], true)) {
            return 'validated';
        }
        if (in_array($status, [4, 99], true)) {
            return 'rejected';
        }
        return 'pending';
    }

    protected function assert_schema_ready()
    {
        foreach (['core_parties', 'core_purchase_orders', 'core_purchase_order_items', 'core_purchase_invoices'] as $table) {
            if (!\DBUtil::table_exists($table)) {
                throw new \Exception('Primero ejecuta: php oil refine migrate');
            }
        }
    }
}
