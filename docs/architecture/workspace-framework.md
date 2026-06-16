# CORE-APP Workspace Framework

## Propósito

Workspace será el escritorio operativo reutilizable de CORE-APP. No reemplaza todavía el dashboard existente y no migra lógica de negocio. Esta base define infraestructura para futuros widgets, acciones rápidas, layouts por usuario/rol y preferencias.

## Alcance Inicial

- Framework MVC compatible con FuelPHP 1.8.
- Vue 2 Options API desde CDN o assets existentes.
- Bootstrap/AdminLTE.
- Layout por coordenadas `x`, `y`, `w`, `h`.
- Widgets dummy sin datos ERP.
- Quick actions de navegación.
- Sin Composer, Vite, Webpack ni SPA.

## Data Provider Layer

Los widgets reales deberán obtener datos mediante proveedores de lectura. Un provider no modifica ventas, compras, inventario, SAT, fiscal, contabilidad ni saldos. Su salida se normaliza antes de llegar a Vue.

## Repository Layer

Los modelos `Model_Core_Workspace_*` son delgados y solo representan tablas. La lectura, validación, cache y resolución de permisos viven en servicios.

## Widget Independent Permissions

Cada widget tiene `permission_key`. El frontend puede ocultar widgets, pero el backend siempre vuelve a validar permisos en `Service_Core_Workspace_WidgetRunner`.

## Widget Categories, Tags and Capabilities

El catálogo guarda:

- `category`
- `tags_json`
- `capabilities_json`

Las capabilities describen lo que soporta el widget: `refresh`, `export`, `configure`, `collapse`, `favorite`.

## Widget States

Estados esperados:

- `loading`
- `ready`
- `empty`
- `error`
- `forbidden`
- `collapsed`
- `hidden`
- `stale`

## Workspace Lightweight Container

`Service_Core_Workspace_Container` centraliza contexto, catálogo, preferencias, quick actions y ejecución de widgets. No debe contener lógica ERP.

## Widget Response Contract

Todo widget responde:

```json
{
  "success": true,
  "message": "",
  "payload": {},
  "meta": {},
  "health": {},
  "actions": [],
  "errors": []
}
```

## Widget Action Contract

Las acciones de widget son seguras y explícitas. No ejecutan operaciones destructivas. Para acciones críticas solo navegan al módulo correspondiente, donde se valida permiso y confirmación.

## Cache Levels

- Nivel widget.
- Nivel usuario/grupo.
- Nivel filtros/settings.
- Nivel manifest/catalog metadata.

No cachear permisos, sesiones, secretos, documentos privados ni rutas físicas.

## Widget Security Rules

- No exponer `file_path`, `storage_path` ni rutas físicas.
- No exponer costos, márgenes ni secretos.
- No elevar permisos por settings.
- No confiar en datos enviados por el frontend.
- Validar permisos por widget y por quick action.

## Workspace Audit Concept

Futuras fases podrán auditar:

- cambios de layout
- widgets agregados/removidos
- preferencias guardadas
- quick actions usadas
- errores de widgets

## Feature Flags

El catálogo incluye `feature_flag` y `status` para activar/desactivar widgets sin borrar layouts.

## Marketplace-ready Metadata

El catálogo conserva metadata suficiente para una futura biblioteca interna:

- icono
- color
- categoría
- tags
- capabilities
- versión
- estado

## Plugin Architecture

El registry PHP mapea `code` a clase. La base de datos no guarda clases PHP ni componentes Vue. Esto permite registrar widgets internos o futuros plugins sin ejecutar clases arbitrarias desde DB.

## Telemetry

Los widgets pueden devolver `health` con:

- `generated_at`
- `cache_hit`
- `execution_ms`
- `stale`
- `warning`

Solo debe mostrarse a usuarios con permiso administrativo/debug.

## Repair Tasks

Futuras tareas:

- reparar layouts con widgets inactivos
- regenerar snapshots
- limpiar preferencias inválidas
- validar instancias sin catálogo

## Workspace SDK Pattern

Para crear widgets futuros:

1. Crear clase que extienda `Service_Core_Workspace_Widget`.
2. Declarar `manifest()`.
3. Implementar `load()`.
4. Registrar clase en `Service_Core_Workspace_WidgetRegistry`.
5. Sembrar catálogo con `seedworkspace`.
6. Validar permisos y respuesta estándar.

## RC2 Widget Engine Contract

