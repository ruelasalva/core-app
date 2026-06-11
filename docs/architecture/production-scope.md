# Alcance productivo actual

## Proposito

Este documento define el alcance productivo inmediato de CORE-APP para evitar que modulos futuros o portales secundarios consuman esfuerzo antes de estabilizar las piezas criticas.

La prioridad es llevar a produccion una base estable, segura y mantenible.

## Dentro del alcance productivo actual

### Admin

El administrador es el centro operativo del ERP.

Prioridad:

- Seguridad de usuarios y permisos.
- Modulos comerciales.
- Ventas.
- Compras.
- Inventario.
- Facturacion.
- CFDI.
- Pagos.
- Contratos.
- Documentos.
- Helpdesk.
- Configuracion estrictamente necesaria para operar.

Regla:

Antes de ampliar portales secundarios, el admin debe estar estable, documentado y con permisos claros.

### Frontend publico

El frontend publico forma parte del alcance productivo porque es el punto de entrada comercial.

Prioridad:

- Identidad publica.
- Catalogo o contenido comercial.
- Formularios de contacto.
- Conversion.
- CMS editable.
- Rutas limpias en produccion.

Regla:

El frontend publico debe permanecer editable por usuarios no tecnicos en la medida posible.

### Portal de clientes

El portal de clientes esta dentro del alcance productivo actual.

Prioridad:

- Dashboard claro.
- Estado de cuenta.
- Facturas / CFDI.
- Contratos.
- Cotizaciones y pedidos.
- Tickets.
- Perfil y cambio de contrasena.
- Descargas seguras.

Identidad:

- Comercial.
- Cuenta corriente.
- Facturacion.
- Seguimiento de ventas.

### Portal de proveedores

El portal de proveedores esta dentro del alcance productivo actual.

Prioridad:

- Dashboard proveedor.
- Ordenes de compra.
- Registro de facturas.
- Contrarecibos.
- Evidencias.
- CFDI visibles.
- Contratos.
- Tickets.
- Perfil documental.
- Descargas seguras.

Identidad:

- Compras.
- Ordenes.
- Facturas.
- Evidencias.
- Cumplimiento documental.

## Fuera del alcance productivo actual

Los siguientes portales quedan diferidos hasta estabilizar Admin, Frontend publico, Portal de clientes y Portal de proveedores:

- Portal de revendedores.
- Portal de empleados.
- Portal de socios.
- Cualquier otro portal futuro.

Esto no significa que no puedan existir clases, vistas base o rutas tecnicas. Significa que no deben recibir trabajo funcional o visual profundo hasta que el alcance productivo principal este estable.

## Justificacion

CORE-APP ya contiene muchos modulos transversales. Abrir todos los portales al mismo tiempo aumenta riesgo en:

- Seguridad.
- Permisos.
- Descargas documentales.
- UX inconsistente.
- Logica duplicada.
- Pruebas incompletas.
- Mantenimiento futuro.

La estrategia correcta es estabilizar primero los flujos que generan operacion real:

1. Admin.
2. Frontend publico.
3. Portal de clientes.
4. Portal de proveedores.

Despues se incorporan portales adicionales con el mismo sistema visual y de seguridad.

## Regla de identidad visual por portal

Todos los portales deben compartir componentes base:

- `portal-page-hero`
- `portal-panel`
- `portal-kpi-grid`
- `portal-kpi`
- `portal-empty`
- `portal-table`
- `portal-tabs`

Cada portal debe usar una identidad propia:

- `portal--clientes`: comercial, cuenta, cobranza, CFDI y ventas.
- `portal--proveedores`: compras, ordenes, facturas, contrarecibos y evidencias.
- Futuro `portal--revendedores`: canal, clientes propios, ventas y comisiones.
- Futuro `portal--empleados`: RH, nomina, expediente y servicios internos.
- Futuro `portal--socios`: contratos, colaboracion y documentos compartidos.

## Linea base de seguridad para todos los portales

Estas reglas son obligatorias para cualquier portal actual o futuro:

- Nunca confiar en `party_id` recibido por request.
- Usar siempre `$this->portal_link->party_id`.
- Validar que el portal link este activo.
- Validar que el tercero este activo.
- Validar que el tipo de tercero sea permitido para el portal.
- Nunca exponer `file_path`.
- Nunca exponer `storage_path`.
- Nunca exponer rutas fisicas.
- Usar siempre `download_url`.
- Validar propiedad antes de descargar documentos.
- Bloquear acceso cruzado entre terceros.
- Usar endpoints controlados de descarga.
- Registrar intentos denegados con `Log::warning()`.
- Registrar archivos faltantes con `Log::error()`.
- Enforzar `password_must_change` cuando el flujo de portal lo requiera.

## Prioridad productiva

Orden recomendado:

1. Completar y estabilizar Admin.
2. Completar y estabilizar Frontend publico.
3. Completar y estabilizar Portal de clientes.
4. Completar y estabilizar Portal de proveedores.
5. Diferir revendedores, empleados, socios y futuros portales hasta despues de estabilizacion productiva.

## Criterios para mover un portal futuro a alcance productivo

Un portal futuro puede entrar a alcance productivo cuando cumpla:

- Tiene objetivo de negocio claro.
- Tiene controlador base seguro.
- No acepta `party_id` del request.
- Usa identidad visual propia.
- Reusa componentes base.
- Tiene dashboard propio.
- Tiene descargas controladas si maneja documentos.
- Tiene pruebas de acceso cruzado.
- Tiene documentacion del flujo.
- No duplica logica existente.

## Checklist de estabilizacion

- Admin carga sin errores criticos.
- Frontend publico carga desde produccion sin `/public`.
- Portal cliente carga y no expone rutas fisicas.
- Portal proveedor carga y no expone rutas fisicas.
- Documentos usan `download_url`.
- Usuarios con contrasena forzada no pueden saltar el cambio.
- Menus moviles no se rompen.
- Vistas principales tienen estados vacios claros.
- Permisos administrativos estan documentados.
- No hay credenciales ni datos sensibles en documentacion.
