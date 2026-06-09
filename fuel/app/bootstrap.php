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

// Bootstrap the framework - THIS LINE NEEDS TO BE FIRST!
require COREPATH.'bootstrap.php';

// Add framework overload classes here
\Autoloader::add_classes(array(
	// Example: 'View' => APPPATH.'classes/myview.php',
));

// Register the autoloader
\Autoloader::register();

/**
 * Your environment.  Can be set to any of the following:
 *
 * Fuel::DEVELOPMENT
 * Fuel::TEST
 * Fuel::STAGING
 * Fuel::PRODUCTION
 */
$env = getenv('FUEL_ENV') ?: Arr::get($_SERVER, 'FUEL_ENV', Arr::get($_ENV, 'FUEL_ENV', null));

if ($env) {
	Fuel::$env = strtolower((string) $env);
} else {
	$host = $_SERVER['HTTP_HOST']
		?? $_SERVER['SERVER_NAME']
		?? php_uname('n')
		?? 'localhost';
	$host = strtolower((string) $host);

	if (
		strpos($host, 'localhost') !== false ||
		strpos($host, '127.0.0.1') !== false ||
		strpos($host, '.local') !== false ||
		strpos($host, '.test') !== false ||
		strpos($host, '.dev') !== false
	) {
		Fuel::$env = Fuel::DEVELOPMENT;
	} else {
		Fuel::$env = Fuel::PRODUCTION;
	}
}

// Initialize the framework with the config file.
\Fuel::init('config.php');
