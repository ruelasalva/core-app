# Communications & Automation Platform

## 1. Overall Architecture

CORE-APP debe evolucionar el modulo actual de comunicaciones hacia una plataforma reutilizable llamada CAP: Communications & Automation Platform.

CAP no reemplaza el Event Bus, Dispatcher, Notification Manager, Email Queue, templates, layouts, recipient rules ni providers actuales. La arquitectura nueva debe envolverlos y extenderlos gradualmente.

Capas propuestas:

- Event Bus: recibe eventos normalizados desde modulos ERP.
- Channel Router: decide canales posibles sin acoplar modulos a proveedores.
- Recipient Resolver: conserva reglas actuales de destinatarios.
- Communications Manager: coordina notificaciones internas y colas salientes.
- Provider Factory: resuelve adaptadores futuros por tipo de proveedor.
- Message Store: normaliza mensajes entrantes y salientes.
- Conversation Manager: agrupa mensajes por entidad de negocio.
- Automation Manager: recibe eventos y delega reglas.
- Rule Engine: evalua condiciones, decisiones, delays y acciones futuras.
- Scheduler: ejecuta trabajos diferidos y periodicos.
- Statistics: entrega metricas operativas de comunicaciones.

## 2. Communication Flow

Flujo base actual y futuro:

1. Un modulo dispara un evento con `Helper_Core_Event::fire()`.
2. El Dispatcher localiza el evento activo.
3. Recipient Resolver resuelve usuarios, grupos, roles o emails.
4. Communications Manager procesa canales internos/email actuales.
5. En fases futuras, Channel Router calculara rutas por canal.
6. Provider Factory seleccionara adaptador concreto.
7. Message Store registrara mensaje saliente normalizado.
8. Conversation Manager vinculara el mensaje a una conversacion.
9. Statistics agregara metricas sin exponer secretos.

Los modulos ERP no deben llamar SMTP, WhatsApp, SMS o IMAP directamente.

## 3. Automation Flow

Flujo futuro:

1. Event Bus recibe evento ERP.
2. Automation Manager identifica reglas activas por evento.
3. Rule Engine valida condiciones.
4. Si aplica delay, Scheduler registra ejecucion futura.
5. Si aplica decision, Rule Engine elige rama segura.
6. Si aplica accion, Automation Manager genera una accion permitida.
7. Toda ejecucion queda auditada.

Conceptos soportados por contrato:

- Event
- Condition
- Delay
- Decision
- Action
- Retry
- Schedule

No se ejecutan automatizaciones en RC4 Sprint 1A.

## 4. Email Flow

El flujo de email existente se conserva:

1. Evento activo con `notify_email = 1`.
2. Recipient Resolver obtiene destinatarios.
3. Email Manager encola usando templates/layouts.
4. Email Queue procesa intentos.

CAP agregara en fases futuras:

- cuentas salientes por proveedor,
- provider abstraction,
- resultados normalizados,
- tracking de reintentos,
- relacion con conversaciones,
- estadisticas por canal y proveedor.

## 5. Incoming Message Flow

Flujo futuro de mensajes entrantes:

1. Scheduler ejecuta `sync_imap`.
2. ImapManager sincroniza cuentas autorizadas.
3. ProviderFactory normaliza metadatos de proveedor.
4. MessageStore registra mensaje entrante.
5. ConversationManager vincula por remitente, asunto, headers o entidad.
6. RuleEngine puede disparar automatizaciones futuras.

No se sincroniza IMAP en esta fase.

## 5.1. Outgoing Compose and Reply Foundation

RC4 Sprint 2E agrega base de composicion y respuesta desde el admin:

- `admin/communications/compose_message` encola correos nuevos desde cuentas asignadas.
- `admin/communications/reply_conversation` encola respuestas sobre conversaciones visibles.
- `Service_Core_Communications_OutgoingComposer` valida permiso `can_send` por cuenta.
- `Service_Core_Email_Manager` sigue siendo la unica entrada para crear cola de email.
- `Service_Core_Communications_MessageStore` registra mensajes salientes con `direction = outgoing` y `status = queued`.
- `Service_Core_Communications_ConversationManager` conserva agrupacion de conversaciones.

