<?php
/**
 * Hooks: shortcode rendering and the form submission handler.
 *
 * @package DCTech\PropertyLeads
 */

namespace DCTech\PropertyLeads;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin into WordPress.
 */
final class Plugin {

	private const ACTION = 'dctech_submit_lead';

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( Lead_Service::class, 'register_post_type' ) );
		add_shortcode( 'dctech_lead_form', array( self::class, 'render_form' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( self::class, 'handle_submit' ) );
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle_submit' ) );
	}

	/**
	 * Renders [dctech_lead_form property="Loft 9"].
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string HTML.
	 */
	public static function render_form( $atts = array() ): string {
		$atts = shortcode_atts( array( 'property' => '' ), (array) $atts, 'dctech_lead_form' );

		$html  = self::notice();
		$html .= '<form class="dctech-lead-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		$html .= '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '" />';
		$html .= '<input type="hidden" name="property" value="' . esc_attr( $atts['property'] ) . '" />';
		$html .= wp_nonce_field( self::ACTION, 'dctech_nonce', true, false );
		$html .= '<p><label for="dctech-name">' . esc_html__( 'Name', 'dctech-property-leads' ) . '</label> ';
		$html .= '<input id="dctech-name" name="name" type="text" maxlength="120" required /></p>';
		$html .= '<p><label for="dctech-email">' . esc_html__( 'E-mail', 'dctech-property-leads' ) . '</label> ';
		$html .= '<input id="dctech-email" name="email" type="email" required /></p>';
		$html .= '<p><label for="dctech-message">' . esc_html__( 'Message', 'dctech-property-leads' ) . '</label> ';
		$html .= '<textarea id="dctech-message" name="message" rows="4"></textarea></p>';
		// Honeypot: hidden from people, tempting to bots.
		$html .= '<p hidden aria-hidden="true"><label>Website <input name="website" type="text" tabindex="-1" autocomplete="off" /></label></p>';
		$html .= '<p><button type="submit">' . esc_html__( 'Send', 'dctech-property-leads' ) . '</button></p>';
		$html .= '</form>';

		return $html;
	}

	/**
	 * Handles the POST to admin-post.php, then redirects back with a status flag.
	 *
	 * @return void
	 */
	public static function handle_submit(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- The nonce is verified on the next lines.
		$nonce = isset( $_POST['dctech_nonce'] ) && is_string( $_POST['dctech_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['dctech_nonce'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dctech-property-leads' ), '', array( 'response' => 403 ) );
		}

		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		if ( '' !== self::field( 'website' ) ) {
			// Honeypot filled: pretend success, store nothing.
			wp_safe_redirect( add_query_arg( 'dctech_lead', 'ok', $redirect ) );
			exit;
		}

		$result = Lead_Service::create(
			array(
				'name'     => self::field( 'name' ),
				'email'    => self::field( 'email' ),
				'message'  => self::field( 'message' ),
				'property' => self::field( 'property' ),
			)
		);

		wp_safe_redirect( add_query_arg( 'dctech_lead', is_wp_error( $result ) ? 'error' : 'ok', $redirect ) );
		exit;
	}

	/**
	 * Reads one sanitised string field from the already nonce-verified POST body.
	 *
	 * @param string $key Field name.
	 * @return string
	 */
	private static function field( string $key ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Only called after handle_submit() verified the nonce.
		if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) {
			return '';
		}
		return sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Result banner shown after the redirect.
	 *
	 * @return string HTML.
	 */
	private static function notice(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag, changes no state.
		$status = isset( $_GET['dctech_lead'] ) && is_string( $_GET['dctech_lead'] ) ? sanitize_key( wp_unslash( $_GET['dctech_lead'] ) ) : '';
		if ( 'ok' === $status ) {
			return '<p role="status">' . esc_html__( 'Thanks! We will be in touch.', 'dctech-property-leads' ) . '</p>';
		}
		if ( 'error' === $status ) {
			return '<p role="alert">' . esc_html__( 'Please check your name and e-mail address and try again.', 'dctech-property-leads' ) . '</p>';
		}
		return '';
	}
}
