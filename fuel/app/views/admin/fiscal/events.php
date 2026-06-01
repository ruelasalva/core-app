<?php
    $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<style>
    [v-cloak] { display: none; }
    .fiscal-event-row { cursor: pointer; }
    .fiscal-event-row.table-active td { background-color: #e9f3ff; }
    .fiscal-event-detail pre {
        white-space: pre-wrap;
        margin-bottom: 0;
        font-size: .82rem;
    }
</style>

<div id="app-fiscal-events" v-cloak>
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1"><?php echo e($title); ?></h4>
            <div class="text-muted">
                RFC {{ events.rfc || 'No configurado' }} &middot;
                Periodo {{ events.period }} &middot;
                Fuente {{ events.rfc_source_label || 'No configurado' }}
            </div>
        </div>
        <div class="col-md-4">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/events'); ?>" class="form-inline justify-content-md-end">
                <input type="text" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($rfc); ?>" readonly>
                <input type="month" name="period" class="form-control form-control-sm mr-2 mb-2" value="<?php echo e($period); ?>">
                <button type="submit" class="btn btn-sm btn-primary mb-2"><i class="bi bi-search"></i> Consultar</button>
            </form>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Filtros de bitacora</h3>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo Uri::create('admin/fiscal/events'); ?>">
                <input type="hidden" name="period" value="<?php echo e($period); ?>">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label>Tipo de evento</label>
                        <select name="event_type" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="ledger_build" <?php echo \Arr::get($filters, 'event_type') === 'ledger_build' ? 'selected' : ''; ?>>Construcci&oacute;n de libro fiscal</option>
                            <option value="ledger_validation" <?php echo \Arr::get($filters, 'event_type') === 'ledger_validation' ? 'selected' : ''; ?>>Validaci&oacute;n de libro fiscal</option>
                            <option value="draft_generation" <?php echo \Arr::get($filters, 'event_type') === 'draft_generation' ? 'selected' : ''; ?>>Generaci&oacute;n de borrador fiscal</option>
                            <option value="draft_cancellation" <?php echo \Arr::get($filters, 'event_type') === 'draft_cancellation' ? 'selected' : ''; ?>>Cancelaci&oacute;n de borrador fiscal</option>
                            <option value="period_lock" <?php echo \Arr::get($filters, 'event_type') === 'period_lock' ? 'selected' : ''; ?>>Bloqueo de periodo fiscal</option>
                            <option value="period_open" <?php echo \Arr::get($filters, 'event_type') === 'period_open' ? 'selected' : ''; ?>>Apertura de periodo fiscal</option>
                            <option value="period_close" <?php echo \Arr::get($filters, 'event_type') === 'period_close' ? 'selected' : ''; ?>>Cierre de periodo fiscal</option>
                            <option value="fiscal_accounts_repair" <?php echo \Arr::get($filters, 'event_type') === 'fiscal_accounts_repair' ? 'selected' : ''; ?>>Reparaci&oacute;n de cuentas fiscales</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Estado</label>
                        <select name="event_status" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="success" <?php echo \Arr::get($filters, 'event_status') === 'success' ? 'selected' : ''; ?>>Correcto</option>
                            <option value="warning" <?php echo \Arr::get($filters, 'event_status') === 'warning' ? 'selected' : ''; ?>>Advertencia</option>
                            <option value="error" <?php echo \Arr::get($filters, 'event_status') === 'error' ? 'selected' : ''; ?>>Error</option>
                            <option value="skipped" <?php echo \Arr::get($filters, 'event_status') === 'skipped' ? 'selected' : ''; ?>>Omitido</option>
                        </select>
                    </div>
                    <div class="form-group col-md-5 text-md-right">
                        <a href="<?php echo Uri::create('admin/fiscal/events', [], ['period' => $period]); ?>" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar filtros
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-funnel"></i> Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
    </div>

    <div v-if="events.warnings.length > 0" class="alert alert-warning">
        <div v-for="warning in events.warnings" :key="warning">{{ warning }}</div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Eventos fiscales</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>RFC</th>
                                    <th>Periodo</th>
                                    <th>Evento</th>
                                    <th>Estado</th>
                                    <th>Resumen</th>
                                    <th>Usuario/Sistema</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in events.items" :key="item.id" class="fiscal-event-row" :class="{ 'table-active': selected && selected.id === item.id }" @click="selectRow(item)">
                                    <td>{{ item.executed_at_label }}</td>
                                    <td>{{ item.taxpayer_rfc || '-' }}</td>
                                    <td>{{ item.fiscal_period || '-' }}</td>
                                    <td>{{ item.event_type_label }}</td>
                                    <td><span class="badge" :class="badgeClass(item.event_status)">{{ item.event_status_label }}</span></td>
                                    <td>{{ item.summary || '-' }}</td>
                                    <td>{{ item.executed_by_label }}</td>
                                </tr>
                                <tr v-if="events.items.length === 0">
                                    <td colspan="7" class="text-center text-muted">Sin eventos fiscales para los filtros seleccionados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    La bitacora fiscal es de solo lectura y registra eventos operativos del motor fiscal cuando la migracion 067 esta aplicada.
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-outline card-info fiscal-event-detail">
                <div class="card-header">
                    <h3 class="card-title mb-0">Detalle del evento</h3>
                </div>
                <div class="card-body">
                    <div v-if="selected">
                        <dl class="row small">
                            <dt class="col-5">Evento</dt>
                            <dd class="col-7">{{ selected.event_type_label }}</dd>
                            <dt class="col-5">Estado</dt>
                            <dd class="col-7"><span class="badge" :class="badgeClass(selected.event_status)">{{ selected.event_status_label }}</span></dd>
                            <dt class="col-5">Fecha</dt>
                            <dd class="col-7">{{ selected.executed_at_label }}</dd>
                            <dt class="col-5">RFC</dt>
                            <dd class="col-7">{{ selected.taxpayer_rfc || '-' }}</dd>
                            <dt class="col-5">Periodo</dt>
                            <dd class="col-7">{{ selected.fiscal_period || '-' }}</dd>
                            <dt class="col-5">Origen</dt>
                            <dd class="col-7">{{ selected.source_module }} / {{ selected.source_entity_type || '-' }} #{{ selected.source_entity_id }}</dd>
                            <dt class="col-5">Usuario</dt>
                            <dd class="col-7">{{ selected.executed_by_label }}</dd>
                        </dl>

                        <h6>Resumen</h6>
                        <p>{{ selected.summary || '-' }}</p>

                        <h6>Detalle</h6>
                        <pre class="bg-light border rounded p-2">{{ detailText(selected.details) }}</pre>
                    </div>
                    <div v-if="!selected" class="text-muted">
                        Selecciona un evento para revisar su detalle.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var root = document.getElementById('app-fiscal-events');
    if (!root || typeof Vue === 'undefined') {
        return;
    }

    var eventsDataUrl = <?php echo json_encode(Uri::create('admin/fiscal/events_data'), $json_flags); ?>;
    var initialRfc = <?php echo json_encode($rfc, $json_flags); ?>;
    var initialPeriod = <?php echo json_encode($period, $json_flags); ?>;
    var initialFilters = <?php echo json_encode((array) $filters, $json_flags); ?>;
    var initialEvents = <?php echo json_encode((array) $events, $json_flags); ?>;

    new Vue({
        el: '#app-fiscal-events',
        data: {
            loading: false,
            errorMessage: '',
            events: initialEvents,
            selected: initialEvents.items && initialEvents.items.length ? initialEvents.items[0] : null
        },
        mounted: function() {
            this.loadEvents();
        },
        methods: {
            loadEvents: function() {
                var self = this;
                self.loading = true;
                self.errorMessage = '';

                var params = new URLSearchParams();
                params.set('rfc', initialRfc || '');
                params.set('period', initialPeriod || '');
                params.set('event_type', initialFilters.event_type || '');
                params.set('event_status', initialFilters.event_status || '');

                fetch(eventsDataUrl + '?' + params.toString(), {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function(data) {
                        if (!data || data.error || data.success === false) {
                            self.errorMessage = data && data.error ? data.error : 'No se pudo cargar la bitacora fiscal.';
                            return;
                        }

                        self.events = data.events || self.events;
                        self.selected = self.events.items && self.events.items.length ? self.events.items[0] : null;
                    })
                    .catch(function() {
                        self.errorMessage = 'No se pudo conectar con el endpoint de bitacora fiscal.';
                    })
                    .then(function() {
                        self.loading = false;
                    });
            },
            selectRow: function(item) {
                this.selected = item;
            },
            badgeClass: function(status) {
                if (status === 'success') return 'badge-success';
                if (status === 'warning') return 'badge-warning';
                if (status === 'error') return 'badge-danger';
                if (status === 'skipped') return 'badge-secondary';
                return 'badge-light';
            },
            detailText: function(details) {
                if (!details) {
                    return 'Sin detalle disponible.';
                }
                return JSON.stringify(details, null, 2);
            }
        }
    });
});
</script>
