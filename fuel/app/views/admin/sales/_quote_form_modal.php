    <?php if ($capture_page): ?>
    <div class="quote-page-capture">
        <div class="modal-content">
    <?php else: ?>
    <div class="modal fade" id="modal-new-quote" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog quote-modal-fullscreen">
            <div class="modal-content">
    <?php endif; ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ quoteForm.quote_mode === 'prequote' ? 'Nueva precotizacion' : 'Nueva cotizacion' }}</h5>
                    <?php if ($capture_page): ?>
                    <a class="close text-white" href="<?php echo Uri::create('admin/sales'); ?>">
                        <span>&times;</span>
                    </a>
                    <?php else: ?>
                    <button type="button" class="close text-white" @click="hideModal('modal-new-quote')">
                        <span>&times;</span>
                    </button>
                    <?php endif; ?>
                </div>
                    <?php echo View::forge('admin/sales/_quote_header_fields'); ?>

                    <?php echo View::forge('admin/sales/_product_capture'); ?>

                    <?php echo View::forge('admin/sales/_quote_items_table'); ?>

                    <?php echo View::forge('admin/sales/_catalog_capture'); ?>

                    <div class="form-group">
                        <label>Notas para el cliente</label>
                        <textarea class="form-control" rows="2" v-model="quoteForm.customer_notes"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Notas internas</label>
                        <textarea class="form-control" rows="2" v-model="quoteForm.internal_notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if ($capture_page): ?>
                    <a class="btn btn-secondary" href="<?php echo Uri::create('admin/sales'); ?>">Regresar</a>
                    <?php else: ?>
                    <button class="btn btn-secondary" @click="hideModal('modal-new-quote')">Cerrar</button>
                    <?php endif; ?>
                    <button class="btn btn-primary" @click="saveQuote">{{ quoteForm.quote_mode === 'prequote' ? 'Guardar precotizacion' : 'Guardar cotizacion' }}</button>
                </div>
    <?php if ($capture_page): ?>
            </div>
        </div>
    <?php else: ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

