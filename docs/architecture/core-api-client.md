# Core API Client

`public/assets/js/core-api-client.js` define `window.CoreApiClient`, un helper compartido para llamadas AJAX de Vue/Admin.

## Purpose

El cliente existe para evitar errores repetidos en módulos Vue:

- `response.json()` ejecutado contra HTML de login, 404 o error.
- loaders que quedan activos cuando una promesa falla.
- manejo inconsistente de 401, 403, 404 y 500.
- duplicación de lógica de CSRF y parsing en cada vista.

RC4 lo integra solo en Comunicaciones. Otros módulos deben migrarse por fases.

## Response Contract

Todos los métodos devuelven:

```js
{
  ok: true,
  status: 200,
  payload: {},
  message: '',
  errors: [],
  code: ''
}
```

Campos:

- `ok`: resultado HTTP y lógico normalizado.
- `status`: código HTTP.
- `payload`: JSON devuelto por el servidor o `{}`.
- `message`: mensaje seguro para UI.
- `errors`: lista normalizada.
- `code`: error principal normalizado.

## Error Handling

Errores estándar:

- `auth_required`: 401.
- `permission_denied`: 403.
- `endpoint_not_found`: 404.
- `server_error`: 500 o mayor.
- `non_json_response`: respuesta HTML/texto donde se esperaba JSON.
- `network_error`: fallo de conexión o excepción del navegador.

Mensajes UI estándar:

- `auth_required`: "Tu sesión expiró. Vuelve a iniciar sesión."
- `permission_denied`: "No tienes permiso para realizar esta acción."
- `endpoint_not_found`: "Endpoint no encontrado."
- `non_json_response`: "El servidor devolvió una respuesta inválida."
- `server_error`: "Error del servidor."

Nunca debe mostrarse HTML crudo ni stack traces en la UI.

## CSRF Behavior

El cliente busca token CSRF en este orden:

1. `<meta name="csrf-token">` o `<meta name="fuel-csrf-token">`.
2. `window.coreAppCsrfToken`.
3. `window.csrfToken`.
4. `window.fuel_csrf_token()`.
5. Input hidden con el nombre de `window.coreAppCsrfKey`.

Para `post()` agrega el token al payload usando `window.coreAppCsrfKey` o `fuel_csrf_token`.

Si el servidor devuelve `csrf_token`, el helper actualiza `window.coreAppCsrfToken`.

## Methods

- `request(url, options)`
- `get(url, options)`
- `post(url, data, options)`
- `parseResponse(response)`
- `normalizeError(error)`
- `csrfToken()`
- `withCsrf(data)`
- `isAuthError(responseOrPayload)`
- `isJsonResponse(response)`
- `redirectToLoginIfNeeded(responseOrPayload, loginUrl)`
- `safeMessage(payload, fallback)`

## Migration Guide

Recommended Vue pattern:

```js
this.loading = true;
window.CoreApiClient.get(url)
  .then(result => {
    if (!result.ok) {
      this.showStatus(false, result.message, result.errors);
      return;
    }
    this.items = result.payload.data.items || [];
  })
  .catch(error => {
    const result = window.CoreApiClient.normalizeError(error);
    this.showStatus(false, result.message, result.errors);
  })
  .finally(() => {
    this.loading = false;
  });
```

Migration rules:

- Do not call `response.json()` directly.
- Do not parse HTML responses as JSON.
- Always use `finally()` for loader flags.
- Preserve existing endpoint payloads.
- Migrate one module at a time.
- Keep backend permission checks unchanged.

## Current Adoption

Implemented first in:

- `fuel/app/views/admin/communications/index.php`

Deferred modules:

- Workspace
- Sales
- Purchases
- Inventory
- SAT/CFDI
- Fiscal
- Helpdesk
- Portals
