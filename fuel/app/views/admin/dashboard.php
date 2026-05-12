<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Bienvenido</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo Auth::get_screen_name(); ?></h5>
                <p class="card-text">Estás logueado como Administrador del sistema.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">Estado del Sistema</div>
            <div class="card-body">
                <p>Módulos base cargados. Utiliza el menú lateral para navegar.</p>
                <?php \Log::info("Dashboard renderizado visualmente para el usuario."); ?>
            </div>
        </div>
    </div>
</div>