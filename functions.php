<?php
/**
 * Тема PROMIX: настройки и подключение ассетов.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

define( 'PROMIX_VERSION', '0.1.0' );

/**
 * Версия файла по времени изменения: браузер не отдаёт старый кэш после правок.
 */
function promix_asset_version( string $relative_path ): string {
    $file = get_theme_file_path( $relative_path );

    return file_exists( $file ) ? (string) filemtime( $file ) : PROMIX_VERSION;
}

/**
 * Возможности темы.
 */
function promix_setup(): void {
    load_theme_textdomain( 'promix', get_template_directory() . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'responsive-embeds' );

    register_nav_menus(
        array(
            'primary' => __( 'Основное меню', 'promix' ),
            'footer'  => __( 'Меню в подвале', 'promix' ),
        )
    );
}
add_action( 'after_setup_theme', 'promix_setup' );

// Поля админки на Carbon Fields.
require_once get_theme_file_path( 'inc/fields.php' );

// Заявки с сайта.
require_once get_theme_file_path( 'inc/lead-form.php' );

/**
 * Стили и скрипты фронта.
 */
function promix_assets(): void {
    wp_enqueue_style(
        'promix-fonts',
        get_theme_file_uri( 'assets/css/fonts.css' ),
        array(),
        promix_asset_version( 'assets/css/fonts.css' )
    );

    wp_enqueue_style(
        'promix-variables',
        get_theme_file_uri( 'assets/css/variables.css' ),
        array( 'promix-fonts' ),
        promix_asset_version( 'assets/css/variables.css' )
    );

    wp_enqueue_style(
        'promix-style',
        get_theme_file_uri( 'assets/css/style.css' ),
        array( 'promix-variables' ),
        promix_asset_version( 'assets/css/style.css' )
    );

    // Плавная прокрутка; лежит в теме, чтобы не ходить на сторонний CDN.
    wp_enqueue_script(
        'lenis',
        get_theme_file_uri( 'assets/js/vendor/lenis.min.js' ),
        array(),
        '1.3.26',
        array(
            'strategy'  => 'defer',
            'in_footer' => true,
        )
    );

    $scripts = array(
        'main'    => array( 'lenis' ),
        'lead'    => array( 'promix-main' ),
        'brands'  => array(),
        'reviews' => array(),
    );

    foreach ( $scripts as $handle => $deps ) {
        // Ленты брендов и отзывов есть только на главной.
        if ( ! is_front_page() && in_array( $handle, array( 'brands', 'reviews' ), true ) ) {
            continue;
        }

        wp_enqueue_script(
            'promix-' . $handle,
            get_theme_file_uri( "assets/js/{$handle}.js" ),
            $deps,
            promix_asset_version( "assets/js/{$handle}.js" ),
            array(
                'strategy'  => 'defer',
                'in_footer' => true,
            )
        );
    }

    // Форме нужен адрес обработчика и тексты ответов.
    wp_localize_script(
        'promix-lead',
        'PROMIX_LEAD',
        array(
            'url'     => admin_url( 'admin-ajax.php' ),
            'sending' => __( 'Отправляем…', 'promix' ),
            'done'    => __( 'Спасибо! Перезвоним в рабочее время.', 'promix' ),
            'error'   => __( 'Не получилось отправить. Позвоните нам, пожалуйста.', 'promix' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'promix_assets' );

/**
 * Начертания для основного текста грузятся сразу, а не после разбора CSS:
 * так текст меньше времени показывается запасным шрифтом.
 *
 * crossorigin обязателен даже для своего домена — шрифты браузер всегда
 * запрашивает как чужой ресурс.
 */
function promix_preload_fonts(): void {
    foreach ( array( 'onest-cyrillic', 'onest-latin' ) as $font ) {
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "
",
            esc_url( get_theme_file_uri( "assets/fonts/{$font}.woff2" ) )
        );
    }
}
add_action( 'wp_head', 'promix_preload_fonts', 2 );