Reglas de seguridad:

- Solo admin.
- Sin adjuntos.
- Sin IMAP APPEND.
- Sin borrar, mover o sincronizar mensajes.
- No se exponen passwords, tokens, API keys, `file_path` ni `storage_path`.
- El HTML de salida se sanitiza antes de almacenarse.
- Los usuarios normales solo pueden enviar desde cuentas con asignacion `can_send = 1`.
- Grupo 100 puede operar todas las cuentas activas.

Limitaciones diferidas:

- Actualizar automaticamente `core_communication_messages.status` cuando la cola marque enviado/fallido.
- Registrar copia en carpeta Sent via IMAP APPEND.
- Adjuntos salientes.
- Responder desde paneles embebidos CRM/Helpdesk.
- Reglas avanzadas de firma, plantillas y tracking.

## 6. Conversation Flow

Cada comunicacion debera pertenecer eventualmente a una conversacion vinculada a:

- Customer
- Supplier
- Employee
- Lead
- Ticket
- Quote
- Order
- Invoice
- Payment
- Document
- Generic entity

Reglas:

- No exponer rutas fisicas.
- Validar permisos antes de mostrar conversaciones.
- Para portales, derivar propiedad desde `portal_link->party_id`.
- Sanitizar adjuntos y metadatos sensibles.

## 7. Provider Abstraction

ProviderFactory define el contrato comun para proveedores actuales y futuros:

- `send(array $message, array $options = array())`
- `validate_configuration(array $config)`
- `test_connection(array $config)`
- `supports_channel($channel_code)`
- `get_capabilities()`
- `get_health(array $config = array())`

Tipos futuros:

- SMTP
- PHP Mail
- IMAP
- SendGrid
- Mailgun
- SES
- Brevo
- WhatsApp
- SMS
- Push

Ningun modulo ERP debe depender de una clase concreta de proveedor.

### Provider Contracts

Los contratos base son:

- `Service_Core_Communications_ProviderContract`
- `Service_Core_Communications_EmailProviderContract`
- `Service_Core_Communications_ImapProviderContract`
- `Service_Core_Communications_SmsProviderContract`
- `Service_Core_Communications_PushProviderContract`
- `Service_Core_Communications_WhatsappProviderContract`

En este proyecto se prefieren clases contrato/base en lugar de interfaces puras para mantener compatibilidad con el estilo FuelPHP existente y permitir respuestas seguras por defecto.

### Provider Factory

`Service_Core_Communications_ProviderFactory` resuelve providers por:

- `provider_code`
- `transport`
- `channel_code`

La factory debe devolver siempre un provider compatible con `ProviderContract`.
Si el provider no existe, esta inactivo o aun no esta implementado, debe devolver `Service_Core_Communications_UnsupportedProvider`.

Transports soportados por contrato:

- `disabled`
- `php_mail`
- `smtp`
- `imap`
- `api`
- `sendgrid`
- `mailgun`
- `ses`
- `brevo`
- `whatsapp`
- `sms`
- `push`

Implementados actualmente:

- `disabled`
- `php_mail`
- `smtp`

Definidos pero diferidos:

- `imap`
- `api`
- `sendgrid`
- `mailgun`
- `ses`
- `brevo`
- `whatsapp`
- `sms`
- `push`

### Provider Capabilities

Cada provider debe declarar:

- `supports_html`
- `supports_attachments`
- `supports_inline_images`
- `supports_tracking`
- `supports_templates`
- `supports_queue`
- `supports_bulk`
- `supports_incoming`
- `supports_reply`
- `supports_webhooks`
- `max_attachment_size`
- `max_recipients`
- `supported_channels`

### Provider Health

Cada provider debe exponer:

- `status`
- `healthy`
- `simulation`
- `last_test`
- `last_error`
- `version`
- `transport`
- `provider_code`

### Standard Provider Response

Toda operacion de provider debe responder:

