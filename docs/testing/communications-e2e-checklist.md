# Communications Compose/Reply E2E Checklist

## Proposito

Validar manualmente el flujo de redaccion y respuesta del Centro de Comunicaciones sin enviar correo real. El objetivo es comprobar permisos, CSRF, cola, Message Store, UI y seguridad usando el proveedor deshabilitado o simulacion.

## Alcance

- Admin: `admin/communications`.
- Endpoints:
  - `POST admin/communications/compose_message`
  - `POST admin/communications/reply_conversation`
- Cola: `core_email_queue`.
- Message Store:
  - `core_communication_conversations`
  - `core_communication_messages`
  - `core_communication_message_attachments`
- Asignaciones:
  - `core_communication_accounts`
  - `core_communication_account_assignments`

No probar adjuntos, IMAP APPEND, integraciones ERP, envio real por SMTP ni automatizaciones.

## Prerrequisitos

1. Migraciones de comunicaciones aplicadas.
2. Seed de comunicaciones aplicado:
   ```bash
   FUEL_ENV=development php oil refine seedcommunications
   ```
3. Proveedor `disabled_default` activo o un proveedor en modo simulacion.
4. Cuenta de correo activa en `core_communication_accounts`.
5. Usuario admin de prueba con permiso:
   - `communications.access[view]`
6. Para pruebas de envio, el usuario debe tener una asignacion de buzon con:
   - `can_send = 1`
   - `active = 1`
7. Navegador con sesion admin valida.

## Diagnostico inicial

Ejecutar:

```bash
FUEL_ENV=development php oil refine diagnosticscommunications
```

Resultado esperado:

- `providers_count` mayor o igual a 1.
- `accounts_count` mayor o igual a 1.
- `assignments_count` coherente con las pruebas.
- `queues_pending`, `queues_sent`, `queues_simulated`, `queues_failed` visibles.
- `conversations_count` visible.
- Ultimos intentos sin passwords, tokens, API keys ni rutas fisicas.

## Asignar buzon a usuario

Desde UI:

1. Entrar a `admin/communications`.
2. Abrir pestana `Cuentas de correo`.
3. Seleccionar la cuenta que se usara para pruebas.
4. En asignaciones, seleccionar:
   - Tipo: `Usuario` o `Grupo`.
   - Destinatario: usuario o grupo de prueba.
   - Nivel: `delegate` u `owner`.
   - Activar `Enviar`.
   - Activar `Recibir` si se validara bandeja.
   - Dejar `Sincronizar` y `Administrar` segun el caso.
   - Activar `Activo`.
5. Guardar asignacion.

Validacion esperada:

- La asignacion aparece en la tabla.
- En `Mis cuentas`, el usuario ve la cuenta asignada.
- `can_send` aparece como `Si` para el usuario asignado.
- No se muestra ninguna contrasena ni secreto.

## Configurar can_send=1

Opcion UI recomendada:

1. En la asignacion de buzon, marcar `Enviar`.
2. Guardar.
3. Recargar `admin/communications`.
4. Confirmar que el boton `Nuevo correo` esta habilitado.

Opcion SQL de solo verificacion:

```sql
SELECT id, account_id, assignment_type, assignment_value, can_send, active
FROM core_communication_account_assignments
ORDER BY id DESC
LIMIT 10;
```

No modificar datos por SQL salvo instruccion explicita.

## Crear o seleccionar conversacion

Para probar respuesta se necesita una conversacion con al menos un mensaje entrante visible para la cuenta asignada.

Opciones:

1. Usar una conversacion ya sincronizada por IMAP.
2. Usar una conversacion existente en `Mi bandeja`.
3. Si no hay datos, omitir prueba de respuesta y registrar bloqueo: `sin conversacion entrante disponible`.

Consulta de apoyo:

```sql
SELECT c.id, c.subject, c.status, c.last_message_at, m.account_id, m.direction, m.from_email
FROM core_communication_conversations c
JOIN core_communication_messages m ON m.conversation_id = c.id
WHERE c.active = 1
  AND m.active = 1
  AND m.direction = 'incoming'
ORDER BY c.last_message_at DESC, c.id DESC
LIMIT 10;
```

## Probar compose

1. Entrar a `admin/communications`.
2. Abrir `Nuevo correo`.
3. Seleccionar cuenta asignada.
4. Capturar:
   - Para: `prueba@example.com`
   - Asunto: `Prueba compose comunicaciones`
   - Mensaje: `Mensaje de prueba sin adjuntos.`
5. Enviar.

Resultado UI esperado:

- Modal cierra.
- Mensaje amigable: correo encolado correctamente.
- No queda loader infinito.
- La conversacion aparece o la lista se actualiza.

Resultado DB esperado:

```sql
SELECT id, event_code, provider_code, to_email, subject, status, simulation_mode, payload_json
FROM core_email_queue
WHERE event_code = 'communications.compose'
ORDER BY id DESC
LIMIT 5;
```

- Hay fila nueva.
- `status = pending` antes de procesar.
- `provider_code` corresponde a la cuenta/proveedor.
- `payload_json` contiene `communication_account_id` y `from_email`.
- No contiene passwords, tokens, API keys, `file_path` ni `storage_path`.

Message Store esperado:

