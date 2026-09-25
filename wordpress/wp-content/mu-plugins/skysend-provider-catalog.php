<?php
/**
 * Plugin Name: SkySend Provider Catalog (MU)
 * Description: Serves the owner-supplied provider catalog in small pages.
 * Version: 0.2.0
 */

defined( 'ABSPATH' ) || exit;

const SKYSEND_PROVIDER_CATALOG_PAGE_SIZE = 9;

/** Load the deployment's local catalog. Never request another site at runtime. */
function skysend_provider_catalog_data() {
	static $catalog = null;
	if ( null !== $catalog ) {
		return $catalog;
	}

	$file = __DIR__ . '/skysend-provider-catalog/catalog.json';
	if ( ! is_readable( $file ) ) {
		$catalog = new WP_Error( 'skysend_catalog_missing', 'Каталог провайдеров недоступен.', array( 'status' => 503 ) );
		return $catalog;
	}

	try {
		$decoded = json_decode( file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
	} catch ( JsonException $error ) {
		$catalog = new WP_Error( 'skysend_catalog_invalid', 'Каталог провайдеров повреждён.', array( 'status' => 503 ) );
		return $catalog;
	}

	if ( ! is_array( $decoded ) || ! isset( $decoded['categories'] ) || ! is_array( $decoded['categories'] ) || array_is_list( $decoded['categories'] ) ) {
		$catalog = new WP_Error( 'skysend_catalog_invalid', 'Неверная структура каталога.', array( 'status' => 503 ) );
		return $catalog;
	}

	foreach ( $decoded['categories'] as $key => $category ) {
		if ( ! is_string( $key ) || ! preg_match( '/^[a-z0-9_-]{1,64}$/D', $key ) || ! is_array( $category ) || ! isset( $category['label'], $category['items'] ) || ! is_string( $category['label'] ) || ! is_array( $category['items'] ) || ! array_is_list( $category['items'] ) ) {
			$catalog = new WP_Error( 'skysend_catalog_invalid', 'Неверная категория каталога.', array( 'status' => 503 ) );
			return $catalog;
		}
		foreach ( $category['items'] as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['id'], $item['name'] ) || ! is_string( $item['id'] ) || ! preg_match( '/^[a-z0-9_-]{1,100}$/D', $item['id'] ) || ! is_string( $item['name'] ) || '' === trim( $item['name'] ) || strlen( $item['name'] ) > 1000 ) {
				$catalog = new WP_Error( 'skysend_catalog_invalid', 'Неверная запись каталога.', array( 'status' => 503 ) );
				return $catalog;
			}
			if ( isset( $item['logo'] ) && ( ! is_string( $item['logo'] ) || ! preg_match( '~^/wp-content/uploads/[A-Za-z0-9/_-]+\.(?:png|jpe?g|webp|svg)$~iD', $item['logo'] ) ) ) {
				$catalog = new WP_Error( 'skysend_catalog_invalid', 'Неверный адрес логотипа.', array( 'status' => 503 ) );
				return $catalog;
			}
		}
	}

	$catalog = $decoded;
	return $catalog;
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'skysend/v1',
			'/providers',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $request ) {
					$category_key = $request->get_param( 'category' );
					$page_value   = $request->get_param( 'page' );
					if ( ! is_string( $category_key ) || ! preg_match( '/^[a-z0-9_-]{1,64}$/D', $category_key ) ) {
						return new WP_Error( 'skysend_category_invalid', 'Неверная категория.', array( 'status' => 400 ) );
					}
					if ( null === $page_value ) {
						$page_value = 1;
					}
					if ( ! ( is_int( $page_value ) && $page_value > 0 ) && ! ( is_string( $page_value ) && preg_match( '/^[1-9][0-9]{0,6}$/D', $page_value ) ) ) {
						return new WP_Error( 'skysend_page_invalid', 'Неверный номер страницы.', array( 'status' => 400 ) );
					}
					$page = (int) $page_value;
					$catalog = skysend_provider_catalog_data();
					if ( is_wp_error( $catalog ) ) {
						return $catalog;
					}
					if ( ! isset( $catalog['categories'][ $category_key ] ) ) {
						return new WP_Error( 'skysend_category_unknown', 'Категория не найдена.', array( 'status' => 404 ) );
					}
					$entries = $catalog['categories'][ $category_key ]['items'];
					$total   = count( $entries );
					$pages   = max( 1, (int) ceil( $total / SKYSEND_PROVIDER_CATALOG_PAGE_SIZE ) );
					if ( $page > $pages ) {
						return new WP_Error( 'skysend_page_out_of_range', 'Страница не найдена.', array( 'status' => 404 ) );
					}
					$items = array_map(
						static function ( $item ) {
							return array(
								'id'   => $item['id'],
								'name' => $item['name'],
								'logo' => $item['logo'] ?? null,
							);
						},
						array_slice( $entries, ( $page - 1 ) * SKYSEND_PROVIDER_CATALOG_PAGE_SIZE, SKYSEND_PROVIDER_CATALOG_PAGE_SIZE )
					);
					return rest_ensure_response(
						array(
							'category' => $category_key,
							'page'     => $page,
							'items'    => $items,
							'total'    => $total,
							'pages'    => $pages,
						)
					);
				},
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! is_front_page() ) {
			return;
		}
		$directory = __DIR__ . '/skysend-provider-catalog/';
		$base_url  = plugin_dir_url( __FILE__ ) . 'skysend-provider-catalog/';
		$style     = $directory . 'catalog.css';
		$script    = $directory . 'catalog.js';
		wp_enqueue_style( 'skysend-provider-catalog', $base_url . 'catalog.css', array(), filemtime( $style ) );
		wp_enqueue_script( 'skysend-provider-catalog', $base_url . 'catalog.js', array(), filemtime( $script ), true );
		wp_add_inline_script(
			'skysend-provider-catalog',
			'window.SkySendProviderCatalog=' . wp_json_encode( array( 'endpoint' => esc_url_raw( rest_url( 'skysend/v1/providers' ) ) ) ) . ';',
			'before'
		);
	}
);
