<?php
/**
 * Util
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\RestrictContentPro
 */

namespace Pronamic\WordPress\Pay\Extensions\RestrictContentPro;

use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Address;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\ContactName;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use Pronamic\WordPress\Pay\Payments\PaymentLineType;
use WP_Query;
use RCP_Payment_Gateway;

/**
 * Util
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
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
	 */
	public static function new_payment_from_rcp_gateway( $gateway ) {
		// Payment.
		$payment = new Payment();

		// Title.
		$payment->title = sprintf(
			/* translators: %s: Restrict Content Pro payment ID */
			__( 'Restrict Content Pro payment %s', 'pronamic_ideal' ),
			$gateway->payment->id
		);

		// Description.
		$payment->description = $gateway->subscription_name;

		// Source.
		$payment->source    = 'rcp_payment';
		$payment->source_id = $gateway->payment->id;

		// Issuer.
		if ( array_key_exists( 'post_data', $gateway->subscription_data ) ) {
			$post_data = $gateway->subscription_data['post_data'];

			if ( array_key_exists( 'pronamic_ideal_issuer_id', $post_data ) ) {
				$payment->issuer = $post_data['pronamic_ideal_issuer_id'];
			}
		}

		// Customer.
		$customer = self::new_customer_from_rcp_gateway( $gateway );

		$payment->set_customer( $customer );

		// Payment lines.
		$payment->lines = self::new_payment_lines_from_rcp_gateway( $gateway );

		// Subscription.
		$payment->subscription = self::new_subscription_from_rcp_gateway( $gateway );

		// Total amount.
		$payment->set_total_amount(
			new TaxedMoney(
				$gateway->initial_amount,
				$gateway->currency
			)
		);

		// Result.
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
	 * @return Payment
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
	 */
	public static function new_payment_lines_from_rcp_gateway( $gateway ) {
		$lines = new PaymentLines();

		// Membership.
		$line = $lines->new_line();

		$line->set_id( $gateway->subscription_id );
		$line->set_sku( null );
		$line->set_type( PaymentLineType::DIGITAL );
		$line->set_name( $gateway->subscription_name );
		$line->set_quantity( 1 );
		$line->set_unit_price( new TaxedMoney( $gateway->payment->subtotal, $gateway->currency ) );
		$line->set_total_amount( new TaxedMoney( $gateway->payment->subtotal, $gateway->currency ) );
		$line->set_product_url( null );
		$line->set_image_url( null );
		$line->set_product_category( null );

		// Discount.
		if ( $gateway->discount ) {
			$line = $lines->new_line();

			$line->set_id( null );
			$line->set_sku( null );
			$line->set_type( PaymentLineType::DISCOUNT );
			$line->set_name(
				sprintf(
					/* translators: %s: Restrict Content Pro discount code */
					__( 'Discount code `%s`', 'pronamic_ideal' ),
					$gateway->discount_code
				)
			);
			$line->set_quantity( 1 );
			$line->set_unit_price( new TaxedMoney( -$gateway->discount, $gateway->currency ) );
			$line->set_total_amount( new TaxedMoney( -$gateway->discount, $gateway->currency ) );
			$line->set_product_url( null );
			$line->set_image_url( null );
			$line->set_product_category( null );
		}

		// Fees.
		if ( $gateway->payment->fees ) {
			$line = $lines->new_line();

			$line->set_id( null );
			$line->set_sku( null );
			$line->set_type( PaymentLineType::FEE );
			$line->set_name( __( 'Fees', 'pronamic_ideal' ) );
			$line->set_quantity( 1 );
			$line->set_unit_price( new TaxedMoney( $gateway->payment->fees, $gateway->currency ) );
			$line->set_total_amount( new TaxedMoney( $gateway->payment->fees, $gateway->currency ) );
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
	 *
	 * @param RCP_Payment_Gateway $gateway Restrict Content Pro gateway object.
	 * @return Subscription|null
	 */
	public static function new_subscription_from_rcp_gateway( $gateway ) {
		if ( ! $gateway->auto_renew ) {
			return null;
		}

		if ( empty( $gateway->length ) ) {
			return null;
		}

		$subscription = new Subscription();

		$subscription->frequency       = null;
		$subscription->interval        = $gateway->length;
		$subscription->interval_period = Core_Util::to_period( $gateway->length_unit );
		$subscription->description     = $gateway->subscription_name;

		// Source.
		$subscription->source    = 'rcp_membership';
		$subscription->source_id = $gateway->membership->get_id();

		// Total amount.
		$subscription->set_total_amount(
			new TaxedMoney(
				$gateway->amount,
				$gateway->currency
			)
		);

		// Result.
		return $subscription;
	}
}
