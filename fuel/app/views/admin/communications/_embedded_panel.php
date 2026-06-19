<script src="<?php echo Uri::base(false); ?>assets/js/core-api-client.js"></script>

<script type="text/x-template" id="embedded-communications-panel-template">
    <div class="embedded-communications-panel">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h6 class="mb-1">{{ title || 'Comunicaciones' }}</h6>
                <p class="text-muted small mb-0">Conversaciones relacionadas. Vista solo lectura.</p>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-xs btn-outline-secondary" disabled>Responder pr&oacute;ximamente</button>
                <button type="button" class="btn btn-xs btn-outline-primary" :disabled="loading" @click="loadConversations">
                    <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                    <span v-else>Actualizar</span>
                </button>
            </div>
        </div>

        <div v-if="error" class="alert alert-warning py-2 mb-2">{{ error }}</div>
        <div v-if="loading" class="text-center text-muted py-3">
            <span class="spinner-border spinner-border-sm mr-1"></span> Cargando comunicaciones...
        </div>
        <div v-if="!loading && !error && conversations.length === 0" class="text-muted small border rounded p-3">
            No hay conversaciones relacionadas todav&iacute;a.
        </div>

        <div v-if="!loading && conversations.length > 0" class="embedded-communications-grid">
            <div class="embedded-communications-list">
                <button
                    v-for="conversation in conversations"
                    :key="'embedded-conversation-'+conversation.id"
                    type="button"
                    class="embedded-communication-item"
                    :class="{ active: selectedConversation && selectedConversation.id === conversation.id }"
                    @click="selectConversation(conversation)">
                    <span class="d-flex justify-content-between">
                        <strong>{{ conversation.subject || '(Sin asunto)' }}</strong>
                        <small>{{ formatDate(conversation.last_message_at) }}</small>
                    </span>
                    <span class="d-block small text-muted">
                        {{ conversation.account_email || 'Cuenta no identificada' }} · {{ conversation.channel_label || conversation.channel_code }}
                    </span>
                    <span class="d-block small">{{ conversation.snippet || 'Sin vista previa.' }}</span>
                    <span v-if="conversation.unread_count > 0" class="badge badge-danger mt-1">{{ conversation.unread_count }} sin leer</span>
                </button>
            </div>

            <div class="embedded-communications-preview">
                <div v-if="!selectedConversation" class="text-muted small border rounded p-3">
                    Selecciona una conversación para ver el detalle.
                </div>
                <div v-else>
                    <div class="border-bottom pb-2 mb-2">
                        <strong>{{ selectedConversation.subject || '(Sin asunto)' }}</strong>
                        <div class="small text-muted">{{ selectedConversation.account_email || 'Cuenta no identificada' }}</div>
                    </div>

                    <div v-if="detailLoading" class="text-center text-muted py-3">
                        <span class="spinner-border spinner-border-sm mr-1"></span> Cargando conversación...
                    </div>
                    <div v-if="!detailLoading && messages.length === 0" class="text-muted small">
                        Sin mensajes visibles.
                    </div>
                    <article v-for="message in messages" :key="'embedded-message-'+message.id" class="embedded-message">
                        <div class="d-flex justify-content-between">
                            <strong>{{ message.from_name || message.from_email || 'Remitente' }}</strong>
                            <small class="text-muted">{{ formatDate(message.date) }}</small>
                        </div>
                        <div class="small text-muted mb-2">
                            Para: <span v-for="recipient in message.to" :key="'embedded-to-'+message.id+'-'+formatRecipient(recipient)">{{ formatRecipient(recipient) }} </span>
                        </div>
                        <div class="embedded-message-html" v-if="message.body_html_sanitized" v-html="message.body_html_sanitized"></div>
                        <pre class="embedded-message-text" v-else>{{ message.body_text || message.snippet || 'Sin contenido visible.' }}</pre>
                        <div v-if="message.attachments && message.attachments.length" class="small mt-2">
                            <strong>Adjuntos</strong>
                            <div v-for="attachment in message.attachments" :key="'embedded-attachment-'+attachment.id">
                                <i class="bi bi-paperclip"></i>
                                {{ attachment.filename }} · {{ attachment.mime_type }} · {{ formatBytes(attachment.size_bytes) }}
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</script>

