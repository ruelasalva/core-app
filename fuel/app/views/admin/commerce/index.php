<div id="app-commerce">
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.products || 0 }}</h3><p>Productos</p></div>
                <div class="icon"><i class="bi bi-box"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ stats.categories || 0 }}</h3><p>Categorias</p></div>
                <div class="icon"><i class="bi bi-diagram-3"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.brands || 0 }}</h3><p>Marcas</p></div>
                <div class="icon"><i class="bi bi-award"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner"><h3>{{ stats.tags || 0 }}</h3><p>Tags</p></div>
                <div class="icon"><i class="bi bi-tags"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">{{ currentDefinition.title || 'Comercial' }}</h3>
                <div class="d-flex align-items-center">
                    <select class="form-control form-control-sm mr-2" v-model="currentSection">
                        <option v-for="key in sectionKeys" :key="key" :value="key">{{ definitions[key].title }}</option>
                    </select>
                    <a v-if="currentSection === 'products'" class="btn btn-outline-success btn-sm mr-2" href="<?php echo Uri::create('admin/commerce/csv_template'); ?>">
                        <i class="bi bi-download"></i> Plantilla
                    </a>
                    <label v-if="currentSection === 'products'" class="btn btn-outline-primary btn-sm mb-0 mr-2">
                        <i class="bi bi-upload"></i> Importar CSV
                        <input type="file" accept=".csv,.txt,text/csv" class="d-none" @change="importCsv">
                    </label>
                    <button class="btn btn-primary btn-sm" @click="newItem"><i class="bi bi-plus-lg"></i> Nuevo</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div v-if="message" class="alert alert-info py-2">{{ message }}</div>
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando comercial...</p>
            </div>

            <table v-show="!loading" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th v-for="field in tableFields" :key="field.name">{{ field.label }}</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in currentItems" :key="item.id">
                        <td v-for="field in tableFields" :key="field.name">{{ displayValue(item, field) }}</td>
                        <td>
                            <span class="badge" :class="isActive(item) ? 'badge-success' : 'badge-secondary'">
                                {{ isActive(item) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="text-center">
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

    <div class="modal fade" id="modal-commerce-item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ form.id ? 'Editar' : 'Nuevo' }} registro</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-commerce-item')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6" v-for="field in currentFields" :key="field.name">
                            <div class="form-group" v-if="field.type !== 'checkbox'">
                                <label>{{ field.label }}</label>
                                <select v-if="field.type === 'select'" class="form-control" v-model="form[field.name]">
                                    <option value="">Selecciona</option>
                                    <option v-for="option in dynamicOptions(field)" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                                <textarea v-else-if="field.type === 'textarea'" class="form-control" rows="3" v-model="form[field.name]"></textarea>
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
                            </div>
                            <div class="custom-control custom-switch mt-4" v-if="field.type === 'checkbox'">
                                <input type="checkbox" class="custom-control-input" :id="'field-' + field.name" v-model="form[field.name]">
                                <label class="custom-control-label" :for="'field-' + field.name">{{ field.label }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-commerce-item')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveItem">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-commerce',
        data: {
            loading: true,
            currentSection: 'products',
            definitions: {},
            items: {},
            options: {},
            stats: {},
            message: '',
            form: {}
        },
        computed: {
            sectionKeys() { return Object.keys(this.definitions); },
            currentDefinition() { return this.definitions[this.currentSection] || {}; },
            currentFields() { return this.currentDefinition.fields || []; },
            tableFields() {
                return this.currentFields.filter(field => !['active', 'description', 'main_image_path', 'logo_path', 'image_path'].includes(field.name)).slice(0, 8);
            },
            currentItems() { return this.items[this.currentSection] || []; }
        },
        mounted() { this.loadData(); },
        methods: {
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/commerce/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) { alert(data.error); return; }
                        this.definitions = data.definitions || {};
                        this.items = data.items || {};
                        this.options = data.options || {};
                        this.stats = data.stats || {};
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
                this.form = this.emptyForm();
                this.showModal('modal-commerce-item');
            },
            editItem(item) {
                const data = this.emptyForm();
                Object.keys(item).forEach(key => { data[key] = item[key]; });
                this.currentFields.forEach(field => {
                    if (field.type === 'checkbox') data[field.name] = data[field.name] == 1;
                });
                data.section = this.currentSection;
                this.form = data;
                this.showModal('modal-commerce-item');
            },
            saveItem() {
                this.form.section = this.currentSection;
                fetch('<?php echo Uri::create('admin/commerce/save'); ?>', {
                    ...window.coreAppFetchOptions(this.form)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) { alert(data.error); return; }
                    this.items = data.items || {};
                    this.options = data.options || {};
                    this.stats = data.stats || {};
                    this.hideModal('modal-commerce-item');
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

                fetch('<?php echo Uri::create('admin/commerce/upload_image'); ?>', {
                    method: 'POST',
                    body: data
                })
                .then(res => res.json())
                .then(data => {
                    event.target.value = '';
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    this.$set(this.form, field.name, data.path);
                });
            },
            importCsv(event) {
                const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                if (!file) return;
                const form = new FormData();
                form.append('file', file);
                form.append(window.coreAppCsrfKey, fuel_csrf_token());
                fetch('<?php echo Uri::create('admin/commerce/import_csv'); ?>', { method: 'POST', body: form })
                    .then(res => res.json())
                    .then(data => {
                        event.target.value = '';
                        if (data.error) { alert(data.error); return; }
                        this.message = data.message || 'Importacion terminada.';
                        this.items = data.items || {};
                        this.options = data.options || {};
                        this.stats = data.stats || {};
                    });
            },
            inputType(field) {
                if (field.type === 'date') return 'date';
                if (field.type === 'number' || field.type === 'integer') return 'number';
                return 'text';
            },
            dynamicOptions(field) { return this.options[field.options] || []; },
            displayValue(item, field) {
                if (field.type === 'checkbox') return item[field.name] == 1 ? 'Si' : 'No';
                if (field.type === 'select') {
                    const found = this.dynamicOptions(field).find(option => option.value == item[field.name]);
                    return found ? found.label : item[field.name];
                }
                return item[field.name] || '-';
            },
            isActive(item) {
                return typeof item.active === 'undefined' || item.active == 1;
            },
            assetUrl(path) {
                if (!path) return '';
                if (/^https?:\/\//.test(path)) return path;
                return '<?php echo Uri::base(false); ?>' + path.replace(/^\/+/, '');
            },
            showModal(id) {
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(element).show(); return; }
                if (window.jQuery && $.fn.modal) $('#' + id).modal('show');
            },
            hideModal(id) {
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
};
</script>
