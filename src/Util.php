<?php
/**
 * Util
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContent
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent;

use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\ContactName;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use Pronamic\WordPress\Pay\Payments\PaymentLineType;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionInterval;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionPhase;
use RCP_Payment_Gateway;

/**
 * Util
 *
 * @author  Reüel van der Steege
 * @version 2.2.2
 * @since   1.0.0
 */
class Util {
	/**
	 * Create new payment from Restrict Content Pro gateway object.
	 *
	 * @link https://restrictcontentpro.com/tour/payment-gateways/add-your-own/
	 * @link http://docs.pippinsplugins.com/article/812-payment-gateway-api
	 * @link https://github.com/wp-pay-extensions/woocommerce/blob/develop/src/Gateway.php
	 *
	 * @param RCP_Payment_Gateway $gateway Restrict Content Pro gateway object.
	 * @return Payment
	 * @throws \Exception Throws an exception if the Restrict Content data does not meet expectations.
	 */
	public static function new_payment_from_rcp_gateway( $gateway ) {
		$payment = new Payment();

		if ( ! \property_exists( $gateway->payment, 'id' ) ) {
			throw new \Exception( 'Payment object from Restrict Content gateway object does not contain an ID.' );
		}

		$payment->title = sprintf(
			/* translators: %s: Restrict Content Pro payment ID */
			__( 'Restrict Content Pro payment %s', 'pronamic_ideal' ),
			$gateway->payment->id
		);

		$payment->set_description( $gateway->subscription_name );

		$payment->source    = 'rcp_payment';
		$payment->source_id = $gateway->payment->id;

		if ( array_key_exists( 'post_data', $gateway->subscription_data ) ) {
			$post_data = $gateway->subscription_data['post_data'];

			if ( array_key_exists( 'pronamic_ideal_issuer_id', $post_data ) ) {
				$payment->set_meta( 'issuer', $post_data['pronamic_ideal_issuer_id'] );
			}
		}

		$customer = self::new_customer_from_rcp_gateway( $gateway );

		$payment->set_customer( $customer );

		$payment->lines = self::new_payment_lines_from_rcp_gateway( $gateway );

		$subscription = self::new_subscription_from_rcp_gateway( $gateway );

		if ( null !== $subscription ) {
			$payment->add_subscription( $subscription );

			$start_date = $subscription->get_start_date();

			if ( null !== $start_date ) {
				$period = $subscription->get_period_for_date( $start_date );

				if ( null !== $period ) {
					$payment->add_period( $period );
				}
			}
		}

		/**
		 * Total amount.
		 *
		 * The `$gateway->initial_amount` property contains the normal price + fees.
		 * In previous version of this library we added the fees, this was wrong.
		 *
		 * @link https://github.com/wp-pay-extensions/restrict-content-pro/issues/1
		 * @link https://gitlab.com/pronamic-plugins/restrict-content-pro/-/blob/3.4.4/includes/registration-functions.php#L1784-1788
		 */
		$amount = new Money( $gateway->initial_amount, $gateway->currency );

		$payment->set_total_amount( $amount );

		return $payment;
	}

	/**
	 * Create new customer from Restrict Content Pro gateway object.
	 *
	 * @link https://restrictcontentpro.com/tour/payment-gateways/add-your-own/
	 * @link http://docs.pippinsplugins.com/article/812-payment-gateway-api
	 * @link https://github.com/wp-pay-extensions/woocommerce/blob/develop/src/Gateway.php
	 *
	 * @param RCP_Payment_Gateway $gateway Restrict Content Pro gateway object.
	 * @return Customer
	 */
	public static function new_customer_from_rcp_gateway( $gateway ) {
		// Contact name.
		$contact_name = new ContactName();

		if ( array_key_exists( 'post_data', $gateway->subscription_data ) ) {
			$post_data = $gateway->subscription_data['post_data'];

			if ( array_key_exists( 'rcp_user_first', $post_data ) ) {
				$contact_name->set_first_name( $post_data['rcp_user_first'] );
			}

			if ( array_key_exists( 'rcp_user_last', $post_data ) ) {
				$contact_name->set_last_name( $post_data['rcp_user_last'] );
			}
		}

		// Customer.
		$customer = new Customer();

		$customer->set_name( $contact_name );
		$customer->set_email( $gateway->email );
		$customer->set_user_id( $gateway->user_id );

		// Result.
		return $customer;
	}

