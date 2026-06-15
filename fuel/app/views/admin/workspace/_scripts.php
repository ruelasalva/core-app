<style>
    .workspace-page .card { border-radius: .35rem; }
    .workspace-grid .card-body { min-height: 130px; }
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
            widgetData: {},
            widgetLoading: {},
            widgetErrors: {}
        },
        mounted: function() {
            this.loadWorkspace();
        },
        methods: {
            loadWorkspace: function() {
                fetch('<?php echo \Uri::create('admin/workspace/data'); ?>')
                    .then(response => response.json())
                    .then(data => {
                        const payload = data.data || {};
                        this.layout = payload.layout || { widgets: [] };
                        this.widgets = payload.widgets || [];
                        this.quickActions = payload.quick_actions || [];
                        this.loading = false;
                        this.loadVisibleWidgets();
                    })
                    .catch(() => {
                        this.loading = false;
                        this.error = 'No se pudo cargar el Workspace.';
                    });
            },
            loadVisibleWidgets: function() {
                (this.layout.widgets || []).forEach(instance => this.loadWidget(instance.widget_code));
            },
            loadWidget: function(code) {
                if (!code) return;
                this.$set(this.widgetLoading, code, true);
                this.$set(this.widgetErrors, code, '');
                fetch('<?php echo \Uri::create('admin/workspace/widget'); ?>/' + encodeURIComponent(code))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success === false) {
                            this.$set(this.widgetErrors, code, data.message || 'Widget no disponible.');
                            return;
                        }
                        this.$set(this.widgetData, code, data);
                    })
                    .catch(() => this.$set(this.widgetErrors, code, 'No se pudo cargar el widget.'))
                    .finally(() => this.$set(this.widgetLoading, code, false));
            },
            widgetHtml: function(code) {
                return (this.widgetData[code] && this.widgetData[code].html) ? this.widgetData[code].html : '<div class="text-muted">Sin contenido.</div>';
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
            }
        }
    });
});
</script>