El motor de widgets usa un contrato uniforme para que los widgets futuros puedan agregarse sin romper Vue ni exponer errores PHP:

```json
{
  "success": true,
  "message": "",
  "state": "ready",
  "payload": {},
  "meta": {},
  "health": {
    "generated_at": "2026-06-15T12:00:00+00:00",
    "cache_until": null,
    "execution_ms": 4.12,
    "cache_hit": false,
    "stale": false,
    "warning": ""
  },
  "actions": [],
  "errors": []
}
```

`payload.html` existe para widgets simples y placeholders. Los widgets profesionales deben preferir datos estructurados y permitir que Vue renderice por `type`.

## Standard Widget States

Estados soportados:

- `loading`: Vue esta cargando el endpoint del widget.
- `ready`: el widget cargo contenido util.
- `empty`: el widget cargo correctamente pero no tiene datos.
- `error`: ocurrio un error controlado.
- `forbidden`: el usuario no tiene permiso para ver el widget.
- `disabled`: el manifest indica que el widget no esta activo.

## Manifest Validation

Cada widget debe declarar:

- `code`
- `title`
- `description`
- `category`
- `type`
- `icon`
- `color`
- `permission_key`
- `refresh_time`
- `dependencies`
- `exportable`
- `configurable`
- `settings_schema`
- `version`
- `status`

Si el manifest es invalido, `Service_Core_Workspace_WidgetRunner` no ejecuta el widget. Debe devolver JSON controlado, registrar `Log::warning()` y evitar paginas de excepcion.

## Settings Validator

`Service_Core_Workspace_WidgetSettingsValidator` valida settings contra `settings_schema`.

Reglas:

- aplica defaults definidos por el manifest
- ignora settings desconocidos
- rechaza llaves inseguras como `permission_key`, `user_id`, `group_id`, `file_path`, `storage_path`, `sql`, `class` o `callback`
- no permite elevar permisos desde settings
- devuelve un arreglo limpio para `load()`

## Widget Health

Cada respuesta incluye metadata de salud:

- `generated_at`
- `cache_until`
- `execution_ms`
- `cache_hit`
- `stale`
- `warning`

El inspector visual solo debe mostrarse a super admin o usuarios con `workspace.access[admin]`. No debe mostrar SQL, stack traces, rutas fisicas ni secretos.

## Widget Actions

Las acciones de widget usan este contrato:

```json
{
  "code": "open_module",
  "title": "Abrir modulo",
  "icon": "bi bi-box-arrow-up-right",
  "type": "route",
  "route": "admin/example",
  "permission_key": "example.access[view]",
  "requires_confirmation": false,
  "color": "primary"
}
```

Tipos soportados:

- `route`
- `refresh`
- `modal`
- `export`

Los widgets no ejecutan acciones destructivas. Para operaciones criticas solo navegan al modulo propietario, donde se validan permisos y confirmaciones.

## Cache Level Support

Niveles definidos para fases futuras:

- `none`
- `request`
- `user`
- `company`
- `global`
- `static`

La invalidacion avanzada queda diferida. No se deben cachear permisos, sesiones, secretos, documentos privados ni rutas fisicas.

## Error Handling Rules

`WidgetRunner` debe capturar:

- codigo de widget invalido
- widget no registrado
- permisos insuficientes
- manifest invalido
- settings invalidos
- excepciones del widget

Los endpoints de widget devuelven JSON controlado con HTTP 200 para que Vue no reciba HTML de excepcion. Nunca debe generarse una respuesta FuelPHP con status `0`.

## RC2 Personal/System Widgets

Sprint 2D agrega los primeros widgets reales sin consultar datos ERP sensibles:

- `welcome`: saludo por horario local, usuario, rol, fecha, estado del Workspace y siguiente accion sugerida.
- `favorites`: acciones favoritas del usuario desde `core_workspace_user_preferences.favorite_actions_json`; si no existen, muestra acciones recomendadas por permisos.
- `recent_activity`: timeline preparado. Solo lee `core_workspace_activity` si existe con columnas seguras; si no existe, devuelve empty state.
- `notifications`: estructura futura con `unread_count`, `critical_count` e `items`, sin crear tablas nuevas.
- `quick_actions`: acciones rapidas permitidas por permisos y agrupadas por categoria.

Aliases conservados:

- `quick_links`
- `notifications_placeholder`

Estos aliases permanecen para compatibilidad con layouts antiguos, pero el layout generico debe usar los codigos nuevos.
