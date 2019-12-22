<?php
/**
 * Upgrade 2.1.6
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Upgrades
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Upgrades\Upgrade;

/**
 * Upgrade 2.1.6
 *
 * @author  Remco Tolsma
 * @version 2.1.6
 * @since   2.1.6
 */
class Upgrade216 extends Upgrade {
	/**
	 * Construct 2.1.6 upgrade.
	 */
	public function __construct() {
		parent::__construct( '2.1.6' );
	}

	/**
	 * Execute.
	 */
	public function execute() {
		include __DIR__ . '/../upgrades/upgrade-2.1.6-source-id.php';
	}
}
