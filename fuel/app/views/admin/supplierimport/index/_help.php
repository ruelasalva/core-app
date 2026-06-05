    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-secondary supplier-import-help">
                <div class="card-header">
                    <h3 class="card-title mb-0">Comandos</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">Validar CSV sin guardar:</p>
                    <code>php oil refine supplierimportcsv --file=PATH --provider=CODE --dry-run=1</code>
                    <hr>
                    <p class="mb-2">Guardar en staging:</p>
                    <code>php oil refine supplierimportcsv --file=PATH --provider=CODE --dry-run=0</code>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-outline card-secondary supplier-import-help">
                <div class="card-header">
                    <h3 class="card-title mb-0">Ayuda: Importaci&oacute;n de proveedores</h3>
                </div>
                <div class="card-body">
                    <p>La importaci&oacute;n de proveedores permite cargar cat&aacute;logos externos a una tabla temporal de revisi&oacute;n antes de crear productos reales.</p>
                    <ul>
                        <li>El modo dry-run solo valida y muestra totales.</li>
                        <li>El modo staging guarda filas en espera de revisi&oacute;n.</li>
                        <li>La ruta recomendada actualmente es CSV / Excel manual.</li>
                        <li>No se modifican productos, precios, inventario ni im&aacute;genes.</li>
                        <li>Las columnas principales son SKU, modelo, nombre, marca, categor&iacute;a, precio, moneda y URL de origen.</li>
                    </ul>
                    <div class="alert alert-warning mb-3">
                        TonersParaImpresoras requiere estrategia adicional porque su cat&aacute;logo depende de JavaScript. Actualmente solo se puede descubrir URLs por sitemap; no se importan productos autom&aacute;ticamente.
                    </div>
                    <p class="mb-0">Cuando se aprueben fases posteriores, estas filas podr&aacute;n mapearse contra productos internos mediante equivalencias de proveedor.</p>
                </div>
            </div>
        </div>
    </div>
