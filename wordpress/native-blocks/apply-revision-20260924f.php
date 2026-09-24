<?php
/** Apply only to the exact VM database state recorded before this revision. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$dry_run = in_array( 'dry-run', $args, true );
$manifest = $base && is_readable( $base . '/manifest.json' )
    ? json_decode( file_get_contents( $base . '/manifest.json' ), true ) : null;
if ( ! is_array( $manifest ) || ( $manifest['revision'] ?? '' ) !== '2026-09-24f' ||
    (int) ( $manifest['categories'] ?? 0 ) !== 10 || (int) ( $manifest['providers'] ?? 0 ) !== 279 ) {
    WP_CLI::error( 'Invalid revision manifest' );
}
$names = array( 'page' => 67, 'header' => 68, 'footer' => 69, 'styles' => 66 );
$payloads = array();
foreach ( $names as $name => $id ) {
    $record = $manifest['report'][ $name ] ?? null;
    $file = $base . '/' . $name . ( $name === 'styles' ? '.json' : '.html' );
    $post = get_post( $id );
    if ( ! $record || ! $post || ! is_readable( $file ) ||
        hash( 'sha256', $post->post_content ) !== $record['beforeHash'] ) {
        WP_CLI::error( 'Live content changed or payload missing: ' . $name );
    }
    $content = file_get_contents( $file );
    if ( hash( 'sha256', $content ) !== $record['afterHash'] ) {
        WP_CLI::error( 'Generated checksum mismatch: ' . $name );
    }
    $payloads[ $name ] = array( 'id' => $id, 'content' => $content );
}
if ( (int) get_option( 'site_icon' ) !== (int) $manifest['previousIconId'] ) {
    WP_CLI::error( 'Site icon changed since source capture' );
}
$ids = $manifest['mediaIds'];
foreach ( $manifest['mediaNames'] as $name => $filename ) {
    $id = (int) ( $ids[ $filename ] ?? 0 );
    if ( get_post_type( $id ) !== 'attachment' ||
        ! str_ends_with( (string) get_attached_file( $id ), '/' . $filename ) ) {
        WP_CLI::error( 'Missing imported media: ' . $filename );
    }
}
$previous_allvend = get_attached_file( 13 );
if ( get_post_type( 13 ) !== 'attachment' ||
    ! str_ends_with( (string) $previous_allvend, '/banner-allvend-20260914.webp' ) ) {
    WP_CLI::error( 'Previous ALLVEND image missing' );
}
$seo = get_option( 'seopress_social_option_name' );
$old_logo = 'http://31.129.98.28/wp-content/uploads/2026/09/skysend-wordmark-light.png';
if ( ! is_array( $seo ) || ( $seo['seopress_social_knowledge_img'] ?? '' ) !== $old_logo ) {
    WP_CLI::error( 'SEO organization logo changed since source capture' );
}
$new_logo_id = (int) $ids[ $manifest['mediaNames']['logoLight'] ];
$new_logo = wp_get_attachment_url( $new_logo_id );
$new_icon_id = (int) $ids[ $manifest['mediaNames']['icon'] ];
WP_CLI::log( 'Guards passed for page, header, footer, styles, media, icon and SEO logo.' );
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
    if ( ! update_option( 'site_icon', $new_icon_id ) ) {
        throw new RuntimeException( 'WordPress rejected site icon' );
    }
    $seo['seopress_social_knowledge_img'] = $new_logo;
    if ( ! update_option( 'seopress_social_option_name', $seo ) ) {
        throw new RuntimeException( 'WordPress rejected SEO logo' );
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
if ( (int) get_option( 'site_icon' ) !== $new_icon_id ||
    ( get_option( 'seopress_social_option_name' )['seopress_social_knowledge_img'] ?? '' ) !== $new_logo ) {
    WP_CLI::error( 'Brand settings did not persist' );
}
WP_CLI::success( 'Revision 2026-09-24f installed and verified.' );
