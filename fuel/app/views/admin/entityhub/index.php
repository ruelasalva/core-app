<section class="content-header">
    <h1>Hub de Entidades <small>Diagn&oacute;stico de relaciones</small></h1>
</section>

<section class="content" id="entityhub-app">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Consulta diagn&oacute;stica</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <label>Tipo de entidad</label>
                    <select class="form-control" v-model="filters.entity_type">
                        <option value="customer">Cliente</option>
                        <option value="supplier">Proveedor</option>
                        <option value="contract">Contrato</option>
                        <option value="invoice">Factura</option>
                        <option value="payment">Pago</option>
                        <option value="quotation">Cotizaci&oacute;n</option>
                        <option value="order">Pedido</option>
                        <option value="ticket">Ticket</option>
                        <option value="communication">Comunicaci&oacute;n</option>
                        <option value="document">Documento</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>ID</label>
                    <input type="number" class="form-control" v-model.number="filters.entity_id" min="1">
                </div>
                <div class="col-md-4">
                    <label>Categor&iacute;as</label>
                    <input type="text" class="form-control" v-model="filters.categories" placeholder="documents,communications,contracts">
                    <small class="text-muted">Opcional. Separar por coma.</small>
                </div>
                <div class="col-md-2">
                    <label>L&iacute;mite</label>
                    <input type="number" class="form-control" v-model.number="filters.limit" min="1" max="500">
                </div>
                <div class="col-md-1">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary btn-block" type="button" v-on:click="loadRelationships" v-bind:disabled="loading">
                        <span v-if="loading">...</span>
                        <span v-else>Cargar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-danger" v-if="error">
        {{ error }}
    </div>

    <div class="box box-info" v-if="entity">
        <div class="box-header with-border">
            <h3 class="box-title">{{ entity.entity_code }} - {{ entity.entity_name }}</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-sm-3">
                    <strong>M&oacute;dulo</strong><br>
                    {{ entity.entity_module }}
                </div>
                <div class="col-sm-3">
                    <strong>Estado</strong><br>
                    {{ entity.status }}
                </div>
                <div class="col-sm-3">
                    <strong>Ocultas por seguridad</strong><br>
                    {{ hidden_count }}
                </div>
                <div class="col-sm-3">
                    <strong>Total de relaciones</strong><br>
                    {{ relationships.length }}
                </div>
            </div>
        </div>
    </div>

    <div class="box box-default" v-if="Object.keys(counts).length">
        <div class="box-header with-border">
            <h3 class="box-title">Conteos por categor&iacute;a</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-condensed table-striped">
                <thead>
                    <tr>
                        <th>Categor&iacute;a</th>
                        <th>Visibles</th>
                        <th>Ocultas</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, category) in counts" v-bind:key="category">
                        <td>{{ category }}</td>
                        <td>{{ row.visible }}</td>
                        <td>{{ row.hidden }}</td>
                        <td>{{ row.total }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">Relaciones normalizadas</h3>
        </div>
        <div class="box-body table-responsive">
            <div class="text-muted" v-if="!loading && relationships.length === 0">
                No hay relaciones para mostrar.
            </div>
            <table class="table table-hover table-condensed" v-if="relationships.length">
                <thead>
                    <tr>
                        <th>Categor&iacute;a</th>
                        <th>Relaci&oacute;n</th>
                        <th>Destino</th>
                        <th>Direcci&oacute;n</th>
                        <th>Confianza</th>
                        <th>Visible</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(relationship, index) in relationships" v-bind:key="index">
                        <td>{{ relationship.category }}</td>
                        <td>{{ relationship.relation_label }}</td>
                        <td>
                            <span v-if="relationship.visible">
                                {{ relationship.target_entity_type }} #{{ relationship.target_entity_id }}
                            </span>
                            <span v-else class="text-muted">Restringido</span>
                        </td>
                        <td>{{ relationship.direction }}</td>
                        <td>{{ relationship.confidence }}</td>
                        <td>
                            <span class="label label-success" v-if="relationship.visible">Visible</span>
                            <span class="label label-default" v-else>{{ relationship.reason_if_hidden }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="content" id="entityhub-timeline-app">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">L&iacute;nea de tiempo diagn&oacute;stica</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-2">
                    <label>Tipo</label>
                    <select class="form-control" v-model="filters.entity_type">
                        <option value="">Selecciona tipo</option>
                        <option value="customer">Cliente</option>
                        <option value="supplier">Proveedor</option>
                        <option value="contract">Contrato</option>
                        <option value="invoice">Factura</option>
                        <option value="payment">Pago</option>
                        <option value="quotation">Cotizaci&oacute;n</option>
                        <option value="order">Pedido</option>
                        <option value="ticket">Ticket</option>
                        <option value="communication">Comunicaci&oacute;n</option>
                        <option value="document">Documento</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label>ID</label>
                    <input type="number" class="form-control" v-model.number="filters.entity_id" min="1" placeholder="8">
                </div>
                <div class="col-md-3">
                    <label>Categor&iacute;as</label>
                    <input type="text" class="form-control" v-model="filters.categories" placeholder="crm,sales,billing,helpdesk">
                    <small class="text-muted">Ejemplos de tipo: customer, supplier, contract, invoice, ticket.</small>
                </div>
                <div class="col-md-2">
                    <label>Desde</label>
                    <input type="date" class="form-control" v-model="filters.date_from">
                </div>
                <div class="col-md-2">
                    <label>Hasta</label>
                    <input type="date" class="form-control" v-model="filters.date_to">
                </div>
                <div class="col-md-1">
                    <label>L&iacute;mite</label>
                    <input type="number" class="form-control" v-model.number="filters.limit" min="1" max="500">
                </div>
                <div class="col-md-1">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary btn-block" type="button" v-on:click="loadTimeline" v-bind:disabled="loading">
                        <span v-if="loading">...</span>
                        <span v-else>Cargar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-danger" v-if="error">
        {{ error }}
    </div>

    <div class="box box-info" v-if="entity">
        <div class="box-header with-border">
            <h3 class="box-title">{{ entity.entity_code }} - {{ entity.entity_name }}</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-sm-4">
                    <strong>M&oacute;dulo</strong><br>{{ entity.entity_module }}
                </div>
                <div class="col-sm-4">
                    <strong>Entradas ocultas</strong><br>{{ hidden_count }}
                </div>
                <div class="col-sm-4">
                    <strong>Total l&iacute;nea de tiempo</strong><br>{{ timeline.length }}
                </div>
            </div>
        </div>
    </div>

    <div class="box box-default" v-if="Object.keys(counts).length">
        <div class="box-header with-border">
            <h3 class="box-title">Conteos de l&iacute;nea de tiempo</h3>
        </div>
        <div class="box-body">
            <span class="label label-default" style="margin-right: 6px;" v-for="(total, category) in counts" v-bind:key="category">
                {{ category }}: {{ total }}
            </span>
        </div>
    </div>

    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">Entradas cronol&oacute;gicas</h3>
        </div>
        <div class="box-body">
            <div class="text-muted" v-if="!loading && timeline.length === 0">
                No hay eventos para mostrar.
            </div>
            <ul class="timeline" v-if="timeline.length">
                <li v-for="(entry, index) in timeline" v-bind:key="index">
                    <i class="fa" v-bind:class="entry.icon || 'fa-circle'" v-bind:style="{ backgroundColor: entry.color || '#6c757d' }"></i>
                    <div class="timeline-item">
                        <span class="time"><i class="fa fa-clock-o"></i> {{ entry.event_date }}</span>
                        <h3 class="timeline-header">{{ entry.event_label }} - {{ entry.title }}</h3>
                        <div class="timeline-body">
                            <div>{{ entry.description }}</div>
                            <small class="text-muted">
                                {{ entry.source_module }} / {{ entry.source_entity_type }} #{{ entry.source_entity_id }}
                            </small>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new Vue({
        el: '#entityhub-app',
        data: {
            loading: false,
            error: '',
            filters: {
                entity_type: 'customer',
                entity_id: 0,
                categories: '',
                limit: 100
            },
            entity: null,
            relationships: [],
            counts: {},
            hidden_count: 0
        },
        methods: {
            loadRelationships: function () {
                var self = this;
                self.error = '';
                if (!self.filters.entity_type || !self.filters.entity_id) {
                    self.error = 'Captura tipo e ID de entidad.';
                    return;
                }

                var params = new URLSearchParams();
                params.set('entity_type', self.filters.entity_type);
                params.set('entity_id', self.filters.entity_id);
                params.set('limit', self.filters.limit || 100);
                if (self.filters.categories) {
                    params.set('categories', self.filters.categories);
                }

                self.loading = true;
                window.CoreApiClient.get('<?php echo \Uri::create('admin/entityhub/relationship_engine'); ?>?' + params.toString())
                    .then(function (result) {
                        if (!result.ok) {
                            self.error = result.message || 'No fue posible consultar relaciones.';
                            return;
                        }
                        var data = result.payload && result.payload.data ? result.payload.data : {};
                        self.entity = data.entity || null;
                        self.relationships = data.relationships || [];
                        self.counts = data.counts || {};
                        self.hidden_count = data.hidden_count || 0;
                    })
                    .catch(function () {
                        self.error = 'Error inesperado al consultar relaciones.';
                    })
                    .finally(function () {
                        self.loading = false;
                    });
            }
        }
    });

    new Vue({
        el: '#entityhub-timeline-app',
        data: {
            loading: false,
            error: '',
            filters: {
                entity_type: '',
                entity_id: null,
                categories: '',
                limit: 100,
                date_from: '',
                date_to: ''
            },
            entity: null,
            timeline: [],
            counts: {},
            hidden_count: 0
        },
        methods: {
            loadTimeline: function () {
                var self = this;
                self.error = '';
                var entityType = (self.filters.entity_type || '').trim();
                var entityId = parseInt(self.filters.entity_id, 10);
                if (!entityType || !entityId || entityId < 1) {
                    self.error = 'Captura tipo de entidad e ID antes de consultar la l&iacute;nea de tiempo.';
                    return;
                }

                var params = new URLSearchParams();
                params.set('entity_type', entityType);
                params.set('entity_id', entityId);
                params.set('limit', self.filters.limit || 100);
                if (self.filters.categories) {
                    params.set('categories', self.filters.categories);
                }
                if (self.filters.date_from) {
                    params.set('date_from', self.filters.date_from);
                }
                if (self.filters.date_to) {
                    params.set('date_to', self.filters.date_to);
                }

                self.loading = true;
                window.CoreApiClient.get('<?php echo \Uri::create('admin/entityhub/timeline'); ?>?' + params.toString())
                    .then(function (result) {
                        if (!result.ok) {
                            self.error = result.message || 'No fue posible consultar la l&iacute;nea de tiempo.';
                            return;
                        }
                        var data = result.payload && result.payload.data ? result.payload.data : {};
                        self.entity = data.entity || null;
                        self.timeline = data.timeline || [];
                        self.counts = data.counts || {};
                        self.hidden_count = data.hidden_count || 0;
                    })
                    .catch(function () {
                        self.error = 'Error inesperado al consultar la l&iacute;nea de tiempo.';
                    })
                    .finally(function () {
                        self.loading = false;
                    });
            }
        }
    });
});
</script>
