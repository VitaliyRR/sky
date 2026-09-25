<?php
/** Import the transparent finance banner as a new, cache-safe media attachment. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$directory = isset( $args[0] ) ? realpath( $args[0] ) : false;
$result_file = $args[1] ?? null;
if ( ! $directory || ! str_ends_with( $directory, '/wordpress/native-blocks/assets/revision-20260925b' ) || ! $result_file ) {
    WP_CLI::error( 'Usage: wp eval-file import-media-20260925b.php ASSETS_DIR OUTPUT_JSON' );
}
$filename = 'banner-finance-transparent-20260925.png';
$source = $directory . '/' . $filename;
$expected_hash = '83059de6a269059237377103589bef679d9c448fd11bdf31940390eb454b879a';
$image_info = is_file( $source ) ? getimagesize( $source ) : false;
if ( ! $image_info || $image_info[0] !== 1774 || $image_info[1] !== 887 || $image_info['mime'] !== 'image/png'
    || hash_file( 'sha256', $source ) !== $expected_hash ) {
    WP_CLI::error( 'Transparent image failed integrity or dimension checks' );
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
global $wpdb;
$id = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
    '%/' . $wpdb->esc_like( $filename )
) );
if ( $id ) {
    if ( get_post_type( $id ) !== 'attachment' || hash_file( 'sha256', (string) get_attached_file( $id ) ) !== $expected_hash ) {
        WP_CLI::error( 'An existing attachment with this filename differs from the approved asset' );
    }
} else {
    $temporary = wp_tempnam( $filename );
    if ( ! $temporary || ! copy( $source, $temporary ) ) {
        WP_CLI::error( 'Cannot prepare media import' );
    }
    $id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $temporary, 'error' => 0, 'size' => filesize( $source ) ), 0, 'Прозрачный баннер: финансовые условия' );
    if ( is_wp_error( $id ) ) {
        @unlink( $temporary );
        WP_CLI::error( 'Media import failed: ' . $id->get_error_message() );
    }
    update_post_meta( $id, '_wp_attachment_image_alt', 'Напольный платёжный терминал, мешок «Доход» и +20%' );
}
if ( file_put_contents( $result_file, wp_json_encode( array( $filename => (int) $id ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" ) === false ) {
    WP_CLI::error( 'Could not write result map' );
}
WP_CLI::success( $filename . ' -> ' . $id );
