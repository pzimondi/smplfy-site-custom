<?php
/**
 * Migrated verbatim from WPCode (28-29 Jul 2026 consolidation).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================================
 *  SNIPPET 1 of 3
 *  Name:  SMPLFY — Dashboard Hero
 *  Type:  PHP snippet   ·   Run: Front-end only
 *  ----------------------------------------------------------------------------
 *  Registers [smplfy_dashboard_hero].
 *
 *  PLACEMENT on the Dashboard page (page-id-108):
 *    1. Put the shortcode at the very top, ABOVE the <h2>Plan</h2> heading.
 *    2. REMOVE [smplfy_organisation_name] — the hero prints the org name
 *       itself, so keeping both shows it twice.
 *
 *  ----------------------------------------------------------------------------
 *  LAYOUT OF FACTS
 *
 *    CHIPS (left)   submission only — how many process forms have been filled
 *                   in, how many haven't.
 *
 *    HUB (right)    implementation ONLY — a count per state, read from each
 *                   form's parent Status radio:
 *                       ToDo  -> Not started
 *                       Doing -> In progress
 *                       Done  -> Fully implemented
 *                   No percentage and no bar: with 8 processes the counts ARE
 *                   the proportion, so a bar only restates the rows, and the
 *                   headline % just repeated "Fully implemented N".
 *
 *  Submission and implementation are different facts: a form can be submitted
 *  with Status = ToDo. The chips answer "have I filled these in?", the hub
 *  answers "have I actually done them?".
 *
 *  RESUME BUTTON: shown ONLY when at least one process form has never been
 *  submitted, and always links to /start/. If everything is submitted there is
 *  nothing to start, so the button is hidden.
 *
 *  SCOPE: the 8 process forms. They are the only forms with a Status radio.
 *  Strategy and Target Market have a hidden Status field with no choices;
 *  Objectives / Action Steps / Links are lists whose status lives per row.
 *  Counting those here would mean inventing data.
 *
 *  READ ONLY. ToDo/Doing/Done are the web<->mobile sync contract.
 * ========================================================================== */

if ( ! function_exists( 'smplfy_hero_process_forms' ) ) {
	/**
	 * form id => [ Status field id, label ]
	 * Status field IDs are NOT uniform — Marketing 176, Sales 16, Risk 10,
	 * the rest 14. Verified against the Gravity Forms export, 2026-07-16.
	 */
	function smplfy_hero_process_forms() {
		return [
			99  => [ 14,  'Leadership' ],
			80  => [ 176, 'Marketing'  ],
			91  => [ 16,  'Sales'      ],
			95  => [ 14,  'Operations' ],
			96  => [ 14,  'People'     ],
			97  => [ 14,  'Money'      ],
			98  => [ 14,  'R&D'        ],
			100 => [ 10,  'Risk'       ],
		];
	}
}

if ( ! function_exists( 'smplfy_hero_read' ) ) {
	/**
	 * Reads one process for one user.
	 * @return array [ 'submitted' => bool, 'status' => 'Done'|'Doing'|'ToDo' ]
	 */
	function smplfy_hero_read( $form_id, $field_id, $user_id ) {
		static $cache = [];
		$key = $form_id . ':' . $user_id;

		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		// The explicit operator matters — omitting it returns unrelated entries.
		$search = [
			'field_filters' => [
				[ 'key' => 'created_by', 'value' => $user_id, 'operator' => '=' ],
			],
		];

		$entries = GFAPI::get_entries( $form_id, $search, null, [ 'offset' => 0, 'page_size' => 1 ] );
		$ok      = ( ! is_wp_error( $entries ) && ! empty( $entries ) );

		$status = $ok ? (string) rgar( $entries[0], (string) $field_id ) : '';
		if ( ! in_array( $status, [ 'Done', 'Doing' ], true ) ) {
			$status = 'ToDo';   // no entry, or radio never answered
		}

		$cache[ $key ] = [ 'submitted' => $ok, 'status' => $status ];

		return $cache[ $key ];
	}
}

if ( ! function_exists( 'smplfy_dashboard_hero_shortcode' ) ) {
	function smplfy_dashboard_hero_shortcode() {

		if ( ! is_user_logged_in() || ! class_exists( 'GFAPI' ) ) {
			return '';
		}

		$user_id = get_current_user_id();

		// Coach viewing a client (?client_id=N) — mirrors the plugin's shortcode.
		$client_id = isset( $_GET['client_id'] ) ? absint( $_GET['client_id'] ) : 0;
		if ( $client_id ) {
			$current = wp_get_current_user();
			if ( in_array( 'coach', (array) $current->roles, true ) ) {
				$user_id = $client_id;
			}
		}

		$forms = smplfy_hero_process_forms();
		$total = count( $forms );

		$done = $doing = $todo = $submitted = 0;
		$first_unsubmitted = '';

		foreach ( $forms as $form_id => $meta ) {
			list( $field_id, $label ) = $meta;
			$row = smplfy_hero_read( $form_id, $field_id, $user_id );

			if ( $row['submitted'] ) {
				$submitted++;
			} elseif ( '' === $first_unsubmitted ) {
				$first_unsubmitted = $label;   // first gap, in page order
			}

			if ( 'Done' === $row['status'] ) {
				$done++;
			} elseif ( 'Doing' === $row['status'] ) {
				$doing++;
			} else {
				$todo++;
			}
		}

		$not_submitted = $total - $submitted;

		$org = get_user_meta( $user_id, 'mepr_organization', true );

		ob_start();
		?>
		<div class="sb-hero">
			<div>
				<p class="sb-hero__eyebrow">Business plan</p>
				<h1 class="sb-hero__title"><?php echo esc_html( $org ? $org : 'Your business plan' ); ?></h1>
				<p class="sb-hero__lede">Where your plan stands today — and the next thing worth doing.</p>

				<?php if ( $not_submitted > 0 ) : ?>
					<a class="sb-resume" href="<?php echo esc_url( home_url( '/start/' ) ); ?>">
						<i class="fa-solid fa-play" aria-hidden="true"></i>
						Continue <?php echo esc_html( $first_unsubmitted ); ?>
						<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<div class="sb-chips">
					<span class="sb-chip"><span class="sb-dot sb-dot--done"></span><b><?php echo (int) $submitted; ?></b>&nbsp;submitted</span>
					<span class="sb-chip"><span class="sb-dot sb-dot--todo"></span><b><?php echo (int) $not_submitted; ?></b>&nbsp;not submitted</span>
				</div>
			</div>

			<div class="sb-hub">
				<span class="sb-hub__flag"><span class="sb-hub__pulse" aria-hidden="true"></span>Process status</span>

				<ul class="sb-legend" aria-label="<?php echo esc_attr( sprintf(
					'%d of %d processes fully implemented, %d in progress, %d not started',
					$done, $total, $doing, $todo
				) ); ?>">
					<li><span class="sb-dot sb-dot--done"></span>Fully implemented<b><?php echo (int) $done; ?></b></li>
					<li><span class="sb-dot sb-dot--doing"></span>In progress<b><?php echo (int) $doing; ?></b></li>
					<li><span class="sb-dot sb-dot--todo"></span>Not started<b><?php echo (int) $todo; ?></b></li>
				</ul>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

add_shortcode( 'smplfy_dashboard_hero', 'smplfy_dashboard_hero_shortcode' );
