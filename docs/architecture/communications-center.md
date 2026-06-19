# Centro de Comunicaciones

## Proposito

El Centro de Comunicaciones centraliza notificaciones internas, correos transaccionales y futuros canales de CORE-APP. La entrada funcional debe ser `Helper_Core_Event::fire()`. Los modulos ERP no deben conocer SMTP, proveedores, colas ni transportes.

## Arquitectura

```text
Modulo ERP
  -> Helper_Core_Event::fire()
  -> Service_Core_Communications_Dispatcher
  -> Service_Core_Communications_Manager
      -> Service_Core_Notifications_Manager
      -> Service_Core_Email_Manager
          -> TemplateRenderer
          -> LayoutRenderer
          -> core_email_queue
  -> Service_Core_Email_QueueProcessor
      -> Transport
      -> core_email_queue_attempts
```

## Componentes

- Dispatcher: resuelve el evento y delega a canales.
- Communications Manager: coordina notificaciones internas y email.
- Email Manager: renderiza y encola correos.
- Notification Manager: crea notificaciones internas usando la estructura existente.
- Queue Processor: procesa cola, registra intentos y aplica reintentos.
- Providers: configuracion de envio sin exponer secretos.
- Channels: internal, email, marketing, whatsapp, sms, push.
- Transports: disabled, php_mail, smtp, api futuro.

## Proveedores y transportes

Tipos iniciales:

- disabled: simula envio y permite pruebas seguras.
- php_mail: usa `mail()` cuando no esta en modo simulacion.
- smtp: usa el paquete Email de FuelPHP si esta disponible.
- api: reservado para proveedores futuros.

Futuros:

- Mailchimp
- SendGrid
- Mailgun
- Amazon SES
- Brevo
- WhatsApp
- SMS
- Push

## Cola

Estados:

- pending
- processing
- sent
- failed
- cancelled
- skipped

Prioridades:

- critical
- high
- normal
- low
- marketing

Reintentos recomendados:

- intento 1: inmediato
- intento 2: 5 minutos
- intento 3: 15 minutos
- intento 4: 1 hora
- intento 5: 6 horas

## Templates y layouts

Separacion:

- Layout: estructura visual base.
- Template: asunto y contenido de un evento.
- Variables: datos del evento y variables globales.

Variables globales iniciales:

- `{{app_name}}`
- `{{current_year}}`

## Seguridad

- No exponer `password_encrypted` ni `api_key_encrypted` en JSON.
- Usar `Crypt::encode()` para secretos cuando se guarden.
- No registrar passwords, API keys ni tokens.
- Mantener modo simulacion para pruebas.
- Proteger acciones administrativas con `communications.access`.

## Centro de pruebas

El panel `admin/communications` puede:

- Mostrar proveedores sin secretos.
- Mostrar resumen de cola.
- Encolar y procesar una prueba.
- Usar `disabled_default` para simular envio sin correo real.

## Alcance RC3 Sprint 1B

Implementa infraestructura base. No conecta ventas, compras, helpdesk, portales, contacto web, CFDI ni password reset. Esos modulos deben migrarse por fases posteriores a `Helper_Core_Event::fire()`.
