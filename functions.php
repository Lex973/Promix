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
 * Слово в нужной форме: 1 товар, 2 товара, 5 товаров.
 *
 * Собственный помощник, потому что _n() без файла перевода считает
 * по английским правилам и на 21 даёт множественное число.
 *
 * @param int    $number Количество.
 * Формы передаются уже через __(): у русского их три, а _n() без
 * .po-файла умеет только две.
 *
 * @param string $one    Форма для 1.
 * @param string $few    Форма для 2-4.
 * @param string $many   Форма для 5 и больше.
 * @return string
 */
function promix_plural( int $number, string $one, string $few, string $many ): string {
    $ten     = $number % 10;
    $hundred = $number % 100;

    if ( 1 === $ten && 11 !== $hundred ) {
        return $one;
    }

    if ( $ten >= 2 && $ten <= 4 && ( $hundred < 10 || $hundred >= 20 ) ) {
        return $few;
    }

    return $many;
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
            'primary'        => __( 'Основное меню (шапка и мобильное)', 'promix' ),
            'footer'         => __( 'Подвал: разделы', 'promix' ),
            'footer_catalog' => __( 'Подвал: каталог', 'promix' ),
        )
    );
}
add_action( 'after_setup_theme', 'promix_setup' );

// Каталог: адреса, фильтры и выборка товаров.
require_once get_theme_file_path( 'inc/catalog.php' );

// Мета-теги: описание и Open Graph.
require_once get_theme_file_path( 'inc/meta.php' );

// Меню сайта.
require_once get_theme_file_path( 'inc/nav.php' );

// WooCommerce: поддержка темой и транслит адресов.
require_once get_theme_file_path( 'inc/woocommerce.php' );

// Корзина и оформление заказа.
require_once get_theme_file_path( 'inc/cart.php' );

// Заявки и заказы — менеджеру в MAX.
require_once get_theme_file_path( 'inc/max.php' );

// Поля админки на Carbon Fields.
require_once get_theme_file_path( 'inc/fields.php' );

// Заявки с сайта.
require_once get_theme_file_path( 'inc/lead-form.php' );

/**
 * Стили и скрипты фронта.
 */
function promix_assets(): void {
    wp_enqueue_style(
        'promix-variables',
        get_theme_file_uri( 'assets/css/variables.css' ),
        array(),
        promix_asset_version( 'assets/css/variables.css' )
    );

    /*
     * Стили разложены по кускам страницы. Первые четыре нужны везде,
     * остальные — только там, где эти блоки есть: незачем возить стили
     * главной на страницу политики.
     */
    $is_catalog = promix_is_catalog();
    $is_product = function_exists( 'is_product' ) && is_product();
    $is_cart    = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() );
    $sheets     = array( 'base', 'header', 'footer', 'modal' );

    if ( is_front_page() ) {
        $sheets[] = 'home';
    }

    // Странице товара нужны карточки соседей, корзине — крошки из стилей каталога.
    if ( $is_catalog || $is_product || $is_cart ) {
        $sheets[] = 'catalog';
    }

    if ( $is_product ) {
        $sheets[] = 'product';
    }

    if ( $is_cart ) {
        $sheets[] = 'cart';
    }

    if ( is_404() ) {
        $sheets[] = 'notfound';
    }

    // Каждый следующий зависит от предыдущего — порядок подключения важен.
    $deps = array( 'promix-variables' );

    foreach ( $sheets as $sheet ) {
        $handle = 'promix-' . $sheet;

        wp_enqueue_style(
            $handle,
            get_theme_file_uri( "assets/css/{$sheet}.css" ),
            $deps,
            promix_asset_version( "assets/css/{$sheet}.css" )
        );

        $deps = array( $handle );
    }

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
        'cookie'  => array(),
        'catalog' => array( 'promix-main' ),
        'product' => array(),
        'cart'    => array(),
    );

    foreach ( $scripts as $handle => $deps ) {
        // Ленты брендов и отзывов есть только на главной.
        if ( ! is_front_page() && in_array( $handle, array( 'brands', 'reviews' ), true ) ) {
            continue;
        }

        // Поиск, фильтры и сортировка — только на странице каталога.
        if ( 'catalog' === $handle && ! $is_catalog ) {
            continue;
        }

        // Счётчик количества — только на странице товара.
        if ( 'product' === $handle && ! $is_product ) {
            continue;
        }

        // Кнопки «В корзину» есть в каталоге, на товаре и в корзине; главной
        // и политике скрипт с nonce ни к чему.
        if ( 'cart' === $handle && ! ( $is_catalog || $is_product || $is_cart ) ) {
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
 * Лишнее от ядра: тема классическая, блоков на фронте нет.
 *
 * global-styles и wp-block-library — 14 КБ инлайн-CSS на каждой странице,
 * wp_print_font_faces — 4 запроса к базе за шрифтами блоков, которых нет,
 * emoji — скрипт и стили для замены смайлов картинками.
 */
function promix_core_assets(): void {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'promix_core_assets', 100 );

/**
 * Хуки ядра снимаются на init: global-styles классическая тема получает
 * в подвале, не в шапке, поэтому wp_dequeue_style его не достаёт.
 */
function promix_core_head(): void {
    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
    remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
    remove_action( 'wp_head', 'wp_print_font_faces', 50 );
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'wp_head', 'wp_generator' );
}
add_action( 'init', 'promix_core_head' );

/**
 * Шрифты объявлены прямо в head.
 *
 * Так браузер узнаёт о них из первого же ответа и не ждёт, пока догрузится
 * отдельный css-файл: preload с его предупреждениями в консоли не нужен.
 *
 * Оба шрифта переменные — один файл на все начертания; кириллица и латиница
 * лежат отдельно, каждая грузится только если на странице есть её буквы.
 * Знак рубля (U+20BD) добавлен в кириллический файл: в подмножествах
 * Google Fonts его нет, и цены рисовались запасным шрифтом.
 * Unbounded (--font-display) пока ни к одному элементу не применён, и файл
 * не скачивается: браузер берёт шрифт только тогда, когда тот кому-то нужен.
 */
function promix_font_faces(): void {
    $ranges = array(
        'cyrillic' => 'U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116, U+20BD',
        'latin'    => 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD',
    );

    $families = array(
        'Onest'     => '400 800',
        'Unbounded' => '500 700',
    );

    $css = '';

    foreach ( $families as $family => $weight ) {
        foreach ( $ranges as $subset => $range ) {
            $file = sprintf( 'assets/fonts/%s-%s.woff2', strtolower( $family ), $subset );

            $css .= sprintf(
                '@font-face{font-family:"%1$s";font-style:normal;font-weight:%2$s;font-display:swap;src:url(%3$s) format("woff2");unicode-range:%4$s}',
                $family,
                $weight,
                esc_url( get_theme_file_uri( $file ) ),
                $range
            );
        }
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- css собран из констант выше, адреса пропущены через esc_url().
    echo '<style id="promix-fonts">' . $css . '</style>' . "
";
}
add_action( 'wp_head', 'promix_font_faces', 2 );
