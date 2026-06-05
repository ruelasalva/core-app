    <div class="row">
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number(summary.total_rows) }}</h3>
                    <p>Total filas</p>
                </div>
                <div class="icon"><i class="bi bi-list-check"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number(summary.valid_rows) }}</h3>
                    <p>Filas v&aacute;lidas</p>
                </div>
                <div class="icon"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number(summary.invalid_rows) }}</h3>
                    <p>Filas inv&aacute;lidas</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-octagon"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number(summary.duplicates) }}</h3>
                    <p>Duplicados</p>
                </div>
                <div class="icon"><i class="bi bi-files"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number(summary.warnings) }}</h3>
                    <p>Advertencias</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number(summary.dry_run_runs) }}</h3>
                    <p>Dry-run guardados</p>
                </div>
                <div class="icon"><i class="bi bi-eye"></i></div>
            </div>
        </div>
    </div>
