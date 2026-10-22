<?php
/**
 * Lead storage: validation, sanitisation and persistence.
 *
 * @package DCTech\PropertyLeads
 */

namespace DCTech\PropertyLeads;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Creates leads as private posts of a non-public post type.
 */
final class Lead_Service {

	public const POST_TYPE = 'dctech_lead';

	/**
	 * Registers the admin-only lead post type.
	 *
	 * @return void
	 */
	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Leads', 'dctech-property-leads' ),
					'singular_name' => __( 'Lead', 'dctech-property-leads' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => false,
				'menu_icon'    => 'dashicons-id',
				'supports'     => array( 'title', 'editor' ),
			)
		);
	}

	/**
	 * Validates and stores a lead.
	 *
	 * @param array<string, string> $data Keys: name, email, message, property.
	 * @return int|WP_Error New lead post ID, or a WP_Error with one code per invalid field.
	 */
	public static function create( array $data ): int|WP_Error {
		$name     = sanitize_text_field( $data['name'] ?? '' );
		$email    = sanitize_email( $data['email'] ?? '' );
		$message  = sanitize_textarea_field( $data['message'] ?? '' );
		$property = sanitize_text_field( $data['property'] ?? '' );

		$errors = new WP_Error();
		if ( '' === $name || strlen( $name ) > 120 ) {
			$errors->add( 'name', __( 'Please enter your name (up to 120 characters).', 'dctech-property-leads' ) );
		}
		if ( ! is_email( $email ) ) {
			$errors->add( 'email', __( 'Please enter a valid e-mail address.', 'dctech-property-leads' ) );
		}
		if ( $errors->has_errors() ) {
			return $errors;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'private',
				'post_title'   => $name,
				'post_content' => $message,
				'meta_input'   => array(
					'_dctech_lead_email'    => $email,
					'_dctech_lead_property' => $property,
				),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		/**
		 * Fires after a lead is stored, e.g. to forward it to a CRM.
		 *
		 * @param int $id Lead post ID.
		 */
		do_action( 'dctech_lead_created', $id );

		return $id;
	}
}
