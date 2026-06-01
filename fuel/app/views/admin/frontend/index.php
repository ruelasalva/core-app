<div id="app-frontend">
    <style>
        .frontend-editor .CodeMirror {
            min-height: 150px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .frontend-editor .ck-editor__editable {
            min-height: 170px;
        }
        .settings-card {
            background: #f8fafc;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 14px;
        }
    </style>
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.pages || 0 }}</h3><p>Páginas</p></div>
                <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ stats.sections || 0 }}</h3><p>Secciones</p></div>
                <div class="icon"><i class="bi bi-layout-text-window"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.banners || 0 }}</h3><p>Banners</p></div>
                <div class="icon"><i class="bi bi-image"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner"><h3>{{ stats.blocks || 0 }}</h3><p>Bloques</p></div>
                <div class="icon"><i class="bi bi-grid"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">{{ currentDefinition.title || 'Frontend' }}</h3>
                <div class="d-flex align-items-center">
                    <a class="btn btn-outline-secondary btn-sm mr-2" href="<?php echo Uri::base(false); ?>" target="_blank"><i class="bi bi-eye"></i> Ver sitio</a>
                    <select class="form-control form-control-sm mr-2" v-model="currentSection">
                        <option v-for="key in sectionKeys" :key="key" :value="key">{{ definitions[key].title }}</option>
                    </select>
                    <button class="btn btn-primary btn-sm" @click="newItem"><i class="bi bi-plus-lg"></i> Nuevo</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando frontend...</p>
            </div>

            <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

            <div v-if="!loading && currentSection === 'sections'">
                <div v-for="group in sectionGroups" :key="'page-sections-' + group.page.id" class="card card-outline card-secondary mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                {{ group.page.title }}
                                <span v-if="group.page.is_home == 1" class="badge badge-info ml-2">Página de inicio</span>
                            </h3>
                            <span class="badge badge-light">{{ group.sections.length }} secciones</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 90px;">Orden</th>
                                    <th>Sección</th>
                                    <th>Tipo</th>
                                    <th>Bloque</th>
                                    <th>Estado</th>
                                    <th class="text-center" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(section, index) in group.sections" :key="'section-row-' + section.id">
                                    <td>{{ section.sort_order }}</td>
                                    <td>
                                        <strong>{{ section.title || section.section_key || 'Sin título' }}</strong>
                                        <div class="text-muted small">{{ section.section_key || '-' }}</div>
                                        <div v-if="blockWarning(section)" class="text-warning small">{{ blockWarning(section) }}</div>
                                    </td>
                                    <td><span class="badge badge-light">{{ sectionTypeLabel(section.section_type) }}</span></td>
                                    <td>{{ section.section_type === 'block' ? blockLabel(section.target_id, section.section_key) : '-' }}</td>
                                    <td>
                                        <span class="badge" :class="isActive(section) ? 'badge-success' : 'badge-secondary'">
                                            {{ isActive(section) ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-outline-secondary mr-1" :disabled="index === 0" title="Mover arriba" @click="moveSection(section, 'up')">
                                            <i class="bi bi-arrow-up"></i>
                                        </button>
                                        <button class="btn btn-xs btn-outline-secondary mr-1" :disabled="index === group.sections.length - 1" title="Mover abajo" @click="moveSection(section, 'down')">
                                            <i class="bi bi-arrow-down"></i>
                                        </button>
                                        <button class="btn btn-xs btn-warning" @click="editItem(section)"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <tr v-if="group.sections.length === 0">
                                    <td colspan="6" class="text-center text-muted">Sin secciones para esta página</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="sectionGroups.length === 0" class="text-center text-muted p-4">Sin páginas para agrupar secciones</div>
            </div>

            <table v-if="!loading && currentSection !== 'sections'" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th v-for="field in tableFields" :key="field.name">{{ field.label }}</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in currentItems" :key="item.id">
                        <td v-for="field in tableFields" :key="field.name">
                            <template v-if="currentSection === 'pages' && field.name === 'published'">
                                <span class="badge" :class="item.published == 1 ? 'badge-success' : 'badge-secondary'">
                                    {{ item.published == 1 ? 'Publicada' : 'Borrador' }}
                                </span>
                            </template>
                            <template v-else-if="currentSection === 'pages' && field.name === 'is_home'">
                                <span v-if="item.is_home == 1" class="badge badge-info">Página de inicio</span>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-else-if="currentSection === 'pages' && field.name === 'template_key'">
                                <span class="badge badge-light">{{ templateLabel(item.template_key) }}</span>
                            </template>
                            <template v-else>{{ displayValue(item, field) }}</template>
                        </td>
                        <td>
                            <span class="badge" :class="isActive(item) ? 'badge-success' : 'badge-secondary'">
                                {{ isActive(item) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a v-if="currentSection === 'pages' && canPreviewPage(item)"
                               class="btn btn-xs btn-info mr-1"
                               :href="previewUrl(item)"
                               target="_blank"
                               rel="noopener"
                               :title="previewTitle(item)">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button v-else-if="currentSection === 'pages'"
                                    class="btn btn-xs btn-secondary mr-1"
                                    disabled
                                    title="Vista previa de borradores pendiente de implementar.">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                            <button class="btn btn-xs btn-warning" @click="editItem(item)"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <tr v-if="currentItems.length === 0">
                        <td :colspan="tableFields.length + 2" class="text-center text-muted">Sin registros</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-frontend-item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ form.id ? 'Editar' : 'Nuevo' }} registro</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-frontend-item')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
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
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-frontend-item')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveItem">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var frontendDataUrl = <?php echo json_encode(Uri::create('admin/frontend/data'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var frontendSaveUrl = <?php echo json_encode(Uri::create('admin/frontend/save'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var frontendMoveSectionUrl = <?php echo json_encode(Uri::create('admin/frontend/move_section'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var frontendUploadUrl = <?php echo json_encode(Uri::create('admin/frontend/upload_image'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var frontendAdminPreviewUrl = <?php echo json_encode(Uri::create('admin/frontend/preview'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var frontendBaseUrl = <?php echo json_encode(Uri::base(false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    new Vue({
        el: '#app-frontend',
        data: {
            loading: true,
            currentSection: 'pages',
            definitions: {},
            items: {},
            options: {},
            stats: {},
            form: {},
            richEditors: {},
            codeEditors: {},
            componentSettings: {},
            errorMessage: ''
        },
        computed: {
            sectionKeys() { return Object.keys(this.definitions); },
            currentDefinition() { return this.definitions[this.currentSection] || {}; },
            currentFields() { return this.currentDefinition.fields || []; },
            tableFields() {
                return this.currentFields.filter(field => !['active', 'content', 'settings_json', 'seo_description', 'media_path', 'image_path', 'logo_path', 'favicon_path', 'custom_css'].includes(field.name)).slice(0, 7);
            },
            currentItems() { return this.items[this.currentSection] || []; },
            sectionGroups() {
                const pages = (this.items.pages || []).slice().sort((a, b) => String(a.title || '').localeCompare(String(b.title || '')));
                const sections = (this.items.sections || []).slice().sort((a, b) => {
                    const order = Number(a.sort_order || 0) - Number(b.sort_order || 0);
                    return order !== 0 ? order : Number(a.id || 0) - Number(b.id || 0);
                });
                return pages.map(page => ({
                    page: page,
                    sections: sections.filter(section => Number(section.page_id || 0) === Number(page.id || 0))
                }));
            }
        },
        mounted() { this.loadData(); },
        methods: {
            loadData() {
                this.loading = true;
                this.errorMessage = '';
                fetch(frontendDataUrl)
                    .then(window.coreAppParseJsonResponse)
                    .then(data => {
                        this.loading = false;
                        if (data.error) { this.errorMessage = data.error; alert(data.error); return; }
                        this.definitions = data.definitions || {};
                        this.items = data.items || {};
                        this.options = data.options || {};
                        this.stats = data.stats || {};
                    })
                    .catch(() => {
                        this.loading = false;
                        this.errorMessage = 'No se pudo cargar el CMS Frontend.';
                        alert(this.errorMessage);
                    });
            },
            emptyForm() {
                const data = { id: null, section: this.currentSection };
                this.currentFields.forEach(field => {
                    data[field.name] = field.type === 'checkbox' ? field.default == 1 : field.default;
                });
                return data;
            },
            newItem() {
                this.destroyEditors();
                this.form = this.emptyForm();
                this.componentSettings = this.parseSettings(this.form.settings_json);
                this.showModal('modal-frontend-item');
                this.$nextTick(this.initEditors);
            },
            editItem(item) {
                this.destroyEditors();
                const data = this.emptyForm();
                Object.keys(item).forEach(key => { data[key] = item[key]; });
                this.currentFields.forEach(field => {
                    if (field.type === 'checkbox') data[field.name] = data[field.name] == 1;
                });
                data.section = this.currentSection;
                this.form = data;
                this.componentSettings = this.parseSettings(this.form.settings_json);
                this.showModal('modal-frontend-item');
                this.$nextTick(this.initEditors);
            },
            saveItem() {
                this.syncEditors();
                if (this.hasVisualSettings()) this.syncComponentSettings();
                this.form.section = this.currentSection;
                fetch(frontendSaveUrl, {
                    ...window.coreAppFetchOptions(this.form)
                })
                .then(window.coreAppParseJsonResponse)
                .then(data => {
                    if (data.error) { alert(data.error); return; }
                    this.items = data.items || {};
                    this.options = data.options || {};
                    this.stats = data.stats || {};
                    this.hideModal('modal-frontend-item');
                })
                .catch(() => {
                    alert('No se pudo guardar el registro.');
                });
            },
            uploadImage(event, field) {
                const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                if (!file) return;

                const data = new FormData();
                data.append('image', file);
                data.append('section', this.currentSection);
                data.append('field', field.name);
                data.append(window.coreAppCsrfKey, fuel_csrf_token());

                fetch(frontendUploadUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json' }, body: data })
                    .then(window.coreAppParseJsonResponse)
                    .then(data => {
                        event.target.value = '';
                        if (data.error) { alert(data.error); return; }
                        this.$set(this.form, field.name, data.path);
                    })
                    .catch(() => {
                        event.target.value = '';
                        alert('No se pudo subir la imagen.');
                    });
            },
            moveSection(section, direction) {
                fetch(frontendMoveSectionUrl, {
                    ...window.coreAppFetchOptions({ id: section.id, direction: direction })
                })
                .then(window.coreAppParseJsonResponse)
                .then(data => {
                    if (data.error) { alert(data.error); return; }
                    this.items = data.items || {};
                    this.options = data.options || {};
                    this.stats = data.stats || {};
                })
                .catch(() => {
                    alert('No se pudo reordenar la sección.');
                });
            },
            inputType(field) {
                if (field.type === 'number' || field.type === 'integer') return 'number';
                if (field.type === 'color') return 'color';
                return 'text';
            },
            getFieldElement(fieldName) {
                const ref = this.$refs['field_' + fieldName];
                return Array.isArray(ref) ? ref[0] : ref;
            },
            initEditors() {
                this.currentFields.forEach(field => {
                    const element = this.getFieldElement(field.name);
                    if (!element) return;

                    if (field.type === 'richtext' && window.ClassicEditor && !this.richEditors[field.name]) {
                        ClassicEditor.create(element, { language: 'es' })
                            .then(editor => {
                                this.richEditors[field.name] = editor;
                                editor.setData(this.form[field.name] || '');
                            })
                            .catch(error => console.error(error));
                    }

                    if ((field.type === 'json' || field.type === 'code_css') && window.CodeMirror && !this.codeEditors[field.name]) {
                        const mode = field.type === 'json' ? { name: 'javascript', json: true } : 'css';
                        const editor = CodeMirror.fromTextArea(element, {
                            lineNumbers: true,
                            mode: mode,
                            lineWrapping: true,
                            viewportMargin: Infinity
                        });
                        editor.setValue(this.form[field.name] || '');
                        editor.on('change', cm => {
                            this.$set(this.form, field.name, cm.getValue());
                            if (field.name === 'settings_json') {
                                this.componentSettings = this.parseSettings(cm.getValue());
                            }
                        });
                        this.codeEditors[field.name] = editor;
                    }
                });
            },
            syncEditors() {
                Object.keys(this.richEditors).forEach(key => {
                    this.$set(this.form, key, this.richEditors[key].getData());
                });
                Object.keys(this.codeEditors).forEach(key => {
                    this.$set(this.form, key, this.codeEditors[key].getValue());
                });
            },
            destroyEditors() {
                Object.keys(this.richEditors).forEach(key => {
                    if (this.richEditors[key] && this.richEditors[key].destroy) {
                        this.richEditors[key].destroy();
                    }
                });
                Object.keys(this.codeEditors).forEach(key => {
                    if (this.codeEditors[key] && this.codeEditors[key].toTextArea) {
                        this.codeEditors[key].toTextArea();
                    }
                });
                this.richEditors = {};
                this.codeEditors = {};
            },
            parseSettings(value) {
                if (!value) return {};
                try {
                    return JSON.parse(value) || {};
                } catch (e) {
                    return {};
                }
            },
            syncComponentSettings() {
                if (!this.hasVisualSettings()) return;
                const json = Object.keys(this.componentSettings || {}).length ? JSON.stringify(this.componentSettings) : '';
                this.$set(this.form, 'settings_json', json);
                if (this.codeEditors.settings_json) {
                    this.codeEditors.settings_json.setValue(json);
                }
            },
            hasVisualSettings() {
                return (this.currentSection === 'sections' && ['download_cards', 'products', 'brands', 'categories', 'cta'].includes(this.form.section_type)) || this.currentSection === 'footer_columns';
            },
            addDownloadItem() {
                if (!this.componentSettings.items) this.$set(this.componentSettings, 'items', []);
                this.componentSettings.items.push({ title: '', url: '' });
                this.syncComponentSettings();
            },
            removeSettingItem(index) {
                if (!this.componentSettings.items) return;
                this.componentSettings.items.splice(index, 1);
                this.syncComponentSettings();
            },
            addFooterItem() {
                if (!this.componentSettings.items) this.$set(this.componentSettings, 'items', []);
                this.componentSettings.items.push({ label: '', url: '', icon: '' });
                this.syncComponentSettings();
            },
            applyFooterPreset(type) {
                this.$set(this.form, 'column_type', type);
                const presets = {
                    contact: [
                        { label: 'Tel: 33 0000 0000', url: 'tel:3300000000', icon: 'bi bi-telephone' },
                        { label: 'contacto@empresa.com', url: 'mailto:contacto@empresa.com', icon: 'bi bi-envelope' },
                        { label: 'Guadalajara, Jalisco', url: '', icon: 'bi bi-geo-alt' }
                    ],
                    links: [
                        { label: 'Productos', url: 'productos', icon: '' },
                        { label: 'Empresa', url: 'empresa', icon: '' },
                        { label: 'Contacto', url: 'contacto', icon: '' }
                    ],
                    social: [
                        { label: 'Facebook', url: 'https://facebook.com/', icon: 'bi bi-facebook' },
                        { label: 'Instagram', url: 'https://instagram.com/', icon: 'bi bi-instagram' },
                        { label: 'WhatsApp', url: 'https://wa.me/520000000000', icon: 'bi bi-whatsapp' }
                    ],
                    legal: [
                        { label: 'Aviso de privacidad', url: 'pagina/aviso-de-privacidad', icon: '' },
                        { label: 'Términos y condiciones', url: 'pagina/terminos-condiciones', icon: '' }
                    ]
                };
                this.$set(this.componentSettings, 'items', presets[type] || []);
                this.syncComponentSettings();
            },
            dynamicOptions(field) { return this.options[field.options] || []; },
            displayValue(item, field) {
                if (field.type === 'checkbox') return item[field.name] == 1 ? 'Sí' : 'No';
                if (field.type === 'select') {
                    const found = this.dynamicOptions(field).find(option => option.value == item[field.name]);
                    return found ? found.label : item[field.name];
                }
                if (field.type === 'select_static') {
                    const found = field.options.find(option => option.value == item[field.name]);
                    return found ? found.label : item[field.name];
                }
                return item[field.name] || '-';
            },
            templateLabel(value) {
                value = value || 'default';
                const labels = {
                    default: 'Predeterminada',
                    home: 'Inicio',
                    content: 'Contenido',
                    catalog: 'Catálogo'
                };
                return labels[value] || value;
            },
            sectionTypeLabel(value) {
                const labels = {
                    hero: 'Hero',
                    content: 'Contenido',
                    content_image: 'Texto con imagen',
                    feature_grid: 'Servicios',
                    products: 'Productos',
                    brands: 'Marcas',
                    categories: 'Categorías',
                    download_cards: 'Descargas',
                    contact_info: 'Contacto',
                    cta: 'CTA',
                    banner: 'Banner',
                    block: 'Bloque reutilizable'
                };
                return labels[value] || value || 'Contenido';
            },
            blockLabel(targetId, sectionKey) {
                const byId = (this.options.blocks || []).find(option => Number(option.value) === Number(targetId || 0));
                if (byId) return byId.label;
                const byCode = (this.items.blocks || []).find(block => String(block.code || '') === String(sectionKey || '') && block.active == 1);
                if (byCode) return byCode.name;
                return 'Sin bloque vinculado';
            },
            blockWarning(section) {
                if (!section || section.section_type !== 'block') return '';
                if (this.blockExists(section)) return '';
                return 'Bloque reutilizable no encontrado o inactivo.';
            },
            blockExists(section) {
                const targetId = Number(section.target_id || 0);
                if (targetId > 0 && (this.options.blocks || []).some(option => Number(option.value) === targetId)) {
                    return true;
                }
                return (this.items.blocks || []).some(block => String(block.code || '') === String(section.section_key || '') && block.active == 1);
            },
            canPreviewPage(item) {
                return !!(item && item.id);
            },
            previewUrl(item) {
                if (!item) return '#';
                if (item.published == 1 && this.isActive(item)) {
                    if (item.is_home == 1) return frontendBaseUrl;
                    return frontendBaseUrl + 'pagina/' + String(item.slug || '').replace(/^\/+/, '');
                }
                return frontendAdminPreviewUrl.replace(/\/+$/, '') + '/' + encodeURIComponent(item.id);
            },
            fieldHelp(field) {
                return field && field.help ? field.help : '';
            },
            previewTitle(item) {
                return item && item.published == 1 && this.isActive(item)
                    ? 'Vista previa pública'
                    : 'Vista previa administrativa';
            },
            isActive(item) { return typeof item.active === 'undefined' || item.active == 1; },
            assetUrl(path) {
                if (!path) return '';
                if (/^https?:\/\//.test(path)) return path;
                return frontendBaseUrl + path.replace(/^\/+/, '');
            },
            showModal(id) {
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(element).show(); return; }
                if (window.jQuery && $.fn.modal) $('#' + id).modal('show');
            },
            hideModal(id) {
                this.syncEditors();
                this.destroyEditors();
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) {
                    const instance = bootstrap.Modal.getInstance(element);
                    if (instance) instance.hide();
                } else if (window.jQuery && $.fn.modal) {
                    $('#' + id).modal('hide');
                }
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            }
        }
    });
});
</script>