```php
array(
    'success' => true,
    'message' => '',
    'provider_code' => '',
    'transport' => '',
    'provider_message_id' => '',
    'capabilities' => array(),
    'health' => array(),
    'errors' => array(),
)
```

Los transports existentes pueden conservar campos legacy adicionales como `simulated` y `response_code`, porque la cola actual los utiliza.

### Unsupported Provider Behavior

Providers aun no implementados deben fallar de forma controlada:

- no fatal error,
- no stack trace,
- no envio real,
- no exposicion de secretos,
- respuesta estandar con `success = false`,
- `errors = array('unsupported_provider')`.

### Future Adapters

Los adaptadores futuros deben envolver SDKs o APIs externas sin que modulos ERP conozcan esas clases. El unico contrato publico debe ser CAP.

## 8. Scheduler

Scheduler debera administrar trabajos periodicos y diferidos:

- Retry failed emails
- Sync IMAP
- Archive conversations
- Execute delayed automations
- Clean temporary data

Debe ejecutarse via Oil tasks o cron controlado, con idempotencia y auditoria.

## 9. Rule Engine

Rule Engine debera evaluar reglas declarativas:

- condiciones por evento,
- filtros por modulo,
- estado de entidad,
- destinatario,
- canal,
- ventana horaria,
- delay,
- retry,
- accion permitida.

Restricciones:

- No elevar permisos.
- No ejecutar acciones destructivas sin aprobacion explicita.
- No aceptar SQL libre desde reglas.
- No exponer secretos en logs.

## 10. Audit

CAP debe registrar:

- evento recibido,
- canales calculados,
- destinatarios resueltos,
- proveedor usado,
- resultado,
- errores sanitizados,
- reintentos,
- usuario o sistema que disparo el evento.

No registrar:

- passwords,
- tokens,
- API keys,
- headers sensibles,
- SAT/PAC secrets,
- rutas fisicas,
- contenido confidencial completo cuando no sea necesario.

## 11. Future Integrations

Integraciones futuras:

- Helpdesk: tickets, respuestas, SLA.
- Ventas: cotizaciones, pedidos, entregas.
- Compras: OC, facturas de proveedor, contrarecibos.
- Fiscal/SAT/CFDI: alertas controladas.
- Cuentas por cobrar/pagar: avisos.
- Inventario: alertas operativas.
- Frontend: leads y conversiones.
- Portales: mensajes a clientes/proveedores.

Cada integracion debe agregarse en fase separada, con evento documentado y validacion de permisos.

## 12. Security

Reglas obligatorias:

- No exponer secretos de proveedores.
- No exponer `file_path`, `storage_path` ni rutas fisicas.
- No enviar datos a proveedores externos sin configuracion explicita.
- No usar `Access-Control-Allow-Origin: *` en rutas autenticadas.
- Respetar ORMAuth.
- Validar ownership en portales.
- Sanitizar errores antes de mostrarlos.
- Mantener configuraciones sensibles cifradas cuando se implementen cuentas reales.

## 13. Folder Structure

Estructura base actual/futura:

```text
fuel/app/classes/service/core/communications/
  dispatcher.php
  manager.php
  recipientresolver.php
  channelrouter.php
  conversationmanager.php
  imapmanager.php
  messagestore.php
  providercontract.php
  emailprovidercontract.php
  imapprovidercontract.php
  smsprovidercontract.php
  pushprovidercontract.php
  whatsappprovidercontract.php
  providerfactory.php
  unsupportedprovider.php
  statistics.php

fuel/app/classes/service/core/automation/
  manager.php
  ruleengine.php
  scheduler.php

docs/architecture/
  communications-automation-platform.md
```

## 14. Services

Servicios creados como foundation:

- `Service_Core_Automation_Manager`
- `Service_Core_Automation_RuleEngine`
- `Service_Core_Automation_Scheduler`
- `Service_Core_Communications_ConversationManager`
- `Service_Core_Communications_MessageStore`
- `Service_Core_Communications_Statistics`
- `Service_Core_Communications_ImapManager`
- `Service_Core_Communications_ProviderFactory`
- `Service_Core_Communications_ChannelRouter`
- `Service_Core_Communications_ProviderContract`
- `Service_Core_Communications_EmailProviderContract`
- `Service_Core_Communications_ImapProviderContract`
- `Service_Core_Communications_SmsProviderContract`
- `Service_Core_Communications_PushProviderContract`
- `Service_Core_Communications_WhatsappProviderContract`
- `Service_Core_Communications_UnsupportedProvider`

