<?php
/**
 * Payment data
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Payments\PaymentData as Pay_PaymentData;
use Pronamic\WordPress\Pay\Payments\Item;
use Pronamic\WordPress\Pay\Payments\Items;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use RCP_Payment_Gateway;
use RCP_Member;

/**
 * Payment data
 *
 * @author  Reüel van der Steege
 * @version 2.1.1
 * @since   1.0.0
 */
class PaymentData extends Pay_PaymentData {
	/**
	 * Gateway
	 *
	 * @var RCP_Payment_Gateway
	 */
	private $gateway;

	/**
	 * Constructs and initializes an Restrict Content Pro iDEAL data proxy
	 *
	 * @param RCP_Payment_Gateway $gateway Gateway.
	 */
	public function __construct( RCP_Payment_Gateway $gateway ) {
		parent::__construct();

		$this->gateway = $gateway;
	}

	/**
	 * Get payment ID.
	 *
	 * @return string
	 */
	private function get_payment_id() {
		return $this->gateway->payment->id;
	}

	/**
	 * Get source ID
	 *
	 * @return int $source_id
	 */
	public function get_source_id() {
		return $this->get_payment_id();
	}

	/**
	 * Get source indicator
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_source()
	 * @return string
	 */
	public function get_source() {
		return 'restrictcontentpro';
	}

	/**
	 * Get title.
	 *
	 * @return string
	 */
	public function get_title() {
		/* translators: %s: order id */
		return sprintf( __( 'Restrict Content Pro order %s', 'pronamic_ideal' ), $this->get_order_id() );
	}

	/**
	 * Get description
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->gateway->subscription_name;
	}

	/**
	 * Get order ID
	 *
	 * @return string
	 */
	public function get_order_id() {
		return $this->get_payment_id();
	}

	/**
	 * Get items
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_items()
	 * @return Items
	 */
	public function get_items() {
		// Items.
		$items = new Items();

		// Item.
		// We only add one total item, because iDEAL cant work with negative price items (discount).
		$item = new Item();
		$item->set_number( $this->get_payment_id() );
		$item->set_description( $this->get_description() );
		$item->set_price( $this->gateway->initial_amount );
		$item->set_quantity( 1 );

		$items->addItem( $item );

		return $items;
	}

	/**
	 * Get currency.
	 *
	 * @return string
	 */
	public function get_currency_alphabetic_code() {
		return rcp_get_currency();
	}

	/**
	 * Get email.
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->gateway->email;
	}

	/**
	 * Get customer name.
	 *
	 * @return string
	 */
	public function get_customer_name() {
		$name = '';

		$user = get_user_by( 'login', $this->gateway->user_name );

		if ( $user ) {
			$name = trim( $user->first_name . ' ' . $user->last_name );
		}

		return $name;
	}

	/**
	 * Get address.
	 *
	 * @return string
	 */
	public function get_address() {
		return '';
	}

	/**
	 * Get city.
	 *
	 * @return string
	 */
	public function get_city() {
		return '';
	}

	/**
	 * Get ZIP.
	 *
	 * @return string
	 */
	public function get_zip() {
		return '';
	}

	/**
	 * Get user ID.
	 *
	 * @return int|string
	 */
	public function get_user_id() {
		return $this->gateway->user_id;
	}

	/**
	 * Get normal return URL.
	 *
	 * @return string
	 */
	public function get_normal_return_url() {
		return rcp_get_return_url( $this->get_user_id() );
	}

	/**
	 * Get success URL.
	 *
	 * @return string
	 */
	public function get_success_url() {
		return rcp_get_return_url( $this->get_user_id() );
	}

	/**
	 * Get subscription.
	 *
	 * @return Subscription|false
	 */
	public function get_subscription() {
		if ( ! $this->gateway->auto_renew ) {
			return false;
		}

		if ( ! isset( $this->gateway->subscription_data ) ) {
			return false;
		}

		$subscription                  = new Subscription();
		$subscription->frequency       = '';
		$subscription->interval        = $this->gateway->subscription_data['length'];
		$subscription->interval_period = Core_Util::to_period( $this->gateway->subscription_data['length_unit'] );
		$subscription->description     = $this->get_description();

		$subscription->set_amount(
			new Money(
				$this->gateway->subscription_data['recurring_price'],
				$this->get_currency_alphabetic_code()
			)
		);

		return $subscription;
	}

	/**
	 * Get subscription source ID.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_subscription_source_id() {
		return $this->gateway->user_id;
	}

	/**
	 * Get subscription ID.
	 *
	 * @link https://github.com/woothemes/woocommerce/blob/v2.1.3/includes/abstracts/abstract-wc-payment-gateway.php#L52
	 * @return string
	 */
	public function get_subscription_id() {
		$member = new RCP_Member( $this->gateway->user_id );

		if ( $member->get_subscription_id() !== $this->gateway->subscription_id ) {
			return;
		}

		$subscription = Util::get_subscription_by_user( $this->gateway->user_id );

		if ( empty( $subscription ) ) {
			return;
		}

		return $subscription->get_id();
	}
}
