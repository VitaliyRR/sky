<?php
/** Replace only the editable ALLVEND section image on the SkySend homepage. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}

$attachment_id = isset( $args[0] ) ? (int) $args[0] : 0;
$dry_run = in_array( 'dry-run', $args, true );
$page = get_post( 67 );
$attachment = get_post( $attachment_id );
if ( ! $page || $page->post_type !== 'page' || $page->post_status !== 'publish' ) {
    WP_CLI::error( 'Homepage 67 is not the expected published page.' );
}
if ( ! $attachment || $attachment->post_type !== 'attachment' ||
    ! wp_attachment_is_image( $attachment_id ) ) {
    WP_CLI::error( 'The requested media ID is not an image attachment.' );
}

$new_url = wp_get_attachment_url( $attachment_id );
$new_file = get_attached_file( $attachment_id );
if ( ! $new_url || ! $new_file || ! is_readable( $new_file ) ||
    basename( $new_file ) !== 'allvend_main.png' ) {
    WP_CLI::error( 'The new attachment is not the supplied allvend_main.png.' );
}

$content = $page->post_content;
if ( substr_count( $content, 'id="allvend"' ) !== 1 ||
    substr_count( $content, 'Универсальное ПО ALLVEND' ) !== 1 ) {
    WP_CLI::error( 'ALLVEND section guard failed.' );
}

$pattern = '~<!-- wp:image \{[^\r\n]*\} -->\s*<figure\b[^>]*>.*?</figure>\s*<!-- /wp:image -->~s';
preg_match_all( $pattern, $content, $matches );
$old_blocks = array_values( array_filter( $matches[0], static function ( $block ) {
    return str_contains( $block, 'banner-allvend-20260914.webp' );
} ) );
if ( count( $old_blocks ) !== 1 ||
    ! str_contains( $old_blocks[0], '"id":13' ) ||
    ! str_contains( $old_blocks[0], 'class="wp-image-13"' ) ) {
    WP_CLI::error( 'Expected exactly one original ALLVEND image block with attachment 13.' );
}

$attrs = array(
    'id' => $attachment_id,
    'width' => '100%',
    'sizeSlug' => 'full',
    'linkDestination' => 'none',
    'style' => array( 'border' => array( 'radius' => '5px' ) ),
);
$figure = '<figure class="wp-block-image size-full is-resized has-custom-border">' .
    '<img src="' . esc_url( $new_url ) . '" alt="Экран программного обеспечения ALLVEND" ' .
    'class="wp-image-' . $attachment_id . '" ' .
    'style="border-radius:5px;width:100%;height:auto"/></figure>';
$new_block = serialize_block( array(
    'blockName' => 'core/image',
    'attrs' => $attrs,
    'innerBlocks' => array(),
    'innerHTML' => $figure,
    'innerContent' => array( $figure ),
) );
$updated = str_replace( $old_blocks[0], $new_block, $content, $count );
if ( $count !== 1 || $updated === $content ||
    substr_count( $updated, $new_url ) !== 1 ) {
    WP_CLI::error( 'The image block replacement was not unique.' );
}

WP_CLI::log( 'New image: ' . $new_url );
WP_CLI::log( 'Homepage content before: ' . hash( 'sha256', $content ) );
WP_CLI::log( 'Homepage content after:  ' . hash( 'sha256', $updated ) );
if ( $dry_run ) {
    WP_CLI::success( 'Dry run complete; no page was changed.' );
    return;
}

$result = wp_update_post( wp_slash( array(
    'ID' => 67,
    'post_content' => $updated,
) ), true );
if ( is_wp_error( $result ) || (int) $result !== 67 ) {
    WP_CLI::error( 'WordPress rejected the homepage update.' );
}
clean_post_cache( 67 );
wp_cache_flush();
$saved = get_post( 67 )->post_content;
if ( $saved !== $updated || ! str_contains( $saved, 'class="wp-image-' . $attachment_id . '"' ) ||
    str_contains( $saved, 'banner-allvend-20260914.webp' ) ) {
    WP_CLI::error( 'Homepage verification failed after update.' );
}
WP_CLI::success( 'ALLVEND image replaced without 3:2 cropping.' );
