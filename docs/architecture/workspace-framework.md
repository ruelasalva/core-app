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

