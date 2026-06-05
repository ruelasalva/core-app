                <div class="modal-body">
                    <div class="alert alert-light border py-2">
                        <span :class="offline.online ? 'text-success' : 'text-warning'">{{ offline.online ? 'Con conexion' : 'Sin conexion' }}</span>
                        <span v-if="offline.lastSaved" class="text-muted ml-2">Borrador local guardado {{ offline.lastSaved }}</span>
                    </div>
                    <h6 class="quote-section-title quote-section-partner">Datos del socio</h6>
                    <div class="quote-partner-panel">
                        <div class="row">
                            <div class="col-md-8">
                                <label>Socio de negocio</label>
                                <select class="form-control" v-model="quoteForm.party_id">
                                    <option value="">Escribe o selecciona socio...</option>
                                    <option v-for="customer in options.customers" :value="customer.value">{{ customer.label }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Vendedor</label>
                                <select class="form-control" v-model="quoteForm.seller_id">
                                    <option value="0">Automatico</option>
                                    <option v-for="seller in options.sellers" :value="seller.value">{{ seller.label }}</option>
                                </select>
                            </div>
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
