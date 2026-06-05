    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.pages || 0 }}</h3><p>Páginas</p></div>
                <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ stats.sections || 0 }}</h3><p>Secciones</p></div>
                <div class="icon"><i class="bi bi-layout-text-window"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.banners || 0 }}</h3><p>Banners</p></div>
                <div class="icon"><i class="bi bi-image"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner"><h3>{{ stats.blocks || 0 }}</h3><p>Bloques</p></div>
                <div class="icon"><i class="bi bi-grid"></i></div>
            </div>
        </div>
    </div>
