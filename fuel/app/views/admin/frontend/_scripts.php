<script>
document.addEventListener('DOMContentLoaded', function() {
    var frontendDataUrl = <?php echo json_encode(Uri::create('admin/frontend/data'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var frontendSaveUrl = <?php echo json_encode(Uri::create('admin/frontend/save'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var frontendMoveSectionUrl = <?php echo json_encode(Uri::create('admin/frontend/move_section'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var frontendUploadUrl = <?php echo json_encode(Uri::create('admin/frontend/upload_image'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var frontendAdminPreviewUrl = <?php echo json_encode(Uri::create('admin/frontend/preview'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var frontendBaseUrl = <?php echo json_encode(Uri::base(false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    new Vue({
        el: '#app-frontend',
        data: {
            loading: true,
            currentSection: 'pages',
            definitions: {},
            items: {},
            options: {},
            stats: {},
            form: {},
            richEditors: {},
            codeEditors: {},
            componentSettings: {},
            errorMessage: ''
        },
        computed: {
            sectionKeys() { return Object.keys(this.definitions); },
            currentDefinition() { return this.definitions[this.currentSection] || {}; },
            currentFields() { return this.currentDefinition.fields || []; },
            tableFields() {
                return this.currentFields.filter(field => !['active', 'content', 'settings_json', 'seo_description', 'media_path', 'image_path', 'logo_path', 'favicon_path', 'custom_css'].includes(field.name)).slice(0, 7);
            },
            currentItems() { return this.items[this.currentSection] || []; },
            sectionGroups() {
                const pages = (this.items.pages || []).slice().sort((a, b) => String(a.title || '').localeCompare(String(b.title || '')));
                const sections = (this.items.sections || []).slice().sort((a, b) => {
                    const order = Number(a.sort_order || 0) - Number(b.sort_order || 0);
                    return order !== 0 ? order : Number(a.id || 0) - Number(b.id || 0);
                });
                return pages.map(page => ({
                    page: page,
                    sections: sections.filter(section => Number(section.page_id || 0) === Number(page.id || 0))
                }));
            }
        },
        mounted() { this.loadData(); },
        methods: {
            loadData() {
                this.loading = true;
                this.errorMessage = '';
                fetch(frontendDataUrl)
                    .then(window.coreAppParseJsonResponse)
                    .then(data => {
                        this.loading = false;
                        if (data.error) { this.errorMessage = data.error; alert(data.error); return; }
                        this.definitions = data.definitions || {};
                        this.items = data.items || {};
                        this.options = data.options || {};
                        this.stats = data.stats || {};
                    })
                    .catch(() => {
                        this.loading = false;
                        this.errorMessage = 'No se pudo cargar el CMS Frontend.';
                        alert(this.errorMessage);
                    });
            },
            emptyForm() {
                const data = { id: null, section: this.currentSection };
                this.currentFields.forEach(field => {
                    data[field.name] = field.type === 'checkbox' ? field.default == 1 : field.default;
                });
                return data;
            },
            newItem() {
                this.destroyEditors();
                this.form = this.emptyForm();
                this.componentSettings = this.parseSettings(this.form.settings_json);
                this.showModal('modal-frontend-item');
                this.$nextTick(this.initEditors);
            },
            editItem(item) {
                this.destroyEditors();
                const data = this.emptyForm();
                Object.keys(item).forEach(key => { data[key] = item[key]; });
                this.currentFields.forEach(field => {
                    if (field.type === 'checkbox') data[field.name] = data[field.name] == 1;
                });
                data.section = this.currentSection;
                this.form = data;
                this.componentSettings = this.parseSettings(this.form.settings_json);
                this.showModal('modal-frontend-item');
                this.$nextTick(this.initEditors);
            },
            saveItem() {
                this.syncEditors();
                if (this.hasVisualSettings()) this.syncComponentSettings();
                this.form.section = this.currentSection;
                fetch(frontendSaveUrl, {
                    ...window.coreAppFetchOptions(this.form)
                })
                .then(window.coreAppParseJsonResponse)
                .then(data => {
                    if (data.error) { alert(data.error); return; }
                    this.items = data.items || {};
                    this.options = data.options || {};
                    this.stats = data.stats || {};
                    this.hideModal('modal-frontend-item');
                })
                .catch(() => {
                    alert('No se pudo guardar el registro.');
                });
            },
            uploadImage(event, field) {
                const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                if (!file) return;

                const data = new FormData();
                data.append('image', file);
                data.append('section', this.currentSection);
                data.append('field', field.name);
                data.append(window.coreAppCsrfKey, fuel_csrf_token());

                fetch(frontendUploadUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json' }, body: data })
                    .then(window.coreAppParseJsonResponse)
                    .then(data => {
                        event.target.value = '';
                        if (data.error) { alert(data.error); return; }
                        this.$set(this.form, field.name, data.path);
                    })
                    .catch(() => {
                        event.target.value = '';
                        alert('No se pudo subir la imagen.');
                    });
            },
            moveSection(section, direction) {
                fetch(frontendMoveSectionUrl, {
                    ...window.coreAppFetchOptions({ id: section.id, direction: direction })
                })
                .then(window.coreAppParseJsonResponse)
                .then(data => {
                    if (data.error) { alert(data.error); return; }
                    this.items = data.items || {};
                    this.options = data.options || {};
                    this.stats = data.stats || {};
                })
                .catch(() => {
                    alert('No se pudo reordenar la sección.');
                });
            },
            inputType(field) {
                if (field.type === 'number' || field.type === 'integer') return 'number';
                if (field.type === 'color') return 'color';
                return 'text';
            },
            getFieldElement(fieldName) {
                const ref = this.$refs['field_' + fieldName];
                return Array.isArray(ref) ? ref[0] : ref;
            },
            initEditors() {
                this.currentFields.forEach(field => {
                    const element = this.getFieldElement(field.name);
                    if (!element) return;

                    if (field.type === 'richtext' && window.ClassicEditor && !this.richEditors[field.name]) {
                        ClassicEditor.create(element, { language: 'es' })
                            .then(editor => {
                                this.richEditors[field.name] = editor;
                                editor.setData(this.form[field.name] || '');
                            })
                            .catch(error => console.error(error));
                    }

                    if ((field.type === 'json' || field.type === 'code_css') && window.CodeMirror && !this.codeEditors[field.name]) {
                        const mode = field.type === 'json' ? { name: 'javascript', json: true } : 'css';
                        const editor = CodeMirror.fromTextArea(element, {
                            lineNumbers: true,
                            mode: mode,
                            lineWrapping: true,
                            viewportMargin: Infinity
                        });
                        editor.setValue(this.form[field.name] || '');
                        editor.on('change', cm => {
                            this.$set(this.form, field.name, cm.getValue());
                            if (field.name === 'settings_json') {
                                this.componentSettings = this.parseSettings(cm.getValue());
                            }
                        });
                        this.codeEditors[field.name] = editor;
                    }
                });
            },
            syncEditors() {
                Object.keys(this.richEditors).forEach(key => {
                    this.$set(this.form, key, this.richEditors[key].getData());
                });
                Object.keys(this.codeEditors).forEach(key => {
                    this.$set(this.form, key, this.codeEditors[key].getValue());
                });
            },
            destroyEditors() {
                Object.keys(this.richEditors).forEach(key => {
                    if (this.richEditors[key] && this.richEditors[key].destroy) {
                        this.richEditors[key].destroy();
                    }
                });
                Object.keys(this.codeEditors).forEach(key => {
                    if (this.codeEditors[key] && this.codeEditors[key].toTextArea) {
                        this.codeEditors[key].toTextArea();
                    }
                });
                this.richEditors = {};
                this.codeEditors = {};
            },
            parseSettings(value) {
                if (!value) return {};
                try {
                    return JSON.parse(value) || {};
                } catch (e) {
                    return {};
                }
            },
            syncComponentSettings() {
                if (!this.hasVisualSettings()) return;
                const json = Object.keys(this.componentSettings || {}).length ? JSON.stringify(this.componentSettings) : '';
                this.$set(this.form, 'settings_json', json);
                if (this.codeEditors.settings_json) {
                    this.codeEditors.settings_json.setValue(json);
                }
            },
            hasVisualSettings() {
                return (this.currentSection === 'sections' && ['download_cards', 'products', 'brands', 'categories', 'cta'].includes(this.form.section_type)) || this.currentSection === 'footer_columns';
            },
            addDownloadItem() {
                if (!this.componentSettings.items) this.$set(this.componentSettings, 'items', []);
                this.componentSettings.items.push({ title: '', url: '' });
                this.syncComponentSettings();
            },
            removeSettingItem(index) {
                if (!this.componentSettings.items) return;
                this.componentSettings.items.splice(index, 1);
                this.syncComponentSettings();
            },
            addFooterItem() {
                if (!this.componentSettings.items) this.$set(this.componentSettings, 'items', []);
                this.componentSettings.items.push({ label: '', url: '', icon: '' });
                this.syncComponentSettings();
            },
            applyFooterPreset(type) {
                this.$set(this.form, 'column_type', type);
                const presets = {
                    contact: [
                        { label: 'Tel: 33 0000 0000', url: 'tel:3300000000', icon: 'bi bi-telephone' },
                        { label: 'contacto@empresa.com', url: 'mailto:contacto@empresa.com', icon: 'bi bi-envelope' },
                        { label: 'Guadalajara, Jalisco', url: '', icon: 'bi bi-geo-alt' }
                    ],
                    links: [
                        { label: 'Productos', url: 'productos', icon: '' },
                        { label: 'Empresa', url: 'empresa', icon: '' },
                        { label: 'Contacto', url: 'contacto', icon: '' }
                    ],
                    social: [
                        { label: 'Facebook', url: 'https://facebook.com/', icon: 'bi bi-facebook' },
                        { label: 'Instagram', url: 'https://instagram.com/', icon: 'bi bi-instagram' },
                        { label: 'WhatsApp', url: 'https://wa.me/520000000000', icon: 'bi bi-whatsapp' }
                    ],
                    legal: [
                        { label: 'Aviso de privacidad', url: 'pagina/aviso-de-privacidad', icon: '' },
                        { label: 'Términos y condiciones', url: 'pagina/terminos-condiciones', icon: '' }
                    ]
                };
                this.$set(this.componentSettings, 'items', presets[type] || []);
                this.syncComponentSettings();
            },
            dynamicOptions(field) { return this.options[field.options] || []; },
            displayValue(item, field) {
                if (field.type === 'checkbox') return item[field.name] == 1 ? 'Sí' : 'No';
                if (field.type === 'select') {
                    const found = this.dynamicOptions(field).find(option => option.value == item[field.name]);
                    return found ? found.label : item[field.name];
                }
                if (field.type === 'select_static') {
                    const found = field.options.find(option => option.value == item[field.name]);
                    return found ? found.label : item[field.name];
                }
                return item[field.name] || '-';
            },
            templateLabel(value) {
                value = value || 'default';
                const labels = {
                    default: 'Predeterminada',
                    home: 'Inicio',
                    content: 'Contenido',
                    catalog: 'Catálogo'
                };
                return labels[value] || value;
            },
            sectionTypeLabel(value) {
                const labels = {
                    hero: 'Hero',
                    content: 'Contenido',
                    content_image: 'Texto con imagen',
                    feature_grid: 'Servicios',
                    products: 'Productos',
                    brands: 'Marcas',
                    categories: 'Categorías',
                    download_cards: 'Descargas',
                    contact_info: 'Contacto',
                    cta: 'CTA',
                    banner: 'Banner',
                    block: 'Bloque reutilizable'
                };
                return labels[value] || value || 'Contenido';
            },
            blockLabel(targetId, sectionKey) {
                const byId = (this.options.blocks || []).find(option => Number(option.value) === Number(targetId || 0));
                if (byId) return byId.label;
                const byCode = (this.items.blocks || []).find(block => String(block.code || '') === String(sectionKey || '') && block.active == 1);
                if (byCode) return byCode.name;
                return 'Sin bloque vinculado';
            },
            blockWarning(section) {
                if (!section || section.section_type !== 'block') return '';
                if (this.blockExists(section)) return '';
                return 'Bloque reutilizable no encontrado o inactivo.';
            },
            blockExists(section) {
                const targetId = Number(section.target_id || 0);
                if (targetId > 0 && (this.options.blocks || []).some(option => Number(option.value) === targetId)) {
                    return true;
                }
                return (this.items.blocks || []).some(block => String(block.code || '') === String(section.section_key || '') && block.active == 1);
            },
            canPreviewPage(item) {
                return !!(item && item.id);
            },
            previewUrl(item) {
                if (!item) return '#';
                if (item.published == 1 && this.isActive(item)) {
                    if (item.is_home == 1) return frontendBaseUrl;
                    return frontendBaseUrl + 'pagina/' + String(item.slug || '').replace(/^\/+/, '');
                }
                return frontendAdminPreviewUrl.replace(/\/+$/, '') + '/' + encodeURIComponent(item.id);
            },
            fieldHelp(field) {
                return field && field.help ? field.help : '';
            },
            previewTitle(item) {
                return item && item.published == 1 && this.isActive(item)
                    ? 'Vista previa pública'
                    : 'Vista previa administrativa';
            },
            isActive(item) { return typeof item.active === 'undefined' || item.active == 1; },
            assetUrl(path) {
                if (!path) return '';
                if (/^https?:\/\//.test(path)) return path;
                return frontendBaseUrl + path.replace(/^\/+/, '');
            },
            showModal(id) {
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(element).show(); return; }
                if (window.jQuery && $.fn.modal) $('#' + id).modal('show');
            },
            hideModal(id) {
                this.syncEditors();
                this.destroyEditors();
                const element = document.getElementById(id);
                if (!element) return;
                if (window.bootstrap && bootstrap.Modal) {
                    const instance = bootstrap.Modal.getInstance(element);
                    if (instance) instance.hide();
                } else if (window.jQuery && $.fn.modal) {
                    $('#' + id).modal('hide');
                }
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            }
        }
    });
});
</script>
