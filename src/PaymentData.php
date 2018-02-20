<?php

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Payments\PaymentData as Pay_PaymentData;
use Pronamic\WordPress\Pay\Payments\Item;
use Pronamic\WordPress\Pay\Payments\Items;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use RCP_Payments;

/**
 * Title: Restrict Content Pro payment data
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 1.0.0
 * @since   1.0.0
 */
class PaymentData extends Pay_PaymentData {
	/**
	 * Payment ID
	 *
	 * @var int
	 */
	private $payment_id;

	/**
	 * Payment data
	 *
	 * @var mixed
	 */
	private $payment_data;

	//////////////////////////////////////////////////

	/**
	 * Constructs and initializes an Restrict Content Pro iDEAL data proxy
	 *
	 * @param int   $payment_id
	 * @param mixed $payment_data
	 */
	public function __construct( $payment_id, $payment_data ) {
		parent::__construct();

		$this->payment_id   = $payment_id;
		$this->payment_data = $payment_data;
	}

	//////////////////////////////////////////////////

	/**
	 * Get source ID
	 *
	 * @return int $source_id
	 */
	public function get_source_id() {
		return $this->payment_id;
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

	//////////////////////////////////////////////////

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
		return $this->payment_data['subscription_name'];
	}

	/**
	 * Get order ID
	 *
	 * @return string
	 */
	public function get_order_id() {
		return $this->payment_id;
	}

	/**
	 * Get items
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_items()
	 * @return Items
	 */
	public function get_items() {
		// Items
		$items = new Items();

		// Item
		// We only add one total item, because iDEAL cant work with negative price items (discount)
		$item = new Item();
		$item->setNumber( $this->payment_id );
		$item->setDescription( $this->get_description() );
		$item->setPrice( $this->payment_data['amount'] );
		$item->setQuantity( 1 );

		$items->addItem( $item );

		return $items;
	}

	//////////////////////////////////////////////////

	/**
	 * Get currency
	 *
	 * @return string
	 */
	public function get_currency_alphabetic_code() {
		return rcp_get_currency();
	}

	//////////////////////////////////////////////////

	public function get_email() {
		return $this->payment_data['email'];
	}

	public function get_customer_name() {
		$name = '';

		if ( isset( $this->payment_data['user_name'] ) ) {
			$user = get_user_by( 'login', $this->payment_data['user_name'] );

			if ( $user ) {
				$name = trim( $user->first_name . ' ' . $user->last_name );
			}
		}

		return $name;
	}

	public function get_address() {
		return '';
	}

	public function get_city() {
		return '';
	}

	public function get_zip() {
		return '';
	}

	public function get_user_id() {
		$user_id = 0;

		if ( isset( $this->payment_data['subscription_data']['user_id'] ) ) {
			$user_id = $this->payment_data['subscription_data']['user_id'];
		} elseif ( isset( $this->payment_data['user_name'] ) ) {
			$user = get_user_by( 'login', $this->payment_data['user_name'] );

			$user_id = $user->ID;
		} else {
			$payments = new RCP_Payments();
			$payment  = $payments->get_payment( $this->payment_id );

			if ( $payment ) {
				$user_id = $payment->user_id;
			}
		}

		return $user_id;
	}

	//////////////////////////////////////////////////

	public function get_normal_return_url() {
		return home_url();
	}

	public function get_success_url() {
		global $rcp_options;

		$page_id = $rcp_options['redirect'];

		if ( is_numeric( $page_id ) ) {
			return get_permalink( $page_id );
		}

		return null;
	}

	//////////////////////////////////////////////////
	// Subscription
	//////////////////////////////////////////////////

	public function get_subscription() {
		if ( ! $this->payment_data['auto_renew'] ) {
			return false;
		}

		if ( ! isset( $this->payment_data['subscription_data'] ) ) {
			return false;
		}

		$subscription_data = $this->payment_data['subscription_data'];

		$subscription                  = new Subscription();
		$subscription->frequency       = '';
		$subscription->interval        = $subscription_data['length'];
		$subscription->interval_period = Core_Util::to_period( $subscription_data['length_unit'] );
		$subscription->amount          = $subscription_data['recurring_price'];
		$subscription->currency        = $this->get_currency();
		$subscription->description     = $this->get_description();

		return $subscription;
	}

	/**
	 * Get subscription source ID.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_subscription_source_id() {
		$subscription = $this->get_subscription();

		if ( ! $subscription ) {
			return false;
		}

		$user_subscription = Util::get_subscription_by_user( $this->get_user_id() );

		if ( $user_subscription ) {
			return $user_subscription->get_source_id();
		}

		return $this->get_source_id();
	}

	/**
	 * Get subscription ID.
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/v2.1.3/includes/abstracts/abstract-wc-payment-gateway.php#L52
	 * @return string
	 */
	public function get_subscription_id() {
		if ( ! $this->get_subscription() ) {
			return;
		}

		$user_subscription = Util::get_subscription_by_user( $this->get_user_id() );

		if ( ! $user_subscription ) {
			return;
		}

		return $user_subscription->get_id();
	}
}
