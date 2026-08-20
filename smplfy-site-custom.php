<?php
/**
 * Plugin Name: SMPLFY Site Custom
 * Description: Site styling (formerly Customizer Additional CSS) and front-end PHP snippets (formerly WPCode), consolidated into one deployable plugin.
 * Version: 1.0.0
 * Author: Simplify Biz
 * Author URI: https://simplifybiz.com
 * Requires PHP: 7.4
 *
 * WHY THIS PLUGIN EXISTS
 * - Additional CSS hit the Customizer's practical size ceiling (~500KB)
 *   and changeset saves started failing. The stylesheet now ships as a
 *   real file: assets/css/smplfy-custom.css.
 * - The WPCode snippets move here one-for-one (snippets/*.php, loaded
 *   in filename order) so styling + behaviour deploy together through
 *   the GitHub -> FTP pipeline instead of two admin screens.
 *
 * CASCADE POSITION (deliberate): Additional CSS used to print last in
 * wp_head, which is why its overrides won ties. The enqueue below runs
 * at priority 20 and, when smplfy-appsimplifybiz's base stylesheet is
 * registered (handle: smplfy-frontend-styles), declares it as a
 * dependency — so this file always prints after it, exactly as before.
 * The dependency is conditional so a deactivated smplfy-appsimplifybiz
 * can never silently suppress this stylesheet.
 *
 * VERSIONING: filemtime() — every FTP deploy of the CSS changes ?ver=
 * automatically, so SG Optimizer / browser caches cannot mask a deploy.
 *
 * ROLLOUT ORDER (do not skip):
 *   1. Deploy + activate this plugin.
 *   2. Verify styling and each snippet feature on the live pages.
 *   3. Deactivate the corresponding WPCode snippets ONE AT A TIME.
 *   4. Empty Customizer -> Additional CSS last, after the stylesheet is
 *      confirmed loading (view-source: smplfy-custom.css?ver=...).
 */

namespace SmplfySiteCustom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SMPLFY_SITE_CUSTOM_DIR', plugin_dir_path( __FILE__ ) );
define( 'SMPLFY_SITE_CUSTOM_URL', plugin_dir_url( __FILE__ ) );

/**
 * The site stylesheet — the full former Additional CSS, byte-identical.
 */
function enqueue_site_styles(): void {
	$relative = 'assets/css/smplfy-custom.css';
	$path     = SMPLFY_SITE_CUSTOM_DIR . $relative;

	if ( ! file_exists( $path ) ) {
		return;
	}

	$deps = wp_style_is( 'smplfy-frontend-styles', 'registered' )
		? array( 'smplfy-frontend-styles' )
		: array();

	wp_enqueue_style(
		'smplfy-site-custom',
		SMPLFY_SITE_CUSTOM_URL . $relative,
		$deps,
		(string) filemtime( $path )
	);
}
add_action( 'wp_enqueue_scripts', 'SmplfySiteCustom\\enqueue_site_styles', 20 );

/**
 * The former WPCode snippets, one file each, loaded in filename order.
 * Files are verbatim migrations in the global namespace, exactly as
 * they ran in WPCode. Number prefixes control load order.
 */
foreach ( glob( SMPLFY_SITE_CUSTOM_DIR . 'snippets/*.php' ) ?: array() as $smplfy_snippet ) {
	require_once $smplfy_snippet;
}
unset( $smplfy_snippet );
