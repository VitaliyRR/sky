<?php
/** Sitemap for the single-page landing. @package SkySend */
if (!defined('ABSPATH')) {
    exit;
}

function skysend_register_landing_sitemap(): void
{
    if (!class_exists('WP_Sitemaps_Provider')) {
        return;
    }

    wp_register_sitemap_provider('skysend', new class extends WP_Sitemaps_Provider {
        public function __construct()
        {
            $this->name = 'skysend';
            $this->object_type = 'page';
        }

        public function get_url_list($page_num, $object_subtype = '')
        {
            if ((int) $page_num !== 1 || $object_subtype !== '') {
                return array();
            }
            return array(array('loc' => home_url('/')));
        }

        public function get_max_num_pages($object_subtype = '')
        {
            return $object_subtype === '' ? 1 : 0;
        }
    });
}
add_action('init', 'skysend_register_landing_sitemap', 15);

function skysend_filter_sitemap_providers($provider, string $name)
{
    // This site has no public blog, taxonomy archives or author pages.
    return in_array($name, array('posts', 'taxonomies', 'users'), true) ? false : $provider;
}
add_filter('wp_sitemaps_add_provider', 'skysend_filter_sitemap_providers', 10, 2);
