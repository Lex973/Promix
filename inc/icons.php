<?php
/**
 * Иконки Lucide: печать инлайновым SVG и список для выпадающих полей.
 *
 * Исходники лежат в assets/icons. Инлайн вместо <img> нужен, чтобы иконка
 * красилась через currentColor вместе с текстом рядом.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Иконки, доступные в полях админки: имя файла => подпись для выпадающего списка.
 *
 * @return array<string, string>
 */
function promix_icon_choices(): array {
    return array(
        'circle-check-big' => __( 'Галочка в круге', 'promix' ),
        'star'             => __( 'Звезда', 'promix' ),
        'package'          => __( 'Коробка', 'promix' ),
        'map-pin'          => __( 'Точка на карте', 'promix' ),
        'clock'            => __( 'Часы', 'promix' ),
        'phone'            => __( 'Телефон', 'promix' ),
        'message-circle'   => __( 'Реплика диалога', 'promix' ),
        'users'            => __( 'Люди', 'promix' ),
        'layers'           => __( 'Слои', 'promix' ),
        'house'            => __( 'Дом', 'promix' ),
        'droplet'          => __( 'Капля', 'promix' ),
        'palette'          => __( 'Палитра', 'promix' ),
        'brush'            => __( 'Кисть', 'promix' ),
        'paint-roller'     => __( 'Валик', 'promix' ),
        'paint-bucket'     => __( 'Ведро с краской', 'promix' ),
        'spray-can'        => __( 'Баллончик', 'promix' ),
        'ruler'            => __( 'Рулетка', 'promix' ),
        'external-link'    => __( 'Ссылка наружу', 'promix' ),
    );
}

/**
 * Готовый инлайновый SVG иконки.
 *
 * @param string $name        Имя файла без расширения.
 * @param float  $stroke      Толщина обводки.
 * @param string $extra_class Дополнительный класс на svg.
 * @return string Разметка иконки или пустая строка, если файла нет.
 */
function promix_icon( string $name, float $stroke = 1.8, string $extra_class = '' ): string {
    static $cache = array();

    // Имя приходит из поля админки — оставляем только безопасные символы.
    $name = preg_replace( '/[^a-z0-9\-]/', '', strtolower( $name ) );

    if ( ! $name ) {
        return '';
    }

    if ( ! isset( $cache[ $name ] ) ) {
        $file = get_theme_file_path( "assets/icons/{$name}.svg" );

        if ( ! file_exists( $file ) ) {
            $cache[ $name ] = '';
        } else {
            $raw   = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- локальный файл темы.
            $open  = strpos( $raw, '>' );
            $close = strrpos( $raw, '</svg>' );

            // Из файла берётся только внутренность: обёртку рисуем свою.
            $cache[ $name ] = ( false === $open || false === $close )
                ? ''
                : str_replace( ' stroke-width="2"', '', substr( $raw, $open + 1, $close - $open - 1 ) );
        }
    }

    if ( ! $cache[ $name ] ) {
        return '';
    }

    return sprintf(
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%s" stroke-linecap="round" stroke-linejoin="round"%s>%s</svg>',
        esc_attr( (string) $stroke ),
        $extra_class ? ' class="' . esc_attr( $extra_class ) . '"' : '',
        $cache[ $name ]
    );
}
