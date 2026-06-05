    <div v-if="showUploadModal" class="modal d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,.45);">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Importar CSV de proveedor</h5>
                    <button type="button" class="close" aria-label="Cerrar" @click="closeUploadModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="uploadError" class="alert alert-danger">{{ uploadError }}</div>
                    <div class="alert alert-info">
                        Primero se cargan los productos a staging. No se crean productos reales hasta que se aprueben.
                    </div>
                    <div class="form-group">
                        <label>Proveedor comercial</label>
                        <select class="form-control" v-model="uploadForm.party_id" @change="selectSupplier">
                            <option value="0">Seleccionar proveedor comercial</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplierLabel(supplier) }}</option>
                        </select>
                        <small class="form-text text-muted">Selecciona un proveedor registrado en terceros comerciales. Si no existe, usa el c&oacute;digo avanzado temporalmente.</small>
                    </div>
                    <div class="form-group">
                        <label>Fuente de importaci&oacute;n</label>
                        <select class="form-control" v-model="uploadForm.source_code">
                            <option v-for="source in sources" :key="source.code" :value="source.code" :disabled="source.pending">{{ sourceLabel(source) }}</option>
                        </select>
                        <small class="form-text text-muted">Por ahora solo CSV / Excel manual est&aacute; disponible. Las APIs y scrapers quedan preparados para fases posteriores.</small>
                    </div>
                    <div class="form-group">
                        <label>C&oacute;digo avanzado de proveedor</label>
                        <input type="text" class="form-control" v-model="uploadForm.provider" placeholder="cva, ct, syscom, tonersparaimpresoras">
                        <small class="form-text text-muted">Usar solo si no seleccionas proveedor comercial. Ejemplo: cva, ct, syscom, tonersparaimpresoras, proveedor_local</small>
                        <small class="form-text text-muted">El c&oacute;digo se normaliza a min&uacute;sculas y guiones bajos. No crea proveedor nuevo.</small>
                    </div>
                    <div class="form-group">
                        <label>Archivo CSV</label>
                        <input ref="csvFile" type="file" class="form-control-file" accept=".csv,.txt,text/csv" @change="handleFileChange">
                        <small class="form-text text-muted">Tama&ntilde;o m&aacute;ximo 5 MB. No se ejecuta contenido del archivo.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label>Modo</label>
                        <div class="custom-control custom-radio">
                            <input id="supplier-import-mode-dry" type="radio" class="custom-control-input" value="dry_run" v-model="uploadForm.mode">
                            <label class="custom-control-label" for="supplier-import-mode-dry">Validar solamente</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input id="supplier-import-mode-staging" type="radio" class="custom-control-input" value="staging" v-model="uploadForm.mode">
                            <label class="custom-control-label" for="supplier-import-mode-staging">Importar a staging</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" :disabled="uploading" @click="closeUploadModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" :disabled="uploading" @click="submitUpload">
                        <span v-if="uploading" class="spinner-border spinner-border-sm mr-1"></span>
                        Procesar CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
