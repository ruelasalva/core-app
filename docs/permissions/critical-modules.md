# Permisos de Modulos Criticos

## Proposito

Este documento define el uso esperado de permisos granulares para modulos criticos de CORE-APP ERP.

Aplica a:

- SAT
- CFDI
- Facturacion
- Fiscal
- Contabilidad

El objetivo es separar lectura, operacion y acciones sensibles sin cambiar la logica de negocio existente.

## Reglas Generales

- Todos los permisos usan ORMAuth.
- Los permisos deben validarse antes de ejecutar acciones administrativas.
- Los permisos granulares no deben sustituir validaciones de negocio.
- Los permisos granulares no deben permitir acceso a modulos relacionados si no se asignan de forma explicita.
- El permiso antiguo amplio se conserva temporalmente como fallback de compatibilidad.
- Todo uso de fallback debe registrarse con `Log::warning()`.
- Super admin puede acceder por reglas globales del sistema.

## SAT

### Permisos

| Permiso | Permite |
| --- | --- |
| `sat.access[view]` | Ver panel SAT y estado general de integracion. |
| `sat.access[download]` | Crear solicitudes de descarga, enviarlas, verificarlas y descargar paquetes SAT. |
| `sat.access[validate]` | Ejecutar validaciones SAT si existen acciones separadas de validacion. |
| `sat.access[catalog_sync]` | Administrar fuentes de sincronizacion y sincronizar catalogos SAT. |
| `sat.access[credentials]` | Guardar configuracion SAT, credenciales, certificados y archivos de FIEL/CSD. |

### No Debe Permitir

- `sat.access[view]` no debe permitir descargar XML, subir certificados ni modificar credenciales.
- `sat.access[download]` no debe permitir ver o cambiar secretos, certificados o configuracion tecnica.
- `sat.access[catalog_sync]` no debe permitir descargar CFDI ni modificar credenciales.
- `sat.access[credentials]` no debe permitir clasificar CFDI, convertir compras o timbrar facturas.

### Fallback Temporal

- Algunas acciones aceptan temporalmente `sat.access[edit]`.
- Si se usa el fallback, debe registrarse:

```php
Log::warning('SAT granular permission fallback used ...');
```

## CFDI

### Permisos

| Permiso | Permite |
| --- | --- |
| `cfdi.access[view]` | Ver auditoria CFDI y documentos importados. |
| `cfdi.access[classify]` | Importar XML, materializar catalogos y procesar documentos seleccionados. |
| `cfdi.access[link]` | Guardar relaciones y equivalencias con proveedores, productos o terceros. |
| `cfdi.access[convert_purchase]` | Convertir CFDI recibido a flujo de compra cuando tambien existan permisos de compras. |
| `cfdi.access[convert_sale]` | Convertir CFDI emitido a flujo de venta o facturacion cuando tambien existan permisos del modulo destino. |
| `cfdi.access[audit]` | Ver auditoria, validaciones, relaciones y revision fiscal de CFDI. |

### No Debe Permitir

- `cfdi.access[view]` no debe permitir convertir documentos a compras o ventas.
- `cfdi.access[classify]` no debe permitir crear ordenes de compra si no existe permiso de compras.
- `cfdi.access[convert_purchase]` no debe sustituir `purchases.access[create]`.
- `cfdi.access[convert_sale]` no debe sustituir permisos de ventas o facturacion.
- Ningun permiso CFDI debe permitir modificar credenciales SAT.

### Fallback Temporal

- Acciones heredadas pueden aceptar temporalmente `sat.access[view]`, `sat.access[import]` o `sat.access[edit]`, segun el caso.
- Si se usa fallback, debe registrarse:

```php
Log::warning('CFDI granular permission fallback used ...');
```

## Facturacion

### Permisos

| Permiso | Permite |
| --- | --- |
| `billing.access[view]` | Ver facturas, documentos fiscales y datos generales de facturacion. |
| `billing.access[create]` | Crear o editar borradores, conceptos, facturas desde entregas y facturas recurrentes. |
| `billing.access[stamp]` | Timbrar CFDI con PAC configurado. |
| `billing.access[cancel]` | Cancelar CFDI timbrados. |
| `billing.access[rep]` | Operar complementos de pago REP cuando existan acciones separadas. |
| `billing.access[credit_note]` | Operar notas de credito cuando existan acciones separadas. |

### No Debe Permitir

- `billing.access[view]` no debe permitir crear, timbrar o cancelar.
- `billing.access[create]` no debe permitir timbrar ni cancelar.
- `billing.access[stamp]` no debe permitir crear borradores si no existe permiso de creacion.
- `billing.access[cancel]` no debe permitir editar conceptos o cambiar saldos.
- `billing.access[rep]` no debe permitir timbrar facturas normales ni cancelar.
- `billing.access[credit_note]` no debe permitir modificar facturas originales sin permisos adicionales.

### Fallback Temporal

- Acciones de creacion, timbrado, cancelacion, REP o notas pueden aceptar temporalmente `billing.access[edit]`.
- Si se usa fallback, debe registrarse:

```php
Log::warning('Billing granular permission fallback used ...');
```

## Fiscal

### Permisos

| Permiso | Permite |
| --- | --- |
| `fiscal.access[view]` | Ver panel fiscal, bitacora fiscal y auditoria REP/PPD de consulta. |
| `fiscal.access[ledger]` | Ver libro fiscal, validaciones y conciliacion fiscal-contable. |
| `fiscal.access[diot]` | Ver preparacion DIOT. |
| `fiscal.access[iva]` | Ver resumen y detalle de IVA mensual. |
| `fiscal.access[closing]` | Ver centro de cierre fiscal. |
| `fiscal.access[export]` | Reservado para futuras exportaciones fiscales. No debe usarse hasta que existan endpoints separados. |

### No Debe Permitir

- `fiscal.access[view]` no debe permitir modificar CFDI, facturas, polizas o periodos.
- `fiscal.access[ledger]` no debe permitir reconstruir libro fiscal si la accion no existe en controlador.
- `fiscal.access[diot]` no debe permitir cambiar configuracion contable.
- `fiscal.access[iva]` no debe permitir generar polizas.
- `fiscal.access[closing]` no debe cerrar periodos contables si no existe una accion explicita y permisos adicionales.
- `fiscal.access[export]` no debe usarse en vistas generales.

### Fallback Temporal

- Acciones fiscales pueden aceptar temporalmente `fiscal.access`.
- Si se usa fallback, debe registrarse:

```php
Log::warning('Fiscal granular permission fallback used ...');
```

## Contabilidad

### Permisos

| Permiso | Permite |
| --- | --- |
| `accounting.access[view]` | Entrar al modulo contable y consultar informacion base. |
| `accounting.access[chart]` | Crear o editar catalogo de cuentas, centros de costo y mapeos fiscales-contables. |
| `accounting.access[post]` | Crear o editar polizas, lineas, reglas de contabilizacion y contabilizar polizas. |
| `accounting.access[periods]` | Crear, editar, cerrar o bloquear periodos contables. |
| `accounting.access[reports]` | Reservado para reportes separados. No debe usarse mientras `action_data` mezcle datos. |
| `accounting.access[export]` | Reservado para exportaciones contables futuras. |

### No Debe Permitir

- `accounting.access[view]` no debe permitir crear cuentas, polizas, reglas o periodos.
- `accounting.access[chart]` no debe permitir contabilizar polizas.
- `accounting.access[post]` no debe permitir cerrar periodos.
- `accounting.access[periods]` no debe permitir editar polizas ni cuentas.
- `accounting.access[reports]` no debe usarse hasta separar endpoints de reportes.
- `accounting.access[export]` no debe usarse hasta separar endpoints de exportacion.

### Regla Base Actual

El controlador de contabilidad conserva `accounting.access[view]` como requisito base en `before()`.

Esto significa que un usuario con solo:

- `accounting.access[chart]`
- `accounting.access[post]`
- `accounting.access[periods]`

no debe entrar al modulo si no tiene tambien `accounting.access[view]`.

Motivo: la accion `data` todavia devuelve datos mixtos de catalogo, polizas, periodos y reportes.

### Fallback Temporal

- Acciones de escritura pueden aceptar temporalmente `accounting.access[edit]`.
- Si se usa fallback, debe registrarse:

```php
Log::warning('Accounting granular permission fallback used ...');
```

## Comportamiento de Fallback Temporal

Los fallbacks existen para no romper usuarios y grupos existentes mientras se migran permisos.

Reglas:

- El fallback no debe ser permanente.
- Cada uso debe dejar log.
- Los logs deben revisarse antes de retirar permisos antiguos.
- Un rol nuevo no debe depender del fallback.
- Los permisos antiguos amplios no deben asignarse a roles nuevos salvo decision explicita.

Fases recomendadas:

1. Sembrar permisos granulares.
2. Ajustar menu con permisos granulares.
3. Ajustar controladores en modo compatibilidad.
4. Revisar logs de fallback.
5. Actualizar grupos reales.
6. Retirar fallback en una fase posterior aprobada.

## Ejemplos de Roles Recomendados

### Administrador Fiscal

Permisos:

- `sat.access[view]`
- `sat.access[download]`
- `sat.access[validate]`
- `cfdi.access[view]`
- `cfdi.access[audit]`
- `fiscal.access[view]`
- `fiscal.access[ledger]`
- `fiscal.access[diot]`
- `fiscal.access[iva]`
- `fiscal.access[closing]`

No asignar por defecto:

- `sat.access[credentials]`
- `billing.access[stamp]`
- `billing.access[cancel]`
- `accounting.access[post]`

### Encargado SAT Tecnico

Permisos:

- `sat.access[view]`
- `sat.access[credentials]`
- `sat.access[catalog_sync]`

No debe tener:

- `cfdi.access[convert_purchase]`
- `cfdi.access[convert_sale]`
- `billing.access[stamp]`
- `accounting.access[post]`

