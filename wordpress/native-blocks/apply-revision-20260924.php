<?php
/** Run only through WP-CLI on the development VM after importing revision media. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$dry_run = in_array( 'dry-run', $args, true );
if ( ! $base || ! is_dir( $base ) ) {
    WP_CLI::error( 'Usage: wp eval-file apply-revision-20260924.php /root/revision-20260924 [dry-run]' );
}
$manifest = json_decode( file_get_contents( $base . '/manifest.json' ), true );
if ( ! is_array( $manifest ) || ( $manifest['revision'] ?? '' ) !== '2026-09-24' ) {
    WP_CLI::error( 'Invalid revision manifest' );
}
$payloads = array();
foreach ( array( 'page', 'header', 'footer', 'styles' ) as $name ) {
    $record = $manifest['reports'][ $name ] ?? null;
    $file = $base . '/' . $name . ( $name === 'styles' ? '.json' : '.html' );
    $post = $record ? get_post( (int) $record['id'] ) : null;
    if ( ! $post || ! is_readable( $file ) ) {
        WP_CLI::error( 'Missing post or file: ' . $name );
    }
    if ( hash( 'sha256', $post->post_content ) !== $record['beforeHash'] ) {
        WP_CLI::error( 'Live content changed; refusing to overwrite: ' . $name );
    }
    $content = file_get_contents( $file );
    if ( hash( 'sha256', $content ) !== $record['afterHash'] ) {
        WP_CLI::error( 'Revision file checksum mismatch: ' . $name );
    }
    $payloads[ $name ] = array( 'id' => (int) $record['id'], 'content' => $content );
}
$site_icon_id = (int) ( $manifest['siteIconId'] ?? 0 );
if ( get_post_type( $site_icon_id ) !== 'attachment' || get_post_mime_type( $site_icon_id ) !== 'image/png' ) {
    WP_CLI::error( 'New WordPress site icon is missing' );
}
WP_CLI::log( 'Guards passed for page 67, header 68, footer 69 and Global Styles 66.' );
if ( $dry_run ) {
    WP_CLI::success( 'Dry run completed; no content changed.' );
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
    if ( ! update_option( 'site_icon', $site_icon_id ) && (int) get_option( 'site_icon' ) !== $site_icon_id ) {
        throw new RuntimeException( 'Could not update site icon' );
    }
    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( $error->getMessage() );
}
wp_cache_flush();
foreach ( $payloads as $name => $payload ) {
    clean_post_cache( $payload['id'] );
    $actual = get_post( $payload['id'] )->post_content;
    if ( hash( 'sha256', $actual ) !== $manifest['reports'][ $name ]['afterHash'] ) {
        WP_CLI::error( 'Post-update verification failed: ' . $name );
    }
}
WP_CLI::success( 'Revision 2026-09-24 installed and verified; site icon ID ' . $site_icon_id );
