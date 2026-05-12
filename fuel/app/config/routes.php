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
    'clientes/login' => 'portal/auth/login/clientes',
    'clientes/logout' => 'portal/auth/logout/clientes',
    'socios/login' => 'portal/auth/login/socios',
    'socios/logout' => 'portal/auth/logout/socios',
    'proveedores/login' => 'portal/auth/login/proveedores',
    'proveedores/logout' => 'portal/auth/logout/proveedores',
    'revendedores/login' => 'portal/auth/login/revendedores',
    'revendedores/logout' => 'portal/auth/logout/revendedores',
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
