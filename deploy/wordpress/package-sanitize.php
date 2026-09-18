<?php
/** Sanitizes authentication data ONLY in the isolated package database. */
if (!defined('WP_CLI') || !WP_CLI) { exit(1); }
global $wpdb;
$expectedDb = $args[0] ?? '';
if (!preg_match('/^skysend_pkg_[0-9]{8}_[0-9]{6}$/', $expectedDb)
    || DB_NAME !== $expectedDb || DB_USER !== $expectedDb
    || !str_starts_with(realpath(ABSPATH), '/opt/skysend-packages/')
    || !defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON) {
    WP_CLI::error('Refusing to sanitize anything except the private package clone.');
}
// Fail closed if there are unexpected, configured credentials in the copied options.
$findSecrets = static function($value, string $path = '') use (&$findSecrets): array {
    $found = [];
    if (!is_array($value) && !is_object($value)) { return $found; }
    foreach ((array) $value as $key => $item) {
        $next = $path.'.'.$key;
        if (preg_match('/(?:api[_-]?key|access[_-]?token|refresh[_-]?token|client[_-]?secret|password|passwd|credential|private[_-]?key)/i', (string) $key)
            && !empty($item) && $item !== 'password') { $found[] = $next; }
        $found = array_merge($found, $findSecrets($item, $next));
    }
    return $found;
};
$issues = [];
foreach ($wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options}", ARRAY_A) as $row) {
    $name = $row['option_name'];
    if (str_starts_with($name, '_transient_') || str_starts_with($name, '_site_transient_')) {
        delete_option($name);
        continue;
    }
    if ($name === 'mailserver_pass' || in_array($name, ['auth_key','secure_auth_key','logged_in_key','nonce_key','auth_salt','secure_auth_salt','logged_in_salt','nonce_salt'], true)) {
        if ($name === 'mailserver_pass') { update_option($name, ''); } else { delete_option($name); }
        continue;
    }
    $value = maybe_unserialize($row['option_value']);
    if ($name === 'seopress_instant_indexing_option_name' && is_array($value)) {
        // The new host must not reuse the source site's IndexNow submission key.
        $value['seopress_instant_indexing_bing_api_key'] = '';
        $value['seopress_instant_indexing_automate_submission'] = '';
        update_option($name, $value);
    }
    if (preg_match('/(?:api[_-]?key|access[_-]?token|refresh[_-]?token|client[_-]?secret|password|passwd|credential|private[_-]?key)/i', $name) && !empty($value)) { $issues[] = $name; }
    $issues = array_merge($issues, $findSecrets($value, $name));
}
if ($issues) { WP_CLI::error('Review nonempty credential fields in the clone before export (values not printed): '.implode(', ', array_unique($issues))); }
$users = get_users(['fields' => 'ID']);
foreach ($users as $id) {
    // wp_set_password does not send a password-changed email. Plugins are skipped.
    wp_set_password(wp_generate_password(64, true, true), $id);
    $wpdb->update($wpdb->users, ['user_activation_key' => ''], ['ID' => $id]);
    delete_user_meta($id, 'session_tokens');
    delete_user_meta($id, '_application_passwords');
}
// Unknown external credentials in user meta must be reviewed, not silently shipped.
foreach ($wpdb->get_results("SELECT meta_key,meta_value FROM {$wpdb->usermeta}", ARRAY_A) as $row) {
    $value = maybe_unserialize($row['meta_value']);
    if (preg_match('/(?:api[_-]?key|access[_-]?token|refresh[_-]?token|client[_-]?secret|password|passwd|credential|private[_-]?key)/i', $row['meta_key']) && !empty($value)) {
        WP_CLI::error('Review nonempty credential user meta in the clone: '.$row['meta_key']);
    }
    $nestedIssues = $findSecrets($value, $row['meta_key']);
    if ($nestedIssues) { WP_CLI::error('Review nested credential user meta in the clone (values not printed): '.implode(', ', array_unique($nestedIssues))); }
}
WP_CLI::success('Clone sanitized: copied account passwords randomized, sessions/application passwords/reset tokens cleared; source unchanged.');
