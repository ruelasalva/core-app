<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1>Biblioteca de Software</h1>
                <p class="text-muted mb-0">Administra instaladores, herramientas, demos, licencias y enlaces de soporte para clientes, proveedores, revendedores, socios y otros portales.</p>
            </div>
            <div class="col-sm-4 text-sm-right mt-3 mt-sm-0">
                <span class="badge badge-info">Carga grande</span>
                <span class="badge badge-secondary">Descarga controlada</span>
            </div>
        </div>
    </div>
</section>

<section class="content" id="software-library-app" v-cloak>
    <div class="container-fluid">
        <div class="alert alert-danger" v-if="error">{{ error }}</div>
        <div class="alert alert-success" v-if="message">{{ message }}</div>

        <div class="row">
            <div class="col-md-3 col-sm-6" v-for="card in statCards" :key="card.key">
                <div class="small-box bg-light">
                    <div class="inner">
                        <h3>{{ card.value }}</h3>
                        <p>{{ card.label }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header p-2">
                <ul class="nav nav-pills flex-wrap">
                    <li class="nav-item" v-for="tab in tabs" :key="tab.key">
                        <button type="button" class="nav-link" :class="{active: activeTab === tab.key}" @click="activeTab = tab.key">
                            {{ tab.label }}
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div v-if="loading" class="text-center text-muted py-5">
                    <i class="fas fa-spinner fa-spin"></i> Cargando biblioteca...
                </div>

                <div v-else>
                    <div v-show="activeTab === 'software'">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Software</th>
                                                <th>Categoría</th>
                                                <th>Tipo</th>
                                                <th>Visibilidad</th>
                                                <th>Estado</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in items" :key="item.id">
                                                <td>{{ item.code }}</td>
                                                <td>
                                                    <strong>{{ item.name }}</strong>
                                                    <div class="text-muted small">{{ item.vendor || 'Sin fabricante' }}</div>
                                                </td>
                                                <td>{{ item.category_name || 'Sin categoría' }}</td>
                                                <td>{{ label(item.software_type) }}</td>
                                                <td><span class="badge badge-light">{{ label(item.visibility) }}</span></td>
                                                <td><span class="badge" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span></td>
                                                <td class="text-right"><button type="button" class="btn btn-xs btn-outline-primary" @click="editItem(item)">Editar</button></td>
                                            </tr>
                                            <tr v-if="items.length === 0">
                                                <td colspan="7" class="text-center text-muted py-4">Aún no hay software registrado.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-header"><strong>{{ forms.item.id ? 'Editar software' : 'Nuevo software' }}</strong></div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Nombre</label>
                                            <input type="text" class="form-control" v-model="forms.item.name">
                                        </div>
                                        <div class="form-group">
                                            <label>Código</label>
                                            <input type="text" class="form-control" v-model="forms.item.code" placeholder="Opcional">
                                        </div>
                                        <div class="form-group">
                                            <label>Categoría</label>
                                            <select class="form-control" v-model="forms.item.category_id">
                                                <option value="0">Sin categoría</option>
                                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                                            </select>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Tipo</label>
                                                <select class="form-control" v-model="forms.item.software_type">
                                                    <option value="installer">Instalable</option>
                                                    <option value="tool">Herramienta</option>
                                                    <option value="license">Licencia</option>
                                                    <option value="demo">Demo</option>
                                                    <option value="link">Enlace</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Plataforma</label>
                                                <select class="form-control" v-model="forms.item.platform">
                                                    <option value="windows">Windows</option>
                                                    <option value="macos">macOS</option>
                                                    <option value="linux">Linux</option>
                                                    <option value="web">Web</option>
                                                    <option value="mobile">Móvil</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Fabricante / origen</label>
                                            <input type="text" class="form-control" v-model="forms.item.vendor">
                                        </div>
                                        <div class="form-group">
                                            <label>Descripción</label>
                                            <textarea class="form-control" rows="3" v-model="forms.item.description"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Notas de soporte</label>
                                            <textarea class="form-control" rows="2" v-model="forms.item.support_notes"></textarea>
                                        </div>
                                        <button type="button" class="btn btn-primary" @click="saveItem" :disabled="saving">Guardar</button>
                                        <button type="button" class="btn btn-link" @click="resetItem">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'versions'">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Software</th>
                                                <th>Versión</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="version in versions" :key="version.id">
                                                <td>{{ version.software_name }}</td>
                                                <td><strong>{{ version.version }}</strong> <span v-if="parseInt(version.is_latest, 10) === 1" class="badge badge-success">Actual</span></td>
                                                <td>{{ version.release_date || 'Sin fecha' }}</td>
                                                <td><span class="badge" :class="statusClass(version.status)">{{ statusLabel(version.status) }}</span></td>
                                                <td class="text-right"><button type="button" class="btn btn-xs btn-outline-primary" @click="editVersion(version)">Editar</button></td>
                                            </tr>
                                            <tr v-if="versions.length === 0">
                                                <td colspan="5" class="text-center text-muted py-4">Aún no hay versiones registradas.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-header"><strong>{{ forms.version.id ? 'Editar versión' : 'Nueva versión' }}</strong></div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Software</label>
                                            <select class="form-control" v-model="forms.version.software_id">
                                                <option value="0">Selecciona</option>
                                                <option v-for="item in items" :key="item.id" :value="item.id">{{ item.name }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Versión</label>
                                            <input type="text" class="form-control" v-model="forms.version.version">
                                        </div>
                                        <div class="form-group">
                                            <label>Fecha de liberación</label>
                                            <input type="date" class="form-control" v-model="forms.version.release_date">
                                        </div>
                                        <div class="form-group">
                                            <label>Cambios</label>
                                            <textarea class="form-control" rows="3" v-model="forms.version.changelog"></textarea>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input type="checkbox" class="form-check-input" id="swlibLatest" v-model="forms.version.is_latest" true-value="1" false-value="0">
                                            <label class="form-check-label" for="swlibLatest">Marcar como versión actual</label>
                                        </div>
                                        <button type="button" class="btn btn-primary" @click="saveVersion" :disabled="saving">Guardar</button>
                                        <button type="button" class="btn btn-link" @click="resetVersion">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'assets'">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Recurso</th>
                                                <th>Software</th>
                                                <th>Tipo</th>
                                                <th>Tamaño</th>
                                                <th>Estado</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="asset in assets" :key="asset.id">
                                                <td>
                                                    <strong>{{ asset.title }}</strong>
                                                    <div class="text-muted small">{{ asset.filename || asset.external_url || 'Sin archivo' }}</div>
                                                </td>
                                                <td>{{ asset.software_name }}</td>
                                                <td>{{ asset.asset_type === 'file' ? 'Archivo' : 'Enlace' }}</td>
                                                <td>{{ formatSize(asset.size_bytes) }}</td>
                                                <td><span class="badge" :class="statusClass(asset.status)">{{ statusLabel(asset.status) }}</span></td>
                                                <td class="text-right">
                                                    <a class="btn btn-xs btn-outline-secondary" :href="asset.download_url" target="_blank" rel="noopener">Abrir</a>
                                                </td>
                                            </tr>
                                            <tr v-if="assets.length === 0">
                                                <td colspan="6" class="text-center text-muted py-4">Aún no hay archivos o enlaces publicados.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-header"><strong>Agregar enlace externo</strong></div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Software</label>
                                            <select class="form-control" v-model="forms.asset.software_id">
                                                <option value="0">Selecciona</option>
                                                <option v-for="item in items" :key="item.id" :value="item.id">{{ item.name }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Título</label>
                                            <input type="text" class="form-control" v-model="forms.asset.title">
                                        </div>
                                        <div class="form-group">
                                            <label>URL externa</label>
                                            <input type="url" class="form-control" v-model="forms.asset.external_url">
                                        </div>
                                        <button type="button" class="btn btn-primary" @click="saveAsset" :disabled="saving">Guardar enlace</button>
                                        <button type="button" class="btn btn-link" @click="resetAsset">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'upload'">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="card bg-light">
                                    <div class="card-header"><strong>Carga grande por partes</strong></div>
                                    <div class="card-body">
                                        <p class="text-muted">Los archivos se guardan fuera de la carpeta pública y se entregan mediante descarga controlada.</p>
                                        <div class="form-group">
                                            <label>Software</label>
                                            <select class="form-control" v-model="forms.upload.software_id">
                                                <option value="0">Selecciona</option>
                                                <option v-for="item in items" :key="item.id" :value="item.id">{{ item.name }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Versión</label>
                                            <select class="form-control" v-model="forms.upload.version_id">
                                                <option value="0">Sin versión</option>
                                                <option v-for="version in versionsForUpload" :key="version.id" :value="version.id">{{ version.version }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Título visible</label>
                                            <input type="text" class="form-control" v-model="forms.upload.title">
                                        </div>
                                        <div class="form-group">
                                            <label>Archivo</label>
                                            <input type="file" class="form-control-file" @change="selectUploadFile">
                                        </div>
                                        <div class="progress mb-3" v-if="upload.progress > 0">
                                            <div class="progress-bar" role="progressbar" :style="{width: upload.progress + '%'}">{{ upload.progress }}%</div>
                                        </div>
                                        <button type="button" class="btn btn-primary" @click="uploadFile" :disabled="saving || !upload.file">Subir archivo</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="callout callout-info">
                                    <h5>Alcance transversal</h5>
                                    <p class="mb-0">Después de subir el recurso, define reglas para clientes, proveedores, revendedores, socios, empleados o una entidad específica.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'access'">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Destino</th>
                                                <th>Alcance</th>
                                                <th>Portal</th>
                                                <th>Entidad</th>
                                                <th>Vigencia</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="rule in accessRules" :key="rule.id">
                                                <td>{{ rule.asset_title || rule.software_name || 'General' }}</td>
                                                <td>{{ label(rule.scope_type) }}</td>
                                                <td>{{ rule.portal_code || 'No aplica' }}</td>
                                                <td>{{ rule.scope_id > 0 ? rule.scope_id : (rule.party_type || 'No aplica') }}</td>
                                                <td>{{ rule.valid_from || 'Sin inicio' }} - {{ rule.valid_until || 'Sin fin' }}</td>
                                                <td class="text-right"><button type="button" class="btn btn-xs btn-outline-primary" @click="editAccessRule(rule)">Editar</button></td>
                                            </tr>
                                            <tr v-if="accessRules.length === 0">
                                                <td colspan="6" class="text-center text-muted py-4">Aún no hay reglas de acceso.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-header"><strong>{{ forms.access.id ? 'Editar acceso' : 'Nueva regla de acceso' }}</strong></div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Software</label>
                                            <select class="form-control" v-model="forms.access.software_id">
                                                <option value="0">Selecciona</option>
                                                <option v-for="item in items" :key="item.id" :value="item.id">{{ item.name }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Recurso específico</label>
                                            <select class="form-control" v-model="forms.access.asset_id">
                                                <option value="0">Todo el software</option>
                                                <option v-for="asset in assets" :key="asset.id" :value="asset.id">{{ asset.title }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Alcance</label>
                                            <select class="form-control" v-model="forms.access.scope_type">
                                                <option v-for="option in options.scope_types" :key="option.code" :value="option.code">{{ option.label }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Portal</label>
                                            <select class="form-control" v-model="forms.access.portal_code">
                                                <option value="">No aplica</option>
                                                <option v-for="option in options.portal_codes" :key="option.code" :value="option.code">{{ option.label }}</option>
                                            </select>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>ID entidad</label>
                                                <input type="number" min="0" class="form-control" v-model="forms.access.scope_id">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Tipo entidad</label>
                                                <select class="form-control" v-model="forms.access.party_type">
                                                    <option value="">No aplica</option>
                                                    <option v-for="option in options.party_types" :key="option.code" :value="option.code">{{ option.label }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Desde</label>
                                                <input type="date" class="form-control" v-model="forms.access.valid_from">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Hasta</label>
                                                <input type="date" class="form-control" v-model="forms.access.valid_until">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Notas</label>
                                            <textarea class="form-control" rows="2" v-model="forms.access.notes"></textarea>
                                        </div>
                                        <button type="button" class="btn btn-primary" @click="saveAccessRule" :disabled="saving">Guardar acceso</button>
                                        <button type="button" class="btn btn-link" @click="resetAccessRule">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'categories'">
                        <div class="row">
                            <div class="col-lg-7">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Categoría</th>
                                            <th>Orden</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="category in categories" :key="category.id">
                                            <td>{{ category.code }}</td>
                                            <td>{{ category.name }}</td>
                                            <td>{{ category.sort_order }}</td>
                                            <td class="text-right"><button type="button" class="btn btn-xs btn-outline-primary" @click="editCategory(category)">Editar</button></td>
                                        </tr>
                                        <tr v-if="categories.length === 0">
                                            <td colspan="4" class="text-center text-muted py-4">Aún no hay categorías.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-lg-5">
                                <div class="card bg-light">
                                    <div class="card-header"><strong>{{ forms.category.id ? 'Editar categoría' : 'Nueva categoría' }}</strong></div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Nombre</label>
                                            <input type="text" class="form-control" v-model="forms.category.name">
                                        </div>
                                        <div class="form-group">
                                            <label>Código</label>
                                            <input type="text" class="form-control" v-model="forms.category.code">
                                        </div>
                                        <div class="form-group">
                                            <label>Descripción</label>
                                            <textarea class="form-control" rows="2" v-model="forms.category.description"></textarea>
                                        </div>
                                        <button type="button" class="btn btn-primary" @click="saveCategory" :disabled="saving">Guardar categoría</button>
                                        <button type="button" class="btn btn-link" @click="resetCategory">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?php echo Uri::base(false); ?>assets/js/core-api-client.js"></script>
<script>
new Vue({
    el: '#software-library-app',
    data: {
        loading: true,
        saving: false,
        error: '',
        message: '',
        activeTab: 'software',
        tabs: [
            {key: 'software', label: 'Software'},
            {key: 'versions', label: 'Versiones'},
            {key: 'assets', label: 'Archivos y enlaces'},
            {key: 'upload', label: 'Carga grande'},
            {key: 'access', label: 'Accesos'},
            {key: 'categories', label: 'Categorías'}
        ],
        categories: [],
        items: [],
        versions: [],
        assets: [],
        accessRules: [],
        stats: {},
        options: {portal_codes: [], party_types: [], scope_types: []},
        upload: {file: null, progress: 0},
        forms: {
            category: {},
            item: {},
            version: {},
            asset: {},
            access: {},
            upload: {}
        }
    },
    computed: {
        statCards: function () {
            return [
                {key: 'items', label: 'Software activo', value: this.stats.items || 0},
                {key: 'assets', label: 'Recursos', value: this.stats.assets || 0},
                {key: 'rules', label: 'Reglas activas', value: this.stats.rules || 0},
                {key: 'downloads', label: 'Descargas', value: this.stats.downloads || 0}
            ];
        },
        versionsForUpload: function () {
            var softwareId = parseInt(this.forms.upload.software_id || 0, 10);
            return this.versions.filter(function (version) {
                return parseInt(version.software_id || 0, 10) === softwareId;
            });
        }
    },
    mounted: function () {
        this.resetForms();
        this.load();
    },
    methods: {
        load: async function () {
            this.loading = true;
            this.error = '';
            try {
                var response = await CoreApiClient.get('<?php echo Uri::create('admin/software-library/data'); ?>');
                if (!response.ok || !response.payload.success) {
                    this.error = CoreApiClient.safeMessage(response.payload, 'No se pudo cargar la biblioteca.');
                    return;
                }
                this.applyLibrary(response.payload.data);
            } catch (error) {
                this.error = CoreApiClient.safeMessage(error, 'No se pudo cargar la biblioteca.');
            } finally {
                this.loading = false;
            }
        },
        post: async function (url, data) {
            this.saving = true;
            this.error = '';
            this.message = '';
            try {
                var response = await CoreApiClient.post(url, CoreApiClient.withCsrf(data || {}));
                if (!response.ok || !response.payload.success) {
                    this.error = CoreApiClient.safeMessage(response.payload, 'No se pudo guardar.');
                    return null;
                }
                this.message = response.payload.message || 'Cambios guardados.';
                if (response.payload.data && response.payload.data.library) {
                    this.applyLibrary(response.payload.data.library);
                }
                return response.payload;
            } catch (error) {
                this.error = CoreApiClient.safeMessage(error, 'No se pudo guardar.');
                return null;
            } finally {
                this.saving = false;
            }
        },
        applyLibrary: function (data) {
            data = data || {};
            this.categories = data.categories || [];
            this.items = data.items || [];
            this.versions = data.versions || [];
            this.assets = data.assets || [];
            this.accessRules = data.access_rules || [];
            this.stats = data.stats || {};
            this.options = data.options || {portal_codes: [], party_types: [], scope_types: []};
        },
        saveCategory: function () {
            return this.post('<?php echo Uri::create('admin/software-library/save_category'); ?>', this.forms.category);
        },
        saveItem: function () {
            return this.post('<?php echo Uri::create('admin/software-library/save_item'); ?>', this.forms.item);
        },
        saveVersion: function () {
            return this.post('<?php echo Uri::create('admin/software-library/save_version'); ?>', this.forms.version);
        },
        saveAsset: function () {
            this.forms.asset.asset_type = 'link';
            return this.post('<?php echo Uri::create('admin/software-library/save_asset'); ?>', this.forms.asset);
        },
        saveAccessRule: function () {
            return this.post('<?php echo Uri::create('admin/software-library/save_access_rule'); ?>', this.forms.access);
        },
        selectUploadFile: function (event) {
            this.upload.file = event.target.files && event.target.files.length ? event.target.files[0] : null;
            if (this.upload.file && !this.forms.upload.title) {
                this.forms.upload.title = this.upload.file.name;
            }
        },
        uploadFile: async function () {
            if (!this.upload.file) {
                this.error = 'Selecciona un archivo.';
                return;
            }
            this.saving = true;
            this.error = '';
            this.message = '';
            this.upload.progress = 0;
            try {
                var start = await CoreApiClient.post('<?php echo Uri::create('admin/software-library/upload_start'); ?>', CoreApiClient.withCsrf({
                    software_id: this.forms.upload.software_id,
                    version_id: this.forms.upload.version_id,
                    title: this.forms.upload.title,
                    filename: this.upload.file.name,
                    mime_type: this.upload.file.type || 'application/octet-stream',
                    size_bytes: this.upload.file.size
                }));
                if (!start.ok || !start.payload.success) {
                    this.error = CoreApiClient.safeMessage(start.payload, 'No se pudo iniciar la carga.');
                    return;
                }

                var uploadCode = start.payload.data.upload_code;
                var chunkSize = parseInt(start.payload.data.chunk_size || 5242880, 10);
                var total = Math.ceil(this.upload.file.size / chunkSize);
                for (var i = 0; i < total; i++) {
                    var formData = new FormData();
                    formData.append('upload_code', uploadCode);
                    formData.append('chunk_index', i);
                    formData.append('chunk', this.upload.file.slice(i * chunkSize, Math.min(this.upload.file.size, (i + 1) * chunkSize)));
                    formData.append(CoreApiClient.csrfToken().key, CoreApiClient.csrfToken().value);

                    var chunk = await CoreApiClient.post('<?php echo Uri::create('admin/software-library/upload_chunk'); ?>', formData);
                    if (!chunk.ok || !chunk.payload.success) {
                        this.error = CoreApiClient.safeMessage(chunk.payload, 'No se pudo subir una parte del archivo.');
                        return;
                    }
                    this.upload.progress = Math.round(((i + 1) / total) * 100);
                }

                var finish = await CoreApiClient.post('<?php echo Uri::create('admin/software-library/upload_finish'); ?>', CoreApiClient.withCsrf({upload_code: uploadCode}));
                if (!finish.ok || !finish.payload.success) {
                    this.error = CoreApiClient.safeMessage(finish.payload, 'No se pudo finalizar la carga.');
                    return;
                }
                this.message = finish.payload.message || 'Archivo cargado.';
                if (finish.payload.data && finish.payload.data.library) {
                    this.applyLibrary(finish.payload.data.library);
                }
                this.upload.file = null;
                this.upload.progress = 0;
                this.activeTab = 'assets';
            } catch (error) {
                this.error = CoreApiClient.safeMessage(error, 'No se pudo subir el archivo.');
            } finally {
                this.saving = false;
            }
        },
        editCategory: function (category) {
            this.forms.category = Object.assign({}, category);
        },
        editItem: function (item) {
            this.forms.item = Object.assign({}, item);
        },
        editVersion: function (version) {
            this.forms.version = Object.assign({}, version);
        },
        editAccessRule: function (rule) {
            this.forms.access = Object.assign({}, rule);
        },
        resetForms: function () {
            this.resetCategory();
            this.resetItem();
            this.resetVersion();
            this.resetAsset();
            this.resetAccessRule();
            this.forms.upload = {software_id: 0, version_id: 0, title: ''};
        },
        resetCategory: function () {
            this.forms.category = {id: 0, name: '', code: '', description: '', sort_order: 0, active: 1};
        },
        resetItem: function () {
            this.forms.item = {id: 0, name: '', code: '', description: '', category_id: 0, vendor: '', software_type: 'tool', platform: 'windows', architecture: 'x64', license_mode: 'none', support_notes: '', status: 'published', visibility: 'restricted', active: 1};
        },
        resetVersion: function () {
            this.forms.version = {id: 0, software_id: 0, version: '', release_date: '', changelog: '', is_latest: 1, status: 'published', active: 1};
        },
        resetAsset: function () {
            this.forms.asset = {id: 0, software_id: 0, version_id: 0, asset_type: 'link', title: '', external_url: '', requires_login: 1, status: 'published', active: 1};
        },
        resetAccessRule: function () {
            this.forms.access = {id: 0, software_id: 0, asset_id: 0, scope_type: 'portal', scope_id: 0, portal_code: 'clientes', party_type: '', permission: 'download', valid_from: '', valid_until: '', notes: '', active: 1};
        },
        statusClass: function (status) {
            if (status === 'published') return 'badge-success';
            if (status === 'draft') return 'badge-secondary';
            if (status === 'archived') return 'badge-dark';
            return 'badge-info';
        },
        statusLabel: function (status) {
            var labels = {published: 'Publicado', draft: 'Borrador', archived: 'Archivado'};
            return labels[status] || this.label(status);
        },
        label: function (value) {
            return String(value || '').replace(/_/g, ' ').replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
        },
        formatSize: function (bytes) {
            bytes = parseInt(bytes || 0, 10);
            if (bytes <= 0) return 'No aplica';
            var units = ['B', 'KB', 'MB', 'GB', 'TB'];
            var index = 0;
            while (bytes >= 1024 && index < units.length - 1) {
                bytes = bytes / 1024;
                index++;
            }
            return bytes.toFixed(index === 0 ? 0 : 1) + ' ' + units[index];
        }
    }
});
</script>
