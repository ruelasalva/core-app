<div id="app-integrations">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ stats.providers || 0 }}</h3><p>Proveedores</p></div>
                <div class="icon"><i class="bi bi-plug"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ stats.connections || 0 }}</h3><p>Conexiones</p></div>
                <div class="icon"><i class="bi bi-link-45deg"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ stats.enabled_connections || 0 }}</h3><p>Activas</p></div>
                <div class="icon"><i class="bi bi-toggle-on"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner"><h3>{{ stats.events || 0 }}</h3><p>Eventos</p></div>
                <div class="icon"><i class="bi bi-activity"></i></div>
            </div>
        </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Proveedores de integracion</h3>
                <button class="btn btn-primary btn-sm" @click="openProvider({})"><i class="bi bi-plus-lg"></i> Proveedor</button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Categoria</th>
                        <th>Adaptador</th>
                        <th>Instalacion</th>
                        <th>Activo</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="provider in providers" :key="provider.id">
                        <td><strong>{{ provider.name }}</strong><div class="text-muted small">{{ provider.code }}</div></td>
                        <td><span class="badge badge-light">{{ provider.category }}</span></td>
                        <td>{{ provider.adapter_class || '-' }}</td>
                        <td>{{ provider.requires_install == 1 ? 'Requiere' : 'No requiere' }}</td>
                        <td>{{ provider.active == 1 ? 'Si' : 'No' }}</td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-primary" @click="openProvider(provider)"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-xs btn-outline-success" @click="openConnection({ provider_id: provider.id })"><i class="bi bi-link-45deg"></i></button>
                        </td>
                    </tr>
                    <tr v-if="providers.length === 0"><td colspan="6" class="text-center text-muted">Sin proveedores</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-success card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Conexiones configuradas</h3>
                <button class="btn btn-success btn-sm" @click="openConnection({})"><i class="bi bi-plus-lg"></i> Conexion</button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Conexion</th>
                        <th>Proveedor</th>
                        <th>Ambiente</th>
                        <th>Credencial visible</th>
                        <th>Secretos</th>
                        <th>Activa</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="connection in connections" :key="connection.id">
                        <td><strong>{{ connection.name }}</strong><div class="text-muted small">{{ connection.code }}</div></td>
                        <td>{{ providerName(connection.provider_id) }}</td>
                        <td><span class="badge badge-light">{{ connection.environment }}</span></td>
                        <td>{{ isTokenOnly(connection.provider_id) ? 'No aplica' : (connection.public_key || '-') }}</td>
                        <td>{{ connection.has_secret ? 'Configurado' : 'Pendiente' }}</td>
                        <td>{{ connection.enabled == 1 ? 'Si' : 'No' }}</td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-primary" @click="openConnection(connection)"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <tr v-if="connections.length === 0"><td colspan="7" class="text-center text-muted">Sin conexiones</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-secondary card-outline">
        <div class="card-header"><h3 class="card-title mb-0">Eventos recientes</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered">
                <thead><tr><th>Fecha</th><th>Proveedor</th><th>Tipo</th><th>Direccion</th><th>Estado</th><th>Error</th></tr></thead>
                <tbody>
                    <tr v-for="event in events" :key="event.id">
                        <td>{{ event.created_at }}</td>
                        <td>{{ event.provider_code }}</td>
                        <td>{{ event.event_type }}</td>
                        <td>{{ event.direction }}</td>
                        <td><span class="badge badge-light">{{ event.status }}</span></td>
                        <td>{{ event.error_message }}</td>
                    </tr>
                    <tr v-if="events.length === 0"><td colspan="6" class="text-center text-muted">Sin eventos</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-provider" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Proveedor de integracion</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-provider')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4"><div class="form-group"><label>Codigo</label><input class="form-control" v-model="providerForm.code"></div></div>
                        <div class="col-md-5"><div class="form-group"><label>Nombre</label><input class="form-control" v-model="providerForm.name"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Categoria</label><input class="form-control" v-model="providerForm.category"></div></div>
                        <div class="col-md-12"><div class="form-group"><label>Descripcion</label><textarea class="form-control" rows="2" v-model="providerForm.description"></textarea></div></div>
                        <div class="col-md-6"><div class="form-group"><label>URL proveedor</label><input class="form-control" v-model="providerForm.website_url"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Clase adaptador</label><input class="form-control" v-model="providerForm.adapter_class"></div></div>
                        <div class="col-md-12"><div class="form-group"><label>Notas instalacion</label><textarea class="form-control" rows="3" v-model="providerForm.install_notes"></textarea></div></div>
                        <div class="col-md-6"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="provider-install" v-model="providerForm.requires_install"><label class="custom-control-label" for="provider-install">Requiere instalacion/adaptador</label></div></div>
                        <div class="col-md-6"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="provider-active" v-model="providerForm.active"><label class="custom-control-label" for="provider-active">Activo</label></div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-provider')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveProvider">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-connection" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Conexion de integracion</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-connection')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">{{ credentialHelp(connectionForm.provider_id) }}</div>
                    <div class="row">
                        <div class="col-md-4"><div class="form-group"><label>Proveedor</label><select class="form-control" v-model="connectionForm.provider_id"><option value="0">Selecciona</option><option v-for="provider in providers" :value="provider.id">{{ provider.name }}</option></select></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Codigo</label><input class="form-control" v-model="connectionForm.code"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Nombre</label><input class="form-control" v-model="connectionForm.name"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Ambiente</label><select class="form-control" v-model="connectionForm.environment"><option value="sandbox">Sandbox</option><option value="production">Produccion</option></select></div></div>
                        <div class="col-md-8" v-if="!isTokenOnly(connectionForm.provider_id)"><div class="form-group"><label>Public key</label><input class="form-control" v-model="connectionForm.public_key"></div></div>
                        <div :class="isTokenOnly(connectionForm.provider_id) ? 'col-md-8' : 'col-md-6'"><div class="form-group"><label>{{ secretLabel(connectionForm.provider_id) }}</label><input type="password" class="form-control" v-model="connectionForm.secret_value" :placeholder="connectionForm.has_secret ? 'Ya hay un valor guardado; captura uno nuevo solo si deseas cambiarlo' : ''"></div></div>
                        <div class="col-md-6" v-if="!isTokenOnly(connectionForm.provider_id)"><div class="form-group"><label>Webhook secret</label><input type="password" class="form-control" v-model="connectionForm.webhook_secret"></div></div>
                        <div class="col-md-12"><div class="form-group"><label>Configuracion JSON</label><textarea class="form-control" rows="3" v-model="connectionForm.config_json"></textarea></div></div>
                        <div class="col-md-6"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="connection-enabled" v-model="connectionForm.enabled"><label class="custom-control-label" for="connection-enabled">Habilitada</label></div></div>
                        <div class="col-md-6"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="connection-active" v-model="connectionForm.active"><label class="custom-control-label" for="connection-active">Activa</label></div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-connection')">Cerrar</button>
                    <button class="btn btn-success" @click="saveConnection">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-integrations',
        data: {
            error: '',
            providers: [],
            connections: [],
            webhooks: [],
            events: [],
            stats: {},
            providerForm: {},
            connectionForm: {}
        },
        mounted() {
            this.loadData();
        },
        methods: {
            loadData() {
                fetch('<?php echo Uri::create('admin/integrations/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) { this.error = data.error; return; }
                        this.providers = data.providers || [];
                        this.connections = data.connections || [];
                        this.webhooks = data.webhooks || [];
                        this.events = data.events || [];
                        this.stats = data.stats || {};
                    });
            },
            providerName(id) {
                const provider = this.providers.find(item => String(item.id) === String(id));
                return provider ? provider.name : '-';
            },
            providerCode(id) {
                const provider = this.providers.find(item => String(item.id) === String(id));
                return provider ? provider.code : '';
            },
            isTokenOnly(providerId) {
                return this.providerCode(providerId) === 'inegi_denue';
            },
            secretLabel(providerId) {
                return this.isTokenOnly(providerId) ? 'Token DENUE INEGI' : 'Secret value';
            },
            credentialHelp(providerId) {
                if (this.isTokenOnly(providerId)) {
                    return 'DENUE solo usa un token. Capturalo aqui; se guarda cifrado y no se muestra de vuelta.';
                }
                return 'Los secretos se cifran y no se muestran de vuelta. Para cambiarlos, captura un valor nuevo.';
            },
            openProvider(provider) {
                this.providerForm = Object.assign({ id: 0, code: '', name: '', category: 'general', description: '', website_url: '', adapter_class: '', requires_install: false, install_notes: '', config_schema_json: '', sort_order: 0, active: true }, provider);
                this.showModal('modal-provider');
            },
            openConnection(connection) {
                this.connectionForm = Object.assign({ id: 0, provider_id: 0, code: '', name: '', environment: 'sandbox', public_key: '', public_value: '', secret_value: '', webhook_secret: '', config_json: '', enabled: false, active: true }, connection);
                if (this.isTokenOnly(this.connectionForm.provider_id) && this.connectionForm.public_key && !this.connectionForm.has_secret) {
                    this.connectionForm.secret_value = this.connectionForm.public_key;
                } else {
                    this.connectionForm.secret_value = '';
                }
                this.connectionForm.webhook_secret = '';
                this.showModal('modal-connection');
            },
            saveProvider() {
                fetch('<?php echo Uri::create('admin/integrations/save_provider'); ?>', window.coreAppFetchOptions(this.providerForm))
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) { this.error = data.error; return; }
                        this.providers = data.providers || [];
                        this.stats = data.stats || this.stats;
                        this.hideModal('modal-provider');
                    });
            },
            saveConnection() {
                fetch('<?php echo Uri::create('admin/integrations/save_connection'); ?>', window.coreAppFetchOptions(this.connectionForm))
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) { this.error = data.error; return; }
                        this.connections = data.connections || [];
                        this.stats = data.stats || this.stats;
                        this.hideModal('modal-connection');
                    });
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
