<?php

/**
 * SERVICE CORE PAYMENTS MANAGER
 *
 * Servicio base para centralizar operaciones de pagos en fases posteriores.
 * En esta fase no se conecta a controladores ni cambia comportamiento actual.
 *
 * @package  app
 */
class Service_Core_Payments_Manager
{
    /**
     * NEXT PAYMENT FOLIO
     *
     * Reutiliza el mismo criterio actual del controlador de pagos.
     *
     * @access  public
     * @return  String
     */
    public function next_payment_folio()
    {
        return 'PAY-'.date('Ymd').'-'.str_pad((string) ((int) \DB::count_records('core_payments') + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * RECALCULATE BILLING INVOICE BALANCE
     *
     * Recalcula el saldo de una factura de venta usando asignaciones activas.
     * No crea pagos ni asignaciones.
     *
     * @access  public
     * @param   Int  $invoice_id
     * @return  Model_Core_Billing_Invoice|null
     */
    public function recalculate_billing_invoice_balance($invoice_id)
    {
        $invoice = Model_Core_Billing_Invoice::find((int) $invoice_id);
        if (!$invoice || $invoice->invoice_type !== 'sale' || (int) $invoice->active !== 1) {
            \Log::warning('No se pudo recalcular saldo de factura de venta: factura no encontrada o inactiva. ID: '.(int) $invoice_id);
            return null;
        }

        $allocated = $this->allocated_amount('billing_invoice', (int) $invoice->id);
        $total = (float) $invoice->total;

        $invoice->balance_due = round(max(0, $total - $allocated), 2);
        $invoice->status = $invoice->balance_due <= 0 ? 'paid' : ($allocated > 0 ? 'partial' : (string) $invoice->status);
        $invoice->save();

        \Log::info('Saldo de factura de venta recalculado. Factura ID: '.(int) $invoice->id);

        return $invoice;
    }

    /**
     * RECALCULATE PURCHASE INVOICE BALANCE
     *
     * Recalcula el saldo de una factura de compra usando asignaciones activas.
     * No crea pagos ni asignaciones.
     *
     * @access  public
     * @param   Int  $invoice_id
     * @return  Model_Core_Purchase_Invoice|null
     */
    public function recalculate_purchase_invoice_balance($invoice_id)
    {
        $invoice = Model_Core_Purchase_Invoice::find((int) $invoice_id);
        if (!$invoice || (int) $invoice->active !== 1) {
            \Log::warning('No se pudo recalcular saldo de factura de compra: factura no encontrada o inactiva. ID: '.(int) $invoice_id);
            return null;
        }

        $allocated = $this->allocated_amount('purchase_invoice', (int) $invoice->id);
        $total = (float) $invoice->total;

        $invoice->balance_due = round(max(0, $total - $allocated), 2);
        $invoice->status = $invoice->balance_due <= 0 ? 'paid' : ($allocated > 0 ? 'partial' : (string) $invoice->status);
        $invoice->save();

        \Log::info('Saldo de factura de compra recalculado. Factura ID: '.(int) $invoice->id);

        return $invoice;
    }

    /**
     * ALLOCATED AMOUNT
     *
     * Suma asignaciones activas para una entidad.
     *
     * @access  protected
     * @param   String  $entity_type
     * @param   Int     $entity_id
     * @return  Float
     */
    protected function allocated_amount($entity_type, $entity_id)
    {
        $row = \DB::select([\DB::expr('COALESCE(SUM(amount), 0)'), 'allocated'])
            ->from('core_payment_allocations')
            ->where('entity_type', '=', (string) $entity_type)
            ->where('entity_id', '=', (int) $entity_id)
            ->where('active', '=', 1)
            ->execute()
            ->current();

        return $row ? (float) $row['allocated'] : 0.0;
    }
}
