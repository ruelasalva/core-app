<?php

return [
    'contact.web.message' => [
        'code' => 'contact.web.message',
        'module' => 'contact',
        'entity' => 'web',
        'action' => 'message',
        'description' => 'Mensaje recibido desde formulario publico de contacto.',
        'default_channels' => ['internal'],
        'default_priority' => 2,
        'email_role' => 'sales',
        'template_code' => '',
    ],
    'helpdesk.ticket.created' => [
        'code' => 'helpdesk.ticket.created',
        'module' => 'helpdesk',
        'entity' => 'ticket',
        'action' => 'created',
        'description' => 'Ticket de soporte creado desde admin o portal.',
        'default_channels' => ['internal'],
        'default_priority' => 2,
        'email_role' => 'support',
        'template_code' => '',
    ],
    'sales.quote.created' => [
        'code' => 'sales.quote.created',
        'module' => 'sales',
        'entity' => 'quote',
        'action' => 'created',
        'description' => 'Cotizacion creada desde un flujo comercial.',
        'default_channels' => ['internal'],
        'default_priority' => 2,
        'email_role' => 'sales',
        'template_code' => '',
    ],
];
