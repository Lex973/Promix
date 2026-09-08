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
        'https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700;800&family=Unbounded:wght@500;600;700&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'promix-variables',
        get_theme_file_uri( 'assets/css/variables.css' ),
        array(),
        promix_asset_version( 'assets/css/variables.css' )
    );

    wp_enqueue_style(
        'promix-style',
        get_theme_file_uri( 'assets/css/style.css' ),
        array( 'promix-variables' ),
        promix_asset_version( 'assets/css/style.css' )
    );

    // Плавная прокрутка; скрипты темы идут после неё.
    wp_enqueue_script(
        'lenis',
        'https://cdn.jsdelivr.net/npm/lenis@1.3.26/dist/lenis.min.js',
        array(),
        '1.3.26',
        true
    );

    foreach ( array( 'main', 'brands', 'reviews', 'lead' ) as $handle ) {
        wp_enqueue_script(
            'promix-' . $handle,
            get_theme_file_uri( "assets/js/{$handle}.js" ),
            array( 'lenis' ),
            promix_asset_version( "assets/js/{$handle}.js" ),
            true
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
 * Ранний коннект к CDN шрифтов — текст не мигает при загрузке.
 */
function promix_resource_hints( array $urls, string $relation ): array {
    if ( 'preconnect' === $relation ) {
        $urls[] = array(
            'href'        => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        );
    }

    return $urls;
}
add_filter( 'wp_resource_hints', 'promix_resource_hints', 10, 2 );
