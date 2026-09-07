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

/**
 * Наборы полей подключаются по одному файлу на секцию главной.
 */
function promix_register_fields(): void {
    if ( ! class_exists( Carbon_Fields::class ) ) {
        return;
    }

    foreach ( array( 'home-hero', 'home-catalog', 'home-brands', 'home-about' ) as $group ) {
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
 * Переносы строк из textarea превращаются в <br>: заголовки секций
 * ломаются по строкам ровно так, как их набрали в админке.
 *
 * @param string $text Текст поля.
 * @return string Готовая к выводу разметка.
 */
function promix_nl2br( string $text ): string {
    return nl2br( esc_html( $text ), false );
}
