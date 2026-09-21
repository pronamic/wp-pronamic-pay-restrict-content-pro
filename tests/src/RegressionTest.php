<?php

declare(strict_types=1);

namespace {
	function __( $text ) {
		return $text;
	}

	function get_pronamic_subscriptions_by_source( $source, $source_id ) {
		return null !== TestState::$subscription ? [ TestState::$subscription ] : [];
	}

	function rcp_get_membership( $membership_id ) {
		return TestState::$membership;
	}

	final class TestState {
		public static $subscription;

		public static $membership;
	}

	final class DummyMembership {
		public $status = 'active';

		public $expiration_time = false;

		public $renew_calls = [];

		public function get_status() {
			return $this->status;
		}

		public function get_expiration_time() {
			return $this->expiration_time;
		}

		public function renew( $renewal, $status, $expiration ) {
			$this->renew_calls[] = [ $renewal, $status, $expiration ];
		}
	}

	class RCP_Payment_Gateway {}

	class RCP_Payments {
		public static $payment;

		public static $updates = [];

		public function get_payment( $payment_id ) {
			return self::$payment;
		}

		public function update( $payment_id, $data ) {
			self::$updates[] = [ $payment_id, $data ];
		}
	}

	class RCP_Payment_Gateways {}

	class RCP_Membership {}

	class WP_Query {}
}

namespace Pronamic\WordPress\DateTime {
	class DateTime extends \DateTime {
		public const MYSQL = 'Y-m-d H:i:s';
	}
}

namespace Pronamic\WordPress\Money {
	class Money {
		public function __construct( private $value, private $currency ) {}

		public function get_value() {
			return $this->value;
		}
	}
}

namespace Pronamic\WordPress\Number {
	class Number {
		public function __construct( private $value ) {}
	}
}

namespace Pronamic\WordPress\Pay {
	class ContactName {
		public function set_first_name( $first_name ) {}
		public function set_last_name( $last_name ) {}
	}

	class Customer {
		private $user_id;

		public function set_name( $name ) {}
		public function set_email( $email ) {}
		public function set_user_id( $user_id ) {
			$this->user_id = $user_id;
		}

		public function get_user_id() {
			return $this->user_id;
		}
	}

	abstract class AbstractPluginIntegration {}
}

namespace Pronamic\WordPress\Pay\Payments {
	class PaymentLineType {
		public const DIGITAL  = 'digital';
		public const DISCOUNT = 'discount';
		public const FEE      = 'fee';
	}

	class PaymentLine {
		public function set_id( $id ) {}
		public function set_sku( $sku ) {}
		public function set_type( $type ) {}
		public function set_name( $name ) {}
		public function set_quantity( $quantity ) {}
		public function set_unit_price( $price ) {}
		public function set_total_amount( $amount ) {}
		public function set_product_url( $url ) {}
		public function set_image_url( $url ) {}
		public function set_product_category( $category ) {}
	}

	class PaymentLines implements \IteratorAggregate {
		private $lines = [];

		public function new_line() {
			$line          = new PaymentLine();
			$this->lines[] = $line;

			return $line;
		}

		public function get_amount() {
			return new \Pronamic\WordPress\Money\Money( 0, 'EUR' );
		}

		public function getIterator(): \Traversable {
			return new \ArrayIterator( $this->lines );
		}
	}

	class PaymentStatus {
		public const CANCELLED = 'cancelled';
		public const EXPIRED   = 'expired';
		public const FAILURE   = 'failure';
		public const OPEN      = 'open';
		public const SUCCESS = 'success';
	}

	class Payment {
		public $title;
		public $source;
		public $source_id;
		public $lines;

		private $customer;
		private $periods = [];
		private $status;
		private $transaction_id;

		public function __construct( $status = null, $source_id = null, $transaction_id = null ) {
			$this->status         = $status;
			$this->source_id      = $source_id;
			$this->transaction_id = $transaction_id;
		}

		public function set_description( $description ) {}
		public function set_customer( $customer ) {
			$this->customer = $customer;
		}

		public function get_customer() {
			return $this->customer;
		}

		public function add_subscription( $subscription ) {}

		public function add_period( $period ) {
			$this->periods[] = $period;
		}

		public function get_periods() {
			return $this->periods;
		}

		public function set_total_amount( $amount ) {}
		public function set_meta( $key, $value ) {}

		public function get_source_id() {
			return $this->source_id;
		}

		public function get_status() {
			return $this->status;
		}

		public function get_transaction_id() {
			return $this->transaction_id;
		}
	}
}

namespace Pronamic\WordPress\Pay\Subscriptions {
	class Subscription {
		public $period_calls = [];

		public function __construct( private $start_date = null, private $next_payment_date = null, private $period = null ) {}

		public function get_start_date() {
			return $this->start_date;
		}

		public function get_next_payment_date() {
			return $this->next_payment_date;
		}

