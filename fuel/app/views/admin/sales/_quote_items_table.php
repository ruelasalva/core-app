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