```sql
SELECT id, conversation_id, account_id, direction, subject, status, queue_id, body_html_sanitized, snippet
FROM core_communication_messages
WHERE direction = 'outgoing'
ORDER BY id DESC
LIMIT 5;
```

- Hay mensaje saliente nuevo.
- `status = queued`.
- `queue_id` corresponde a la fila de `core_email_queue`.
- `body_html_sanitized` no contiene scripts ni manejadores `on...`.

## Probar reply

1. Entrar a `admin/communications`.
2. Abrir `Mi bandeja` o `Conversaciones`.
3. Seleccionar conversacion visible de la cuenta asignada.
4. Abrir `Responder`.
5. Capturar mensaje: `Respuesta de prueba sin adjuntos.`
6. Enviar.

Resultado UI esperado:

- Modal cierra.
- Mensaje amigable: respuesta encolada correctamente.
- La conversacion se mantiene como la misma conversacion.
- No queda loader infinito.

Resultado DB esperado:

```sql
SELECT id, event_code, provider_code, to_email, subject, status, payload_json
FROM core_email_queue
WHERE event_code = 'communications.reply'
ORDER BY id DESC
LIMIT 5;
```

- Hay fila nueva.
- `payload_json` incluye `conversation_id`.
- No contiene secretos ni rutas fisicas.

Message Store esperado:

```sql
SELECT id, conversation_id, account_id, direction, subject, status, queue_id, in_reply_to
FROM core_communication_messages
WHERE direction = 'outgoing'
ORDER BY id DESC
LIMIT 5;
```

- `conversation_id` es el mismo de la conversacion original.
- No se crea conversacion duplicada.
- `status = queued`.

## Procesar cola con proveedor deshabilitado

Ejecutar:

```bash
FUEL_ENV=development php oil refine processemailqueue --limit=10 --provider=disabled_default
```

Resultado esperado:

- `Fallidos: 0`.
- `Simulados` aumenta si habia pendientes con `disabled_default`.
- No se envia correo real.

Validar intentos:

```sql
SELECT id, queue_id, provider_code, transport, status, response_code, attempted_at
FROM core_email_queue_attempts
ORDER BY id DESC
LIMIT 5;
```

## Pruebas de permisos

### Usuario sin can_send

1. Asignar cuenta al usuario con `can_send = 0` o retirar asignacion.
2. Iniciar sesion con ese usuario.
3. Abrir `admin/communications`.
4. Intentar abrir `Nuevo correo`.

Esperado:

- El boton queda deshabilitado si no hay cuentas enviables.
- Si se fuerza POST, endpoint responde JSON 403 con error de permiso de envio.

### Usuario con can_send

1. Asignar cuenta con `can_send = 1`.
2. Iniciar sesion.
3. Enviar compose.

Esperado:

- POST llega al endpoint.
- Se crea cola.
- Se crea mensaje saliente.

### Responder conversacion no asignada

1. Iniciar sesion con usuario sin acceso a la cuenta de la conversacion.
2. Intentar abrir o responder la conversacion por URL/POST forzado.

Esperado:

- JSON 403.
- Error controlado.
- No se crea cola.
- No se crea mensaje saliente.

## Pruebas CSRF

### Token valido

Usar la UI normal. `CoreApiClient` debe enviar:

- Campo JSON `fuel_csrf_token`.
- Header `X-CSRF-Token`.

Esperado:

- El endpoint procesa despues de validar sesion, permiso y CSRF.

### Token faltante o invalido

Forzar POST sin token o con token alterado.

Esperado:

- JSON 403.
- `errors` contiene `csrf_invalid`.
- No se crea cola.
- No se crea mensaje.

## Comportamiento UI esperado

- `admin/communications` carga sin quedarse en `Cargando comunicaciones...`.
- `Nuevo correo` muestra solo cuentas con `can_send = 1`.
- `Responder` selecciona la cuenta de la conversacion si el usuario puede enviar desde ella.
- Los errores aparecen como mensajes amigables.
- No se muestra HTML crudo de errores.
- No hay stack traces en pantalla.
- No hay inputs de adjuntos.

## Controles de seguridad

Revisar fuente de pagina y respuestas JSON:

- No aparecen passwords.
- No aparecen API keys.
- No aparecen tokens.
- No aparecen secretos SMTP/IMAP.
- No aparece `file_path`.
- No aparece `storage_path`.
- No aparecen rutas fisicas.
- No se aceptan adjuntos.
- No se muestran stack traces.

Busquedas utiles:

```bash
rg "password|api_key|token|secret|file_path|storage_path" fuel/app/views/admin/communications/index.php
rg "attachments_not_supported|csrf_invalid|can_send_from_account" fuel/app/classes/service/core/communications fuel/app/classes/controller/admin/communications.php
```

## Criterios de aprobacion

- Compose crea exactamente una fila en `core_email_queue`.
- Compose crea un mensaje `outgoing` con `status = queued`.
- Reply crea exactamente una fila nueva en `core_email_queue`.
- Reply crea un mensaje `outgoing` en la misma conversacion.
- Provider deshabilitado simula sin envio real.
- Usuario sin `can_send` no puede enviar.
- Usuario sin acceso a conversacion no puede responder.
- Token CSRF invalido devuelve error controlado.
- No hay secretos ni rutas fisicas en UI/JSON.
