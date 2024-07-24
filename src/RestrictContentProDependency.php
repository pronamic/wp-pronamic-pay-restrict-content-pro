<?php
/**
 * PHP Dependency
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContent
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent;

/**
 * PHP Dependency
 *
 * @link    https://github.com/Yoast/yoast-acf-analysis/blob/2.3.0/inc/dependencies/dependency-yoast-seo.php
 * @link    https://github.com/dsawardekar/wp-requirements/blob/0.3.0/lib/Requirements.php#L104-L118
 * @author  Remco Tolsma
 * @version 2.1.6
 * @since   2.1.6
 */
class RestrictContentProDependency extends \Pronamic\WordPress\Pay\Dependencies\Dependency {
	/**
	 * Is met.
	 *
	 * @link https://github.com/dsawardekar/wp-requirements/blob/0.3.0/lib/Requirements.php#L104-L118
	 * @return bool True if dependency is met, false otherwise.
	 */
	public function is_met() {
		if ( ! \defined( 'RCP_PLUGIN_VERSION' ) ) {
			return false;
		}

		return \version_compare(
			RCP_PLUGIN_VERSION,
			'3.0.0',
			'>='
		);
	}
}