Servicios existentes preservados:

- `Service_Core_Communications_Dispatcher`
- `Service_Core_Communications_Manager`
- `Service_Core_Communications_RecipientResolver`

## 15. Future Models

Modelos foundation disponibles:

- `Model_Core_Communication_Account`
- `Model_Core_Communication_Conversation`
- `Model_Core_Communication_Message`
- `Model_Core_Communication_MessageAttachment`
- `Model_Core_Communication_MessageLink`

Modelos futuros propuestos, sin implementar:

- `Model_Core_Communication_Log`
- `Model_Core_Communication_Statistic`
- `Model_Core_Automation_Rule`
- `Model_Core_Automation_Run`
- `Model_Core_Automation_Schedule`

## 16. Future Migrations

Migraciones foundation disponibles:

- `078_create_core_communication_accounts`
  - crea `core_communication_accounts`
  - guarda configuracion IMAP/SMTP por cuenta
  - no almacena mensajes
  - no crea conversaciones
  - no ejecuta sincronizacion
- `079_create_core_communication_message_store`
  - crea conversaciones, mensajes, metadata de adjuntos y vinculos logicos
  - no sincroniza IMAP
  - no almacena archivos fisicos
  - no crea automatizaciones

Migraciones futuras sugeridas:

- `core_communication_logs`
- `core_communication_statistics`
- `core_automation_rules`
- `core_automation_runs`
- `core_automation_schedules`

Cada migracion debe ser reversible y no debe duplicar tablas existentes.

### Inbound Accounts / IMAP Configuration

`core_communication_accounts` concentra la configuracion base de cuentas de correo entrante y saliente por area operativa. La tabla permite preparar cuentas para soporte, ventas, compras, facturacion, sistema u otros usos sin conectar todavia la lectura de buzones.

Campos principales:

- identidad: `code`, `name`, `email_address`, `account_type`
- proveedores: `provider_code`, `smtp_provider_code`, `imap_provider_code`
- IMAP: `imap_host`, `imap_port`, `imap_encryption`, `imap_username`, `imap_password_encrypted`
- carpetas: `imap_folder_inbox`, `imap_folder_sent`, `imap_folder_drafts`, `imap_folder_trash`
- flags futuros: `sync_inbox`, `sync_sent`, `sync_drafts`, `sync_trash`, `append_sent`, `sync_enabled`
- estado: `last_sync_at`, `last_sync_status`, `last_sync_error`, `active`

### Folder Strategy

Las carpetas se guardan como texto configurable porque cada proveedor usa nombres distintos:

- Inbox default: `INBOX`
- Sent default: `Sent`
- Drafts default: `Drafts`
- Trash default: `Trash`

La sincronizacion futura debe respetar los flags `sync_*` y nunca leer carpetas no habilitadas.

### Append Sent Strategy

`append_sent` queda como bandera foundation. En fases futuras, cuando el ERP envie correo desde una cuenta con IMAP habilitado, el sistema podra intentar anexar una copia en la carpeta enviada del proveedor. Esta fase no realiza append ni envio.

### Controlled IMAP Sync Worker

RC4 Sprint 1E agrega un worker controlado para sincronizar mensajes desde cuentas IMAP configuradas:

```bash
php oil refine syncimapaccounts --limit=20 --account=optional_code
```

Reglas operativas:

