    <div class="modal fade" id="modal-contract" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ form.id ? 'Editar contrato' : 'Nuevo contrato' }}</h5>
                    <button type="button" class="close text-white" @click="hideModal()"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label>Tipo</label>
                            <select class="form-control" v-model="form.contract_type">
                                <option v-for="option in options.contract_types" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Tercero</label>
                            <select class="form-control" v-model="form.party_id">
                                <option value="0">Sin tercero</option>
                                <option v-for="option in options.parties" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Portal</label>
                            <select class="form-control" v-model="form.portal_code">
                                <option v-for="option in options.portal_codes" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-8 mt-3">
                            <label>Titulo</label>
                            <input class="form-control" v-model="form.title" maxlength="180">
                        </div>
                        <div class="col-md-4 mt-3">
                            <label>Responsable</label>
                            <select class="form-control" v-model="form.responsible_user_id">
                                <option value="0">Sin responsable</option>
                                <option v-for="option in options.users" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label>Descripcion</label>
                            <textarea id="contract-description-editor" class="form-control" rows="5" v-model="form.description"></textarea>
                            <small class="form-text text-muted">Puedes usar parrafos, listas y texto con formato. No se permiten scripts, iframes ni eventos.</small>
                            <small v-if="richEditorFallbackMessage" class="form-text text-warning">{{ richEditorFallbackMessage }}</small>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label>Notas internas</label>
                            <textarea id="contract-notes-editor" class="form-control" rows="5" v-model="form.notes"></textarea>
                            <small class="form-text text-muted">Notas visibles solo para administracion autorizada.</small>
                            <small v-if="richEditorFallbackMessage" class="form-text text-warning">{{ richEditorFallbackMessage }}</small>
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Inicio</label>
                            <input type="date" class="form-control" v-model="form.start_date">
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Fin</label>
                            <input type="date" class="form-control" v-model="form.end_date">
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Renovacion</label>
                            <select class="form-control" v-model="form.renewal_type">
                                <option v-for="option in options.renewal_types" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Visibilidad</label>
                            <select class="form-control" v-model="form.visibility">
                                <option v-for="option in options.visibilities" :value="option.value">{{ option.label }}</option>
                            </select>
                            <small class="form-text text-muted">{{ visibilityHelp(form.visibility) }}</small>
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Valor</label>
                            <input type="number" min="0" step="0.01" class="form-control" v-model.number="form.contract_value">
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Moneda</label>
                            <select class="form-control" v-model="form.currency_code">
                                <option v-for="option in options.currencies" :value="option.value">{{ option.value }} - {{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Valor renovacion</label>
                            <input type="number" min="0" step="0.01" class="form-control" v-model.number="form.renewal_value">
                        </div>
                        <div class="col-md-3 mt-3">
                            <label>Moneda renovacion</label>
                            <select class="form-control" v-model="form.renewal_currency_code">
                                <option v-for="option in options.currencies" :value="option.value">{{ option.value }} - {{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mt-3">
                            <label>Tipo de facturacion</label>
                            <select class="form-control" v-model="form.billing_type">
                                <option v-for="option in options.billing_types" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mt-3">
                            <label>Horas respuesta</label>
                            <input type="number" min="0" step="0.25" class="form-control" v-model.number="form.response_hours">
                        </div>
                        <div class="col-md-4 mt-3">
                            <label>Horas resolucion</label>
                            <input type="number" min="0" step="0.25" class="form-control" v-model.number="form.resolution_hours">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal()">Cancelar</button>
                    <button class="btn btn-primary" @click="save()">Guardar</button>
                </div>
            </div>
        </div>
    </div>
