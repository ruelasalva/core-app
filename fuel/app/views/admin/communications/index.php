<div id="app-communications">
    <div v-if="statusMessage" class="alert" :class="statusOk ? 'alert-success' : 'alert-danger'">
        <strong>{{ statusOk ? 'OK' : 'Atencion' }}:</strong> {{ statusMessage }}
        <ul v-if="statusErrors.length" class="mb-0 mt-2">
            <li v-for="(error, idx) in statusErrors" :key="'status-error-'+idx">{{ error }}</li>
        </ul>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#tab-summary" role="tab">
                        <i class="bi bi-speedometer2 mr-1"></i> Resumen
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-providers" role="tab">
                        <i class="bi bi-hdd-network mr-1"></i> Proveedores
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-accounts" role="tab">
                        <i class="bi bi-inbox mr-1"></i> Cuentas de correo
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-my-accounts" role="tab">
                        <i class="bi bi-person-lines-fill mr-1"></i> Mis cuentas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-my-mailbox" role="tab">
                        <i class="bi bi-envelope-open mr-1"></i> Mi bandeja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-conversations" role="tab">
                        <i class="bi bi-chat-left-text mr-1"></i> Conversaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-recipients" role="tab">
                        <i class="bi bi-people mr-1"></i> Destinatarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-templates" role="tab">
                        <i class="bi bi-file-earmark-text mr-1"></i> Templates
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-layouts" role="tab">
                        <i class="bi bi-window mr-1"></i> Layouts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-preview" role="tab">
                        <i class="bi bi-eye mr-1"></i> Preview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-test" role="tab">
                        <i class="bi bi-envelope-check mr-1"></i> Prueba
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-queue" role="tab">
                        <i class="bi bi-list-check mr-1"></i> Cola
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-attempts" role="tab">
                        <i class="bi bi-clock-history mr-1"></i> Intentos recientes
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando comunicaciones...</p>
            </div>

            <div v-show="!loading" class="tab-content">
                <div class="tab-pane fade show active" id="tab-summary" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ stats.events }}</h3>
                                    <p>Eventos</p>
                                </div>
                                <div class="icon"><i class="bi bi-lightning"></i></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ stats.providers }}</h3>
                                    <p>Proveedores</p>
                                </div>
                                <div class="icon"><i class="bi bi-hdd-network"></i></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ queue_summary.pending }}</h3>
                                    <p>Correos pendientes</p>
                                </div>
                                <div class="icon"><i class="bi bi-envelope"></i></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ queue_summary.failed }}</h3>
                                    <p>Correos fallidos</p>
                                </div>
                                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-7">
                            <h5 class="mb-3">Eventos configurados</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Codigo</th>
                                            <th>Nombre</th>
                                            <th>Interna</th>
                                            <th>Email</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="event in events" :key="event.id">
                                            <td><code>{{ event.code }}</code></td>
                                            <td>{{ event.name }}</td>
                                            <td>{{ event.notify_internal == 1 ? 'Si' : 'No' }}</td>
                                            <td>{{ event.notify_email == 1 ? 'Si' : 'No' }}</td>
                                            <td>
                                                <span class="badge" :class="event.active == 1 ? 'badge-success' : 'badge-secondary'">
                                                    {{ event.active == 1 ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="events.length === 0">
                                            <td colspan="5" class="text-center text-muted">Sin eventos configurados.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <h5 class="mb-3">Estado de cola</h5>
                            <table class="table table-bordered table-sm">
                                <tbody>
                                    <tr><th>Pendientes</th><td>{{ queue_summary.pending }}</td></tr>
                                    <tr><th>Procesando</th><td>{{ queue_summary.processing }}</td></tr>
                                    <tr><th>Enviados</th><td>{{ queue_summary.sent }}</td></tr>
                                    <tr><th>Simulados</th><td>{{ queue_summary.simulated }}</td></tr>
                                    <tr><th>Fallidos</th><td>{{ queue_summary.failed }}</td></tr>
                                    <tr><th>Con error registrado</th><td>{{ queue_summary.last_errors }}</td></tr>
                                </tbody>
                            </table>
                            <button class="btn btn-outline-primary btn-sm" :disabled="processingQueue" @click="processQueue">
                                <i class="bi bi-play-circle mr-1"></i> Procesar cola
                            </button>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-providers" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-7">
                            <h5 class="mb-3">Proveedores de comunicacion</h5>
                            <p class="text-muted">Los secretos se muestran solo como estado configurado. No se devuelven contraseñas ni API keys.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Codigo</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Transporte</th>
                                            <th>Host</th>
                                            <th>Puerto</th>
                                            <th>Cifrado</th>
                                            <th>From email</th>
                                            <th>From name</th>
                                            <th>Reply-to</th>
                                            <th>Estado</th>
                                            <th>Ultima prueba</th>
                                            <th>Prueba</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="provider in providers" :key="provider.id" :class="selectedProvider && selectedProvider.id == provider.id ? 'table-primary' : ''">
                                            <td><code>{{ provider.code }}</code></td>
                                            <td>{{ provider.name }}</td>
                                            <td>{{ provider.type }}</td>
                                            <td>{{ provider.transport }}</td>
                                            <td>{{ provider.host || '-' }}</td>
                                            <td>{{ provider.port || '-' }}</td>
                                            <td>{{ provider.encryption || '-' }}</td>
                                            <td>{{ provider.from_email || '-' }}</td>
                                            <td>{{ provider.from_name || '-' }}</td>
                                            <td>{{ provider.reply_to_email || '-' }}</td>
                                            <td>
                                                <span class="badge" :class="provider.active == 1 ? 'badge-success' : 'badge-secondary'">
                                                    {{ provider.active == 1 ? 'Activo' : 'Inactivo' }}
                                                </span>
                                                <span class="badge badge-info" v-if="provider.simulation_mode == 1">Simulacion</span>
                                            </td>
                                            <td>
                                                <span>{{ provider.last_test_status || '-' }}</span><br>
                                                <small class="text-muted">{{ formatDate(provider.last_test_at) }}</small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" @click="selectProvider(provider)">
                                                    Editar
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" @click="prepareTest(provider)">
                                                    Probar
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="providers.length === 0">
                                            <td colspan="13" class="text-center text-muted">No hay proveedores configurados. Ejecuta seedcommunications.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <h5 class="mb-3">Editar proveedor</h5>
                            <div v-if="!selectedProvider" class="alert alert-info">
                                Selecciona un proveedor para editarlo.
                            </div>
                            <form v-if="selectedProvider" @submit.prevent="saveProvider">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Codigo</label>
                                        <input class="form-control" :value="providerForm.code" disabled>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Nombre</label>
                                        <input class="form-control" v-model="providerForm.name">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Tipo</label>
                                        <select class="form-control" v-model="providerForm.type">
                                            <option value="disabled">disabled</option>
                                            <option value="php_mail">php_mail</option>
                                            <option value="smtp">smtp</option>
                                            <option value="api">api</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Transporte</label>
                                        <select class="form-control" v-model="providerForm.transport">
                                            <option value="disabled">disabled</option>
                                            <option value="php_mail">php_mail</option>
                                            <option value="smtp">smtp</option>
                                            <option value="api">api</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-8">
                                        <label>Host</label>
                                        <input class="form-control" v-model="providerForm.host">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Puerto</label>
                                        <input type="number" class="form-control" v-model.number="providerForm.port">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Usuario</label>
                                        <input class="form-control" v-model="providerForm.username">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Cifrado</label>
                                        <select class="form-control" v-model="providerForm.encryption">
                                            <option value="">Ninguno</option>
                                            <option value="tls">TLS</option>
                                            <option value="ssl">SSL</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Nueva contraseña</label>
                                        <input type="password" class="form-control" v-model="providerForm.new_password" autocomplete="new-password">
                                        <small class="text-muted">
                                            {{ providerForm.password_configured ? 'Configurado. Deja vacío para conservarlo.' : 'Sin contraseña configurada.' }}
                                        </small>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Nueva API key</label>
                                        <input type="password" class="form-control" v-model="providerForm.new_api_key" autocomplete="new-password">
                                        <small class="text-muted">
                                            {{ providerForm.api_key_configured ? 'Configurado. Deja vacio para conservarla.' : 'Sin API key configurada.' }}
                                        </small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>API base URL</label>
                                    <input class="form-control" v-model="providerForm.api_base_url">
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>From email</label>
                                        <input class="form-control" v-model="providerForm.from_email">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>From name</label>
                                        <input class="form-control" v-model="providerForm.from_name">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Reply-to email</label>
                                    <input class="form-control" v-model="providerForm.reply_to_email">
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>Timeout</label>
                                        <input type="number" class="form-control" v-model.number="providerForm.timeout_seconds">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Limite diario</label>
                                        <input type="number" class="form-control" v-model.number="providerForm.daily_limit">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Limite por hora</label>
                                        <input type="number" class="form-control" v-model.number="providerForm.hourly_limit">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="provider-active" v-model="providerForm.active" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="provider-active">Activo</label>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="provider-simulation" v-model="providerForm.simulation_mode" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="provider-simulation">Simulacion</label>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="provider-tls" v-model="providerForm.verify_tls" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="provider-tls">Verificar TLS</label>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-primary" :disabled="savingProvider">
                                    <i class="bi bi-save mr-1"></i> Guardar proveedor
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-accounts" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-7">
                            <h5 class="mb-3">Cuentas de correo</h5>
                            <p class="text-muted">Configuracion base para futuras sincronizaciones IMAP. No se leen buzones en esta fase.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Codigo</th>
                                            <th>Nombre</th>
                                            <th>Correo</th>
                                            <th>Tipo</th>
                                            <th>Alcance</th>
                                            <th>SMTP</th>
                                            <th>IMAP host</th>
                                            <th>Puerto</th>
                                            <th>Sync</th>
                                            <th>Append sent</th>
                                            <th>Ultima prueba</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="account in accounts" :key="account.id" :class="selectedAccount && selectedAccount.id == account.id ? 'table-primary' : ''">
                                            <td><code>{{ account.code }}</code></td>
                                            <td>{{ account.name }}</td>
                                            <td>{{ account.email_address }}</td>
                                            <td>{{ account.account_type }}</td>
                                            <td>{{ account.mailbox_scope || 'system' }}</td>
                                            <td>{{ account.smtp_provider_code || account.provider_code || '-' }}</td>
                                            <td>{{ account.imap_host || '-' }}</td>
                                            <td>{{ account.imap_port || '-' }}</td>
                                            <td>
                                                <span class="badge" :class="account.sync_enabled == 1 ? 'badge-info' : 'badge-secondary'">
                                                    {{ account.sync_enabled == 1 ? 'Preparada' : 'Sin sync' }}
                                                </span>
                                            </td>
                                            <td>{{ account.append_sent == 1 ? 'Si' : 'No' }}</td>
                                            <td>
                                                <span>{{ account.last_sync_status || '-' }}</span><br>
                                                <small class="text-muted">{{ formatDate(account.last_sync_at) }}</small>
                                            </td>
                                            <td>
                                                <span class="badge" :class="account.active == 1 ? 'badge-success' : 'badge-secondary'">
                                                    {{ account.active == 1 ? 'Activa' : 'Inactiva' }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" @click="selectAccount(account)">Editar</button>
                                                <button class="btn btn-sm btn-outline-success" @click="testImapAccount(account)" :disabled="testingAccount">
                                                    Probar IMAP
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" @click="syncImapAccount(account)" :disabled="syncingAccount">
                                                    Sincronizar ahora
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="accounts.length === 0">
                                            <td colspan="13" class="text-center text-muted">No hay cuentas de correo configuradas.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Editar cuenta</h5>
                                <button class="btn btn-sm btn-outline-secondary" @click="newAccount">
                                    Nueva cuenta
                                </button>
                            </div>
                            <form @submit.prevent="saveAccount">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Codigo</label>
                                        <input class="form-control" v-model="accountForm.code">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Tipo</label>
                                        <select class="form-control" v-model="accountForm.account_type">
                                            <option value="support">Soporte</option>
                                            <option value="sales">Ventas</option>
                                            <option value="purchases">Compras</option>
                                            <option value="billing">Facturacion</option>
                                            <option value="system">Sistema</option>
                                            <option value="other">Otro</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>Alcance</label>
                                        <select class="form-control" v-model="accountForm.mailbox_scope">
                                            <option value="system">Sistema</option>
                                            <option value="personal">Personal</option>
                                            <option value="shared">Compartida</option>
                                            <option value="department">Departamento</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Propietario usuario</label>
                                        <select class="form-control" v-model="accountForm.owner_user_id">
                                            <option value="0">Sin usuario</option>
                                            <option v-for="user in users" :key="'owner-user-'+user.id" :value="user.id">{{ user.label }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Propietario grupo</label>
                                        <select class="form-control" v-model="accountForm.owner_group_id">
                                            <option value="0">Sin grupo</option>
                                            <option v-for="group in groups" :key="'owner-group-'+group.id" :value="group.id">{{ group.name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Nombre</label>
                                    <input class="form-control" v-model="accountForm.name">
                                </div>
                                <div class="form-group">
                                    <label>Correo</label>
                                    <input class="form-control" v-model="accountForm.email_address">
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Proveedor SMTP</label>
                                        <select class="form-control" v-model="accountForm.smtp_provider_code">
                                            <option value="">Sin proveedor</option>
                                            <option v-for="provider in providers" :key="'smtp-'+provider.code" :value="provider.code">
                                                {{ provider.name }} ({{ provider.code }})
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Proveedor IMAP</label>
                                        <input class="form-control" v-model="accountForm.imap_provider_code" placeholder="imap_default">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-8">
                                        <label>Host IMAP</label>
                                        <input class="form-control" v-model="accountForm.imap_host">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Puerto</label>
                                        <input type="number" class="form-control" v-model.number="accountForm.imap_port">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Cifrado</label>
                                        <select class="form-control" v-model="accountForm.imap_encryption">
                                            <option value="ssl">SSL</option>
                                            <option value="tls">TLS</option>
                                            <option value="none">Ninguno</option>
                                            <option value="">Sin especificar</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Usuario IMAP</label>
                                        <input class="form-control" v-model="accountForm.imap_username">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Nueva contraseña IMAP</label>
                                    <input type="password" class="form-control" v-model="accountForm.new_imap_password" autocomplete="new-password">
                                    <small class="text-muted">
                                        {{ accountForm.imap_password_configured ? 'Configurada. Deja vacío para conservarla.' : 'Sin contraseña configurada.' }}
                                    </small>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Inbox</label>
                                        <input class="form-control" v-model="accountForm.imap_folder_inbox">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Sent</label>
                                        <input class="form-control" v-model="accountForm.imap_folder_sent">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Drafts</label>
                                        <input class="form-control" v-model="accountForm.imap_folder_drafts">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Trash</label>
                                        <input class="form-control" v-model="accountForm.imap_folder_trash">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="account-sync-inbox" v-model="accountForm.sync_inbox" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="account-sync-inbox">Preparar Inbox</label>
                                        </div>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="account-sync-sent" v-model="accountForm.sync_sent" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="account-sync-sent">Preparar Sent</label>
                                        </div>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="account-sync-drafts" v-model="accountForm.sync_drafts" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="account-sync-drafts">Preparar Drafts</label>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="account-sync-trash" v-model="accountForm.sync_trash" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="account-sync-trash">Preparar Trash</label>
                                        </div>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="account-append-sent" v-model="accountForm.append_sent" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="account-append-sent">Append sent futuro</label>
                                        </div>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="account-sync-enabled" v-model="accountForm.sync_enabled" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="account-sync-enabled">Preparar sync</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="account-active" v-model="accountForm.active" true-value="1" false-value="0">
                                        <label class="custom-control-label" for="account-active">Activa</label>
                                    </div>
                                </div>
                                <button class="btn btn-primary" :disabled="savingAccount">
                                    <i class="bi bi-save mr-1"></i> Guardar cuenta
                                </button>
                            </form>

                            <hr>
                            <h5 class="mb-2">Asignaciones del buzon</h5>
                            <p class="text-muted small">Asigna esta cuenta a usuarios, grupos o roles. No se muestran contraseñas ni secretos.</p>
                            <div v-if="!accountForm.id" class="alert alert-info">
                                Guarda o selecciona una cuenta antes de asignarla.
                            </div>
                            <form v-if="accountForm.id" @submit.prevent="saveAccountAssignment">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>Tipo</label>
                                        <select class="form-control" v-model="assignmentForm.assignment_type">
                                            <option value="user">Usuario</option>
                                            <option value="group">Grupo</option>
                                            <option value="role">Rol</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-8" v-if="assignmentForm.assignment_type === 'user'">
                                        <label>Usuario</label>
                                        <select class="form-control" v-model="assignmentForm.assignment_value">
                                            <option value="">Selecciona usuario</option>
                                            <option v-for="user in users" :key="'assign-user-'+user.id" :value="user.id">{{ user.label }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-8" v-if="assignmentForm.assignment_type === 'group'">
                                        <label>Grupo</label>
                                        <select class="form-control" v-model="assignmentForm.assignment_value">
                                            <option value="">Selecciona grupo</option>
                                            <option v-for="group in groups" :key="'assign-group-'+group.id" :value="group.id">{{ group.name }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-8" v-if="assignmentForm.assignment_type === 'role'">
                                        <label>Rol</label>
                                        <input class="form-control" v-model="assignmentForm.assignment_value" placeholder="sales, support, billing">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Nivel</label>
                                        <select class="form-control" v-model="assignmentForm.access_level">
                                            <option value="owner">Owner</option>
                                            <option value="delegate">Delegate</option>
                                            <option value="viewer">Viewer</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Estado</label>
                                        <select class="form-control" v-model="assignmentForm.active">
                                            <option value="1">Activa</option>
                                            <option value="0">Inactiva</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="assign-can-send" v-model="assignmentForm.can_send" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="assign-can-send">Puede enviar</label>
                                        </div>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="assign-can-receive" v-model="assignmentForm.can_receive" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="assign-can-receive">Puede recibir</label>
                                        </div>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="assign-default-sender" v-model="assignmentForm.default_sender" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="assign-default-sender">Remitente predeterminado</label>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="assign-can-sync" v-model="assignmentForm.can_sync" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="assign-can-sync">Puede sincronizar</label>
                                        </div>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="assign-can-manage" v-model="assignmentForm.can_manage" true-value="1" false-value="0">
                                            <label class="custom-control-label" for="assign-can-manage">Puede administrar</label>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-outline-primary btn-sm" :disabled="savingAssignment">
                                    <i class="bi bi-person-plus mr-1"></i> Guardar asignacion
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">Asignaciones actuales</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Cuenta</th>
                                            <th>Asignado a</th>
                                            <th>Nivel</th>
                                            <th>Permisos</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="assignment in filteredAssignments" :key="'assignment-'+assignment.id">
                                            <td>{{ accountLabel(assignment.account_id) }}</td>
                                            <td>{{ assignment.assignment_type }}: {{ assignment.label || assignment.assignment_value }}</td>
                                            <td>{{ assignment.access_level }}</td>
                                            <td>
                                                <span class="badge badge-light" v-if="assignment.can_send == 1">Enviar</span>
                                                <span class="badge badge-light" v-if="assignment.can_receive == 1">Recibir</span>
                                                <span class="badge badge-light" v-if="assignment.can_sync == 1">Sync</span>
                                                <span class="badge badge-light" v-if="assignment.can_manage == 1">Admin</span>
                                                <span class="badge badge-info" v-if="assignment.default_sender == 1">Default</span>
                                            </td>
                                            <td>
                                                <span class="badge" :class="assignment.active == 1 ? 'badge-success' : 'badge-secondary'">
                                                    {{ assignment.active == 1 ? 'Activa' : 'Inactiva' }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary" @click="editAccountAssignment(assignment)">Editar</button>
                                                <button class="btn btn-sm btn-outline-danger" @click="revokeAccountAssignment(assignment)" :disabled="assignment.active != 1">Desactivar</button>
                                            </td>
                                        </tr>
                                        <tr v-if="filteredAssignments.length === 0">
                                            <td colspan="6" class="text-center text-muted">No hay asignaciones para esta vista.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-my-accounts" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">Mis cuentas</h5>
                            <p class="text-muted mb-0">Buzones asignados a tu usuario, grupo o rol. No se muestran secretos ni credenciales.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Cuenta</th>
                                    <th>Correo</th>
                                    <th>Alcance</th>
                                    <th>Enviar</th>
                                    <th>Recibir</th>
                                    <th>Sync</th>
                                    <th>Default</th>
                                    <th>Ultimo estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="account in my_accounts" :key="'my-account-'+account.id">
                                    <td>{{ account.name }} <br><small class="text-muted">{{ account.code }}</small></td>
                                    <td>{{ account.email_address }}</td>
                                    <td>{{ account.mailbox_scope || 'system' }}</td>
                                    <td>{{ account.can_send == 1 ? 'Si' : 'No' }}</td>
                                    <td>{{ account.can_receive == 1 ? 'Si' : 'No' }}</td>
                                    <td>{{ account.can_sync == 1 ? 'Si' : 'No' }}</td>
                                    <td>{{ account.default_sender == 1 ? 'Si' : 'No' }}</td>
                                    <td>
                                        {{ account.last_sync_status || '-' }}<br>
                                        <small class="text-muted">{{ formatDate(account.last_sync_at) }}</small>
                                    </td>
                                </tr>
                                <tr v-if="my_accounts.length === 0">
                                    <td colspan="8" class="text-center text-muted">No tienes cuentas de correo asignadas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-my-mailbox" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">Mi bandeja</h5>
                            <p class="text-muted mb-0">Conversaciones de las cuentas de correo asignadas a tu usuario, grupo o rol. Vista solo lectura.</p>
                        </div>
                        <button class="btn btn-sm btn-outline-primary mt-2 mt-md-0" :disabled="myMailboxLoading" @click="loadMyMailbox">
                            <i class="bi bi-arrow-clockwise mr-1"></i> Actualizar
                        </button>
                    </div>

                    <div v-if="!myMailboxLoading && myMailboxAccounts.length === 0" class="conversation-empty">
                        <i class="bi bi-inbox"></i>
                        <strong>No tienes cuentas de correo asignadas.</strong>
                        <p>Solicita a un administrador que asigne una cuenta a tu usuario, grupo o rol.</p>
                    </div>

                    <div v-if="myMailboxAccounts.length > 0" class="conversation-center">
                        <aside class="conversation-folders">
                            <div class="form-group mb-2">
                                <label class="small text-muted">Cuenta</label>
                                <select class="form-control form-control-sm" v-model="myMailboxFilters.account_id" @change="searchMyMailbox">
                                    <option value="0">Todas mis cuentas</option>
                                    <option v-for="account in myMailboxAccounts" :key="'mailbox-account-'+account.id" :value="account.id">
                                        {{ account.email_address }}
                                    </option>
                                </select>
                            </div>
                            <button
                                v-for="folder in conversationFolders"
                                :key="'mailbox-folder-'+folder.code"
                                type="button"
                                class="conversation-folder"
                                :class="{ active: myMailboxFilters.folder === folder.code }"
                                @click="selectMyMailboxFolder(folder.code)">
                                <i :class="folder.icon"></i>
                                <span>{{ folder.label }}</span>
                            </button>
                        </aside>

                        <section class="conversation-list-panel">
                            <div class="conversation-filters">
                                <div class="input-group input-group-sm mb-2">
                                    <input
                                        class="form-control"
                                        v-model="myMailboxFilters.q"
                                        @keyup.enter="searchMyMailbox"
                                        placeholder="Buscar por asunto, email o texto">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" @click="searchMyMailbox">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4 mb-2">
                                        <select class="form-control form-control-sm" v-model="myMailboxFilters.channel" @change="searchMyMailbox">
                                            <option value="">Todos los canales</option>
                                            <option v-for="channel in myMailboxChannels" :key="'mailbox-channel-'+channel.code" :value="channel.code">{{ channel.label }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4 mb-2">
                                        <label class="small mb-0">
                                            <input type="checkbox" v-model="myMailboxFilters.unread" true-value="1" false-value="0" @change="searchMyMailbox">
                                            No leidas
                                        </label>
                                    </div>
                                    <div class="form-group col-md-4 mb-2 text-right">
                                        <button class="btn btn-sm btn-outline-secondary" @click="clearMyMailboxFilters">Limpiar</button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="myMailboxLoading" class="text-center text-muted py-4">
                                <span class="spinner-border spinner-border-sm mr-2"></span> Cargando bandeja...
                            </div>
                            <div v-if="!myMailboxLoading && myMailboxConversations.length === 0" class="conversation-empty">
                                <i class="bi bi-inbox"></i>
                                <strong>No hay conversaciones para mostrar.</strong>
                                <p>Cuando existan mensajes en tus cuentas asignadas apareceran aqui.</p>
                            </div>
                            <div
                                v-for="conversation in myMailboxConversations"
                                :key="'my-mailbox-conversation-'+conversation.id"
                                v-if="!myMailboxLoading"
                                class="conversation-item"
                                :class="{ active: selectedMyMailboxConversation && selectedMyMailboxConversation.id === conversation.id }"
                                @click="selectMyMailboxConversation(conversation)">
                                <div class="conversation-item-head">
                                    <span class="conversation-subject">{{ conversation.subject || '(Sin asunto)' }}</span>
                                    <small>{{ formatDate(conversation.last_message_at) }}</small>
                                </div>
                                <div class="conversation-item-meta">
                                    <span><i :class="conversation.channel_icon"></i> {{ conversation.channel_label }}</span>
                                    <span>{{ conversation.direction }}</span>
                                    <span v-if="conversation.account_email" class="badge badge-light">{{ conversation.account_email }}</span>
                                    <span v-if="conversation.related_label" class="badge badge-light">{{ conversation.related_label }}</span>
                                    <span v-if="conversation.unread_count > 0" class="badge badge-danger">{{ conversation.unread_count }}</span>
                                    <span class="badge badge-secondary">{{ conversation.status }}</span>
                                </div>
                                <div class="conversation-participants">{{ conversation.participants.join(', ') }}</div>
                                <div class="conversation-snippet">{{ conversation.snippet || 'Sin vista previa.' }}</div>
                            </div>
                            <div class="conversation-pagination" v-if="myMailboxPagination.total > 0">
                                <button class="btn btn-sm btn-outline-secondary" :disabled="myMailboxPagination.page <= 1" @click="changeMyMailboxPage(myMailboxPagination.page - 1)">Anterior</button>
                                <span class="small text-muted">Pagina {{ myMailboxPagination.page }} de {{ myMailboxPagination.pages || 1 }}</span>
                                <button class="btn btn-sm btn-outline-secondary" :disabled="myMailboxPagination.page >= myMailboxPagination.pages" @click="changeMyMailboxPage(myMailboxPagination.page + 1)">Siguiente</button>
                            </div>
                        </section>

                        <section class="conversation-preview-panel">
                            <div v-if="!selectedMyMailboxConversation" class="conversation-empty h-100">
                                <i class="bi bi-envelope-open"></i>
                                <strong>Selecciona una conversacion.</strong>
                                <p>El contenido se mostrara aqui en modo lectura.</p>
                            </div>
                            <div v-else>
                                <div class="conversation-preview-header">
                                    <div>
                                        <h5 class="mb-1">{{ selectedMyMailboxConversation.subject || '(Sin asunto)' }}</h5>
                                        <small class="text-muted">
                                            {{ selectedMyMailboxConversation.account_email || 'Cuenta no identificada' }}
                                        </small>
                                    </div>
                                    <span class="badge badge-secondary">{{ selectedMyMailboxConversation.status }}</span>
                                </div>
                                <div v-if="myMailboxDetailLoading" class="text-center text-muted py-4">
                                    <span class="spinner-border spinner-border-sm mr-2"></span> Cargando conversacion...
                                </div>
                                <div v-if="!myMailboxDetailLoading && myMailboxMessages.length === 0" class="conversation-empty">
                                    <i class="bi bi-chat-left-text"></i>
                                    <strong>Sin mensajes visibles.</strong>
                                    <p>La conversacion existe, pero aun no tiene mensajes visibles.</p>
                                </div>
                                <article v-for="message in myMailboxMessages" :key="'my-mailbox-message-'+message.id" class="conversation-message">
                                    <div class="conversation-message-head">
                                        <div>
                                            <strong>{{ message.from_name || message.from_email || 'Remitente' }}</strong><br>
                                            <small class="text-muted">
                                                Para: <span v-for="recipient in message.to" :key="'mailbox-to-'+message.id+'-'+formatRecipient(recipient)">{{ formatRecipient(recipient) }} </span>
                                            </small>
                                        </div>
                                        <small>{{ formatDate(message.date) }}</small>
                                    </div>
                                    <div class="small text-muted mb-2" v-if="message.account_email">Cuenta: {{ message.account_email }}</div>
                                    <div class="conversation-message-body" v-if="message.body_html_sanitized" v-html="message.body_html_sanitized"></div>
                                    <pre class="conversation-message-text" v-else>{{ message.body_text || message.snippet || 'Sin contenido visible.' }}</pre>
                                    <div v-if="message.attachments.length" class="conversation-attachments">
                                        <strong>Adjuntos</strong>
                                        <div v-for="attachment in message.attachments" :key="'mailbox-attachment-'+attachment.id" class="conversation-attachment">
                                            <i class="bi bi-paperclip"></i>
                                            <span>{{ attachment.filename }}</span>
                                            <small>{{ attachment.mime_type }} - {{ formatBytes(attachment.size_bytes) }}</small>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-conversations" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">Centro de conversaciones</h5>
                            <p class="text-muted mb-0">Lectura operativa de mensajes almacenados y envio por cuentas asignadas. Sin adjuntos en esta fase.</p>
                        </div>
                        <div class="btn-group mt-2 mt-md-0">
                            <button class="btn btn-sm btn-primary" :disabled="sendableAccounts.length === 0" @click="openComposeModal">
                                <i class="bi bi-pencil-square mr-1"></i> Nuevo correo
                            </button>
                            <button class="btn btn-sm btn-outline-primary" :disabled="conversationLoading" @click="loadConversations">
                                <i class="bi bi-arrow-clockwise mr-1"></i> Actualizar
                            </button>
                        </div>
                    </div>

                    <div class="conversation-center">
                        <aside class="conversation-folders">
                            <button
                                v-for="folder in conversationFolders"
                                :key="'folder-'+folder.code"
                                type="button"
                                class="conversation-folder"
                                :class="{ active: conversationFilters.folder === folder.code }"
                                @click="selectConversationFolder(folder.code)">
                                <i :class="folder.icon"></i>
                                <span>{{ folder.label }}</span>
                            </button>
                        </aside>

                        <section class="conversation-list-panel">
                            <div class="conversation-filters">
                                <div class="input-group input-group-sm mb-2">
                                    <input
                                        class="form-control"
                                        v-model="conversationFilters.q"
                                        @keyup.enter="searchConversations"
                                        placeholder="Buscar por asunto, email o texto">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" @click="searchConversations">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4 mb-2">
                                        <select class="form-control form-control-sm" v-model="conversationFilters.channel" @change="searchConversations">
                                            <option value="">Todos los canales</option>
                                            <option v-for="channel in conversationChannels" :key="'channel-'+channel.code" :value="channel.code">{{ channel.label }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4 mb-2">
                                        <input type="date" class="form-control form-control-sm" v-model="conversationFilters.date_from" @change="searchConversations">
                                    </div>
                                    <div class="form-group col-md-4 mb-2">
                                        <input type="date" class="form-control form-control-sm" v-model="conversationFilters.date_to" @change="searchConversations">
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center">
                                    <label class="mr-3 small mb-2">
                                        <input type="checkbox" v-model="conversationFilters.unread" true-value="1" false-value="0" @change="searchConversations">
                                        No leídas
                                    </label>
                                    <label class="mr-3 small mb-2">
                                        <input type="checkbox" v-model="conversationFilters.assigned" true-value="1" false-value="0" @change="searchConversations">
                                        Asignadas
                                    </label>
                                    <button class="btn btn-link btn-sm p-0 mb-2" @click="clearConversationFilters">Limpiar filtros</button>
                                </div>
                            </div>

                            <div v-if="conversationLoading" class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm text-primary"></div>
                                <span class="ml-2">Cargando conversaciones...</span>
                            </div>

                            <div v-if="!conversationLoading && conversations.length === 0" class="conversation-empty">
                                <i class="bi bi-inbox"></i>
                                <strong>Aún no hay conversaciones para mostrar.</strong>
                                <span>Cuando existan mensajes sincronizados apareceran en esta vista.</span>
                            </div>

                            <button
                                v-for="conversation in conversations"
                                :key="'conversation-'+conversation.id"
                                type="button"
                                class="conversation-item"
                                :class="{ active: selectedConversation && selectedConversation.id === conversation.id }"
                                @click="selectConversation(conversation)">
                                <div class="conversation-item-head">
                                    <span class="conversation-subject">{{ conversation.subject || '(Sin asunto)' }}</span>
                                    <small>{{ formatDate(conversation.last_message_at) }}</small>
                                </div>
                                <div class="conversation-item-meta">
                                    <span><i :class="conversation.channel_icon"></i> {{ conversation.channel_label }}</span>
                                    <span v-if="conversation.related_label" class="badge badge-light">{{ conversation.related_label }}</span>
                                    <span v-if="conversation.unread_count > 0" class="badge badge-danger">{{ conversation.unread_count }}</span>
                                </div>
                                <div class="conversation-participants">{{ conversation.participants.join(', ') }}</div>
                                <div class="conversation-snippet">{{ conversation.snippet || 'Sin vista previa.' }}</div>
                            </button>

                            <div class="conversation-pagination" v-if="conversationPagination.total > 0">
                                <button class="btn btn-sm btn-outline-secondary" :disabled="conversationPagination.page <= 1" @click="changeConversationPage(conversationPagination.page - 1)">Anterior</button>
                                <span class="small text-muted">Página {{ conversationPagination.page }} de {{ conversationPagination.pages || 1 }}</span>
                                <button class="btn btn-sm btn-outline-secondary" :disabled="conversationPagination.page >= conversationPagination.pages" @click="changeConversationPage(conversationPagination.page + 1)">Siguiente</button>
                            </div>
                        </section>

                        <section class="conversation-preview-panel">
                            <div v-if="!selectedConversation" class="conversation-empty h-100">
                                <i class="bi bi-chat-left-text"></i>
                                <strong>Selecciona una conversación.</strong>
                                <span>El historial read-only se mostrara en este panel.</span>
                            </div>

                            <div v-if="selectedConversation">
                                <div class="conversation-preview-header">
                                    <div>
                                        <h5 class="mb-1">{{ selectedConversation.subject || '(Sin asunto)' }}</h5>
                                        <div class="text-muted small">
                                            <i :class="selectedConversation.channel_icon"></i>
                                            {{ selectedConversation.channel_label }}
                                            <span class="mx-1">|</span>
                                            {{ selectedConversation.message_count }} mensajes
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <button class="btn btn-sm btn-primary mb-1" :disabled="sendableAccounts.length === 0 || conversationDetailLoading" @click="openReplyModal(selectedConversation)">
                                            <i class="bi bi-reply mr-1"></i> Responder
                                        </button><br>
                                        <span class="badge" :class="selectedConversation.status === 'open' ? 'badge-success' : 'badge-secondary'">
                                            {{ selectedConversation.status }}
                                        </span>
                                    </div>
                                </div>

                                <div class="conversation-related mt-2">
                                    <span v-if="conversationDetail.related_summary && conversationDetail.related_summary.label" class="badge badge-info">
                                        {{ conversationDetail.related_summary.label }}
                                    </span>
                                    <span v-else class="badge badge-light">Sin entidad relacionada</span>
                                </div>

                                <div v-if="conversationDetailLoading" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                    <span class="ml-2">Cargando mensajes...</span>
                                </div>

                                <div v-if="!conversationDetailLoading && conversationMessages.length === 0" class="conversation-empty">
                                    <i class="bi bi-envelope-open"></i>
                                    <strong>Sin mensajes almacenados.</strong>
                                    <span>La conversación existe, pero aún no tiene mensajes visibles.</span>
                                </div>

                                <article v-for="message in conversationMessages" :key="'message-'+message.id" class="conversation-message">
                                    <div class="conversation-message-head">
                                        <div>
                                            <strong>{{ message.from_name || message.from_email || 'Remitente desconocido' }}</strong>
                                            <div class="text-muted small">
                                                Para: {{ message.to.length ? message.to.map(formatRecipient).join(', ') : 'Sin destinatarios' }}
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ formatDate(message.date) }}</small>
                                    </div>
                                    <div class="conversation-message-body" v-if="message.body_html_sanitized" v-html="message.body_html_sanitized"></div>
                                    <pre class="conversation-message-text" v-else>{{ message.body_text || message.snippet || 'Sin contenido visible.' }}</pre>
                                    <div v-if="message.attachments.length" class="conversation-attachments">
                                        <strong>Adjuntos</strong>
                                        <div v-for="attachment in message.attachments" :key="'attachment-'+attachment.id" class="conversation-attachment">
                                            <i class="bi bi-paperclip"></i>
                                            <span>{{ attachment.filename || 'Adjunto sin nombre' }}</span>
                                            <small>{{ attachment.mime_type || 'tipo desconocido' }} | {{ formatBytes(attachment.size_bytes) }}</small>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-recipients" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-7">
                            <h5 class="mb-3">Reglas de destinatarios por evento</h5>
                            <p class="text-muted">Define destinatarios internos o de email por evento y canal. Las exclusiones se aplican despues de las inclusiones.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Evento</th>
                                            <th>Canal</th>
                                            <th>Modo</th>
                                            <th>Tipo</th>
                                            <th>Destinatario</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="rule in recipient_rules" :key="rule.id" :class="recipientForm.id == rule.id ? 'table-primary' : ''">
                                            <td><code>{{ rule.event_code }}</code></td>
                                            <td>{{ rule.channel_code }}</td>
                                            <td>
                                                <span class="badge" :class="rule.mode === 'include' ? 'badge-success' : 'badge-warning'">
                                                    {{ rule.mode === 'include' ? 'Incluir' : 'Excluir' }}
                                                </span>
                                            </td>
                                            <td>{{ rule.recipient_type }}</td>
                                            <td>{{ rule.label || rule.recipient_value }}</td>
                                            <td>
                                                <span class="badge" :class="rule.active == 1 ? 'badge-success' : 'badge-secondary'">
                                                    {{ rule.active == 1 ? 'Activa' : 'Inactiva' }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" @click="editRecipientRule(rule)">Editar</button>
                                                <button class="btn btn-sm btn-outline-secondary" @click="toggleRecipientRule(rule)">
                                                    {{ rule.active == 1 ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="recipient_rules.length === 0">
                                            <td colspan="7" class="text-center text-muted">Sin reglas configuradas.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <h5 class="mb-3">Agregar o editar regla</h5>
                            <form @submit.prevent="saveRecipientRule">
                                <div class="form-group">
                                    <label>Evento</label>
                                    <select class="form-control" v-model="recipientForm.event_code">
                                        <option value="">Selecciona evento</option>
                                        <option v-for="event in events" :key="event.code" :value="event.code">{{ event.name }} ({{ event.code }})</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Canal</label>
                                        <select class="form-control" v-model="recipientForm.channel_code">
                                            <option value="internal">internal</option>
                                            <option value="email">email</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Modo</label>
                                        <select class="form-control" v-model="recipientForm.mode">
                                            <option value="include">Incluir</option>
                                            <option value="exclude">Excluir</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Tipo</label>
                                        <select class="form-control" v-model="recipientForm.recipient_type" @change="recipientForm.recipient_value = ''">
                                            <option value="user">Usuario</option>
                                            <option value="group">Grupo</option>
                                            <option value="role">Rol email</option>
                                            <option value="email">Email manual</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Destinatario</label>
                                        <select v-if="recipientForm.recipient_type === 'user'" class="form-control" v-model="recipientForm.recipient_value">
                                            <option value="">Selecciona usuario</option>
                                            <option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.label }} - {{ user.email }}</option>
                                        </select>
                                        <select v-else-if="recipientForm.recipient_type === 'group'" class="form-control" v-model="recipientForm.recipient_value">
                                            <option value="">Selecciona grupo</option>
                                            <option v-for="group in groups" :key="group.id" :value="String(group.id)">{{ group.name }}</option>
                                        </select>
                                        <select v-else-if="recipientForm.recipient_type === 'role'" class="form-control" v-model="recipientForm.recipient_value">
                                            <option value="">Selecciona rol</option>
                                            <option v-for="role in roles" :key="role.code" :value="role.code">{{ role.name }} ({{ role.code }})</option>
                                        </select>
                                        <input v-else class="form-control" v-model="recipientForm.recipient_value" placeholder="correo@dominio.com">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="recipient-active" v-model="recipientForm.active" true-value="1" false-value="0">
                                        <label class="custom-control-label" for="recipient-active">Regla activa</label>
                                    </div>
                                </div>
                                <button class="btn btn-primary" :disabled="savingRecipient">
                                    <i class="bi bi-save mr-1"></i> Guardar regla
                                </button>
                                <button type="button" class="btn btn-outline-secondary" @click="resetRecipientForm">Nueva regla</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-templates" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-6">
                            <h5 class="mb-3">Templates de correo</h5>
                            <p class="text-muted">Edita asuntos y cuerpos de correo. No se permiten scripts, PHP ni referencias a secretos.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Codigo</th>
                                            <th>Nombre</th>
                                            <th>Asunto</th>
                                            <th>Estado</th>
                                            <th>Actualizado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="template in templates" :key="template.id" :class="templateForm.id == template.id ? 'table-primary' : ''">
                                            <td><code>{{ template.code }}</code></td>
                                            <td>{{ template.name }}</td>
                                            <td>{{ template.subject }}</td>
                                            <td>
                                                <span class="badge" :class="template.active == 1 ? 'badge-success' : 'badge-secondary'">
                                                    {{ template.active == 1 ? 'Activa' : 'Inactiva' }}
                                                </span>
                                            </td>
                                            <td>{{ formatDate(template.updated_at) }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" @click="selectTemplate(template)">Editar</button>
                                            </td>
                                        </tr>
                                        <tr v-if="templates.length === 0">
                                            <td colspan="6" class="text-center text-muted">Sin templates configurados.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h5 class="mb-3">Editor de template</h5>
                            <form v-if="templateForm.id" @submit.prevent="saveTemplate">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>Codigo</label>
                                        <input class="form-control" v-model="templateForm.code" disabled>
                                    </div>
                                    <div class="form-group col-md-8">
                                        <label>Nombre</label>
                                        <input class="form-control" v-model="templateForm.name">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Asunto</label>
                                    <input class="form-control" v-model="templateForm.subject">
                                </div>
                                <div class="form-group">
                                    <label>Cuerpo HTML</label>
                                    <textarea class="form-control text-monospace" rows="8" v-model="templateForm.content"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Cuerpo texto</label>
                                    <textarea class="form-control text-monospace" rows="5" v-model="templateForm.body_text"></textarea>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="template-active" v-model="templateForm.active" true-value="1" false-value="0">
                                        <label class="custom-control-label" for="template-active">Template activo</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <strong>Variables detectadas:</strong>
                                    <span v-if="templateForm.variables && templateForm.variables.length">
                                        <code v-for="variable in templateForm.variables" :key="'tpl-var-'+variable" class="mr-1">{{ variable }}</code>
                                    </span>
                                    <span v-else class="text-muted">Sin variables detectadas.</span>
                                </div>
                                <button class="btn btn-primary" :disabled="savingTemplate">
                                    <i class="bi bi-save mr-1"></i> Guardar template
                                </button>
                            </form>
                            <div v-else class="alert alert-info">Selecciona un template para editarlo.</div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-layouts" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-5">
                            <h5 class="mb-3">Layouts de correo</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Codigo</th>
                                            <th>Nombre</th>
                                            <th>Version</th>
                                            <th>Estado</th>
                                            <th>Actualizado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="layout in layouts" :key="layout.id" :class="layoutForm.id == layout.id ? 'table-primary' : ''">
                                            <td><code>{{ layout.code }}</code></td>
                                            <td>{{ layout.name }}</td>
                                            <td>{{ layout.version }}</td>
                                            <td>
                                                <span class="badge" :class="layout.active == 1 ? 'badge-success' : 'badge-secondary'">
                                                    {{ layout.active == 1 ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td>{{ formatDate(layout.updated_at) }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" @click="selectLayout(layout)">Editar</button>
                                            </td>
                                        </tr>
                                        <tr v-if="layouts.length === 0">
                                            <td colspan="6" class="text-center text-muted">Sin layouts configurados.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <h5 class="mb-3">Editor de layout</h5>
                            <form v-if="layoutForm.id" @submit.prevent="saveLayout">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>Codigo</label>
                                        <input class="form-control" v-model="layoutForm.code" disabled>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Nombre</label>
                                        <input class="form-control" v-model="layoutForm.name">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>Version</label>
                                        <input class="form-control" v-model="layoutForm.version" disabled>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Descripcion</label>
                                    <input class="form-control" v-model="layoutForm.description">
                                </div>
                                <div class="form-group">
                                    <label>Layout HTML</label>
                                    <textarea class="form-control text-monospace" rows="8" v-model="layoutForm.html_layout"></textarea>
                                    <small class="text-muted">Debe incluir <code v-pre>{{body}}</code> para insertar el cuerpo del template.</small>
                                </div>
                                <div class="form-group">
                                    <label>Layout texto</label>
                                    <textarea class="form-control text-monospace" rows="5" v-model="layoutForm.text_layout"></textarea>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="layout-active" v-model="layoutForm.active" true-value="1" false-value="0">
                                        <label class="custom-control-label" for="layout-active">Layout activo</label>
                                    </div>
                                </div>
                                <button class="btn btn-primary" :disabled="savingLayout">
                                    <i class="bi bi-save mr-1"></i> Guardar layout
                                </button>
                            </form>
                            <div v-else class="alert alert-info">Selecciona un layout para editarlo.</div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-preview" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-4">
                            <h5 class="mb-3">Vista previa</h5>
                            <p class="text-muted">Renderiza template y layout sin enviar correo.</p>
                            <div class="form-group">
                                <label>Template</label>
                                <select class="form-control" v-model="previewForm.template_id">
                                    <option value="">Selecciona template</option>
                                    <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }} ({{ template.code }})</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Layout</label>
                                <select class="form-control" v-model="previewForm.layout_code">
                                    <option value="">Sin layout</option>
                                    <option v-for="layout in layouts" :key="layout.code" :value="layout.code">{{ layout.name }} ({{ layout.code }})</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Variables de muestra JSON</label>
                                <textarea class="form-control text-monospace" rows="8" v-model="previewForm.variables_json"></textarea>
                            </div>
                            <button class="btn btn-primary" :disabled="previewingTemplate" @click="previewTemplate">
                                <i class="bi bi-eye mr-1"></i> Generar vista previa
                            </button>
                            <hr>
                            <h6>Variables globales</h6>
                            <div>
                                <code v-for="variable in variables.global" :key="'global-var-'+variable" class="mr-1">{{ variable }}</code>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <h5>Resultado</h5>
                            <div v-if="previewResult.warnings && previewResult.warnings.length" class="alert alert-warning">
                                <strong>Advertencias:</strong>
                                <ul class="mb-0">
                                    <li v-for="(warning, idx) in previewResult.warnings" :key="'preview-warning-'+idx">{{ warning }}</li>
                                </ul>
                            </div>
                            <div class="form-group">
                                <label>Asunto renderizado</label>
                                <input class="form-control" :value="previewResult.subject || ''" readonly>
                            </div>
                            <div class="form-group">
                                <label>HTML renderizado</label>
                                <iframe class="border rounded w-100" style="min-height:320px;background:#fff;" :srcdoc="previewResult.html || ''"></iframe>
                            </div>
                            <div class="form-group">
                                <label>Texto renderizado</label>
                                <pre class="bg-light border rounded p-3" style="max-height:260px;overflow:auto;">{{ previewResult.text || '-' }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-test" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-7">
                            <h5>Probar proveedor</h5>
                            <p class="text-muted">El proveedor deshabilitado simula exito. SMTP intenta envio real cuando no esta en simulacion.</p>
                            <div class="form-group">
                                <label>Proveedor</label>
                                <select class="form-control" v-model="testForm.provider_code">
                                    <option v-for="provider in providers" :key="provider.code" :value="provider.code">
                                        {{ provider.name }} ({{ provider.code }})
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Destinatario</label>
                                <input class="form-control" v-model="testForm.to_email" placeholder="correo@empresa.com">
                            </div>
                            <div class="form-group">
                                <label>Asunto</label>
                                <input class="form-control" v-model="testForm.subject">
                            </div>
                            <div class="form-group">
                                <label>Mensaje</label>
                                <textarea class="form-control" rows="4" v-model="testForm.message"></textarea>
                            </div>
                            <button class="btn btn-primary" :disabled="testing" @click="sendTestEmail">
                                <i class="bi bi-envelope-check mr-1"></i> Probar proveedor
                            </button>
                        </div>
                        <div class="col-lg-5">
                            <h5>Resultado seguro</h5>
                            <pre class="bg-light border rounded p-3" style="max-height:320px;overflow:auto;">{{ testResult }}</pre>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-lg-7">
                            <h5>Probar evento y destinatarios</h5>
                            <p class="text-muted">Resuelve reglas por evento/canal y ejecuta una prueba controlada.</p>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Evento</label>
                                    <select class="form-control" v-model="eventTestForm.event_code">
                                        <option value="">Selecciona evento</option>
                                        <option v-for="event in events" :key="event.code" :value="event.code">{{ event.name }} ({{ event.code }})</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Canal</label>
                                    <select class="form-control" v-model="eventTestForm.channel_code">
                                        <option value="internal">internal</option>
                                        <option value="email">email</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Proveedor</label>
                                    <select class="form-control" v-model="eventTestForm.provider_code">
                                        <option v-for="provider in providers" :key="provider.code" :value="provider.code">{{ provider.code }}</option>
                                    </select>
                                </div>
                            </div>
                            <button class="btn btn-outline-primary" :disabled="previewingRecipients" @click="previewRecipients">
                                Previsualizar destinatarios
                            </button>
                            <button class="btn btn-success" :disabled="testingEvent" @click="sendEventTest">
                                Enviar prueba de evento
                            </button>
                        </div>
                        <div class="col-lg-5">
                            <h5>Preview</h5>
                            <div class="border rounded p-3 bg-light">
                                <p><strong>Usuarios:</strong> {{ recipientPreview.users.length }}</p>
                                <ul>
                                    <li v-for="user in recipientPreview.user_labels" :key="'preview-user-'+user.id">{{ user.label }} <small class="text-muted">{{ user.email }}</small></li>
                                </ul>
                                <p><strong>Grupos:</strong> {{ recipientPreview.groups.length }}</p>
                                <ul>
                                    <li v-for="group in recipientPreview.group_labels" :key="'preview-group-'+group.id">{{ group.name }}</li>
                                </ul>
                                <p><strong>Emails:</strong> {{ recipientPreview.emails.length }}</p>
                                <ul>
                                    <li v-for="email in recipientPreview.emails" :key="'preview-email-'+email">{{ email }}</li>
                                </ul>
                                <p><strong>Excluidos:</strong> {{ recipientPreview.excluded_count }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-queue" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Cola reciente</h5>
                        <button class="btn btn-outline-primary btn-sm" :disabled="processingQueue" @click="processQueue">
                            <i class="bi bi-play-circle mr-1"></i> Procesar cola
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Proveedor</th>
                                    <th>Destinatario</th>
                                    <th>Asunto</th>
                                    <th>Estado</th>
                                    <th>Prioridad</th>
                                    <th>Intentos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in queue" :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ item.provider_code || '-' }}</td>
                                    <td>{{ item.to_email }}</td>
                                    <td>{{ item.subject }}</td>
                                    <td>{{ item.status }}</td>
                                    <td>{{ item.priority || '-' }}</td>
                                    <td>{{ item.attempts }}</td>
                                </tr>
                                <tr v-if="queue.length === 0">
                                    <td colspan="7" class="text-center text-muted">Sin correos recientes.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-attempts" role="tabpanel">
                    <h5 class="mb-3">Intentos recientes</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Queue ID</th>
                                    <th>Proveedor</th>
                                    <th>Transporte</th>
                                    <th>Estado</th>
                                    <th>Codigo</th>
                                    <th>Respuesta</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="attempt in recent_attempts" :key="attempt.queue_id + '-' + attempt.attempt_number + '-' + attempt.attempted_at">
                                    <td>{{ attempt.queue_id }}</td>
                                    <td>{{ attempt.provider_code }}</td>
                                    <td>{{ attempt.transport }}</td>
                                    <td>{{ attempt.status }}</td>
                                    <td>{{ attempt.response_code || '-' }}</td>
                                    <td>{{ attempt.response_message || '-' }}</td>
                                    <td>{{ formatDate(attempt.attempted_at) }}</td>
                                </tr>
                                <tr v-if="recent_attempts.length === 0">
                                    <td colspan="7" class="text-center text-muted">Sin intentos registrados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="composeModal.open" class="modal d-block" tabindex="-1" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form @submit.prevent="sendComposeMessage">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo correo</h5>
                        <button type="button" class="close" aria-label="Cerrar" @click="closeComposeModal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            El correo se encola para envio con la cuenta asignada. Los adjuntos se registran como metadatos seguros; la entrega binaria queda preparada para una fase posterior.
                        </div>
                        <div class="form-group">
                            <label>Cuenta de envio</label>
                            <select class="form-control" v-model.number="composeForm.account_id" required>
                                <option value="0">Selecciona cuenta</option>
                                <option v-for="account in sendableAccounts" :key="'compose-account-'+account.id" :value="account.id">
                                    {{ account.email_address }} - {{ account.name || account.code }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Para</label>
                            <input class="form-control" v-model="composeForm.to" placeholder="cliente@dominio.com; otro@dominio.com" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>CC</label>
                                <input class="form-control" v-model="composeForm.cc" placeholder="Opcional">
                            </div>
                            <div class="form-group col-md-6">
                                <label>BCC</label>
                                <input class="form-control" v-model="composeForm.bcc" placeholder="Opcional">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Asunto</label>
                            <input class="form-control" v-model="composeForm.subject" required>
                        </div>
                        <div class="form-group">
                            <label>Mensaje</label>
                            <textarea class="form-control" rows="7" v-model="composeForm.body_text" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Adjuntos</label>
                            <input type="file" class="form-control-file" multiple @change="handleComposeAttachments">
                            <small class="form-text text-muted">
                                Permitidos: PDF, imagenes, TXT, CSV, Word y Excel. Maximo 5 MB por archivo. No se permiten PHP, JS, HTML ni ejecutables.
                            </small>
                            <div v-if="composeFiles.length" class="attachment-selection mt-2">
                                <div v-for="(file, idx) in composeFiles" :key="'compose-file-'+idx" class="attachment-selection-item">
                                    <i class="bi bi-paperclip mr-1"></i>
                                    <span>{{ file.name }}</span>
                                    <small class="text-muted ml-2">{{ formatBytes(file.size) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="closeComposeModal">Cancelar</button>
                        <button class="btn btn-primary" :disabled="sendingMessage || sendableAccounts.length === 0">
                            <span v-if="sendingMessage" class="spinner-border spinner-border-sm mr-1"></span>
                            Encolar correo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div v-if="composeModal.open" class="modal-backdrop show"></div>

    <div v-if="replyModal.open" class="modal d-block" tabindex="-1" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form @submit.prevent="sendReplyMessage">
                    <div class="modal-header">
                        <h5 class="modal-title">Responder conversacion</h5>
                        <button type="button" class="close" aria-label="Cerrar" @click="closeReplyModal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            La respuesta se enviara al remitente del ultimo mensaje entrante visible. Los adjuntos se registran como metadatos seguros.
                        </div>
                        <div class="form-group">
                            <label>Cuenta de envio</label>
                            <select class="form-control" v-model.number="replyForm.account_id" required>
                                <option value="0">Selecciona cuenta</option>
                                <option v-for="account in sendableAccounts" :key="'reply-account-'+account.id" :value="account.id">
                                    {{ account.email_address }} - {{ account.name || account.code }}
                                </option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>CC</label>
                                <input class="form-control" v-model="replyForm.cc" placeholder="Opcional">
                            </div>
                            <div class="form-group col-md-6">
                                <label>BCC</label>
                                <input class="form-control" v-model="replyForm.bcc" placeholder="Opcional">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mensaje</label>
                            <textarea class="form-control" rows="7" v-model="replyForm.body_text" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Adjuntos</label>
                            <input type="file" class="form-control-file" multiple @change="handleReplyAttachments">
                            <small class="form-text text-muted">
                                Permitidos: PDF, imagenes, TXT, CSV, Word y Excel. Maximo 5 MB por archivo. No se permiten PHP, JS, HTML ni ejecutables.
                            </small>
                            <div v-if="replyFiles.length" class="attachment-selection mt-2">
                                <div v-for="(file, idx) in replyFiles" :key="'reply-file-'+idx" class="attachment-selection-item">
                                    <i class="bi bi-paperclip mr-1"></i>
                                    <span>{{ file.name }}</span>
                                    <small class="text-muted ml-2">{{ formatBytes(file.size) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="closeReplyModal">Cancelar</button>
                        <button class="btn btn-primary" :disabled="sendingMessage || sendableAccounts.length === 0">
                            <span v-if="sendingMessage" class="spinner-border spinner-border-sm mr-1"></span>
                            Encolar respuesta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div v-if="replyModal.open" class="modal-backdrop show"></div>
</div>

<style>
.conversation-center {
    display: grid;
    grid-template-columns: 180px minmax(280px, 380px) minmax(0, 1fr);
    gap: 12px;
    min-height: 620px;
}
.conversation-folders,
.conversation-list-panel,
.conversation-preview-panel {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: #fff;
}
.conversation-folders {
    padding: 8px;
}
.conversation-folder {
    width: 100%;
    border: 0;
    background: transparent;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 10px;
    border-radius: 5px;
    text-align: left;
}
.conversation-folder:hover,
.conversation-folder.active {
    background: #e9f3ff;
    color: #0056b3;
}
.conversation-list-panel {
    overflow: hidden;
}
.conversation-filters {
    border-bottom: 1px solid #e9ecef;
    padding: 10px;
}
.conversation-item {
    width: 100%;
    border: 0;
    border-bottom: 1px solid #eef0f2;
    background: #fff;
    text-align: left;
    padding: 12px;
    display: block;
}
.conversation-item:hover,
.conversation-item.active {
    background: #f4f8fc;
}
.conversation-item-head,
.conversation-message-head,
.conversation-pagination {
    display: flex;
    justify-content: space-between;
    gap: 8px;
}
.conversation-subject {
    font-weight: 600;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 220px;
}
.conversation-item-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 4px;
    color: #6c757d;
    font-size: 12px;
}
.conversation-participants,
.conversation-snippet {
    color: #6c757d;
    font-size: 12px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 3px;
}
.conversation-pagination {
    align-items: center;
    padding: 10px;
}
.conversation-preview-panel {
    padding: 14px;
    overflow: auto;
}
.conversation-preview-header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid #eef0f2;
    padding-bottom: 10px;
}
.conversation-empty {
    min-height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #6c757d;
    padding: 24px;
    gap: 6px;
}
.conversation-empty i {
    font-size: 28px;
    color: #adb5bd;
}
.conversation-message {
    border: 1px solid #eef0f2;
    border-radius: 6px;
    margin-top: 12px;
    padding: 12px;
    background: #fff;
}
.conversation-message-body {
    margin-top: 10px;
    color: #212529;
    overflow-wrap: anywhere;
}
.conversation-message-body img {
    max-width: 100%;
    height: auto;
}
.conversation-message-text {
    margin-top: 10px;
    white-space: pre-wrap;
    background: #f8f9fa;
    border: 1px solid #eef0f2;
    border-radius: 4px;
    padding: 10px;
    color: #343a40;
}
.conversation-attachments {
    margin-top: 12px;
    border-top: 1px solid #eef0f2;
    padding-top: 8px;
}
.attachment-selection {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 8px 10px;
    background: #f8f9fa;
}
.attachment-selection-item {
    display: flex;
    align-items: center;
    min-width: 0;
    padding: 2px 0;
    font-size: 13px;
}
.attachment-selection-item span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.conversation-attachment {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    color: #495057;
    font-size: 13px;
    padding-top: 4px;
}
@media (max-width: 1199px) {
    .conversation-center {
        grid-template-columns: 150px minmax(250px, 340px) minmax(0, 1fr);
    }
}
@media (max-width: 991px) {
    .conversation-center {
        grid-template-columns: 1fr;
    }
    .conversation-folders {
        display: flex;
        gap: 6px;
        overflow-x: auto;
    }
    .conversation-folder {
        width: auto;
        white-space: nowrap;
    }
}
</style>

<script src="<?php echo Uri::base(false); ?>assets/js/core-api-client.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Vue({
        el: '#app-communications',
        data: {
            loading: true,
            conversationLoading: false,
            conversationDetailLoading: false,
            myMailboxLoading: false,
            myMailboxDetailLoading: false,
            testing: false,
            testingAccount: false,
            syncingAccount: false,
            savingProvider: false,
            savingAccount: false,
            savingAssignment: false,
            processingQueue: false,
            statusMessage: '',
            statusOk: true,
            statusErrors: [],
            events: [],
            users: [],
            groups: [],
            roles: [],
            recipient_rules: [],
            providers: [],
            accounts: [],
            account_assignments: [],
            my_accounts: [],
            myMailboxAccounts: [],
            myMailboxConversations: [],
            conversations: [],
            conversationFolders: [
                { code: 'inbox', label: 'Inbox', icon: 'bi bi-inbox' },
                { code: 'sent', label: 'Sent', icon: 'bi bi-send' },
                { code: 'drafts', label: 'Drafts', icon: 'bi bi-file-earmark' },
                { code: 'trash', label: 'Trash', icon: 'bi bi-trash' },
                { code: 'assigned', label: 'Asignadas', icon: 'bi bi-person-check' },
                { code: 'favorites', label: 'Favoritas', icon: 'bi bi-star' }
            ],
            conversationChannels: [],
            conversationFilters: {
                folder: 'inbox',
                q: '',
                unread: 0,
                assigned: 0,
                channel: '',
                date_from: '',
                date_to: '',
                page: 1,
                per_page: 15
            },
            conversationPagination: { page: 1, per_page: 15, total: 0, pages: 1 },
            myMailboxChannels: [],
            myMailboxFilters: {
                folder: 'inbox',
                q: '',
                unread: 0,
                assigned: 0,
                channel: '',
                account_id: 0,
                date_from: '',
                date_to: '',
                page: 1,
                per_page: 15
            },
            myMailboxPagination: { page: 1, per_page: 15, total: 0, pages: 1 },
            selectedConversation: null,
            conversationDetail: {},
            conversationMessages: [],
            selectedMyMailboxConversation: null,
            myMailboxDetail: {},
            myMailboxMessages: [],
            sendingMessage: false,
            composeFiles: [],
            replyFiles: [],
            composeModal: { open: false },
            replyModal: { open: false },
            composeForm: {
                account_id: 0,
                to: '',
                cc: '',
                bcc: '',
                subject: '',
                body_text: '',
                body_html: '',
                related_entity_type: '',
                related_entity_id: 0,
                related_party_id: 0
            },
            replyForm: {
                conversation_id: 0,
                account_id: 0,
                cc: '',
                bcc: '',
                body_text: '',
                body_html: ''
            },
            selectedAccount: null,
            accountForm: {},
            assignmentForm: {
                account_id: 0,
                assignment_type: 'user',
                assignment_value: '',
                access_level: 'viewer',
                can_send: 0,
                can_receive: 1,
                can_sync: 0,
                can_manage: 0,
                default_sender: 0,
                active: 1
            },
            imap_defaults: { inbox: 'INBOX', sent: 'Sent', drafts: 'Drafts', trash: 'Trash' },
            imap_capabilities: {},
            templates: [],
            layouts: [],
            variables: { global: [], sample: {} },
            recent_attempts: [],
            queue: [],
            selectedProvider: null,
            providerForm: {},
            templateForm: {},
            layoutForm: {},
            savingRecipient: false,
            savingTemplate: false,
            savingLayout: false,
            previewingRecipients: false,
            previewingTemplate: false,
            testingEvent: false,
            recipientForm: {
                id: 0,
                event_code: '',
                channel_code: 'internal',
                recipient_type: 'user',
                recipient_value: '',
                mode: 'include',
                active: 1
            },
            eventTestForm: {
                event_code: '',
                channel_code: 'internal',
                provider_code: 'disabled_default'
            },
            recipientPreview: {
                users: [],
                emails: [],
                groups: [],
                excluded_count: 0,
                user_labels: [],
                group_labels: []
            },
            queue_summary: { pending: 0, sent: 0, failed: 0, simulated: 0, processing: 0, last_errors: 0 },
            stats: { events: 0, notifications: 0, unread: 0, emails_pending: 0, emails_failed: 0, emails_sent: 0, emails_processing: 0, providers: 0, accounts: 0, attempts: 0 },
            previewForm: {
                template_id: '',
                layout_code: '',
                variables_json: '{}'
            },
            previewResult: {
                subject: '',
                html: '',
                text: '',
                warnings: []
            },
            testResult: '-',
            testForm: {
                provider_code: 'disabled_default',
                to_email: '',
                subject: 'Prueba del Centro de Comunicaciones',
                message: 'Mensaje de prueba.'
            }
        },
        mounted() {
            this.accountForm = this.defaultAccountForm();
            this.loadData();
        },
        computed: {
            sendableAccounts() {
                return (this.my_accounts || this.myMailboxAccounts || []).filter(account => parseInt(account.can_send || 0, 10) === 1);
            },
            filteredAssignments() {
                if (!this.accountForm || !this.accountForm.id) {
                    return this.account_assignments || [];
                }

                return (this.account_assignments || []).filter(assignment => {
                    return parseInt(assignment.account_id || 0, 10) === parseInt(this.accountForm.id || 0, 10);
                });
            }
        },
        methods: {
            loadData() {
                this.loading = true;
                this.apiGet('<?php echo Uri::create('admin/communications/data'); ?>')
                    .then(result => {
                        const data = result.data || {};
                        if (data.success === false) {
                            this.showStatus(false, data.message || 'No se pudo cargar comunicaciones.', data.errors || []);
                            return;
                        }
                        if (data.error) {
                            this.showStatus(false, data.error, data.errors || []);
                            return;
                        }
                        this.applyPayload(data);
                        this.loadConversations();
                        this.loadMyMailbox();
                    })
                    .catch(error => {
                        this.showStatus(false, 'No se pudo cargar comunicaciones.', [String(error)]);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            apiGet(url, options) {
                return window.CoreApiClient.get(url, options || {}).then(result => ({
                    status: result.status,
                    ok: result.ok,
                    code: result.code,
                    message: result.message,
                    errors: result.errors,
                    data: this.normalizeApiPayload(result)
                }));
            },
            apiPost(url, data, options) {
                return window.CoreApiClient.post(url, data || {}, options || {}).then(result => ({
                    status: result.status,
                    ok: result.ok,
                    code: result.code,
                    message: result.message,
                    errors: result.errors,
                    data: this.normalizeApiPayload(result)
                }));
            },
            normalizeApiPayload(result) {
                const payload = result.payload && typeof result.payload === 'object' ? Object.assign({}, result.payload) : {};
                if (typeof payload.success === 'undefined') {
                    payload.success = !!result.ok;
                }
                if (!payload.message) {
                    payload.message = result.message || '';
                }
                if (!Array.isArray(payload.errors)) {
                    payload.errors = result.errors || [];
                }
                return payload;
            },
            applyPayload(data) {
                this.events = data.events || this.events;
                this.users = data.users || this.users;
                this.groups = data.groups || this.groups;
                this.roles = data.roles || this.roles;
                this.recipient_rules = data.recipient_rules || this.recipient_rules;
                this.providers = data.providers || this.providers;
                this.accounts = data.accounts || this.accounts;
                this.account_assignments = data.account_assignments || this.account_assignments;
                this.my_accounts = data.my_accounts || this.my_accounts;
                this.myMailboxAccounts = data.my_accounts || this.myMailboxAccounts;
                this.conversations = data.conversations || this.conversations;
                if (data.conversation_center) {
                    this.applyConversationCenter(data.conversation_center);
                }
                this.imap_defaults = data.imap_defaults || this.imap_defaults;
                this.imap_capabilities = data.imap_capabilities || this.imap_capabilities;
                this.templates = data.templates || this.templates;
                this.layouts = data.layouts || this.layouts;
                this.variables = data.variables || this.variables;
                this.recent_attempts = data.recent_attempts || this.recent_attempts;
                this.queue = data.queue || this.queue;
                this.queue_summary = data.queue_summary || this.queue_summary;
                this.stats = data.stats || this.stats;
                if (this.variables.sample && this.previewForm.variables_json === '{}') {
                    this.previewForm.variables_json = JSON.stringify(this.variables.sample, null, 2);
                }
                if (this.templates.length && !this.previewForm.template_id) {
                    this.previewForm.template_id = this.templates[0].id;
                }
                if (this.layouts.length && !this.previewForm.layout_code) {
                    this.previewForm.layout_code = this.layouts[0].code;
                }
                if (this.providers.length && !this.testForm.provider_code) {
                    this.testForm.provider_code = this.providers[0].code;
                }
                if (this.providers.length && !this.eventTestForm.provider_code) {
                    this.eventTestForm.provider_code = this.providers[0].code;
                }
                if (this.selectedProvider) {
                    const updated = this.providers.find(provider => provider.id == this.selectedProvider.id);
                    if (updated) {
                        this.selectProvider(updated);
                    }
                }
                if (this.selectedAccount) {
                    const updatedAccount = this.accounts.find(account => account.id == this.selectedAccount.id);
                    if (updatedAccount) {
                        this.selectAccount(updatedAccount);
                    }
                }
                if (this.templateForm.id) {
                    const updatedTemplate = this.templates.find(template => template.id == this.templateForm.id);
                    if (updatedTemplate) {
                        this.selectTemplate(updatedTemplate);
                    }
                }
                if (this.layoutForm.id) {
                    const updatedLayout = this.layouts.find(layout => layout.id == this.layoutForm.id);
                    if (updatedLayout) {
                        this.selectLayout(updatedLayout);
                    }
                }
            },
            loadConversations() {
                this.conversationLoading = true;
                const params = new URLSearchParams();
                Object.keys(this.conversationFilters).forEach(key => {
                    if (this.conversationFilters[key] !== '' && this.conversationFilters[key] !== null) {
                        params.append(key, this.conversationFilters[key]);
                    }
                });

                this.apiGet('<?php echo Uri::create('admin/communications/conversationlist'); ?>?' + params.toString())
                    .then(result => {
                        this.conversationLoading = false;
                        const data = result.data || {};
                        if (!data.success) {
                            this.showStatus(false, data.message || 'No se pudieron cargar conversaciones.', data.errors || []);
                            return;
                        }
                        this.applyConversationCenter(data.data || {});
                    })
                    .catch(error => {
                        this.showStatus(false, 'No se pudieron cargar conversaciones.', [String(error)]);
                    })
                    .finally(() => {
                        this.conversationLoading = false;
                    });
            },
            applyConversationCenter(payload) {
                this.conversations = payload.items || [];
                this.conversationPagination = payload.pagination || this.conversationPagination;
                this.conversationFolders = payload.folders || this.conversationFolders;
                this.conversationChannels = payload.channels || this.conversationChannels;
                if (this.selectedConversation) {
                    const selected = this.conversations.find(item => item.id === this.selectedConversation.id);
                    if (!selected) {
                        this.selectedConversation = null;
                        this.conversationDetail = {};
                        this.conversationMessages = [];
                    } else {
                        this.selectedConversation = selected;
                    }
                }
            },
            loadMyMailbox() {
                this.myMailboxLoading = true;
                const params = new URLSearchParams();
                Object.keys(this.myMailboxFilters).forEach(key => {
                    if (this.myMailboxFilters[key] !== '' && this.myMailboxFilters[key] !== null) {
                        params.append(key, this.myMailboxFilters[key]);
                    }
                });

                this.apiGet('<?php echo Uri::create('admin/communications/my_mailbox'); ?>?' + params.toString())
                    .then(result => {
                        const data = result.data || {};
                        if (!data.success) {
                            this.showStatus(false, data.message || 'No se pudo cargar Mi bandeja.', data.errors || []);
                            return;
                        }
                        this.applyMyMailbox(data.data || {});
                    })
                    .catch(error => {
                        this.showStatus(false, 'No se pudo cargar Mi bandeja.', [String(error)]);
                    })
                    .finally(() => {
                        this.myMailboxLoading = false;
                    });
            },
            applyMyMailbox(payload) {
                this.myMailboxAccounts = payload.accounts || [];
                this.myMailboxConversations = payload.items || [];
                this.myMailboxPagination = payload.pagination || this.myMailboxPagination;
                this.myMailboxChannels = payload.channels || this.myMailboxChannels;
                if (this.selectedMyMailboxConversation) {
                    const selected = this.myMailboxConversations.find(item => item.id === this.selectedMyMailboxConversation.id);
                    if (!selected) {
                        this.selectedMyMailboxConversation = null;
                        this.myMailboxDetail = {};
                        this.myMailboxMessages = [];
                    } else {
                        this.selectedMyMailboxConversation = selected;
                    }
                }
            },
            selectMyMailboxFolder(folder) {
                this.myMailboxFilters.folder = folder;
                this.myMailboxFilters.page = 1;
                this.selectedMyMailboxConversation = null;
                this.myMailboxDetail = {};
                this.myMailboxMessages = [];
                this.loadMyMailbox();
            },
            searchMyMailbox() {
                this.myMailboxFilters.page = 1;
                this.loadMyMailbox();
            },
            clearMyMailboxFilters() {
                this.myMailboxFilters.q = '';
                this.myMailboxFilters.unread = 0;
                this.myMailboxFilters.assigned = 0;
                this.myMailboxFilters.channel = '';
                this.myMailboxFilters.account_id = 0;
                this.myMailboxFilters.date_from = '';
                this.myMailboxFilters.date_to = '';
                this.myMailboxFilters.page = 1;
                this.loadMyMailbox();
            },
            changeMyMailboxPage(page) {
                this.myMailboxFilters.page = Math.max(1, page);
                this.loadMyMailbox();
            },
            selectMyMailboxConversation(conversation) {
                this.selectedMyMailboxConversation = conversation;
                this.myMailboxDetailLoading = true;
                this.apiGet('<?php echo Uri::create('admin/communications/my_mailbox_detail'); ?>/' + conversation.id)
                    .then(result => {
                        const data = result.data || {};
                        if (!data.success) {
                            this.showStatus(false, data.message || 'No se pudo cargar la conversacion.', data.errors || []);
                            return;
                        }
                        this.myMailboxDetail = data.data || {};
                        this.selectedMyMailboxConversation = this.myMailboxDetail.conversation || conversation;
                        this.myMailboxMessages = this.myMailboxDetail.messages || [];
                    })
                    .catch(error => {
                        this.showStatus(false, 'No se pudo cargar la conversacion.', [String(error)]);
                    })
                    .finally(() => {
                        this.myMailboxDetailLoading = false;
                    });
            },
            selectConversationFolder(folder) {
                this.conversationFilters.folder = folder;
                this.conversationFilters.page = 1;
                this.selectedConversation = null;
                this.conversationDetail = {};
                this.conversationMessages = [];
                this.loadConversations();
            },
            searchConversations() {
                this.conversationFilters.page = 1;
                this.loadConversations();
            },
            clearConversationFilters() {
                this.conversationFilters.q = '';
                this.conversationFilters.unread = 0;
                this.conversationFilters.assigned = 0;
                this.conversationFilters.channel = '';
                this.conversationFilters.date_from = '';
                this.conversationFilters.date_to = '';
                this.conversationFilters.page = 1;
                this.loadConversations();
            },
            changeConversationPage(page) {
                this.conversationFilters.page = Math.max(1, page);
                this.loadConversations();
            },
            selectConversation(conversation) {
                this.selectedConversation = conversation;
                this.conversationDetailLoading = true;
                this.apiGet('<?php echo Uri::create('admin/communications/conversationdetail'); ?>/' + conversation.id)
                    .then(result => {
                        this.conversationDetailLoading = false;
                        const data = result.data || {};
                        if (!data.success) {
                            this.showStatus(false, data.message || 'No se pudo cargar la conversación.', data.errors || []);
                            return;
                        }
                        this.conversationDetail = data.data || {};
                        this.selectedConversation = this.conversationDetail.conversation || conversation;
                        this.conversationMessages = this.conversationDetail.messages || [];
                    })
                    .catch(error => {
                        this.showStatus(false, 'No se pudo cargar la conversación.', [String(error)]);
                    })
                    .finally(() => {
                        this.conversationDetailLoading = false;
                    });
            },
            defaultSenderAccountId() {
                const defaultAccount = this.sendableAccounts.find(account => parseInt(account.default_sender || 0, 10) === 1);
                if (defaultAccount) {
                    return parseInt(defaultAccount.id || 0, 10);
                }
                return this.sendableAccounts.length ? parseInt(this.sendableAccounts[0].id || 0, 10) : 0;
            },
            openComposeModal() {
                this.composeForm = {
                    account_id: this.defaultSenderAccountId(),
                    to: '',
                    cc: '',
                    bcc: '',
                    subject: '',
                    body_text: '',
                    body_html: '',
                    related_entity_type: '',
                    related_entity_id: 0,
                    related_party_id: 0
                };
                this.composeFiles = [];
                this.composeModal.open = true;
            },
            closeComposeModal() {
                if (!this.sendingMessage) {
                    this.composeModal.open = false;
                    this.composeFiles = [];
                }
            },
            openReplyModal(conversation) {
                if (!conversation) {
                    return;
                }
                let accountId = this.defaultSenderAccountId();
                const conversationAccountId = parseInt(conversation.account_id || 0, 10);
                if (conversationAccountId > 0 && this.sendableAccounts.some(account => parseInt(account.id || 0, 10) === conversationAccountId)) {
                    accountId = conversationAccountId;
                }
                this.replyForm = {
                    conversation_id: parseInt(conversation.id || 0, 10),
                    account_id: accountId,
                    cc: '',
                    bcc: '',
                    body_text: '',
                    body_html: ''
                };
                this.replyFiles = [];
                this.replyModal.open = true;
            },
            closeReplyModal() {
                if (!this.sendingMessage) {
                    this.replyModal.open = false;
                    this.replyFiles = [];
                }
            },
            handleComposeAttachments(event) {
                this.composeFiles = Array.prototype.slice.call((event.target && event.target.files) || []);
            },
            handleReplyAttachments(event) {
                this.replyFiles = Array.prototype.slice.call((event.target && event.target.files) || []);
            },
            messageFormData(form, files) {
                const data = new FormData();
                Object.keys(form).forEach(key => {
                    const value = form[key];
                    data.append(key, value === null || typeof value === 'undefined' ? '' : value);
                });
                (files || []).forEach(file => {
                    data.append('attachments[]', file, file.name);
                });
                return data;
            },
            sendComposeMessage() {
                this.sendingMessage = true;
                this.apiPost('<?php echo Uri::create('admin/communications/compose_message'); ?>', this.messageFormData(this.composeForm, this.composeFiles))
                    .then(result => {
                        const data = result.data || {};
                        if (!data.success) {
                            this.showStatus(false, data.message || 'No se pudo encolar el correo.', data.errors || []);
                            return;
                        }
                        this.composeModal.open = false;
                        this.composeFiles = [];
                        this.showStatus(true, data.message || 'Correo encolado correctamente.', []);
                        this.loadConversations();
                        this.loadMyMailbox();
                    })
                    .catch(error => {
                        this.showStatus(false, 'No se pudo encolar el correo.', [String(error)]);
                    })
                    .finally(() => {
                        this.sendingMessage = false;
                    });
            },
            sendReplyMessage() {
                this.sendingMessage = true;
                this.apiPost('<?php echo Uri::create('admin/communications/reply_conversation'); ?>', this.messageFormData(this.replyForm, this.replyFiles))
                    .then(result => {
                        const data = result.data || {};
                        if (!data.success) {
                            this.showStatus(false, data.message || 'No se pudo encolar la respuesta.', data.errors || []);
                            return;
                        }
                        this.replyModal.open = false;
                        this.replyFiles = [];
                        this.showStatus(true, data.message || 'Respuesta encolada correctamente.', []);
                        this.loadConversations();
                        this.loadMyMailbox();
                        if (this.selectedConversation) {
                            this.selectConversation(this.selectedConversation);
                        }
                    })
                    .catch(error => {
                        this.showStatus(false, 'No se pudo encolar la respuesta.', [String(error)]);
                    })
                    .finally(() => {
                        this.sendingMessage = false;
                    });
            },
            formatRecipient(recipient) {
                if (!recipient) return '';
                return recipient.name || recipient.email || '';
            },
            formatBytes(bytes) {
                const value = parseInt(bytes || 0, 10);
                if (!value) return '0 B';
                if (value < 1024) return value + ' B';
                if (value < 1048576) return (value / 1024).toFixed(1) + ' KB';
                return (value / 1048576).toFixed(1) + ' MB';
            },
            selectProvider(provider) {
                this.selectedProvider = provider;
                this.providerForm = Object.assign({}, provider, {
                    new_password: '',
                    new_api_key: ''
                });
            },
            prepareTest(provider) {
                this.testForm.provider_code = provider.code;
                this.eventTestForm.provider_code = provider.code;
                this.showStatus(true, 'Proveedor seleccionado para prueba: ' + provider.name, []);
            },
            saveProvider() {
                this.savingProvider = true;
                this.apiPost('<?php echo Uri::create('admin/communications/save_provider'); ?>', this.providerForm)
                .then(result => {
                    this.savingProvider = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.providers = data.data.providers || this.providers;
                        this.stats = data.data.stats || this.stats;
                    }
                    this.showStatus(!!data.success, data.message || 'Proveedor procesado.', data.errors || []);
                })
                .catch(error => {
                    this.savingProvider = false;
                    this.showStatus(false, 'No se pudo guardar el proveedor.', [String(error)]);
                });
            },
            defaultAccountForm() {
                return {
                    id: 0,
                    code: '',
                    name: '',
                    email_address: '',
                    account_type: 'support',
                    owner_user_id: 0,
                    owner_group_id: 0,
                    mailbox_scope: 'system',
                    provider_code: '',
                    smtp_provider_code: '',
                    imap_provider_code: 'imap_default',
                    imap_host: '',
                    imap_port: 993,
                    imap_encryption: 'ssl',
                    imap_username: '',
                    new_imap_password: '',
                    imap_password_configured: false,
                    imap_folder_inbox: this.imap_defaults.inbox || 'INBOX',
                    imap_folder_sent: this.imap_defaults.sent || 'Sent',
                    imap_folder_drafts: this.imap_defaults.drafts || 'Drafts',
                    imap_folder_trash: this.imap_defaults.trash || 'Trash',
                    sync_inbox: 1,
                    sync_sent: 0,
                    sync_drafts: 0,
                    sync_trash: 0,
                    append_sent: 0,
                    sync_enabled: 0,
                    active: 0
                };
            },
            newAccount() {
                this.selectedAccount = null;
                this.accountForm = this.defaultAccountForm();
                this.resetAssignmentForm();
            },
            selectAccount(account) {
                this.selectedAccount = account;
                this.accountForm = Object.assign(this.defaultAccountForm(), account, {
                    new_imap_password: ''
                });
                this.resetAssignmentForm();
                this.assignmentForm.account_id = this.accountForm.id || 0;
            },
            saveAccount() {
                this.savingAccount = true;
                this.apiPost('<?php echo Uri::create('admin/communications/save_account'); ?>', this.accountForm)
                .then(result => {
                    this.savingAccount = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.accounts = data.data.accounts || this.accounts;
                        this.account_assignments = data.data.account_assignments || this.account_assignments;
                        this.my_accounts = data.data.my_accounts || this.my_accounts;
                        this.stats = data.data.stats || this.stats;
                    }
                    this.showStatus(!!data.success, data.message || 'Cuenta procesada.', data.errors || []);
                    if (data.success && this.accountForm.code) {
                        const updated = this.accounts.find(account => account.code === this.accountForm.code);
                        if (updated) {
                            this.selectAccount(updated);
                        }
                    }
                })
                .catch(error => {
                    this.savingAccount = false;
                    this.showStatus(false, 'No se pudo guardar la cuenta.', [String(error)]);
                });
            },
            resetAssignmentForm() {
                this.assignmentForm = {
                    account_id: this.accountForm && this.accountForm.id ? this.accountForm.id : 0,
                    assignment_type: 'user',
                    assignment_value: '',
                    access_level: 'viewer',
                    can_send: 0,
                    can_receive: 1,
                    can_sync: 0,
                    can_manage: 0,
                    default_sender: 0,
                    active: 1
                };
            },
            editAccountAssignment(assignment) {
                this.assignmentForm = Object.assign({}, assignment, {
                    assignment_value: String(assignment.assignment_value || '')
                });
                const account = this.accounts.find(item => item.id == assignment.account_id);
                if (account) {
                    this.selectAccount(account);
                    this.assignmentForm = Object.assign({}, assignment, {
                        assignment_value: String(assignment.assignment_value || '')
                    });
                }
            },
            saveAccountAssignment() {
                if (!this.accountForm.id) {
                    this.showStatus(false, 'Selecciona una cuenta antes de asignarla.', []);
                    return;
                }

                this.savingAssignment = true;
                const payload = Object.assign({}, this.assignmentForm, {
                    account_id: this.accountForm.id
                });

                this.apiPost('<?php echo Uri::create('admin/communications/save_account_assignment'); ?>', payload)
                .then(result => {
                    this.savingAssignment = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.account_assignments = data.data.account_assignments || this.account_assignments;
                        this.my_accounts = data.data.my_accounts || this.my_accounts;
                        this.accounts = data.data.accounts || this.accounts;
                    }
                    this.showStatus(!!data.success, data.message || 'Asignacion procesada.', data.errors || []);
                    if (data.success) {
                        this.resetAssignmentForm();
                    }
                })
                .catch(error => {
                    this.savingAssignment = false;
                    this.showStatus(false, 'No se pudo guardar la asignacion.', [String(error)]);
                });
            },
            revokeAccountAssignment(assignment) {
                this.savingAssignment = true;
                this.apiPost('<?php echo Uri::create('admin/communications/revoke_account_assignment'); ?>', { id: assignment.id })
                .then(result => {
                    this.savingAssignment = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.account_assignments = data.data.account_assignments || this.account_assignments;
                        this.my_accounts = data.data.my_accounts || this.my_accounts;
                        this.accounts = data.data.accounts || this.accounts;
                    }
                    this.showStatus(!!data.success, data.message || 'Asignacion actualizada.', data.errors || []);
                })
                .catch(error => {
                    this.savingAssignment = false;
                    this.showStatus(false, 'No se pudo desactivar la asignacion.', [String(error)]);
                });
            },
            accountLabel(accountId) {
                const account = this.accounts.find(item => item.id == accountId);
                return account ? (account.name + ' (' + account.code + ')') : ('Cuenta #' + accountId);
            },
            testImapAccount(account) {
                this.testingAccount = true;
                this.apiPost('<?php echo Uri::create('admin/communications/test_imap_account'); ?>', { id: account.id })
                .then(result => {
                    this.testingAccount = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.accounts = data.data.accounts || this.accounts;
                    }
                    this.showStatus(!!data.success, data.message || 'Prueba IMAP procesada.', data.errors || []);
                })
                .catch(error => {
                    this.testingAccount = false;
                    this.showStatus(false, 'No se pudo probar IMAP.', [String(error)]);
                });
            },
            syncImapAccount(account) {
                this.syncingAccount = true;
                this.apiPost('<?php echo Uri::create('admin/communications/sync_imap_account'); ?>', { id: account.id, limit: 20 })
                .then(result => {
                    this.syncingAccount = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.accounts = data.data.accounts || this.accounts;
                        this.conversations = data.data.conversations || this.conversations;
                        this.stats = data.data.stats || this.stats;
                    }
                    this.showStatus(!!data.success, data.message || 'Sincronizacion IMAP procesada.', data.errors || []);
                })
                .catch(error => {
                    this.syncingAccount = false;
                    this.showStatus(false, 'No se pudo sincronizar IMAP.', [String(error)]);
                });
            },
            selectTemplate(template) {
                this.templateForm = Object.assign({}, template);
                this.previewForm.template_id = template.id;
            },
            saveTemplate() {
                this.savingTemplate = true;
                this.apiPost('<?php echo Uri::create('admin/communications/save_template'); ?>', this.templateForm)
                .then(result => {
                    this.savingTemplate = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.templates = data.data.templates || this.templates;
                        this.variables = data.data.variables || this.variables;
                    }
                    this.showStatus(!!data.success, data.message || 'Template procesado.', data.errors || []);
                })
                .catch(error => {
                    this.savingTemplate = false;
                    this.showStatus(false, 'No se pudo guardar el template.', [String(error)]);
                });
            },
            selectLayout(layout) {
                this.layoutForm = Object.assign({}, layout);
                this.previewForm.layout_code = layout.code;
            },
            saveLayout() {
                this.savingLayout = true;
                this.apiPost('<?php echo Uri::create('admin/communications/save_layout'); ?>', this.layoutForm)
                .then(result => {
                    this.savingLayout = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.layouts = data.data.layouts || this.layouts;
                        this.variables = data.data.variables || this.variables;
                    }
                    this.showStatus(!!data.success, data.message || 'Layout procesado.', data.errors || []);
                })
                .catch(error => {
                    this.savingLayout = false;
                    this.showStatus(false, 'No se pudo guardar el layout.', [String(error)]);
                });
            },
            previewTemplate() {
                this.previewingTemplate = true;
                let variables = {};
                try {
                    variables = this.previewForm.variables_json ? JSON.parse(this.previewForm.variables_json) : {};
                } catch (error) {
                    this.previewingTemplate = false;
                    this.showStatus(false, 'El JSON de variables no es valido.', [String(error)]);
                    return;
                }

                this.apiPost('<?php echo Uri::create('admin/communications/preview_template'); ?>', {
                    template_id: this.previewForm.template_id,
                    layout_code: this.previewForm.layout_code,
                    variables: variables
                })
                .then(result => {
                    this.previewingTemplate = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.previewResult = Object.assign({}, this.previewResult, data.data);
                    }
                    this.showStatus(!!data.success, data.message || 'Vista previa procesada.', data.errors || []);
                })
                .catch(error => {
                    this.previewingTemplate = false;
                    this.showStatus(false, 'No se pudo generar la vista previa.', [String(error)]);
                });
            },
            sendTestEmail() {
                this.testing = true;
                this.testResult = 'Procesando...';
                this.apiPost('<?php echo Uri::create('admin/communications/test_email'); ?>', this.testForm)
                .then(result => {
                    this.testing = false;
                    const data = result.data || {};
                    this.testResult = JSON.stringify(data, null, 2);
                    if (data.data) {
                        this.applyPayload(data.data);
                    }
                    this.showStatus(!!data.success, data.message || 'Prueba procesada.', data.errors || []);
                })
                .catch(error => {
                    this.testing = false;
                    this.testResult = String(error);
                    this.showStatus(false, 'No se pudo ejecutar la prueba.', [String(error)]);
                });
            },
            processQueue() {
                this.processingQueue = true;
                this.apiPost('<?php echo Uri::create('admin/communications/process_queue'); ?>', { limit: 10 })
                .then(result => {
                    this.processingQueue = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.applyPayload(data.data);
                    }
                    this.showStatus(!!data.success, data.message || 'Cola procesada.', data.errors || []);
                })
                .catch(error => {
                    this.processingQueue = false;
                    this.showStatus(false, 'No se pudo procesar la cola.', [String(error)]);
                });
            },
            editRecipientRule(rule) {
                this.recipientForm = Object.assign({}, rule);
            },
            resetRecipientForm() {
                this.recipientForm = {
                    id: 0,
                    event_code: '',
                    channel_code: 'internal',
                    recipient_type: 'user',
                    recipient_value: '',
                    mode: 'include',
                    active: 1
                };
            },
            saveRecipientRule() {
                this.savingRecipient = true;
                this.apiPost('<?php echo Uri::create('admin/communications/save_recipient_rule'); ?>', this.recipientForm)
                .then(result => {
                    this.savingRecipient = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.recipient_rules = data.data.recipient_rules || this.recipient_rules;
                    }
                    this.showStatus(!!data.success, data.message || 'Regla procesada.', data.errors || []);
                    if (data.success) {
                        this.resetRecipientForm();
                    }
                })
                .catch(error => {
                    this.savingRecipient = false;
                    this.showStatus(false, 'No se pudo guardar la regla.', [String(error)]);
                });
            },
            toggleRecipientRule(rule) {
                this.apiPost('<?php echo Uri::create('admin/communications/toggle_recipient_rule'); ?>', { id: rule.id, active: rule.active == 1 ? 0 : 1 })
                .then(result => {
                    const data = result.data || {};
                    if (data.data) {
                        this.recipient_rules = data.data.recipient_rules || this.recipient_rules;
                    }
                    this.showStatus(!!data.success, data.message || 'Regla actualizada.', data.errors || []);
                })
                .catch(error => {
                    this.showStatus(false, 'No se pudo actualizar la regla.', [String(error)]);
                });
            },
            previewRecipients() {
                this.previewingRecipients = true;
                this.apiPost('<?php echo Uri::create('admin/communications/preview_recipients'); ?>', this.eventTestForm)
                .then(result => {
                    this.previewingRecipients = false;
                    const data = result.data || {};
                    if (data.data) {
                        this.recipientPreview = Object.assign({}, this.recipientPreview, data.data);
                    }
                    this.showStatus(!!data.success, data.message || 'Destinatarios resueltos.', data.errors || []);
                })
                .catch(error => {
                    this.previewingRecipients = false;
                    this.showStatus(false, 'No se pudo resolver destinatarios.', [String(error)]);
                });
            },
            sendEventTest() {
                this.testingEvent = true;
                this.apiPost('<?php echo Uri::create('admin/communications/test_event'); ?>', this.eventTestForm)
                .then(result => {
                    this.testingEvent = false;
                    const data = result.data || {};
                    if (data.data) {
                        if (data.data.preview) {
                            this.recipientPreview = Object.assign({}, this.recipientPreview, data.data.preview);
                        }
                        this.applyPayload(data.data);
                    }
                    this.showStatus(!!data.success, data.message || 'Prueba de evento procesada.', data.errors || []);
                })
                .catch(error => {
                    this.testingEvent = false;
                    this.showStatus(false, 'No se pudo probar el evento.', [String(error)]);
                });
            },
            formatDate(timestamp) {
                const value = parseInt(timestamp || 0, 10);
                if (!value) return '-';
                return new Date(value * 1000).toLocaleString();
            },
            showStatus(ok, message, errors) {
                this.statusOk = !!ok;
                this.statusMessage = message || (ok ? 'Operación realizada.' : 'No se pudo completar la operación.');
                this.statusErrors = errors || [];
            }
        }
    });
});
</script>
