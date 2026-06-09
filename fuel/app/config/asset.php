<?php

$core_app_asset_base_url = rtrim(\Config::get('base_url', '/'), '/').'/';

return array(
	/**
	 * Public base URL. Fuel appends the resolved asset path below.
	 * - development: /core-app/public/ + assets/js/...
	 * - production: / + assets/js/...
	 */
	'url' => $core_app_asset_base_url,

	/**
	 * Public asset paths relative to DOCROOT.
	 */
	'paths' => array(
		'assets/',
	),

	'folders' => array(
		'css' => 'css/',
		'js'  => 'js/',
		'img' => 'img/',
	),

	'add_mtime' => false,
	'fail_silently' => false,
	'always_resolve' => false,
);
