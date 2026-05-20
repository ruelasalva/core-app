<div id="app-portal-purchases">
    <?php echo View::forge('proveedores/compras/_summary'); ?>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <?php echo View::forge('proveedores/compras/_tabs'); ?>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Cargando compras...</p>
            </div>

            <?php echo View::forge('proveedores/compras/_orders'); ?>
            <?php echo View::forge('proveedores/compras/_invoices'); ?>
            <?php echo View::forge('proveedores/compras/_receipts'); ?>
            <?php echo View::forge('proveedores/compras/_documents'); ?>
        </div>
    </div>

    <?php echo View::forge('proveedores/compras/_modals'); ?>
</div>

<?php echo View::forge('proveedores/compras/_scripts', ['portal_code' => $portal_code]); ?>
