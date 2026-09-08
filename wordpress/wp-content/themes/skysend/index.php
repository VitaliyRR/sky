<?php
/**
 * Fallback template.
 *
 * @package SkySend
 */

get_header();
?>
<main class="simple-page shell" id="main">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h1><?php the_title(); ?></h1>
                <?php the_content(); ?>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <h1>SkySend</h1>
    <?php endif; ?>
</main>
<?php
get_footer();
