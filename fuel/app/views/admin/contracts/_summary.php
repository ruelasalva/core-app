    <div class="row">
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ stats.active || 0 }}</h3><p>Activos</p></div>
                <div class="icon"><i class="bi bi-file-earmark-check"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.expiring_90 || 0 }}</h3><p>Por vencer 90 dias</p></div>
                <div class="icon"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.expiring_60 || 0 }}</h3><p>Por vencer 60 dias</p></div>
                <div class="icon"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-orange">
                <div class="inner"><h3>{{ stats.expiring_30 || 0 }}</h3><p>Por vencer 30 dias</p></div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-danger">
                <div class="inner"><h3>{{ stats.expired || 0 }}</h3><p>Vencidos</p></div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-secondary">
                <div class="inner"><h3>{{ stats.no_end_date || 0 }}</h3><p>Sin vencimiento</p></div>
                <div class="icon"><i class="bi bi-infinity"></i></div>
            </div>
        </div>
    </div>
