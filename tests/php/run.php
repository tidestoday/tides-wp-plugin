<?php

if (! defined('ABSPATH') && 'cli' !== PHP_SAPI) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.Security.EscapeOutput, WordPress.PHP.DevelopmentFunctions, WordPress.WP.AlternativeFunctions

if (! defined('ABSPATH')) {
	define('ABSPATH', dirname(dirname(__DIR__)) . '/');
}

if (! function_exists('absint')) {
	function absint($value) {
		return abs((int) $value);
	}
}

if (! function_exists('trailingslashit')) {
	function trailingslashit($value) {
		return rtrim($value, '/') . '/';
	}
}

if (! function_exists('untrailingslashit')) {
	function untrailingslashit($value) {
		return rtrim($value, '/');
	}
}

if (! function_exists('wp_parse_url')) {
	function wp_parse_url($url, $component = -1) {
		return parse_url($url, $component);
	}
}

if (! function_exists('sanitize_key')) {
	function sanitize_key($value) {
		return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $value));
	}
}

if (! function_exists('sanitize_text_field')) {
	function sanitize_text_field($value) {
		return trim(strip_tags((string) $value));
	}
}

if (! function_exists('esc_url_raw')) {
	function esc_url_raw($value) {
		return filter_var((string) $value, FILTER_SANITIZE_URL);
	}
}

if (! function_exists('__')) {
	function __($value) {
		return $value;
	}
}

require_once dirname(dirname(__DIR__)) . '/includes/class-tttw-plugin.php';

function tttw_create_plugin_without_constructor() {
	$reflection = new ReflectionClass('TTTW_Plugin');

	return $reflection->newInstanceWithoutConstructor();
}

function tttw_call_private_method($object, $method_name, $arguments) {
	$method = new ReflectionMethod(get_class($object), $method_name);
	$method->setAccessible(true);

	return $method->invokeArgs($object, $arguments);
}

function tttw_assert_same($expected, $actual, $message) {
	if ($expected !== $actual) {
		throw new Exception(
			$message . PHP_EOL .
			'Expected: ' . var_export($expected, true) . PHP_EOL .
			'Actual:   ' . var_export($actual, true)
		);
	}
}

function tttw_assert_true($condition, $message) {
	if (! $condition) {
		throw new Exception($message);
	}
}

function tttw_extract_country_names($items) {
	$names = array();

	foreach ($items as $item) {
		$names[] = $item['name'];
	}

	return $names;
}

$plugin = tttw_create_plugin_without_constructor();
$tests  = array();

$tests['sanitize_widget_settings normalizes values and clamps ranges'] = function () use ($plugin) {
	$settings = tttw_call_private_method(
		$plugin,
		'sanitize_widget_settings',
		array(
			array(
				'number_days'     => 9,
				'include_map'     => 'false',
				'include_weather' => '1',
				'include_styles'  => 0,
				'include_title'   => 'yes',
				'weather_unit'    => 'invalid',
				'height_unit'     => 'yards',
			),
		)
	);

	tttw_assert_same(5, $settings['number_days'], 'Days should be clamped to the supported maximum.');
	tttw_assert_same(false, $settings['include_map'], 'Boolean false strings should be normalized.');
	tttw_assert_same(true, $settings['include_weather'], 'Truthy strings should be normalized.');
	tttw_assert_same(false, $settings['include_styles'], 'Numeric zero should be normalized to false.');
	tttw_assert_same(true, $settings['include_title'], 'Yes should be normalized to true.');
	tttw_assert_same('c', $settings['weather_unit'], 'Invalid weather units should fall back to Celsius.');
	tttw_assert_same('m', $settings['height_unit'], 'Invalid height units should fall back to meters.');
	tttw_assert_true(! isset($settings['custom_css']), 'Custom CSS should not be stored by the plugin.');
};

$tests['reorder_countries promotes English priority countries in the expected order'] = function () use ($plugin) {
	$items = array(
		array('id' => 1, 'name' => 'France', 'slug' => 'france'),
		array('id' => 2, 'name' => 'Wales', 'slug' => 'wales'),
		array('id' => 3, 'name' => 'New Zealand', 'slug' => 'new-zealand'),
		array('id' => 4, 'name' => 'Canada', 'slug' => 'canada'),
		array('id' => 5, 'name' => 'Spain', 'slug' => 'spain'),
		array('id' => 6, 'name' => 'England', 'slug' => 'england'),
	);

	$reordered = tttw_call_private_method($plugin, 'reorder_countries', array($items, 'en'));

	tttw_assert_same(
		array('England', 'Wales', 'Canada', 'New Zealand', 'France', 'Spain'),
		tttw_extract_country_names($reordered),
		'English country ordering should move preferred countries to the top.'
	);
};

$tests['reorder_countries promotes French priority countries in the expected order'] = function () use ($plugin) {
	$items = array(
		array('id' => 1, 'name' => 'Belgium', 'slug' => 'belgium'),
		array('id' => 2, 'name' => 'Canada', 'slug' => 'canada'),
		array('id' => 3, 'name' => 'France', 'slug' => 'france'),
		array('id' => 4, 'name' => 'Spain', 'slug' => 'spain'),
	);

	$reordered = tttw_call_private_method($plugin, 'reorder_countries', array($items, 'fr'));

	tttw_assert_same(
		array('France', 'Canada', 'Belgium', 'Spain'),
		tttw_extract_country_names($reordered),
		'French country ordering should move France and Canada to the top.'
	);
};

$tests['sanitize_slug_segment keeps URL-safe slug characters only'] = function () use ($plugin) {
	$slug = tttw_call_private_method($plugin, 'sanitize_slug_segment', array(' Conwy & Llandudno!! '));

	tttw_assert_same('conwy-llandudno', $slug, 'Slug sanitization should produce lowercase URL-safe segments.');
};

