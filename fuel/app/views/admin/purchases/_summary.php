    <div class="row">
        <div class="col-lg-3"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.orders || 0 }}</h3><p>Ordenes</p></div><div class="icon"><i class="bi bi-cart-check"></i></div></div></div>
        <div class="col-lg-3"><div class="small-box bg-warning"><div class="inner"><h3>{{ stats.pending_authorizations || 0 }}</h3><p>Por autorizar</p></div><div class="icon"><i class="bi bi-shield-check"></i></div></div></div>
        <div class="col-lg-3"><div class="small-box bg-primary"><div class="inner"><h3>{{ stats.pending_invoices || 0 }}</h3><p>Facturas pendientes</p></div><div class="icon"><i class="bi bi-file-earmark-text"></i></div></div></div>
        <div class="col-lg-3"><div class="small-box bg-success"><div class="inner"><h3>{{ stats.receipts || 0 }}</h3><p>Contrarecibos</p></div><div class="icon"><i class="bi bi-receipt-cutoff"></i></div></div></div>
    </div>
