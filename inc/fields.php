<?php
/**
 * Carbon Fields: загрузка библиотеки и регистрация полей.
 *
 * Поля описаны кодом и лежат в inc/fields/ — так они версионируются
 * вместе с темой и переносятся на прод обычным git pull.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Carbon_Fields;
use Carbon_Fields\Container;

/**
 * Библиотека ставится composer'ом в vendor/ и коммитится вместе с темой:
 * на хостинге composer может быть недоступен.
 */
function promix_boot_carbon_fields(): void {
    $autoload = get_theme_file_path( 'vendor/autoload.php' );

    if ( ! file_exists( $autoload ) ) {
        return;
    }

    require_once $autoload;
    Carbon_Fields::boot();
}
add_action( 'after_setup_theme', 'promix_boot_carbon_fields', 20 );

// Иконки Lucide для полей и шаблонов.
require_once get_theme_file_path( 'inc/icons.php' );

// Список марок для секции брендов, пока повторитель в админке пустой.
require_once get_theme_file_path( 'inc/brands-default.php' );

// Отзывы из 2ГИС, пока повторитель в админке пустой.
require_once get_theme_file_path( 'inc/reviews-default.php' );

/**
 * Наборы полей подключаются по одному файлу на секцию главной.
 */
function promix_register_fields(): void {
    if ( ! class_exists( Carbon_Fields::class ) ) {
        return;
    }

    foreach ( array( 'home-hero', 'home-catalog', 'home-brands', 'options', 'home-about', 'home-why', 'home-reviews', 'home-contacts' ) as $group ) {
        $file = get_theme_file_path( "inc/fields/{$group}.php" );

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}
add_action( 'carbon_fields_register_fields', 'promix_register_fields' );

/**
 * На главной блочный редактор не нужен: содержимое собирают шаблоны и поля,
 * а в Gutenberg метабоксы Carbon Fields прячутся в самый низ страницы.
 *
 * @param bool     $use_block_editor Включать ли Gutenberg.
 * @param \WP_Post $post             Редактируемая запись.
 * @return bool
 */
function promix_disable_block_editor_on_front( bool $use_block_editor, $post ): bool {
    $front_id = (int) get_option( 'page_on_front' );

    if ( $front_id && isset( $post->ID ) && (int) $post->ID === $front_id ) {
        return false;
    }

    return $use_block_editor;
}
add_filter( 'use_block_editor_for_post', 'promix_disable_block_editor_on_front', 10, 2 );

/**
 * Редактор на главной тоже лишний — остаётся заголовок и поля секций.
 * У остальных страниц он на месте, поэтому проверяем, что открыта именно главная.
 */
function promix_hide_front_page_editor(): void {
    $front_id = (int) get_option( 'page_on_front' );

    if ( ! $front_id ) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- читаем только id открытой записи.
    $post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;

    if ( $post_id === $front_id ) {
        remove_post_type_support( 'page', 'editor' );
    }
}
add_action( 'admin_init', 'promix_hide_front_page_editor' );

/**
 * Значение поля главной страницы с запасным вариантом.
 *
 * Пока поля не заполнены, секции показывают согласованные тексты статики —
 * страница не разъезжается на пустом сайте.
 *
 * @param string $name    Имя поля без префикса promix_.
 * @param mixed  $default Что вернуть, если поле пустое.
 * @return mixed
 */
function promix_field( string $name, $default = '' ) {
    if ( ! function_exists( 'carbon_get_post_meta' ) ) {
        return $default;
    }

    $page_id = (int) get_queried_object_id();

    if ( ! $page_id ) {
        return $default;
    }

    $value = carbon_get_post_meta( $page_id, 'promix_' . $name );

    if ( '' === $value || null === $value || array() === $value ) {
        return $default;
    }

    return $value;
}

/**
 * Контейнер полей для главной страницы.
 *
 * Условия у всех секций одинаковые, поэтому собираются в одном месте.
 * Заодно блок сворачивается по умолчанию: секций семь, развёрнутыми они
 * превращают страницу редактирования в километровую простыню.
 *
 * @param string $title Заголовок блока в админке.
 * @return \Carbon_Fields\Container\Post_Meta_Container
 */
function promix_front_container( string $title ) {
    $container = Container::make( 'post_meta', $title )
        ->where( 'post_type', '=', 'page' )
        ->where( 'post_id', '=', (int) get_option( 'page_on_front' ) );

    $GLOBALS['promix_front_containers'][] = $container->get_id();

    return $container;
}

/**
 * Свернуть блоки полей: фильтры вешаются на add_meta_boxes, когда
 * зарегистрированы уже все секции — в момент создания контейнера
 * последний из них не успевал.
 */
function promix_collapse_field_boxes(): void {
    foreach ( (array) ( $GLOBALS['promix_front_containers'] ?? array() ) as $id ) {
        add_filter( 'postbox_classes_page_' . $id, 'promix_close_postbox' );
    }
}
add_action( 'add_meta_boxes', 'promix_collapse_field_boxes', 99 );

/**
 * Класс свёрнутого блока — пока редактор не решил иначе.
 *
 * Как только он свернёт или развернёт хоть один блок, WordPress запомнит
 * его выбор в настройках пользователя, и мы больше не вмешиваемся.
 *
 * @param string[] $classes Классы блока.
 * @return string[]
 */
function promix_close_postbox( array $classes ): array {
    if ( false !== get_user_option( 'closedpostboxes_page' ) ) {
        return $classes;
    }

    $classes[] = 'closed';

    return $classes;
}

/**
 * Значение общей настройки сайта с запасным вариантом.
 *
 * Контакты повторяются в шапке, меню, секции контактов и подвале —
 * читаются одним помощником, чтобы правка в админке доходила везде.
 *
 * @param string $name    Имя поля без префикса promix_.
 * @param string $default Что вернуть, если поле пустое.
 * @return string
 */
function promix_option( string $name, string $default = '' ): string {
    if ( ! function_exists( 'carbon_get_theme_option' ) ) {
        return $default;
    }

    $value = carbon_get_theme_option( 'promix_' . $name );

    return ( '' === $value || null === $value ) ? $default : (string) $value;
}

/**
 * Контакты магазина: значения по умолчанию совпадают с согласованной статикой.
 *
 * @return array<string, string>
 */
function promix_contacts(): array {
    return array(
        'phone'         => promix_option( 'phone', '+7 (953) 484-00-00' ),
        'phone_raw'     => promix_option( 'phone_raw', '+79534840000' ),
        'address'       => promix_option( 'address', 'Казань, ул. Габдуллы Тукая, 91' ),
        'address_short' => promix_option( 'address_short', 'Габдуллы Тукая, 91' ),
        'hours'         => promix_option( 'hours', 'Пн–Пт 9:00–18:00' ),
        'hours_extra'   => promix_option( 'hours_extra', 'Сб 9:00–14:00 · Вс — выходной' ),
        'max_url'       => promix_option( 'max_url', '#' ),
        'gis_url'       => promix_option( '2gis_url', 'https://2gis.ru/kazan/firm/70000001060590384' ),
        'yandex_url'    => promix_option( 'yandex_url', 'https://yandex.ru/maps/org/promix/59684652364/' ),
        'map_embed'     => promix_option( 'map_embed', 'https://yandex.ru/map-widget/v1/org/promix/59684652364/?ll=49.120092%2C55.774053&z=17' ),
    );
}

/**
 * Переносы строк из textarea превращаются в <br>: заголовки секций
 * ломаются по строкам ровно так, как их набрали в админке.
 *
 * @param string $text Текст поля.
 * @return string Готовая к выводу разметка.
 */
function promix_nl2br( string $text ): string {
    return nl2br( esc_html( $text ), false );
}
