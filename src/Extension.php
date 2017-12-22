<?php

/**
 * Title: Restrict Content Pro iDEAL Add-On
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_RCP_Extension {
	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		// The "plugins_loaded" is one of the earliest hooks after EDD is set up
		add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ) );
	}

	//////////////////////////////////////////////////

	/**
	 * Test to see if the Restrict Content Pro plugin is active, then add all actions.
	 */
	public static function plugins_loaded() {
		if ( Pronamic_WP_Pay_Extensions_RCP_RestrictContentPro::is_active() ) {
			/*
			 * Gateways
			 * @since 1.1.0
			 */
			new Pronamic_WP_Pay_Extensions_RCP_Gateway();
			new Pronamic_WP_Pay_Extensions_RCP_BankTransferGateway();
			new Pronamic_WP_Pay_Extensions_RCP_BitcoinGateway();
			new Pronamic_WP_Pay_Extensions_RCP_CreditCardGateway();
			new Pronamic_WP_Pay_Extensions_RCP_DirectDebitGateway();
			new Pronamic_WP_Pay_Extensions_RCP_IDealGateway();
			new Pronamic_WP_Pay_Extensions_RCP_BancontactGateway();
			new Pronamic_WP_Pay_Extensions_RCP_SofortGateway();
			new Pronamic_WP_Pay_Extensions_RCP_DirectDebitIDealGateway();
			new Pronamic_WP_Pay_Extensions_RCP_DirectDebitBancontactGateway();
			new Pronamic_WP_Pay_Extensions_RCP_DirectDebitSofortGateway();

			add_action( 'pronamic_payment_status_update_restrictcontentpro', array( __CLASS__, 'status_update' ), 10, 1 );
			add_filter( 'pronamic_payment_redirect_url_restrictcontentpro', array( __CLASS__, 'redirect_url' ), 10, 2 );
			add_filter( 'pronamic_payment_source_text_restrictcontentpro', array( __CLASS__, 'source_text' ), 10, 2 );

			add_filter( 'rcp_payment_status_label', array( __CLASS__, 'rcp_status_label_cancelled' ), 10, 2 );

			add_action( 'rcp_set_status', array( __CLASS__, 'rcp_set_status' ), 10, 3 );

			// Icons
			add_filter( 'rcp_accepted_payment_icons', array( __CLASS__, 'accepted_payment_icons' ) );
		}

		add_filter( 'pronamic_payment_source_description_restrictcontentpro', array( __CLASS__, 'source_description' ), 10, 2 );
		add_filter( 'pronamic_payment_source_url_restrictcontentpro', array( __CLASS__, 'source_url' ), 10, 2 );
	}

	/**
	 * Restrict Content Pro payment status 'Cancelled' label.
	 */
	public static function rcp_status_label_cancelled( $label, $status ) {
		switch ( $status ) {
			case 'cancelled':
				$label = _x( 'Cancelled', 'Payment status', 'pronamic_ideal' );

				break;
			case 'expired':
				$label = _x( 'Expired', 'Payment status', 'pronamic_ideal' );

				break;
			case 'failed':
				$label = _x( 'Failed', 'Payment status', 'pronamic_ideal' );

				break;
		}

		return $label;
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @param string                  $url
	 * @param Pronamic_WP_Pay_Payment $payment
	 * @return string
	 */
	public static function redirect_url( $url, $payment ) {
		$source_id = $payment->get_source_id();

		$data = new Pronamic_WP_Pay_Extensions_RCP_PaymentData( $source_id, array() );

		$url = $data->get_normal_return_url();

		switch ( $payment->get_status() ) {
			case Pronamic_WP_Pay_Statuses::CANCELLED:
				$url = $data->get_cancel_url();

				break;
			case Pronamic_WP_Pay_Statuses::EXPIRED:
				$url = $data->get_error_url();

				break;
			case Pronamic_WP_Pay_Statuses::FAILURE:
				$url = $data->get_error_url();

				break;
			case Pronamic_WP_Pay_Statuses::SUCCESS:
				$url = $data->get_success_url();

				break;
			case Pronamic_WP_Pay_Statuses::OPEN:
				// Nothing to do?

				break;
		}

		return $url;
	}

	/**
	 * Update the status of the specified payment
	 *
	 * @param Pronamic_Pay_Payment $payment
	 */
	public static function status_update( Pronamic_Pay_Payment $payment ) {
		$source_id = $payment->get_source_id();

		$payments    = new RCP_Payments();
		$rcp_payment = $payments->get_payment( $source_id );

		// Only update if order is not completed
		if ( ! $payment->get_subscription() && Pronamic_WP_Pay_Extensions_RCP_RestrictContentPro::PAYMENT_STATUS_COMPLETE === $rcp_payment->status ) {
			return;
		}

		$data = new Pronamic_WP_Pay_Extensions_RCP_PaymentData( $source_id, array() );

		$member = new RCP_Member( $data->get_user_id() );

		switch ( $payment->get_status() ) {
			case Pronamic_WP_Pay_Statuses::CANCELLED:
				$payments->update( $source_id, array( 'status' => Pronamic_WP_Pay_Extensions_RCP_RestrictContentPro::PAYMENT_STATUS_CANCELLED ) );

				if ( $member ) {
					$member->cancel();
				}

				break;
			case Pronamic_WP_Pay_Statuses::EXPIRED:
				$payments->update( $source_id, array( 'status' => Pronamic_WP_Pay_Extensions_RCP_RestrictContentPro::PAYMENT_STATUS_EXPIRED ) );

				if ( $member ) {
					$member->cancel();
				}

				break;
			case Pronamic_WP_Pay_Statuses::FAILURE:
				$payments->update( $source_id, array( 'status' => Pronamic_WP_Pay_Extensions_RCP_RestrictContentPro::PAYMENT_STATUS_FAILED ) );

				if ( $member ) {
					$member->cancel();
				}

				break;
			case Pronamic_WP_Pay_Statuses::SUCCESS:
				$payments->update( $source_id, array( 'status' => Pronamic_WP_Pay_Extensions_RCP_RestrictContentPro::PAYMENT_STATUS_COMPLETE ) );

				if ( $member ) {
					$auto_renew = false;

					if ( 'yes' === get_user_meta( $data->get_user_id(), 'rcp_recurring', true ) ) {
						$auto_renew = true;
					}

					$member->renew( $auto_renew, 'active' );
				}

				break;
			case Pronamic_WP_Pay_Statuses::OPEN:
				// Nothing to do?

				break;
		}
	}

	/**
	 * Restrict Content Pro user subscription status updated.
	 */
	public static function rcp_set_status( $new_status, $user_id, $old_status ) {
		$subscription = Pronamic_WP_Pay_Extensions_RCP_Util::get_subscription_by_user( $user_id );

		if ( ! $subscription ) {
			return;
		}

		if ( $new_status === $old_status ) {
			return;
		}

		$note = null;

		switch ( $new_status ) {
			case 'active':
				$status = Pronamic_WP_Pay_Statuses::ACTIVE;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription activated by %s', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case 'cancelled':
				$status = Pronamic_WP_Pay_Statuses::CANCELLED;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription canceled by %s', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case 'free':
			case 'expired':
				$status = Pronamic_WP_Pay_Statuses::COMPLETED;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription completed by %s', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;

			case 'pending':
				$status = Pronamic_WP_Pay_Statuses::OPEN;

				$note = sprintf(
					/* translators: %s: Restrict Content Pro */
					__( 'Subscription pending by %s', 'pronamic_ideal' ),
					__( 'Restrict Content Pro', 'pronamic_ideal' )
				);

				break;
		}

		if ( isset( $status ) ) {
			$subscription->update_status( $status, $note );

			Pronamic_WP_Pay_Plugin::update_subscription( $subscription, false );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Source column
	 *
	 * @param string $text
	 * @param Pronamic_WP_Pay_Payment $payment
	 *
	 * @return string $text
	 */
	public static function source_text( $text, Pronamic_WP_Pay_Payment $payment ) {
		$text = __( 'Restrict Content Pro', 'pronamic_ideal' ) . '<br />';

		$source_url = add_query_arg(
			'user_id',
			$payment->post->post_author,
			menu_page_url( 'rcp-payments', false )
		);

		$text .= sprintf(
			'<a href="%s">%s</a>',
			esc_url( $source_url ),
			/* translators: %s: source id */
			sprintf( __( 'Payment %s', 'pronamic_ideal' ), $payment->source_id )
		);

		return $text;
	}

	/**
	 * Source description.
	 */
	public static function source_description( $description, Pronamic_Pay_Payment $payment ) {
		$description = __( 'Restrict Content Pro Order', 'pronamic_ideal' );

		return $description;
	}

	/**
	 * Source URL.
	 */
	public static function source_url( $url, Pronamic_Pay_Payment $payment ) {
		$url = add_query_arg(
			'user_id',
			$payment->post->post_author,
			menu_page_url( 'rcp-payments', false )
		);

		return $url;
	}

	//////////////////////////////////////////////////

	/**
	 * Accepted payment icons
	 *
	 * @see https://github.com/restrictcontentpro/restrict-content-pro/blob/2.1.3/includes/admin/settings/register-settings.php#L261-L268
	 * @see https://github.com/restrictcontentpro/restrict-content-pro/blob/2.1.3/includes/checkout/template.php#L573-L609
	 *
	 * @param array $icons
	 * @return array
	 */
	public static function accepted_payment_icons( $icons ) {
		// iDEAL
		$key = plugins_url( 'images/ideal/icon-64x48.png', Pronamic_WP_Pay_Plugin::$file );

		$icons[ $key ] = __( 'iDEAL', 'pronamic_ideal' );

		// Bancontact/Mister Cash
		$key = plugins_url( 'images/bancontact/icon-64x48.png', Pronamic_WP_Pay_Plugin::$file );

		$icons[ $key ] = __( 'Bancontact', 'pronamic_ideal' );

		// Bitcoin
		$key = plugins_url( 'images/bitcoin/icon-64x48.png', Pronamic_WP_Pay_Plugin::$file );

		$icons[ $key ] = __( 'Bitcoin', 'pronamic_ideal' );

		// Sofort
		$key = plugins_url( 'images/sofort/icon-64x48.png', Pronamic_WP_Pay_Plugin::$file );

		$icons[ $key ] = __( 'SOFORT Überweisung', 'pronamic_ideal' );

		return $icons;
	}
}
