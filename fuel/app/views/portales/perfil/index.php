<div id="portal-profile" v-cloak>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <h1 class="h4 mb-1">{{ labels.profile || 'Mi cuenta' }}</h1>
                    <p class="text-muted mb-0">Administra informacion operativa del portal sin depender del administrador para cada ajuste.</p>
                </div>
                <button class="btn btn-outline-primary btn-sm" v-on:click="load"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
            </div>
        </div>
    </div>

    <div class="alert alert-success mt-3" v-if="message">{{ message }}</div>
    <div class="alert alert-danger mt-3" v-if="error">{{ error }}</div>

    <div class="row mt-3">
        <div class="col-lg-4">
            <div class="card card-primary card-outline">
                <div class="card-header"><h2 class="card-title h6 mb-0">Datos fiscales y comerciales</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Nombre comercial</label>
                        <input class="form-control" v-model="party.name">
                    </div>
                    <div class="form-group">
                        <label>Razon social</label>
                        <input class="form-control" v-model="party.legal_name">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>RFC</label>
                            <input class="form-control" v-model="party.rfc" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Regimen fiscal</label>
                            <input class="form-control" v-model="party.sat_tax_regime_code">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Correo</label>
                        <input class="form-control" v-model="party.email">
                    </div>
                    <div class="form-group">
                        <label>Telefono</label>
                        <input class="form-control" v-model="party.phone">
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea class="form-control" rows="3" v-model="party.notes"></textarea>
                    </div>
                    <button class="btn btn-primary btn-sm" v-on:click="saveParty"><i class="bi bi-save"></i> Guardar datos</button>
                </div>
            </div>

            <div class="card card-info card-outline">
                <div class="card-header"><h2 class="card-title h6 mb-0">{{ labels.credit || 'Credito' }}</h2></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6">Dias</dt>
                        <dd class="col-6">{{ party.credit_days || 0 }}</dd>
                        <dt class="col-6">Limite</dt>
                        <dd class="col-6">{{ money(party.credit_limit || 0) }}</dd>
                    </dl>
                    <small class="text-muted">Estos valores los autoriza administracion para mantener control de riesgo.</small>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-success card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title h6 mb-0">{{ labels.addresses || 'Direcciones' }}</h2>
                    <button class="btn btn-success btn-xs ml-auto" v-on:click="newAddress"><i class="bi bi-plus"></i> Nueva</button>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tipo</label>
                            <select class="form-control" v-model="address.address_type">
                                <option value="delivery">Entrega</option>
                                <option value="warehouse">Bodega</option>
                                <option value="pickup">Recoleccion</option>
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
                        <div class="form-group col-md-3"><label>Pais</label><input class="form-control" v-model="address.country_code"></div>
                        <div class="form-group col-md-3"><label>Default</label><select class="form-control" v-model="address.is_default"><option value="0">No</option><option value="1">Si</option></select></div>
                        <div class="form-group col-md-3"><button class="btn btn-success btn-block" v-on:click="saveAddress">Guardar</button></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Tipo</th><th>Nombre</th><th>Direccion</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="row in addresses" v-bind:key="row.id">
                                    <td>{{ row.address_type }}</td>
                                    <td>{{ row.name }}</td>
                                    <td>{{ row.street }} {{ row.exterior_number }}, {{ row.city }} {{ row.state }} {{ row.postal_code }}</td>
                                    <td><button class="btn btn-outline-secondary btn-xs" v-on:click="editAddress(row)">Editar</button></td>
                                </tr>
                                <tr v-if="!addresses.length"><td colspan="4" class="text-muted">Sin direcciones.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-warning card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title h6 mb-0">{{ labels.contacts || 'Contactos' }}</h2>
                    <button class="btn btn-warning btn-xs ml-auto" v-on:click="newContact"><i class="bi bi-plus"></i> Nuevo</button>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Nombre</label><input class="form-control" v-model="contact.name"></div>
                        <div class="form-group col-md-3"><label>Puesto / funcion</label><input class="form-control" v-model="contact.position"></div>
                        <div class="form-group col-md-3"><label>Correo</label><input class="form-control" v-model="contact.email"></div>
                        <div class="form-group col-md-2"><label>Telefono</label><input class="form-control" v-model="contact.phone"></div>
                    </div>
                    <button class="btn btn-warning btn-sm" v-on:click="saveContact">Guardar contacto</button>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Nombre</th><th>Funcion</th><th>Correo</th><th>Telefono</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="row in contacts" v-bind:key="row.id">
                                    <td>{{ row.name }}</td><td>{{ row.position }}</td><td>{{ row.email }}</td><td>{{ row.phone }}</td>
                                    <td><button class="btn btn-outline-secondary btn-xs" v-on:click="editContact(row)">Editar</button></td>
                                </tr>
                                <tr v-if="!contacts.length"><td colspan="5" class="text-muted">Sin contactos.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-secondary card-outline">
                <div class="card-header"><h2 class="card-title h6 mb-0">{{ labels.documents || 'Documentos' }}</h2></div>
                <div class="card-body">
                    <form v-on:submit.prevent="uploadDocument" enctype="multipart/form-data">
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-3">
                                <label>Tipo</label>
                                <select class="form-control" v-model="document_type">
                                    <option value="constancia_fiscal">Constancia fiscal</option>
                                    <option value="opinion_cumplimiento">Opinion cumplimiento</option>
                                    <option value="contrato">Contrato</option>
                                    <option value="identificacion">Identificacion</option>
                                    <option value="evidencia">Evidencia</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4"><label>Titulo</label><input class="form-control" v-model="document_title"></div>
                            <div class="form-group col-md-3"><label>Archivo</label><input class="form-control-file" ref="file" type="file"></div>
                            <div class="form-group col-md-2"><button class="btn btn-secondary btn-block">Subir</button></div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Tipo</th><th>Titulo</th><th>Archivo</th><th>Fecha</th></tr></thead>
                            <tbody>
                                <tr v-for="row in documents" v-bind:key="row.id">
                                    <td>{{ row.relation_type }}</td><td>{{ row.title }}</td>
                                    <td><a v-bind:href="baseUrl + row.file_path" target="_blank">{{ row.original_name }}</a></td>
                                    <td>{{ date(row.created_at) }}</td>
                                </tr>
                                <tr v-if="!documents.length"><td colspan="4" class="text-muted">Sin documentos.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="clientes" class="card card-danger card-outline" v-if="features.reseller">
                <div class="card-header"><h2 class="card-title h6 mb-0">Clientes del revendedor</h2></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Nombre</label><input class="form-control" v-model="reseller_customer.name"></div>
                        <div class="form-group col-md-4"><label>Razon social</label><input class="form-control" v-model="reseller_customer.legal_name"></div>
                        <div class="form-group col-md-4"><label>RFC</label><input class="form-control" v-model="reseller_customer.rfc"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Correo</label><input class="form-control" v-model="reseller_customer.email"></div>
                        <div class="form-group col-md-4"><label>Telefono</label><input class="form-control" v-model="reseller_customer.phone"></div>
                        <div class="form-group col-md-4 d-flex align-items-end"><button class="btn btn-danger btn-block" v-on:click="createResellerCustomer">Crear cliente</button></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>Cliente</th><th>RFC</th><th>Correo</th><th>Telefono</th></tr></thead>
                            <tbody>
                                <tr v-for="row in reseller_customers" v-bind:key="row.id"><td>{{ row.name }}</td><td>{{ row.rfc }}</td><td>{{ row.email }}</td><td>{{ row.phone }}</td></tr>
                                <tr v-if="!reseller_customers.length"><td colspan="4" class="text-muted">Sin clientes registrados.</td></tr>
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
            party: {},
            addresses: [],
            contacts: [],
            documents: [],
            reseller_customers: [],
            features: {},
            labels: {},
            address: {},
            contact: {},
            reseller_customer: {},
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
                    .catch(function(err) { self.error = err && err.error ? err.error : 'No se pudo completar la accion.'; });
            },
            load: function() {
                var self = this;
                fetch('<?php echo Uri::base(false); ?>' + self.portal + '/perfil_data', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function(response) {
                        return response.json().then(function(json) { if (!response.ok) { throw json; } return json; });
                    })
                    .then(function(json) { self.apply(json); })
                    .catch(function(err) { self.error = err && err.error ? err.error : 'No se pudo cargar la informacion. Revisa sesion, permisos o conexion.'; });
            },
            apply: function(json) {
                this.party = json.party || {};
                this.addresses = json.addresses || [];
                this.contacts = json.contacts || [];
                this.documents = json.documents || [];
                this.reseller_customers = json.reseller_customers || [];
                this.features = json.features || {};
                this.labels = json.labels || {};
                this.message = json.message || '';
            },
            saveParty: function() { this.request('perfil_save', this.party); },
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
