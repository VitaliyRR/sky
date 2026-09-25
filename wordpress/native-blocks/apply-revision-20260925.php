<?php
/** Guarded switch of editable tabs to the complete local provider catalog. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$dry_run = in_array( 'dry-run', $args, true );
if ( ! $base || ! is_dir( $base ) ) {
    WP_CLI::error( 'Usage: wp eval-file apply-revision-20260925.php OUTPUT_DIR [dry-run]' );
}
$manifest_file = is_readable( $base . '/revision-manifest-20260925.json' )
    ? $base . '/revision-manifest-20260925.json' : $base . '/manifest.json';
$manifest = is_readable( $manifest_file ) ? json_decode( file_get_contents( $manifest_file ), true ) : null;
$record = $manifest['report']['page'] ?? null;
$file = $base . '/page.html';
$post = get_post( 67 );
if ( ! is_array( $manifest ) || ( $manifest['revision'] ?? '' ) !== '2026-09-25' ||
    ( $manifest['categories'] ?? 0 ) !== 15 || ( $manifest['entries'] ?? 0 ) !== 5000 ||
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
$catalog_file = WP_CONTENT_DIR . '/mu-plugins/skysend-provider-catalog/catalog.json';
$catalog = is_readable( $catalog_file ) ? json_decode( file_get_contents( $catalog_file ), true ) : null;
if ( ! is_array( $catalog ) || ( $catalog['version'] ?? '' ) !== '2026-09-25' ||
    ( $catalog['source']['sha256'] ?? '' ) !== $manifest['sourceArchiveSha256'] ||
    count( $catalog['categories'] ?? array() ) !== 15 ) {
    WP_CLI::error( 'Expected installed catalog missing' );
}
$ids = array();
foreach ( $catalog['categories'] as $category ) {
    foreach ( $category['items'] as $item ) {
        $id = $item['id'] ?? '';
        $logo = $item['logo'] ?? '';
        if ( isset( $ids[ $id ] ) || ! preg_match( '~^/wp-content/uploads/skysend-providers-20260925/[0-9]{4}\.webp$~D', $logo ) ||
            ! is_file( ABSPATH . ltrim( $logo, '/' ) ) ) {
            WP_CLI::error( 'Missing or duplicate provider logo: ' . $id );
        }
        $ids[ $id ] = true;
    }
}
if ( count( $ids ) !== 5000 ) {
    WP_CLI::error( 'Expected exactly 5,000 unique entries' );
}
WP_CLI::log( 'Guards passed: live page, 15 categories and 5,000 local logos.' );
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
WP_CLI::success( 'Archive provider catalog installed and verified.' );
