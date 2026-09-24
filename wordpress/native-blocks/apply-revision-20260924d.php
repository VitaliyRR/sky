<?php
/** Guarded editable page update for two corrected SkySend illustrations. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$dry_run = in_array( 'dry-run', $args, true );
if ( ! $base || ! is_dir( $base ) ) {
    WP_CLI::error( 'Usage: wp eval-file apply-revision-20260924d.php OUTPUT_DIR [dry-run]' );
}
$manifest_file = is_readable( $base . '/revision-manifest-20260924d.json' )
    ? $base . '/revision-manifest-20260924d.json' : $base . '/manifest.json';
$manifest = is_readable( $manifest_file ) ? json_decode( file_get_contents( $manifest_file ), true ) : null;
$record = $manifest['report']['page'] ?? null;
$file = $base . '/page.html';
$post = get_post( 67 );
if ( ! is_array( $manifest ) || ( $manifest['revision'] ?? '' ) !== '2026-09-24d' ||
    ! $record || ! $post || ! is_readable( $file ) ) {
    WP_CLI::error( 'Invalid revision payload' );
}
if ( hash( 'sha256', $post->post_content ) !== $record['beforeHash'] ) {
    WP_CLI::error( 'Live page changed; refusing to overwrite it' );
}
$content = file_get_contents( $file );
if ( hash( 'sha256', $content ) !== $record['afterHash'] ) {
    WP_CLI::error( 'Generated page checksum mismatch' );
}
foreach ( array( 'banner-finance-income-20260924.webp', 'partner-gateways-xml-20260924.webp' ) as $filename ) {
    $id = (int) ( $manifest['mediaIds'][ $filename ] ?? 0 );
    if ( get_post_type( $id ) !== 'attachment' || ! str_ends_with( (string) get_attached_file( $id ), '/' . $filename ) ) {
        WP_CLI::error( 'Missing media attachment: ' . $filename );
    }
}
WP_CLI::log( 'Guards passed: page 67 and two new media attachments.' );
if ( $dry_run ) {
    WP_CLI::success( 'Dry run complete.' );
    return;
}
$updated = wp_update_post( wp_slash( array( 'ID' => 67, 'post_content' => $content ) ), true );
if ( is_wp_error( $updated ) || (int) $updated !== 67 ) {
    WP_CLI::error( 'WordPress rejected the page update' );
}
wp_cache_flush();
clean_post_cache( 67 );
if ( hash( 'sha256', get_post( 67 )->post_content ) !== $record['afterHash'] ) {
    WP_CLI::error( 'Post-update checksum mismatch' );
}
WP_CLI::success( 'Revision 2026-09-24d installed and verified.' );
