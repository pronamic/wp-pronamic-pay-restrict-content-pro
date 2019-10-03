<?php
/**
 * Restrict Content Pro
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

/**
 * Restrict Content Pro
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
 * @since   1.0.0
 */
class RestrictContentPro {
	/**
	 * Check if Restrict Content Pro is active
	 *
	 * @return boolean
	 */
	public static function is_active() {
		return defined( 'RCP_PLUGIN_VERSION' ) && \version_compare( RCP_PLUGIN_VERSION, '3.0', '>=' );
	}
}
