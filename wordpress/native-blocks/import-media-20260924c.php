<?php
/** Idempotent import of the 24 September final-pass artwork and historical catalogue logos. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$result_file = $args[1] ?? null;
if ( ! $base || ! $result_file ) {
    WP_CLI::error( 'Usage: wp eval-file import-media-20260924c.php ASSETS_DIR OUTPUT_JSON' );
}
$directory = realpath( $base );
if ( ! $directory || ! str_ends_with( $directory, '/wordpress/native-blocks/assets/revision-20260924c' ) ) {
    WP_CLI::error( 'Unexpected asset directory' );
}
$repo = dirname( $directory, 2 );
$providers = json_decode( file_get_contents( $repo . '/providers-expanded-20260924c.json' ), true );
if ( ! is_array( $providers ) || (int) ( $providers['selectedCount'] ?? 0 ) !== 279 || count( $providers['categories'] ?? array() ) !== 10 ) {
    WP_CLI::error( 'Missing verified historical provider selection' );
}
$paths = array();
foreach ( array( 'skysend-wordmark-light.png', 'skysend-wordmark-dark.png', 'skysend-site-icon.png', 'pos-terminal-20260924c.png', 'gear-20260924c.png' ) as $filename ) {
    $paths[ $filename ] = $directory . '/' . $filename;
}
foreach ( $providers['categories'] as $category ) {
    foreach ( $category['items'] as $item ) {
        $media_file = $item['mediaFile'] ?? '';
        if ( ! str_starts_with( $media_file, 'wordpress/native-blocks/assets/revision-20260924c/providers/' ) ) {
            continue;
        }
        $filename = basename( $media_file );
        if ( ! preg_match( '/^provider-[0-9]+\.png$/', $filename ) ) {
            WP_CLI::error( 'Unexpected provider logo: ' . $filename );
        }
        $paths[ $filename ] = $directory . '/providers/' . $filename;
    }
}
if ( count( $paths ) !== 176 ) {
    WP_CLI::error( 'Expected 171 new provider logos and 5 design assets; got ' . count( $paths ) );
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
global $wpdb;
$result = json_decode( file_get_contents( $repo . '/revision-media-20260924b.json' ), true );
if ( ! is_array( $result ) ) {
    WP_CLI::error( 'Previous media map missing' );
}
foreach ( $paths as $filename => $source ) {
    if ( ! is_file( $source ) || filesize( $source ) > 500000 ) {
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
ksort( $result );
if ( file_put_contents( $result_file, wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" ) === false ) {
    WP_CLI::error( 'Could not write result map' );
}
WP_CLI::success( count( $paths ) . ' revision images ready; map: ' . $result_file );
