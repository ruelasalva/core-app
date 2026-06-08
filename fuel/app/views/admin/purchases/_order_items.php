                <div class="border rounded p-3 my-3">
                    <div class="row">
                        <div class="col-md-4"><label>Concepto</label><input class="form-control" v-model="line.description"></div>
                        <div class="col-md-2"><label>Cantidad</label><input class="form-control" type="number" step="0.01" v-model.number="line.quantity"></div>
                        <div class="col-md-2"><label>Precio unitario</label><input class="form-control" type="number" step="0.01" v-model.number="line.unit_price"></div>
                        <div class="col-md-2"><label>IVA trasladado</label><select class="form-control" v-model="line.tax_code" @change="applyTax"><option value="">Sin IVA</option><option v-for="tax in options.taxes" :value="tax.value">{{ tax.label }} ({{ tax.rate_label }})</option></select></div>
                        <div class="col-md-1"><label>Tasa</label><input class="form-control" type="number" step="0.000001" v-model.number="line.tax_rate"></div>
                        <div class="col-md-1 d-flex align-items-end"><button class="btn btn-outline-primary btn-block" @click="addLine">+</button></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3"><label>Retencion</label><select class="form-control" v-model="line.retention_code" @change="applyRetention"><option value="">Sin retencion</option><option v-for="retention in options.retentions" :value="retention.value">{{ retention.label }} ({{ retention.rate_label }})</option></select></div>
                        <div class="col-md-3"><label>Monto retenido</label><input class="form-control" type="number" step="0.01" v-model.number="line.retention_amount"></div>
                        <div class="col-md-2"><label>Subtotal</label><input class="form-control" type="text" :value="money(lineSubtotal(line))" readonly></div>
                        <div class="col-md-2"><label>IVA</label><input class="form-control" type="text" :value="money(lineTax(line))" readonly></div>
                        <div class="col-md-2"><label>Total linea</label><input class="form-control" type="text" :value="money(lineTotal(line))" readonly></div>
                    </div>
                </div>
                <table class="table table-sm table-bordered" v-if="orderForm.items.length"><thead><tr><th>Concepto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th><th>IVA</th><th>Retencion</th><th>Total</th><th></th></tr></thead><tbody><tr v-for="(item, i) in orderForm.items"><td>{{ item.description }}</td><td>{{ item.quantity }}</td><td>{{ money(item.unit_price) }}</td><td>{{ money(lineSubtotal(item)) }}</td><td>{{ money(lineTax(item)) }}</td><td>{{ money(item.retention_amount) }}</td><td>{{ money(lineTotal(item)) }}</td><td><button class="btn btn-xs btn-danger" @click="orderForm.items.splice(i, 1)">Quitar</button></td></tr></tbody></table>
