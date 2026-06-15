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
                        <td colspan="7" class="text-center text-muted">Todavía no hay cotizaciones.</td>
                    </tr>
                </tbody>
            </table>
