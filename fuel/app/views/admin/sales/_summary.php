    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ stats.quotes || 0 }}</h3>
                    <p>Cotizaciones</p>
                </div>
                <div class="icon"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ stats.prequote || 0 }}</h3>
                    <p>Precotizaciones</p>
                </div>
                <div class="icon"><i class="bi bi-bag"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ stats.requested || 0 }}</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ stats.orders || 0 }}</h3>
                    <p>Pedidos</p>
                </div>
                <div class="icon"><i class="bi bi-clipboard-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ stats.deliveries || 0 }}</h3>
                    <p>Entregas</p>
                </div>
                <div class="icon"><i class="bi bi-truck"></i></div>
            </div>
        </div>
    </div>
