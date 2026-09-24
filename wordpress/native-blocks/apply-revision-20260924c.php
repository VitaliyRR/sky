<?php
/** Guarded update of editable Gutenberg content and the site icon on the development VM. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$dry_run = in_array( 'dry-run', $args, true );
if ( ! $base || ! is_dir( $base ) ) {
    WP_CLI::error( 'Usage: wp eval-file apply-revision-20260924c.php OUTPUT_DIR [dry-run]' );
}
$manifest = json_decode( file_get_contents( $base . '/manifest.json' ), true );
if ( ! is_array( $manifest ) || ( $manifest['revision'] ?? '' ) !== '2026-09-24c' ||
    (int) ( $manifest['categories'] ?? 0 ) !== 10 || (int) ( $manifest['providers'] ?? 0 ) !== 279 ) {
    WP_CLI::error( 'Invalid revision manifest' );
}
$posts = array( 'page' => 67, 'header' => 68, 'footer' => 69, 'styles' => 66 );
$payloads = array();
foreach ( $posts as $name => $id ) {
    $record = $manifest['report'][ $name ] ?? null;
    $file = $base . '/' . $name . ( $name === 'styles' ? '.json' : '.html' );
    $post = get_post( $id );
    if ( ! $record || ! $post || ! is_readable( $file ) ) {
        WP_CLI::error( 'Missing guarded content: ' . $name );
    }
    if ( hash( 'sha256', $post->post_content ) !== $record['beforeHash'] ) {
        WP_CLI::error( 'Live content changed; refusing to overwrite ' . $name );
    }
    $content = file_get_contents( $file );
    if ( hash( 'sha256', $content ) !== $record['afterHash'] ) {
        WP_CLI::error( 'Generated checksum mismatch: ' . $name );
    }
    $payloads[ $name ] = array( 'id' => $id, 'content' => $content );
}
if ( (int) get_option( 'site_icon' ) !== 105 ) {
    WP_CLI::error( 'Live site icon changed; refusing to overwrite it' );
}
$new_icon = (int) ( $manifest['mediaIds']['skysend-site-icon.png'] ?? 0 );
if ( get_post_type( $new_icon ) !== 'attachment' || ! str_ends_with( (string) get_attached_file( $new_icon ), '/skysend-site-icon.png' ) ) {
    WP_CLI::error( 'New site icon attachment missing' );
}
foreach ( array( 'skysend-wordmark-light.png', 'skysend-wordmark-dark.png', 'pos-terminal-20260924c.png', 'gear-20260924c.png' ) as $filename ) {
    $id = (int) ( $manifest['mediaIds'][ $filename ] ?? 0 );
    if ( get_post_type( $id ) !== 'attachment' || ! str_ends_with( (string) get_attached_file( $id ), '/' . $filename ) ) {
        WP_CLI::error( 'Missing imported media: ' . $filename );
    }
}
WP_CLI::log( 'Guards passed: page 67, header 68, footer 69, Global Styles 66, site icon 105.' );
if ( $dry_run ) {
    WP_CLI::success( 'Dry run complete.' );
    return;
}
global $wpdb;
$wpdb->query( 'START TRANSACTION' );
try {
    foreach ( $payloads as $name => $payload ) {
        $result = wp_update_post( wp_slash( array( 'ID' => $payload['id'], 'post_content' => $payload['content'] ) ), true );
        if ( is_wp_error( $result ) || (int) $result !== $payload['id'] ) {
            throw new RuntimeException( 'WordPress rejected ' . $name );
        }
    }
    if ( ! update_option( 'site_icon', $new_icon ) ) {
        throw new RuntimeException( 'WordPress rejected the site icon' );
    }
    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( $error->getMessage() );
}
wp_cache_flush();
foreach ( $payloads as $name => $payload ) {
    clean_post_cache( $payload['id'] );
    if ( hash( 'sha256', get_post( $payload['id'] )->post_content ) !== $manifest['report'][ $name ]['afterHash'] ) {
        WP_CLI::error( 'Post-update checksum mismatch: ' . $name );
    }
}
if ( (int) get_option( 'site_icon' ) !== $new_icon ) {
    WP_CLI::error( 'Site icon did not update' );
}
WP_CLI::success( 'Revision 2026-09-24c installed and verified.' );