		public function get_period_for_date( $date ) {
			$this->period_calls[] = $date;

			return $this->period;
		}

		public function set_description( $description ) {}
	}

	class SubscriptionStatus {}
}

namespace Pronamic\WordPress\Pay\Extensions\RestrictContent {
	class SubscriptionUpdater {
		public function __construct( $membership, $subscription ) {}
		public function update_pronamic_subscription() {}
	}

	class PaymentStatus {
		public const COMPLETE = 'complete';

		public static function from_core( $status ) {
			return $status;
		}
	}

	class MembershipStatus {
		public const ACTIVE = 'active';
	}
}

namespace {
	require_once '/home/runner/work/wp-pronamic-pay-restrict-content-pro/wp-pronamic-pay-restrict-content-pro/src/Util.php';
	require_once '/home/runner/work/wp-pronamic-pay-restrict-content-pro/wp-pronamic-pay-restrict-content-pro/src/Extension.php';

	use PHPUnit\Framework\TestCase;
	use Pronamic\WordPress\Pay\Extensions\RestrictContent\Extension;
	use Pronamic\WordPress\Pay\Extensions\RestrictContent\Util;
	use Pronamic\WordPress\Pay\Payments\Payment;
	use Pronamic\WordPress\Pay\Payments\PaymentStatus as CorePaymentStatus;
	use Pronamic\WordPress\Pay\Subscriptions\Subscription;

	final class RegressionTest extends TestCase {
		protected function setUp(): void {
			TestState::$subscription = null;
			TestState::$membership   = null;
			RCP_Payments::$payment   = null;
			RCP_Payments::$updates   = [];
		}

		public function test_new_payment_uses_subscription_next_payment_date_for_period_selection(): void {
			$start_date        = new \DateTimeImmutable( '2021-02-03 14:22:00' );
			$next_payment_date = new \DateTimeImmutable( '2025-11-30 14:22:00' );
			$period            = new class() {
				public function get_end_date() {
					return new \DateTimeImmutable( '2026-11-30 14:22:00' );
				}
			};

			$subscription            = new Subscription( $start_date, $next_payment_date, $period );
			TestState::$subscription = $subscription;

			$gateway = $this->new_gateway();

			$payment = Util::new_payment_from_rcp_gateway( $gateway );

			$this->assertSame( [ $next_payment_date ], $subscription->period_calls );
			$this->assertCount( 1, $payment->get_periods() );
		}

		public function test_new_payment_falls_back_to_subscription_start_date_when_next_payment_date_is_missing(): void {
			$start_date = new \DateTimeImmutable( '2021-02-03 14:22:00' );
			$period     = new class() {
				public function get_end_date() {
					return new \DateTimeImmutable( '2022-02-03 14:22:00' );
				}
			};

			$subscription            = new Subscription( $start_date, null, $period );
			TestState::$subscription = $subscription;

			$gateway = $this->new_gateway();

			Util::new_payment_from_rcp_gateway( $gateway );

			$this->assertSame( [ $start_date ], $subscription->period_calls );
		}

		public function test_successful_payment_renews_active_membership_when_payment_period_extends_expiration(): void {
			$period = new class() {
				public function get_end_date() {
					return new \DateTimeImmutable( '2026-11-30 14:22:00' );
				}
			};

			$payment = new Payment( CorePaymentStatus::SUCCESS, 123, 'tr_123' );
			$payment->add_period( $period );

			RCP_Payments::$payment = (object) [
				'membership_id' => 456,
				'status'        => 'pending',
			];

			$membership                  = new DummyMembership();
			$membership->status          = 'active';
			$membership->expiration_time = ( new \DateTimeImmutable( '2025-11-30 14:22:00' ) )->getTimestamp();
			TestState::$membership       = $membership;

			$extension = ( new \ReflectionClass( Extension::class ) )->newInstanceWithoutConstructor();
			$extension->payment_status_update( $payment );

			$this->assertCount( 1, $membership->renew_calls );
			$this->assertSame( '2026-11-30 14:22:00', $membership->renew_calls[0][2] );
		}

		private function new_gateway(): RCP_Payment_Gateway {
			return new class() extends RCP_Payment_Gateway {
				public $payment;
				public $subscription_name = 'Membership';
				public $subscription_data = [];
				public $email = 'member@example.com';
				public $user_id = 1;
				public $currency = 'EUR';
				public $initial_amount = 47.99;
				public $auto_renew = true;
				public $length = 1;
				public $membership;
				public $subscription_id = 12;
				public $discount = 0;

				public function __construct() {
					$this->payment = (object) [
						'id'       => 123,
						'subtotal' => 47.99,
					];

					$this->membership = new class() {
						public function get_id() {
							return 456;
						}
					};
				}

				public function is_trial() {
					return false;
				}
			};
		}
	}
}
