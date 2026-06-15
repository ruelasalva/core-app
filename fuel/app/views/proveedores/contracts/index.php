<?php
/**
 * Portal proveedores - Mis contratos.
 *
 * Vista de lectura para contratos visibles en portal.
 */
?>
<style>
    .contracts-shell { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 16px; }
    .contract-row { cursor: pointer; }
    .contract-row.active { background: var(--portal-soft); }
    .contract-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .contract-meta-item { border: 1px solid #e5e9f0; border-radius: 8px; padding: 10px; background: #fff; }
    .contract-meta-label { color: #6c757d; font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 4px; }
    .contract-description { line-height: 1.6; }
    .contract-description p:last-child { margin-bottom: 0; }
    .badge-expiration-active { background: #28a745; color: #fff; }
    .badge-expiration-expiring_90 { background: #17a2b8; color: #fff; }
    .badge-expiration-expiring_60 { background: #ffc107; color: #212529; }
    .badge-expiration-expiring_30 { background: #fd7e14; color: #fff; }
    .badge-expiration-expired { background: #dc3545; color: #fff; }
    .badge-expiration-no_end_date { background: #6c757d; color: #fff; }
    .badge-expiration-inactive { background: #6c757d; color: #fff; }
    @media (max-width: 991px) {
        .contracts-shell { grid-template-columns: 1fr; }
        .contract-meta { grid-template-columns: 1fr; }
    }
</style>

<div id="app-supplier-contracts">
    <div class="portal-page-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h4 mb-1">Contratos de proveedor</h1>
                <div class="text-muted">Consulta contratos autorizados, documentos relacionados y eventos visibles.</div>
            </div>
            <div class="portal-page-actions mt-3 mt-md-0">
                <a class="btn btn-outline-secondary btn-sm" href="<?php echo Uri::create('proveedores'); ?>">
                    <i class="bi bi-arrow-left mr-1"></i> Inicio
                </a>
                <button class="btn btn-primary btn-sm" @click="load" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm mr-1"></span>
                    Actualizar
                </button>
            </div>
        </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="contracts-shell">
        <div class="portal-panel">
            <div class="portal-panel-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h2 class="h6 mb-0">Contratos visibles</h2>
                    <span class="badge badge-light">{{ contracts.length }} contrato(s)</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div v-if="loading" class="text-center p-4">
                    <div class="spinner-border text-primary"></div>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-sm table-hover portal-table mb-0">
                        <thead>
                            <tr>
                                <th>Numero</th>
                                <th>Tipo</th>
                                <th>Titulo</th>
                                <th>Estado</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Vencimiento</th>
                                <th class="text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="contract in contracts" :key="contract.id" class="contract-row" :class="{active: selected && selected.id === contract.id}" @click="select(contract)">
                                <td><strong>{{ contract.contract_number }}</strong></td>
                                <td>{{ contract.contract_type_label || contract.contract_type }}</td>
                                <td>{{ contract.title }}</td>
                                <td><span class="badge" :class="statusClass(contract.status)">{{ contract.status_label }}</span></td>
                                <td>{{ contract.start_label }}</td>
                                <td>{{ contract.end_label }}</td>
                                <td>
                                    <span class="badge" :class="expirationClass(contract.expiration_status)">{{ contract.expiration_label }}</span>
                                    <div class="text-muted small">{{ contract.expiration_days_label }}</div>
                                </td>
                                <td class="text-right">{{ money(contract.contract_value, contract.currency_code) }}</td>
                            </tr>
                            <tr v-if="contracts.length === 0">
                                <td colspan="8"><div class="portal-empty m-3">No hay contratos visibles para tu portal.</div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="portal-panel">
            <div class="portal-panel-header">
                <h2 class="h6 mb-0">Detalle</h2>
            </div>
            <div class="portal-panel-body">
                <div v-if="!selected" class="portal-empty">Selecciona un contrato para ver su detalle.</div>
                <div v-else>
                    <div class="mb-3">
                        <div class="text-muted small">Contrato</div>
                        <h3 class="h5 mb-1">{{ selected.contract_number }}</h3>
                        <div>{{ selected.title }}</div>
                    </div>

                    <ul class="nav nav-pills portal-tabs mb-3">
                        <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'general'}" @click.prevent="tab = 'general'"><i class="bi bi-info-circle mr-1"></i> General</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'documents'}" @click.prevent="tab = 'documents'"><i class="bi bi-paperclip mr-1"></i> Documentos</a></li>
                        <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'events'}" @click.prevent="tab = 'events'"><i class="bi bi-clock-history mr-1"></i> Eventos</a></li>
                    </ul>

                    <div v-show="tab === 'general'">
                        <div class="contract-meta mb-3">
                            <div class="contract-meta-item">
                                <div class="contract-meta-label">Tipo</div>
                                <div>{{ selected.contract_type_label }}</div>
                            </div>
                            <div class="contract-meta-item">
                                <div class="contract-meta-label">Estado</div>
                                <span class="badge" :class="statusClass(selected.status)">{{ selected.status_label }}</span>
                            </div>
                            <div class="contract-meta-item">
                                <div class="contract-meta-label">Inicio</div>
                                <div>{{ selected.start_label }}</div>
                            </div>
                            <div class="contract-meta-item">
                                <div class="contract-meta-label">Fin</div>
                                <div>{{ selected.end_label }}</div>
                            </div>
                            <div class="contract-meta-item">
                                <div class="contract-meta-label">Vencimiento</div>
                                <span class="badge" :class="expirationClass(selected.expiration_status)">{{ selected.expiration_label }}</span>
                                <div class="text-muted small mt-1">{{ selected.expiration_days_label }}</div>
                            </div>
                            <div class="contract-meta-item">
                                <div class="contract-meta-label">Visibilidad</div>
                                <div>{{ selected.visibility_label }}</div>
                            </div>
                            <div class="contract-meta-item">
                                <div class="contract-meta-label">Valor</div>
                                <div>{{ money(selected.contract_value, selected.currency_code) }}</div>
                            </div>
                            <div class="contract-meta-item">
                                <div class="contract-meta-label">Moneda</div>
                                <div>{{ selected.currency_code || '-' }}</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="font-weight-bold mb-1">Descripción</div>
                            <div class="contract-description text-muted">{{ displayText(selected.description, 'Sin descripción.') }}</div>
                        </div>
                        <div>
                            <div class="font-weight-bold mb-1">Notas</div>
                            <div class="contract-description text-muted">{{ displayText(selected.notes, 'Sin notas visibles.') }}</div>
                        </div>
                    </div>

                    <div v-show="tab === 'documents'">
                        <div v-if="selectedDocuments.length === 0" class="portal-empty">Sin documentos disponibles.</div>
                        <div v-for="document in selectedDocuments" :key="document.link_id" class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ document.title || document.original_name }}</strong>
                                    <div class="text-muted small">{{ document.relation_label }} · {{ document.original_name }}</div>
                                    <div class="text-muted small">{{ document.created_at || '-' }} · {{ fileSize(document.file_size) }}</div>
                                </div>
                                <a class="btn btn-outline-primary btn-sm" :href="document.download_url">
                                    Descargar
                                </a>
                            </div>
                        </div>
                    </div>

                    <div v-show="tab === 'events'">
                        <div v-if="selectedEvents.length === 0" class="portal-empty">Sin eventos visibles.</div>
                        <div v-for="event in selectedEvents" :key="event.id" class="border-left pl-3 mb-3">
                            <div class="font-weight-bold">{{ event.event_label }}</div>
                            <div class="text-muted small">{{ event.created_at || '-' }}</div>
                            <div>{{ event.message || 'Sin mensaje.' }}</div>
                            <div class="text-muted small" v-if="event.old_status || event.new_status">
                                {{ event.old_status_label || '-' }} &rarr; {{ event.new_status_label || '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    new Vue({
        el: '#app-supplier-contracts',
        data: function() {
            return {
                loading: false,
                error: '',
                tab: 'general',
                contracts: [],
                documents: [],
                events: [],
                selected: null
            };
        },
        computed: {
            selectedDocuments: function() {
                var id = this.selected ? Number(this.selected.id) : 0;
                return this.documents.filter(function(document) {
                    return Number(document.contract_id) === id;
                });
            },
            selectedEvents: function() {
                var id = this.selected ? Number(this.selected.id) : 0;
                return this.events.filter(function(event) {
                    return Number(event.contract_id) === id;
                });
            }
        },
        mounted: function() {
            this.load();
        },
        methods: {
            load: function() {
                var self = this;
                self.loading = true;
                self.error = '';

                fetch(<?php echo json_encode(Uri::create('proveedores/contracts_data')); ?>, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(function(response) { return response.json(); })
                    .then(function(json) {
                        if (!json.success) {
                            throw new Error(json.message || 'No se pudieron cargar los contratos.');
                        }

                        self.contracts = json.data && json.data.contracts ? json.data.contracts : [];
                        self.documents = json.data && json.data.documents ? json.data.documents : [];
                        self.events = json.data && json.data.events ? json.data.events : [];

                        if (!self.selected && self.contracts.length > 0) {
                            self.select(self.contracts[0]);
                        } else if (self.selected) {
                            var current = self.contracts.filter(function(contract) {
                                return Number(contract.id) === Number(self.selected.id);
                            })[0];
                            self.selected = current || (self.contracts[0] || null);
                        }
                    })
                    .catch(function(error) {
                        self.error = error.message || 'No se pudieron cargar los contratos.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            select: function(contract) {
                this.selected = contract;
                this.tab = 'general';
            },
            money: function(value, currency) {
                var amount = Number(value || 0);
                return (currency || 'MXN') + ' ' + amount.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            fileSize: function(bytes) {
                bytes = Number(bytes || 0);
                if (bytes < 1024) {
                    return bytes + ' B';
                }
                if (bytes < 1048576) {
                    return (bytes / 1024).toFixed(1) + ' KB';
                }
                return (bytes / 1048576).toFixed(1) + ' MB';
            },
            statusClass: function(status) {
                var map = {
                    draft: 'badge-secondary',
                    pending_signature: 'badge-info',
                    active: 'badge-success',
                    renewal_pending: 'badge-warning',
                    expired: 'badge-danger',
                    terminated: 'badge-dark',
                    cancelled: 'badge-danger',
                    archived: 'badge-secondary'
                };
                return map[status] || 'badge-secondary';
            },
            expirationClass: function(status) {
                return 'badge-expiration-' + (status || 'active');
            },
            displayText: function(value, fallback) {
                value = String(value || '').trim();
                return value || (fallback || '');
            }
        }
    });
});
</script>
