<script>
window.onload = function() {
    new Vue({
        el: '#app-sales',
        data: {
            loading: true,
            error: '',
            quotes: [],
            orders: [],
            deliveries: [],
            viewMode: '<?php echo $initial_view; ?>',
            selected: null,
            selectedOrder: null,
            stats: { quotes: 0, orders: 0, deliveries: 0, prequote: 0, requested: 0, approved: 0, rejected: 0 },
            periodFilters: { start_date: '', end_date: '' },
            options: { customers: [], sellers: [], products: [], brands: [], categories: [], warehouses: [] },
            quoteForm: { party_id: '', seller_id: 0, quote_mode: 'quote', items: [], customer_notes: '', internal_notes: '', offline_uuid: '' },
            lineForm: { product_id: '', product_query: '', product_type: 'product', quantity: 1, search_open: false, search_results: [] },
            closeForm: { party_id: '' },
            deliveryForm: { order_id: 0, warehouse_id: '', items: [] },
            filters: { q: '', brand_id: '', category_id: '', stock: '' },
            searchTimer: null,
            noImage: <?php echo json_encode($no_image_svg); ?>,
            capturePage: <?php echo $capture_page ? 'true' : 'false'; ?>,
            captureMode: <?php echo json_encode($capture_mode); ?>,
            offline: { online: navigator.onLine, drafts: [], syncing: false, saveTimer: null, lastSaved: '' }
        },
        computed: {
            filteredProducts() {
                const q = (this.filters.q || '').toLowerCase();
                return (this.options.products || []).filter(product => {
                    if (q && (String(product.label || '').toLowerCase().indexOf(q) < 0 && String(product.sku || '').toLowerCase().indexOf(q) < 0)) return false;
                    if (this.filters.brand_id && Number(product.brand_id) !== Number(this.filters.brand_id)) return false;
                    if (this.filters.category_id && Number(product.category_id) !== Number(this.filters.category_id)) return false;
                    if (this.filters.stock === 'available' && Number(product.available_stock || 0) <= 0) return false;
                    if (this.filters.stock === 'zero' && Number(product.available_stock || 0) > 0) return false;
                    return true;
                });
            },
            productSearchResults() {
                return this.lineForm.search_results || [];
            },
            selectedProduct() {
                return this.productById(this.lineForm.product_id);
            },
            quoteTotal() {
                if (this.quoteForm.quote_mode === 'prequote') return 0;
                return (this.quoteForm.items || []).reduce((sum, item) => sum + this.lineTotal(item), 0);
            },
            quoteCurrency() {
                const first = (this.quoteForm.items || [])[0];
                return first ? this.productCurrency(first.product_id) : 'MXN';
            }
        },
        mounted() {
            if (this.capturePage) {
                this.prepareQuoteForm(this.captureMode || 'quote');
            }
            this.loadData();
            this.loadDrafts();
            window.addEventListener('online', this.onOnline);
            window.addEventListener('offline', this.onOffline);
        },
        watch: {
            quoteForm: {
                deep: true,
                handler: function() {
                    this.scheduleDraftSave();
                }
            }
        },
        methods: {
            loadData() {
                this.loading = true;
                this.error = '';
                const params = [];
                if (this.periodFilters.start_date) params.push('start_date=' + encodeURIComponent(this.periodFilters.start_date));
                if (this.periodFilters.end_date) params.push('end_date=' + encodeURIComponent(this.periodFilters.end_date));
                let url = '<?php echo Uri::create('admin/sales/data'); ?>';
                if (params.length) url += '?' + params.join('&');
                fetch(url)
                    .then(res => window.coreAppJson ? window.coreAppJson(res) : res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.orders = data.orders || [];
                        this.deliveries = data.deliveries || [];
                        this.stats = data.stats || this.stats;
                        this.periodFilters = data.period_filters || this.periodFilters;
                        this.options = data.options || this.options;
                        this.cacheCatalogs();
                    })
                    .catch(() => {
                        this.loading = false;
                        this.offline.online = false;
                        this.error = 'No se pudo cargar ventas. Si estás sin conexión se intentará usar catálogos locales.';
                        this.hydrateOptionsFromCache();
                    });
            },
            onOnline() {
                this.offline.online = true;
            },
            onOffline() {
                this.offline.online = false;
            },
            cacheCatalogs() {
                if (!window.CoreOffline) return;
                window.CoreOffline.put('catalog:sales:options', this.options);
            },
            hydrateOptionsFromCache() {
                if (!window.CoreOffline) return Promise.resolve();
                return window.CoreOffline.get('catalog:sales:options').then(options => {
                    if (options && (!this.options.products || this.options.products.length === 0)) {
                        this.options = options;
                    }
                });
            },
            money(value) {
                return Number(value || 0).toFixed(2);
            },
            statusLabel(status) {
                const labels = {
                    prequote: 'Precotización',
                    requested: 'Solicitada',
                    approved: 'Aprobada',
                    rejected: 'Rechazada',
                    converted: 'Convertida',
                    open: 'Abierto',
                    partial: 'Parcial / backorder',
                    delivered: 'Entregado',
                    billed: 'Facturado',
                    closed: 'Cerrado'
                };
                return labels[status] || status;
            },
            statusClass(status) {
                const classes = {
                    prequote: 'badge-secondary',
                    requested: 'badge-warning',
                    approved: 'badge-success',
                    rejected: 'badge-danger',
                    converted: 'badge-primary',
                    open: 'badge-info',
                    partial: 'badge-warning',
                    delivered: 'badge-success',
                    billed: 'badge-primary',
                    closed: 'badge-secondary'
                };
                return classes[status] || 'badge-secondary';
            },
            openDetail(quote) {
                this.selected = JSON.parse(JSON.stringify(quote));
                this.closeForm = { party_id: this.selected.party_id || '' };
                this.showModal('modal-quote');
            },
            saveSelected() {
                if (!this.selected) return;
                this.setStatus(this.selected, this.selected.status, this.selected.internal_notes, true);
            },
            setStatus(quote, status) {
                this.error = '';
                fetch('<?php echo Uri::create('admin/sales/update_status'); ?>', window.coreAppFetchOptions({
                    id: quote.id,
                    status: status,
                    internal_notes: quote.internal_notes || ''
                }))
                    .then(res => window.coreAppJson ? window.coreAppJson(res) : res.json())
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.orders = data.orders || this.orders;
                        this.deliveries = data.deliveries || this.deliveries;
                        this.stats = data.stats || this.stats;
                        this.hideModal('modal-quote');
                    });
            },
            closePrequote() {
                if (!this.selected) return;
                if (!this.closeForm.party_id) {
                    this.error = 'Selecciona cliente para cerrar con precios.';
                    return;
                }
                fetch('<?php echo Uri::create('admin/sales/close_prequote'); ?>', window.coreAppFetchOptions({
                    id: this.selected.id,
                    party_id: this.closeForm.party_id,
                    internal_notes: this.selected.internal_notes || ''
                }))
                    .then(res => window.coreAppJson ? window.coreAppJson(res) : res.json())
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.orders = data.orders || this.orders;
                        this.deliveries = data.deliveries || this.deliveries;
                        this.stats = data.stats || this.stats;
                        this.hideModal('modal-quote');
                    });
            },
            createOrderFromQuote() {
                if (!this.selected) return;
                this.error = '';
                fetch('<?php echo Uri::create('admin/sales/create_order_from_quote'); ?>', window.coreAppFetchOptions({ id: this.selected.id }))
                    .then(res => window.coreAppJson ? window.coreAppJson(res) : res.json())
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.orders = data.orders || this.orders;
                        this.deliveries = data.deliveries || this.deliveries;
                        this.stats = data.stats || this.stats;
                        this.selected = this.quotes.find(item => Number(item.id) === Number(this.selected.id)) || this.selected;
                        this.viewMode = 'orders';
                    });
            },
            openFulfillment(order) {
                this.selectedOrder = JSON.parse(JSON.stringify(order));
                const defaultWarehouse = (this.options.warehouses || [])[0] || {};
                this.deliveryForm = {
                    order_id: order.id,
                    warehouse_id: defaultWarehouse.value || '',
                    items: (order.items || []).filter(item => Number(item.pending_quantity || 0) > 0).map(item => ({
                        order_item_id: item.id,
                        product_id: item.product_id,
                        sku: item.sku,
                        name: item.name,
                        image_url: item.image_url,
                        available_stock: item.available_stock,
                        ordered_quantity: item.quantity,
                        delivered_quantity: item.delivered_quantity,
                        pending_quantity: item.pending_quantity,
                        quantity: item.pending_quantity
                    }))
                };
                this.showModal('modal-fulfillment');
            },
            createDeliveryFromOrder() {
                this.error = '';
                fetch('<?php echo Uri::create('admin/sales/create_delivery_from_order'); ?>', window.coreAppFetchOptions({
                    id: this.deliveryForm.order_id,
                    warehouse_id: this.deliveryForm.warehouse_id,
                    items: this.deliveryForm.items
                }))
                    .then(res => window.coreAppJson ? window.coreAppJson(res) : res.json())
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.orders = data.orders || this.orders;
                        this.deliveries = data.deliveries || this.deliveries;
                        this.stats = data.stats || this.stats;
                        if (this.selected) {
                            this.selected = this.quotes.find(item => Number(item.id) === Number(this.selected.id)) || this.selected;
                        }
                        this.selectedOrder = null;
                        this.viewMode = 'deliveries';
                        this.hideModal('modal-fulfillment');
                    });
            },
            invoiceDelivery(delivery) {
                this.error = '';
                fetch('<?php echo Uri::create('admin/billing/create_from_delivery'); ?>', window.coreAppFetchOptions({ delivery_id: delivery.id }))
                    .then(res => window.coreAppJson ? window.coreAppJson(res) : res.json())
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.error = '';
                        this.offline.lastSaved = 'Factura creada: ' + data.folio;
                        this.loadData();
                        this.hideModal('modal-quote');
                    });
            },
            showModal(id) {
                if (window.jQuery) {
                    $('#' + id).modal('show');
                }
            },
            hideModal(id) {
                if (window.jQuery) {
                    $('#' + id).modal('hide');
                }
            },
            newQuote() {
                this.prepareQuoteForm('quote');
                this.hydrateOptionsFromCache();
                this.showModal('modal-new-quote');
            },
            newPrequote() {
                this.prepareQuoteForm('prequote');
                this.hydrateOptionsFromCache();
                this.showModal('modal-new-quote');
            },
            prepareQuoteForm(mode) {
                this.quoteForm = {
                    party_id: '',
                    seller_id: 0,
                    quote_mode: mode,
                    items: [],
                    customer_notes: '',
                    internal_notes: mode === 'prequote' ? 'Precotización sin precios para mostrar catálogo al cliente.' : '',
                    offline_uuid: this.newOfflineUuid()
                };
                this.lineForm = { product_id: '', product_query: '', product_type: 'product', quantity: 1, search_open: false, search_results: [] };
            },
            onProductSearchInput() {
                this.lineForm.product_id = '';
                this.lineForm.search_open = true;
                clearTimeout(this.searchTimer);
                const q = (this.lineForm.product_query || '').trim();
                if (q.length < 2) {
                    this.lineForm.search_results = [];
                    return;
                }
                this.searchTimer = setTimeout(() => this.searchProducts(q), 220);
            },
            searchProducts(q) {
                const url = '<?php echo Uri::create('admin/sales/product_search'); ?>'
                    + '?q=' + encodeURIComponent(q)
                    + '&limit=25';
                fetch(url)
                    .then(res => window.coreAppJson ? window.coreAppJson(res) : res.json())
                    .then(data => {
                        if (data.error) return;
                        this.lineForm.search_results = data.products || [];
                        this.mergeProducts(data.products || []);
                    });
            },
            mergeProducts(products) {
                products.forEach(product => {
                    const exists = (this.options.products || []).some(item => Number(item.value) === Number(product.value));
                    if (!exists) {
                        this.options.products.push(product);
                    }
                });
            },
            refreshCatalog() {
                const url = '<?php echo Uri::create('admin/sales/product_search'); ?>'
                    + '?q=' + encodeURIComponent(this.filters.q || '')
                    + '&brand_id=' + encodeURIComponent(this.filters.brand_id || '')
                    + '&category_id=' + encodeURIComponent(this.filters.category_id || '')
                    + '&stock=' + encodeURIComponent(this.filters.stock || '')
                    + '&limit=120';
                fetch(url)
                    .then(res => window.coreAppJson ? window.coreAppJson(res) : res.json())
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.options.products = data.products || [];
                    });
            },
            selectProduct(product) {
                this.lineForm.product_id = product.value;
                this.lineForm.product_query = product.label;
                this.lineForm.search_open = false;
                this.mergeProducts([product]);
            },
            selectFirstSearchResult() {
                if (this.productSearchResults.length) {
                    this.selectProduct(this.productSearchResults[0]);
                }
            },
            addSelectedLine() {
                this.addLine();
            },
            addLine() {
                if (!this.lineForm.product_id) return;
                this.quoteForm.items.push({
                    product_id: this.lineForm.product_id,
                    quantity: this.lineForm.quantity || 1
                });
                this.lineForm = { product_id: '', product_query: '', product_type: 'product', quantity: 1, search_open: false, search_results: [] };
            },
            quickAdd(product) {
                this.quoteForm.items.push({ product_id: product.value, quantity: this.lineForm.quantity || 1 });
            },
            quickAddRange(product, range) {
                this.quoteForm.items.push({
                    product_id: product.value,
                    quantity: Number(range.min_quantity || this.lineForm.quantity || 1)
                });
            },
            addFilteredProducts() {
                this.filteredProducts.forEach(product => this.quoteForm.items.push({ product_id: product.value, quantity: this.lineForm.quantity || 1 }));
            },
            addBrandProducts() {
                const brandId = this.selectedProduct.brand_id || this.filters.brand_id;
                if (!brandId) return;
                const url = '<?php echo Uri::create('admin/sales/product_search'); ?>'
                    + '?brand_id=' + encodeURIComponent(brandId)
                    + '&limit=120';
                fetch(url)
                    .then(res => window.coreAppJson ? window.coreAppJson(res) : res.json())
                    .then(data => {
                        if (data.error) return;
                        this.mergeProducts(data.products || []);
                        (data.products || []).forEach(product => this.quoteForm.items.push({ product_id: product.value, quantity: this.lineForm.quantity || 1 }));
                    });
            },
            addCategoryProducts() {
                if (!this.filters.category_id) return;
                const url = '<?php echo Uri::create('admin/sales/product_search'); ?>'
                    + '?category_id=' + encodeURIComponent(this.filters.category_id)
                    + '&limit=120';
                fetch(url)
                    .then(res => window.coreAppJson ? window.coreAppJson(res) : res.json())
                    .then(data => {
                        if (data.error) return;
                        this.mergeProducts(data.products || []);
                        (data.products || []).forEach(product => this.quoteForm.items.push({ product_id: product.value, quantity: this.lineForm.quantity || 1 }));
                    });
            },
            clearFilters() {
                this.filters = { q: '', brand_id: '', category_id: '', stock: '' };
            },
            addSelectedRange() {
                if (!this.selectedProduct.value || !this.selectedProduct.price_ranges || !this.selectedProduct.price_ranges.length) return;
                this.quickAddRange(this.selectedProduct, this.selectedProduct.price_ranges[0]);
            },
            removeLine(index) {
                this.quoteForm.items.splice(index, 1);
            },
            productById(productId) {
                return (this.options.products || []).find(product => Number(product.value) === Number(productId)) || {};
            },
            productLabel(productId) {
                return this.productById(productId).label || '-';
            },
            productPrice(productId, quantity) {
                const product = this.productById(productId);
                const ranges = product.price_ranges || [];
                let price = Number(product.price || 0);
                ranges.forEach(range => {
                    const min = Number(range.min_quantity || 1);
                    const max = Number(range.max_quantity || 0);
                    if (Number(quantity || 1) >= min && (max <= 0 || Number(quantity || 1) <= max)) {
                        price = Number(range.price || price);
                    }
                });
                return price;
            },
            productCurrency(productId) {
                return this.productById(productId).currency_code || 'MXN';
            },
            productImage(productId) {
                return this.productById(productId).image_url || this.noImage;
            },
            productStock(productId) {
                return this.productById(productId).available_stock || 0;
            },
            lineTotal(item) {
                if (this.quoteForm.quote_mode === 'prequote') return 0;
                return Number(item.quantity || 0) * Number(this.productPrice(item.product_id, item.quantity) || 0);
            },
            saveQuote() {
                this.ensureOfflineUuid();
                fetch('<?php echo Uri::create('admin/sales/create_quote'); ?>', window.coreAppFetchOptions(this.quoteForm))
                    .then(res => {
                        if (window.coreAppJson) {
                            return window.coreAppJson(res);
                        }
                        if (!res.ok) {
                            return res.text().then(text => {
                                let message = 'Error HTTP ' + res.status;
                                try {
                                    const payload = JSON.parse(text);
                                    message = payload.error || message;
                                } catch (e) {
                                    if (res.status === 400) {
                                        message = 'La sesión de seguridad expiró o no se envió correctamente. Recarga la pantalla e intenta de nuevo.';
                                    } else if (res.status === 404) {
                                        message = 'No se encontró la ruta para guardar la cotización. Recarga la pantalla e intenta de nuevo.';
                                    }
                                }
                                throw new Error(message);
                            });
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data.error) {
                            this.error = data.error;
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.orders = data.orders || this.orders;
                        this.deliveries = data.deliveries || this.deliveries;
                        this.stats = data.stats || this.stats;
                        this.removeDraftByUuid(this.quoteForm.offline_uuid);
                        if (this.capturePage) {
                            window.location.href = '<?php echo Uri::create('admin/sales'); ?>';
                            return;
                        }
                        this.hideModal('modal-new-quote');
                    })
                    .catch(error => {
                        if (error && error.name !== 'TypeError') {
                            this.error = 'No se pudo guardar la cotización: ' + (error.message || 'respuesta inválida del servidor');
                            return;
                        }
                        this.saveDraftNow();
                        this.error = 'Sin conexión. La cotización quedó guardada como borrador en este equipo.';
                    });
            },
            newOfflineUuid() {
                return window.CoreOffline ? window.CoreOffline.uuid('quote') : ('quote_' + Date.now());
            },
            ensureOfflineUuid() {
                if (!this.quoteForm.offline_uuid) {
                    this.quoteForm.offline_uuid = this.newOfflineUuid();
                }
            },
            draftKey(uuid) {
                return 'draft:sales_quote:' + uuid;
            },
            scheduleDraftSave() {
                if (!this.quoteForm || (!this.quoteForm.party_id && (!this.quoteForm.items || this.quoteForm.items.length === 0))) return;
                clearTimeout(this.offline.saveTimer);
                this.offline.saveTimer = setTimeout(this.saveDraftNow, 800);
            },
            saveDraftNow() {
                if (!window.CoreOffline || !this.quoteForm) return;
                this.ensureOfflineUuid();
                const customer = (this.options.customers || []).find(c => Number(c.value) === Number(this.quoteForm.party_id));
                const payload = {
                    module: 'sales',
                    type: 'sales_quote',
                    label: customer ? customer.label : 'Cotizacion local',
                    data: JSON.parse(JSON.stringify(this.quoteForm)),
                    created_at: Date.now(),
                    updated_at: Date.now()
                };
                window.CoreOffline.put(this.draftKey(this.quoteForm.offline_uuid), payload).then(() => {
                    this.offline.lastSaved = new Date().toLocaleTimeString('es-MX');
                    this.loadDrafts();
                });
            },
            loadDrafts() {
                if (!window.CoreOffline) return;
                window.CoreOffline.list('draft:sales_quote:').then(items => {
                    this.offline.drafts = items.sort((a, b) => (b.updated_at || 0) - (a.updated_at || 0));
                });
            },
            recoverDraft(draft) {
                this.quoteForm = JSON.parse(JSON.stringify(draft.value.data || {}));
                this.lineForm = { product_id: '', quantity: 1 };
                this.hydrateOptionsFromCache();
                if (!this.capturePage) {
                    this.showModal('modal-new-quote');
                }
            },
            discardDraft(draft) {
                if (!window.CoreOffline) return;
                window.CoreOffline.remove(draft.key).then(() => this.loadDrafts());
            },
            removeDraftByUuid(uuid) {
                if (!window.CoreOffline || !uuid) return;
                window.CoreOffline.remove(this.draftKey(uuid)).then(() => this.loadDrafts());
            },
            syncDrafts() {
                if (!this.offline.online || !this.offline.drafts.length) return;
                this.offline.syncing = true;
                const drafts = this.offline.drafts.slice();
                const syncOne = index => {
                    if (index >= drafts.length) {
                        this.offline.syncing = false;
                        this.loadData();
                        this.loadDrafts();
                        return;
                    }
                    const draft = drafts[index];
                    fetch('<?php echo Uri::create('admin/sales/create_quote'); ?>', window.coreAppFetchOptions(draft.value.data))
                        .then(res => {
                            if (!res.ok) {
                                throw new Error('HTTP ' + res.status);
                            }
                            return res.json();
                        })
                        .then(data => {
                            if (!data.error) {
                                return window.CoreOffline.remove(draft.key);
                            }
                            this.error = data.error;
                        })
                        .catch(error => {
                            this.error = 'No se pudo sincronizar una cotización local. Revisa sesión, permisos o recarga la pantalla.';
                        })
                        .then(() => syncOne(index + 1));
                };
                syncOne(0);
            }
        }
    });
};
</script>
