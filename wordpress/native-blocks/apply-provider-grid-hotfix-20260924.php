<?php
/** Narrow post-QA adjustment: symmetrical provider cards on desktop and mobile. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$manifest = $base ? json_decode( file_get_contents( $base . '/manifest.json' ), true ) : null;
if ( ! $manifest || ( $manifest['revision'] ?? '' ) !== '2026-09-24b' ) {
    WP_CLI::error( 'Invalid source revision' );
}
$parts = array(
    'page' => array( 67, 'd8bab1c109e4fc94b1c662f03de65df0941b2a58e6f59637ef525597d3a8f112', 'html' ),
    'styles' => array( 66, '0128238bdfa12d83499f4c5ecb528aea19f03df44645068b008ade1ec4d067d7', 'json' ),
);
$updates = array();
foreach ( $parts as $name => array( $id, $before, $extension ) ) {
    $file = $base . '/' . $name . '.' . $extension;
    if ( ! is_readable( $file ) || hash( 'sha256', get_post( $id )->post_content ) !== $before ) {
        WP_CLI::error( 'Live content changed: ' . $name );
    }
    $content = file_get_contents( $file );
    if ( hash( 'sha256', $content ) !== $manifest['report'][ $name ]['afterHash'] ) {
        WP_CLI::error( 'Generated content changed: ' . $name );
    }
    $updates[ $name ] = array( $id, $content );
}
global $wpdb;
$wpdb->query( 'START TRANSACTION' );
try {
    foreach ( $updates as $name => array( $id, $content ) ) {
        $result = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $content ) ), true );
        if ( is_wp_error( $result ) || (int) $result !== $id ) {
            throw new RuntimeException( 'WordPress rejected ' . $name );
        }
    }
    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( $error->getMessage() );
}
wp_cache_flush();
foreach ( $updates as $name => array( $id ) ) {
    clean_post_cache( $id );
    if ( hash( 'sha256', get_post( $id )->post_content ) !== $manifest['report'][ $name ]['afterHash'] ) {
        WP_CLI::error( 'Verification failed: ' . $name );
    }
}
WP_CLI::success( 'Provider grid hotfix verified.' );
