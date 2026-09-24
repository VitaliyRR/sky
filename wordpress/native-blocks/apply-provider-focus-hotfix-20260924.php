<?php
/** Remove mouse-focus outline from provider tabs, preserving keyboard focus. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$file = $args[0] ?? null;
$before = '1aa89005b90bff3a111457ec9b334f644f82c9ad02c68dcfd30f7e673a660802';
$after = 'd0ae23168386331c6c4229c4eebeb5293407a89b1795de763c70e789c96574f2';
if ( ! $file || ! is_readable( $file ) || hash( 'sha256', get_post( 66 )->post_content ) !== $before ) {
    WP_CLI::error( 'Current Global Styles changed; no update made.' );
}
$content = file_get_contents( $file );
if ( hash( 'sha256', $content ) !== $after ) {
    WP_CLI::error( 'Style file checksum mismatch.' );
}
$result = wp_update_post( wp_slash( array( 'ID' => 66, 'post_content' => $content ) ), true );
if ( is_wp_error( $result ) || (int) $result !== 66 ) {
    WP_CLI::error( 'WordPress rejected the style update.' );
}
clean_post_cache( 66 );
wp_cache_flush();
if ( hash( 'sha256', get_post( 66 )->post_content ) !== $after ) {
    WP_CLI::error( 'Style update verification failed.' );
}
WP_CLI::success( 'Provider mouse-focus outline removed; keyboard focus preserved.' );