1. Solo procesa cuentas `active = 1` y `sync_enabled = 1`.
2. Si `--account` se proporciona, evalua solo esa cuenta y la omite de forma controlada si esta inactiva o no tiene sincronizacion habilitada.
3. Lee solo carpetas habilitadas por flags: `sync_inbox`, `sync_sent`, `sync_drafts`, `sync_trash`.
4. El limite default es 20 mensajes por carpeta por ejecucion.
5. Si la extension PHP IMAP no existe, devuelve error controlado sin fatal.
6. Los mensajes se almacenan mediante `Service_Core_Communications_MessageStore`.
7. Las conversaciones se crean o actualizan mediante `Service_Core_Communications_ConversationManager`.
8. No se ejecutan automatizaciones.
9. No se crean tickets, ordenes, cotizaciones ni entidades ERP.
10. No se descargan ni almacenan binarios de adjuntos.

### Sync Flow

1. Scheduler selecciona cuentas activas con `sync_enabled = 1`.
2. IMAP Manager valida configuracion y disponibilidad de extension.
3. IMAP Manager obtiene mensajes por carpeta habilitada en modo lectura.
4. Message Store deduplica por `external_message_id + account_id`.
5. Si no hay identificador externo, Message Store usa deduplicacion secundaria por hash/contenido y metadatos.
6. Conversation Manager agrupa mensajes por conversacion.
7. El worker actual no publica eventos ERP ni dispara automatizaciones.

### IMAP Security Rules

- Nunca mostrar `imap_password_encrypted`.
- Dejar password vacio en UI conserva el secreto existente.
- Cualquier password nuevo debe cifrarse antes de persistir.
- No registrar password, tokens ni secretos en logs.
- `test_connection()` debe devolver error controlado si la extension PHP IMAP no existe.
- `syncimapaccounts` debe devolver error controlado si la extension PHP IMAP no existe.
- Leer buzones solo desde cuentas activas con `sync_enabled = 1`.
- Respetar siempre flags de carpeta antes de leer.
- No exponer rutas fisicas ni `file_path`/`storage_path`.
- Los resultados de prueba deben mostrar estado operacional, no credenciales.
- Sanitizar headers y cuerpos antes de persistir.
- No guardar binarios de adjuntos en esta fase.

### Message Store

El Message Store es la capa de persistencia normalizada para mensajes entrantes y salientes. A partir de RC4 Sprint 1E puede recibir mensajes desde el worker IMAP controlado, sin automatizar procesos ERP.

Tablas:

- `core_communication_messages`
- `core_communication_message_attachments`
- `core_communication_message_links`

Reglas:

- `body_html_sanitized` almacena HTML filtrado, no HTML crudo.
- `raw_headers_json` debe eliminar headers sensibles como authorization, cookies, tokens y API keys.
- `to_json`, `cc_json` y `bcc_json` deben contener datos normalizados sin secretos.
- `content_hash` permite trazabilidad y deduplicacion complementaria.
- `external_message_id + account_id` evita duplicados cuando el proveedor entrega identificador estable.

### Conversation Store

`core_communication_conversations` agrupa mensajes por hilo, entidad o asunto normalizado. No reemplaza Helpdesk ni CRM; solo prepara una capa comun para futuras conversaciones.

Campos clave:

- `channel_code`
- `subject`
- `normalized_subject`
- `direction`
- `status`
- `owner_user_id`
- `assigned_user_id`
- `assigned_group_id`
- `related_entity_type`
- `related_entity_id`
- `related_party_id`
- `message_count`
- `unread_count`

### Attachment Metadata

Los adjuntos se registran solo como metadata. No se guarda ruta fisica.

Permitido:

- `filename`
- `mime_type`
- `size_bytes`
- `storage_ref`
- `content_hash`
- `disposition`

Prohibido:

- `file_path`
- `storage_path`
- rutas absolutas
- rutas bajo DOCROOT/APPPATH

`storage_ref` debe ser una referencia logica futura, no una ruta de filesystem.

### Entity Linking

`core_communication_message_links` permite vincular mensajes y conversaciones con entidades ERP sin acoplar Message Store a modulos de negocio.

Ejemplos futuros:

- ticket
- quote
- order
- invoice
- payment
- document
- customer
- supplier
- employee
- lead

### Dedupe Strategy