### Auditor CFDI

Permisos:

- `cfdi.access[view]`
- `cfdi.access[audit]`
- `fiscal.access[view]`

No debe tener:

- `cfdi.access[convert_purchase]`
- `cfdi.access[convert_sale]`
- `sat.access[credentials]`

### Facturacion Operativa

Permisos:

- `billing.access[view]`
- `billing.access[create]`

Opcional con autorizacion:

- `billing.access[stamp]`
- `billing.access[rep]`
- `billing.access[credit_note]`

No debe tener por defecto:

- `billing.access[cancel]`
- `sat.access[credentials]`
- `accounting.access[post]`

### Responsable de Cancelaciones

Permisos:

- `billing.access[view]`
- `billing.access[cancel]`

Opcional:

- `billing.access[credit_note]`

No debe tener por defecto:

- `billing.access[stamp]`
- `accounting.access[post]`

### Contador Operativo

Permisos:

- `accounting.access[view]`
- `accounting.access[chart]`
- `accounting.access[post]`
- `accounting.access[periods]`
- `fiscal.access[view]`
- `fiscal.access[ledger]`

No debe tener por defecto:

- `sat.access[credentials]`
- `billing.access[cancel]`
- `users.access[edit]`
- `permissions.access[edit]`

### Analista Contable de Consulta

Permisos:

- `accounting.access[view]`
- `fiscal.access[view]`
- `fiscal.access[ledger]`

No debe tener:

- `accounting.access[post]`
- `accounting.access[periods]`
- `billing.access[stamp]`

## Checklist de Pruebas

### Validacion General

- Confirmar que el usuario super admin conserva acceso.
- Confirmar que un usuario sin permisos criticos no ve menus criticos.
- Confirmar que un usuario sin permisos criticos no puede ejecutar endpoints por URL directa.
- Confirmar que los endpoints devuelven denegado cuando falta permiso.
- Confirmar que los fallbacks registran `Log::warning()`.
- Confirmar que roles nuevos no usan permisos antiguos amplios.

### SAT

- Usuario con `sat.access[view]` puede ver panel SAT.
- Usuario con solo `sat.access[view]` no puede guardar credenciales.
- Usuario con `sat.access[credentials]` puede guardar credenciales si tambien tiene acceso base necesario.
- Usuario con `sat.access[download]` puede operar solicitudes de descarga.
- Usuario sin `sat.access[download]` no puede enviar, verificar ni descargar paquetes.
- Uso de `sat.access[edit]` genera warning de fallback.

### CFDI

- Usuario con `cfdi.access[view]` puede ver auditoria CFDI.
- Usuario con `cfdi.access[classify]` puede importar o materializar documentos.
- Usuario sin `cfdi.access[convert_purchase]` no puede convertir a compra.
- Usuario sin `cfdi.access[convert_sale]` no puede convertir a venta.
- Conversiones siguen exigiendo permisos del modulo destino cuando apliquen.
- Uso de fallback heredado genera warning.

### Facturacion

- Usuario con `billing.access[view]` puede consultar facturas.
- Usuario con `billing.access[create]` puede crear borradores y conceptos.
- Usuario sin `billing.access[stamp]` no puede timbrar.
- Usuario sin `billing.access[cancel]` no puede cancelar.
- Usuario sin `billing.access[rep]` no debe operar REP cuando existan endpoints separados.
- Uso de `billing.access[edit]` genera warning de fallback.

### Fiscal

- Usuario con `fiscal.access[view]` puede ver panel y bitacora.
- Usuario con `fiscal.access[ledger]` puede ver libro fiscal y conciliacion.
- Usuario con `fiscal.access[iva]` puede ver IVA mensual.
- Usuario con `fiscal.access[diot]` puede ver DIOT.
- Usuario con `fiscal.access[closing]` puede ver centro de cierre.
- Usuario sin permiso granular no puede acceder por URL directa.
- Uso de `fiscal.access` genera warning de fallback.

### Contabilidad

- Usuario con `accounting.access[view]` puede entrar al modulo.
- Usuario con solo `accounting.access[chart]` no entra si no tiene tambien `accounting.access[view]`.
- Usuario con `accounting.access[chart]` y `accounting.access[view]` puede editar cuentas, centros de costo y mapeos.
- Usuario con `accounting.access[post]` y `accounting.access[view]` puede crear y contabilizar polizas.
- Usuario con `accounting.access[periods]` y `accounting.access[view]` puede administrar periodos.
- Usuario sin `accounting.access[post]` no puede contabilizar.
- Uso de `accounting.access[edit]` genera warning de fallback.

## Notas de Mantenimiento

- Antes de retirar fallbacks, revisar logs de produccion.
- Antes de activar permisos de exportacion, crear endpoints separados.
- Antes de usar `accounting.access[reports]`, separar la carga de reportes de `action_data`.
- Antes de crear roles nuevos, validar menus visibles y endpoints directos.
- Documentar cualquier permiso nuevo en este archivo y en la seed correspondiente.
