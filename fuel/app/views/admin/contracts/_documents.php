                        <div v-show="tab === 'documents'">
                            <div class="alert alert-info py-2">
                                Adjunta documentos de este contrato desde esta pestaña. El archivo queda en Documentos y aqui se guarda el vinculo al contrato.
                            </div>
                            <div v-if="permissions.upload_document" class="mb-3">
                                <button class="btn btn-primary btn-sm mr-2 mb-1" @click="openUpload('main_contract')">
                                    <i class="bi bi-file-earmark-pdf"></i> Subir contrato PDF
                                </button>
                                <button class="btn btn-outline-primary btn-sm mr-2 mb-1" @click="openUpload('annex')">
                                    <i class="bi bi-paperclip"></i> Subir anexo
                                </button>
                                <button class="btn btn-outline-success btn-sm mr-2 mb-1" @click="openUpload('signed_document')">
                                    <i class="bi bi-file-earmark-check"></i> Subir documento firmado
                                </button>
                                <button class="btn btn-outline-secondary btn-sm mb-1" @click="showLinkDocument = !showLinkDocument; showUpload = false">
                                    <i class="bi bi-link-45deg"></i> Vincular documento existente
                                </button>
                                <div class="text-muted small mt-2">
                                    Tipos permitidos: PDF, JPG, PNG, DOC y DOCX. Usa el tipo de relacion correcto: Contrato principal, Anexo, Evidencia o Documento firmado.
                                </div>
                            </div>

                            <div v-if="permissions.upload_document && showUpload" class="border rounded p-2 mb-3">
                                <h6>Subir documento</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Archivo</label>
                                        <input type="file" class="form-control-file" @change="onFileChange">
                                        <small class="form-text text-muted">Acepta PDF, JPG, PNG, DOC y DOCX.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Tipo</label>
                                        <select class="form-control form-control-sm" v-model="uploadForm.relation_type">
                                            <option v-for="option in documentStructure.relation_types" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label>Titulo</label>
                                        <input class="form-control form-control-sm" v-model="uploadForm.title">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <label>Visibilidad</label>
                                        <select class="form-control form-control-sm" v-model="uploadForm.visibility">
                                            <option v-for="option in options.visibilities" :value="option.value">{{ option.label }}</option>
                                        </select>
                                        <small class="form-text text-muted">{{ visibilityHelp(uploadForm.visibility) }}</small>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <label>Notas</label>
                                        <input class="form-control form-control-sm" v-model="uploadForm.notes">
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-sm mt-2" @click="uploadDocument">Subir documento</button>
                            </div>

                            <div v-if="permissions.upload_document && showLinkDocument" class="border rounded p-2 mb-3">
                                <h6>Vincular documento existente</h6>
                                <div class="row">
                                    <div class="col-md-7">
                                        <select class="form-control form-control-sm" v-model="linkDocumentForm.document_id">
                                            <option value="0">Selecciona documento</option>
                                            <option v-for="document in availableDocuments" :value="document.value">{{ document.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-control form-control-sm" v-model="linkDocumentForm.relation_type">
                                            <option v-for="option in documentStructure.relation_types" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-outline-primary btn-sm btn-block" @click="linkDocument">Vincular</button>
                                    </div>
                                </div>
                            </div>

                            <table class="table table-sm table-bordered">
                                <thead><tr><th>Tipo</th><th>Documento</th><th>Archivo</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <tr v-for="document in selectedDocuments" :key="document.link_id">
                                        <td>{{ document.relation_label }}</td>
                                        <td>{{ document.title }}<div class="text-muted small">{{ document.created_at }}</div></td>
                                        <td>{{ document.original_name || '-' }}</td>
                                        <td>
                                            <a class="btn btn-outline-secondary btn-xs" :href="document.download_url">Descargar</a>
                                            <button v-if="permissions.upload_document" class="btn btn-outline-danger btn-xs" @click="removeDocumentLink(document)">Quitar</button>
                                        </td>
                                    </tr>
                                    <tr v-if="selectedDocuments.length === 0"><td colspan="4" class="text-muted text-center">Sin documentos.</td></tr>
                                </tbody>
                            </table>
                        </div>
