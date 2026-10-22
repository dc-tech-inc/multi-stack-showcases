<?php
/**
 * Integration tests: run inside the WordPress test suite (real WP + MySQL).
 *
 * @package DCTech\PropertyLeads
 */

namespace DCTech\PropertyLeads\Tests;

use DCTech\PropertyLeads\Lead_Service;
use DCTech\PropertyLeads\Plugin;
use WP_UnitTestCase;

class LeadServiceTest extends WP_UnitTestCase {

	public function test_post_type_is_registered_and_not_public(): void {
		$type = get_post_type_object( Lead_Service::POST_TYPE );
		$this->assertNotNull( $type );
		$this->assertFalse( $type->public );
	}

	public function test_create_stores_private_lead_with_meta(): void {
		$id = Lead_Service::create(
			array(
				'name'     => 'Alex Example',
				'email'    => 'alex@example.com',
				'message'  => 'Is parking included?',
				'property' => 'Loft 9',
			)
		);

		$this->assertIsInt( $id );
		$this->assertSame( 'private', get_post_status( $id ) );
		$this->assertSame( 'Alex Example', get_the_title( $id ) );
		$this->assertSame( 'alex@example.com', get_post_meta( $id, '_dctech_lead_email', true ) );
		$this->assertSame( 'Loft 9', get_post_meta( $id, '_dctech_lead_property', true ) );
	}

	public function test_create_reports_each_invalid_field(): void {
		$result = Lead_Service::create(
			array(
				'name'  => '   ',
				'email' => 'not-an-email',
			)
		);

		$this->assertWPError( $result );
		$this->assertContains( 'name', $result->get_error_codes() );
		$this->assertContains( 'email', $result->get_error_codes() );
	}

	public function test_create_strips_markup_from_name(): void {
		$id = Lead_Service::create(
			array(
				'name'  => '<b>Sam</b><script>alert(1)</script>',
				'email' => 'sam@example.com',
			)
		);

		$this->assertIsInt( $id );
		$this->assertStringNotContainsString( '<', get_the_title( $id ) );
	}

	public function test_create_fires_action_only_on_success(): void {
		$before = did_action( 'dctech_lead_created' );
		Lead_Service::create( array( 'name' => '' ) );
		$this->assertSame( $before, did_action( 'dctech_lead_created' ) );

		Lead_Service::create(
			array(
				'name'  => 'Jo',
				'email' => 'jo@example.com',
			)
		);
		$this->assertSame( $before + 1, did_action( 'dctech_lead_created' ) );
	}

	public function test_shortcode_is_registered_and_renders_nonce_form(): void {
		$this->assertTrue( shortcode_exists( 'dctech_lead_form' ) );

		$html = do_shortcode( '[dctech_lead_form property="Loft 9"]' );
		$this->assertStringContainsString( 'name="dctech_nonce"', $html );
		$this->assertStringContainsString( 'value="dctech_submit_lead"', $html );
		$this->assertStringContainsString( 'value="Loft 9"', $html );
	}

	public function test_shortcode_escapes_attribute_values(): void {
		$html = Plugin::render_form( array( 'property' => '"><script>alert(1)</script>' ) );

		$this->assertStringNotContainsString( '<script>', $html );
	}
}
