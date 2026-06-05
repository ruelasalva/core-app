    <div class="card card-primary card-outline">
        <div class="card-header">
            <?php echo View::forge('admin/frontend/_toolbar'); ?>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando frontend...</p>
            </div>

            <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

            <?php echo View::forge('admin/frontend/_sections'); ?>

            <?php echo View::forge('admin/frontend/_table'); ?>
        </div>
    </div>
