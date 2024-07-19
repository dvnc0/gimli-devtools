<?php
declare(strict_types=1);

namespace GimliDev;

if (!function_exists('GimliDev\load_config')) {
	/**
	 * Load the devtools.json configuration file
	 *
	 * @return array
	 */
	function load_config(): array {
		return json_decode(file_get_contents(ROOT . '/devtools.json'), true);
	}
}

if (!function_exists('GimliDev\save_config')) {
	/**
	 * Save the devtools.json configuration file
	 *
	 * @param array $config
	 * @return void
	 */
	function save_config(array $config): void {
		file_put_contents(ROOT . '/devtools.json', json_encode($config, JSON_PRETTY_PRINT));
	}
}