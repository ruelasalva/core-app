                    <div v-if="currentSection === 'footer_columns'" class="settings-card mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>Constructor visual de footer</strong>
                                <div class="text-muted small">Usa items para links, contacto, redes, legales o distintivos sin editar JSON manual.</div>
                            </div>
                            <span class="badge badge-light">{{ form.column_type || 'text' }}</span>
                        </div>
                        <div class="btn-group btn-group-sm mb-3">
                            <button class="btn btn-outline-secondary" @click="applyFooterPreset('contact')">Contacto</button>
                            <button class="btn btn-outline-secondary" @click="applyFooterPreset('links')">Links</button>
                            <button class="btn btn-outline-secondary" @click="applyFooterPreset('social')">Redes</button>
                            <button class="btn btn-outline-secondary" @click="applyFooterPreset('legal')">Legales</button>
                        </div>
                        <div class="row" v-for="(item, index) in componentSettings.items" :key="'footer-' + index">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Etiqueta</label>
                                    <input class="form-control" v-model="item.label" @input="syncComponentSettings">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>URL o dato</label>
                                    <input class="form-control" v-model="item.url" @input="syncComponentSettings">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Icono</label>
                                    <input class="form-control" v-model="item.icon" placeholder="bi bi-telephone" @input="syncComponentSettings">
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button class="btn btn-outline-danger btn-block mb-3" @click="removeSettingItem(index)"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" @click="addFooterItem"><i class="bi bi-plus"></i> Agregar elemento</button>
                    </div>
