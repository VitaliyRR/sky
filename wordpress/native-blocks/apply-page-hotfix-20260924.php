<?php
/** Narrow follow-up: restore the approved Partners heading and section proportions. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$base = $args[0] ?? null;
$manifest = $base ? json_decode( file_get_contents( $base . '/manifest.json' ), true ) : null;
$file = $base ? $base . '/page.html' : null;
$before_hash = 'b8cb36d9ef01fd3c275f37426ebd5ed0bc3e52f7e9bd28f24e5616a5f2902d06';
if ( ! $manifest || ! is_readable( $file ) || hash( 'sha256', get_post( 67 )->post_content ) !== $before_hash ) {
    WP_CLI::error( 'Current page does not match the first 24.09 revision; no change made.' );
}
$content = file_get_contents( $file );
if ( hash( 'sha256', $content ) !== $manifest['reports']['page']['afterHash'] ) {
    WP_CLI::error( 'Final page checksum mismatch.' );
}
$result = wp_update_post( wp_slash( array( 'ID' => 67, 'post_content' => $content ) ), true );
if ( is_wp_error( $result ) || (int) $result !== 67 ) {
    WP_CLI::error( 'WordPress refused to update page 67.' );
}
clean_post_cache( 67 );
if ( hash( 'sha256', get_post( 67 )->post_content ) !== $manifest['reports']['page']['afterHash'] ) {
    WP_CLI::error( 'Final page verification failed.' );
}
WP_CLI::success( 'Final 24.09 page revision installed and verified.' );
