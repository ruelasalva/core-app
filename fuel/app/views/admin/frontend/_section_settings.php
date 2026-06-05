                    <div v-if="currentSection === 'sections'" class="settings-card mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Configuración del componente</strong>
                            <span class="badge badge-light">{{ form.section_type || 'content' }}</span>
                        </div>

                        <div v-if="form.section_type === 'download_cards'">
                            <div class="row" v-for="(item, index) in componentSettings.items" :key="'download-' + index">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Título</label>
                                        <input class="form-control" v-model="item.title" @input="syncComponentSettings">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>URL</label>
                                        <input class="form-control" v-model="item.url" @input="syncComponentSettings">
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-outline-danger btn-block mb-3" @click="removeSettingItem(index)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <button class="btn btn-outline-primary btn-sm" @click="addDownloadItem"><i class="bi bi-plus"></i> Agregar descarga</button>
                        </div>

                        <div v-else-if="['products', 'brands', 'categories'].includes(form.section_type)">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Origen</label>
                                        <select class="form-control" v-model="componentSettings.source" @change="syncComponentSettings">
                                            <option value="featured">Destacados</option>
                                            <option value="show_in_home">Mostrar en inicio</option>
                                            <option value="latest">Recientes</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Límite</label>
                                        <input type="number" min="1" max="24" class="form-control" v-model.number="componentSettings.limit" @input="syncComponentSettings">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="form.section_type === 'cta'">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Texto botón</label>
                                        <input class="form-control" v-model="componentSettings.button_text" @input="syncComponentSettings">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>URL botón</label>
                                        <input class="form-control" v-model="componentSettings.button_url" @input="syncComponentSettings">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-muted">
                            Este tipo de seccion usa los campos principales. Puedes usar configuracion avanzada solo cuando el componente la necesite.
                        </div>
                    </div>
