<?php
/**
 * Migrated verbatim from WPCode (28-29 Jul 2026 consolidation).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'gravityflow_workflow_complete', function ( $entry_id, $form, $status ) {

    // ---- Config -------------------------------------------------
    $parent_form_id     = 75;
    $include_field_id   = 31;
    $parent_id_field_id = 1;
    $report_url         = home_url( '/track/leadership-reports/' );

    $children = array(
        'Leadership' => 116,
        'Marketing'  => 113,
        'Sales'      => 114,
        'Operations' => 115,
    );
    // -------------------------------------------------------------

    $log = function ( $msg ) use ( $entry_id ) {
        error_log( sprintf( '[SMPLFY notify] entry %s: %s', $entry_id, $msg ) );
    };

    $form_id = (int) rgar( (array) $form, 'id' );
    if ( ! in_array( $form_id, $children, true ) ) {
        return; // not a child form
    }

    $log( 'hook fired on child form ' . $form_id . ' with status ' . $status );

    $child = GFAPI::get_entry( $entry_id );
    if ( is_wp_error( $child ) ) {
        $log( 'could not load child entry' );
        return;
    }

    $parent_entry_id = (int) rgar( $child, (string) $parent_id_field_id );
    if ( ! $parent_entry_id ) {
        $log( 'no parent entry id on child' );
        return;
    }

    $parent = GFAPI::get_entry( $parent_entry_id );
    if ( is_wp_error( $parent ) || (int) rgar( $parent, 'form_id' ) !== $parent_form_id ) {
        $log( 'parent ' . $parent_entry_id . ' missing or wrong form' );
        return;
    }

    if ( gform_get_meta( $parent_entry_id, 'smplfy_all_sections_notified' ) ) {
        $log( 'already notified for parent ' . $parent_entry_id );
        return;
    }

    // Which processes were included?
    $included = array();
    $i = 1;
    foreach ( $children as $label => $child_form_id ) {
        if ( ! empty( rgar( $parent, $include_field_id . '.' . $i ) ) ) {
            $included[ $label ] = $child_form_id;
        }
        $i++;
    }

    $log( 'included: ' . implode( ',', array_keys( $included ) ) );

    if ( empty( $included ) ) {
        return;
    }

    foreach ( $included as $label => $child_form_id ) {

        $found = GFAPI::get_entries(
            $child_form_id,
            array(
                'field_filters' => array(
                    array( 'key' => (string) $parent_id_field_id, 'value' => (string) $parent_entry_id, 'operator' => '=' ),
                ),
            ),
            array( 'key' => 'id', 'direction' => 'DESC' ),
            array( 'page_size' => 50 )
        );

        if ( is_wp_error( $found ) || empty( $found ) ) {
            $log( $label . ': no child entry yet' );
            return;
        }

        $done = false;

        foreach ( $found as $e ) {
            $eid = (int) $e['id'];

            // Guard: the filter can return unrelated entries. Verify the parent.
            if ( (int) rgar( $e, (string) $parent_id_field_id ) !== $parent_entry_id ) {
                $log( $label . ': entry ' . $eid . ' has wrong parent (' . rgar( $e, (string) $parent_id_field_id ) . ') — skipping' );
                continue;
            }

            // The entry that triggered this hook is complete by definition;
            // its final status may not be persisted yet.
            if ( $eid === (int) $entry_id ) {
                $log( $label . ': entry ' . $eid . ' is the trigger — complete' );
                $done = true;
                break;
            }

            $st = rgar( $e, 'workflow_final_status' );
            if ( empty( $st ) ) {
                $st = gform_get_meta( $eid, 'workflow_final_status' );
            }

            $log( $label . ': entry ' . $eid . ' status = ' . var_export( $st, true ) );

            if ( 'complete' === $st ) {
                $done = true;
                break;
            }
        }

        if ( ! $done ) {
            $log( $label . ': not complete yet' );
            return;
        }
    }

    $user = get_userdata( (int) rgar( $parent, 'created_by' ) );
    if ( ! $user || ! is_email( $user->user_email ) ) {
        $log( 'no valid submitter email' );
        return;
    }

    $fmt = function ( $raw ) {
        if ( ! $raw ) { return ''; }
        $d = date_create( $raw );
        return $d ? date_format( $d, 'j M Y' ) : $raw;
    };

    $from   = $fmt( rgar( $parent, '8' ) );
    $to     = $fmt( rgar( $parent, '9' ) );
    $period = ( $from && $to ) ? sprintf( ' for %s – %s', esc_html( $from ), esc_html( $to ) ) : '';

    $sections = esc_html( implode( ', ', array_keys( $included ) ) );

    $subject = 'Your Performance Report is complete';

    $message  = '<p>Hi,</p>';
    $message .= sprintf( '<p>All sections of your Performance Report%s have been completed.</p>', $period );
    $message .= sprintf( '<p>Sections received: %s</p>', $sections );
    $message .= sprintf(
        '<p>You can view the full report in <a href="%1$s">Leadership Reports</a>.</p>',
        esc_url( $report_url )
    );
    $message .= '<p>— Simplify Biz</p>';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Simplify Biz <' . get_option( 'admin_email' ) . '>',
    );

    $sent = wp_mail( $user->user_email, $subject, $message, $headers );
    $log( 'wp_mail to ' . $user->user_email . ' returned ' . var_export( $sent, true ) );

    gform_update_meta( $parent_entry_id, 'smplfy_all_sections_notified', 1 );
    GFAPI::add_note( $parent_entry_id, 0, 'Workflow', 'All included sections complete. Submitter notified.' );

}, 10, 3 );
