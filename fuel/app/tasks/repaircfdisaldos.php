<?php
namespace Fuel\Tasks;

/**
 * TAREA REPAIRCFDISALDOS
 *
 * Recalcula saldos de facturas SAT importadas y aplica REP descargados.
 *
 * @package  app
 */
class Repaircfdisaldos
{
    /**
     * RUN
     *
     * REPARA SALDOS DE CXC CREADOS DESDE AUDITORIA SAT.
     *
     * @access  public
     * @return  Void
     */
    public function run()
    {
        $now = time();
        $restored = \DB::query("
            UPDATE core_billing_invoices i
            LEFT JOIN (
                SELECT entity_id, COALESCE(SUM(amount),0) AS allocated
                FROM core_payment_allocations
                WHERE entity_type = 'billing_invoice' AND active = 1
                GROUP BY entity_id
            ) a ON a.entity_id = i.id
            SET i.balance_due = GREATEST(0, i.total - COALESCE(a.allocated,0)),
                i.status = CASE
                    WHEN GREATEST(0, i.total - COALESCE(a.allocated,0)) <= 0 THEN 'paid'
                    WHEN COALESCE(a.allocated,0) > 0 THEN 'partial'
                    ELSE 'stamped'
                END,
                i.updated_at = ".$now."
            WHERE i.active = 1
              AND i.invoice_type = 'sale'
              AND i.source_module = 'sat_cfdi'
        ")->execute();

        $created = $this->apply_rep_payments($now);

        $summary = \DB::query("
            SELECT COUNT(*) AS imported_sales,
                   COALESCE(SUM(total),0) AS total,
                   COALESCE(SUM(balance_due),0) AS balance
            FROM core_billing_invoices
            WHERE active = 1
              AND invoice_type = 'sale'
              AND source_module = 'sat_cfdi'
        ")->execute()->current();

        \Cli::write('Facturas recalculadas: '.$restored);
        \Cli::write('Pagos REP creados: '.$created);
        \Cli::write('Ventas SAT: '.$summary['imported_sales'].' Total: '.$summary['total'].' Saldo: '.$summary['balance']);
    }

    /**
     * APPLY REP PAYMENTS
     *
     * CREA PAGOS DE SISTEMA DESDE REP SAT NO MATERIALIZADOS.
     *
     * @access  protected
     * @return  Int
     */
    protected function apply_rep_payments($now)
    {
        $base = 'PAY-REP-'.date('Ymd').'-';
        $count = (int) \DB::query("SELECT COUNT(*) AS total FROM core_payments WHERE folio LIKE '".$base."%'")->execute()->get('total', 0);
        $created = 0;

        $rows = \DB::query("
            SELECT pd.id AS payment_detail_id,
                   pd.paid_amount,
                   pd.currency,
                   p.uuid AS payment_uuid,
                   p.issued_at AS payment_date,
                   p.payment_form,
                   i.id AS invoice_id,
                   i.folio AS invoice_folio,
                   i.party_id,
                   i.currency_code,
                   i.issue_date,
                   i.balance_due
            FROM core_sat_payment_details pd
            LEFT JOIN core_sat_cfdi p ON p.id = pd.payment_cfdi_id
            INNER JOIN core_billing_invoices i ON UPPER(i.uuid) = UPPER(pd.invoice_uuid)
            LEFT JOIN core_payments pay ON pay.external_id = CONCAT('sat_rep:', pd.id)
            WHERE i.active = 1
              AND i.invoice_type = 'sale'
              AND i.source_module = 'sat_cfdi'
              AND pd.paid_amount > 0
              AND pay.id IS NULL
            ORDER BY pd.id ASC
        ")->execute();

        foreach ($rows as $row) {
            $invoice = \Model_Core_Billing_Invoice::find((int) $row['invoice_id']);
            if (!$invoice || (float) $invoice->balance_due <= 0) {
                continue;
            }

            $amount = min((float) $row['paid_amount'], (float) $invoice->balance_due);
            if ($amount <= 0) {
                continue;
            }

            $count++;
            $payment = \Model_Core_Payment::forge([
                'folio' => $base.str_pad((string) $count, 5, '0', STR_PAD_LEFT),
                'payment_type' => 'received',
                'party_id' => (int) $invoice->party_id,
                'bank_account_id' => 0,
                'integration_connection_id' => 0,
                'fiscal_document_id' => 0,
                'fiscal_mode' => 'fiscal_required',
                'rep_status' => 'stamped',
                'payment_date' => $row['payment_date'] ? substr((string) $row['payment_date'], 0, 10) : (string) $invoice->issue_date,
                'currency_code' => (string) $row['currency'] ?: (string) $invoice->currency_code,
                'exchange_rate' => 1,
                'amount' => round($amount, 2),
                'sat_payment_form_code' => (string) $row['payment_form'] ?: '99',
                'reference' => 'REP SAT '.$row['payment_uuid'],
                'external_id' => 'sat_rep:'.(int) $row['payment_detail_id'],
                'status' => 'confirmed',
                'notes' => 'Cobro creado desde REP SAT importado para factura '.$invoice->folio,
                'created_by' => 1,
                'active' => 1,
            ]);
            $payment->save();

            \Model_Core_Payment_Allocation::forge([
                'payment_id' => (int) $payment->id,
                'entity_type' => 'billing_invoice',
                'entity_id' => (int) $invoice->id,
                'amount' => round($amount, 2),
                'notes' => 'Aplicacion automatica desde REP SAT '.$row['payment_uuid'],
                'active' => 1,
            ])->save();

            $invoice->balance_due = round(max(0, (float) $invoice->balance_due - $amount), 2);
            $invoice->status = $invoice->balance_due <= 0 ? 'paid' : 'partial';
            $invoice->save();
            $created++;
        }

        return $created;
    }
}
