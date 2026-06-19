# ERP Event Bus

## 1. Propósito

El Event Bus estandariza cómo los módulos de CORE-APP avisan que ocurrió un hecho de negocio. Su objetivo es desacoplar módulos operativos de comunicaciones, notificaciones internas, workspace, auditoría y automatizaciones futuras.

Los módulos deben emitir eventos usando un único contrato:

```php
Helper_Core_Event::fire($event_code, $payload, $recipients = array(), $meta = array());
```

Ningún módulo debe conocer SMTP, colas, WhatsApp, Mailchimp, widgets de Workspace o detalles internos de notificación.

## 2. Convención de nombres

Formato:

```text
module.entity.action
```

Ejemplos:

- `contact.web.message`
- `helpdesk.ticket.created`
- `helpdesk.ticket.replied`
- `sales.quote.created`
- `sales.quote.status_changed`
- `purchases.invoice.received`
- `supplier.document.uploaded`
- `customer.portal.login`
- `cfdi.downloaded`
- `billing.invoice.created`

## 3. Estructura estándar de payload

El payload debe ser mínimo, seguro y orientado a identificación.

Campos permitidos:

- `entity_type`
- `entity_id`
- `folio`
- `status`
- `previous_status`
- `new_status`
- `party_id`
- `user_id`
- `portal_code`
- `module`
- `public_url`
- `admin_url`
- `source`

También pueden incluirse textos cortos de contexto, por ejemplo `subject`, `name`, `email`, `phone` o `message_summary`, siempre que no contengan secretos ni documentos completos.

## 4. Estructura estándar de meta

Campos recomendados:

- `source_module`
- `source_action`
- `triggered_by_user_id`
- `company_id`
- `branch_id`
- `ip`, solo si ya se registra de forma segura
- `user_agent`, solo si es necesario

Meta no debe usarse para transportar documentos, secretos ni trazas técnicas.

## 5. Reglas de seguridad

El Event Bus es best-effort para eventos operativos normales. Si falla el dispatcher:

- se registra en log,
- no se rompe la operación principal,
- no se revierte contacto, ticket, cotización ni flujo operativo.

Los eventos críticos futuros deberán declararse explícitamente como críticos antes de poder bloquear una transacción.

## 6. Datos prohibidos

Nunca enviar en payload ni meta:

- passwords
- salts
- tokens
- API keys
- credenciales SMTP
- credenciales SAT/PAC
- certificados SAT
- XML completo
- contenido completo de documentos fiscales o privados
- `file_path`
- `storage_path`
- rutas físicas
- SQL crudo
- stack traces
- márgenes, costos internos o información sensible no necesaria

## 7. Relación con Communications Center

`Helper_Core_Event::fire()` delega en `Service_Core_Communications_Dispatcher`. El dispatcher resuelve el evento configurado, destinatarios y canales. Communications decide si genera notificación interna, email o canales futuros.

## 8. Relación con notificaciones internas

Los eventos configurados en `core_notification_events` pueden crear notificaciones internas según `notify_internal`. Los módulos no deben llamar a `Helper_Core_Notification` para nuevos flujos salvo compatibilidad existente.

## 9. Relación futura con Workspace

Workspace podrá escuchar eventos para actualizar widgets, actividad reciente o tareas pendientes. Los módulos no deben invocar widgets directamente.

## 10. Relación futura con auditoría/timeline

Audit y timeline podrán consumir eventos para registrar actividad de negocio. Los payloads deben ser seguros para almacenamiento y visualización operativa.

## 11. Cómo agregar un evento

1. Definir código con formato `module.entity.action`.
2. Agregarlo al catálogo `fuel/app/config/core/events.php`.
3. Sembrarlo en `seedcommunications` si debe aparecer en Communications Center.
4. Emitirlo con `Helper_Core_Event::fire()`.
5. Agregar comentario `CORE EVENT` en el punto de emisión.
6. Validar que el payload no contenga datos prohibidos.
7. Probar que una falla del dispatcher no rompe el flujo principal.

## 12. Ejemplo en controlador o servicio

```php
// CORE EVENT:
// Event: sales.quote.created
// Purpose: notify communications/workspace/audit subsystems after quote creation.
// Payload safety: no secrets, no physical paths, no XML/certificates/tokens.
Helper_Core_Event::fire('sales.quote.created', [
    'entity_type' => 'sales_quote',
    'entity_id' => (int) $quote->id,
    'folio' => (string) $quote->folio,
    'party_id' => (int) $quote->party_id,
    'total' => (float) $quote->total,
    'source' => 'frontend_cart',
], [], [
    'source_module' => 'sales',
    'source_action' => 'frontend_cart_checkout_quote',
    'triggered_by_user_id' => (int) $user_id,
]);
```

## 13. Formato obligatorio de comentario

Antes de cada emisión:

```php
// CORE EVENT:
// Event: module.entity.action
// Purpose: explain why this event is emitted.
// Payload safety: no secrets, no physical paths, no XML/certificates/tokens.
```

## 14. Catálogo inicial por módulo

Contacto:

- `contact.web.message`: mensaje enviado desde formulario público.

Helpdesk:

- `helpdesk.ticket.created`: ticket creado desde admin o portal.
- `helpdesk.ticket.replied`: respuesta en ticket, diferido para fase posterior.

Ventas:

- `sales.quote.created`: cotización creada desde carrito frontend.
- `sales.quote.status_changed`: cambio de estado, diferido.

Compras:

- `purchases.invoice.received`: diferido.

Portales:

- `customer.portal.login`: diferido.
- `supplier.document.uploaded`: diferido.

SAT/CFDI:

- `cfdi.downloaded`: diferido.

Facturación:

- `billing.invoice.created`: diferido.

## 15. Checklist de pruebas

- `php -l` en archivos modificados.
- El formulario de contacto sigue enviando.
- Crear ticket Helpdesk desde admin sigue funcionando.
- Crear ticket Helpdesk desde portal sigue funcionando.
- Cotización frontend desde carrito sigue funcionando si se toca.
- `admin/communications` sigue cargando.
- `php oil refine seedcommunications` es idempotente.
- `php oil refine testemail --provider=disabled_default --to=prueba@example.com` sigue simulando envío.
- Revisar que payload/meta no incluyan `file_path`, `storage_path`, tokens, secretos, XML, certificados ni stack traces.
