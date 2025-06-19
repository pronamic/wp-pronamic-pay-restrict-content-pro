<?php
/**
 * Plugin Name: Pronamic Pay Restrict Content (Pro) Add-On
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-pay-restrict-content-pro/
 * Description: Extend the Pronamic Pay plugin with Restrict Content (Pro) support to receive payments through a variety of payment providers.
 *
 * Version: 4.6.3
 * Requires at least: 4.7
 * Requires PHP: 8.1
 *
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 *
 * Text Domain: pronamic-pay-restrict-content
 * Domain Path: /languages/
 *
 * License: GPL-3.0-or-later
 *
 * Depends: wp-pay/core
 *
 * GitHub URI: https://github.com/wp-pay-extensions/restrict-content-pro
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoload.
 */
require_once __DIR__ . '/vendor/autoload_packages.php';

/**
 * Bootstrap.
 */
\Pronamic\WordPress\Pay\Plugin::instance(
	[
		'file'             => __FILE__,
		'action_scheduler' => __DIR__ . '/packages/woocommerce/action-scheduler/action-scheduler.php',
	]
);

add_filter(
	'pronamic_pay_modules',
	function ( $modules ) {
		$modules[] = 'subscriptions';

		return $modules;
	}
);

add_filter(
	'pronamic_pay_plugin_integrations',
	function ( $integrations ) {
		$integrations[] = new \Pronamic\WordPress\Pay\Extensions\RestrictContent\Extension();

		return $integrations;
	}
);

add_filter(
	'pronamic_pay_gateways',
	function ( $gateways ) {
		$gateways[] = new \Pronamic\WordPress\Pay\Gateways\Mollie\Integration(
			[
				'manual_url' => \__( 'https://www.pronamicpay.com/en/manuals/how-to-connect-mollie-to-wordpress-with-pronamic-pay/', 'pronamic-pay-with-mollie-for-contact-form-7' ),
			]
		);

		return $gateways;
	}
);