1. Si existe `external_message_id` y `account_id`, se busca mensaje activo existente.
2. Si existe, `store_message()` devuelve el mensaje existente con `duplicate = true`.
3. Si no existe identificador externo, se usa fallback por `content_hash`, `account_id`, fechas, remitente y asunto.
4. Si no existe duplicado, se crea mensaje y se actualizan contadores de conversacion.
5. `content_hash` complementa la deduplicacion, pero no reemplaza el identificador del proveedor cuando existe.

### Message Body Security

- Remover `<script>`.
- Remover atributos `on*`.
- Remover `javascript:` URLs.
- Remover referencias PHP.
- Redactar patrones visibles de password, token, secret y API key.
- No mostrar body completo en admin hasta que exista visor con permisos y sanitizacion probada.

### Mailbox Ownership Model

`core_communication_accounts` representa buzones operativos. Cada cuenta puede ser:

- `system`: buzón del sistema o transaccional.
- `personal`: buzón asignado principalmente a un usuario.
- `shared`: buzón compartido por varios usuarios o grupos.
- `department`: buzón de un área operativa.

Campos de propiedad:

- `owner_user_id`: propietario administrativo opcional.
- `owner_group_id`: grupo propietario opcional.
- `mailbox_scope`: alcance operativo del buzón.

Las asignaciones se guardan en `core_communication_account_assignments`.

Campos principales:

- `account_id`
- `assignment_type`: `user`, `group`, `role`
- `assignment_value`: id de usuario, id de grupo o código de rol
- `access_level`: `owner`, `delegate`, `viewer`
- `can_send`
- `can_receive`
- `can_sync`
- `can_manage`
- `default_sender`
- `active`

Reglas:

- Una cuenta puede tener múltiples usuarios, grupos o roles asignados.
- Un usuario puede tener múltiples cuentas.
- `default_sender` se valida desde servicio para evitar múltiples remitentes directos por usuario cuando sea posible.
- Grupo 100 puede administrar y ver todas las cuentas.
- Usuarios normales solo deben ver cuentas asignadas a su usuario, grupo o rol.
- Ninguna respuesta debe exponer `imap_password_encrypted`, contraseñas, tokens, API keys, `file_path` o `storage_path`.

### Personal vs Shared vs System Accounts

Cuenta personal:

- Uso futuro: vendedor, ejecutivo de soporte o usuario administrativo.
- Debe permitir distinguir remitente predeterminado por usuario.

Cuenta compartida:

- Uso futuro: soporte, ventas, compras, facturación.
- Puede asignarse a grupo o rol.
- Permite bandejas de equipo sin mover usuarios de grupo principal.

Cuenta de sistema:

- Uso actual/futuro: mensajes transaccionales, cola de correo, notificaciones automáticas.
- No debe usarse para lectura operativa de conversaciones por usuarios finales salvo asignación explícita.

### Seller and Support Use Case

El objetivo del modelo es permitir que un vendedor o agente de soporte trabaje desde el ERP sin salir a un cliente de correo externo.

Flujo futuro:

1. El administrador crea o configura la cuenta.
2. El administrador asigna la cuenta al usuario, grupo o rol.
3. El usuario ve la cuenta en `Mis cuentas`.
4. El Centro de Conversaciones filtra mensajes por cuentas asignadas.
5. En fases posteriores, CRM/Helpdesk podrá iniciar respuesta desde una cuenta autorizada.
6. El proveedor/canal real queda encapsulado por Provider Factory.

### Future CRM/Helpdesk Reply Flow

Futuras respuestas desde CRM/Helpdesk deben:

- Resolver cuentas autorizadas con `Service_Core_Communications_MailboxAccess`.
- Validar `can_send_from_account()` antes de preparar envío.
- Usar `default_sender_for_user()` solo como sugerencia, no como bypass.
- Registrar mensaje saliente en Message Store.
- Enviar por cola/proveedor sin exponer credenciales.
- Vincular conversación a ticket, lead, cliente, cotización u otra entidad.

### Assigned Mailbox Inbox

`admin/communications` incluye una vista read-only de bandeja asignada para usuarios administrativos.

Endpoints:

- `GET admin/communications/my_mailbox`
- `GET admin/communications/my_mailbox_detail/{conversation_id}`

Reglas:

