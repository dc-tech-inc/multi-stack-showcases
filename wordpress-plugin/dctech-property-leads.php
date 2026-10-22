<?php
/**
 * Plugin Name:       DC Tech Property Leads
 * Description:       Lead-capture form shortcode for property listings. Leads are stored as private posts.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            DC TECH INC
 * License:           MIT
 * Text Domain:       dctech-property-leads
 *
 * @package DCTech\PropertyLeads
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-lead-service.php';
require_once __DIR__ . '/includes/class-plugin.php';

\DCTech\PropertyLeads\Plugin::init();
