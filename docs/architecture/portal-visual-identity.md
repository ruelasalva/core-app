# Identidad visual de portales

## Proposito

Este documento define la estrategia visual de los portales externos de CORE-APP para mantener consistencia tecnica sin confundir al usuario final.

Los portales deben compartir un sistema de componentes, pero cada portal debe tener una identidad visual propia para que el usuario reconozca de inmediato en que contexto operativo esta trabajando.

## Alcance actual

En produccion inicial se consideran activos y prioritarios:

- Portal de clientes.
- Portal de proveedores.

Otros portales pueden existir en codigo o estructura base, pero no forman parte del alcance productivo inmediato hasta estabilizar los portales anteriores.

## Regla general

Todos los portales deben usar los mismos componentes base:

- `portal-page-hero`
- `portal-panel`
- `portal-kpi-grid`
- `portal-kpi`
- `portal-empty`
- `portal-table`
- `portal-tabs`

Cada portal debe aplicar una clase de tema en el `body`:

- `portal--clientes`
- `portal--proveedores`
- Futuro: `portal--revendedores`
- Futuro: `portal--empleados`
- Futuro: `portal--socios`

Las clases base definen estructura, espaciado y comportamiento responsivo. Las clases de tema definen color, tono visual y lenguaje del portal.

## Portal de clientes

Identidad:

- Comercial.
- Estado de cuenta.
- Facturacion visible.
- CFDI.
- Cotizaciones y pedidos.
- Tickets de atencion.

Lenguaje recomendado:

- "Estado de cuenta"
- "Facturas / CFDI"
- "Cotizaciones y pedidos"
- "Saldo pendiente"
- "Saldo vencido"
- "Abrir ticket"

Uso visual:

- Acento azul.
- Enfoque en claridad financiera y seguimiento comercial.
- Acciones orientadas a consulta, seguimiento y solicitud.

## Portal de proveedores

Identidad:

- Compras.
- Ordenes.
- Facturas de proveedor.
- Contrarecibos.
- Evidencias.
- Cumplimiento documental.

Lenguaje recomendado:

- "Portal de proveedores"
- "Ordenes de compra"
- "Registrar factura"
- "Adjuntar evidencia"
- "Contrarecibos"
- "Documentos de cumplimiento"

Uso visual:

- Acento verde petroleo / teal.
- Acento secundario ambar para acciones operativas.
- Enfoque en compras, entregas, facturas y evidencia documental.

## Futuro portal de revendedores

Identidad recomendada:

- Canal comercial.
- Ventas indirectas.
- Clientes propios.
- Cotizaciones.
- Comisiones.

Lenguaje sugerido:

- "Portal de revendedores"
- "Mis clientes"
- "Cotizaciones del canal"
- "Comisiones"
- "Pedidos del canal"

Color sugerido:

- Indigo o violeta sobrio, evitando que se confunda con cliente o proveedor.

## Futuro portal de empleados

Identidad recomendada:

- Recursos humanos.
- Nomina.
- Documentos internos.
- Solicitudes.
- Servicios internos.

Lenguaje sugerido:

- "Portal de empleados"
- "Recibos de nomina"
- "Solicitudes internas"
- "Documentos laborales"
- "Mi expediente"

Color sugerido:

- Azul grisaceo o cian sobrio.

## Futuro portal de socios

Identidad recomendada:

- Contratos.
- Colaboracion.
- Relaciones comerciales.
- Documentos compartidos.
- Seguimiento ejecutivo.

Lenguaje sugerido:

- "Portal de socios"
- "Contratos"
- "Documentos compartidos"
- "Eventos"
- "Colaboracion"

Color sugerido:

- Verde oscuro, grafito o dorado institucional.

## Reglas de implementacion

1. No crear componentes nuevos si los componentes base resuelven el caso.
2. No duplicar estilos por vista cuando pueda usarse una clase de tema.
3. No mezclar lenguaje de cliente en proveedor ni lenguaje de proveedor en cliente.
4. No introducir dependencias frontend nuevas para identidad visual.
5. Mantener Vue 2 Options API en vistas que usen Vue.
6. Mantener Bootstrap/AdminLTE cuando el modulo ya dependa de ellos.
7. Mantener las acciones principales visibles en el `portal-page-hero`.
8. Usar `portal-empty` para estados vacios en tablas, listas y paneles.

## Seguridad visual y documental

La identidad visual no debe reducir controles de seguridad.

Reglas obligatorias:

- Nunca mostrar `file_path`.
- Nunca mostrar `storage_path`.
- Nunca mostrar rutas fisicas.
- Usar siempre `download_url`.
- Las descargas deben ir por endpoints controlados.
- No aceptar `party_id` desde formularios o URLs del portal.
- Usar siempre `$this->portal_link->party_id` en controladores de portal.
- Validar propiedad del documento antes de descargar.
- Bloquear acceso cruzado entre terceros.

## Checklist para un nuevo portal

- Definir clase `portal--codigo`.
- Definir color principal, secundario y acento.
- Crear dashboard especifico si el portal entra a produccion.
- Reusar componentes base.
- Revisar que todos los listados tengan estados vacios.
- Revisar que todos los documentos usen `download_url`.
- Validar que ningun JSON exponga rutas fisicas.
- Validar que ninguna consulta acepte `party_id` del request.
- Validar `php -l` en todas las vistas modificadas.
- Probar menu en movil.
