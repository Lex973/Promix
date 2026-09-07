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

/**
 * Наборы полей подключаются по одному файлу на секцию главной.
 */
function promix_register_fields(): void {
    if ( ! class_exists( Carbon_Fields::class ) ) {
        return;
    }

    foreach ( array( 'home-hero' ) as $group ) {
        $file = get_theme_file_path( "inc/fields/{$group}.php" );

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}
add_action( 'carbon_fields_register_fields', 'promix_register_fields' );

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
