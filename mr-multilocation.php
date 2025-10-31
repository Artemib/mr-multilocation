<?php
/*
Plugin Name: MR Multilocation
Description: Multiregional routing and admin SPA. Vue 3 + Vite + Tailwind admin interface.
Version: 0.1.0
Author: MR
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MR_ML_PLUGIN_FILE', __FILE__ );
define( 'MR_ML_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MR_ML_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation: set a transient to redirect to settings page once.
 */
function mr_ml_activate(): void {
	set_transient( 'mr_ml_do_activation_redirect', 1, 30 );
}
register_activation_hook( __FILE__, 'mr_ml_activate' );

/**
 * After activation redirect to plugin page.
 */
function mr_ml_maybe_redirect_after_activation(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( get_transient( 'mr_ml_do_activation_redirect' ) ) {
		delete_transient( 'mr_ml_do_activation_redirect' );
		if ( ! isset( $_GET['activate-multi'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-multilocation' ) );
			exit;
		}
	}
}
add_action( 'admin_init', 'mr_ml_maybe_redirect_after_activation' );

/**
 * Register admin page.
 */
function mr_ml_register_admin_page(): void {
	add_menu_page(
		'MR Multilocation',
		'MR Multilocation',
		'manage_options',
		'mr-multilocation',
		'mr_ml_render_admin_page',
		'dashicons-location-alt',
		60
	);
}
add_action( 'admin_menu', 'mr_ml_register_admin_page' );

/**
 * Enqueue SPA assets if built, pass WP data.
 */
function mr_ml_enqueue_admin_assets( string $hook_suffix ): void {
    // Подключаем ассеты только на странице плагина
    $page = isset( $_GET['page'] ) ? sanitize_text_field( (string) $_GET['page'] ) : '';
    if ( $page !== 'mr-multilocation' ) {
        return;
    }

	$dist_dir = MR_ML_PLUGIN_DIR . 'admin/dist/';
	$assets_manifest = $dist_dir . 'manifest.json';

	// Basic fallback if not built yet.
	$built = file_exists( $assets_manifest );

	if ( $built ) {
		$manifest = json_decode( file_get_contents( $assets_manifest ), true );
		$entry = null;
		foreach ( $manifest as $key => $value ) {
			if ( isset( $value['isEntry'] ) && $value['isEntry'] ) {
				$entry = $value;
				break;
			}
		}
		$entry_js = $entry && isset( $entry['file'] ) ? $entry['file'] : 'assets/index.js';
		$entry_css_list = $entry && isset( $entry['css'] ) ? (array) $entry['css'] : array();

		foreach ( $entry_css_list as $css_file ) {
			wp_enqueue_style( 'mr-ml-admin-style', MR_ML_PLUGIN_URL . 'admin/dist/' . ltrim( $css_file, '/' ), array(), null );
		}
		wp_enqueue_script( 'mr-ml-admin', MR_ML_PLUGIN_URL . 'admin/dist/' . ltrim( $entry_js, '/' ), array(), null, true );
		// Vite build генерирует ESM, укажем type="module"
		if ( function_exists( 'wp_script_add_data' ) ) {
			wp_script_add_data( 'mr-ml-admin', 'type', 'module' );
		}
    } else {
        // Фоллбек без manifest: ищем entry index-*.js и все css
        $assets_dir = $dist_dir . 'assets/';
        $entry_candidates = glob( $assets_dir . 'index-*.js' );
        $js_files = is_array( $entry_candidates ) ? $entry_candidates : array();
        $css_files = glob( $assets_dir . '*.css' );

        if ( ! empty( $css_files ) ) {
            foreach ( $css_files as $i => $css_path ) {
                $css_url = str_replace( MR_ML_PLUGIN_DIR, MR_ML_PLUGIN_URL, $css_path );
                wp_enqueue_style( 'mr-ml-admin-style' . ( $i ? ( '-' . $i ) : '' ), $css_url, array(), null );
            }
        }

        if ( ! empty( $js_files ) ) {
            $first_js = $js_files[0]; // index-*.js
            $js_url = str_replace( MR_ML_PLUGIN_DIR, MR_ML_PLUGIN_URL, $first_js );
            wp_enqueue_script( 'mr-ml-admin', $js_url, array(), null, true );
            if ( function_exists( 'wp_script_add_data' ) ) {
                wp_script_add_data( 'mr-ml-admin', 'type', 'module' );
            }
        } else {
            // Minimal styles so content looks okay pre-build.
            wp_add_inline_style( 'wp-admin', '.mr-ml-alert{background:#fff3cd;border:1px solid #ffe69c;padding:12px;margin-top:12px;border-radius:4px}' );
        }
	}

	// Local data for SPA
	$local = array(
		'rootUrl'        => esc_url_raw( get_site_url() ),
		'adminUrl'       => esc_url_raw( admin_url() ),
		'restUrl'        => esc_url_raw( get_rest_url() ),
		'nonce'          => wp_create_nonce( 'wp_rest' ),
		'userCanManage'  => current_user_can( 'manage_options' ),
		'pluginVersion'  => '0.1.0',
	);

	if ( wp_script_is( 'mr-ml-admin', 'enqueued' ) ) {
		wp_localize_script( 'mr-ml-admin', 'MR_ML_BOOT', $local );
		// Лог оставим, визуальный маркер уберём, чтобы не затирать разметку Vue
		wp_add_inline_script( 'mr-ml-admin', 'console.info("MR_ML: admin bundle loaded", window.MR_ML_BOOT);', 'after' );
	} else {
		// If not built, localize to a temp handle and render inline boot script in page.
		wp_register_script( 'mr-ml-admin-inline', '' );
		wp_localize_script( 'mr-ml-admin-inline', 'MR_ML_BOOT', $local );
		wp_enqueue_script( 'mr-ml-admin-inline' );
	}
}
add_action( 'admin_enqueue_scripts', 'mr_ml_enqueue_admin_assets' );

/**
 * Register CPT: multiregional_page
 */
function mr_ml_register_cpt(): void {
    $labels = array(
        'name' => 'MR Страницы',
        'singular_name' => 'MR Страница',
        'add_new' => 'Добавить',
        'add_new_item' => 'Добавить MR Страницу',
        'edit_item' => 'Редактировать MR Страницу',
        'new_item' => 'Новая MR Страница',
        'view_item' => 'Просмотр MR Страницы',
        'search_items' => 'Искать MR Страницы',
        'not_found' => 'Не найдено',
        'not_found_in_trash' => 'Не найдено в корзине',
        'all_items' => 'Все MR Страницы',
    );

    register_post_type( 'multiregional_page', array(
        'labels' => $labels,
        'public' => true,
        'show_in_rest' => true,
        'supports' => array( 'title', 'editor', 'revisions' ),
        'menu_icon' => 'dashicons-location-alt',
        'has_archive' => false,
        // Избежим конфликтов на фронте: отключим rewrite (без красивых URL для CPT)
        'rewrite' => false,
        // Разрешим прямой запрос по post_type+name, резолв делаем в фильтре request
        'publicly_queryable' => true,
        // Отключим query var, чтобы не было ?multiregional_page=slug
        'query_var' => false,
    ) );
}
add_action( 'init', 'mr_ml_register_cpt' );

/**
 * Render admin page wrapper; SPA mounts into #mr-ml-app.
 */
function mr_ml_render_admin_page(): void {
	$manifest_path = MR_ML_PLUGIN_DIR . 'admin/dist/manifest.json';
	$assets_dir = MR_ML_PLUGIN_DIR . 'admin/dist/assets/';
	$has_build = file_exists( $manifest_path ) || ( is_dir( $assets_dir ) && ( glob( $assets_dir . '*.js' ) || glob( $assets_dir . '*.css' ) ) );
	?>
	<div class="wrap">
		<h1>MR Multilocation</h1>
		<div id="mr-ml-app"></div>
		<?php if ( ! $has_build ) : ?>
			<div class="mr-ml-alert">
				<p><strong>Сборка SPA не найдена.</strong> Выполните сборку:</p>
				<pre>cd wp-content/plugins/mr-multilocation/admin
npm install
npm run build</pre>
				<p class="description">Ожидаемый файл: admin/dist/manifest.json</p>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Minimal REST: subdomains & folders CRUD backed by options for now.
 */
function mr_ml_register_rest_routes(): void {
	register_rest_route( 'mr-ml/v1', '/subdomains', array(
		array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => 'mr_ml_rest_get_subdomains',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
		array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => 'mr_ml_rest_create_subdomain',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );

	register_rest_route( 'mr-ml/v1', '/subdomains/(?P<id>\d+)', array(
		array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'mr_ml_rest_update_subdomain',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
		array(
			'methods'  => WP_REST_Server::DELETABLE,
			'callback' => 'mr_ml_rest_delete_subdomain',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );

	register_rest_route( 'mr-ml/v1', '/folders', array(
		array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => 'mr_ml_rest_get_folders',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
		array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => 'mr_ml_rest_create_folder',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );

	register_rest_route( 'mr-ml/v1', '/folders/(?P<id>\d+)', array(
		array(
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'mr_ml_rest_update_folder',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
		array(
			'methods'  => WP_REST_Server::DELETABLE,
			'callback' => 'mr_ml_rest_delete_folder',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );

	// Mode option
    register_rest_route( 'mr-ml/v1', '/mode', array(
		array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => function() {
                $mode = get_option( 'mr_ml_mode', 'hybrid' );
                $mainNoPrefix = (bool) get_option( 'mr_ml_main_no_prefix', false );
                return rest_ensure_response( array( 'mode' => $mode, 'mainNoPrefix' => $mainNoPrefix ) );
			},
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
		array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => function( WP_REST_Request $req ) {
				$params = $req->get_json_params();
				$mode = isset( $params['mode'] ) ? sanitize_text_field( (string) $params['mode'] ) : '';
				if ( ! in_array( $mode, array( 'subdomain', 'folder', 'hybrid' ), true ) ) {
					return new WP_Error( 'mr_ml_bad_request', 'invalid mode', array( 'status' => 400 ) );
				}
				update_option( 'mr_ml_mode', $mode, false );
                $mainNoPrefix = ! empty( $params['mainNoPrefix'] );
                update_option( 'mr_ml_main_no_prefix', $mainNoPrefix, false );
				return rest_ensure_response( array( 'success' => true ) );
			},
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );

	// SEO options
	register_rest_route( 'mr-ml/v1', '/seo', array(
		array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => function() {
				return rest_ensure_response( array(
					'enableCanonical' => (bool) get_option( 'mr_ml_enable_canonical', true ),
					'enableHreflang' => (bool) get_option( 'mr_ml_enable_hreflang', true ),
					'robotsGoogleDisallowFolders' => (bool) get_option( 'mr_ml_robots_google_disallow_folders', false ),
					'robotsYandexDisallowSubdomains' => (bool) get_option( 'mr_ml_robots_yandex_disallow_subdomains', false ),
					'robotsTplGoogle' => (string) get_option( 'mr_ml_robots_tpl_google', '' ),
					'robotsTplYandex' => (string) get_option( 'mr_ml_robots_tpl_yanex', '' ),
				) );
			},
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
		array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => function( WP_REST_Request $req ) {
				$p = $req->get_json_params();
				update_option( 'mr_ml_enable_canonical', ! empty( $p['enableCanonical'] ), false );
				update_option( 'mr_ml_enable_hreflang', ! empty( $p['enableHreflang'] ), false );
				update_option( 'mr_ml_robots_google_disallow_folders', ! empty( $p['robotsGoogleDisallowFolders'] ), false );
				update_option( 'mr_ml_robots_yandex_disallow_subdomains', ! empty( $p['robotsYandexDisallowSubdomains'] ), false );
				update_option( 'mr_ml_robots_tpl_google', isset( $p['robotsTplGoogle'] ) ? wp_kses_post( (string) $p['robotsTplGoogle'] ) : '', false );
				update_option( 'mr_ml_robots_tpl_yanex', isset( $p['robotsTplYandex'] ) ? wp_kses_post( (string) $p['robotsTplYandex'] ) : '', false );
				return rest_ensure_response( array( 'success' => true ) );
			},
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );

	// Audit endpoints (stub)
	register_rest_route( 'mr-ml/v1', '/audit/shortcodes', array(
		array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => function() {
				$cached = get_transient( 'mr_ml_audit_cache' );
				if ( $cached && is_array( $cached ) ) {
					return rest_ensure_response( $cached );
				}
				return rest_ensure_response( array() );
			},
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );

	register_rest_route( 'mr-ml/v1', '/audit/reindex', array(
		array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => function() {
				// Простая заглушка: пройдёмся по последним 20 постам и найдём упоминания шорткодов
				$q = new WP_Query( array( 'posts_per_page' => 20, 'post_type' => 'any' ) );
				$rows = array();
				if ( $q->have_posts() ) {
					foreach ( $q->posts as $p ) {
						$sc = array();
						if ( preg_match_all( '/\[(mr_name|mr_meta|show_if)([^\]]*)\]/i', $p->post_content, $m ) ) {
							$sc = array_values( array_unique( $m[1] ) );
						}
						if ( ! empty( $sc ) ) {
							$rows[] = array(
								'id' => (int) $p->ID,
								'postTitle' => get_the_title( $p ),
								'shortcodes' => $sc,
							);
						}
					}
				}
				set_transient( 'mr_ml_audit_cache', $rows, 5 * MINUTE_IN_SECONDS );
				return rest_ensure_response( array( 'indexed' => count( $rows ) ) );
			},
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );

	// Pages list with visibility and URLs
	register_rest_route( 'mr-ml/v1', '/pages', array(
		array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => function( WP_REST_Request $req ) {
				$q = new WP_Query( array(
					'post_type' => 'multiregional_page',
					'posts_per_page' => 100,
					'post_status' => array('publish','draft','pending','future'),
				) );
				$items = array();
				$subs = mr_ml_opt_get('mr_ml_subdomains');
				$fols = mr_ml_opt_get('mr_ml_folders');
				foreach ( $q->posts as $p ) {
					$vis = get_post_meta( $p->ID, '_mr_ml_visibility', true );
					$urls = mr_ml_generate_urls_for_post( $p );
					$items[] = array(
						'id' => (int)$p->ID,
						'title' => get_the_title($p),
						'slug' => $p->post_name,
						'visibility' => is_array($vis) ? $vis : array(),
						'urls' => $urls,
						'folders' => $fols,
						'subdomains' => $subs,
					);
				}
				return rest_ensure_response( $items );
			},
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );

	// Visibility per post
	register_rest_route( 'mr-ml/v1', '/visibility/(?P<post_id>\d+)', array(
		array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => function( WP_REST_Request $req ) {
				$post_id = (int) $req['post_id'];
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return new WP_Error( 'forbidden', 'Not allowed', array( 'status' => 403 ) );
				}
				$vis = get_post_meta( $post_id, '_mr_ml_visibility', true );
				return rest_ensure_response( is_array($vis) ? $vis : array( 'rule' => 'allow', 'subdomains' => array(), 'folders' => array() ) );
			},
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
		array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => function( WP_REST_Request $req ) {
				$post_id = (int) $req['post_id'];
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return new WP_Error( 'forbidden', 'Not allowed', array( 'status' => 403 ) );
				}
				$p = (array) $req->get_json_params();
				$rule = isset($p['rule']) && in_array($p['rule'], array('all','allow','deny'), true) ? $p['rule'] : 'allow';
				$subs = isset($p['subdomains']) && is_array($p['subdomains']) ? array_map('intval',$p['subdomains']) : array();
				$fols = isset($p['folders']) && is_array($p['folders']) ? array_map('intval',$p['folders']) : array();
				update_post_meta( $post_id, '_mr_ml_visibility', array( 'rule' => $rule, 'subdomains' => $subs, 'folders' => $fols ) );
				return rest_ensure_response( array( 'success' => true ) );
			},
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );
}
add_action( 'rest_api_init', 'mr_ml_register_rest_routes' );

/**
 * Resolver: определить текущую цель и виртуальный роутинг папок
 */
function mr_ml_is_rest_request(): bool {
    return ( defined('REST_REQUEST') && REST_REQUEST ) || ( isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false );
}
function mr_ml_get_current_target(): array {
    $mode = get_option( 'mr_ml_mode', 'hybrid' );
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( $_SERVER['HTTP_HOST'] ) : '';
    $site_host = parse_url( home_url(), PHP_URL_HOST );
    $path = trim( isset( $_SERVER['REQUEST_URI'] ) ? explode('?', $_SERVER['REQUEST_URI'], 2)[0] : '', '/' );

    $subdomains = mr_ml_opt_get( 'mr_ml_subdomains' );
    $folders = mr_ml_opt_get( 'mr_ml_folders' );
    $sub_slugs = array_map( function($r){ return $r['slug']; }, $subdomains );
    $folder_slugs = array_map( function($r){ return $r['slug']; }, $folders );

    // try subdomain
    if ( in_array( $mode, array('subdomain','hybrid'), true ) && $host && $site_host ) {
        if ( $host !== $site_host && str_ends_with( $host, '.' . $site_host ) ) {
            $sub = substr( $host, 0, - ( strlen($site_host) + 1 ) );
            if ( $sub !== '' && in_array( $sub, $sub_slugs, true ) ) {
                return array( 'type' => 'subdomain', 'slug' => $sub );
            }
        }
    }

    // try folder
    if ( in_array( $mode, array('folder','hybrid'), true ) && $path !== '' ) {
        $first = explode('/', $path, 2)[0];
        if ( in_array( $first, $folder_slugs, true ) ) {
            return array( 'type' => 'folder', 'slug' => $first );
        }
    }

    return array( 'type' => null, 'slug' => null );
}

function mr_ml_apply_virtual_folder_routing( $wp ) {
    // Перенесли всю логику в фильтр 'request' ниже, чтобы не ломать $wp->request
    return;
}
add_action( 'parse_request', 'mr_ml_apply_virtual_folder_routing', 1 );

// Дополнительно поправим query_vars на ранней стадии для страниц
function mr_ml_request_vars_filter( array $qv ) : array {
    if ( is_admin() || mr_ml_is_rest_request() ) { return $qv; }
    $target = mr_ml_get_current_target();
    $main_no_prefix = (bool) get_option( 'mr_ml_main_no_prefix', false );

    // Разберём исходный путь запроса
    $uri = isset($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI'], 2)[0] : '';
    $path = trim($uri, '/');
    if ($path === '') { return $qv; }

    $rest = '';
    if ( $target['type'] === 'folder' ) {
        $slug = $target['slug'];
        $parts = explode('/', $path, 2);
        if ($parts[0] !== $slug) { return $qv; }
        $rest = isset($parts[1]) ? $parts[1] : '';
    } elseif ( $target['type'] === 'subdomain' ) {
        // Для поддомена используем путь как есть
        $rest = $path;
    } else {
        // Без цели: если включён mainNoPrefix — используем путь как есть, иначе выходим
        if ( ! $main_no_prefix ) { return $qv; }
        $rest = $path;
    }

    if ($rest === '') {
        // Корень папки — оставляем как есть (можно привязать к главной/архиву)
        return $qv;
    }

    // Ищем точное совпадение среди поддерживаемых типов
    $post = get_page_by_path( $rest, OBJECT, array( 'multiregional_page', 'page', 'post' ) );
    if ( $post instanceof WP_Post ) {
        if ( $post->post_type === 'page' ) {
            $qv['pagename'] = $rest;
            unset( $qv['name'], $qv['post_type'] );
        } else {
            $qv['post_type'] = $post->post_type;
            $qv['name'] = $post->post_name;
            unset( $qv['pagename'] );
        }
        return $qv;
    }

    // Fallback: трактуем как запись multiregional_page (и post) по basename
    $qv['post_type'] = array( 'multiregional_page', 'post' );
    $qv['name'] = basename( $rest );
    unset( $qv['pagename'] );
    return $qv;
}
add_filter( 'request', 'mr_ml_request_vars_filter', 1 );

// REST: current-target
add_action( 'rest_api_init', function(){
    register_rest_route( 'mr-ml/v1', '/current-target', array(
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => function(){ return rest_ensure_response( mr_ml_get_current_target() ); },
            'permission_callback' => '__return_true',
        )
    ) );
});

/**
 * URL Guard: зарезервированные/конфликтные slug
 */
function mr_ml_is_reserved_slug( string $slug ): bool {
    $reserved = array( 'www','sitemap','robots','wp','wp-admin','admin','xmlrpc','api','feed','static' );
    return in_array( $slug, $reserved, true );
}

function mr_ml_opt_get( string $key ): array {
	$val = get_option( $key );
	return is_array( $val ) ? $val : array();
}

function mr_ml_opt_set( string $key, array $value ): void {
	update_option( $key, $value, false );
}

function mr_ml_sanitize_slug( string $slug ): string {
	$slug = sanitize_title( $slug );
	return $slug;
}
/**
 * Переписывание ссылок для текущей цели
 */
function mr_ml_filter_permalink( string $permalink, $post ): string {
    if ( is_admin() || mr_ml_is_rest_request() ) { return $permalink; }
    static $in_progress = false;
    if ( $in_progress ) { return $permalink; }
    $in_progress = true;

    $target = mr_ml_get_current_target();
    $mode = get_option( 'mr_ml_mode', 'hybrid' );
    if ( $target['type'] === null ) { $in_progress = false; return $permalink; }

    if ( $target['type'] === 'folder' && in_array( $mode, array('folder','hybrid'), true ) ) {
        $slug = $target['slug'];
        $base = get_option( 'home' );
        // не дублировать префикс, если уже есть
        $rel = '/' . ltrim( wp_make_link_relative( $permalink ), '/' );
        if ( strpos( $rel, '/' . $slug . '/' ) === 0 ) { $in_progress = false; return $permalink; }
        $base = trailingslashit( rtrim( $base, '/' ) . '/' . $slug . '/' );
        $path = wp_make_link_relative( $permalink );
        $in_progress = false;
        return trailingslashit( $base . ltrim( $path, '/' ) );
    }

    if ( $target['type'] === 'subdomain' && in_array( $mode, array('subdomain','hybrid'), true ) ) {
        $slug = $target['slug'];
        $parts = wp_parse_url( $permalink );
        $host = parse_url( get_option('home'), PHP_URL_HOST );
        if ( $host && isset( $parts['host'] ) ) {
            $parts['host'] = $slug . '.' . $host;
            $scheme = is_ssl() ? 'https' : ( $parts['scheme'] ?? 'http' );
            $rebuilt = $scheme . '://' . $parts['host'] . ( $parts['path'] ?? '/' );
            if ( ! empty( $parts['query'] ) ) { $rebuilt .= '?' . $parts['query']; }
            $in_progress = false;
            return $rebuilt;
        }
    }
    $in_progress = false;
    return $permalink;
}
// add_filter( 'post_link', 'mr_ml_filter_permalink', 10, 2 );
// add_filter( 'page_link', 'mr_ml_filter_permalink', 10, 2 );

function mr_ml_filter_home_url( string $url, string $path, $orig_scheme, $blog_id ) : string {
    if ( is_admin() || mr_ml_is_rest_request() ) { return $url; }
    $target = mr_ml_get_current_target();
    $mode = get_option( 'mr_ml_mode', 'hybrid' );

    // Базовый URL без вызова home_url(), чтобы избежать рекурсии
    $base = get_option( 'home' );
    if ( $orig_scheme ) {
        $base = set_url_scheme( $base, $orig_scheme );
    }

    if ( $target['type'] === 'folder' && in_array( $mode, array('folder','hybrid'), true ) ) {
        $url = trailingslashit( rtrim( $base, '/' ) . '/' . $target['slug'] . '/' );
        if ( $path ) { $url .= ltrim( $path, '/' ); }
    } elseif ( $target['type'] === 'subdomain' && in_array( $mode, array('subdomain','hybrid'), true ) ) {
        $host = parse_url( $base, PHP_URL_HOST );
        if ( $host ) {
            $scheme = is_ssl() ? 'https' : ( $orig_scheme ?: parse_url( $base, PHP_URL_SCHEME ) ?: 'http' );
            $url = $scheme . '://' . $target['slug'] . '.' . $host . '/';
            if ( $path ) { $url .= ltrim( $path, '/' ); }
        }
    }
    return $url;
}
// add_filter( 'home_url', 'mr_ml_filter_home_url', 10, 4 );

/**
 * Переписывание href в меню и в контенте под текущую цель
 */
function mr_ml_rewrite_url_for_target( string $url ): string {
    if ( is_admin() || mr_ml_is_rest_request() ) { return $url; }
    $target = mr_ml_get_current_target();
    $mode = get_option( 'mr_ml_mode', 'hybrid' );
    $main_no_prefix = (bool) get_option( 'mr_ml_main_no_prefix', false );
    if ( ! $target['type'] ) { return $url; }

    $home = rtrim( get_option( 'home' ), '/' );
    $is_internal = false;
    if ( strpos( $url, '/' ) === 0 ) { $is_internal = true; }
    if ( strpos( $url, $home ) === 0 ) { $is_internal = true; }
    if ( ! $is_internal ) { return $url; }

    // Нормализуем к абсолютному виду для простоты
    if ( strpos( $url, '/' ) === 0 ) { $url = $home . $url; }

    if ( $target['type'] === 'folder' && in_array( $mode, array('folder','hybrid'), true ) ) {
        if ( $main_no_prefix ) { return $url; }
        $prefix = '/' . $target['slug'] . '/';
        $after_home = substr( $url, strlen( $home ) );
        if ( strpos( $after_home, $prefix ) === 0 ) { return $url; }
        return $home . $prefix . ltrim( $after_home, '/' );
    }

    if ( $target['type'] === 'subdomain' && in_array( $mode, array('subdomain','hybrid'), true ) ) {
        $host = parse_url( $home, PHP_URL_HOST );
        if ( $host ) {
            $scheme = is_ssl() ? 'https' : parse_url( $home, PHP_URL_SCHEME );
            $path = substr( $url, strlen( $home ) );
            return $scheme . '://' . $target['slug'] . '.' . $host . $path;
        }
    }
    return $url;
}

function mr_ml_filter_nav_menu_link_atts( array $atts, $item, $args, $depth ) : array {
    if ( ! empty( $atts['href'] ) ) {
        $atts['href'] = mr_ml_rewrite_url_for_target( $atts['href'] );
    }
    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'mr_ml_filter_nav_menu_link_atts', 10, 4 );

function mr_ml_filter_the_content( string $content ) : string {
    if ( is_admin() || mr_ml_is_rest_request() ) { return $content; }
    if ( false === stripos( $content, '<a ' ) ) { return $content; }
    $content = preg_replace_callback( '~<a\s+[^>]*href=(["\'])([^"\']+)\1~i', function( $m ){
        $orig = $m[0];
        $q = $m[1];
        $href = $m[2];
        $new = mr_ml_rewrite_url_for_target( $href );
        if ( $new === $href ) { return $orig; }
        return str_replace( $q.$href.$q, $q.$new.$q, $orig );
    }, $content );
    return $content;
}
add_filter( 'the_content', 'mr_ml_filter_the_content', 20 );

/**
 * Метабокс "MR Multilocation: Видимость" для всех public post types
 */
function mr_ml_visibility_meta_box_add() : void {
    $types = get_post_types( array( 'public' => true ), 'names' );
    foreach ( $types as $pt ) {
        add_meta_box(
            'mr_ml_visibility',
            'MR Multilocation: Видимость',
            'mr_ml_visibility_meta_box_render',
            $pt,
            'side',
            'default'
        );
    }
}
add_action( 'add_meta_boxes', 'mr_ml_visibility_meta_box_add' );

function mr_ml_visibility_meta_box_render( WP_Post $post ) : void {
    wp_nonce_field( 'mr_ml_visibility_save', 'mr_ml_visibility_nonce' );
    $val = get_post_meta( $post->ID, '_mr_ml_visibility', true );
    $rule = is_array($val) && isset($val['rule']) ? $val['rule'] : 'allow';
    $sub_selected = is_array($val) && !empty($val['subdomains']) ? (array)$val['subdomains'] : array();
    $fol_selected = is_array($val) && !empty($val['folders']) ? (array)$val['folders'] : array();

    $subs = mr_ml_opt_get('mr_ml_subdomains');
    $fols = mr_ml_opt_get('mr_ml_folders');
    ?>
    <p>
        <label><input type="radio" name="mr_ml_vis[rule]" value="all" <?php checked($rule,'all');?>> Показывать везде (игнорировать списки ниже)</label><br>
        <label><input type="radio" name="mr_ml_vis[rule]" value="allow" <?php checked($rule,'allow');?>> Показывать только для выбранных</label><br>
        <label><input type="radio" name="mr_ml_vis[rule]" value="deny" <?php checked($rule,'deny');?>> Скрывать для выбранных</label>
    </p>
    <p><strong>Поддомены</strong></p>
    <div class="mr-ml-list-subdomains" style="max-height:140px; overflow:auto; border:1px solid #ddd; padding:6px;">
        <?php foreach ($subs as $s): $sid=(int)$s['id']; ?>
            <label style="display:block;margin-bottom:4px;">
                <input type="checkbox" name="mr_ml_vis[subdomains][]" value="<?php echo esc_attr($sid); ?>" <?php checked(in_array($sid,$sub_selected,true));?>>
                <?php echo esc_html($s['slug'].' — '.$s['name']); ?>
            </label>
        <?php endforeach; ?>
    </div>
    <p><strong>Папки</strong></p>
    <div class="mr-ml-list-folders" style="max-height:140px; overflow:auto; border:1px solid #ddd; padding:6px;">
        <?php foreach ($fols as $f): $fid=(int)$f['id']; ?>
            <label style="display:block;margin-bottom:4px;">
                <input type="checkbox" name="mr_ml_vis[folders][]" value="<?php echo esc_attr($fid); ?>" <?php checked(in_array($fid,$fol_selected,true));?>>
                <?php echo esc_html('/'.$f['slug'].' — '.$f['name']); ?>
            </label>
        <?php endforeach; ?>
    </div>
    <script>
    (function(){
      function toggleLists(){
        var rule = document.querySelector('input[name="mr_ml_vis[rule]"]:checked');
        var hide = rule && rule.value === 'all';
        var subs = document.querySelector('.mr-ml-list-subdomains');
        var fols = document.querySelector('.mr-ml-list-folders');
        if (subs) subs.style.display = hide ? 'none' : '';
        if (fols) fols.style.display = hide ? 'none' : '';
      }
      document.addEventListener('change', function(e){ if (e.target && e.target.name === 'mr_ml_vis[rule]') toggleLists(); });
      document.addEventListener('DOMContentLoaded', toggleLists);
      toggleLists();
    })();
    </script>
    <?php
}

function mr_ml_visibility_meta_box_save( int $post_id ) : void {
    if ( ! isset($_POST['mr_ml_visibility_nonce']) || ! wp_verify_nonce( $_POST['mr_ml_visibility_nonce'], 'mr_ml_visibility_save' ) ) { return; }
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }
    if ( wp_is_post_revision( $post_id ) ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

    $val = isset($_POST['mr_ml_vis']) && is_array($_POST['mr_ml_vis']) ? $_POST['mr_ml_vis'] : array();
    $rule = isset($val['rule']) && in_array($val['rule'], array('all','allow','deny'), true) ? $val['rule'] : 'allow';
    $sub = isset($val['subdomains']) && is_array($val['subdomains']) ? array_map('intval', $val['subdomains']) : array();
    $fol = isset($val['folders']) && is_array($val['folders']) ? array_map('intval', $val['folders']) : array();
    update_post_meta( $post_id, '_mr_ml_visibility', array( 'rule' => $rule, 'subdomains' => $sub, 'folders' => $fol ) );
}
add_action( 'save_post', 'mr_ml_visibility_meta_box_save' );

// Метабокс со списком URL (внизу, normal/low)
function mr_ml_urls_meta_box_add() : void {
    add_meta_box(
        'mr_ml_urls_list',
        'MR Multilocation: Доступные URL',
        'mr_ml_urls_meta_box_render',
        'multiregional_page',
        'normal',
        'low'
    );
}
add_action( 'add_meta_boxes', 'mr_ml_urls_meta_box_add' );

function mr_ml_urls_meta_box_render( WP_Post $post ) : void {
    $urls = mr_ml_generate_urls_for_post( $post );
    $subs = mr_ml_opt_get('mr_ml_subdomains');
    $fols = mr_ml_opt_get('mr_ml_folders');
    $home = rtrim( get_option('home'), '/' );
    $host = parse_url( $home, PHP_URL_HOST );

    echo '<table class="widefat fixed striped"><thead><tr><th>URL</th><th style="width:180px">Цель</th></tr></thead><tbody>';
    if ( empty($urls) ) {
        echo '<tr><td colspan="2"><em>URL не сгенерированы</em></td></tr>';
    } else {
        foreach ( $urls as $u ) {
            // Определим метку цели
            $label = '—';
            $pu = wp_parse_url( $u );
            if ( isset($pu['host']) && $host && $pu['host'] !== $host ) {
                // subdomain
                $prefix = rtrim( str_replace('.'.$host, '', $pu['host'] ), '.' );
                foreach ($subs as $s) { if ($s['slug'] === $prefix) { $label = 'Поддомен: ' . esc_html($s['slug']); break; } }
            } else {
                // folder or root
                $path = isset($pu['path']) ? trim($pu['path'], '/') : '';
                foreach ($fols as $f) {
                    if ( strpos($path, $f['slug'].'/') === 0 ) { $label = 'Папка: ' . esc_html($f['slug']); break; }
                }
                if ( $label === '—' ) { $label = 'Основной сайт'; }
            }
            echo '<tr><td><a href="' . esc_url($u) . '" target="_blank" rel="noopener">' . esc_html($u) . '</a></td><td>' . $label . '</td></tr>';
        }
    }
    echo '</tbody></table>';
}

/**
 * Применение правил видимости на фронте
 */
function mr_ml_is_post_visible_for_target( WP_Post $post ) : bool {
    $val = get_post_meta( $post->ID, '_mr_ml_visibility', true );
    if ( ! is_array( $val ) ) { return true; }
    $rule = isset($val['rule']) ? $val['rule'] : 'allow';
    $subs = isset($val['subdomains']) ? (array)$val['subdomains'] : array();
    $fols = isset($val['folders']) ? (array)$val['folders'] : array();

    $target = mr_ml_get_current_target();
    $main_no_prefix = (bool) get_option( 'mr_ml_main_no_prefix', false );
    if ( ! $target['type'] ) {
        if ( $rule === 'all' ) { return true; }
        if ( $main_no_prefix ) { return true; }
        if ( $rule === 'allow' && ( !empty($subs) || !empty($fols) ) ) { return false; }
        return true;
    }

    $match = false;
    if ( $target['type'] === 'subdomain' && ! empty($subs) ) {
        // сопоставим по id: ищем id записи с таким slug
        $all = mr_ml_opt_get('mr_ml_subdomains');
        foreach ($all as $r) { if ( (int)$r['id'] && $r['slug'] === $target['slug'] && in_array( (int)$r['id'], array_map('intval',$subs), true ) ) { $match = true; break; } }
    } elseif ( $target['type'] === 'folder' && ! empty($fols) ) {
        $all = mr_ml_opt_get('mr_ml_folders');
        foreach ($all as $r) { if ( (int)$r['id'] && $r['slug'] === $target['slug'] && in_array( (int)$r['id'], array_map('intval',$fols), true ) ) { $match = true; break; } }
    }

    if ( $rule === 'all' ) { return true; }
    if ( $rule === 'allow' ) { return $match || (empty($subs) && empty($fols)); }
    if ( $rule === 'deny' ) { return ! $match; }
    return true;
}

function mr_ml_filter_the_posts( array $posts, WP_Query $q ) : array {
    if ( is_admin() || mr_ml_is_rest_request() ) { return $posts; }
    if ( empty($posts) ) { return $posts; }
    if ( $q->is_singular() ) { return $posts; }
    $out = array();
    foreach ( $posts as $p ) { if ( mr_ml_is_post_visible_for_target( $p ) ) { $out[] = $p; } }
    return $out;
}
add_filter( 'the_posts', 'mr_ml_filter_the_posts', 10, 2 );

function mr_ml_template_redirect_visibility() : void {
    if ( is_admin() || mr_ml_is_rest_request() ) { return; }
    // Блок доступа к служебному /mr/*
    $req_uri = isset($_SERVER['REQUEST_URI']) ? trim(explode('?', $_SERVER['REQUEST_URI'], 2)[0], '/') : '';
    if ( $req_uri === 'mr' || strpos($req_uri, 'mr/') === 0 ) {
        status_header(404);
        nocache_headers();
        include get_404_template();
        exit;
    }
    if ( is_singular() ) {
        $p = get_queried_object();
        if ( $p instanceof WP_Post ) {
            if ( ! mr_ml_is_post_visible_for_target( $p ) ) {
                status_header(404);
                nocache_headers();
                include get_404_template();
                exit;
            }
        }
    }
}
add_action( 'template_redirect', 'mr_ml_template_redirect_visibility', 1 );

/**
 * Генерация списка доступных URL для поста с учётом режима и видимости
 */
function mr_ml_generate_urls_for_post( WP_Post $post ) : array {
    $urls = array();
    $home = rtrim( get_option( 'home' ), '/' );
    $mode = get_option( 'mr_ml_mode', 'hybrid' );
    $main_no_prefix = (bool) get_option( 'mr_ml_main_no_prefix', false );
    $slug = $post->post_name;

    // Определим разрешённые цели по мета
    $meta = get_post_meta( $post->ID, '_mr_ml_visibility', true );
    $rule = is_array($meta) && isset($meta['rule']) ? $meta['rule'] : 'allow';
    $allowSubs = is_array($meta) && !empty($meta['subdomains']) ? array_map('intval',$meta['subdomains']) : array();
    $allowFols = is_array($meta) && !empty($meta['folders']) ? array_map('intval',$meta['folders']) : array();
    $subs = mr_ml_opt_get('mr_ml_subdomains');
    $fols = mr_ml_opt_get('mr_ml_folders');

    $isAllowed = function(string $type, array $row) use ($rule,$allowSubs,$allowFols) : bool {
        if ($rule === 'all') return true;
        if ($type === 'subdomain') {
            if (empty($allowSubs) && empty($allowFols) && $rule==='allow') return true;
            if ($rule==='allow') return in_array( (int)$row['id'], $allowSubs, true );
            if ($rule==='deny') return !in_array( (int)$row['id'], $allowSubs, true );
        }
        if ($type === 'folder') {
            if (empty($allowSubs) && empty($allowFols) && $rule==='allow') return true;
            if ($rule==='allow') return in_array( (int)$row['id'], $allowFols, true );
            if ($rule==='deny') return !in_array( (int)$row['id'], $allowFols, true );
        }
        return true;
    };

    // Основной домен без префикса (если разрешено)
    if ( $main_no_prefix ) {
        $urls[] = $home . '/' . $slug . '/';
    }

    if ( in_array($mode, array('folder','hybrid'), true) ) {
        foreach ($fols as $f) {
            if ( ! $isAllowed('folder',$f) ) continue;
            $urls[] = $home . '/' . $f['slug'] . '/' . $slug . '/';
        }
    }

    if ( in_array($mode, array('subdomain','hybrid'), true) ) {
        $host = parse_url( $home, PHP_URL_HOST );
        $scheme = is_ssl() ? 'https' : ( parse_url($home, PHP_URL_SCHEME) ?: 'http' );
        foreach ($subs as $s) {
            if ( ! $isAllowed('subdomain',$s) ) continue;
            $urls[] = $scheme . '://' . $s['slug'] . '.' . $host . '/' . $slug . '/';
        }
    }

    return array_values(array_unique($urls));
}

/**
 * Подменяем блок "Постоянная ссылка" на экране редактирования CPT, выводим список доступных URL
 */
function mr_ml_filter_sample_permalink_html( string $html, int $post_id, ?string $new_title, ?string $new_slug, WP_Post $post ) : string {
    if ( $post->post_type !== 'multiregional_page' ) { return $html; }
    $urls = mr_ml_generate_urls_for_post( $post );
    if ( empty($urls) ) { return $html; }
    $out = '<div class="mr-ml-urls"><p><strong>Доступные URL:</strong></p><ul style="margin:6px 0 0 16px">';
    foreach ($urls as $u) {
        $out .= '<li><a href="' . esc_url($u) . '" target="_blank" rel="noopener">' . esc_html($u) . '</a></li>';
    }
    $out .= '</ul><p class="description">Ссылки генерируются плагином MR Multilocation.</p></div>';
    return $out;
}
// Отключим замену permalink-блока; список URL выведем отдельным метабоксом ниже
// add_filter( 'get_sample_permalink_html', 'mr_ml_filter_sample_permalink_html', 10, 5 );

/**
 * Меняем ссылку просмотра для CPT на первую "красивую"
 */
function mr_ml_filter_post_type_link( string $post_link, WP_Post $post ) : string {
    if ( $post->post_type !== 'multiregional_page' ) { return $post_link; }
    $urls = mr_ml_generate_urls_for_post( $post );
    return !empty($urls) ? $urls[0] : $post_link;
}
add_filter( 'post_type_link', 'mr_ml_filter_post_type_link', 10, 2 );

/**
 * Блокируем прямой доступ через ?multiregional_page=slug (и подобные query var)
 */
function mr_ml_block_query_var_access( WP_Query $q ) : void {
    if ( is_admin() || mr_ml_is_rest_request() ) { return; }
    if ( ! $q->is_main_query() ) { return; }
    // Если кто-то явно передал query var CPT — отдаём 404
    if ( isset( $q->query_vars['multiregional_page'] ) ) {
        $q->set_404();
        status_header(404);
        nocache_headers();
    }
}
add_action( 'pre_get_posts', 'mr_ml_block_query_var_access' );

/**
 * Отключаем redirect_canonical, когда активен виртуальный роутинг
 * Иначе WP будет перенаправлять на "канонический" URL (например, с /mr/ или без папки)
 */
function mr_ml_disable_canonical_redirect( $redirect_url, $requested_url ) {
    if ( is_admin() || mr_ml_is_rest_request() ) { return $redirect_url; }
    // Временно полностью отключаем canonical на фронте для стабильности роутинга
    return false;
}
add_filter( 'redirect_canonical', 'mr_ml_disable_canonical_redirect', 10, 2 );


// Subdomains
function mr_ml_rest_get_subdomains( WP_REST_Request $req ) {
	return rest_ensure_response( mr_ml_opt_get( 'mr_ml_subdomains' ) );
}

function mr_ml_rest_create_subdomain( WP_REST_Request $req ) {
	$params = $req->get_json_params();
	$slug = isset( $params['slug'] ) ? mr_ml_sanitize_slug( (string) $params['slug'] ) : '';
	$name = isset( $params['name'] ) ? sanitize_text_field( (string) $params['name'] ) : '';
    $nominative = isset( $params['nominative'] ) ? sanitize_text_field( (string) $params['nominative'] ) : '';
    $dative     = isset( $params['dative'] ) ? sanitize_text_field( (string) $params['dative'] ) : '';
    $genitive   = isset( $params['genitive'] ) ? sanitize_text_field( (string) $params['genitive'] ) : '';
	if ( $slug === '' || $name === '' ) {
		return new WP_Error( 'mr_ml_bad_request', 'slug and name required', array( 'status' => 400 ) );
	}
	if ( mr_ml_is_reserved_slug( $slug ) ) {
		return new WP_Error( 'mr_ml_reserved', 'slug is reserved', array( 'status' => 400 ) );
	}
	$list = mr_ml_opt_get( 'mr_ml_subdomains' );
	foreach ( $list as $r ) { if ( $r['slug'] === $slug ) { return new WP_Error( 'mr_ml_exists', 'slug already exists', array( 'status' => 409 ) ); } }
	$id = time();
	$list[] = array(
		'id' => $id,
		'slug' => $slug,
		'name' => $name,
		'nominative' => $nominative,
		'dative' => $dative,
		'genitive' => $genitive,
	);
	mr_ml_opt_set( 'mr_ml_subdomains', $list );
	return rest_ensure_response( array( 'id' => $id, 'slug' => $slug, 'name' => $name, 'nominative' => $nominative, 'dative' => $dative, 'genitive' => $genitive ) );
}

function mr_ml_rest_update_subdomain( WP_REST_Request $req ) {
	$id = (int) $req['id'];
	$params = $req->get_json_params();
	$slug = isset( $params['slug'] ) ? mr_ml_sanitize_slug( (string) $params['slug'] ) : null;
	$name = isset( $params['name'] ) ? sanitize_text_field( (string) $params['name'] ) : null;
    $nominative = array_key_exists('nominative',$params) ? sanitize_text_field( (string) $params['nominative'] ) : null;
    $dative     = array_key_exists('dative',$params) ? sanitize_text_field( (string) $params['dative'] ) : null;
    $genitive   = array_key_exists('genitive',$params) ? sanitize_text_field( (string) $params['genitive'] ) : null;
	$list = mr_ml_opt_get( 'mr_ml_subdomains' );
	$found = false;
	foreach ( $list as &$row ) {
		if ( (int) $row['id'] === $id ) {
			if ( $slug !== null ) {
				if ( mr_ml_is_reserved_slug( $slug ) ) { return new WP_Error( 'mr_ml_reserved', 'slug is reserved', array( 'status' => 400 ) ); }
				$row['slug'] = $slug;
			}
			if ( $name !== null ) { $row['name'] = $name; }
            if ( $nominative !== null ) { $row['nominative'] = $nominative; }
            if ( $dative !== null ) { $row['dative'] = $dative; }
            if ( $genitive !== null ) { $row['genitive'] = $genitive; }
			$found = true;
			break;
		}
	}
	if ( ! $found ) {
		return new WP_Error( 'mr_ml_not_found', 'Item not found', array( 'status' => 404 ) );
	}
	mr_ml_opt_set( 'mr_ml_subdomains', $list );
	return rest_ensure_response( array( 'success' => true ) );
}

function mr_ml_rest_delete_subdomain( WP_REST_Request $req ) {
	$id = (int) $req['id'];
	$list = mr_ml_opt_get( 'mr_ml_subdomains' );
	$before = count( $list );
	$list = array_values( array_filter( $list, function( $r ) use ( $id ) { return (int) $r['id'] !== $id; } ) );
	if ( count( $list ) === $before ) {
		return new WP_Error( 'mr_ml_not_found', 'Item not found', array( 'status' => 404 ) );
	}
	mr_ml_opt_set( 'mr_ml_subdomains', $list );
	return rest_ensure_response( array( 'success' => true ) );
}

// Folders
function mr_ml_rest_get_folders( WP_REST_Request $req ) {
	return rest_ensure_response( mr_ml_opt_get( 'mr_ml_folders' ) );
}

function mr_ml_rest_create_folder( WP_REST_Request $req ) {
	$params = $req->get_json_params();
	$slug = isset( $params['slug'] ) ? mr_ml_sanitize_slug( (string) $params['slug'] ) : '';
	$name = isset( $params['name'] ) ? sanitize_text_field( (string) $params['name'] ) : '';
    $nominative = isset( $params['nominative'] ) ? sanitize_text_field( (string) $params['nominative'] ) : '';
    $dative     = isset( $params['dative'] ) ? sanitize_text_field( (string) $params['dative'] ) : '';
    $genitive   = isset( $params['genitive'] ) ? sanitize_text_field( (string) $params['genitive'] ) : '';
	if ( $slug === '' || $name === '' ) {
		return new WP_Error( 'mr_ml_bad_request', 'slug and name required', array( 'status' => 400 ) );
	}
	if ( mr_ml_is_reserved_slug( $slug ) ) {
		return new WP_Error( 'mr_ml_reserved', 'slug is reserved', array( 'status' => 400 ) );
	}
	$list = mr_ml_opt_get( 'mr_ml_folders' );
	foreach ( $list as $r ) { if ( $r['slug'] === $slug ) { return new WP_Error( 'mr_ml_exists', 'slug already exists', array( 'status' => 409 ) ); } }
	$id = time();
	$list[] = array(
		'id' => $id,
		'slug' => $slug,
		'name' => $name,
		'nominative' => $nominative,
		'dative' => $dative,
		'genitive' => $genitive,
	);
	mr_ml_opt_set( 'mr_ml_folders', $list );
	return rest_ensure_response( array( 'id' => $id, 'slug' => $slug, 'name' => $name, 'nominative' => $nominative, 'dative' => $dative, 'genitive' => $genitive ) );
}

function mr_ml_rest_update_folder( WP_REST_Request $req ) {
	$id = (int) $req['id'];
	$params = $req->get_json_params();
	$slug = isset( $params['slug'] ) ? mr_ml_sanitize_slug( (string) $params['slug'] ) : null;
	$name = isset( $params['name'] ) ? sanitize_text_field( (string) $params['name'] ) : null;
    $nominative = array_key_exists('nominative',$params) ? sanitize_text_field( (string) $params['nominative'] ) : null;
    $dative     = array_key_exists('dative',$params) ? sanitize_text_field( (string) $params['dative'] ) : null;
    $genitive   = array_key_exists('genitive',$params) ? sanitize_text_field( (string) $params['genitive'] ) : null;
	$list = mr_ml_opt_get( 'mr_ml_folders' );
	$found = false;
	foreach ( $list as &$row ) {
		if ( (int) $row['id'] === $id ) {
			if ( $slug !== null ) {
				if ( mr_ml_is_reserved_slug( $slug ) ) { return new WP_Error( 'mr_ml_reserved', 'slug is reserved', array( 'status' => 400 ) ); }
				$row['slug'] = $slug;
			}
			if ( $name !== null ) { $row['name'] = $name; }
            if ( $nominative !== null ) { $row['nominative'] = $nominative; }
            if ( $dative !== null ) { $row['dative'] = $dative; }
            if ( $genitive !== null ) { $row['genitive'] = $genitive; }
			$found = true;
			break;
		}
	}
	if ( ! $found ) {
		return new WP_Error( 'mr_ml_not_found', 'Item not found', array( 'status' => 404 ) );
	}
	mr_ml_opt_set( 'mr_ml_folders', $list );
	return rest_ensure_response( array( 'success' => true ) );
}

function mr_ml_rest_delete_folder( WP_REST_Request $req ) {
	$id = (int) $req['id'];
	$list = mr_ml_opt_get( 'mr_ml_folders' );
	$before = count( $list );
	$list = array_values( array_filter( $list, function( $r ) use ( $id ) { return (int) $r['id'] !== $id; } ) );
	if ( count( $list ) === $before ) {
		return new WP_Error( 'mr_ml_not_found', 'Item not found', array( 'status' => 404 ) );
	}
	mr_ml_opt_set( 'mr_ml_folders', $list );
	return rest_ensure_response( array( 'success' => true ) );
}


