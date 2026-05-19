<div id="app-sales">
    <?php
    $capture_page = !empty($capture_page);
    $initial_view = Input::get('view', 'quotes');
    if (!in_array($initial_view, ['quotes', 'orders', 'deliveries'], true)) {
        $initial_view = 'quotes';
    }
    $no_image_svg = 'data:image/svg+xml;charset=UTF-8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="360" height="260" viewBox="0 0 360 260"><rect width="360" height="260" fill="#eef3f7"/><path d="M72 178h216l-64-82-48 60-34-44-70 66z" fill="#cbd5e1"/><circle cx="130" cy="86" r="24" fill="#cbd5e1"/><text x="180" y="226" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="20" fill="#64748b">Sin imagen</text></svg>');
    ?>
    <style>
        .quote-section-title { color: #fff; font-weight: 700; padding: 14px 18px; margin: 0 -1rem 1rem; }
        .quote-section-partner { background: #5b6ee1; }
        .quote-section-values { background: #15bfd2; }
        .quote-section-products { background: #ff5b42; }
        .quote-partner-panel { background: #fff; border-radius: 6px; padding: 12px; margin-bottom: 18px; }
        .quote-product-capture { background: #fff; border: 1px solid #e0e6ef; border-radius: 6px; padding: 14px; margin-bottom: 14px; }
        .quote-search-wrap { position: relative; }
        .quote-search-results { position: absolute; z-index: 1060; left: 0; right: 0; max-height: 280px; overflow: auto; background: #fff; border: 1px solid #cfd8e3; border-radius: 0 0 6px 6px; box-shadow: 0 10px 24px rgba(15,23,42,.16); }
        .quote-search-result { display: grid; grid-template-columns: 52px 1fr auto; gap: 10px; align-items: center; width: 100%; border: 0; border-bottom: 1px solid #edf1f5; background: #fff; text-align: left; padding: 8px; }
        .quote-search-result:hover { background: #f5f8fb; }
        .quote-search-result img { width: 52px; height: 42px; object-fit: cover; border-radius: 5px; border: 1px solid #dde3ea; }
        .quote-selected-product { display: grid; grid-template-columns: 132px 1fr; gap: 14px; align-items: start; min-height: 132px; }
        .quote-selected-product img { width: 132px; height: 112px; object-fit: cover; border: 1px solid #dde3ea; border-radius: 6px; background: #eef3f7; }
        .quote-items-panel { background: #fff; border: 1px solid #e0e6ef; border-radius: 6px; padding: 12px; margin-bottom: 14px; }
        .quote-workbench { display: grid; grid-template-columns: minmax(0, 1.25fr) 420px; gap: 16px; }
        .quote-product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; max-height: 52vh; overflow: auto; padding-right: 4px; }
        .quote-product-card { border: 1px solid #dde3ea; border-radius: 8px; background: #fff; overflow: hidden; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
        .quote-product-card:hover, .quote-product-card.active { border-color: #007bff; box-shadow: 0 6px 16px rgba(15,23,42,.10); }
        .quote-product-card img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; background: #eef3f7; }
        .quote-product-body { padding: 9px; }
        .quote-product-title { font-size: .88rem; line-height: 1.25; font-weight: 700; min-height: 36px; }
        .quote-meta { display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; font-size: .78rem; color: #6c757d; }
        .quote-thumb { width: 54px; height: 44px; border-radius: 6px; border: 1px solid #dde3ea; object-fit: cover; background: #eef3f7; }
        .quote-cart { position: sticky; top: 12px; }
        .quote-toolbar { display: grid; grid-template-columns: 1.3fr 1fr 1fr auto; gap: 8px; align-items: end; }
        .quote-modal-fullscreen { width: calc(100vw - 24px); max-width: calc(100vw - 24px); margin: 12px auto; }
        .quote-modal-fullscreen .modal-content { min-height: calc(100vh - 24px); }
        .quote-modal-fullscreen .modal-body { max-height: calc(100vh - 156px); overflow: auto; }
        .quote-page-capture { margin: -10px -7.5px 0; }
        .quote-page-capture .modal-content { min-height: calc(100vh - 150px); border: 0; border-radius: 0; box-shadow: none; }
        .quote-page-capture .modal-body { min-height: calc(100vh - 280px); overflow: visible; }
        .price-hidden .money-cell, .price-hidden .price-text { display: none; }
        .range-chip { display: inline-block; border: 1px solid #dee2e6; border-radius: 999px; padding: 2px 7px; margin: 2px 2px 0 0; font-size: .72rem; color: #495057; background: #f8f9fa; cursor: pointer; }
        .range-chip:hover { border-color: #007bff; color: #0056b3; }
        @media (max-width: 1100px) { .quote-workbench { grid-template-columns: 1fr; } .quote-cart { position: static; } .quote-selected-product { grid-template-columns: 1fr; } }
    </style>
    <?php if (!$capture_page): ?>
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
                    <h3>{{ stats.orders || 0 }}</h3>
                    <p>Pedidos</p>
                </div>
                <div class="icon"><i class="bi bi-clipboard-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ stats.deliveries || 0 }}</h3>
                    <p>Entregas</p>
                </div>
                <div class="icon"><i class="bi bi-truck"></i></div>
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
                    <i class="bi bi-bag-plus"></i> Vista cliente / catalogo
                </button>
                <a class="btn btn-primary btn-sm" href="<?php echo Uri::create('admin/sales/create'); ?>">
                    <i class="bi bi-plus-lg"></i> Nueva cotizacion
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="btn-group btn-group-sm mb-3" role="group" aria-label="Vista de ventas">
                <button class="btn" :class="viewMode === 'quotes' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'quotes'">
                    Cotizaciones
                </button>
                <button class="btn" :class="viewMode === 'orders' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'orders'">
                    Pedidos
                </button>
                <button class="btn" :class="viewMode === 'deliveries' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'deliveries'">
                    Entregas
                </button>
            </div>
            <div v-if="offline.drafts.length" class="alert alert-warning">
                <strong>Borradores en este equipo:</strong>
                <span v-for="draft in offline.drafts" :key="draft.key" class="badge badge-light border ml-2">
                    {{ draft.value.label || 'Cotizacion local' }}
                    <a href="#" @click.prevent="recoverDraft(draft)">abrir</a>
                    <a href="#" class="text-danger" @click.prevent="discardDraft(draft)">quitar</a>
                </span>
            </div>
            <div v-if="error" class="alert alert-danger">
                {{ error }}
            </div>
            <div v-if="loading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando ventas...</p>
            </div>

            <table v-show="!loading && viewMode === 'quotes'" class="table table-bordered table-hover">
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
                                <span>{{ item.quantity }} x {{ item.name }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" @click="openDetail(quote)">Detalle</button>
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

            <table v-show="!loading && viewMode === 'orders'" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Cotizacion</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Pendiente</th>
                        <th>Backorder</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in orders" :key="order.id">
                        <td><strong>{{ order.folio }}</strong></td>
                        <td>{{ order.party_name || '-' }}</td>
                        <td>{{ order.quote_folio || '-' }}</td>
                        <td>{{ order.order_date || '-' }}</td>
                        <td><span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                        <td>{{ order.currency_code }} {{ money(order.total) }}</td>
                        <td>{{ money(order.pending_quantity) }}</td>
                        <td><span class="badge" :class="order.backorder == 1 ? 'badge-warning' : 'badge-light'">{{ order.backorder == 1 ? 'Si' : 'No' }}</span></td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-success" @click="openFulfillment(order)" :disabled="order.status === 'delivered' || order.status === 'closed' || order.status === 'billed' || Number(order.pending_quantity || 0) <= 0">
                                Surtir
                            </button>
                        </td>
                    </tr>
                    <tr v-if="orders.length === 0">
                        <td colspan="9" class="text-center text-muted">Todavia no hay pedidos.</td>
                    </tr>
                </tbody>
            </table>

            <table v-show="!loading && viewMode === 'deliveries'" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Entrega</th>
                        <th>Cliente</th>
                        <th>Pedido</th>
                        <th>Almacen</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="delivery in deliveries" :key="delivery.id">
                        <td><strong>{{ delivery.folio }}</strong></td>
                        <td>{{ delivery.party_name || '-' }}</td>
                        <td>{{ delivery.order_folio || '-' }}</td>
                        <td>{{ delivery.warehouse_name || '-' }}</td>
                        <td>{{ delivery.delivery_date || '-' }}</td>
                        <td><span class="badge" :class="statusClass(delivery.status)">{{ statusLabel(delivery.status) }}</span></td>
                        <td>{{ delivery.currency_code }} {{ money(delivery.total) }}</td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-primary" @click="invoiceDelivery(delivery)" :disabled="delivery.billing_invoice_id > 0">
                                Facturar
                            </button>
                        </td>
                    </tr>
                    <tr v-if="deliveries.length === 0">
                        <td colspan="8" class="text-center text-muted">Todavia no hay entregas.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($capture_page): ?>
    <div class="quote-page-capture">
        <div class="modal-content">
    <?php else: ?>
    <div class="modal fade" id="modal-new-quote" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog quote-modal-fullscreen">
            <div class="modal-content">
    <?php endif; ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ quoteForm.quote_mode === 'prequote' ? 'Nueva precotizacion' : 'Nueva cotizacion' }}</h5>
                    <?php if ($capture_page): ?>
                    <a class="close text-white" href="<?php echo Uri::create('admin/sales'); ?>">
                        <span>&times;</span>
                    </a>
                    <?php else: ?>
                    <button type="button" class="close text-white" @click="hideModal('modal-new-quote')">
                        <span>&times;</span>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border py-2">
                        <span :class="offline.online ? 'text-success' : 'text-warning'">{{ offline.online ? 'Con conexion' : 'Sin conexion' }}</span>
                        <span v-if="offline.lastSaved" class="text-muted ml-2">Borrador local guardado {{ offline.lastSaved }}</span>
                    </div>
                    <h6 class="quote-section-title quote-section-partner">Datos del socio</h6>
                    <div class="quote-partner-panel">
                        <div class="form-group">
                            <label>Socio de negocio</label>
                            <select class="form-control" v-model="quoteForm.party_id">
                                <option value="">Escribe o selecciona socio...</option>
                                <option v-for="customer in options.customers" :value="customer.value">{{ customer.label }}</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label>Modo</label>
                                <select class="form-control" v-model="quoteForm.quote_mode">
                                    <option value="quote">Cotizacion con precios</option>
                                    <option value="prequote">Precotizacion / catalogo sin precios</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label>Referencia</label>
                                <input class="form-control" v-model="quoteForm.customer_notes" placeholder="Referencia o comentario visible">
                            </div>
                            <div class="col-md-3">
                                <label>Valido hasta</label>
                                <input class="form-control" disabled :value="'15 dias'">
                            </div>
                        </div>
                    </div>

                    <h6 class="quote-section-title quote-section-values">Valores generales / Impuestos, Retenciones, Monedas, Descuentos</h6>
                    <h6 class="quote-section-title quote-section-products">Productos y servicios</h6>
                    <div class="quote-product-capture" v-if="quoteForm.quote_mode === 'quote'">
                        <div class="row">
                            <div class="col-md-2">
                                <label>Tipo</label>
                                <select class="form-control" v-model="lineForm.product_type">
                                    <option value="product">Producto</option>
                                    <option value="service">Servicio</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label>Buscar producto/servicio</label>
                                <div class="quote-search-wrap">
                                    <input class="form-control" v-model="lineForm.product_query" @input="onProductSearchInput" @focus="lineForm.search_open = true" @keydown.enter.prevent="selectFirstSearchResult" placeholder="Buscar producto...">
                                    <div class="quote-search-results" v-if="lineForm.search_open && productSearchResults.length">
                                        <button type="button" class="quote-search-result" v-for="product in productSearchResults" :key="product.value" @mousedown.prevent="selectProduct(product)">
                                            <img :src="product.image_url || noImage" :alt="product.label">
                                            <span>
                                                <strong>{{ product.label }}</strong>
                                                <small class="d-block text-muted">{{ product.brand_name || 'Sin marca' }} / {{ product.category_name || 'Sin categoria' }}</small>
                                            </span>
                                            <small class="text-right">Exist. {{ money(product.available_stock) }}</small>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <label>Existencias</label>
                                <input class="form-control" disabled :value="money(selectedProduct.available_stock)">
                            </div>
                            <div class="col-md-2">
                                <label>Precio unitario</label>
                                <input class="form-control money-cell" disabled :value="productCurrency(lineForm.product_id) + ' ' + money(productPrice(lineForm.product_id, lineForm.quantity))">
                            </div>
                            <div class="col-md-2">
                                <label>Cantidad</label>
                                <input type="number" min="1" step="1" class="form-control" v-model.number="lineForm.quantity">
                            </div>
                        </div>
                        <div class="row mt-3 align-items-center">
                            <div class="col-md-3">
                                <div class="quote-selected-product">
                                    <img :src="selectedProduct.image_url || noImage" :alt="selectedProduct.label || 'Sin imagen'">
                                    <div>
                                        <strong>{{ selectedProduct.label || 'Selecciona un producto' }}</strong>
                                        <div class="text-muted small">{{ selectedProduct.sku || '' }}</div>
                                        <div class="small">{{ selectedProduct.brand_name || '' }} {{ selectedProduct.category_name ? '/ ' + selectedProduct.category_name : '' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <button class="btn btn-info mr-2" @click="addBrandProducts" :disabled="!selectedProduct.brand_id && !filters.brand_id">Agregar por marca</button>
                                <button class="btn btn-info mr-2" @click="addSelectedRange" :disabled="!selectedProduct.price_ranges || selectedProduct.price_ranges.length === 0">Agregar por rango</button>
                                <span class="price-text" v-if="selectedProduct.price_ranges && selectedProduct.price_ranges.length">
                                    <button type="button" class="range-chip" v-for="range in selectedProduct.price_ranges" @click="quickAddRange(selectedProduct, range)">
                                        +{{ money(range.min_quantity) }}: {{ range.currency_code }} {{ money(range.price) }}
                                    </button>
                                </span>
                            </div>
                            <div class="col-md-4 text-right">
                                <button class="btn btn-primary px-5" @click="addSelectedLine" :disabled="!lineForm.product_id">Agregar</button>
                            </div>
                        </div>
                    </div>

                    <div class="quote-items-panel" v-if="quoteForm.quote_mode === 'quote'">
                        <table class="table table-sm table-bordered mb-2" v-if="quoteForm.items.length">
                            <thead><tr><th>Tipo</th><th>Codigo</th><th>Descripcion</th><th>Cantidad</th><th>Precio unitario</th><th>Total</th><th>Acciones</th></tr></thead>
                            <tbody>
                                <tr v-for="(item, index) in quoteForm.items" :key="index">
                                    <td>Producto</td>
                                    <td>{{ productById(item.product_id).sku || '' }}</td>
                                    <td>{{ productLabel(item.product_id) }}</td>
                                    <td><input class="form-control form-control-sm" type="number" min="1" step="1" v-model.number="item.quantity"></td>
                                    <td>{{ productCurrency(item.product_id) }} {{ money(productPrice(item.product_id, item.quantity)) }}</td>
                                    <td>{{ productCurrency(item.product_id) }} {{ money(lineTotal(item)) }}</td>
                                    <td class="text-center"><button class="btn btn-xs btn-danger" @click="removeLine(index)">Quitar</button></td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="text-center text-muted p-3 border rounded">Sin productos agregados.</div>
                        <div class="text-right border-top pt-2"><strong>Total estimado: {{ quoteCurrency }} {{ money(quoteTotal) }}</strong></div>
                    </div>

                    <div class="quote-workbench price-hidden" v-if="quoteForm.quote_mode === 'prequote'">
                        <div>
                            <div class="border rounded p-2 mb-2">
                                <div class="row">
                                    <div class="col-md-4"><input class="form-control form-control-sm" v-model="filters.q" placeholder="Buscar SKU o producto"></div>
                                    <div class="col-md-3"><select class="form-control form-control-sm" v-model="filters.brand_id"><option value="">Todas las marcas</option><option v-for="brand in options.brands" :value="brand.value">{{ brand.label }}</option></select></div>
                                    <div class="col-md-3"><select class="form-control form-control-sm" v-model="filters.category_id"><option value="">Todas las categorias</option><option v-for="category in options.categories" :value="category.value">{{ category.label }}</option></select></div>
                                    <div class="col-md-2"><select class="form-control form-control-sm" v-model="filters.stock"><option value="">Existencia</option><option value="available">Disponible</option><option value="zero">Sin existencia</option></select></div>
                                </div>
                                <div class="mt-2">
                                    <button class="btn btn-xs btn-outline-primary mr-1" @click="refreshCatalog">Buscar catalogo</button>
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
                    <?php if ($capture_page): ?>
                    <a class="btn btn-secondary" href="<?php echo Uri::create('admin/sales'); ?>">Regresar</a>
                    <?php else: ?>
                    <button class="btn btn-secondary" @click="hideModal('modal-new-quote')">Cerrar</button>
                    <?php endif; ?>
                    <button class="btn btn-primary" @click="saveQuote">{{ quoteForm.quote_mode === 'prequote' ? 'Guardar precotizacion' : 'Guardar cotizacion' }}</button>
                </div>
    <?php if ($capture_page): ?>
            </div>
        </div>
    <?php else: ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-success" @click="setStatus(selected, 'approved')" :disabled="selected.status === 'prequote' || selected.status === 'approved' || (selected.orders && selected.orders.length)">
                                    Aprobar y mandar a pedido
                                </button>
                                <button class="btn btn-outline-success" v-if="selected.orders && selected.orders.length" @click="openFulfillment(selected.orders[0])" :disabled="selected.orders[0].status === 'delivered' || selected.orders[0].status === 'closed' || selected.orders[0].status === 'billed'">
                                    Surtir pedido
                                </button>
                            </div>
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
                            <option value="approved">Aprobada</option>
                            <option value="rejected">Rechazada</option>
                            <option value="converted">Convertida</option>
                        </select>
                    </div>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex align-items-center mb-2">
                            <h6 class="mb-0">Flujo comercial</h6>
                            <button class="btn btn-sm btn-outline-success ml-auto" @click="setStatus(selected, 'approved')" :disabled="selected.status === 'prequote' || selected.status === 'approved' || (selected.orders && selected.orders.length)">
                                Aprobar y mandar a pedido
                            </button>
                        </div>
                        <div v-if="!selected.orders || selected.orders.length === 0" class="text-muted small">Sin pedido relacionado.</div>
                        <div v-for="order in selected.orders" :key="order.id" class="border rounded p-2 mb-2 bg-white">
                            <div class="d-flex align-items-center">
                                <strong>{{ order.folio }}</strong>
                                <span class="badge badge-info ml-2">{{ order.status }}</span>
                                <button class="btn btn-xs btn-outline-success ml-auto" @click="openFulfillment(order)" :disabled="order.status === 'delivered' || order.status === 'closed' || order.status === 'billed'">
                                    Surtir
                                </button>
                            </div>
                            <div v-if="!order.deliveries || order.deliveries.length === 0" class="text-muted small mt-1">Sin entrega.</div>
                            <div v-for="delivery in order.deliveries" :key="delivery.id" class="small mt-1">
                                Entrega <strong>{{ delivery.folio }}</strong>
                                <span class="badge badge-secondary">{{ delivery.status }}</span>
                                <button class="btn btn-xs btn-outline-primary ml-2" @click="invoiceDelivery(delivery)" :disabled="delivery.billing_invoice_id > 0">
                                    Facturar entrega
                                </button>
                            </div>
                        </div>
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

    <div class="modal fade" id="modal-fulfillment" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" v-if="selectedOrder">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Surtir pedido {{ selectedOrder.folio }}</h5>
                    <button type="button" class="close text-white" @click="hideModal('modal-fulfillment')"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Almacen de salida</label>
                        <select class="form-control" v-model="deliveryForm.warehouse_id">
                            <option v-for="warehouse in options.warehouses" :value="warehouse.value">{{ warehouse.label }}</option>
                        </select>
                    </div>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th></th><th>SKU</th><th>Producto</th><th>Pedido</th><th>Surtido</th><th>Pendiente</th><th>A surtir</th></tr></thead>
                        <tbody>
                            <tr v-for="item in deliveryForm.items" :key="item.order_item_id">
                                <td><img class="quote-thumb" :src="item.image_url || noImage" :alt="item.name"></td>
                                <td>{{ item.sku }}</td>
                                <td>{{ item.name }}<div class="text-muted small">Disponible catalogo: {{ money(item.available_stock) }}</div></td>
                                <td>{{ money(item.ordered_quantity) }}</td>
                                <td>{{ money(item.delivered_quantity) }}</td>
                                <td><strong>{{ money(item.pending_quantity) }}</strong></td>
                                <td><input class="form-control form-control-sm" type="number" min="0" :max="item.pending_quantity" step="1" v-model.number="item.quantity"></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="alert alert-warning py-2 mb-0">
                        Si surtimos menos del pendiente, el pedido queda parcial y el resto queda en backorder esperando inventario.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal('modal-fulfillment')">Cerrar</button>
                    <button class="btn btn-success" @click="createDeliveryFromOrder()">Crear entrega</button>
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
            error: '',
            quotes: [],
            orders: [],
            deliveries: [],
            viewMode: '<?php echo $initial_view; ?>',
            selected: null,
            selectedOrder: null,
            stats: { quotes: 0, orders: 0, deliveries: 0, prequote: 0, requested: 0, approved: 0, rejected: 0 },
            options: { customers: [], products: [], brands: [], categories: [], warehouses: [] },
            quoteForm: { party_id: '', quote_mode: 'quote', items: [], customer_notes: '', internal_notes: '', offline_uuid: '' },
            lineForm: { product_id: '', product_query: '', product_type: 'product', quantity: 1, search_open: false, search_results: [] },
            closeForm: { party_id: '' },
            deliveryForm: { order_id: 0, warehouse_id: '', items: [] },
            filters: { q: '', brand_id: '', category_id: '', stock: '' },
            searchTimer: null,
            noImage: <?php echo json_encode($no_image_svg); ?>,
            capturePage: <?php echo $capture_page ? 'true' : 'false'; ?>,
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
                this.prepareQuoteForm('quote');
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
                fetch('<?php echo Uri::create('admin/sales/data'); ?>')
                    .then(res => res.json())
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
                        this.options = data.options || this.options;
                        this.cacheCatalogs();
                    })
                    .catch(() => {
                        this.loading = false;
                        this.offline.online = false;
                        this.error = 'No se pudo cargar ventas. Si estas sin conexion se intentara usar catalogos locales.';
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
                    .then(res => res.json())
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
                    .then(res => res.json())
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
                    .then(res => res.json())
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
                fetch('<?php echo Uri::create('admin/billing/create_from_delivery'); ?>', window.coreAppFetchOptions({ delivery_id: delivery.id }))
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        alert('Factura creada: ' + data.folio);
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
                    quote_mode: mode,
                    items: [],
                    customer_notes: '',
                    internal_notes: mode === 'prequote' ? 'Precotizacion sin precios para mostrar catalogo al cliente.' : '',
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
                    .then(res => res.json())
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
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
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
                    .then(res => res.json())
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
                    .then(res => res.json())
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
                        if (!res.ok) {
                            return res.text().then(text => {
                                let message = 'Error HTTP ' + res.status;
                                try {
                                    const payload = JSON.parse(text);
                                    message = payload.error || message;
                                } catch (e) {
                                    if (res.status === 400) {
                                        message = 'La sesion de seguridad expiro o no se envio correctamente. Recarga la pantalla e intenta de nuevo.';
                                    } else if (res.status === 404) {
                                        message = 'No se encontro la ruta para guardar la cotizacion. Recarga la pantalla e intenta de nuevo.';
                                    }
                                }
                                throw new Error(message);
                            });
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
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
                            alert('No se pudo guardar la cotizacion: ' + (error.message || 'respuesta invalida del servidor'));
                            return;
                        }
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
                            this.error = 'No se pudo sincronizar una cotizacion local. Revisa sesion, permisos o recarga la pantalla.';
                        })
                        .then(() => syncOne(index + 1));
                };
                syncOne(0);
            }
        }
    });
};
</script>