	/**
	 * New payment lines from Restrict Content Pro gateway object.
	 *
	 * @link https://restrictcontentpro.com/tour/payment-gateways/add-your-own/
	 * @link http://docs.pippinsplugins.com/article/812-payment-gateway-api
	 * @link https://github.com/wp-pay-extensions/woocommerce/blob/develop/src/Gateway.php
	 *
	 * @param RCP_Payment_Gateway $gateway Restrict Content Pro gateway object.
	 * @return PaymentLines
	 * @throws \Exception Throws an exception if the Restrict Content data does not meet expectations.
	 */
	public static function new_payment_lines_from_rcp_gateway( $gateway ) {
		if ( ! \property_exists( $gateway->payment, 'subtotal' ) ) {
			throw new \Exception( 'Payment object from Restrict Content gateway object does not contain a subtotal.' );
		}

		$lines = new PaymentLines();

		// Membership.
		$line = $lines->new_line();

		$line->set_id( (string) $gateway->subscription_id );
		$line->set_sku( null );
		$line->set_type( PaymentLineType::DIGITAL );
		$line->set_name( $gateway->subscription_name );
		$line->set_quantity( 1 );
		$line->set_unit_price( new Money( $gateway->payment->subtotal, $gateway->currency ) );
		$line->set_total_amount( new Money( $gateway->payment->subtotal, $gateway->currency ) );
		$line->set_product_url( null );
		$line->set_image_url( null );
		$line->set_product_category( null );

		// Discount.
		if ( $gateway->discount ) {
			$line = $lines->new_line();

			$name = \__( 'Discount', 'pronamic_ideal' );

			if ( \property_exists( $gateway, 'discount_code' ) ) {
				$name = \sprintf(
					/* translators: %s: Restrict Content Pro discount code */
					__( 'Discount code `%s`', 'pronamic_ideal' ),
					$gateway->discount_code
				);
			}

			$line->set_id( null );
			$line->set_sku( null );
			$line->set_type( PaymentLineType::DISCOUNT );
			$line->set_name( $name );
			$line->set_quantity( 1 );
			$line->set_unit_price( new Money( -$gateway->discount, $gateway->currency ) );
			$line->set_total_amount( new Money( -$gateway->discount, $gateway->currency ) );
			$line->set_product_url( null );
			$line->set_image_url( null );
			$line->set_product_category( null );
		}

		// Fees.
		if ( \property_exists( $gateway->payment, 'fees' ) ) {
			$line = $lines->new_line();

			$line->set_id( null );
			$line->set_sku( null );
			$line->set_type( PaymentLineType::FEE );
			$line->set_name( __( 'Fees', 'pronamic_ideal' ) );
			$line->set_quantity( 1 );
			$line->set_unit_price( new Money( $gateway->payment->fees, $gateway->currency ) );
			$line->set_total_amount( new Money( $gateway->payment->fees, $gateway->currency ) );
			$line->set_product_url( null );
			$line->set_image_url( null );
			$line->set_product_category( null );
		}

		// Result.
		return $lines;
	}

	/**
	 * Create new subscription from Restrict Content Pro gateway object.
	 *
	 * @link https://restrictcontentpro.com/tour/payment-gateways/add-your-own/
	 * @link http://docs.pippinsplugins.com/article/812-payment-gateway-api
	 * @link https://github.com/wp-pay-extensions/woocommerce/blob/develop/src/Gateway.php
	 * @param RCP_Payment_Gateway $gateway Restrict Content Pro gateway object.
	 * @return Subscription|null
	 * @throws \Exception Throws an exception if the initial subscription phase has no end date.
	 */
	public static function new_subscription_from_rcp_gateway( $gateway ) {
		if ( ! $gateway->auto_renew ) {
			return null;
		}

		if ( empty( $gateway->length ) ) {
			return null;
		}

		// Get existing subscription for membership.
		$subscriptions = \get_pronamic_subscriptions_by_source( 'rcp_membership', (string) $gateway->membership->get_id() );

		$subscription = array_shift( $subscriptions );

		if ( null === $subscription ) {
			$subscription = new Subscription();
		}

		$subscription_updater = new SubscriptionUpdater( $gateway->membership, $subscription );

		$subscription_updater->update_pronamic_subscription();

		// Other.
		$subscription->set_description( $gateway->subscription_name );

		// Source.
		$subscription->source    = 'rcp_membership';
		$subscription->source_id = $gateway->membership->get_id();

		return $subscription;
	}

	/**
	 * Store payment ID in Restrict Content Pro membership meta.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/8
	 * @link https://github.com/pronamic/wp-pronamic-pay-woocommerce/blob/8d32295882ac2c4b0c3d3adc6c8355eb13916edb/src/Gateway.php#L446C29-L446C80
	 * @param int     $rcp_membership_id Restrict Content Pro membership ID.
	 * @param Payment $payment           Pronamic payment object.
	 * @return void
	 */
	public static function connect_pronamic_payment_id_to_rcp_payment( $rcp_membership_id, Payment $payment ) {
		\rcp_update_membership_meta( $rcp_membership_id, '_pronamic_payment_id', (string) $payment->get_id() );
	}

	/**
	 * Store payment ID in Restrict Content Pro payment meta.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/8
	 * @link https://github.com/pronamic/wp-pronamic-pay-woocommerce/blob/8d32295882ac2c4b0c3d3adc6c8355eb13916edb/src/Gateway.php#L446C29-L446C80
	 * @param int     $rcp_payment_id Restrict Content Pro payment ID.
	 * @param Payment $payment        Pronamic payment object.
	 * @return void
	 */
	public static function connect_pronamic_payment_id_to_rcp_membership( $rcp_payment_id, Payment $payment ) {
		\rcp_update_payment_meta( $rcp_payment_id, '_pronamic_payment_id', (string) $payment->get_id() );
	}
}