<style>
.embedded-communications-panel {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 12px;
    background: #fff;
}
.embedded-communications-grid {
    display: grid;
    grid-template-columns: minmax(220px, 320px) minmax(0, 1fr);
    gap: 12px;
}
.embedded-communications-list {
    max-height: 360px;
    overflow-y: auto;
}
.embedded-communication-item {
    display: block;
    width: 100%;
    text-align: left;
    border: 1px solid #e9ecef;
    background: #fff;
    border-radius: 6px;
    padding: 8px;
    margin-bottom: 8px;
}
.embedded-communication-item.active,
.embedded-communication-item:hover {
    border-color: #007bff;
    background: #f8fbff;
}
.embedded-communications-preview {
    min-width: 0;
    max-height: 420px;
    overflow-y: auto;
}
.embedded-message {
    border-bottom: 1px solid #eef0f2;
    padding: 10px 0;
}
.embedded-message-text {
    white-space: pre-wrap;
    word-break: break-word;
    background: #f8f9fa;
    border: 1px solid #eef0f2;
    border-radius: 4px;
    padding: 8px;
    font-size: 13px;
}
.embedded-message-html {
    word-break: break-word;
}
@media (max-width: 991px) {
    .embedded-communications-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(function (window) {
    'use strict';

    if (!window.Vue || window.CoreAppEmbeddedCommunicationsRegistered) {
        return;
    }

    window.CoreAppEmbeddedCommunicationsRegistered = true;

    Vue.component('embedded-communications-panel', {
        template: '#embedded-communications-panel-template',
        props: {
            entityType: { type: String, required: true },
            entityId: { type: [String, Number], default: 0 },
            partyId: { type: [String, Number], default: 0 },
            title: { type: String, default: 'Comunicaciones' },
            limit: { type: [String, Number], default: 10 }
        },
        data: function () {
            return {
                loading: false,
                detailLoading: false,
                error: '',
                conversations: [],
                selectedConversation: null,
                messages: []
            };
        },
        mounted: function () {
            this.loadConversations();
        },
        watch: {
            entityId: function () {
                this.resetAndLoad();
            },
            partyId: function () {
                this.resetAndLoad();
            }
        },
        methods: {
            resetAndLoad: function () {
                this.selectedConversation = null;
                this.messages = [];
                this.loadConversations();
            },
            loadConversations: function () {
                var entityId = parseInt(this.entityId || 0, 10);
                var partyId = parseInt(this.partyId || 0, 10);
                if (!this.entityType || (entityId <= 0 && partyId <= 0)) {
                    this.conversations = [];
                    this.error = '';
                    return;
                }

                this.loading = true;
                this.error = '';

                var params = new URLSearchParams();
                params.append('entity_type', this.entityType);
                params.append('entity_id', entityId);
                params.append('party_id', partyId);
                params.append('limit', parseInt(this.limit || 10, 10));

                window.CoreApiClient.get('<?php echo Uri::create('admin/communications/entity_conversations'); ?>?' + params.toString())
                    .then(result => {
                        var payload = result.payload || {};
                        if (!result.ok || payload.success === false) {
                            this.error = payload.message || result.message || 'No se pudieron cargar las comunicaciones.';
                            this.conversations = [];
                            return;
                        }
                        var data = payload.data || {};
                        this.conversations = data.conversations || [];
                    })
                    .catch(error => {
                        this.error = error && error.message ? error.message : 'No se pudieron cargar las comunicaciones.';
                        this.conversations = [];
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            selectConversation: function (conversation) {
                this.selectedConversation = conversation;
                this.messages = [];
                this.detailLoading = true;
                this.error = '';

                window.CoreApiClient.get('<?php echo Uri::create('admin/communications/entity_conversation_detail'); ?>/' + conversation.id)
                    .then(result => {
                        var payload = result.payload || {};
                        if (!result.ok || payload.success === false) {
                            this.error = payload.message || result.message || 'No se pudo cargar la conversación.';
                            return;
                        }
                        var data = payload.data || {};
                        this.selectedConversation = data.conversation || conversation;
                        this.messages = data.messages || [];
                    })
                    .catch(error => {
                        this.error = error && error.message ? error.message : 'No se pudo cargar la conversación.';
                    })
                    .finally(() => {
                        this.detailLoading = false;
                    });
            },
            formatDate: function (timestamp) {
                var value = parseInt(timestamp || 0, 10);
                if (!value) return '-';
                return new Date(value * 1000).toLocaleString('es-MX');
            },
            formatRecipient: function (recipient) {
                if (!recipient) return '';
                if (typeof recipient === 'string') return recipient;
                return recipient.name || recipient.email || '';
            },
            formatBytes: function (bytes) {
                var value = parseInt(bytes || 0, 10);
                if (value >= 1048576) return (value / 1048576).toFixed(1) + ' MB';
                if (value >= 1024) return (value / 1024).toFixed(1) + ' KB';
                return value + ' B';
            }
        }
    });
})(window);
</script>
