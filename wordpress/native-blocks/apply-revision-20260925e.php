<?php
/** Guarded update restoring original branded ALLVEND pictograms in native blocks. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$dry_run = in_array( 'dry-run', $args, true );
if ( ! $base || ! is_dir( $base ) ) {
    WP_CLI::error( 'Usage: wp eval-file apply-revision-20260925e.php OUTPUT_DIR [dry-run]' );
}
$manifest_file = is_readable( $base . '/revision-manifest-20260925e.json' )
    ? $base . '/revision-manifest-20260925e.json' : $base . '/manifest.json';
$manifest = is_readable( $manifest_file ) ? json_decode( file_get_contents( $manifest_file ), true ) : null;
$record = $manifest['report']['page'] ?? null;
$file = $base . '/page.html';
$post = get_post( 67 );
if ( ! is_array( $manifest ) || ( $manifest['revision'] ?? '' ) !== '2026-09-25e' ||
    ! $record || ! $post || $post->post_type !== 'page' || ! is_readable( $file ) ||
    count( $manifest['replacements'] ?? array() ) !== 4 || count( $manifest['assetHashes'] ?? array() ) !== 4 ) {
    WP_CLI::error( 'Invalid ALLVEND revision payload' );
}
if ( hash( 'sha256', $post->post_content ) !== $record['beforeHash'] ) {
    WP_CLI::error( 'Live landing page changed; refusing to overwrite it' );
}
$content = file_get_contents( $file );
if ( hash( 'sha256', $content ) !== $record['afterHash'] ) {
    WP_CLI::error( 'Generated page checksum mismatch' );
}
foreach ( $manifest['replacements'] as $item ) {
    $id = (int) ( $item['id'] ?? 0 );
    $filename = $item['filename'] ?? '';
    $expected_hash = $manifest['assetHashes'][ $filename ] ?? null;
    $attached = $id ? get_attached_file( $id ) : false;
    if ( ! $attached || get_post_type( $id ) !== 'attachment' || basename( $attached ) !== $filename ||
        ! is_file( $attached ) || ! is_string( $expected_hash ) ||
        hash_file( 'sha256', $attached ) !== $expected_hash ) {
        WP_CLI::error( 'Original icon attachment missing or changed: ' . $filename );
    }
}
WP_CLI::log( 'Guards passed: current landing page and four original icon attachments.' );
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
WP_CLI::success( 'ALLVEND icon revision 2026-09-25e installed and verified.' );
