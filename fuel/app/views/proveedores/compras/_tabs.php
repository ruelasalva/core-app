<div class="portal-panel-header p-2">
    <ul class="nav nav-pills portal-tabs">
        <li class="nav-item">
            <a class="nav-link" :class="{active: tab === 'orders'}" href="#" @click.prevent="tab = 'orders'"><i class="bi bi-cart-check mr-1"></i> 1. Órdenes de compra</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{active: tab === 'invoices'}" href="#" @click.prevent="tab = 'invoices'"><i class="bi bi-file-earmark-text mr-1"></i> 2. Facturas enviadas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{active: tab === 'receipts'}" href="#" @click.prevent="tab = 'receipts'"><i class="bi bi-receipt-cutoff mr-1"></i> 3. Contrarecibos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{active: tab === 'documents'}" href="#" @click.prevent="tab = 'documents'"><i class="bi bi-folder2-open mr-1"></i> 4. Evidencias</a>
        </li>
    </ul>
</div>
