<div id="app-crm">
    <div class="row">
        <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ stats.opportunities || 0 }}</h3><p>Oportunidades</p></div><div class="icon"><i class="bi bi-graph-up-arrow"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-secondary"><div class="inner"><h3>{{ stats.prospects || 0 }}</h3><p>Prospectos</p></div><div class="icon"><i class="bi bi-building-add"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ stats.open_activities || 0 }}</h3><p>Actividades abiertas</p></div><div class="icon"><i class="bi bi-list-check"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ stats.customer_tickets || 0 }}</h3><p>Tickets clientes</p></div><div class="icon"><i class="bi bi-life-preserver"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ stats.surveys || 0 }}</h3><p>Encuestas</p></div><div class="icon"><i class="bi bi-clipboard2-check"></i></div></div></div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="card card-primary card-outline">
        <div class="card-header p-2">
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'opportunities'}" @click.prevent="tab = 'opportunities'">Oportunidades</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'prospects'}" @click.prevent="tab = 'prospects'">Prospectos DENUE</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'activities'}" @click.prevent="tab = 'activities'">Actividades</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'tickets'}" @click.prevent="tab = 'tickets'">Tickets clientes</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'surveys'}" @click.prevent="tab = 'surveys'">Encuestas</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="{active: tab === 'cut'}" @click.prevent="tab = 'cut'">Calculadora de corte</a></li>
            </ul>
        </div>
        <div class="card-body">
            <div v-show="tab === 'opportunities'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Pipeline comercial</h3>
                    <button class="btn btn-primary btn-sm" @click="openOpportunity({})"><i class="bi bi-plus-lg"></i> Oportunidad</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Folio</th><th>Cliente / prospecto</th><th>Titulo</th><th>Etapa</th><th>Monto</th><th>Prob.</th><th>Proxima accion</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="item in opportunities" :key="item.id">
                                <td><strong>{{ item.folio }}</strong><div class="text-muted small">{{ item.source }}</div></td>
                                <td>{{ item.party_name || '-' }}</td>
                                <td>{{ item.title }}<div class="text-muted small">{{ item.description }}</div></td>
                                <td><span class="badge" :class="stageClass(item.stage)">{{ stageLabel(item.stage) }}</span></td>
                                <td>{{ money(item.estimated_amount) }}</td>
                                <td>{{ item.probability }}%</td>
                                <td>{{ item.next_action_at_label || '-' }}</td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openOpportunity(item)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="opportunities.length === 0"><td colspan="8" class="text-center text-muted">Sin oportunidades registradas.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'prospects'">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card card-secondary card-outline">
                            <div class="card-header"><h3 class="card-title h6 mb-0">Buscar en DENUE</h3></div>
                            <div class="card-body">
                                <div v-if="!options.denue_connection_ready" class="alert alert-warning py-2">Activa la conexion INEGI DENUE API en Integraciones y captura el token.</div>
                                <label>Palabra / giro</label>
                                <input class="form-control" v-model="denueForm.keyword" placeholder="Ej. papeleria, ferreteria, toner">
                                <div class="row">
                                    <div class="col-4 mt-2"><label>Estado</label><input class="form-control" v-model="denueForm.state_code" placeholder="14"></div>
                                    <div class="col-4 mt-2"><label>Latitud</label><input class="form-control" v-model="denueForm.latitude"></div>
                                    <div class="col-4 mt-2"><label>Longitud</label><input class="form-control" v-model="denueForm.longitude"></div>
                                </div>
                                <label class="mt-2">Radio metros</label>
                                <input type="number" class="form-control" v-model.number="denueForm.radius">
                                <button class="btn btn-primary btn-block mt-3" @click="searchDenue" :disabled="denueLoading">
                                    <i class="bi bi-search"></i> {{ denueLoading ? 'Buscando...' : 'Buscar' }}
                                </button>
                                <button class="btn btn-outline-success btn-block mt-2" @click="importDenue" :disabled="selectedDenue.length === 0">
                                    <i class="bi bi-download"></i> Importar seleccionados
                                </button>
                            </div>
                        </div>
                        <div class="card card-light card-outline">
                            <div class="card-header"><h3 class="card-title h6 mb-0">Importaciones recientes</h3></div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <tr v-for="item in prospectImports" :key="item.id">
                                            <td><strong>{{ item.folio }}</strong><div class="text-muted small">{{ dateLabel(item.created_at) }}</div></td>
                                            <td>{{ item.imported_count }}/{{ item.results_count }}</td>
                                        </tr>
                                        <tr v-if="prospectImports.length === 0"><td class="text-muted">Sin importaciones.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card card-info card-outline" v-if="denueResults.length">
                            <div class="card-header"><h3 class="card-title h6 mb-0">Resultados DENUE</h3></div>
                            <div class="card-body table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead><tr><th></th><th>Negocio</th><th>Giro</th><th>Ubicacion</th><th>Contacto</th></tr></thead>
                                    <tbody>
                                        <tr v-for="item in denueResults" :key="item.external_id || item.external_clee">
                                            <td><input type="checkbox" :value="item" v-model="selectedDenue"></td>
                                            <td><strong>{{ item.name }}</strong><div class="text-muted small">{{ item.external_id }}</div></td>
                                            <td>{{ item.activity }}<div class="text-muted small">{{ item.size_range }}</div></td>
                                            <td>{{ item.full_address }}</td>
                                            <td>{{ item.phone || '-' }}<div class="text-muted small">{{ item.website || item.email || '' }}</div></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="h6 mb-0">Prospectos importados</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead><tr><th>Prospecto</th><th>Giro</th><th>Ubicacion</th><th>Contacto</th><th>Responsable</th><th>Estado</th><th></th></tr></thead>
                                <tbody>
                                    <tr v-for="item in prospects" :key="item.id">
                                        <td><strong>{{ item.name }}</strong><div class="text-muted small">{{ item.source_name }} {{ item.external_id }}</div></td>
                                        <td>{{ item.activity || '-' }}</td>
                                        <td>{{ item.municipality }}, {{ item.state }}<div class="text-muted small">{{ item.full_address }}</div></td>
                                        <td>{{ item.phone || '-' }}<div class="text-muted small">{{ item.website || item.email || '' }}</div></td>
                                        <td>{{ item.owner_name || '-' }}<div class="text-muted small">{{ item.seller_name || '' }}</div></td>
                                        <td><span class="badge" :class="prospectStatusClass(item.status)">{{ prospectStatusLabel(item.status) }}</span></td>
                                        <td>
                                            <button class="btn btn-xs btn-outline-primary" @click="openProspect(item)"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-xs btn-outline-success" @click="convertProspect(item)" :disabled="item.converted_party_id > 0">Cliente</button>
                                        </td>
                                    </tr>
                                    <tr v-if="prospects.length === 0"><td colspan="7" class="text-center text-muted">Sin prospectos importados.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div v-show="tab === 'activities'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 mb-0">Seguimiento comercial</h3>
                    <button class="btn btn-primary btn-sm" @click="openActivity({})"><i class="bi bi-plus-lg"></i> Actividad</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Asunto</th><th>Cliente</th><th>Tipo</th><th>Estado</th><th>Prioridad</th><th>Responsable</th><th>Fecha</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="item in activities" :key="item.id">
                                <td>{{ item.subject }}<div class="text-muted small"><span v-if="item.opportunity_folio">{{ item.opportunity_folio }}</span> <span v-if="item.ticket_folio">/ {{ item.ticket_folio }}</span></div></td>
                                <td>{{ item.party_name || '-' }}</td>
                                <td>{{ activityTypeLabel(item.activity_type) }}</td>
                                <td><span class="badge" :class="activityStatusClass(item.status)">{{ activityStatusLabel(item.status) }}</span></td>
                                <td>{{ priorityLabel(item.priority) }}</td>
                                <td>{{ item.assigned_name || '-' }}</td>
                                <td>{{ item.due_at_label || '-' }}</td>
                                <td><button class="btn btn-xs btn-outline-primary" @click="openActivity(item)"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                            <tr v-if="activities.length === 0"><td colspan="8" class="text-center text-muted">Sin actividades.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'tickets'">
                <div class="alert alert-light border">
                    Los tickets se atienden en Helpdesk; aqui se muestran como contexto del cliente para no perder la lectura comercial.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Folio</th><th>Cliente</th><th>Asunto</th><th>Estado</th><th>Prioridad</th><th>Ultimo movimiento</th></tr></thead>
                        <tbody>
                            <tr v-for="ticket in customerTickets" :key="ticket.id">
                                <td><strong>{{ ticket.folio }}</strong></td>
                                <td>{{ ticket.party_name || '-' }}</td>
                                <td>{{ ticket.subject }}</td>
                                <td><span class="badge" :class="'badge-' + (ticket.status_color || 'secondary')">{{ ticket.status_name || '-' }}</span></td>
                                <td>{{ priorityLabel(ticket.priority) }}</td>
                                <td>{{ dateLabel(ticket.last_message_at || ticket.created_at) }}</td>
                            </tr>
                            <tr v-if="customerTickets.length === 0"><td colspan="6" class="text-center text-muted">Sin tickets de clientes.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-show="tab === 'surveys'">
                <div class="row">
                    <div class="col-md-5">
                        <div class="card card-secondary card-outline">
                            <div class="card-header"><h3 class="card-title h6 mb-0">Registrar respuesta</h3></div>
                            <div class="card-body">
                                <label>Encuesta</label>
                                <select class="form-control" v-model="surveyForm.survey_id"><option value="0">Selecciona</option><option v-for="s in options.surveys" :value="s.value">{{ s.label }}</option></select>
                                <label class="mt-2">Cliente</label>
                                <select class="form-control" v-model="surveyForm.party_id"><option value="0">Sin cliente</option><option v-for="p in options.parties" :value="p.value">{{ p.label }}</option></select>
                                <label class="mt-2">Calificacion</label>
                                <input type="number" class="form-control" min="0" max="10" step="0.1" v-model="surveyForm.score">
                                <label class="mt-2">Comentarios</label>
                                <textarea class="form-control" rows="4" v-model="surveyForm.comments"></textarea>
                                <button class="btn btn-primary mt-3" @click="saveSurveyResponse"><i class="bi bi-save"></i> Guardar respuesta</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead><tr><th>Encuesta</th><th>Cliente</th><th>Origen</th><th>Score</th><th>Comentarios</th></tr></thead>
                                <tbody>
                                    <tr v-for="item in surveyResponses" :key="item.id">
                                        <td>{{ item.survey_name }}</td>
                                        <td>{{ item.party_name || '-' }}</td>
                                        <td>{{ item.portal_code }}</td>
                                        <td>{{ item.score }}</td>
                                        <td>{{ item.comments }}</td>
                                    </tr>
                                    <tr v-if="surveyResponses.length === 0"><td colspan="5" class="text-center text-muted">Sin respuestas.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div v-show="tab === 'cut'">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card card-secondary card-outline">
                            <div class="card-header"><h3 class="card-title h6 mb-0">Calculo de corte</h3></div>
                            <div class="card-body">
                                <label>Cliente</label>
                                <select class="form-control" v-model="cutForm.party_id"><option value="0">Sin cliente</option><option v-for="p in options.parties" :value="p.value">{{ p.label }}</option></select>
                                <label class="mt-2">Material</label><input class="form-control" v-model="cutForm.material">
                                <div class="row">
                                    <div class="col-6 mt-2"><label>Lamina ancho</label><input type="number" step="0.01" class="form-control" v-model="cutForm.sheet_width"></div>
                                    <div class="col-6 mt-2"><label>Lamina alto</label><input type="number" step="0.01" class="form-control" v-model="cutForm.sheet_height"></div>
                                    <div class="col-6 mt-2"><label>Pieza ancho</label><input type="number" step="0.01" class="form-control" v-model="cutForm.piece_width"></div>
                                    <div class="col-6 mt-2"><label>Pieza alto</label><input type="number" step="0.01" class="form-control" v-model="cutForm.piece_height"></div>
                                    <div class="col-6 mt-2"><label>Merma corte</label><input type="number" step="0.01" class="form-control" v-model="cutForm.kerf"></div>
                                </div>
                                <label class="mt-2">Notas</label><input class="form-control" v-model="cutForm.notes">
                                <button class="btn btn-primary mt-3" @click="saveCut"><i class="bi bi-calculator"></i> Calcular y guardar</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead><tr><th>Folio</th><th>Cliente</th><th>Material</th><th>Lamina</th><th>Pieza</th><th>Resultado</th><th>Merma</th></tr></thead>
                                <tbody>
                                    <tr v-for="item in cutCalculations" :key="item.id">
                                        <td><strong>{{ item.folio }}</strong></td>
                                        <td>{{ item.party_name || '-' }}</td>
                                        <td>{{ item.material }}</td>
                                        <td>{{ item.sheet_width }} x {{ item.sheet_height }}</td>
                                        <td>{{ item.piece_width }} x {{ item.piece_height }}</td>
                                        <td>{{ item.pieces_x }} x {{ item.pieces_y }} = <strong>{{ item.total_pieces }}</strong></td>
                                        <td>{{ item.waste_percent }}%</td>
                                    </tr>
                                    <tr v-if="cutCalculations.length === 0"><td colspan="7" class="text-center text-muted">Sin calculos guardados.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-crm-opportunity" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Oportunidad</h5><button class="close text-white" @click="hideModal('modal-crm-opportunity')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-6"><label>Cliente</label><select class="form-control" v-model="opportunityForm.party_id"><option value="0">Sin tercero</option><option v-for="p in options.parties" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-6"><label>Prospecto</label><select class="form-control" v-model="opportunityForm.prospect_id"><option value="0">Sin prospecto</option><option v-for="p in options.prospects" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-3"><label>Etapa</label><select class="form-control" v-model="opportunityForm.stage"><option value="new">Nueva</option><option value="qualified">Calificada</option><option value="quoted">Cotizada</option><option value="won">Ganada</option><option value="lost">Perdida</option></select></div>
                <div class="col-md-3"><label>Responsable</label><select class="form-control" v-model="opportunityForm.owner_user_id"><option value="0">Sin asignar</option><option v-for="u in options.users" :value="u.value">{{ u.label }}</option></select></div>
                <div class="col-md-12 mt-2"><label>Titulo</label><input class="form-control" v-model="opportunityForm.title"></div>
                <div class="col-md-4 mt-2"><label>Monto estimado</label><input type="number" step="0.01" class="form-control" v-model="opportunityForm.estimated_amount"></div>
                <div class="col-md-4 mt-2"><label>Probabilidad %</label><input type="number" min="0" max="100" class="form-control" v-model="opportunityForm.probability"></div>
                <div class="col-md-4 mt-2"><label>Proxima accion</label><input type="datetime-local" class="form-control" v-model="opportunityForm.next_action_at_input"></div>
                <div class="col-md-12 mt-2"><label>Descripcion</label><textarea class="form-control" rows="4" v-model="opportunityForm.description"></textarea></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-crm-opportunity')">Cerrar</button><button class="btn btn-primary" @click="saveOpportunity">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-crm-activity" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Actividad</h5><button class="close text-white" @click="hideModal('modal-crm-activity')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-6"><label>Cliente</label><select class="form-control" v-model="activityForm.party_id"><option value="0">Sin cliente</option><option v-for="p in options.parties" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-6"><label>Prospecto</label><select class="form-control" v-model="activityForm.prospect_id"><option value="0">Sin prospecto</option><option v-for="p in options.prospects" :value="p.value">{{ p.label }}</option></select></div>
                <div class="col-md-3"><label>Tipo</label><select class="form-control" v-model="activityForm.activity_type"><option value="call">Llamada</option><option value="visit">Visita</option><option value="email">Correo</option><option value="task">Tarea</option><option value="note">Nota</option><option value="survey">Encuesta</option><option value="cut">Corte</option></select></div>
                <div class="col-md-3"><label>Estado</label><select class="form-control" v-model="activityForm.status"><option value="open">Abierta</option><option value="scheduled">Programada</option><option value="done">Completada</option><option value="cancelled">Cancelada</option></select></div>
                <div class="col-md-12 mt-2"><label>Asunto</label><input class="form-control" v-model="activityForm.subject"></div>
                <div class="col-md-4 mt-2"><label>Oportunidad</label><select class="form-control" v-model="activityForm.opportunity_id"><option value="0">Sin oportunidad</option><option v-for="o in opportunities" :value="o.id">{{ o.folio }} - {{ o.title }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Responsable</label><select class="form-control" v-model="activityForm.assigned_user_id"><option value="0">Sin asignar</option><option v-for="u in options.users" :value="u.value">{{ u.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Fecha compromiso</label><input type="datetime-local" class="form-control" v-model="activityForm.due_at_input"></div>
                <div class="col-md-12 mt-2"><label>Detalle</label><textarea class="form-control" rows="4" v-model="activityForm.description"></textarea></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-crm-activity')">Cerrar</button><button class="btn btn-primary" @click="saveActivity">Guardar</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="modal-crm-prospect" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Prospecto</h5><button class="close text-white" @click="hideModal('modal-crm-prospect')"><span>&times;</span></button></div>
            <div class="modal-body"><div class="row">
                <div class="col-md-8">
                    <h5>{{ prospectForm.name }}</h5>
                    <p class="text-muted mb-2">{{ prospectForm.activity }}</p>
                    <p class="mb-1"><i class="bi bi-geo-alt"></i> {{ prospectForm.full_address }}</p>
                    <p class="mb-1"><i class="bi bi-telephone"></i> {{ prospectForm.phone || '-' }} <span class="ml-3"><i class="bi bi-globe"></i> {{ prospectForm.website || '-' }}</span></p>
                </div>
                <div class="col-md-4">
                    <label>Estado</label>
                    <select class="form-control" v-model="prospectForm.status">
                        <option value="new">Nuevo</option>
                        <option value="assigned">Asignado</option>
                        <option value="contacted">Contactado</option>
                        <option value="interested">Interesado</option>
                        <option value="not_interested">No interesado</option>
                        <option value="discarded">Descartado</option>
                    </select>
                    <label class="mt-2">Prioridad</label>
                    <select class="form-control" v-model="prospectForm.priority"><option value="low">Baja</option><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select>
                </div>
                <div class="col-md-4 mt-2"><label>Responsable</label><select class="form-control" v-model="prospectForm.owner_user_id"><option value="0">Sin asignar</option><option v-for="u in options.users" :value="u.value">{{ u.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Vendedor</label><select class="form-control" v-model="prospectForm.seller_id"><option value="0">Sin vendedor</option><option v-for="s in options.sellers" :value="s.value">{{ s.label }}</option></select></div>
                <div class="col-md-4 mt-2"><label>Proxima accion</label><input type="datetime-local" class="form-control" v-model="prospectForm.next_action_at_input"></div>
                <div class="col-md-12 mt-2"><label>Notas</label><textarea class="form-control" rows="4" v-model="prospectForm.notes"></textarea></div>
            </div></div>
            <div class="modal-footer"><button class="btn btn-secondary" @click="hideModal('modal-crm-prospect')">Cerrar</button><button class="btn btn-primary" @click="saveProspect">Guardar</button></div>
        </div></div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-crm',
        data: {
            tab: 'opportunities', error: '', opportunities: [], activities: [], prospects: [], prospectImports: [], customerTickets: [],
            surveys: [], surveyResponses: [], cutCalculations: [], options: { parties: [], prospects: [], users: [], surveys: [], sellers: [] },
            stats: {}, opportunityForm: {}, activityForm: {}, surveyForm: { survey_id: 0, party_id: 0, score: 10, comments: '' },
            prospectForm: {}, denueLoading: false, denueResults: [], selectedDenue: [],
            denueForm: { keyword: '', state_code: '14', latitude: '', longitude: '', radius: 500 },
            cutForm: { party_id: 0, material: '', sheet_width: 0, sheet_height: 0, piece_width: 0, piece_height: 0, kerf: 0, notes: '' }
        },
        mounted: function() { this.load(); },
        methods: {
            load: function() {
                fetch('<?php echo Uri::create('admin/crm/data'); ?>').then(r => r.json()).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.opportunities = data.opportunities || [];
                    this.activities = data.activities || [];
                    this.prospects = data.prospects || [];
                    this.prospectImports = data.prospect_imports || [];
                    this.customerTickets = data.customer_tickets || [];
                    this.surveys = data.surveys || [];
                    this.surveyResponses = data.survey_responses || [];
                    this.cutCalculations = data.cut_calculations || [];
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                });
            },
            openOpportunity: function(item) {
                this.opportunityForm = Object.assign({ id: 0, party_id: 0, prospect_id: 0, owner_user_id: 0, source: 'manual', stage: 'new', title: '', description: '', estimated_amount: 0, probability: 0, next_action_at_input: '', lost_reason: '', active: true }, item);
                this.showModal('modal-crm-opportunity');
            },
            openActivity: function(item) {
                this.activityForm = Object.assign({ id: 0, party_id: 0, prospect_id: 0, opportunity_id: 0, ticket_id: 0, activity_type: 'note', subject: '', description: '', status: 'open', priority: 'normal', assigned_user_id: 0, due_at_input: '', active: true }, item);
                this.showModal('modal-crm-activity');
            },
            openProspect: function(item) {
                this.prospectForm = Object.assign({ id: 0, owner_user_id: 0, seller_id: 0, status: 'new', priority: 'normal', next_action_at_input: '', notes: '' }, item);
                this.showModal('modal-crm-prospect');
            },
            saveOpportunity: function() { this.post('save_opportunity', this.opportunityForm, 'modal-crm-opportunity'); },
            saveActivity: function() { this.post('save_activity', this.activityForm, 'modal-crm-activity'); },
            saveProspect: function() { this.post('save_prospect', this.prospectForm, 'modal-crm-prospect'); },
            saveSurveyResponse: function() { this.post('save_survey_response', this.surveyForm, ''); },
            saveCut: function() { this.post('save_cut_calculation', this.cutForm, ''); },
            searchDenue: function() {
                this.error = '';
                this.denueLoading = true;
                fetch('<?php echo Uri::create('admin/crm/denue_search'); ?>', window.coreAppFetchOptions(this.denueForm)).then(window.coreAppJson).then(data => {
                    this.denueResults = data.results || [];
                    this.selectedDenue = [];
                    this.denueLoading = false;
                }).catch(err => { this.error = err && err.error ? err.error : 'No se pudo consultar DENUE.'; this.denueLoading = false; });
            },
            importDenue: function() {
                this.post('denue_import', { query: this.denueForm, items: this.selectedDenue }, '');
                this.selectedDenue = [];
            },
            convertProspect: function(item) {
                if (!confirm('Convertir este prospecto en cliente?')) return;
                this.post('convert_prospect', { id: item.id, create_opportunity: 1 }, '');
            },
            post: function(action, payload, modal) {
                this.error = '';
                fetch('<?php echo Uri::create('admin/crm'); ?>/' + action, window.coreAppFetchOptions(payload)).then(window.coreAppJson).then(data => {
                    if (data.error) { this.error = data.error; return; }
                    if (data.opportunities) this.opportunities = data.opportunities;
                    if (data.activities) this.activities = data.activities;
                    if (data.prospects) this.prospects = data.prospects;
                    if (data.prospect_imports) this.prospectImports = data.prospect_imports;
                    if (data.survey_responses) this.surveyResponses = data.survey_responses;
                    if (data.cut_calculations) this.cutCalculations = data.cut_calculations;
                    if (data.options) this.options = data.options;
                    if (data.stats) this.stats = data.stats;
                    if (modal) this.hideModal(modal);
                }).catch(err => { this.error = err && err.error ? err.error : 'No se pudo guardar la informacion.'; });
            },
            prospectStatusLabel: function(v) { return ({new:'Nuevo', assigned:'Asignado', contacted:'Contactado', interested:'Interesado', not_interested:'No interesado', converted:'Convertido', discarded:'Descartado'})[v] || v; },
            prospectStatusClass: function(v) { return ({new:'badge-secondary', assigned:'badge-info', contacted:'badge-primary', interested:'badge-success', not_interested:'badge-warning', converted:'badge-success', discarded:'badge-dark'})[v] || 'badge-secondary'; },
            stageLabel: function(v) { return ({new:'Nueva', qualified:'Calificada', quoted:'Cotizada', won:'Ganada', lost:'Perdida'})[v] || v; },
            stageClass: function(v) { return ({new:'badge-secondary', qualified:'badge-info', quoted:'badge-primary', won:'badge-success', lost:'badge-danger'})[v] || 'badge-secondary'; },
            activityTypeLabel: function(v) { return ({call:'Llamada', visit:'Visita', email:'Correo', task:'Tarea', note:'Nota', survey:'Encuesta', cut:'Corte'})[v] || v; },
            activityStatusLabel: function(v) { return ({open:'Abierta', scheduled:'Programada', done:'Completada', cancelled:'Cancelada'})[v] || v; },
            activityStatusClass: function(v) { return ({open:'badge-warning', scheduled:'badge-info', done:'badge-success', cancelled:'badge-secondary'})[v] || 'badge-secondary'; },
            priorityLabel: function(v) { return ({low:'Baja', normal:'Normal', high:'Alta', urgent:'Urgente'})[v] || v; },
            dateLabel: function(ts) { return Number(ts || 0) > 0 ? new Date(Number(ts) * 1000).toLocaleString('es-MX') : '-'; },
            money: function(v) { return Number(v || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' }); },
            showModal: function(id) { $('#' + id).modal('show'); },
            hideModal: function(id) { $('#' + id).modal('hide'); }
        }
    });
};
</script>
