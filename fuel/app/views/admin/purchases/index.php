<div id="app-purchases">
    <?php echo View::forge('admin/purchases/_summary'); ?>

    <?php echo View::forge('admin/purchases/_filters'); ?>

    <div class="card card-primary card-outline">
        <?php echo View::forge('admin/purchases/_tabs'); ?>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando compras...</p></div>

            <?php echo View::forge('admin/purchases/_orders_table'); ?>

            <?php echo View::forge('admin/purchases/_invoices_table'); ?>

            <?php echo View::forge('admin/purchases/_receipts_table'); ?>

            <?php echo View::forge('admin/purchases/_documents_table'); ?>
        </div>
    </div>

    <?php echo View::forge('admin/purchases/_order_modal'); ?>

    <?php echo View::forge('admin/purchases/_invoice_modal'); ?>

    <?php echo View::forge('admin/purchases/_receipt_modal'); ?>
</div>

<?php echo View::forge('admin/purchases/_scripts'); ?>
