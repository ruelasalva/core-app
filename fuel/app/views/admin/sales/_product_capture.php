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
