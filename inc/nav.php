<?php
/**
 * Меню сайта.
 *
 * Пункты берутся из «Внешний вид → Меню». Пока меню там не собрано,
 * выводится тот же набор ссылок, что был в шаблонах: сайт не должен
 * оставаться без навигации из-за незаполненной админки.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Набор ссылок по умолчанию для области меню.
 *
 * Якоря пишутся вместе с адресом главной, чтобы «Каталог» работал
 * и с внутренних страниц, а не только с главной.
 *
 * @param string $location Область меню.
 * @return array<string, string> Адрес => подпись.
 */
function promix_default_menu( string $location ): array {
    $home = home_url( '/' );

    $menus = array(
        'primary' => array(
            $home . '#catalog'  => __( 'Каталог', 'promix' ),
            $home . '#brands'   => __( 'Бренды', 'promix' ),
            $home . '#about'    => __( 'О компании', 'promix' ),
            $home . '#reviews'  => __( 'Отзывы', 'promix' ),
            $home . '#contacts' => __( 'Контакты', 'promix' ),
        ),
        'footer' => array(
            $home . '#catalog'  => __( 'Каталог', 'promix' ),
            $home . '#brands'   => __( 'Бренды', 'promix' ),
            $home . '#about'    => __( 'О компании', 'promix' ),
            $home . '#why'      => __( 'Почему PROMIX', 'promix' ),
            $home . '#reviews'  => __( 'Отзывы', 'promix' ),
            $home . '#contacts' => __( 'Контакты', 'promix' ),
        ),
        /*
         * Разделы каталога ведут в никуда, пока нет WooCommerce: настоящие
         * адреса появятся вместе с категориями товаров.
         */
        'footer_catalog' => array(
            '#paints'    => __( 'Краски', 'promix' ),
            '#putty'     => __( 'Шпаклёвки и клеи', 'promix' ),
            '#primers'   => __( 'Грунты', 'promix' ),
            '#tools'     => __( 'Инструмент', 'promix' ),
            '#equipment' => __( 'Оборудование', 'promix' ),
            '#supplies'  => __( 'Расходники', 'promix' ),
        ),
    );

    return $menus[ $location ] ?? array();
}

/**
 * Запасной вывод меню: тот же список, что отдал бы wp_nav_menu.
 *
 * @param array<string, mixed> $args Аргументы wp_nav_menu.
 * @return void
 */
function promix_menu_fallback( array $args ): void {
    $location = (string) ( $args['theme_location'] ?? '' );
    $items    = promix_default_menu( $location );

    if ( ! $items ) {
        return;
    }

    printf( '<ul class="%s">', esc_attr( (string) ( $args['menu_class'] ?? '' ) ) );

    foreach ( $items as $url => $label ) {
        printf(
            '<li class="menu-item"><a href="%s">%s</a></li>',
            esc_url( $url ),
            esc_html( $label )
        );
    }

    echo '</ul>';
}

/**
 * Меню области с общими для темы настройками.
 *
 * @param string $location   Область меню.
 * @param string $menu_class Класс списка.
 * @return void
 */
function promix_menu( string $location, string $menu_class ): void {
    wp_nav_menu(
        array(
            'theme_location' => $location,
            'menu_class'     => $menu_class,
            'container'      => false,
            'depth'          => 1,
            'fallback_cb'    => 'promix_menu_fallback',
        )
    );
}
