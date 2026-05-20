<?php
    $no_image_svg = 'data:image/svg+xml;charset=UTF-8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="360" height="260" viewBox="0 0 360 260"><rect width="360" height="260" fill="#eef3f7"/><path d="M72 178h216l-64-82-48 60-34-44-70 66z" fill="#cbd5e1"/><circle cx="130" cy="86" r="24" fill="#cbd5e1"/><text x="180" y="226" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="20" fill="#64748b">Sin imagen</text></svg>');
?>
<div id="app-billing" class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0">Facturacion CFDI</h3>
        <button class="btn btn-primary btn-sm ml-auto" @click="newInvoice">
            <i class="bi bi-plus-circle"></i> Nueva factura
        </button>
        <button class="btn btn-outline-primary btn-sm ml-2" @click="newRecurringProfile">
            <i class="bi bi-arrow-repeat"></i> Recurrente
        </button>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-3 col-6" v-for="box in statBoxes" :key="box.key">
                <div class="small-box" :class="box.className">
                    <div class="inner">
                        <h3>{{ stats[box.key] || 0 }}</h3>
                        <p>{{ box.label }}</p>
                    </div>
                    <div class="icon"><i :class="box.icon"></i></div>
                </div>
            </div>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>
        <div v-if="loading" class="text-muted">Cargando facturacion...</div>

        <div class="row" v-if="!loading">
            <div class="col-12">
                <div class="card card-outline card-warning">
                    <div class="card-header py-2">
                        <h3 class="card-title mb-0">Entregas pendientes de facturar</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Entrega</th>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Almacen</th>
                                    <th>Fecha</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="delivery in pendingDeliveries" :key="delivery.id">
                                    <td><strong>{{ delivery.folio }}</strong></td>
                                    <td>{{ delivery.order_folio || '-' }}</td>
                                    <td>{{ delivery.party_name || '-' }}</td>
                                    <td>{{ delivery.warehouse_name || '-' }}</td>
                                    <td>{{ delivery.delivery_date || '-' }}</td>
                                    <td class="text-right">{{ money(delivery.total, delivery.currency_code) }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-outline-primary" @click="createInvoiceFromDelivery(delivery)">
                                            Facturar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="pendingDeliveries.length === 0">
                                    <td colspan="7" class="text-center text-muted">No hay entregas pendientes de factura.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card card-outline card-info">
                    <div class="card-header py-2 d-flex align-items-center">
                        <h3 class="card-title mb-0">Facturas recurrentes</h3>
                        <button class="btn btn-info btn-sm ml-auto" @click="newRecurringProfile">
                            <i class="bi bi-plus-lg"></i> Programar
                        </button>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Perfil</th>
                                    <th>Cliente</th>
                                    <th>Frecuencia</th>
                                    <th>Siguiente</th>
                                    <th>Estado</th>
                                    <th>Conceptos</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="profile in recurringProfiles" :key="profile.id" :class="{ 'table-info': selectedRecurringProfile && selectedRecurringProfile.id == profile.id }">
                                    <td><strong>{{ profile.folio }}</strong><div class="text-muted small">{{ profile.name }}</div></td>
                                    <td>{{ profile.party_name || '-' }}</td>
                                    <td>{{ recurringFrequencyLabel(profile.frequency) }}</td>
                                    <td>{{ profile.next_run_date || '-' }}</td>
                                    <td><span class="badge" :class="profile.status === 'active' ? 'badge-success' : 'badge-secondary'">{{ recurringStatusLabel(profile.status) }}</span></td>
                                    <td>
                                        <button class="btn btn-xs btn-outline-info" @click="selectRecurringProfile(profile)">
                                            Ver conceptos
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-outline-primary" @click="editRecurringProfile(profile)" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-xs btn-outline-secondary" @click="newRecurringItem(profile)" title="Concepto">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                        <button class="btn btn-xs btn-success" @click="generateRecurringInvoice(profile)" title="Generar factura">
                                            Generar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="recurringProfiles.length === 0">
                                    <td colspan="7" class="text-center text-muted">Sin perfiles recurrentes.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer p-0" v-if="selectedRecurringProfile">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Concepto</th><th>Cantidad</th><th>Precio</th><th>Impuesto</th><th></th></tr></thead>
                                <tbody>
                                    <tr v-for="item in recurringItems" :key="item.id">
                                        <td>{{ item.description }}</td>
                                        <td>{{ item.quantity }}</td>
                                        <td>{{ money(item.unit_price, selectedRecurringProfile.currency_code) }}</td>
                                        <td>{{ item.tax_code }}</td>
                                        <td class="text-right"><button class="btn btn-xs btn-outline-primary" @click="editRecurringItem(item)"><i class="bi bi-pencil"></i></button></td>
                                    </tr>
                                    <tr v-if="recurringItems.length === 0"><td colspan="5" class="text-center text-muted">Agrega conceptos recurrentes al perfil.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Tercero</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th class="text-right">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in invoices" :key="invoice.id" :class="{ 'table-primary': selectedInvoice && selectedInvoice.id == invoice.id }">
                                <td>{{ invoice.folio }}</td>
                                <td>{{ invoice.party_name }}</td>
                                <td>{{ invoice.issue_date }}</td>
                                <td><span class="badge badge-secondary">{{ invoice.status }}</span></td>
                                <td class="text-right">{{ money(invoice.total, invoice.currency_code) }}</td>
                                <td class="text-right">
                                    <button class="btn btn-xs btn-outline-info" @click="prepareCfdi(invoice)" title="Preparar CFDI">
                                        <i class="bi bi-braces"></i>
                                    </button>
                                    <button class="btn btn-xs btn-outline-success" @click="stampInvoice(invoice)" :disabled="invoice.status === 'stamped' || invoice.status === 'cancelled'" title="Timbrar">
                                        <i class="bi bi-patch-check"></i>
                                    </button>
                                    <button class="btn btn-xs btn-outline-danger" @click="openCancel(invoice)" :disabled="invoice.status !== 'stamped'" title="Cancelar">
                                        <i class="bi bi-x-octagon"></i>
                                    </button>
                                    <button class="btn btn-xs btn-outline-primary" @click="editInvoice(invoice)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-xs btn-outline-secondary" @click="selectInvoice(invoice)">
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="invoices.length === 0">
                                <td colspan="6" class="text-muted">Sin facturas registradas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="border rounded p-3" v-if="selectedInvoice">
                    <div class="d-flex align-items-center mb-2">
                        <h5 class="mb-0">Conceptos de {{ selectedInvoice.folio }}</h5>
                        <button class="btn btn-outline-primary btn-sm ml-auto" @click="newItem">
                            <i class="bi bi-plus"></i> Concepto
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Descripcion</th>
                                    <th class="text-right">Cant.</th>
                                    <th class="text-right">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id">
                                    <td>{{ item.description }}</td>
                                    <td class="text-right">{{ item.quantity }}</td>
                                    <td class="text-right">{{ money(item.line_total, selectedInvoice.currency_code) }}</td>
                                    <td class="text-right">
                                        <button class="btn btn-xs btn-outline-primary" @click="editItem(item)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="items.length === 0">
                                    <td colspan="4" class="text-muted">Agrega conceptos para calcular el total.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-right">
                        <div>Subtotal: <strong>{{ money(selectedInvoice.subtotal, selectedInvoice.currency_code) }}</strong></div>
                        <div>Impuestos: <strong>{{ money(selectedInvoice.tax_total, selectedInvoice.currency_code) }}</strong></div>
                        <div>Retenciones: <strong>{{ money(selectedInvoice.retention_total, selectedInvoice.currency_code) }}</strong></div>
                        <div>Total: <strong>{{ money(selectedInvoice.total, selectedInvoice.currency_code) }}</strong></div>
                        <div v-if="selectedInvoice.uuid" class="text-muted small">UUID: {{ selectedInvoice.uuid }}</div>
                    </div>
                </div>
                <div class="alert alert-info" v-else>
                    Selecciona una factura para ver o capturar conceptos.
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoice-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ invoiceForm.id ? 'Editar factura' : 'Nueva factura' }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Tipo</label>
                            <select v-model="invoiceForm.invoice_type" class="form-control">
                                <option value="sale">Venta</option>
                                <option value="purchase">Compra</option>
                                <option value="credit_note">Nota de credito</option>
                                <option value="payment_complement">Complemento pago</option>
                            </select>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Tercero</label>
                            <select v-model="invoiceForm.party_id" class="form-control">
                                <option value="0">Selecciona...</option>
                                <option v-for="option in options.parties" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Fecha emision</label>
                            <input v-model="invoiceForm.issue_date" type="date" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Fecha vencimiento</label>
                            <input v-model="invoiceForm.due_date" type="date" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Estado</label>
                            <select v-model="invoiceForm.status" class="form-control">
                                <option value="draft">Borrador</option>
                                <option value="ready">Lista para timbrar</option>
                                <option value="stamped">Timbrada</option>
                                <option value="paid">Pagada</option>
                                <option value="cancelled">Cancelada</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Moneda</label>
                            <select v-model="invoiceForm.currency_code" class="form-control">
                                <option v-for="option in options.currencies" :value="option.value">{{ option.value }} - {{ option.label }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Tipo cambio</label>
                            <input v-model.number="invoiceForm.exchange_rate" type="number" step="0.000001" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Condicion pago</label>
                            <select v-model="invoiceForm.payment_term_id" class="form-control">
                                <option value="0">Sin condicion</option>
                                <option v-for="option in options.payment_terms" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Conexion PAC</label>
                            <select v-model="invoiceForm.pac_connection_id" class="form-control">
                                <option value="0">Factura.com PAC predeterminada</option>
                                <option v-for="option in options.pac_connections" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Serie Factura.com</label>
                            <input v-model="invoiceForm.pac_series_id" class="form-control" placeholder="ID de serie">
                        </div>
                        <div class="form-group col-md-4">
                            <label>UID receptor Factura.com</label>
                            <input v-model="invoiceForm.pac_receptor_uid" class="form-control" placeholder="UID del cliente en Factura.com">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Uso CFDI</label>
                            <select v-model="invoiceForm.sat_cfdi_use_code" class="form-control">
                                <option v-for="option in options.sat_cfdi_uses" :value="option.value">{{ option.value }} - {{ option.label }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Forma pago</label>
                            <select v-model="invoiceForm.sat_payment_form_code" class="form-control">
                                <option v-for="option in options.sat_payment_forms" :value="option.value">{{ option.value }} - {{ option.label }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Metodo pago</label>
                            <select v-model="invoiceForm.sat_payment_method_code" class="form-control">
                                <option v-for="option in options.sat_payment_methods" :value="option.value">{{ option.value }} - {{ option.label }}</option>
                            </select>
                        </div>
                        <div class="form-group col-12">
                            <label>Notas</label>
                            <textarea v-model="invoiceForm.notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="border rounded p-3 bg-light" v-if="!invoiceForm.id">
                        <div class="d-flex align-items-center mb-2">
                            <h6 class="mb-0">Conceptos de la factura</h6>
                            <span class="ml-auto text-muted small">Se guardan al crear la factura</span>
                        </div>
                        <div class="row align-items-end">
                            <div class="form-group col-md-5">
                                <label>Producto o servicio</label>
                                <input v-model="invoiceDraftSearch" class="form-control" placeholder="Buscar SKU o producto">
                                <div class="list-group position-absolute w-100 shadow-sm" style="z-index: 1060; max-height: 220px; overflow-y: auto;" v-if="invoiceDraftSearch && invoiceDraftProducts.length">
                                    <button type="button" class="list-group-item list-group-item-action" v-for="product in invoiceDraftProducts" :key="product.value" @click="chooseInvoiceDraftProduct(product)">
                                        <strong>{{ product.name }}</strong>
                                        <span class="d-block small text-muted">{{ product.sku || 'Sin SKU' }} - Disponible {{ product.available_stock || 0 }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group col-md-2">
                                <label>Cantidad</label>
                                <input v-model.number="invoiceDraftConcept.quantity" type="number" min="0.0001" step="0.0001" class="form-control">
                            </div>
                            <div class="form-group col-md-2">
                                <label>Precio</label>
                                <input v-model.number="invoiceDraftConcept.unit_price" type="number" step="0.01" class="form-control">
                            </div>
                            <div class="form-group col-md-3">
                                <button class="btn btn-outline-primary btn-block" type="button" @click="addInvoiceDraftConcept">
                                    <i class="bi bi-plus"></i> Agregar concepto
                                </button>
                            </div>
                        </div>
                        <div class="row" v-if="invoiceDraftConcept.product_id">
                            <div class="col-md-3">
                                <div class="border rounded bg-white d-flex align-items-center justify-content-center mb-2" style="height: 120px; overflow: hidden;">
                                    <img :src="invoiceDraftProduct.image_url || noImage" :alt="invoiceDraftProduct.label || 'Sin imagen'" style="max-width: 100%; max-height: 100%;">
                                </div>
                            </div>
                            <div class="col-md-9 small text-muted">
                                <div><strong>{{ invoiceDraftProduct.name }}</strong></div>
                                <div>SKU: {{ invoiceDraftProduct.sku || 'Sin SKU' }}</div>
                                <div>Existencia disponible: {{ invoiceDraftProduct.available_stock || 0 }}</div>
                                <div>Impuesto: {{ invoiceDraftProduct.tax_code || 'iva_16' }}</div>
                            </div>
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th class="text-right">Cantidad</th>
                                        <th class="text-right">Precio</th>
                                        <th class="text-right">Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(concept, index) in invoiceDraftItems" :key="index">
                                        <td>{{ concept.description }}</td>
                                        <td class="text-right">{{ concept.quantity }}</td>
                                        <td class="text-right">{{ money(concept.unit_price, invoiceForm.currency_code) }}</td>
                                        <td class="text-right">{{ money(draftConceptTotal(concept), invoiceForm.currency_code) }}</td>
                                        <td class="text-right"><button class="btn btn-xs btn-outline-danger" type="button" @click="removeInvoiceDraftConcept(index)">Quitar</button></td>
                                    </tr>
                                    <tr v-if="invoiceDraftItems.length === 0">
                                        <td colspan="5" class="text-muted">Agrega productos para facturar directo.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveInvoice">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="item-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ itemForm.id ? 'Editar concepto' : 'Nuevo concepto' }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Buscar producto</label>
                        <input v-model="productSearch" class="form-control" placeholder="Escribe SKU o nombre del producto">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="border rounded bg-light d-flex align-items-center justify-content-center mb-3" style="height: 190px; overflow: hidden;">
                                <img :src="selectedProduct.image_url || noImage" :alt="selectedProduct.label || 'Sin imagen'" style="max-width: 100%; max-height: 100%;">
                            </div>
                            <div class="small text-muted" v-if="selectedProduct.value">
                                <div><strong>SKU:</strong> {{ selectedProduct.sku || 'Sin SKU' }}</div>
                                <div><strong>Existencia:</strong> {{ selectedProduct.available_stock || 0 }}</div>
                                <div><strong>Precio:</strong> {{ money(selectedProduct.price || 0, selectedProduct.currency_code || selectedInvoice.currency_code) }}</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label>Selecciona producto</label>
                            <div class="list-group mb-3" style="max-height: 230px; overflow-y: auto;">
                                <button type="button" class="list-group-item list-group-item-action" @click="chooseManualConcept">
                                    Concepto manual
                                </button>
                                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                    v-for="option in filteredProducts" :key="option.value" @click="chooseProduct(option)"
                                    :class="{ active: parseInt(itemForm.product_id || 0) === parseInt(option.value) }">
                                    <span>
                                        <strong>{{ option.name }}</strong>
                                        <span class="d-block small" :class="parseInt(itemForm.product_id || 0) === parseInt(option.value) ? 'text-white' : 'text-muted'">
                                            {{ option.sku || 'Sin SKU' }} - Disponible {{ option.available_stock || 0 }}
                                        </span>
                                    </span>
                                    <span>{{ money(option.price || 0, option.currency_code || 'MXN') }}</span>
                                </button>
                                <div class="list-group-item text-muted" v-if="filteredProducts.length === 0">
                                    Sin coincidencias en productos.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descripcion</label>
                        <input v-model="itemForm.description" class="form-control">
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Cantidad</label>
                            <input v-model.number="itemForm.quantity" type="number" step="0.0001" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Precio</label>
                            <input v-model.number="itemForm.unit_price" type="number" step="0.01" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Descuento</label>
                            <input v-model.number="itemForm.discount_amount" type="number" step="0.01" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Unidad SAT</label>
                            <select v-model="itemForm.unit_code" class="form-control">
                                <option v-for="option in options.units" :value="option.value">{{ option.value }} - {{ option.label }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Clave producto SAT</label>
                            <input v-model="itemForm.sat_product_service_code" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Objeto impuesto</label>
                            <select v-model="itemForm.sat_object_tax_code" class="form-control">
                                <option value="01">01 - No objeto</option>
                                <option value="02">02 - Si objeto</option>
                                <option value="03">03 - Si objeto no obligado</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Tasa impuesto</label>
                            <input v-model.number="itemForm.tax_rate" type="number" step="0.000001" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Tipo factor</label>
                            <select v-model="itemForm.tax_factor_type" class="form-control">
                                <option value="Tasa">Tasa</option>
                                <option value="Cuota">Cuota</option>
                                <option value="Exento">Exento</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Retencion</label>
                            <select v-model="itemForm.retention_tax_code" class="form-control">
                                <option value="">Sin retencion</option>
                                <option v-for="option in options.retentions" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Tasa retencion</label>
                            <input v-model.number="itemForm.retention_rate" type="number" step="0.000001" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Importe retencion</label>
                            <input v-model.number="itemForm.retention_amount" type="number" step="0.01" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveItem">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="recurring-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ recurringForm.id ? 'Editar recurrente' : 'Nueva factura recurrente' }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-6"><label>Nombre</label><input v-model="recurringForm.name" class="form-control" placeholder="Renta mensual equipo..."></div>
                        <div class="form-group col-md-6"><label>Cliente</label><select v-model="recurringForm.party_id" class="form-control"><option value="0">Selecciona...</option><option v-for="option in options.parties" :value="option.value">{{ option.label }}</option></select></div>
                        <div class="form-group col-md-3"><label>Frecuencia</label><select v-model="recurringForm.frequency" class="form-control"><option value="weekly">Semanal</option><option value="biweekly">Quincenal</option><option value="monthly">Mensual</option><option value="quarterly">Trimestral</option><option value="yearly">Anual</option></select></div>
                        <div class="form-group col-md-3"><label>Inicio</label><input v-model="recurringForm.start_date" type="date" class="form-control"></div>
                        <div class="form-group col-md-3"><label>Siguiente ejecucion</label><input v-model="recurringForm.next_run_date" type="date" class="form-control"></div>
                        <div class="form-group col-md-3"><label>Fin contrato</label><input v-model="recurringForm.end_date" type="date" class="form-control"></div>
                        <div class="form-group col-md-3"><label>Estado</label><select v-model="recurringForm.status" class="form-control"><option value="active">Activo</option><option value="paused">Pausado</option><option value="finished">Terminado</option></select></div>
                        <div class="form-group col-md-3"><label>Moneda</label><select v-model="recurringForm.currency_code" class="form-control"><option v-for="option in options.currencies" :value="option.value">{{ option.value }}</option></select></div>
                        <div class="form-group col-md-3"><label>Forma pago</label><select v-model="recurringForm.sat_payment_form_code" class="form-control"><option v-for="option in options.sat_payment_forms" :value="option.value">{{ option.value }} - {{ option.label }}</option></select></div>
                        <div class="form-group col-md-3"><label>Metodo pago</label><select v-model="recurringForm.sat_payment_method_code" class="form-control"><option v-for="option in options.sat_payment_methods" :value="option.value">{{ option.value }} - {{ option.label }}</option></select></div>
                        <div class="form-group col-md-4"><label>Uso CFDI</label><select v-model="recurringForm.sat_cfdi_use_code" class="form-control"><option v-for="option in options.sat_cfdi_uses" :value="option.value">{{ option.value }} - {{ option.label }}</option></select></div>
                        <div class="form-group col-md-4"><label>Serie Factura.com</label><input v-model="recurringForm.pac_series_id" class="form-control"></div>
                        <div class="form-group col-md-4"><label>UID receptor Factura.com</label><input v-model="recurringForm.pac_receptor_uid" class="form-control"></div>
                        <div class="form-group col-12"><label>Notas</label><textarea v-model="recurringForm.notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveRecurringProfile">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="recurring-item-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Concepto recurrente</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-8">
                            <label>Servicio / producto</label>
                            <input v-model="productSearch" class="form-control" placeholder="Busca servicio interno o producto">
                            <div class="list-group mt-1" style="max-height: 220px; overflow-y: auto;" v-if="productSearch">
                                <button type="button" class="list-group-item list-group-item-action" v-for="option in filteredProducts" :key="option.value" @click="chooseRecurringProduct(option)">
                                    <strong>{{ option.name }}</strong>
                                    <span class="d-block small text-muted">{{ option.sku || 'Sin SKU' }} - {{ productTypeLabel(option.product_type) }} <span v-if="option.is_internal_service == 1">/ interno</span></span>
                                </button>
                            </div>
                        </div>
                        <div class="form-group col-md-4"><label>Cantidad</label><input v-model.number="recurringItemForm.quantity" type="number" step="0.0001" class="form-control"></div>
                        <div class="form-group col-md-12"><label>Descripcion</label><input v-model="recurringItemForm.description" class="form-control"></div>
                        <div class="form-group col-md-3"><label>Unidad</label><input v-model="recurringItemForm.unit_code" class="form-control"></div>
                        <div class="form-group col-md-3"><label>Precio</label><input v-model.number="recurringItemForm.unit_price" type="number" step="0.01" class="form-control"></div>
                        <div class="form-group col-md-3"><label>IVA</label><input v-model.number="recurringItemForm.tax_rate" type="number" step="0.000001" class="form-control"></div>
                        <div class="form-group col-md-3"><label>Clave SAT</label><input v-model="recurringItemForm.sat_product_service_code" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" @click="saveRecurringItem">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cancel-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancelar CFDI</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Motivo SAT</label>
                        <select v-model="cancelForm.cancel_motive" class="form-control">
                            <option value="01">01 - Comprobante emitido con errores con relacion</option>
                            <option value="02">02 - Comprobante emitido con errores sin relacion</option>
                            <option value="03">03 - No se llevo a cabo la operacion</option>
                            <option value="04">04 - Operacion nominativa relacionada en factura global</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>UUID sustituto</label>
                        <input v-model="cancelForm.cancel_substitute_uuid" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button class="btn btn-danger" @click="cancelInvoice">Cancelar CFDI</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Vue({
    el: '#app-billing',
    data: {
        loading: true,
        error: '',
        invoices: [],
        recurringProfiles: [],
        recurringItems: [],
        recurringRuns: [],
        selectedRecurringProfile: null,
        pendingDeliveries: [],
        items: [],
        selectedInvoice: null,
        stats: {},
        options: { parties: [], products: [], currencies: [], payment_terms: [], sat_cfdi_uses: [], sat_payment_forms: [], sat_payment_methods: [], units: [], taxes: [], retentions: [], pac_connections: [] },
        noImage: '<?php echo $no_image_svg; ?>',
        productSearch: '',
        invoiceDraftSearch: '',
        invoiceDraftConcept: {},
        invoiceDraftItems: [],
        invoiceForm: {},
        recurringForm: {},
        recurringItemForm: {},
        itemForm: {},
        cancelForm: {},
        statBoxes: [
            { key: 'invoices', label: 'Facturas', icon: 'bi bi-receipt', className: 'bg-info' },
            { key: 'draft', label: 'Borradores', icon: 'bi bi-pencil-square', className: 'bg-secondary' },
            { key: 'ready', label: 'Listas', icon: 'bi bi-check2-circle', className: 'bg-warning' },
            { key: 'stamped', label: 'Timbradas', icon: 'bi bi-patch-check', className: 'bg-success' },
            { key: 'pending_deliveries', label: 'Entregas por facturar', icon: 'bi bi-truck', className: 'bg-primary' },
            { key: 'recurring_due', label: 'Recurrentes por generar', icon: 'bi bi-arrow-repeat', className: 'bg-warning' }
        ]
    },
    computed: {
        filteredProducts: function() {
            const q = (this.productSearch || '').toLowerCase().trim();
            const products = this.options.products || [];
            if (!q) return products.slice(0, 40);
            return products.filter(product => {
                const text = [product.label, product.name, product.sku].join(' ').toLowerCase();
                return text.indexOf(q) !== -1;
            }).slice(0, 40);
        },
        selectedProduct: function() {
            const productId = parseInt(this.itemForm.product_id || 0);
            return (this.options.products || []).find(product => parseInt(product.value) === productId) || {};
        },
        invoiceDraftProducts: function() {
            const q = (this.invoiceDraftSearch || '').toLowerCase().trim();
            if (q.length < 2) return [];
            return (this.options.products || []).filter(product => {
                const text = [product.label, product.name, product.sku].join(' ').toLowerCase();
                return text.indexOf(q) !== -1;
            }).slice(0, 20);
        },
        invoiceDraftProduct: function() {
            const productId = parseInt(this.invoiceDraftConcept.product_id || 0);
            return (this.options.products || []).find(product => parseInt(product.value) === productId) || {};
        }
    },
    mounted: function() {
        this.load();
    },
    methods: {
        load: function(invoiceId, recurringProfileId) {
            this.loading = true;
            let url = '<?php echo Uri::create('admin/billing/data'); ?>';
            const params = [];
            if (invoiceId) params.push('invoice_id=' + invoiceId);
            if (recurringProfileId) params.push('recurring_profile_id=' + recurringProfileId);
            if (params.length) url += '?' + params.join('&');
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.error) this.error = data.error;
                    this.invoices = data.invoices || [];
                    this.recurringProfiles = data.recurring_profiles || [];
                    this.recurringItems = data.recurring_items || [];
                    this.recurringRuns = data.recurring_runs || [];
                    this.pendingDeliveries = data.pending_deliveries || [];
                    this.items = data.items || [];
                    this.options = data.options || this.options;
                    this.stats = data.stats || {};
                    if (invoiceId) {
                        this.selectedInvoice = this.invoices.find(item => parseInt(item.id) === parseInt(invoiceId)) || this.selectedInvoice;
                    }
                })
                .catch(() => { this.error = 'No se pudo cargar facturacion.'; })
                .finally(() => { this.loading = false; });
        },
        newInvoice: function() {
            this.invoiceForm = {
                invoice_type: 'sale',
                party_id: 0,
                issue_date: new Date().toISOString().slice(0, 10),
                due_date: '',
                currency_code: 'MXN',
                exchange_rate: 1,
                payment_term_id: 0,
                pac_connection_id: 0,
                pac_series_id: '',
                pac_receptor_uid: '',
                sat_cfdi_use_code: 'G03',
                sat_payment_form_code: '99',
                sat_payment_method_code: 'PPD',
                status: 'draft',
                notes: '',
                active: true
            };
            this.invoiceDraftSearch = '';
            this.invoiceDraftConcept = this.emptyInvoiceDraftConcept();
            this.invoiceDraftItems = [];
            $('#invoice-modal').modal('show');
        },
        editInvoice: function(invoice) {
            this.invoiceForm = Object.assign({}, invoice);
            this.invoiceDraftSearch = '';
            this.invoiceDraftConcept = this.emptyInvoiceDraftConcept();
            this.invoiceDraftItems = [];
            $('#invoice-modal').modal('show');
        },
        newRecurringProfile: function() {
            const today = new Date().toISOString().slice(0, 10);
            this.recurringForm = {
                invoice_type: 'sale',
                name: '',
                party_id: 0,
                frequency: 'monthly',
                start_date: today,
                end_date: '',
                next_run_date: today,
                auto_stamp: false,
                pac_connection_id: 0,
                pac_series_id: '',
                pac_receptor_uid: '',
                currency_code: 'MXN',
                exchange_rate: 1,
                payment_term_id: 0,
                sat_cfdi_use_code: 'G03',
                sat_payment_form_code: '99',
                sat_payment_method_code: 'PPD',
                status: 'active',
                notes: '',
                active: true
            };
            $('#recurring-modal').modal('show');
        },
        editRecurringProfile: function(profile) {
            this.recurringForm = Object.assign({}, profile);
            $('#recurring-modal').modal('show');
        },
        saveRecurringProfile: function() {
            fetch('<?php echo Uri::create('admin/billing/save_recurring_profile'); ?>', window.coreAppFetchOptions(this.recurringForm))
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.recurringProfiles = data.recurring_profiles || [];
                    this.recurringItems = data.recurring_items || [];
                    this.recurringRuns = data.recurring_runs || [];
                    this.stats = data.stats || this.stats;
                    $('#recurring-modal').modal('hide');
                });
        },
        selectRecurringProfile: function(profile) {
            this.selectedRecurringProfile = profile;
            this.load(this.selectedInvoice ? this.selectedInvoice.id : 0, profile.id);
        },
        emptyRecurringItem: function(profile) {
            return {
                profile_id: profile ? profile.id : 0,
                product_id: 0,
                sat_product_service_code: '01010101',
                description: '',
                quantity: 1,
                unit_code: 'E48',
                sat_object_tax_code: '02',
                unit_price: 0,
                discount_amount: 0,
                tax_code: 'iva_16',
                tax_factor_type: 'Tasa',
                tax_rate: 0.16,
                retention_tax_code: '',
                retention_rate: 0,
                retention_amount: 0,
                active: true
            };
        },
        newRecurringItem: function(profile) {
            this.selectedRecurringProfile = profile;
            this.recurringItemForm = this.emptyRecurringItem(profile);
            this.productSearch = '';
            $('#recurring-item-modal').modal('show');
        },
        editRecurringItem: function(item) {
            this.recurringItemForm = Object.assign({}, item);
            const product = (this.options.products || []).find(option => parseInt(option.value) === parseInt(item.product_id || 0));
            this.productSearch = product ? product.label : '';
            $('#recurring-item-modal').modal('show');
        },
        chooseRecurringProduct: function(product) {
            this.recurringItemForm.product_id = product.value;
            this.recurringItemForm.description = product.name || product.label || '';
            this.recurringItemForm.unit_code = product.unit_code || (product.product_type === 'product' ? 'H87' : 'E48');
            this.recurringItemForm.unit_price = parseFloat(product.price || 0);
            this.recurringItemForm.tax_code = product.tax_code || 'iva_16';
            this.recurringItemForm.tax_rate = parseFloat(product.tax_rate || 0);
            this.recurringItemForm.sat_object_tax_code = this.recurringItemForm.tax_rate > 0 ? '02' : '01';
            this.productSearch = product.label || product.name || '';
        },
        saveRecurringItem: function() {
            fetch('<?php echo Uri::create('admin/billing/save_recurring_item'); ?>', window.coreAppFetchOptions(this.recurringItemForm))
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.recurringProfiles = data.recurring_profiles || this.recurringProfiles;
                    this.recurringItems = data.recurring_items || [];
                    this.recurringRuns = data.recurring_runs || this.recurringRuns;
                    this.stats = data.stats || this.stats;
                    $('#recurring-item-modal').modal('hide');
                });
        },
        generateRecurringInvoice: function(profile) {
            if (!confirm('Generar factura recurrente para ' + profile.folio + '?')) return;
            fetch('<?php echo Uri::create('admin/billing/generate_recurring_invoice'); ?>', window.coreAppFetchOptions({ id: profile.id }))
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.invoices = data.invoices || this.invoices;
                    this.recurringProfiles = data.recurring_profiles || this.recurringProfiles;
                    this.recurringRuns = data.recurring_runs || this.recurringRuns;
                    this.stats = data.stats || this.stats;
                    if (data.invoice_id) this.load(data.invoice_id, profile.id);
                });
        },
        saveInvoice: function() {
            fetch('<?php echo Uri::create('admin/billing/save_invoice'); ?>', window.coreAppFetchOptions(this.invoiceForm))
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.error = data.error; return; }
                    const invoiceId = data.invoice_id || (data.invoices && data.invoices.length ? data.invoices[0].id : 0);
                    if (!this.invoiceForm.id && this.invoiceDraftItems.length && invoiceId) {
                        this.saveInvoiceDraftItems(invoiceId).then(() => {
                            this.load(invoiceId);
                            $('#invoice-modal').modal('hide');
                        });
                        return;
                    }
                    this.invoices = data.invoices || [];
                    this.stats = data.stats || {};
                    if (invoiceId) this.selectedInvoice = this.invoices.find(item => parseInt(item.id) === parseInt(invoiceId)) || this.selectedInvoice;
                    $('#invoice-modal').modal('hide');
                });
        },
        saveInvoiceDraftItems: function(invoiceId) {
            const items = this.invoiceDraftItems.slice();
            const saveOne = index => {
                if (index >= items.length) return Promise.resolve();
                const payload = Object.assign({}, items[index], { invoice_id: invoiceId });
                return fetch('<?php echo Uri::create('admin/billing/save_item'); ?>', window.coreAppFetchOptions(payload))
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) throw new Error(data.error);
                    })
                    .then(() => saveOne(index + 1));
            };
            return saveOne(0).catch(error => {
                this.error = error.message || 'No se pudieron guardar todos los conceptos.';
            });
        },
        emptyInvoiceDraftConcept: function() {
            return {
                product_id: 0,
                description: '',
                quantity: 1,
                unit_code: 'H87',
                sat_object_tax_code: '02',
                unit_price: 0,
                discount_amount: 0,
                tax_code: 'iva_16',
                tax_rate: 0.16,
                tax_factor_type: 'Tasa',
                retention_tax_code: '',
                retention_rate: 0,
                retention_amount: 0,
                sat_product_service_code: '01010101',
                active: true
            };
        },
        chooseInvoiceDraftProduct: function(product) {
            this.invoiceDraftConcept = Object.assign({}, this.emptyInvoiceDraftConcept(), {
                product_id: product.value,
                description: product.name || product.label || '',
                quantity: this.invoiceDraftConcept.quantity || 1,
                unit_code: product.unit_code || 'H87',
                unit_price: parseFloat(product.price || 0),
                tax_code: product.tax_code || 'iva_16',
                tax_rate: parseFloat(product.tax_rate || 0),
                sat_object_tax_code: parseFloat(product.tax_rate || 0) > 0 ? '02' : '01'
            });
            this.invoiceDraftSearch = product.label || product.name || '';
        },
        addInvoiceDraftConcept: function() {
            if (!this.invoiceDraftConcept.description) {
                alert('Selecciona producto o captura descripcion.');
                return;
            }
            this.invoiceDraftItems.push(Object.assign({}, this.invoiceDraftConcept));
            this.invoiceDraftSearch = '';
            this.invoiceDraftConcept = this.emptyInvoiceDraftConcept();
        },
        removeInvoiceDraftConcept: function(index) {
            this.invoiceDraftItems.splice(index, 1);
        },
        draftConceptTotal: function(concept) {
            const base = Math.max(0, (Number(concept.quantity || 0) * Number(concept.unit_price || 0)) - Number(concept.discount_amount || 0));
            return base + (base * Number(concept.tax_rate || 0)) - Number(concept.retention_amount || 0);
        },
        selectInvoice: function(invoice) {
            this.selectedInvoice = invoice;
            this.load(invoice.id);
        },
        newItem: function() {
            if (!this.selectedInvoice) return;
            this.itemForm = {
                invoice_id: this.selectedInvoice.id,
                product_id: 0,
                description: '',
                quantity: 1,
                unit_code: 'H87',
                sat_object_tax_code: '02',
                unit_price: 0,
                discount_amount: 0,
                tax_rate: 0.16,
                tax_factor_type: 'Tasa',
                retention_tax_code: '',
                retention_rate: 0,
                retention_amount: 0,
                sat_product_service_code: '01010101',
                active: true
            };
            this.productSearch = '';
            $('#item-modal').modal('show');
        },
        editItem: function(item) {
            this.itemForm = Object.assign({}, item);
            const product = (this.options.products || []).find(option => parseInt(option.value) === parseInt(this.itemForm.product_id || 0));
            this.productSearch = product ? product.label : '';
            $('#item-modal').modal('show');
        },
        chooseManualConcept: function() {
            this.itemForm.product_id = 0;
            this.productSearch = '';
        },
        chooseProduct: function(product) {
            this.itemForm.product_id = product.value;
            this.itemForm.description = product.name || product.label || '';
            this.itemForm.unit_code = product.unit_code || 'H87';
            this.itemForm.unit_price = parseFloat(product.price || 0);
            this.itemForm.tax_code = product.tax_code || 'iva_16';
            this.itemForm.tax_rate = parseFloat(product.tax_rate || 0);
            this.itemForm.sat_object_tax_code = this.itemForm.tax_rate > 0 ? '02' : '01';
            this.productSearch = product.label || product.name || '';
        },
        saveItem: function() {
            fetch('<?php echo Uri::create('admin/billing/save_item'); ?>', window.coreAppFetchOptions(this.itemForm))
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.invoices = data.invoices || [];
                    this.items = data.items || [];
                    this.stats = data.stats || {};
                    this.selectedInvoice = this.invoices.find(item => parseInt(item.id) === parseInt(this.itemForm.invoice_id)) || this.selectedInvoice;
                    $('#item-modal').modal('hide');
                });
        },
        prepareCfdi: function(invoice) {
            fetch('<?php echo Uri::create('admin/billing/prepare_cfdi'); ?>', window.coreAppFetchOptions({ id: invoice.id }))
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.invoices = data.invoices || [];
                    this.items = data.items || this.items;
                    this.stats = data.stats || {};
                    this.selectedInvoice = this.invoices.find(item => parseInt(item.id) === parseInt(invoice.id)) || this.selectedInvoice;
                });
        },
        stampInvoice: function(invoice) {
            if (!confirm('Timbrar factura ' + invoice.folio + ' con Factura.com?')) return;
            fetch('<?php echo Uri::create('admin/billing/stamp_invoice'); ?>', window.coreAppFetchOptions({ id: invoice.id }))
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.invoices = data.invoices || [];
                    this.items = data.items || this.items;
                    this.stats = data.stats || {};
                    this.selectedInvoice = this.invoices.find(item => parseInt(item.id) === parseInt(invoice.id)) || this.selectedInvoice;
                });
        },
        openCancel: function(invoice) {
            this.cancelForm = { id: invoice.id, cancel_motive: '02', cancel_substitute_uuid: '' };
            $('#cancel-modal').modal('show');
        },
        cancelInvoice: function() {
            fetch('<?php echo Uri::create('admin/billing/cancel_invoice'); ?>', window.coreAppFetchOptions(this.cancelForm))
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.invoices = data.invoices || [];
                    this.items = data.items || this.items;
                    this.stats = data.stats || {};
                    this.selectedInvoice = this.invoices.find(item => parseInt(item.id) === parseInt(this.cancelForm.id)) || this.selectedInvoice;
                    $('#cancel-modal').modal('hide');
                });
        },
        createInvoiceFromDelivery: function(delivery) {
            if (!confirm('Crear factura desde entrega ' + delivery.folio + '?')) return;
            fetch('<?php echo Uri::create('admin/billing/create_from_delivery'); ?>', window.coreAppFetchOptions({ delivery_id: delivery.id }))
                .then(res => res.json())
                .then(data => {
                    if (data.error) { this.error = data.error; return; }
                    this.invoices = data.invoices || this.invoices;
                    this.pendingDeliveries = data.pending_deliveries || [];
                    this.stats = data.stats || this.stats;
                    this.load(data.invoice_id || 0);
                });
        },
        money: function(value, currency) {
            return new Intl.NumberFormat('es-MX', { style: 'currency', currency: currency || 'MXN' }).format(parseFloat(value || 0));
        },
        recurringFrequencyLabel: function(value) {
            return ({ weekly: 'Semanal', biweekly: 'Quincenal', monthly: 'Mensual', quarterly: 'Trimestral', yearly: 'Anual' })[value] || value;
        },
        recurringStatusLabel: function(value) {
            return ({ active: 'Activo', paused: 'Pausado', finished: 'Terminado' })[value] || value;
        },
        productTypeLabel: function(value) {
            return ({ product: 'Producto', service: 'Servicio', rental: 'Renta' })[value] || value;
        }
    }
});
</script>
