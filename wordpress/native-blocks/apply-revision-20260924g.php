<?php
/** Guarded page and Global Styles visual refinement. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$dry_run = in_array( 'dry-run', $args, true );
$manifest = $base && is_readable( $base . '/manifest.json' )
    ? json_decode( file_get_contents( $base . '/manifest.json' ), true ) : null;
if ( ! is_array( $manifest ) || ( $manifest['revision'] ?? '' ) !== '2026-09-24g' ||
    (int) ( $manifest['providers'] ?? 0 ) !== 279 ) {
    WP_CLI::error( 'Invalid revision manifest' );
}
$payloads = array();
foreach ( array( 'page' => 67, 'styles' => 66 ) as $name => $id ) {
    $record = $manifest['report'][ $name ] ?? null;
    $file = $base . '/' . $name . ( $name === 'page' ? '.html' : '.json' );
    $post = get_post( $id );
    if ( ! $record || ! $post || ! is_readable( $file ) ||
        hash( 'sha256', $post->post_content ) !== $record['beforeHash'] ) {
        WP_CLI::error( 'Live content changed: ' . $name );
    }
    $content = file_get_contents( $file );
    if ( hash( 'sha256', $content ) !== $record['afterHash'] ) {
        WP_CLI::error( 'Generated content checksum mismatch: ' . $name );
    }
    $payloads[ $name ] = array( 'id' => $id, 'content' => $content );
}
WP_CLI::log( 'Guards passed for page and Global Styles.' );
if ( $dry_run ) {
    WP_CLI::success( 'Dry run complete.' );
    return;
}
global $wpdb;
$wpdb->query( 'START TRANSACTION' );
try {
    foreach ( $payloads as $name => $payload ) {
        $result = wp_update_post( wp_slash( array( 'ID' => $payload['id'],
            'post_content' => $payload['content'] ) ), true );
        if ( is_wp_error( $result ) || (int) $result !== $payload['id'] ) {
            throw new RuntimeException( 'WordPress rejected ' . $name );
        }
    }
    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( $error->getMessage() );
}
wp_cache_flush();
foreach ( $payloads as $name => $payload ) {
    clean_post_cache( $payload['id'] );
    if ( hash( 'sha256', get_post( $payload['id'] )->post_content ) !==
        $manifest['report'][ $name ]['afterHash'] ) {
        WP_CLI::error( 'Post-update checksum mismatch: ' . $name );
    }
}
WP_CLI::success( 'Revision 2026-09-24g installed and verified.' );
