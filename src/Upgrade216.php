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
 * @version unreleased
 * @since   unreleased
 */
class Upgrade216 extends Upgrade {
	/**
	 * Execute.
	 */
	abstract public function execute() {
		include __DIR__ . '/../updates/update-2.1.6-source-id.php';
	}
}
