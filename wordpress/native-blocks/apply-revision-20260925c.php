<?php
/** Guarded editable-template-part update for the compact header. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$dry_run = in_array( 'dry-run', $args, true );
if ( ! $base || ! is_dir( $base ) ) {
    WP_CLI::error( 'Usage: wp eval-file apply-revision-20260925c.php OUTPUT_DIR [dry-run]' );
}
$manifest_file = is_readable( $base . '/revision-manifest-20260925c.json' )
    ? $base . '/revision-manifest-20260925c.json' : $base . '/manifest.json';
$manifest = is_readable( $manifest_file ) ? json_decode( file_get_contents( $manifest_file ), true ) : null;
$record = $manifest['report']['header'] ?? null;
$file = $base . '/header.html';
$post = get_post( 68 );
if ( ! is_array( $manifest ) || ( $manifest['revision'] ?? '' ) !== '2026-09-25c' ||
    ! $record || ! $post || $post->post_type !== 'wp_template_part' || ! is_readable( $file ) ) {
    WP_CLI::error( 'Invalid header revision payload' );
}
if ( hash( 'sha256', $post->post_content ) !== $record['beforeHash'] ) {
    WP_CLI::error( 'Live header changed; refusing to overwrite it' );
}
$content = file_get_contents( $file );
if ( hash( 'sha256', $content ) !== $record['afterHash'] ||
    ( $record['logoWidth'] ?? '' ) !== '96px' || ( $record['verticalPadding'] ?? '' ) !== '6px' ) {
    WP_CLI::error( 'Generated header checksum or dimensions mismatch' );
}
$logo_file = get_attached_file( 400 );
if ( get_post_type( 400 ) !== 'attachment' || ! $logo_file ||
    basename( $logo_file ) !== 'skysend-header-20260924h.png' || ! is_file( $logo_file ) ) {
    WP_CLI::error( 'Active header logo attachment missing' );
}
WP_CLI::log( 'Guards passed: active header and logo attachment.' );
if ( $dry_run ) {
    WP_CLI::success( 'Dry run complete.' );
    return;
}
$updated = wp_update_post( wp_slash( array( 'ID' => 68, 'post_content' => $content ) ), true );
if ( is_wp_error( $updated ) || (int) $updated !== 68 ) {
    WP_CLI::error( 'WordPress rejected the header update' );
}
wp_cache_flush();
clean_post_cache( 68 );
if ( hash( 'sha256', get_post( 68 )->post_content ) !== $record['afterHash'] ) {
    WP_CLI::error( 'Post-update header checksum mismatch' );
}
WP_CLI::success( 'Compact header installed and verified.' );
