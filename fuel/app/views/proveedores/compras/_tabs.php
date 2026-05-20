<div class="card-header p-2">
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link" :class="{active: tab === 'orders'}" href="#" @click.prevent="tab = 'orders'">Ordenes</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{active: tab === 'invoices'}" href="#" @click.prevent="tab = 'invoices'">Facturas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{active: tab === 'receipts'}" href="#" @click.prevent="tab = 'receipts'">Contrarecibos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" :class="{active: tab === 'documents'}" href="#" @click.prevent="tab = 'documents'">Documentos</a>
        </li>
    </ul>
</div>
