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
                                    <button class="btn btn-xs btn-outline-primary mr-1" @click="refreshCatalog">Buscar catálogo</button>
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
                                    <div v-else class="text-center text-muted p-3">Selecciona productos del catálogo.</div>
                                    <div class="d-flex justify-content-between border-top pt-2 money-cell">
                                        <strong>Total estimado</strong>
                                        <strong>{{ quoteCurrency }} {{ money(quoteTotal) }}</strong>
                                    </div>
                                    <div v-if="quoteForm.quote_mode === 'prequote'" class="alert alert-secondary mt-2 mb-0 py-2 small">
                                        Modo catálogo: no se muestran ni guardan precios. Podrás cerrar la cotización después.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
