<?php
/** Guarded update of the editable landing page after restoring the provider categories. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$dry_run = in_array( 'dry-run', $args, true );
if ( ! $base || ! is_dir( $base ) ) {
    WP_CLI::error( 'Usage: wp eval-file apply-revision-20260925d.php OUTPUT_DIR [dry-run]' );
}
$manifest_file = is_readable( $base . '/revision-manifest-20260925d.json' )
    ? $base . '/revision-manifest-20260925d.json' : $base . '/manifest.json';
$manifest = is_readable( $manifest_file ) ? json_decode( file_get_contents( $manifest_file ), true ) : null;
$record = $manifest['report']['page'] ?? null;
$file = $base . '/page.html';
$post = get_post( 67 );
if ( ! is_array( $manifest ) || ( $manifest['revision'] ?? '' ) !== '2026-09-25d' ||
    ! $record || ! $post || $post->post_type !== 'page' || ! is_readable( $file ) ||
    ( $manifest['partnerPdfs'] ?? 0 ) !== 6 || ( $manifest['categories'] ?? 0 ) !== 10 ||
    ( $manifest['entries'] ?? 0 ) !== 2600 ) {
    WP_CLI::error( 'Invalid landing-page revision payload' );
}
if ( hash( 'sha256', $post->post_content ) !== $record['beforeHash'] ) {
    WP_CLI::error( 'Live landing page changed; refusing to overwrite it' );
}
$content = file_get_contents( $file );
if ( hash( 'sha256', $content ) !== $record['afterHash'] ||
    substr_count( $content, 'Скачать презентацию · PDF,' ) !== 6 ||
    str_contains( $content, 'Распределённая архитектура — серверы в разных центрах' ) ) {
    WP_CLI::error( 'Generated page checksum or copy mismatch' );
}
$catalog_file = WPMU_PLUGIN_DIR . '/skysend-provider-catalog/catalog.json';
$catalog = is_readable( $catalog_file ) ? json_decode( file_get_contents( $catalog_file ), true ) : null;
$categories = $catalog['categories'] ?? null;
if ( ! is_array( $categories ) || count( $categories ) !== 10 ||
    array_sum( array_map( static fn( $category ) => count( $category['items'] ?? array() ), $categories ) ) !== 2600 ) {
    WP_CLI::error( 'Restored provider catalogue is not installed' );
}
foreach ( $manifest['iconHashes'] ?? array() as $filename => $hash ) {
    $id = (int) ( $manifest['mediaIds'][ $filename ] ?? 0 );
    $attached = $id ? get_attached_file( $id ) : false;
    if ( get_post_type( $id ) !== 'attachment' || ! $attached || basename( $attached ) !== $filename ||
        ! is_file( $attached ) || hash_file( 'sha256', $attached ) !== $hash ) {
        WP_CLI::error( 'ALLVEND icon missing or changed: ' . $filename );
    }
}
if ( count( $manifest['iconHashes'] ?? array() ) !== 9 ) {
    WP_CLI::error( 'ALLVEND icon count mismatch' );
}
WP_CLI::log( 'Guards passed: page, provider catalogue and nine media attachments.' );
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
WP_CLI::success( 'Landing page revision 2026-09-25d installed and verified.' );
