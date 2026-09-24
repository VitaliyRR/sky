<?php
/** Guarded update of editable Gutenberg blocks on the SkySend development VM. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$dry_run = in_array( 'dry-run', $args, true );
if ( ! $base || ! is_dir( $base ) ) {
    WP_CLI::error( 'Usage: wp eval-file apply-revision-20260924b.php OUTPUT_DIR [dry-run]' );
}
$manifest = json_decode( file_get_contents( $base . '/manifest.json' ), true );
if ( ! is_array( $manifest ) || ( $manifest['revision'] ?? '' ) !== '2026-09-24b' ) {
    WP_CLI::error( 'Invalid revision manifest' );
}
$posts = array( 'page' => 67, 'footer' => 69, 'styles' => 66 );
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
foreach ( $manifest['mediaIds'] as $filename => $id ) {
    if ( preg_match( '/^(provider-[0-9]+|banner-finance-terminal-20260924|fastsys-boot-official-20260924|payment-kiosk-20260924|zip-20260924)\./', $filename ) && get_post_type( (int) $id ) !== 'attachment' ) {
        WP_CLI::error( 'Missing imported media: ' . $filename );
    }
}
WP_CLI::log( 'Guards passed: page 67, footer 69, Global Styles 66.' );
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
WP_CLI::success( 'Revision 2026-09-24b installed and verified.' );
