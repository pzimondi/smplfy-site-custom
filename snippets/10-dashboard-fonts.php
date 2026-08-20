<?php
/**
 * Migrated verbatim from WPCode (28-29 Jul 2026 consolidation).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================================
 *  SNIPPET 3 of 3  (OPTIONAL)
 *  Name:  SMPLFY — Dashboard Fonts (Poppins)
 *  Type:  PHP snippet   ·   Run: Front-end only
 *  ----------------------------------------------------------------------------
 *  The CSS asks for Poppins on headings and falls back to Source Sans Pro
 *  (already loaded by Genesis) if it isn't there. The dashboard looks correct
 *  either way — this snippet just loads Poppins so it matches the mockup.
 *  Skip it if you'd rather not add a Google Fonts request.
 * ========================================================================== */

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'smplfy-poppins',
		'https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap',
		[],
		null
	);
}, 20 );
