<style>
    .workspace-page .card { border-radius: .35rem; }
    .workspace-page .card-header { min-height: 42px; padding: .5rem .75rem; }
    .workspace-page .card-title { font-size: .95rem; font-weight: 600; }
    .workspace-grid .card-body { min-height: 130px; padding: .75rem; }
    .workspace-widget-skeleton .placeholder-line {
        height: 10px;
        margin-bottom: 8px;
        border-radius: 999px;
        background: #eef1f5;
    }
    .workspace-widget-empty { color: #6c757d; }
    .workspace-welcome h4 {
        margin: 0 0 .25rem;
        font-size: 1.05rem;
        font-weight: 700;
    }
    .workspace-welcome p {
        margin-bottom: .4rem;
        color: #6c757d;
        font-size: .9rem;
    }
    .workspace-eyebrow {
        font-size: 11px;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: .15rem;
    }
    .workspace-welcome-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 6px;
    }
    .workspace-welcome-meta span {
        color: #495057;
        font-size: 12px;
    }
    .workspace-action-group-title {
        margin: 8px 0 4px;
        color: #6c757d;
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 700;
    }
    .workspace-action-list {
        border: 1px solid #eef0f2;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 6px;
    }
    .workspace-action-row {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 7px 9px;
        border: 0;
        border-bottom: 1px solid #eef0f2;
        color: #2f3439;
    }
    .workspace-action-row:last-child {
        border-bottom: 0;
    }
    .workspace-action-icon {
        width: 22px;
        text-align: center;
        font-size: 14px;
        flex: 0 0 auto;
    }
    .workspace-action-title {
        display: block;
        font-weight: 600;
        line-height: 1.2;
        font-size: 13px;
        flex: 1 1 auto;
    }
    .workspace-action-arrow {
        color: #6c757d;
        font-size: 11px;
        flex: 0 0 auto;
    }
    .workspace-empty-state {
        color: #6c757d;
        font-size: .9rem;
    }
    .workspace-empty-state strong {
        display: block;
        color: #495057;
        margin-bottom: 2px;
    }
    .workspace-empty-state p {
        margin: 0;
    }
    .workspace-empty-icon {
        display: inline-block;
        color: #6c757d;
        margin-right: 4px;
    }
    .workspace-timeline {
        display: flex;
        flex-direction: column;
        gap: 7px;
        font-size: .9rem;
    }
    .workspace-timeline-item {
        display: flex;
        gap: 7px;
        align-items: flex-start;
    }
    .workspace-timeline-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #6c757d;
        margin-top: 7px;
        flex: 0 0 auto;
    }
    .workspace-inspector-toggle {
        font-size: 11px;
        color: #6c757d;
        padding: 0 .35rem;
    }
    .workspace-widget-inspector {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        font-size: 11px;
        color: #6c757d;
        border-top: 1px solid #edf0f2;
        padding-top: 7px;
    }
    .workspace-widget-inspector span {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 3px;
        padding: 2px 5px;
    }
    .workspace-table-list {
        border: 1px solid #eef0f2;
        border-radius: 4px;
        overflow: hidden;
        font-size: 12px;
    }
    .workspace-table-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(72px, 1fr));
        gap: 8px;
        align-items: center;
        padding: 7px 9px;
        border-bottom: 1px solid #eef0f2;
    }
    .workspace-table-row:last-child {
        border-bottom: 0;
    }
    .workspace-table-head {
        background: #f8f9fa;
        color: #6c757d;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .workspace-table-row span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .workspace-edit-toolbar .card-body {
        min-height: auto;
        padding: .65rem .75rem;
    }
    .workspace-widget-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        padding: .45rem .75rem;
        border-bottom: 1px solid #edf0f2;
        background: #f8f9fa;
    }
    .workspace-widget-controls .btn-xs,
    .workspace-widget-controls .btn-group-xs > .btn {
        padding: .15rem .4rem;
        font-size: 11px;
        line-height: 1.4;
    }
    .workspace-modal-backdrop {
        position: fixed;
        z-index: 1070;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        background: rgba(20, 25, 34, .42);
    }
    .workspace-modal-panel {
        width: min(420px, 100%);
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .25);
    }
    .workspace-modal-header,
    .workspace-modal-footer {
        display: flex;
        align-items: center;
        padding: .75rem;
    }
    .workspace-modal-header {
        border-bottom: 1px solid #edf0f2;
    }
    .workspace-modal-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        flex: 1 1 auto;
    }
    .workspace-modal-body {
        padding: .85rem .75rem;
    }
    .workspace-modal-footer {
        justify-content: flex-end;
        gap: 8px;
        border-top: 1px solid #edf0f2;
    }
    .workspace-palette-panel {
        width: min(760px, 100%);
    }
    .workspace-palette-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 460px;
        overflow: auto;
    }
    .workspace-palette-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px;
        border: 1px solid #edf0f2;
        border-radius: 6px;
        background: #fff;
    }
    .workspace-palette-icon {
        width: 30px;
        text-align: center;
        color: #495057;
        flex: 0 0 auto;
    }
    .workspace-palette-text {
        min-width: 0;
        flex: 1 1 auto;
    }
    .workspace-palette-text strong,
    .workspace-palette-text small {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .workspace-palette-text small {
        color: #6c757d;
        margin-bottom: 3px;
    }
    .workspace-command-backdrop {
        position: fixed;
        z-index: 1060;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 8vh 16px 16px;
        background: rgba(20, 25, 34, .42);
    }
    .workspace-command-panel {
        width: min(720px, 100%);
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .25);
        overflow: hidden;
    }
    .workspace-command-search {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-bottom: 1px solid #edf0f2;
    }
    .workspace-command-search .form-control {
        border: 0;
        box-shadow: none;
        padding-left: 0;
        font-size: 15px;
    }
    .workspace-command-body {
        max-height: 420px;
        overflow: auto;
        padding: 8px;
    }
    .workspace-command-empty {
        padding: 22px 14px;
        color: #6c757d;
        text-align: center;
    }
    .workspace-command-result {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        min-height: 52px;
        padding: 9px 10px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: #2f3439;
        text-align: left;
    }
    .workspace-command-result:hover,
    .workspace-command-result.active {
        background: #f4f6f8;
    }
    .workspace-command-icon {
        width: 28px;
        color: #495057;
        text-align: center;
        flex: 0 0 auto;
    }
    .workspace-command-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
        flex: 1 1 auto;
    }
    .workspace-command-text strong,
    .workspace-command-text small {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .workspace-command-text small {
        color: #6c757d;
    }
    .workspace-command-badge {
        padding: 2px 7px;
        border-radius: 999px;
        background: #eef1f5;
        color: #6c757d;
        font-size: 11px;
        flex: 0 0 auto;
    }
    @media (max-width: 576px) {
        .workspace-command-backdrop {
            padding-top: 12px;
        }
        .workspace-command-panel {
            border-radius: 6px;
        }
        .workspace-command-body {
            max-height: 70vh;
        }
    }
</style>

<script>
window.addEventListener('load', function() {
    new Vue({
        el: '#app-workspace',
        data: {
            baseUrl: '<?php echo \Uri::base(false); ?>',
            loading: true,
            notice: '',
            error: '',
            layout: { widgets: [] },
            widgets: [],
            quickActions: [],
            context: {},
            showWidgetInspector: false,
            widgetInspectorOpen: {},
            widgetData: {},
            widgetLoading: {},
            widgetErrors: {},
            commandPaletteOpen: false,
            commandPaletteQuery: '',
            commandPaletteResults: [],
            commandPaletteLoading: false,
            commandPaletteError: '',
            commandPaletteSelectedIndex: 0,
            commandPaletteTimer: null,
            commandPaletteKeyHandler: null,
            editMode: false,
            canEditWorkspace: false,
            layoutDirty: false,
            layoutSaving: false,
            resetLayoutModalOpen: false,
            widgetPaletteOpen: false,
            widgetPaletteLoading: false,
            widgetPaletteSaving: '',
            widgetPaletteQuery: '',
            widgetPaletteCategory: '',
            widgetPaletteItems: []
        },
        mounted: function() {
            this.commandPaletteKeyHandler = this.handleGlobalShortcut.bind(this);
            window.addEventListener('keydown', this.commandPaletteKeyHandler);
            this.loadWorkspace();
        },
        beforeDestroy: function() {
            if (this.commandPaletteKeyHandler) {
                window.removeEventListener('keydown', this.commandPaletteKeyHandler);
            }
            if (this.commandPaletteTimer) {
                clearTimeout(this.commandPaletteTimer);
            }
        },
        methods: {
            loadWorkspace: function() {
                fetch('<?php echo \Uri::create('admin/workspace/data'); ?>')
                    .then(response => this.parseJsonResponse(response))
                    .then(data => {
                        if (this.isAuthRequired(data)) {
                            this.loading = false;
                            this.error = 'Tu sesión expiró. Vuelve a iniciar sesión.';
                            return;
                        }
                        if (data.success === false) {
                            this.loading = false;
                            this.error = data.message || 'No se pudo cargar el Workspace.';
                            return;
                        }
                        const payload = data.data || {};
                        this.layout = payload.layout || { widgets: [] };
                        this.widgets = payload.widgets || [];
                        this.quickActions = payload.quick_actions || [];
                        this.context = payload.context || {};
                        this.showWidgetInspector = !!(this.context.is_super_admin || this.context.can_admin_workspace);
                        this.canEditWorkspace = !!(this.context.is_super_admin || this.context.can_edit_workspace);
                        this.loading = false;
                        this.loadVisibleWidgets();
                    })
                    .catch(() => {
                        this.loading = false;
                        this.error = 'No se pudo cargar el Workspace.';
                    });
            },
            loadVisibleWidgets: function() {
                this.visibleLayoutWidgets().forEach(instance => this.loadWidget(instance.widget_code));
            },
            loadWidget: function(code) {
                if (!code) return;
                this.$set(this.widgetLoading, code, true);
                this.$set(this.widgetErrors, code, '');
                fetch('<?php echo \Uri::create('admin/workspace/widget'); ?>/' + encodeURIComponent(code))
                    .then(response => this.parseJsonResponse(response))
                    .then(data => {
                        if (this.isAuthRequired(data)) {
                            data.message = 'Tu sesión expiró. Vuelve a iniciar sesión.';
                        }
                        this.$set(this.widgetData, code, data);
                        if (data.success === false) {
                            this.$set(this.widgetErrors, code, data.message || 'Widget no disponible.');
                        }
                    })
                    .catch(() => {
                        this.$set(this.widgetErrors, code, 'No se pudo cargar el widget.');
                        this.$set(this.widgetData, code, {
                            success: false,
                            message: 'No se pudo cargar el widget.',
                            state: 'error',
                            payload: { html: '' },
                            health: {},
                            actions: [],
                            errors: ['request_failed']
                        });
                    })
                    .finally(() => this.$set(this.widgetLoading, code, false));
            },
            parseJsonResponse: function(response) {
                const contentType = response.headers.get('content-type') || '';
                if (contentType.indexOf('application/json') === -1) {
                    return Promise.resolve({
                        success: false,
                        state: 'error',
                        message: 'Respuesta inválida del servidor.',
                        errors: ['invalid_json_response'],
                        payload: { html: '' },
                        health: {},
                        actions: []
                    });
                }

                return response.json();
            },
            isAuthRequired: function(data) {
                return data && Array.isArray(data.errors) && data.errors.indexOf('auth_required') !== -1;
            },
            widgetHtml: function(code) {
                const data = this.widgetData[code] || {};
                const payload = data.payload || {};
                return payload.html ? payload.html : '<div class="text-muted">Sin contenido.</div>';
            },
            widgetPayload: function(code) {
                const data = this.widgetData[code] || {};
                return data.payload || {};
            },
            widgetRenderType: function(code) {
                return this.widgetPayload(code).render || '';
            },
            widgetRows: function(code) {
                return this.widgetPayload(code).rows || [];
            },
            widgetColumns: function(code) {
                return this.widgetPayload(code).columns || [];
            },
            widgetAction: function(code) {
                return this.widgetPayload(code).action || null;
            },
            formatCell: function(value) {
                if (value === null || value === undefined || value === '') {
                    return '-';
                }
                return value;
            },
            widgetState: function(code) {
                if (this.widgetLoading[code]) {
                    return 'loading';
                }

                const data = this.widgetData[code] || {};
                if (data.state) {
                    return data.state;
                }

                return this.widgetErrors[code] ? 'error' : 'loading';
            },
            widgetMessage: function(code, fallback) {
                const data = this.widgetData[code] || {};
                return data.message || this.widgetErrors[code] || fallback;
            },
            widgetHealth: function(code) {
                const data = this.widgetData[code] || {};
                return data.health || {};
            },
            toggleWidgetInspector: function(code) {
                this.$set(this.widgetInspectorOpen, code, !this.widgetInspectorOpen[code]);
            },
            isWidgetInspectorOpen: function(code) {
                return !!this.widgetInspectorOpen[code];
            },
            widgetTitle: function(code) {
                const found = (this.widgets || []).find(widget => widget.code === code);
                return found ? found.title : code;
            },
            widgetCardClass: function(code) {
                const found = (this.widgets || []).find(widget => widget.code === code);
                return found && found.color ? 'card-' + found.color + ' card-outline' : 'card-secondary card-outline';
            },
            columnClass: function(instance) {
                const width = Math.max(1, Math.min(12, parseInt(instance.w || 4, 10)));
                return 'col-lg-' + width + ' col-md-6 col-12';
            },
            visibleLayoutWidgets: function() {
                return (this.layout.widgets || []).filter(instance => !instance.hidden && parseInt(instance.active || 1, 10) !== 0);
            },
            toggleEditMode: function() {
                if (!this.canEditWorkspace) {
                    return;
                }
                this.editMode = !this.editMode;
                this.notice = '';
                this.error = '';
            },
            markLayoutDirty: function() {
                this.layoutDirty = true;
                this.notice = '';
                this.error = '';
            },
            moveWidget: function(code, direction) {
                const widgets = this.layout.widgets || [];
                const index = widgets.findIndex(instance => instance.widget_code === code);
                if (index < 0) {
                    return;
                }

                const target = index + direction;
                if (target < 0 || target >= widgets.length) {
                    return;
                }

                const current = widgets[index];
                widgets.splice(index, 1);
                widgets.splice(target, 0, current);
                this.resequenceWidgets();
                this.markLayoutDirty();
            },
            setWidgetSize: function(code, size) {
                const sizes = {
                    small: { w: 4, h: 2 },
                    medium: { w: 6, h: 2 },
                    large: { w: 12, h: 3 }
                };
                const selected = sizes[size] || sizes.medium;
                const instance = this.findLayoutWidget(code);
                if (!instance) {
                    return;
                }

                this.$set(instance, 'w', selected.w);
                this.$set(instance, 'h', selected.h);
                this.markLayoutDirty();
            },
            hideWidget: function(code) {
                const instance = this.findLayoutWidget(code);
                if (!instance) {
                    return;
                }

                this.$set(instance, 'hidden', 1);
                this.$set(instance, 'active', 0);
                this.markLayoutDirty();
            },
            findLayoutWidget: function(code) {
                return (this.layout.widgets || []).find(instance => instance.widget_code === code);
            },
            resequenceWidgets: function() {
                (this.layout.widgets || []).forEach((instance, index) => {
                    this.$set(instance, 'x', 0);
                    this.$set(instance, 'y', index);
                });
            },
            saveLayout: function() {
                if (!this.canEditWorkspace || this.layoutSaving) {
                    return;
                }

                this.layoutSaving = true;
                this.notice = '';
                this.error = '';

                fetch('<?php echo \Uri::create('admin/workspace/save_layout'); ?>', window.coreAppFetchOptions({
                    widgets: this.layoutPayload()
                }))
                    .then(response => this.parseJsonResponse(response))
                    .then(data => {
                        if (this.isAuthRequired(data)) {
                            this.error = 'Tu sesión expiró. Vuelve a iniciar sesión.';
                            return;
                        }
                        if (data.success === false) {
                            this.error = data.message || 'No se pudo guardar el layout.';
                            return;
                        }

                        this.notice = data.message || 'Layout guardado.';
                        this.layoutDirty = false;
                        this.editMode = false;
                        this.loadWorkspace();
                    })
                    .catch(() => {
                        this.error = 'No se pudo guardar el layout.';
                    })
                    .finally(() => {
                        this.layoutSaving = false;
                    });
            },
            layoutPayload: function() {
                this.resequenceWidgets();
                return (this.layout.widgets || []).map(instance => ({
                    widget_code: instance.widget_code,
                    x: parseInt(instance.x || 0, 10),
                    y: parseInt(instance.y || 0, 10),
                    w: parseInt(instance.w || 4, 10),
                    h: parseInt(instance.h || 2, 10),
                    collapsed: parseInt(instance.collapsed || 0, 10),
                    favorite: parseInt(instance.favorite || 0, 10),
                    hidden: parseInt(instance.hidden || 0, 10),
                    mobile_hidden: parseInt(instance.mobile_hidden || 0, 10)
                }));
            },
            openResetLayoutModal: function() {
                this.resetLayoutModalOpen = true;
            },
            closeResetLayoutModal: function() {
                this.resetLayoutModalOpen = false;
            },
            resetLayout: function() {
                if (!this.canEditWorkspace || this.layoutSaving) {
                    return;
                }

                this.layoutSaving = true;
                this.notice = '';
                this.error = '';

                fetch('<?php echo \Uri::create('admin/workspace/reset_layout'); ?>', window.coreAppFetchOptions({}))
                    .then(response => this.parseJsonResponse(response))
                    .then(data => {
                        if (this.isAuthRequired(data)) {
                            this.error = 'Tu sesión expiró. Vuelve a iniciar sesión.';
                            return;
                        }
                        if (data.success === false) {
                            this.error = data.message || 'No se pudo restablecer el Workspace.';
                            return;
                        }

                        this.notice = data.message || 'Workspace restablecido.';
                        this.layoutDirty = false;
                        this.editMode = false;
                        this.resetLayoutModalOpen = false;
                        this.loadWorkspace();
                    })
                    .catch(() => {
                        this.error = 'No se pudo restablecer el Workspace.';
                    })
                    .finally(() => {
                        this.layoutSaving = false;
                    });
            },
            openWidgetPalette: function() {
                if (!this.canEditWorkspace) {
                    return;
                }
                this.widgetPaletteOpen = true;
                this.widgetPaletteQuery = '';
                this.widgetPaletteCategory = '';
                this.loadWidgetPalette();
            },
            closeWidgetPalette: function() {
                this.widgetPaletteOpen = false;
                this.widgetPaletteSaving = '';
            },
            loadWidgetPalette: function() {
                this.widgetPaletteLoading = true;
                this.error = '';

                fetch('<?php echo \Uri::create('admin/workspace/available_widgets'); ?>')
                    .then(response => this.parseJsonResponse(response))
                    .then(data => {
                        if (this.isAuthRequired(data)) {
                            this.error = 'Tu sesión expiró. Vuelve a iniciar sesión.';
                            this.widgetPaletteItems = [];
                            return;
                        }
                        if (data.success === false) {
                            this.error = data.message || 'No se pudieron cargar los widgets disponibles.';
                            this.widgetPaletteItems = [];
                            return;
                        }

                        const payload = data.data || {};
                        this.widgetPaletteItems = payload.widgets || [];
                    })
                    .catch(() => {
                        this.error = 'No se pudieron cargar los widgets disponibles.';
                        this.widgetPaletteItems = [];
                    })
                    .finally(() => {
                        this.widgetPaletteLoading = false;
                    });
            },
            widgetPaletteCategories: function() {
                const categories = {};
                (this.widgetPaletteItems || []).forEach(widget => {
                    if (widget.category) {
                        categories[widget.category] = true;
                    }
                });

                return Object.keys(categories).sort();
            },
            filteredWidgetPalette: function() {
                const query = this.normalizePaletteText(this.widgetPaletteQuery || '');
                const category = this.widgetPaletteCategory || '';

                return (this.widgetPaletteItems || []).filter(widget => {
                    if (category && widget.category !== category) {
                        return false;
                    }
                    if (!query) {
                        return true;
                    }

                    const text = this.normalizePaletteText([
                        widget.title || '',
                        widget.description || '',
                        widget.category || '',
                        widget.type || '',
                        widget.code || ''
                    ].join(' '));

                    return text.indexOf(query) !== -1;
                });
            },
            normalizePaletteText: function(value) {
                return String(value || '').toLowerCase()
                    .replace(/[áàäâ]/g, 'a')
                    .replace(/[éèëê]/g, 'e')
                    .replace(/[íìïî]/g, 'i')
                    .replace(/[óòöô]/g, 'o')
                    .replace(/[úùüû]/g, 'u')
                    .replace(/ñ/g, 'n');
            },
            addWidgetFromPalette: function(widget) {
                if (!widget || !widget.code || widget.state === 'visible' || this.widgetPaletteSaving) {
                    return;
                }

                this.widgetPaletteSaving = widget.code;
                this.notice = '';
                this.error = '';

                fetch('<?php echo \Uri::create('admin/workspace/add_widget'); ?>', window.coreAppFetchOptions({
                    widget_code: widget.code
                }))
                    .then(response => this.parseJsonResponse(response))
                    .then(data => {
                        if (this.isAuthRequired(data)) {
                            this.error = 'Tu sesión expiró. Vuelve a iniciar sesión.';
                            return;
                        }
                        if (data.success === false) {
                            this.error = data.message || 'No se pudo agregar el widget.';
                            return;
                        }

                        this.notice = data.message || 'Widget agregado.';
                        this.layoutDirty = false;
                        if (data.data && data.data.layout) {
                            this.layout = data.data.layout;
                        }
                        this.loadVisibleWidgets();
                        this.loadWidgetPalette();
                    })
                    .catch(() => {
                        this.error = 'No se pudo agregar el widget.';
                    })
                    .finally(() => {
                        this.widgetPaletteSaving = '';
                    });
            },
            handleGlobalShortcut: function(event) {
                const key = (event.key || '').toLowerCase();
                if ((event.ctrlKey || event.metaKey) && key === 'k') {
                    event.preventDefault();
                    this.openCommandPalette();
                    return;
                }

                if (event.key === 'Escape' && this.commandPaletteOpen) {
                    event.preventDefault();
                    this.closeCommandPalette();
                }
            },
            openCommandPalette: function() {
                this.commandPaletteOpen = true;
                this.commandPaletteQuery = '';
                this.commandPaletteResults = [];
                this.commandPaletteError = '';
                this.commandPaletteSelectedIndex = 0;
                this.searchCommandPalette();
                this.$nextTick(() => {
                    if (this.$refs.commandPaletteInput) {
                        this.$refs.commandPaletteInput.focus();
                    }
                });
            },
            closeCommandPalette: function() {
                this.commandPaletteOpen = false;
                this.commandPaletteLoading = false;
                if (this.commandPaletteTimer) {
                    clearTimeout(this.commandPaletteTimer);
                    this.commandPaletteTimer = null;
                }
            },
            onCommandPaletteInput: function() {
                if (this.commandPaletteTimer) {
                    clearTimeout(this.commandPaletteTimer);
                }
                this.commandPaletteTimer = setTimeout(() => this.searchCommandPalette(), 200);
            },
            searchCommandPalette: function() {
                this.commandPaletteLoading = true;
                this.commandPaletteError = '';
                const query = encodeURIComponent(this.commandPaletteQuery || '');

                fetch('<?php echo \Uri::create('admin/workspace/command_palette'); ?>?q=' + query)
                    .then(response => this.parseJsonResponse(response))
                    .then(data => {
                        if (this.isAuthRequired(data)) {
                            this.commandPaletteResults = [];
                            this.commandPaletteError = 'Tu sesión expiró. Vuelve a iniciar sesión.';
                            return;
                        }
                        if (data.success === false) {
                            this.commandPaletteResults = [];
                            this.commandPaletteError = data.message || 'No se pudo buscar en el Workspace.';
                            return;
                        }

                        const payload = data.data || {};
                        this.commandPaletteResults = payload.results || [];
                        this.commandPaletteSelectedIndex = 0;
                    })
                    .catch(() => {
                        this.commandPaletteResults = [];
                        this.commandPaletteError = 'No se pudo buscar en el Workspace.';
                    })
                    .finally(() => {
                        this.commandPaletteLoading = false;
                    });
            },
            moveCommandSelection: function(direction) {
                if (!this.commandPaletteResults.length) {
                    return;
                }

                const length = this.commandPaletteResults.length;
                this.commandPaletteSelectedIndex = (this.commandPaletteSelectedIndex + direction + length) % length;
            },
            openSelectedCommandResult: function() {
                if (!this.commandPaletteResults.length) {
                    return;
                }

                this.openCommandResult(this.commandPaletteResults[this.commandPaletteSelectedIndex]);
            },
            openCommandResult: function(result) {
                if (!result || !result.url) {
                    return;
                }

                window.location.href = result.url;
            },
            commandTypeLabel: function(type) {
                if (type === 'quick_action') {
                    return 'Acción';
                }
                if (type === 'menu') {
                    return 'Menú';
                }
                if (type === 'widget') {
                    return 'Widget';
                }

                return 'Resultado';
            }
        }
    });
});
</script>
