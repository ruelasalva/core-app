<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

return array(
	/**
	 * -------------------------------------------------------------------------
	 *  Default route
	 * -------------------------------------------------------------------------
	 *
	 */

	'_root_' => 'frontend/index',

	/**
	 * -------------------------------------------------------------------------
	 *  Page not found
	 * -------------------------------------------------------------------------
	 *
	 */

	'_404_' => 'welcome/404',
    '_400_' => 'welcome/400',

	// Ruta para el panel administrativo
    'admin' => 'admin/dashboard/index',
    'admin/workspace' => 'admin/workspace/index',
    'admin/workspace/data' => 'admin/workspace/data',
    'admin/workspace/widget/(:any)' => 'admin/workspace/widget/$1',
    'admin/workspace/quick_actions' => 'admin/workspace/quick_actions',
    'admin/workspace/command_palette' => 'admin/workspace/command_palette',
    'admin/workspace/available_widgets' => 'admin/workspace/available_widgets',
    'admin/workspace/add_widget' => 'admin/workspace/add_widget',
    'admin/workspace/save_layout' => 'admin/workspace/save_layout',
    'admin/workspace/save_preferences' => 'admin/workspace/save_preferences',
    'admin/workspace/reset_layout' => 'admin/workspace/reset_layout',
    'admin/commission-config' => 'admin/commissionconfig/index',
    'admin/commission-config/data' => 'admin/commissionconfig/data',
    'admin/commission-config/save_plan' => 'admin/commissionconfig/save_plan',
    'admin/commission-config/save_version' => 'admin/commissionconfig/save_version',
    'admin/commission-config/save_group' => 'admin/commissionconfig/save_group',
    'admin/commission-config/save_rule' => 'admin/commissionconfig/save_rule',
    'admin/commission-config/save_stage' => 'admin/commissionconfig/save_stage',
    'admin/commission-config/save_beneficiary' => 'admin/commissionconfig/save_beneficiary',
    'admin/commission-config/save_exclusion' => 'admin/commissionconfig/save_exclusion',
    'admin/commission-config/save_catalog' => 'admin/commissionconfig/save_catalog',
    'admin/commission-config/publish_version' => 'admin/commissionconfig/publish_version',
    'admin/commission-config/simulate' => 'admin/commissionconfig/simulate',
    'admin/communications/conversationlist' => 'admin/communications/conversationlist',
    'admin/communications/conversationdetail/(:num)' => 'admin/communications/conversationdetail/$1',
    'admin/sales/create' => 'admin/sales/create',
    'admin/sales/create_quote' => 'admin/sales/create_quote',
    'admin/sales/update_status' => 'admin/sales/update_status',
    'admin/sales/close_prequote' => 'admin/sales/close_prequote',
    'admin/sales/create_order_from_quote' => 'admin/sales/create_order_from_quote',
    'admin/sales/create_delivery_from_order' => 'admin/sales/create_delivery_from_order',

	// Ruta para el login (el que creamos anteriormente)
    'login' => 'auth/login',
	'logout' => 'auth/logout',
    'acceso' => 'account/login',
    'registro' => 'account/register',
    'mi-cuenta' => 'account/index',
    'salir-cuenta' => 'account/logout',
    'carrito' => 'cart/index',
    'carrito/agregar' => 'cart/add',
    'carrito/actualizar' => 'cart/update',
    'carrito/quitar/(:num)' => 'cart/remove/$1',
    'carrito/vaciar' => 'cart/clear',
    'carrito/checkout' => 'cart/checkout',
    'clientes/login' => 'portal/auth/login/clientes',
    'clientes/logout' => 'portal/auth/logout/clientes',
    'clientes/helpdesk' => 'clientes/helpdesk',
    'clientes/helpdesk_data' => 'clientes/helpdesk_data',
    'clientes/helpdesk_create' => 'clientes/helpdesk_create',
    'clientes/helpdesk_reply' => 'clientes/helpdesk_reply',
    'clientes/helpdesk_upload' => 'clientes/helpdesk_upload',
    'clientes/helpdesk_document_download/(:num)' => 'clientes/helpdesk_document_download/$1',
    'clientes/perfil_document_download/(:num)' => 'clientes/perfil_document_download/$1',
    'clientes/data' => 'clientes/data',
    'clientes/estado-cuenta' => 'clientes/account',
    'clientes/estado-cuenta_data' => 'clientes/account_data',
    'clientes/quotes' => 'clientes/quotes',
    'clientes/quote_request' => 'clientes/quote_request',
    'clientes/contracts' => 'clientes/contracts',
    'clientes/contracts_data' => 'clientes/contracts_data',
    'clientes/contracts_document_download/(:num)' => 'clientes/contracts_document_download/$1',
    'clientes/cfdi' => 'clientes/cfdi',
    'clientes/cfdi_data' => 'clientes/cfdi_data',
    'clientes/cfdi_xml_download/(:num)' => 'clientes/cfdi_xml_download/$1',
    'clientes/cfdi_pdf_download/(:num)' => 'clientes/cfdi_pdf_download/$1',
    'socios/login' => 'portal/auth/login/socios',
    'socios/logout' => 'portal/auth/logout/socios',
    'socios/helpdesk' => 'socios/helpdesk',
    'socios/helpdesk_data' => 'socios/helpdesk_data',
    'socios/helpdesk_create' => 'socios/helpdesk_create',
    'socios/helpdesk_reply' => 'socios/helpdesk_reply',
    'socios/helpdesk_upload' => 'socios/helpdesk_upload',
    'socios/helpdesk_document_download/(:num)' => 'socios/helpdesk_document_download/$1',
    'socios/perfil_document_download/(:num)' => 'socios/perfil_document_download/$1',
    'proveedores/login' => 'portal/auth/login/proveedores',
    'proveedores/logout' => 'portal/auth/logout/proveedores',
    'proveedores/registro' => 'proveedores/registro',
    'proveedores/registro_submit' => 'proveedores/registro_submit',
    'proveedores/helpdesk' => 'proveedores/helpdesk',
    'proveedores/helpdesk_data' => 'proveedores/helpdesk_data',
    'proveedores/helpdesk_create' => 'proveedores/helpdesk_create',
    'proveedores/helpdesk_reply' => 'proveedores/helpdesk_reply',
    'proveedores/helpdesk_upload' => 'proveedores/helpdesk_upload',
    'proveedores/helpdesk_document_download/(:num)' => 'proveedores/helpdesk_document_download/$1',
    'proveedores/perfil_document_download/(:num)' => 'proveedores/perfil_document_download/$1',
    'proveedores/compras' => 'proveedores/compras',
    'proveedores/compras_data' => 'proveedores/compras_data',
    'proveedores/compras_invoice' => 'proveedores/compras_invoice',
    'proveedores/compras_upload' => 'proveedores/compras_upload',
    'proveedores/compras_document_download/(:num)' => 'proveedores/compras/compras_document_download/$1',
    'proveedores/contracts' => 'proveedores/contracts',
    'proveedores/contracts_data' => 'proveedores/contracts_data',
    'proveedores/contracts_document_download/(:num)' => 'proveedores/contracts_document_download/$1',
    'proveedores/cfdi' => 'proveedores/cfdi',
    'proveedores/cfdi_data' => 'proveedores/cfdi_data',
    'revendedores/login' => 'portal/auth/login/revendedores',
    'revendedores/logout' => 'portal/auth/logout/revendedores',
    'revendedores/helpdesk' => 'revendedores/helpdesk',
    'revendedores/helpdesk_data' => 'revendedores/helpdesk_data',
    'revendedores/helpdesk_create' => 'revendedores/helpdesk_create',
    'revendedores/helpdesk_reply' => 'revendedores/helpdesk_reply',
    'revendedores/helpdesk_upload' => 'revendedores/helpdesk_upload',
    'revendedores/helpdesk_document_download/(:num)' => 'revendedores/helpdesk_document_download/$1',
    'revendedores/perfil_document_download/(:num)' => 'revendedores/perfil_document_download/$1',
    'legal/cookies/accept' => 'legal/cookies_accept',
    'about-us' => 'frontend/page/empresa',
    'empresa' => 'frontend/page/empresa',
    'servicios' => 'frontend/page/servicios',
    'distribucion' => 'frontend/page/distribucion',
    'contacto' => 'frontend/page/contacto',
    'contacto/enviar' => 'frontend/contact_submit',
    'pagina/(:any)' => 'frontend/page/$1',
    'productos' => 'frontend/products',
    'producto/(:any)' => 'frontend/product/$1',
    'categoria/(:any)' => 'frontend/category/$1',
    'tag/(:any)' => 'frontend/tag/$1',

	/**
	 * -------------------------------------------------------------------------
	 *  Example for Presenter
	 * -------------------------------------------------------------------------
	 *
	 *  A route for showing page using Presenter
	 *
	 */

	'hello(/:name)?' => array('welcome/hello', 'name' => 'hello'),
);
