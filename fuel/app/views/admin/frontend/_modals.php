    <div class="modal fade" id="modal-frontend-item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ form.id ? 'Editar' : 'Nuevo' }} registro</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-frontend-item')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <?php echo View::forge('admin/frontend/_form_fields'); ?>

                    <?php echo View::forge('admin/frontend/_section_settings'); ?>

                    <?php echo View::forge('admin/frontend/_footer_builder'); ?>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-frontend-item')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveItem">Guardar</button>
                </div>
            </div>
        </div>
    </div>