$tests['get_init_query_args_from_settings produces widget query arguments'] = function () use ($plugin) {
	$args = tttw_call_private_method(
		$plugin,
		'get_init_query_args_from_settings',
		array(
			array(
				'include_map'     => false,
				'include_weather' => true,
				'include_styles'  => false,
				'include_title'   => true,
				'number_days'     => 3,
				'weather_unit'    => 'f',
				'height_unit'     => 'ft',
			),
		)
	);

	tttw_assert_same(
		array(
			'includeMap'     => 'false',
			'includeWeather' => 'true',
			'includeStyles'  => 'false',
			'includeTitle'   => 'true',
			'numberDays'     => 3,
			'weatherUnit'    => 'f',
			'heightUnit'     => 'ft',
		),
		$args,
		'Init query arguments should match the saved widget settings.'
	);
};

$tests['build_widget_data_url targets the data endpoint'] = function () use ($plugin) {
	$url = tttw_call_private_method(
		$plugin,
		'build_widget_data_url',
		array(
			array(
				'language' => 'en',
				'country'  => array('slug' => 'wales'),
				'region'   => array('slug' => 'conwy'),
				'location' => array('slug' => 'llandudno'),
			)
		)
	);

	tttw_assert_same(
		'https://api.tidestoday.io/widgets-api/wp-v1/en/wales/conwy/llandudno/data.json',
		$url,
		'Widget data URLs should point to data.json, not remote JavaScript.'
	);
};

$tests['build_request_signature signs method path and timestamp'] = function () use ($plugin) {
	$signature = tttw_call_private_method(
		$plugin,
		'build_request_signature',
		array(
			'GET',
			'https://api.tidestoday.io/widgets-api/wp-v1/en/wales/conwy/llandudno/data.json',
			'1772300000',
			'secret-key'
		)
	);

	tttw_assert_same(
		hash_hmac('sha256', "GET\n/widgets-api/wp-v1/en/wales/conwy/llandudno/data.json\n1772300000", 'secret-key'),
		$signature,
		'Signatures should be based on the HTTP method, URL path, timestamp, and installation secret.'
	);
};

$tests['normalize_widget_data preserves tide and weather values'] = function () use ($plugin) {
	$data = tttw_call_private_method(
		$plugin,
		'normalize_widget_data',
		array(
			array(
				'id'          => 511,
				'location'    => 'Llandudno',
				'locationUrl' => 'https://tides.today/en/wales/conwy/llandudno',
				'termsUrl'    => 'https://tides.today/en/terms-of-service',
				'map'         => 'https://cdn.tidestoday.media/prod/maps/test.webp',
				'data'        => array(
					array(
						'date'    => '30 April 2026',
						'tides'   => array(
							array('time' => '05:02', 'heightM' => 1.01, 'heightF' => 3.31, 'type' => 'low'),
							array('time' => '10:50', 'heightM' => 7.16, 'heightF' => 23.49, 'type' => 'high'),
						),
						'weather' => array(
							'description' => 'Patchy rain nearby',
							'icon'        => 'https://cdn.tidestoday.media/prod/weather-icons/1063.svg',
							'highF'       => 62.78,
							'lowF'        => 42.98,
							'highC'       => 17.1,
							'lowC'        => 6.1,
						),
					),
				),
			)
		)
	);

	tttw_assert_same(511, $data['id'], 'Location ID should be normalized as an integer.');
	tttw_assert_same('Llandudno', $data['location'], 'Location name should be preserved.');
	tttw_assert_same('low', $data['days'][0]['tides'][0]['type'], 'Tide type should be normalized as a key.');
	tttw_assert_same(7.16, $data['days'][0]['tides'][1]['heightM'], 'Metric tide heights should be preserved.');
	tttw_assert_same(17.1, $data['days'][0]['weather']['highC'], 'Metric weather values should be preserved.');
};

$tests['French locale packs exist for both France and French Canada'] = function () {
	$base_dir = dirname(dirname(__DIR__)) . '/languages/';

	tttw_assert_true(file_exists($base_dir . 'tides-today-tides-and-weather-fr_FR.po'), 'The fr_FR PO file should exist.');
	tttw_assert_true(file_exists($base_dir . 'tides-today-tides-and-weather-fr_FR.mo'), 'The fr_FR MO file should exist.');
	tttw_assert_true(file_exists($base_dir . 'tides-today-tides-and-weather-fr_CA.po'), 'The fr_CA PO file should exist.');
	tttw_assert_true(file_exists($base_dir . 'tides-today-tides-and-weather-fr_CA.mo'), 'The fr_CA MO file should exist.');
	tttw_assert_true(! file_exists($base_dir . 'tides-today-cy.po'), 'The legacy Welsh PO file should be removed.');
	tttw_assert_true(! file_exists($base_dir . 'tides-today-cy.mo'), 'The legacy Welsh MO file should be removed.');
};

$failures = array();

foreach ($tests as $name => $test) {
	try {
		$test();
		echo '[PASS] ' . $name . PHP_EOL;
	} catch (Exception $exception) {
		$failures[] = array(
			'name'    => $name,
			'message' => $exception->getMessage(),
		);

		fwrite(STDERR, '[FAIL] ' . $name . PHP_EOL . $exception->getMessage() . PHP_EOL);
	}
}

if (! empty($failures)) {
	fwrite(STDERR, PHP_EOL . count($failures) . ' PHP test(s) failed.' . PHP_EOL);
	exit(1);
}

echo PHP_EOL . count($tests) . ' PHP test(s) passed.' . PHP_EOL;
