<?php
/** Replace only the previous SkySend brand in SEOPress Organization metadata. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}
$options = get_option( 'seopress_social_option_name' );
$old = 'http://31.129.98.28/wp-content/uploads/2026/09/skysend-logo.png';
$new_id = 205;
$new = wp_get_attachment_url( $new_id );
if ( ! is_array( $options ) || ( $options['seopress_social_knowledge_img'] ?? '' ) !== $old ||
    get_post_type( $new_id ) !== 'attachment' ||
    ! str_ends_with( (string) get_attached_file( $new_id ), '/skysend-wordmark-light.png' ) ) {
    WP_CLI::error( 'SEO logo guard failed; settings may have changed' );
}
$options['seopress_social_knowledge_img'] = $new;
if ( ! update_option( 'seopress_social_option_name', $options ) ) {
    WP_CLI::error( 'SEOPress option update failed' );
}
wp_cache_flush();
if ( ( get_option( 'seopress_social_option_name' )['seopress_social_knowledge_img'] ?? '' ) !== $new ) {
    WP_CLI::error( 'SEOPress option did not persist' );
}
WP_CLI::success( 'Organization logo updated to media attachment ' . $new_id );
