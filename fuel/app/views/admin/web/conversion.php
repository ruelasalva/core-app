<div id="app-web-conversion" v-cloak>
    <div class="alert" :class="messageType === 'error' ? 'alert-danger' : 'alert-success'" v-if="message">
        {{ message }}
        <ul v-if="errors.length" class="mb-0 mt-2">
            <li v-for="error in errors" :key="error">{{ error }}</li>
        </ul>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#tab-whatsapp" role="tab">WhatsApp</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-messenger" role="tab">Messenger</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-mobile-cta" role="tab">CTA móvil</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-product" role="tab">Producto</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-trust" role="tab">Confianza</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-leads" role="tab">Leads</a></li>
            </ul>
        </div>

        <div class="card-body">
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando configuración...</p>
            </div>

            <div v-show="!loading" class="tab-content">
                <div class="tab-pane fade show active" id="tab-whatsapp" role="tabpanel">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="custom-control custom-switch mt-2">
                                <input id="whatsapp-enabled" class="custom-control-input" type="checkbox" v-model="settings.whatsapp.enabled">
                                <label class="custom-control-label" for="whatsapp-enabled">Activar WhatsApp</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-control custom-switch mt-2">
                                <input id="whatsapp-mobile" class="custom-control-input" type="checkbox" v-model="settings.whatsapp.show_mobile">
                                <label class="custom-control-label" for="whatsapp-mobile">Visible en móvil</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-control custom-switch mt-2">
                                <input id="whatsapp-desktop" class="custom-control-input" type="checkbox" v-model="settings.whatsapp.show_desktop">
                                <label class="custom-control-label" for="whatsapp-desktop">Visible en escritorio</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Posición</label>
                            <select class="form-control" v-model="settings.whatsapp.position">
                                <option value="right">Derecha</option>
                                <option value="left">Izquierda</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label>Teléfono</label>
                            <input class="form-control" v-model="settings.whatsapp.phone" placeholder="Ej. 5215512345678">
                            <small class="form-text text-muted">Formato internacional, sin espacios ni signos.</small>
                        </div>
                        <div class="col-md-4">
                            <label>Etiqueta del botón</label>
                            <input class="form-control" v-model="settings.whatsapp.label">
                        </div>
                        <div class="col-md-4">
                            <label>Mensaje predeterminado</label>
                            <input class="form-control" v-model="settings.whatsapp.message">
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-messenger" role="tabpanel">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="custom-control custom-switch mt-2">
                                <input id="messenger-enabled" class="custom-control-input" type="checkbox" v-model="settings.messenger.enabled">
                                <label class="custom-control-label" for="messenger-enabled">Activar Messenger</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-control custom-switch mt-2">
                                <input id="messenger-consent" class="custom-control-input" type="checkbox" v-model="settings.messenger.requires_consent" disabled>
                                <label class="custom-control-label" for="messenger-consent">Requiere consentimiento</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Categoría</label>
                            <input class="form-control" value="Marketing" disabled>
                        </div>
                        <div class="col-md-3">
                            <label>Page ID</label>
                            <input class="form-control" v-model="settings.messenger.page_id">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label>Etiqueta visible</label>
                            <input class="form-control" v-model="settings.messenger.label">
                        </div>
                        <div class="col-md-8">
                            <label>Texto de ayuda</label>
                            <input class="form-control" v-model="settings.messenger.help_text">
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        Messenger se guarda como integración <code>meta_messenger</code>. No se muestran ni editan secretos en esta pantalla.
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-mobile-cta" role="tabpanel">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="custom-control custom-switch mt-2">
                                <input id="cta-enabled" class="custom-control-input" type="checkbox" v-model="settings.mobile_cta.enabled">
                                <label class="custom-control-label" for="cta-enabled">Activar barra móvil</label>
                            </div>
                        </div>
                        <div class="col-md-3" v-for="item in ctaItems" :key="item.key">
                            <div class="custom-control custom-switch mt-2">
                                <input :id="'cta-' + item.key" class="custom-control-input" type="checkbox" v-model="settings.mobile_cta[item.visible]">
                                <label class="custom-control-label" :for="'cta-' + item.key">{{ item.label }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label>Etiqueta WhatsApp</label>
                            <input class="form-control" v-model="settings.mobile_cta.whatsapp_label">
                        </div>
                        <div class="col-md-4">
                            <label>Etiqueta Catálogo</label>
                            <input class="form-control" v-model="settings.mobile_cta.catalog_label">
                        </div>
                        <div class="col-md-4">
                            <label>Etiqueta Contacto</label>
                            <input class="form-control" v-model="settings.mobile_cta.contact_label">
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-product" role="tabpanel">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="custom-control custom-switch mt-2">
                                <input id="product-enabled" class="custom-control-input" type="checkbox" v-model="settings.product_inquiry.enabled">
                                <label class="custom-control-label" for="product-enabled">Activar consulta de producto</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>Etiqueta</label>
                            <input class="form-control" v-model="settings.product_inquiry.label">
                        </div>
                        <div class="col-md-5">
                            <label>Destino</label>
                            <select class="form-control" v-model="settings.product_inquiry.destination">
                                <option value="whatsapp">WhatsApp</option>
                                <option value="contact">Página de contacto</option>
                                <option value="crm">Formulario / lead CRM</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-muted mt-3 mb-0">Si WhatsApp no está configurado, el frontend usa la página de contacto como respaldo.</p>
                </div>

                <div class="tab-pane fade" id="tab-trust" role="tabpanel">
                    <div class="row" v-for="(badge, index) in settings.trust_badges" :key="index">
                        <div class="col-md-5">
                            <label>Texto badge {{ index + 1 }}</label>
                            <input class="form-control" v-model="badge.label">
                        </div>
                        <div class="col-md-5">
                            <label>Icono badge {{ index + 1 }}</label>
                            <input class="form-control" v-model="badge.icon" placeholder="bi bi-patch-check">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <span class="badge badge-light p-2"><i :class="badge.icon"></i> Vista previa</span>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-leads" role="tabpanel">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="custom-control custom-switch mt-2">
                                <input id="leads-crm" class="custom-control-input" type="checkbox" v-model="settings.leads.crm_enabled" :disabled="!crmAvailable">
                                <label class="custom-control-label" for="leads-crm">Crear prospecto CRM</label>
                            </div>
                            <small v-if="!crmAvailable" class="form-text text-muted">La tabla core_crm_prospects no está disponible.</small>
                        </div>
                        <div class="col-md-4">
                            <label>Origen predeterminado</label>
                            <input class="form-control" v-model="settings.leads.default_source">
                        </div>
                        <div class="col-md-4">
                            <label>Correo de notificación</label>
                            <input class="form-control" v-model="settings.leads.notification_email" placeholder="opcional">
                        </div>
                    </div>
                    <p class="text-muted mt-3 mb-0">Esta pantalla no configura SMTP ni secretos. Solo define comportamiento público de leads.</p>
                </div>
            </div>
        </div>

        <div class="card-footer text-right">
            <a class="btn btn-default" href="<?php echo Uri::create('admin/web'); ?>">Volver a Web y tracking</a>
            <button class="btn btn-primary" :disabled="saving || loading" @click="save">
                <i class="bi bi-save"></i> {{ saving ? 'Guardando...' : 'Guardar configuración' }}
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new Vue({
        el: '#app-web-conversion',
        data: {
            loading: true,
            saving: false,
            crmAvailable: false,
            message: '',
            messageType: 'success',
            errors: [],
            settings: {
                whatsapp: {},
                messenger: {},
                mobile_cta: {},
                product_inquiry: {},
                trust_badges: [],
                leads: {}
            },
            ctaItems: [
                { key: 'whatsapp', visible: 'show_whatsapp', label: 'Mostrar WhatsApp' },
                { key: 'catalog', visible: 'show_catalog', label: 'Mostrar Catálogo' },
                { key: 'contact', visible: 'show_contact', label: 'Mostrar Contacto' }
            ]
        },
        mounted: function () {
            this.load();
        },
        methods: {
            load: function () {
                var self = this;
                self.loading = true;
                fetch(<?php echo json_encode(Uri::create('admin/web/conversion_data')); ?>, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                })
                    .then(window.coreAppParseJsonResponse)
                    .then(function (response) {
                        self.loading = false;
                        if (!response || response.success === false) {
                            self.showError(response && response.message ? response.message : 'No se pudo cargar la configuración.');
                            return;
                        }
                        self.settings = response.data.settings || self.settings;
                        self.crmAvailable = !!(response.data && response.data.crm_available);
                        self.normalize();
                    })
                    .catch(function () {
                        self.loading = false;
                        self.showError('No se pudo cargar la configuración.');
                    });
            },
            save: function () {
                var self = this;
                self.saving = true;
                self.message = '';
                self.errors = [];
                fetch(<?php echo json_encode(Uri::create('admin/web/save_conversion')); ?>, window.coreAppFetchOptions({
                    settings: self.settings
                }))
                    .then(window.coreAppParseJsonResponse)
                    .then(function (response) {
                        self.saving = false;
                        if (!response || response.success === false) {
                            self.showError(response && response.message ? response.message : 'No se pudo guardar la configuración.', response && response.errors ? response.errors : []);
                            return;
                        }
                        self.settings = response.data.settings || self.settings;
                        self.crmAvailable = !!(response.data && response.data.crm_available);
                        self.normalize();
                        self.messageType = 'success';
                        self.message = response.message || 'Configuración guardada.';
                    })
                    .catch(function (error) {
                        self.saving = false;
                        self.showError(error && error.message ? error.message : 'No se pudo guardar la configuración.');
                    });
            },
            normalize: function () {
                this.settings.whatsapp = this.normalizeBooleans(this.settings.whatsapp, ['enabled', 'show_mobile', 'show_desktop']);
                this.settings.messenger = this.normalizeBooleans(this.settings.messenger, ['enabled', 'requires_consent']);
                this.settings.mobile_cta = this.normalizeBooleans(this.settings.mobile_cta, ['enabled', 'show_whatsapp', 'show_catalog', 'show_contact']);
                this.settings.product_inquiry = this.normalizeBooleans(this.settings.product_inquiry, ['enabled']);
                this.settings.leads = this.normalizeBooleans(this.settings.leads, ['crm_enabled']);
                if (!Array.isArray(this.settings.trust_badges)) {
                    this.settings.trust_badges = [];
                }
            },
            normalizeBooleans: function (object, keys) {
                object = object || {};
                keys.forEach(function (key) {
                    object[key] = object[key] === true || object[key] === 1 || object[key] === '1' || object[key] === 'true';
                });
                return object;
            },
            showError: function (message, errors) {
                this.messageType = 'error';
                this.message = message || 'Ocurrió un error.';
                this.errors = errors || [];
            }
        }
    });
});
</script>
