<div id="app-sales">
    <?php
    $no_image_svg = 'data:image/svg+xml;charset=UTF-8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="360" height="260" viewBox="0 0 360 260"><rect width="360" height="260" fill="#eef3f7"/><path d="M72 178h216l-64-82-48 60-34-44-70 66z" fill="#cbd5e1"/><circle cx="130" cy="86" r="24" fill="#cbd5e1"/><text x="180" y="226" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="20" fill="#64748b">Sin imagen</text></svg>');
    ?>
    <style>
        .quote-workbench { display: grid; grid-template-columns: minmax(0, 1.25fr) 420px; gap: 16px; }
        .quote-product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; max-height: 58vh; overflow: auto; padding-right: 4px; }
        .quote-product-card { border: 1px solid #dde3ea; border-radius: 8px; background: #fff; overflow: hidden; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
        .quote-product-card:hover, .quote-product-card.active { border-color: #007bff; box-shadow: 0 6px 16px rgba(15,23,42,.10); }
        .quote-product-card img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; background: #eef3f7; }
        .quote-product-body { padding: 9px; }
        .quote-product-title { font-size: .88rem; line-height: 1.25; font-weight: 700; min-height: 36px; }
        .quote-meta { display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; font-size: .78rem; color: #6c757d; }
        .quote-thumb { width: 54px; height: 44px; border-radius: 6px; border: 1px solid #dde3ea; object-fit: cover; background: #eef3f7; }
        .quote-cart { position: sticky; top: 12px; }
        .quote-toolbar { display: grid; grid-template-columns: 1.3fr 1fr 1fr auto; gap: 8px; align-items: end; }
        .price-hidden .money-cell, .price-hidden .price-text { display: none; }
        .range-chip { display: inline-block; border: 1px solid #dee2e6; border-radius: 999px; padding: 2px 7px; margin: 2px 2px 0 0; font-size: .72rem; color: #495057; background: #f8f9fa; cursor: pointer; }
        .range-chip:hover { border-color: #007bff; color: #0056b3; }
        @media (max-width: 1100px) { .quote-workbench { grid-template-columns: 1fr; } .quote-cart { position: static; } }
    </style>
    <div class="row">
        <div class="col-lg-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ stats.quotes || 0 }}</h3>
                    <p>Cotizaciones</p>
                </div>
                <div class="icon"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ stats.prequote || 0 }}</h3>
                    <p>Precotizaciones</p>
                </div>
                <div class="icon"><i class="bi bi-bag"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ stats.requested || 0 }}</h3>
                    <p>Pendientes</p>
                </div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ stats.approved || 0 }}</h3>
                    <p>Aprobadas</p>
                </div>
                <div class="icon"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ stats.rejected || 0 }}</h3>
                    <p>Rechazadas</p>
                </div>
                <div class="icon"><i class="bi bi-x-circle"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Solicitudes de cotizacion</h3>
            <div class="card-tools">
                <span class="badge mr-2" :class="offline.online ? 'badge-success' : 'badge-warning'">
                    {{ offline.online ? 'En linea' : 'Sin conexion' }}
                </span>
                <button class="btn btn-outline-info btn-sm mr-2" @click="syncDrafts" :disabled="offline.syncing || offline.drafts.length === 0">
                    <i class="bi bi-arrow-repeat"></i> Sincronizar {{ offline.drafts.length || '' }}
                </button>
                <button class="btn btn-outline-secondary btn-sm mr-1" @click="newPrequote">
                    <i class="bi bi-bag-plus"></i> Precotizacion
                </button>
                <button class="btn btn-primary btn-sm" @click="newQuote">
                    <i class="bi bi-plus-lg"></i> Nueva cotizacion
                </button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="offline.drafts.length" class="alert alert-warning">
                <strong>Borradores en este equipo:</strong>
                <span v-for="draft in offline.drafts" :key="draft.key" class="badge badge-light border ml-2">
                    {{ draft.value.label || 'Cotizacion local' }}
                    <a href="#" @click.prevent="recoverDraft(draft)">abrir</a>
                    <a href="#" class="text-danger" @click.prevent="discardDraft(draft)">quitar</a>
                </span>
            </div>
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando ventas...</p>
            </div>

            <table v-show="!loading" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Productos</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="quote in quotes" :key="quote.id">
                        <td><strong>{{ quote.folio }}</strong><div class="text-muted small">{{ quote.source }}</div></td>
                        <td>{{ quote.party_name || '-' }}<div class="text-muted small">{{ quote.party_email || '' }}</div></td>
                        <td><span class="badge" :class="statusClass(quote.status)">{{ statusLabel(quote.status) }}</span></td>
                        <td>{{ quote.currency_code }} {{ money(quote.total) }}</td>
                        <td>{{ quote.created_label }}</td>
                        <td>
                            <div v-for="item in quote.items" :key="item.sku + item.name" class="small d-flex align-items-center mb-1">
                                <img class="quote-thumb mr-2" :src="item.image_url || noImage" :alt="item.name">
                                <span>{{ item.quantity }} x {{ item.name }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" @click="openDetail(quote)">Detalle</button>
                                <button class="btn btn-outline-secondary" @click="setStatus(quote, 'reviewed')">Revisada</button>
                                <button class="btn btn-outline-success" @click="setStatus(quote, 'approved')">Aprobar</button>
                                <button class="btn btn-outline-danger" @click="setStatus(quote, 'rejected')">Rechazar</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="quotes.length === 0">
                        <td colspan="7" class="text-center text-muted">Todavia no hay cotizaciones.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-new-quote" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ quoteForm.quote_mode === 'prequote' ? 'Nueva precotizacion' : 'Nueva cotizacion' }}</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-new-quote')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border py-2">
                        <span :class="offline.online ? 'text-success' : 'text-warning'">{{ offline.online ? 'Con conexion' : 'Sin conexion' }}</span>
                        <span v-if="offline.lastSaved" class="text-muted ml-2">Borrador local guardado {{ offline.lastSaved }}</span>
                    </div>
                    <div class="quote-toolbar mb-3">
                        <div class="form-group mb-0">
                            <label>Cliente</label>
                            <select class="form-control" v-model="quoteForm.party_id">
                                <option value="">Selecciona cliente</option>
                                <option v-for="customer in options.customers" :value="customer.value">{{ customer.label }}</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label>Modo</label>
                            <select class="form-control" v-model="quoteForm.quote_mode">
                                <option value="quote">Cotizacion con precios</option>
                                <option value="prequote">Precotizacion / catalogo sin precios</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label>Cantidad rapida</label>
                            <input type="number" min="1" step="1" class="form-control" v-model.number="lineForm.quantity">
                        </div>
                        <button class="btn btn-outline-primary" @click="addSelectedLine" :disabled="!lineForm.product_id">Agregar seleccionado</button>
                    </div>

                    <div class="quote-workbench" :class="quoteForm.quote_mode === 'prequote' ? 'price-hidden' : ''">
                        <div>
                            <div class="border rounded p-2 mb-2">
                                <div class="row">
                                    <div class="col-md-4"><input class="form-control form-control-sm" v-model="filters.q" placeholder="Buscar SKU o producto"></div>
                                    <div class="col-md-3"><select class="form-control form-control-sm" v-model="filters.brand_id"><option value="">Todas las marcas</option><option v-for="brand in options.brands" :value="brand.value">{{ brand.label }}</option></select></div>
                                    <div class="col-md-3"><select class="form-control form-control-sm" v-model="filters.category_id"><option value="">Todas las categorias</option><option v-for="category in options.categories" :value="category.value">{{ category.label }}</option></select></div>
                                    <div class="col-md-2"><select class="form-control form-control-sm" v-model="filters.stock"><option value="">Existencia</option><option value="available">Disponible</option><option value="zero">Sin existencia</option></select></div>
                                </div>
                                <div class="mt-2">
                                    <button class="btn btn-xs btn-outline-secondary mr-1" @click="addFilteredProducts">Agregar filtrados</button>
                                    <button class="btn btn-xs btn-outline-secondary mr-1" @click="addBrandProducts" :disabled="!filters.brand_id">Agregar marca</button>
                                    <button class="btn btn-xs btn-outline-secondary mr-1" @click="addCategoryProducts" :disabled="!filters.category_id">Agregar categoria</button>
                                    <button class="btn btn-xs btn-outline-secondary" @click="clearFilters">Limpiar filtros</button>
                                    <span class="text-muted small ml-2">{{ filteredProducts.length }} productos</span>
                                </div>
                            </div>
                            <div class="quote-product-grid">
                                <div class="quote-product-card" v-for="product in filteredProducts" :key="product.value" :class="{active: Number(lineForm.product_id) === Number(product.value)}" @click="selectProduct(product)">
                                    <img :src="product.image_url || noImage" :alt="product.label">
                                    <div class="quote-product-body">
                                        <div class="quote-product-title">{{ product.label }}</div>
                                        <div class="quote-meta"><span>{{ product.brand_name || 'Sin marca' }}</span><span>{{ product.category_name || '' }}</span></div>
                                        <div class="quote-meta mt-1"><span>Exist. {{ money(product.available_stock) }}</span><span class="price-text">{{ product.currency_code }} {{ money(product.price) }}</span></div>
                                        <div v-if="product.price_ranges && product.price_ranges.length" class="price-text mt-1">
                                            <button type="button" class="range-chip" v-for="range in product.price_ranges" @click.stop="quickAddRange(product, range)" :title="'Agregar cantidad ' + money(range.min_quantity)">
                                                +{{ money(range.min_quantity) }}: {{ range.currency_code }} {{ money(range.price) }}
                                            </button>
                                        </div>
                                        <button class="btn btn-xs btn-primary mt-2" @click.stop="quickAdd(product)">Agregar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="quote-cart">
                            <div class="card card-outline card-info">
                                <div class="card-header py-2">
                                    <strong>Partidas</strong>
                                    <span class="badge badge-light float-right">{{ quoteForm.items.length }}</span>
                                </div>
                                <div class="card-body p-2">
                                    <table class="table table-sm table-bordered mb-2" v-if="quoteForm.items.length">
                                        <thead><tr><th>Producto</th><th>Cant.</th><th class="money-cell">Precio</th><th class="money-cell">Total</th><th></th></tr></thead>
                                        <tbody>
                                            <tr v-for="(item, index) in quoteForm.items" :key="index">
                                                <td><div class="d-flex align-items-center"><img class="quote-thumb mr-2" :src="productImage(item.product_id)" :alt="productLabel(item.product_id)"><div><strong class="small">{{ productLabel(item.product_id) }}</strong><div class="text-muted small">Exist. {{ money(productStock(item.product_id)) }}</div></div></div></td>
                                                <td><input class="form-control form-control-sm" type="number" min="1" step="1" v-model.number="item.quantity"></td>
                                                <td class="money-cell">{{ productCurrency(item.product_id) }} {{ money(productPrice(item.product_id, item.quantity)) }}</td>
                                                <td class="money-cell">{{ productCurrency(item.product_id) }} {{ money(lineTotal(item)) }}</td>
                                                <td class="text-center"><button class="btn btn-xs btn-danger" @click="removeLine(index)">Quitar</button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div v-else class="text-center text-muted p-3">Selecciona productos del catalogo.</div>
                                    <div class="d-flex justify-content-between border-top pt-2 money-cell">
                                        <strong>Total estimado</strong>
                                        <strong>{{ quoteCurrency }} {{ money(quoteTotal) }}</strong>
                                    </div>
                                    <div v-if="quoteForm.quote_mode === 'prequote'" class="alert alert-secondary mt-2 mb-0 py-2 small">
                                        Modo catalogo: no se muestran ni guardan precios. Podras cerrar la cotizacion despues.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notas para el cliente</label>
                        <textarea class="form-control" rows="2" v-model="quoteForm.customer_notes"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Notas internas</label>
                        <textarea class="form-control" rows="2" v-model="quoteForm.internal_notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-new-quote')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveQuote">{{ quoteForm.quote_mode === 'prequote' ? 'Guardar precotizacion' : 'Guardar cotizacion' }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-quote" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" v-if="selected">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ selected.folio }}</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-quote')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Cliente</h6>
                            <p class="mb-1"><strong>{{ selected.party_name || '-' }}</strong></p>
                            <p class="mb-1 text-muted">{{ selected.party_email || '' }}</p>
                            <p class="mb-1 text-muted">{{ selected.party_phone || '' }}</p>
                            <p class="mb-3 text-muted">{{ selected.party_rfc || '' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Resumen</h6>
                            <p class="mb-1"><strong>Estado:</strong> <span class="badge" :class="statusClass(selected.status)">{{ statusLabel(selected.status) }}</span></p>
                            <p class="mb-1"><strong>Fecha:</strong> {{ selected.created_label }}</p>
                            <p class="mb-1"><strong>Vence:</strong> {{ selected.expires_label || '-' }}</p>
                            <p class="mb-3"><strong>Total:</strong> {{ selected.currency_code }} {{ money(selected.total) }}</p>
                        </div>
                    </div>

                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th></th>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selected.items" :key="item.sku + item.name">
                                <td><img class="quote-thumb" :src="item.image_url || noImage" :alt="item.name"></td>
                                <td>{{ item.sku }}</td>
                                <td>{{ item.name }}<div class="text-muted small">Exist. {{ money(item.available_stock) }}</div></td>
                                <td>{{ item.quantity }}</td>
                                <td>{{ selected.currency_code }} {{ money(item.unit_price) }}</td>
                                <td>{{ selected.currency_code }} {{ money(item.line_total) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="form-group">
                        <label>Notas del cliente</label>
                        <div class="border rounded p-2 bg-light">{{ selected.customer_notes || 'Sin notas.' }}</div>
                    </div>
                    <div class="form-group">
                        <label>Notas internas</label>
                        <textarea class="form-control" rows="3" v-model="selected.internal_notes"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control" v-model="selected.status">
                            <option value="prequote">Precotizacion</option>
                            <option value="requested">Solicitada</option>
                            <option value="reviewed">Revisada</option>
                            <option value="approved">Aprobada</option>
                            <option value="rejected">Rechazada</option>
                            <option value="converted">Convertida</option>
                        </select>
                    </div>
                    <div v-if="selected.status === 'prequote'" class="border rounded p-3 bg-light">
                        <h6>Cerrar con precios</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <select class="form-control" v-model="closeForm.party_id">
                                    <option value="">Selecciona cliente</option>
                                    <option v-for="customer in options.customers" :value="customer.value">{{ customer.label }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary btn-block" @click="closePrequote">Cerrar cotizacion</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-quote')">Cerrar</button>
                    <button class="btn btn-primary" @click="saveSelected">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    new Vue({
        el: '#app-sales',
        data: {
            loading: true,
            quotes: [],
            selected: null,
            stats: { quotes: 0, prequote: 0, requested: 0, reviewed: 0, approved: 0, rejected: 0 },
            options: { customers: [], products: [], brands: [], categories: [] },
            quoteForm: { party_id: '', quote_mode: 'quote', items: [], customer_notes: '', internal_notes: '', offline_uuid: '' },
            lineForm: { product_id: '', quantity: 1 },
            closeForm: { party_id: '' },
            filters: { q: '', brand_id: '', category_id: '', stock: '' },
            noImage: <?php echo json_encode($no_image_svg); ?>,
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
                fetch('<?php echo Uri::create('admin/sales/data'); ?>')
                    .then(res => res.json())
                    .then(data => {
                        this.loading = false;
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.stats = data.stats || this.stats;
                        this.options = data.options || this.options;
                        this.cacheCatalogs();
                    })
                    .catch(() => {
                        this.loading = false;
                        this.offline.online = false;
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
                    prequote: 'Precotizacion',
                    requested: 'Solicitada',
                    reviewed: 'Revisada',
                    approved: 'Aprobada',
                    rejected: 'Rechazada',
                    converted: 'Convertida'
                };
                return labels[status] || status;
            },
            statusClass(status) {
                const classes = {
                    prequote: 'badge-secondary',
                    requested: 'badge-warning',
                    reviewed: 'badge-info',
                    approved: 'badge-success',
                    rejected: 'badge-danger',
                    converted: 'badge-primary'
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
                fetch('<?php echo Uri::create('admin/sales/update_status'); ?>', window.coreAppFetchOptions({
                    id: quote.id,
                    status: status,
                    internal_notes: quote.internal_notes || ''
                }))
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.stats = data.stats || this.stats;
                        this.hideModal('modal-quote');
                    });
            },
            closePrequote() {
                if (!this.selected) return;
                if (!this.closeForm.party_id) {
                    alert('Selecciona cliente para cerrar con precios.');
                    return;
                }
                fetch('<?php echo Uri::create('admin/sales/close_prequote'); ?>', window.coreAppFetchOptions({
                    id: this.selected.id,
                    party_id: this.closeForm.party_id,
                    internal_notes: this.selected.internal_notes || ''
                }))
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.stats = data.stats || this.stats;
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
                this.quoteForm = { party_id: '', quote_mode: 'quote', items: [], customer_notes: '', internal_notes: '', offline_uuid: this.newOfflineUuid() };
                this.lineForm = { product_id: '', quantity: 1 };
                this.hydrateOptionsFromCache();
                this.showModal('modal-new-quote');
            },
            newPrequote() {
                this.quoteForm = { party_id: '', quote_mode: 'prequote', items: [], customer_notes: '', internal_notes: 'Precotizacion sin precios para mostrar catalogo al cliente.', offline_uuid: this.newOfflineUuid() };
                this.lineForm = { product_id: '', quantity: 1 };
                this.hydrateOptionsFromCache();
                this.showModal('modal-new-quote');
            },
            selectProduct(product) {
                this.lineForm.product_id = product.value;
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
                this.lineForm = { product_id: '', quantity: 1 };
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
                if (!this.filters.brand_id) return;
                this.filteredProducts.filter(product => Number(product.brand_id) === Number(this.filters.brand_id)).forEach(product => this.quoteForm.items.push({ product_id: product.value, quantity: this.lineForm.quantity || 1 }));
            },
            addCategoryProducts() {
                if (!this.filters.category_id) return;
                this.filteredProducts.filter(product => Number(product.category_id) === Number(this.filters.category_id)).forEach(product => this.quoteForm.items.push({ product_id: product.value, quantity: this.lineForm.quantity || 1 }));
            },
            clearFilters() {
                this.filters = { q: '', brand_id: '', category_id: '', stock: '' };
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
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        this.quotes = data.quotes || [];
                        this.stats = data.stats || this.stats;
                        this.removeDraftByUuid(this.quoteForm.offline_uuid);
                        this.hideModal('modal-new-quote');
                    })
                    .catch(() => {
                        this.saveDraftNow();
                        alert('Sin conexion. La cotizacion quedo guardada como borrador en este equipo.');
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
                this.showModal('modal-new-quote');
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
                        .then(res => res.json())
                        .then(data => {
                            if (!data.error) {
                                return window.CoreOffline.remove(draft.key);
                            }
                        })
                        .catch(() => null)
                        .then(() => syncOne(index + 1));
                };
                syncOne(0);
            }
        }
    });
};
</script>
