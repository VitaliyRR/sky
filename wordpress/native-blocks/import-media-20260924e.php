<?php
/** Idempotently import the repositioned finance illustration. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$result_file = $args[1] ?? null;
$directory = $base ? realpath( $base ) : false;
if ( ! $directory || ! str_ends_with( $directory, '/wordpress/native-blocks/assets/revision-20260924e' ) || ! $result_file ) {
    WP_CLI::error( 'Usage: wp eval-file import-media-20260924e.php ASSETS_DIR OUTPUT_JSON' );
}
$filename = 'banner-finance-bag-income-20260924.webp';
$source = $directory . '/' . $filename;
if ( ! is_file( $source ) || filesize( $source ) > 150000 ) {
    WP_CLI::error( 'Missing or oversized image: ' . $source );
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
global $wpdb;
$id = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
    '%/' . $wpdb->esc_like( $filename )
) );
if ( ! $id || get_post_type( $id ) !== 'attachment' ) {
    $temporary = wp_tempnam( $filename );
    if ( ! $temporary || ! copy( $source, $temporary ) ) {
        WP_CLI::error( 'Cannot prepare media import' );
    }
    $id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $temporary, 'error' => 0, 'size' => filesize( $source ) ), 0, pathinfo( $filename, PATHINFO_FILENAME ) );
    if ( is_wp_error( $id ) ) {
        @unlink( $temporary );
        WP_CLI::error( 'Media import failed: ' . $id->get_error_message() );
    }
}
if ( file_put_contents( $result_file, wp_json_encode( array( $filename => (int) $id ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" ) === false ) {
    WP_CLI::error( 'Could not write result map' );
}
WP_CLI::success( $filename . ' -> ' . $id );
