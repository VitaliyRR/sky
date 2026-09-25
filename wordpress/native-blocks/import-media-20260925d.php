<?php
/** Import the nine cohesive ALLVEND pictograms into the editable Media Library. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$directory = isset( $args[0] ) ? realpath( $args[0] ) : false;
$result_file = $args[1] ?? null;
if ( ! $directory || ! str_ends_with( $directory, '/wordpress/native-blocks/assets/allvend-icons-20260925' ) || ! $result_file ) {
    WP_CLI::error( 'Usage: wp eval-file import-media-20260925d.php ASSETS_DIR OUTPUT_JSON' );
}
$expected = array(
    'payment-services.png' => '19dd8aa450c32b8788178f3f471ee910bcfeee03ae2f30fd390ab899c66895cc',
    'self-service.png' => '82b30b78a509b0db30cf3e01429902b215c5d9b2f8311533cb79b41836b83cf3',
    'advertising-video.png' => '59cb3123fbe650f6c7a953e5d4e137838ef1d73a7d73a2bce3896225ab83cf63',
    'cashless-pos.png' => '60d627e6b864550dcbe5f33ed7cf7c7924373dda26e72366c9b932c0797b41c8',
    'qr-scan.png' => '819079f9dd9ad21a394285d7f82fdeaf3d2924ef1eb506ac67e750c3ba8033cc',
    'biometric.png' => '78020ecf86d982bf2fbe6b1b2f72ddb1fcb61c5d28430eec300f3cf11e1dd24d',
    'interface-settings.png' => 'c22636b59e03a40107393b80e4acddcafaaaf31122ab55ff89ec6f8067d72489',
    'remote-console.png' => 'f7e6e0f7348144fbb4f08e586ab457c950f7ace43ca0a7db914f81e2a84bfc91',
    'goods-sales.png' => 'fe94b66297a348c56b8419291adc520318cca547c7273785e6ae63c1b73f3f01',
);
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
global $wpdb;
$result = array();
foreach ( $expected as $filename => $hash ) {
    $source = $directory . '/' . $filename;
    $image_info = is_file( $source ) ? getimagesize( $source ) : false;
    if ( ! $image_info || $image_info[0] !== 96 || $image_info[1] !== 96 ||
        $image_info['mime'] !== 'image/png' || hash_file( 'sha256', $source ) !== $hash ) {
        WP_CLI::error( 'Icon failed integrity or dimension checks: ' . $filename );
    }
    $id = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
        '%/' . $wpdb->esc_like( $filename )
    ) );
    if ( $id ) {
        $attached = get_attached_file( $id );
        if ( get_post_type( $id ) !== 'attachment' || ! $attached || ! is_file( $attached ) || hash_file( 'sha256', $attached ) !== $hash ) {
            WP_CLI::error( 'An existing attachment with this name differs: ' . $filename );
        }
    } else {
        $temporary = wp_tempnam( $filename );
        if ( ! $temporary || ! copy( $source, $temporary ) ) {
            WP_CLI::error( 'Cannot prepare icon import: ' . $filename );
        }
        $id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $temporary, 'error' => 0, 'size' => filesize( $source ) ), 0, 'ALLVEND: ' . $filename );
        if ( is_wp_error( $id ) ) {
            @unlink( $temporary );
            WP_CLI::error( 'Media import failed: ' . $id->get_error_message() );
        }
    }
    $result[ $filename ] = (int) $id;
}
if ( file_put_contents( $result_file, wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" ) === false ) {
    WP_CLI::error( 'Cannot write media ID map' );
}
WP_CLI::success( 'Nine ALLVEND pictograms imported to Media Library.' );
