<?php
/**
 * Migrated verbatim from WPCode (28-29 Jul 2026 consolidation).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GRAVITYVIEW: Single Entry Back Link → Dashboard (excl. coach Views)
 *
 * GravityView's single-entry back link defaults to the View's own directory
 * page. This points it at /dashboard/ instead and relabels it to match.
 *
 * Coach-facing Views are excluded and keep the default back-to-directory
 * behaviour, so a coach reviewing a client returns to the Coaching Hub.
 *
 * Filters: gravityview/template/links/back/url
 *          gravityview/template/links/back/label
 */

if ( ! function_exists( 'smplfy_gv_back_is_coach_view' ) ) {
	/**
	 * Is the current single entry being rendered by a coach-facing View?
	 *
	 * @param \GV\Template_Context|null $context Template context, when available.
	 *
	 * @return bool
	 */
	function smplfy_gv_back_is_coach_view( $context = null ) {

		$exclude = array(
			101080, // COACH: View Operations Process
			101108, // Coach Invite
		);

		$view_id = 0;

		if ( $context instanceof \GV\Template_Context && $context->view ) {
			$view_id = (int) $context->view->ID;
		} elseif ( function_exists( 'gravityview_get_view_id' ) ) {
			$view_id = (int) gravityview_get_view_id();
		}

		return in_array( $view_id, $exclude, true );
	}
}

add_filter(
	'gravityview/template/links/back/url',
	function ( $href, $context = null ) {

		if ( smplfy_gv_back_is_coach_view( $context ) ) {
			return $href;
		}

		return home_url( '/dashboard/' );
	},
	10,
	2
);

add_filter(
	'gravityview/template/links/back/label',
	function ( $label, $context = null ) {

		if ( smplfy_gv_back_is_coach_view( $context ) ) {
			return $label;
		}

		return '&larr; Back to Dashboard';
	},
	10,
	2
);
