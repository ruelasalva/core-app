        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Detalle del contrato</h3>
                </div>
                <div class="card-body">
                    <div v-if="!selected" class="text-muted">Selecciona un contrato para ver documentos, relaciones y eventos.</div>
                    <div v-if="selected">
                        <h5 class="mb-1">{{ selected.contract_number }} - {{ selected.title }}</h5>
                        <div class="text-muted mb-3">{{ selected.party_name || 'Sin tercero' }} / {{ selected.contract_type_label }}</div>

                        <ul class="nav nav-tabs mb-3">
                            <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'general'}" @click.prevent="tab = 'general'">General</a></li>
                            <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'documents'}" @click.prevent="tab = 'documents'">Documentos</a></li>
                            <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'relations'}" @click.prevent="tab = 'relations'">Relaciones</a></li>
                            <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'events'}" @click.prevent="tab = 'events'">Eventos</a></li>
                        </ul>

                        <div v-show="tab === 'general'">
                            <table class="table table-sm table-bordered">
                                <tr><th>Inicio</th><td>{{ selected.start_date || '-' }}</td></tr>
                                <tr><th>Fin</th><td>{{ selected.end_date || '-' }} / <span class="badge" :class="expirationClass(selected.expiration_status)">{{ selected.expiration_label }}</span> <span class="text-muted">{{ selected.expiration_days_label }}</span></td></tr>
                                <tr><th>Valor</th><td>{{ money(selected.contract_value) }} {{ selected.currency_code }}</td></tr>
                                <tr><th>Renovacion</th><td>{{ selected.renewal_type }}</td></tr>
                                <tr><th>Facturacion</th><td>{{ selected.billing_type }}</td></tr>
                                <tr><th>SLA</th><td>Respuesta {{ selected.response_hours || 0 }} h / Resolucion {{ selected.resolution_hours || 0 }} h</td></tr>
                            </table>
                            <p class="mb-1"><strong>Descripcion</strong></p>
                            <div v-if="selected.description" class="border rounded bg-light p-2 contract-rich-preview" v-html="selected.description"></div>
                            <p v-else class="text-muted">Sin descripcion.</p>
                            <p class="mb-1"><strong>Notas</strong></p>
                            <div v-if="selected.notes" class="border rounded bg-light p-2 contract-rich-preview" v-html="selected.notes"></div>
                            <p v-else class="text-muted">Sin notas.</p>
                        </div>

                        <?php echo View::forge('admin/contracts/_documents'); ?>

                        <?php echo View::forge('admin/contracts/_relations'); ?>

                        <?php echo View::forge('admin/contracts/_events'); ?>
                    </div>
                </div>
            </div>
        </div>
