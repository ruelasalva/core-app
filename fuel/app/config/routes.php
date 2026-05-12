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

	// Ruta para el panel administrativo
    'admin' => 'admin/dashboard/index',

	// Ruta para el login (el que creamos anteriormente)
    'login' => 'auth/login',
	'logout' => 'auth/logout',
    'acceso' => 'account/login',
    'registro' => 'account/register',
    'mi-cuenta' => 'account/index',
    'salir-cuenta' => 'account/logout',
    'clientes/login' => 'portal/auth/login/clientes',
    'clientes/logout' => 'portal/auth/logout/clientes',
    'clientes/helpdesk' => 'clientes/helpdesk',
    'clientes/helpdesk_data' => 'clientes/helpdesk_data',
    'clientes/helpdesk_create' => 'clientes/helpdesk_create',
    'clientes/helpdesk_reply' => 'clientes/helpdesk_reply',
    'clientes/helpdesk_upload' => 'clientes/helpdesk_upload',
    'socios/login' => 'portal/auth/login/socios',
    'socios/logout' => 'portal/auth/logout/socios',
    'socios/helpdesk' => 'socios/helpdesk',
    'socios/helpdesk_data' => 'socios/helpdesk_data',
    'socios/helpdesk_create' => 'socios/helpdesk_create',
    'socios/helpdesk_reply' => 'socios/helpdesk_reply',
    'socios/helpdesk_upload' => 'socios/helpdesk_upload',
    'proveedores/login' => 'portal/auth/login/proveedores',
    'proveedores/logout' => 'portal/auth/logout/proveedores',
    'proveedores/helpdesk' => 'proveedores/helpdesk',
    'proveedores/helpdesk_data' => 'proveedores/helpdesk_data',
    'proveedores/helpdesk_create' => 'proveedores/helpdesk_create',
    'proveedores/helpdesk_reply' => 'proveedores/helpdesk_reply',
    'proveedores/helpdesk_upload' => 'proveedores/helpdesk_upload',
    'revendedores/login' => 'portal/auth/login/revendedores',
    'revendedores/logout' => 'portal/auth/logout/revendedores',
    'revendedores/helpdesk' => 'revendedores/helpdesk',
    'revendedores/helpdesk_data' => 'revendedores/helpdesk_data',
    'revendedores/helpdesk_create' => 'revendedores/helpdesk_create',
    'revendedores/helpdesk_reply' => 'revendedores/helpdesk_reply',
    'revendedores/helpdesk_upload' => 'revendedores/helpdesk_upload',
    'legal/cookies/accept' => 'legal/cookies_accept',
    'empresa' => 'frontend/page/empresa',
    'distribucion' => 'frontend/page/distribucion',
    'contacto' => 'frontend/page/contacto',
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
