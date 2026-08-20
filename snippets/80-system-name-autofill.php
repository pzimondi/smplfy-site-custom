<?php
/**
 * Migrated verbatim from WPCode (28-29 Jul 2026 consolidation).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('gform_entry_created', function($entry, $form) {
    if ($form['id'] != 93) return;
    if (!rgblank(rgar($entry, '1'))) return;
    $parent_form_id = gform_get_meta($entry['id'], 'gpnf_entry_parent_form');
    $map = [80=>'Marketing', 91=>'Sales', 95=>'Operations', 96=>'People',
            97=>'Money', 98=>'Research & Development', 99=>'Leadership', 100=>'Risk'];
    if (isset($map[$parent_form_id])) {
        GFAPI::update_entry_field($entry['id'], 1, $map[$parent_form_id]);
    }
}, 10, 2);
