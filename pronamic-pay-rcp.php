<?php
/**
 * Plugin Name: Pronamic Pay Restrict Content Pro Add-On
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-pay-restrict-content-pro/
 * Description: Extend the Pronamic Pay plugin with Restrict Content Pro support to receive payments through a variety of payment providers.
 *
 * Version: 4.0.0
 * Requires at least: 4.7
 *
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 *
 * Text Domain: pronamic-pay-rcp
 * Domain Path: /languages/
 *
 * License: GPL-3.0-or-later
 *
 * Depends: wp-pay/core
 *
 * GitHub URI: https://github.com/wp-pay-extensions/restrict-content-pro
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

add_filter(
	'pronamic_pay_plugin_integrations',
	function( $integrations ) {
		$integrations[] = new \Pronamic\WordPress\Pay\Extensions\RestrictContentPro\Extension();

		return $integrations;
	}
);
