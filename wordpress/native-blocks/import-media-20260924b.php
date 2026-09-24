<?php
/** One-time idempotent import of the second 24.09.2026 media set on the dev VM. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$result_file = $args[1] ?? null;
if ( ! $base || ! is_dir( $base ) || ! $result_file ) {
    WP_CLI::error( 'Usage: wp eval-file import-media-20260924b.php ASSETS_DIR OUTPUT_JSON' );
}
$directory = realpath( $base );
if ( ! $directory || ! str_ends_with( $directory, '/wordpress/native-blocks/assets/revision-20260924' ) ) {
    WP_CLI::error( 'Unexpected asset directory' );
}
$repo = dirname( $directory, 2 );
$providers = json_decode( file_get_contents( $repo . '/providers-expanded-20260924.json' ), true );
if ( ! is_array( $providers ) || count( $providers['categories'] ?? array() ) < 8 ) {
    WP_CLI::error( 'Missing verified provider selection' );
}
$paths = array(
    'banner-finance-terminal-20260924.webp' => $directory . '/banner-finance-terminal-20260924.webp',
    'fastsys-boot-official-20260924.webp' => $directory . '/fastsys-boot-official-20260924.webp',
    'payment-kiosk-20260924.png' => $directory . '/icons/payment-kiosk-20260924.png',
    'zip-20260924.png' => $directory . '/icons/zip-20260924.png',
);
foreach ( $providers['categories'] as $category ) {
    foreach ( $category['items'] as $item ) {
        if ( empty( $item['mediaFile'] ) ) {
            continue;
        }
        $filename = basename( $item['mediaFile'] );
        if ( ! str_starts_with( $item['mediaFile'], 'wordpress/native-blocks/assets/revision-20260924/providers/' ) || ! preg_match( '/^provider-[0-9]+\.(png|webp)$/', $filename ) ) {
            WP_CLI::error( 'Unexpected provider media filename: ' . $filename );
        }
        $paths[ $filename ] = $directory . '/providers/' . $filename;
    }
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
global $wpdb;
$result = json_decode( file_get_contents( $repo . '/revision-media-20260924.json' ), true );
if ( ! is_array( $result ) ) {
    WP_CLI::error( 'Previous media map missing' );
}
foreach ( $paths as $filename => $source ) {
    if ( ! is_file( $source ) || filesize( $source ) > 500000 ) {
        WP_CLI::error( 'Missing or oversized image: ' . $source );
    }
    $suffix = '%/' . $wpdb->esc_like( $filename );
    $existing = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
        $suffix
    ) );
    if ( $existing && get_post_type( $existing ) === 'attachment' ) {
        $result[ $filename ] = $existing;
        continue;
    }
    $temporary = wp_tempnam( $filename );
    if ( ! $temporary || ! copy( $source, $temporary ) ) {
        WP_CLI::error( 'Cannot prepare import for ' . $filename );
    }
    $file = array( 'name' => $filename, 'tmp_name' => $temporary, 'error' => 0, 'size' => filesize( $source ) );
    $id = media_handle_sideload( $file, 0, pathinfo( $filename, PATHINFO_FILENAME ) );
    if ( is_wp_error( $id ) ) {
        @unlink( $temporary );
        WP_CLI::error( 'Media import failed for ' . $filename . ': ' . $id->get_error_message() );
    }
    $result[ $filename ] = (int) $id;
    WP_CLI::log( $filename . ' -> ' . $id );
}
ksort( $result );
$json = wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
if ( file_put_contents( $result_file, $json ) === false ) {
    WP_CLI::error( 'Could not write result map' );
}
WP_CLI::success( count( $paths ) . ' revision images ready; map: ' . $result_file );
