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

            <div v-show="!loading" class="card bg-light border mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h5 class="mb-1">Sincronizacion oficial SAT</h5>
                            <small class="text-muted">Descarga un archivo CSV, XLSX o Excel-HTML del SAT y actualiza el catalogo seleccionado por codigo.</small>
                        </div>
                        <button class="btn btn-success btn-sm" @click="syncCatalog" :disabled="syncing || !currentSyncSource.source_url">
                            <i class="bi bi-arrow-repeat"></i> {{ syncing ? 'Sincronizando...' : 'Sincronizar' }}
                        </button>
                    </div>
                    <div v-if="syncMessage" class="alert py-2" :class="syncError ? 'alert-warning' : 'alert-info'">{{ syncMessage }}</div>
                    <div class="row">
                        <div class="col-md-5">
                            <label>URL oficial de descarga</label>
                            <input class="form-control form-control-sm" v-model="currentSyncSource.source_url" placeholder="https://.../catCFDI...">
                        </div>
                        <div class="col-md-2">
                            <label>Formato</label>
                            <select class="form-control form-control-sm" v-model="currentSyncSource.source_format">
                                <option value="auto">Auto</option>
                                <option value="csv">CSV</option>
                                <option value="xlsx">XLSX</option>
                                <option value="xls">Excel HTML</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Hoja</label>
                            <input class="form-control form-control-sm" v-model="currentSyncSource.sheet_name">
                        </div>
                        <div class="col-md-1">
                            <label>Codigo</label>
                            <input class="form-control form-control-sm" v-model="currentSyncSource.code_column">
                        </div>
                        <div class="col-md-1">
                            <label>Nombre</label>
                            <input class="form-control form-control-sm" v-model="currentSyncSource.name_column">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-outline-primary btn-sm btn-block" @click="saveSyncSource">Guardar</button>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        Ultimo estado: <strong>{{ currentSyncSource.last_status || 'pending' }}</strong>
                        <span class="mx-2">|</span> Ultima sync: {{ currentSyncSource.last_synced_label || 'Nunca' }}
                        <span v-if="currentSyncSource.last_message">| {{ currentSyncSource.last_message }}</span>
                    </div>
                </div>
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

            <div v-show="!loading" class="mt-4">
                <h5>Bitacora de sincronizacion</h5>
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Catalogo</th><th>Estado</th><th>Nuevos</th><th>Actualizados</th><th>Omitidos</th><th>Mensaje</th><th>Fecha</th></tr></thead>
                    <tbody>
                        <tr v-for="log in syncLogs" :key="log.id">
                            <td>{{ log.catalog_key }}</td>
                            <td><span class="badge" :class="log.status === 'ok' ? 'badge-success' : 'badge-warning'">{{ log.status }}</span></td>
                            <td>{{ log.inserted_count }}</td>
                            <td>{{ log.updated_count }}</td>
                            <td>{{ log.skipped_count }}</td>
                            <td><small>{{ log.message }}</small></td>
                            <td>{{ dateLabel(log.created_at) }}</td>
                        </tr>
                        <tr v-if="syncLogs.length === 0"><td colspan="7" class="text-muted">Sin sincronizaciones registradas.</td></tr>
                    </tbody>
                </table>
            </div>
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
            syncSources: {},
            syncLogs: [],
            syncing: false,
            syncMessage: '',
            syncError: false,
            form: {}
        },
        computed: {
            catalogKeys() { return Object.keys(this.definitions); },
            currentDefinition() { return this.definitions[this.currentCatalog] || {}; },
            currentFields() { return this.currentDefinition.fields || []; },
            tableFields() { return this.currentFields.filter(field => field.name !== 'active'); },
            currentItems() { return this.items[this.currentCatalog] || []; },
            currentSyncSource() {
                if (!this.syncSources[this.currentCatalog]) {
                    this.$set(this.syncSources, this.currentCatalog, {
                        id: 0,
                        catalog_key: this.currentCatalog,
                        source_name: 'SAT CFDI 4.0',
                        source_url: '',
                        source_format: 'auto',
                        sheet_name: '',
                        code_column: 'code',
                        name_column: 'name',
                        active: true,
                        last_status: 'pending',
                        last_synced_label: 'Nunca',
                        last_message: ''
                    });
                }
                return this.syncSources[this.currentCatalog];
            },
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
                        this.syncSources = data.sync_sources || {};
                        this.syncLogs = data.sync_logs || [];
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
            saveSyncSource() {
                const payload = Object.assign({}, this.currentSyncSource, { catalog_key: this.currentCatalog });
                fetch('<?php echo Uri::create('admin/sat/save_catalog_sync_source'); ?>', {
                    ...window.coreAppFetchOptions(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.syncError = true; this.syncMessage = data.error; return; }
                    this.syncSources = data.sync_sources || this.syncSources;
                    this.syncError = false;
                    this.syncMessage = 'Fuente guardada.';
                });
            },
            syncCatalog() {
                this.syncing = true;
                this.syncError = false;
                this.syncMessage = 'Descargando y procesando catalogo...';
                fetch('<?php echo Uri::create('admin/sat/sync_catalog'); ?>', {
                    ...window.coreAppFetchOptions({ catalog_key: this.currentCatalog })
                })
                .then(res => res.json())
                .then(data => {
                    this.syncing = false;
                    if (data.error) { this.syncError = true; this.syncMessage = data.error; return; }
                    this.items = data.items || this.items;
                    this.stats = data.stats || this.stats;
                    this.syncSources = data.sync_sources || this.syncSources;
                    this.syncLogs = data.sync_logs || this.syncLogs;
                    this.syncMessage = data.message || 'Catalogo sincronizado.';
                })
                .catch(error => {
                    this.syncing = false;
                    this.syncError = true;
                    this.syncMessage = error.message || 'No se pudo sincronizar.';
                });
            },
            dateLabel(value) {
                const timestamp = parseInt(value || 0);
                if (!timestamp) return '-';
                return new Date(timestamp * 1000).toLocaleString();
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
