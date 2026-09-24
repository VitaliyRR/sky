<?php
/** Idempotent import of the two text-in-image illustrations on the development VM. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$result_file = $args[1] ?? null;
$directory = $base ? realpath( $base ) : false;
if ( ! $directory || ! str_ends_with( $directory, '/wordpress/native-blocks/assets/revision-20260924d' ) || ! $result_file ) {
    WP_CLI::error( 'Usage: wp eval-file import-media-20260924d.php ASSETS_DIR OUTPUT_JSON' );
}
$filenames = array( 'banner-finance-income-20260924.webp', 'partner-gateways-xml-20260924.webp' );
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
global $wpdb;
$result = array();
foreach ( $filenames as $filename ) {
    $source = $directory . '/' . $filename;
    if ( ! is_file( $source ) || filesize( $source ) > 150000 ) {
        WP_CLI::error( 'Missing or oversized image: ' . $source );
    }
    $existing = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
        '%/' . $wpdb->esc_like( $filename )
    ) );
    if ( $existing && get_post_type( $existing ) === 'attachment' ) {
        $result[ $filename ] = $existing;
        continue;
    }
    $temporary = wp_tempnam( $filename );
    if ( ! $temporary || ! copy( $source, $temporary ) ) {
        WP_CLI::error( 'Cannot prepare import for ' . $filename );
    }
    $id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $temporary, 'error' => 0, 'size' => filesize( $source ) ), 0, pathinfo( $filename, PATHINFO_FILENAME ) );
    if ( is_wp_error( $id ) ) {
        @unlink( $temporary );
        WP_CLI::error( 'Media import failed for ' . $filename . ': ' . $id->get_error_message() );
    }
    $result[ $filename ] = (int) $id;
    WP_CLI::log( $filename . ' -> ' . $id );
}
if ( file_put_contents( $result_file, wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" ) === false ) {
    WP_CLI::error( 'Could not write result map' );
}
WP_CLI::success( 'Two revised images ready' );
