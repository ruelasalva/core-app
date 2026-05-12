<div id="app-sat-catalogs">
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ totalRecords }}</h3><p>Registros</p></div>
                <div class="icon"><i class="bi bi-list-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ stats.payment_forms || 0 }}</h3><p>Formas pago</p></div>
                <div class="icon"><i class="bi bi-credit-card"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.tax_regimes || 0 }}</h3><p>Regimenes</p></div>
                <div class="icon"><i class="bi bi-person-vcard"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner"><h3>{{ stats.unit_keys || 0 }}</h3><p>Unidades</p></div>
                <div class="icon"><i class="bi bi-rulers"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">{{ currentDefinition.title || 'Catalogos SAT' }}</h3>
                <div class="d-flex align-items-center">
                    <a class="btn btn-outline-secondary btn-sm mr-2" href="<?php echo Uri::create('admin/sat'); ?>">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <select class="form-control form-control-sm mr-2" v-model="currentCatalog">
                        <option v-for="key in catalogKeys" :key="key" :value="key">{{ definitions[key].title }}</option>
                    </select>
                    <button class="btn btn-primary btn-sm" @click="newItem"><i class="bi bi-plus-lg"></i> Nuevo</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando catalogos SAT...</p>
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
                            <span class="badge" :class="item.active == 1 ? 'badge-success' : 'badge-secondary'">
                                {{ item.active == 1 ? 'Activo' : 'Inactivo' }}
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

    <div class="modal fade" id="modal-sat-catalog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ form.id ? 'Editar' : 'Nuevo' }} registro SAT</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-sat-catalog')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6" v-for="field in currentFields" :key="field.name">
                            <div class="form-group" v-if="field.type !== 'checkbox'">
                                <label>{{ field.label }}</label>
                                <input class="form-control" :type="inputType(field)" v-model="form[field.name]">
                            </div>
                            <div class="custom-control custom-switch mt-4" v-if="field.type === 'checkbox'">
                                <input type="checkbox" class="custom-control-input" :id="'field-' + field.name" v-model="form[field.name]">
                                <label class="custom-control-label" :for="'field-' + field.name">{{ field.label }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-sat-catalog')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveItem">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-sat-catalogs',
        data: {
            loading: true,
            currentCatalog: 'payment_forms',
            definitions: {},
            items: {},
            stats: {},
            form: {}
        },
        computed: {
            catalogKeys() { return Object.keys(this.definitions); },
            currentDefinition() { return this.definitions[this.currentCatalog] || {}; },
            currentFields() { return this.currentDefinition.fields || []; },
            tableFields() { return this.currentFields.filter(field => field.name !== 'active'); },
            currentItems() { return this.items[this.currentCatalog] || []; },
            totalRecords() {
                return Object.values(this.stats).reduce((total, value) => total + parseInt(value || 0), 0);
            }
        },
        mounted() { this.loadData(); },
        methods: {
            loadData() {
                this.loading = true;
                fetch('<?php echo Uri::create('admin/sat/catalogs_data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) { alert(data.error); return; }
                        this.definitions = data.definitions || {};
                        this.items = data.items || {};
                        this.stats = data.stats || {};
                    });
            },
            emptyForm() {
                const data = { id: null, catalog: this.currentCatalog };
                this.currentFields.forEach(field => {
                    data[field.name] = field.type === 'checkbox' ? field.default == 1 : field.default;
                });
                return data;
            },
            newItem() {
                this.form = this.emptyForm();
                this.showModal('modal-sat-catalog');
            },
            editItem(item) {
                const data = this.emptyForm();
                Object.keys(item).forEach(key => { data[key] = item[key]; });
                this.currentFields.forEach(field => {
                    if (field.type === 'checkbox') data[field.name] = data[field.name] == 1;
                });
                data.catalog = this.currentCatalog;
                this.form = data;
                this.showModal('modal-sat-catalog');
            },
            saveItem() {
                this.form.catalog = this.currentCatalog;
                fetch('<?php echo Uri::create('admin/sat/save_catalog'); ?>', {
                    ...window.coreAppFetchOptions(this.form)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) { alert(data.error); return; }
                    this.items = data.items || {};
                    this.stats = data.stats || {};
                    this.hideModal('modal-sat-catalog');
                });
            },
            inputType(field) {
                if (field.type === 'number') return 'number';
                return 'text';
            },
            displayValue(item, field) {
                if (field.type === 'checkbox') return item[field.name] == 1 ? 'Si' : 'No';
                return item[field.name] || '-';
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
