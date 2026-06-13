<?php

return array(
	'cookie_name'      => 'fuelcid',
	'cookie_path'      => '/',
	'cookie_http_only' => true,
	'cookie_same_site' => 'Lax',
	'cookie_secure'    => Fuel::$env === Fuel::PRODUCTION,
);