- Requiere sesión admin y `communications.access[view]`.
- Grupo 100 puede ver todas las cuentas y conversaciones.
- Usuarios normales solo ven conversaciones asociadas a cuentas activas asignadas por usuario, grupo o rol.
- Si el usuario no tiene cuentas asignadas, la UI muestra un estado vacío.
- El filtro de cuenta permite `Todas mis cuentas` o una cuenta asignada específica.
- El detalle es solo lectura.
- No se permite compose, reply, delete, move ni sync desde esta vista.
- No se devuelven contraseñas, API keys, tokens, `file_path` ni `storage_path`.
- Los adjuntos se muestran solo como metadata.

Preparación futura:

- CRM customer timeline: podrá mostrar conversaciones filtradas por cliente/lead y cuenta autorizada.
- Helpdesk ticket thread: podrá mostrar mensajes vinculados a ticket sin implementar respuesta directa todavía.
- Sales quote conversation: podrá mostrar hilos relacionados a cotizaciones/pedidos con permisos del usuario.
- Cualquier embebido futuro debe consultar `MailboxAccess` antes de mostrar o enviar mensajes.

### Embedded Mailbox Panels

RC4 Sprint 2D agrega paneles embebidos read-only para consultar conversaciones relacionadas sin salir del modulo operativo.

Endpoints:

- `GET admin/communications/entity_conversations`
- `GET admin/communications/entity_conversation_detail/{conversation_id}`

Tipos permitidos en esta fase:

- `customer`
- `party`
- `helpdesk_ticket`

Tipos documentados para fases futuras:

- `sales_quote`
- `sales_order`
- `purchase_order`
- `purchase_invoice`
- `document`

Reglas de visibilidad:

- Los endpoints son solo admin y requieren `communications.access[view]`.
- Grupo 100 puede ver todas las conversaciones relacionadas.
- Usuarios normales solo ven conversaciones con mensajes de cuentas asignadas por usuario, grupo o rol.
- El panel no permite compose, reply, delete, move, sync ni cambios de estado.
- El detalle devuelve cuerpo sanitizado, texto plano y metadata de adjuntos.
- No devuelve contrasenas, API keys, tokens, `file_path`, `storage_path` ni rutas fisicas.

Uso actual:

- CRM: panel compacto en modales de oportunidad y actividad cuando existe `party_id`.
- Helpdesk: panel compacto en modal de seguimiento de ticket usando `helpdesk_ticket` y `party_id` si existe.

Flujo futuro de respuesta/compose:

- Debe validar `can_send_from_account()` antes de preparar respuesta.
- Debe resolver cuenta sugerida con `default_sender_for_user()` sin usarla como bypass.
- Debe registrar salida en Message Store y enviar por cola.
- Debe mantener el mismo filtro de visibilidad por `MailboxAccess`.

## 17. Implementation Roadmap

### Fase 1: Foundation

- Crear documento de arquitectura.
- Crear esqueletos de servicios.
- Preservar Dispatcher/Manager/Resolver actuales.
- Sin cambios de negocio.

### Fase 2: Provider Contracts

- Crear interfaces/adaptadores concretos.
- Normalizar resultados.
- Agregar pruebas de conexion sin exponer secretos.
- Crear foundation de cuentas IMAP sin sincronizacion real.

### Fase 3: Message Store y Conversations

- Crear migraciones foundation.
- Persistir mensajes salientes/entrantes desde servicios internos.
- Vincular conversaciones a entidades sin acoplar modulos ERP.
- Mostrar resumen read-only en Centro de Comunicaciones.

### Fase 4: Incoming Email

- Implementar IMAP seguro.
- Deduplicar mensajes.
- Crear conversaciones desde respuestas.

### Fase 5: Automation Rules

- Crear reglas declarativas.
- Implementar preview.
- Agregar scheduler y retries.

### Fase 6: Integraciones ERP

- Conectar modulos uno por uno.
- Documentar eventos.
- Validar permisos y ownership por modulo.

### Fase 7: Estadisticas y Operacion

- Dashboards operativos.
- Monitoreo de colas.
- Trazabilidad por proveedor/canal.
