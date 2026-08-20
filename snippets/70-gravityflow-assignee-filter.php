<?php
/**
 * Migrated verbatim from WPCode (28-29 Jul 2026 consolidation).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter('gravityflow_assignee_field_users', function($users, $form_id, $field) {
    if ((int)$form_id !== 75) return $users;
    $org = get_user_meta(get_current_user_id(), 'mepr_organization', true);
    if (empty($org)) return $users;
    $org_ids = array_map('intval', get_users(['meta_key' => 'mepr_organization', 'meta_value' => $org, 'fields' => 'ID']));
    $get_id = function($u) {
        if (is_object($u)) return (int)($u->ID ?? 0);
        if (is_array($u)) {
            if (isset($u['ID'])) return (int)$u['ID'];
            if (isset($u['id'])) return (int)$u['id'];
            if (isset($u['value'])) {
                $v = $u['value'];
                if (strpos($v, 'user_id|') === 0) return (int)substr($v, 8);
                if (is_numeric($v)) return (int)$v;
            }
        }
        return 0;
    };
    return array_values(array_filter((array)$users, function($u) use ($org_ids, $get_id) {
        return in_array($get_id($u), $org_ids, true);
    }));
}, 10, 3);
