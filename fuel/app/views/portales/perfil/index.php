<style>
    .portal-profile-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
    .portal-profile-summary-item { border: 1px solid #e5e9f0; border-radius: 8px; background: #fff; padding: 10px; min-width: 0; }
    .portal-profile-summary-label { color: #64748b; font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
    .portal-document-link { word-break: break-word; }
    @media (max-width: 767px) {
        .portal-profile-summary { grid-template-columns: 1fr; }
        .portal-page-actions .btn { width: 100%; margin-bottom: 6px; }
    }
</style>

<div id="portal-profile" v-cloak>
    <div class="portal-page-hero">
        <div>
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <h1 class="h4 mb-1"><?php echo ($portal_code === 'proveedores') ? 'Mi perfil de proveedor' : "{{ labels.profile || 'Mi cuenta' }}"; ?></h1>
                    <p class="text-muted mb-0">Administra información operativa del portal sin depender del administrador para cada ajuste.</p>
                </div>
                <div class="portal-page-actions mt-3 mt-md-0">
                    <a class="btn btn-outline-secondary btn-sm" v-bind:href="baseUrl + portal">
                        <i class="bi bi-arrow-left mr-1"></i> Inicio
                    </a>
                    <button class="btn btn-primary btn-sm" v-on:click="load"><i class="bi bi-arrow-clockwise mr-1"></i> Actualizar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-success mt-3" v-if="message">{{ message }}</div>
    <div class="alert alert-danger mt-3" v-if="error">{{ error }}</div>

    <div class="row mt-3">
        <div class="col-lg-4">
            <div class="portal-panel">
                <div class="portal-panel-header"><h2 class="h6 mb-0">Datos fiscales y comerciales</h2></div>
                <div class="portal-panel-body">
                    <div class="portal-profile-summary">
                        <div class="portal-profile-summary-item">
                            <div class="portal-profile-summary-label">Nombre</div>
                            <div>{{ party.name || '-' }}</div>
                        </div>
                        <div class="portal-profile-summary-item">
                            <div class="portal-profile-summary-label">RFC</div>
                            <div>{{ party.rfc || '-' }}</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nombre comercial</label>
                        <input class="form-control" v-model="party.name">
                    </div>
                    <div class="form-group">
                        <label>Razón social</label>
                        <input class="form-control" v-model="party.legal_name">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>RFC</label>
                            <input class="form-control" v-model="party.rfc" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Régimen fiscal</label>
                            <select class="form-control" v-model="party.sat_tax_regime_code">
                                <option value="">Selecciona régimen</option>
                                <option v-for="option in options.sat_tax_regimes" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Correo</label>
                        <input class="form-control" v-model="party.email">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input class="form-control" v-model="party.phone">
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea class="form-control" rows="3" v-model="party.notes"></textarea>
                    </div>
                    <button class="btn btn-primary btn-sm" v-on:click="saveParty"><i class="bi bi-save"></i> Guardar datos</button>
                </div>
            </div>

            <div class="portal-panel">
                <div class="portal-panel-header"><h2 class="h6 mb-0">{{ labels.credit || 'Crédito' }}</h2></div>
                <div class="portal-panel-body">
                    <dl class="row mb-0">
                        <dt class="col-6">Días</dt>
                        <dd class="col-6">{{ party.credit_days || 0 }}</dd>
                        <dt class="col-6">Límite</dt>
                        <dd class="col-6">{{ money(party.credit_limit || 0) }}</dd>
                    </dl>
                    <small class="text-muted">Estos valores los autoriza administración para mantener control de riesgo.</small>
                </div>
            </div>

            <div class="portal-panel">
                <div class="portal-panel-header"><h2 class="h6 mb-0">Acceso y contraseña</h2></div>
                <div class="portal-panel-body">
                    <dl class="row mb-3">
                        <dt class="col-5">Usuario</dt>
                        <dd class="col-7">{{ user.username || '-' }}</dd>
                        <dt class="col-5">Correo</dt>
                        <dd class="col-7">{{ user.email || '-' }}</dd>
                        <dt class="col-5">{{ portal === 'proveedores' ? 'Proveedor' : 'Cliente' }}</dt>
                        <dd class="col-7">{{ party.name || '-' }}</dd>
                    </dl>

                    <div class="alert alert-light border small">
                        Cambia solo tu contraseña de acceso. Debe tener mínimo 12 caracteres. Esto no modifica permisos, grupo, correo ni datos fiscales.
                    </div>

                    <div class="form-group">
                        <label>Contraseña actual</label>
                        <input type="password" class="form-control" v-model="password_form.current_password" autocomplete="current-password">
                    </div>
                    <div class="form-group">
                        <label>Nueva contraseña</label>
                        <input type="password" class="form-control" v-model="password_form.password" autocomplete="new-password">
                        <small class="text-muted">Mínimo 12 caracteres.</small>
                    </div>
                    <div class="form-group">
                        <label>Confirmar nueva contraseña</label>
                        <input type="password" class="form-control" v-model="password_form.password_confirm" autocomplete="new-password">
                    </div>
                    <button class="btn btn-primary btn-sm" v-on:click="changePassword" v-bind:disabled="password_saving">
                        <span v-if="password_saving" class="spinner-border spinner-border-sm mr-1"></span>
                        Cambiar contraseña
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="portal-panel">
                <div class="portal-panel-header">
                    <h2 class="h6 mb-0">{{ labels.addresses || 'Direcciones' }}</h2>
                    <button class="btn btn-success btn-xs ml-auto" v-on:click="newAddress"><i class="bi bi-plus"></i> Nueva</button>
                </div>
                <div class="portal-panel-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tipo</label>
                            <select class="form-control" v-model="address.address_type">
                                <option value="delivery">Entrega</option>
                                <option value="warehouse">Bodega</option>
                                <option value="pickup">Recolección</option>
                                <option value="billing">Fiscal</option>
                            </select>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Nombre</label>
                            <input class="form-control" v-model="address.name" placeholder="Matriz, bodega norte, obra...">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label>Calle</label><input class="form-control" v-model="address.street"></div>
                        <div class="form-group col-md-3"><label>Exterior</label><input class="form-control" v-model="address.exterior_number"></div>
                        <div class="form-group col-md-3"><label>Interior</label><input class="form-control" v-model="address.interior_number"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Colonia</label><input class="form-control" v-model="address.neighborhood"></div>
                        <div class="form-group col-md-4"><label>Ciudad</label><input class="form-control" v-model="address.city"></div>
                        <div class="form-group col-md-4"><label>Estado</label><input class="form-control" v-model="address.state"></div>
                    </div>
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-3"><label>CP</label><input class="form-control" v-model="address.postal_code"></div>
                        <div class="form-group col-md-3"><label>País</label><input class="form-control" v-model="address.country_code"></div>
                        <div class="form-group col-md-3"><label>Principal</label><select class="form-control" v-model="address.is_default"><option value="0">No</option><option value="1">Sí</option></select></div>
                        <div class="form-group col-md-3"><button class="btn btn-success btn-block" v-on:click="saveAddress">Guardar</button></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped portal-table">
                            <thead><tr><th>Tipo</th><th>Nombre</th><th>Dirección</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="row in addresses" v-bind:key="row.id">
                                    <td>{{ row.address_type }}</td>
                                    <td>{{ row.name }}</td>
                                    <td>{{ row.street }} {{ row.exterior_number }}, {{ row.city }} {{ row.state }} {{ row.postal_code }}</td>
                                    <td><button class="btn btn-outline-secondary btn-xs" v-on:click="editAddress(row)">Editar</button></td>
                                </tr>
                                <tr v-if="!addresses.length"><td colspan="4"><div class="portal-empty">Sin direcciones registradas.</div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="portal-panel">
                <div class="portal-panel-header">
                    <h2 class="h6 mb-0">{{ labels.contacts || 'Contactos' }}</h2>
                    <button class="btn btn-warning btn-xs ml-auto" v-on:click="newContact"><i class="bi bi-plus"></i> Nuevo</button>
                </div>
                <div class="portal-panel-body">
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Nombre</label><input class="form-control" v-model="contact.name"></div>
                        <div class="form-group col-md-3"><label>Puesto / función</label><input class="form-control" v-model="contact.position"></div>
                        <div class="form-group col-md-3"><label>Correo</label><input class="form-control" v-model="contact.email"></div>
                        <div class="form-group col-md-2"><label>Teléfono</label><input class="form-control" v-model="contact.phone"></div>
                    </div>
                    <button class="btn btn-warning btn-sm" v-on:click="saveContact">Guardar contacto</button>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-striped portal-table">
                            <thead><tr><th>Nombre</th><th>Función</th><th>Correo</th><th>Teléfono</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="row in contacts" v-bind:key="row.id">
                                    <td>{{ row.name }}</td><td>{{ row.position }}</td><td>{{ row.email }}</td><td>{{ row.phone }}</td>
                                    <td><button class="btn btn-outline-secondary btn-xs" v-on:click="editContact(row)">Editar</button></td>
                                </tr>
                                <tr v-if="!contacts.length"><td colspan="5"><div class="portal-empty">Sin contactos registrados.</div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="portal-panel">
                <div class="portal-panel-header"><h2 class="h6 mb-0">{{ labels.documents || 'Documentos' }}</h2></div>
                <div class="portal-panel-body">
                    <form v-on:submit.prevent="uploadDocument" enctype="multipart/form-data">
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-3">
                                <label>Tipo</label>
                                <select class="form-control" v-model="document_type">
                                    <option value="constancia_fiscal">Constancia fiscal</option>
                                    <option value="opinion_cumplimiento">Opinión cumplimiento</option>
                                    <option value="contrato">Contrato</option>
                                    <option value="identificacion">Identificación</option>
                                    <option value="evidencia">Evidencia</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4"><label>Título</label><input class="form-control" v-model="document_title"></div>
                            <div class="form-group col-md-3"><label>Archivo</label><input class="form-control-file" ref="file" type="file"></div>
                            <div class="form-group col-md-2"><button class="btn btn-secondary btn-block">Subir</button></div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped portal-table">
                            <thead><tr><th>Tipo</th><th>Título</th><th>Archivo</th><th>Fecha</th></tr></thead>
                            <tbody>
                                <tr v-for="row in documents" v-bind:key="row.id">
                                    <td>{{ row.relation_type }}</td><td>{{ row.title }}</td>
                                    <td><a v-if="row.download_url" class="portal-document-link" v-bind:href="row.download_url" target="_blank" rel="noopener">{{ row.original_name }}</a><span v-else class="text-muted small">Sin descarga</span></td>
                                    <td>{{ date(row.created_at) }}</td>
                                </tr>
                                <tr v-if="!documents.length"><td colspan="4"><div class="portal-empty">Sin documentos cargados.</div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="clientes" class="portal-panel" v-if="features.reseller">
                <div class="portal-panel-header"><h2 class="h6 mb-0">Clientes del revendedor</h2></div>
                <div class="portal-panel-body">
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Nombre</label><input class="form-control" v-model="reseller_customer.name"></div>
                        <div class="form-group col-md-4"><label>Razón social</label><input class="form-control" v-model="reseller_customer.legal_name"></div>
                        <div class="form-group col-md-4"><label>RFC</label><input class="form-control" v-model="reseller_customer.rfc"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Correo</label><input class="form-control" v-model="reseller_customer.email"></div>
                        <div class="form-group col-md-4"><label>Teléfono</label><input class="form-control" v-model="reseller_customer.phone"></div>
                        <div class="form-group col-md-4 d-flex align-items-end"><button class="btn btn-danger btn-block" v-on:click="createResellerCustomer">Crear cliente</button></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped portal-table">
                            <thead><tr><th>Cliente</th><th>RFC</th><th>Correo</th><th>Teléfono</th></tr></thead>
                            <tbody>
                                <tr v-for="row in reseller_customers" v-bind:key="row.id"><td>{{ row.name }}</td><td>{{ row.rfc }}</td><td>{{ row.email }}</td><td>{{ row.phone }}</td></tr>
                                <tr v-if="!reseller_customers.length"><td colspan="4"><div class="portal-empty">Sin clientes registrados.</div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Vue({
        el: '#portal-profile',
        data: {
            baseUrl: <?php echo json_encode(Uri::base(false)); ?>,
            portal: <?php echo json_encode($portal_code); ?>,
            user: {},
            party: {},
            addresses: [],
            contacts: [],
            documents: [],
            reseller_customers: [],
            features: {},
            labels: {},
            options: { sat_tax_regimes: [] },
            address: {},
            contact: {},
            reseller_customer: {},
            password_form: { current_password: '', password: '', password_confirm: '' },
            password_saving: false,
            document_type: 'constancia_fiscal',
            document_title: '',
            message: '',
            error: ''
        },
        mounted: function() {
            this.newAddress();
            this.newContact();
            this.load();
        },
        methods: {
            request: function(path, data) {
                var self = this;
                self.message = '';
                self.error = '';
                return fetch('<?php echo Uri::base(false); ?>' + self.portal + '/' + path, window.coreAppFetchOptions(data || {}))
                    .then(function(response) { return response.json().then(function(json) { if (!response.ok) { throw json; } return json; }); })
                    .then(function(json) { self.apply(json); return json; })
                    .catch(function(err) { self.error = err && err.error ? err.error : 'No se pudo completar la acción.'; });
            },
            load: function() {
                var self = this;
                fetch('<?php echo Uri::base(false); ?>' + self.portal + '/perfil_data', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function(response) {
                        return response.json().then(function(json) { if (!response.ok) { throw json; } return json; });
                    })
                    .then(function(json) { self.apply(json); })
                    .catch(function(err) { self.error = err && err.error ? err.error : 'No se pudo cargar la información. Revisa sesión, permisos o conexión.'; });
            },
            apply: function(json) {
                this.user = json.user || {};
                this.party = json.party || {};
                this.addresses = json.addresses || [];
                this.contacts = json.contacts || [];
                this.documents = json.documents || [];
                this.reseller_customers = json.reseller_customers || [];
                this.features = json.features || {};
                this.labels = json.labels || {};
                this.options = json.options || { sat_tax_regimes: [] };
                this.message = json.message || '';
            },
            saveParty: function() { this.request('perfil_save', this.party); },
            changePassword: function() {
                var self = this;
                self.password_saving = true;
                self.request('perfil_password', self.password_form).then(function(json) {
                    if (json && json.status === 'ok') {
                        self.password_form = { current_password: '', password: '', password_confirm: '' };
                    }
                }).finally(function() {
                    self.password_saving = false;
                });
            },
            saveAddress: function() { this.request('perfil_address', this.address); },
            saveContact: function() { this.request('perfil_contact', this.contact); },
            editAddress: function(row) { this.address = Object.assign({}, row); },
            editContact: function(row) { this.contact = Object.assign({}, row); },
            newAddress: function() { this.address = { address_type: 'delivery', country_code: 'MX', is_default: 0 }; },
            newContact: function() { this.contact = { receives_notifications: 1 }; },
            createResellerCustomer: function() {
                this.request('cliente_create', this.reseller_customer);
                this.reseller_customer = {};
            },
            uploadDocument: function() {
                var file = this.$refs.file.files[0];
                if (!file) {
                    this.error = 'Selecciona un archivo.';
                    return;
                }
                var self = this;
                var form = new FormData();
                form.append(window.coreAppCsrfKey, fuel_csrf_token());
                form.append('document_type', self.document_type);
                form.append('title', self.document_title);
                form.append('file', file);
                fetch('<?php echo Uri::base(false); ?>' + self.portal + '/perfil_upload', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-Token': fuel_csrf_token() },
                    body: form
                }).then(function(response) {
                    return response.json().then(function(json) { if (!response.ok) { throw json; } return json; });
                }).then(function(json) {
                    self.apply(json);
                    self.document_title = '';
                    self.$refs.file.value = '';
                }).catch(function(err) {
                    self.error = err && err.error ? err.error : 'No se pudo subir el archivo.';
                });
            },
            money: function(value) {
                return 'MXN ' + Number(value || 0).toFixed(2);
            },
            date: function(value) {
                if (!value) { return ''; }
                return new Date(Number(value) * 1000).toLocaleDateString();
            }
        }
    });
});
</script>
