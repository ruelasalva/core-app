                    <div class="row frontend-editor">
                        <div class="col-md-6" v-for="field in currentFields" :key="field.name">
                            <div class="form-group" v-if="field.type !== 'checkbox'">
                                <label>{{ field.label }}</label>
                                <select v-if="currentSection === 'sections' && form.section_type === 'block' && field.name === 'target_id'" class="form-control" v-model="form[field.name]">
                                    <option value="0">Selecciona un bloque reutilizable</option>
                                    <option v-for="option in options.blocks || []" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                                <select v-else-if="field.type === 'select'" class="form-control" v-model="form[field.name]">
                                    <option value="">Selecciona</option>
                                    <option v-for="option in dynamicOptions(field)" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                                <select v-else-if="field.type === 'select_static'" class="form-control" v-model="form[field.name]">
                                    <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                                <textarea v-else-if="field.type === 'textarea'" class="form-control" rows="3" v-model="form[field.name]"></textarea>
                                <textarea v-else-if="field.type === 'richtext'" class="form-control" rows="5" v-model="form[field.name]" :ref="'field_' + field.name"></textarea>
                                <textarea v-else-if="field.type === 'json' || field.type === 'code_css'" class="form-control" rows="5" v-model="form[field.name]" :ref="'field_' + field.name"></textarea>
                                <div v-else-if="field.type === 'image'">
                                    <div class="input-group">
                                        <input class="form-control" v-model="form[field.name]">
                                        <div class="input-group-append">
                                            <label class="btn btn-outline-primary mb-0">
                                                <i class="bi bi-upload"></i>
                                                <input type="file" class="d-none" accept="image/jpeg,image/png,image/webp" @change="uploadImage($event, field)">
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="form[field.name]" class="mt-2">
                                        <img :src="assetUrl(form[field.name])" class="img-thumbnail" style="max-height: 90px;">
                                    </div>
                                </div>
                                <input v-else class="form-control" :type="inputType(field)" v-model="form[field.name]">
                                <small v-if="fieldHelp(field)" class="form-text text-muted">{{ fieldHelp(field) }}</small>
                            </div>
                            <div class="custom-control custom-switch mt-4" v-if="field.type === 'checkbox'">
                                <input type="checkbox" class="custom-control-input" :id="'field-' + field.name" v-model="form[field.name]">
                                <label class="custom-control-label" :for="'field-' + field.name">{{ field.label }}</label>
                            </div>
                        </div>
                    </div>
