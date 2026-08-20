<?php
/**
 * Migrated verbatim from WPCode (28-29 Jul 2026 consolidation).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================================
 *  SNIPPET 2 of 3
 *  Name:  SMPLFY — Process Status
 *  Type:  PHP snippet   ·   Run: Front-end only
 *  ----------------------------------------------------------------------------
 *  Gives each process card TWO pills: whether the form was submitted, and the
 *  process's own parent Status radio.
 *
 *  WHY THIS EXISTS: "Submitted" and "done" are different facts. A user can
 *  submit the Sales form with Status = ToDo — the form is filled in, the work
 *  hasn't started. The Status radio is the user's own answer to "have I
 *  actually implemented this process", which is what the dashboard is for.
 *
 *  VOCABULARY: taken from the legend printed inside the forms themselves, so
 *  the dashboard and the form say the same words. The radio's own choice labels
 *  are Font Awesome circles, not text:
 *
 *      <i class="fa-regular fa-circle">           ToDo   -> "Not Started"
 *      <i class="fa-solid fa-circle-half-stroke"> Doing  -> "In Progress"
 *      <i class="fa-solid fa-circle">             Done   -> "Fully Implemented"
 *
 *  READ ONLY. The stored values ToDo/Doing/Done are the web<->mobile sync
 *  contract and must never be written or renamed from here.
 *
 *  SCOPE: the 8 process forms only. Strategy has no Status choices (its Status
 *  field is hidden with no options) so it keeps the CSS "Submitted" pill.
 *  Target Market, Objectives, Action Steps, Links, Systems and Coach are
 *  untouched — see notes in the dashboard CSS block.
 *
 *  Pairs with these rules in the dashboard CSS block:
 *      a.smplfy-heading-link p.smplfy-pills   (the row)
 *      .smplfy-pill.is-sub|is-unsub           (pill 1)
 *      .smplfy-pill.is-todo|is-doing|is-done  (pill 2)
 * ========================================================================== */

if ( ! function_exists( 'smplfy_process_status_map' ) ) {
	/**
	 * form slug (as used in the shortcode) => [ form id, Status field id ]
	 * Field IDs are NOT uniform — Marketing is 176, Sales 16, Risk 10, rest 14.
	 * Verified against the Gravity Forms export, 2026-07-16.
	 */
	function smplfy_process_status_map() {
		return [
			'leadership' => [ 99,  14  ],
			'marketing'  => [ 80,  176 ],
			'sales'      => [ 91,  16  ],
			'operations' => [ 95,  14  ],
			'people'     => [ 96,  14  ],
			'money'      => [ 97,  14  ],
			'randd'      => [ 98,  14  ],
			'risk'       => [ 100, 10  ],
		];
	}
}

add_filter( 'do_shortcode_tag', function ( $output, $tag, $attr ) {

	if ( 'smplfy_dashboard_view_shortcode' !== $tag ) {
		return $output;
	}

	$form = isset( $attr['form'] ) ? strtolower( trim( $attr['form'] ) ) : '';
	$map  = smplfy_process_status_map();

	if ( ! isset( $map[ $form ] ) ) {
		return $output;   // not a process card
	}

	if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
		return $output;
	}

	list( $form_id, $field_id ) = $map[ $form ];

	$user_id = get_current_user_id();

	// Coach viewing a client (?client_id=N) — mirrors the plugin's own logic.
	$client_id = isset( $_GET['client_id'] ) ? absint( $_GET['client_id'] ) : 0;
	if ( $client_id ) {
		$current = wp_get_current_user();
		if ( in_array( 'coach', (array) $current->roles, true ) ) {
			$user_id = $client_id;
		}
	}

	// The explicit operator matters — omitting it returns unrelated entries.
	$search = [
		'field_filters' => [
			[ 'key' => 'created_by', 'value' => $user_id, 'operator' => '=' ],
		],
	];

	$entries = GFAPI::get_entries( $form_id, $search, null, [ 'offset' => 0, 'page_size' => 1 ] );
	$status  = ( is_wp_error( $entries ) || empty( $entries ) )
		? ''
		: (string) rgar( $entries[0], (string) $field_id );

	// PILL 1 — was the form filled in? An entry exists iff the shortcode
	// resolved the card to a single-entry view URL.
	$submitted = ( ! is_wp_error( $entries ) && ! empty( $entries ) );
	$sub_cls   = $submitted ? 'is-sub' : 'is-unsub';
	$sub_label = $submitted ? 'Submitted' : 'Not submitted';

	// PILL 2 — has the work actually been done? No entry, or a radio that was
	// never answered, both mean not started.
	switch ( $status ) {
		case 'Done':
			$st_cls = 'is-done';  $st_label = 'Fully Implemented'; break;
		case 'Doing':
			$st_cls = 'is-doing'; $st_label = 'In Progress';       break;
		default:
			$st_cls = 'is-todo';  $st_label = 'Not Started';       break;
	}

	// Both pills ride in one <p> so they sit on a single row inside the card.
	$pill = '<p class="smplfy-pills">'
		. '<span class="smplfy-pill ' . $sub_cls . '">' . esc_html( $sub_label ) . '</span>'
		. '<span class="smplfy-pill ' . $st_cls  . '">' . esc_html( $st_label )  . '</span>'
		. '</p>';

	// Unsubmitted cards already carry the plugin's own <p>Not submitted</p> —
	// swap it wholesale so we never print two conflicting submitted states.
	if ( false !== strpos( $output, '<p>Not submitted</p>' ) ) {
		return str_replace( '<p>Not submitted</p>', $pill, $output );
	}

	$pos = strrpos( $output, '</a>' );
	if ( false === $pos ) {
		return $output;
	}

	return substr( $output, 0, $pos ) . $pill . substr( $output, $pos );

}, 10, 3 );
