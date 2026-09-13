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

    // Иконки декоративные: рядом либо текст, либо aria-label на кнопке.
    return sprintf(
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"%s>%s</svg>',
        esc_attr( (string) $stroke ),
        $extra_class ? ' class="' . esc_attr( $extra_class ) . '"' : '',
        $cache[ $name ]
    );
}

/**
 * Какие теги и атрибуты допустимы в инлайновом SVG темы.
 *
 * Иконки из файлов и звёзды печатаются через wp_kses с этим списком —
 * вывод экранируется по-настоящему, а не помечается «доверенным».
 *
 * @return array<string, array<string, bool>>
 */
function promix_svg_kses(): array {
    static $allowed = null;

    if ( null === $allowed ) {
        $shape = array(
            'd'                 => true,
            'fill'              => true,
            'stroke'            => true,
            'stroke-width'      => true,
            'stroke-linecap'    => true,
            'stroke-linejoin'   => true,
            'cx'                => true,
            'cy'                => true,
            'r'                 => true,
            'rx'                => true,
            'ry'                => true,
            'x'                 => true,
            'y'                 => true,
            'x1'                => true,
            'y1'                => true,
            'x2'                => true,
            'y2'                => true,
            'width'             => true,
            'height'            => true,
            'points'            => true,
            'transform'         => true,
            'opacity'           => true,
            'fill-rule'         => true,
            'clip-rule'         => true,
        );

        $allowed = array(
            'svg'      => array(
                'viewbox'           => true,
                'fill'              => true,
                'stroke'            => true,
                'stroke-width'      => true,
                'stroke-linecap'    => true,
                'stroke-linejoin'   => true,
                'aria-hidden'       => true,
                'aria-label'        => true,
                'role'              => true,
                'class'             => true,
                'width'             => true,
                'height'            => true,
                'xmlns'             => true,
            ),
            'g'        => $shape,
            'path'     => $shape,
            'circle'   => $shape,
            'ellipse'  => $shape,
            'rect'     => $shape,
            'line'     => $shape,
            'polyline' => $shape,
            'polygon'  => $shape,
            'div'      => array(
                'class'      => true,
                'style'      => true,
                'role'       => true,
                'aria-label' => true,
            ),
        );
    }

    return $allowed;
}

/**
 * Напечатать иконку.
 *
 * @param string $name        Имя файла без расширения.
 * @param float  $stroke      Толщина обводки.
 * @param string $extra_class Дополнительный класс на svg.
 */
function promix_the_icon( string $name, float $stroke = 1.8, string $extra_class = '' ): void {
    echo wp_kses( promix_icon( $name, $stroke, $extra_class ), promix_svg_kses() );
}

/**
 * Напечатать ряд звёзд (см. promix_stars).
 *
 * @param float  $rating Оценка от 0 до 5.
 * @param string $label  Подпись для программ чтения с экрана.
 */
function promix_the_stars( float $rating, string $label = '' ): void {
    // Инлайновый style="--stars: N" kses по умолчанию вырезает, для него своё разрешение.
    add_filter( 'safe_style_css', 'promix_svg_kses_style' );
    echo wp_kses( promix_stars( $rating, $label ), promix_svg_kses() );
    remove_filter( 'safe_style_css', 'promix_svg_kses_style' );
}

/**
 * Разрешить кастомное свойство --stars в инлайновом стиле звёзд.
 *
 * @param string[] $styles Разрешённые свойства.
 * @return string[]
 */
function promix_svg_kses_style( array $styles ): array {
    $styles[] = '--stars';
    return $styles;
}

/**
 * Иконка категории каталога.
 *
 * Фотографий товаров пока нет, поэтому карточка показывает иконку
 * по разделу — так сетка не выглядит набором пустых прямоугольников.
 *
 * @param string $category Название категории.
 * @return string Имя файла иконки.
 */
function promix_category_icon( string $category ): string {
    $map = array(
        'Шпатели и лезвия'                   => 'ruler',
        'Ленты и укрывные материалы'         => 'package',
        'Валики и ручки'                     => 'paint-roller',
        'Окрасочное оборудование'            => 'spray-can',
        'Шлифование и абразивы'              => 'disc',
        'Краски, грунты, лаки'               => 'paint-bucket',
        'Кисти и щётки'                      => 'brush',
        'Ёмкости и инвентарь'                => 'package',
        'Шпатлёвки, штукатурки, клеи'        => 'droplet',
        'Средства защиты'                    => 'shield',
        'Стеклохолст и армирующие материалы' => 'layers',
        'Электроинструмент и оснастка'       => 'wrench',
        'Разметка и измерение'               => 'ruler',
    );

    return $map[ $category ] ?? 'package';
}

/**
 * Ряд звёзд с дробной оценкой.
 *
 * Пять контурных звёзд и залитая копия поверх, обрезанная по ширине:
 * 4,9 из 5 — это 98% ширины, а не «четыре звезды» и не «пять».
 *
 * @param float  $rating Оценка от 0 до 5, можно дробную.
 * @param string $label  Подпись для программ чтения с экрана.
 * @return string
 */
function promix_stars( float $rating, string $label = '' ): string {
    $rating = max( 0.0, min( 5.0, $rating ) );
    $path   = 'M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z';

    $outline = sprintf(
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="%s"/></svg>',
        $path
    );

    $filled = sprintf(
        '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="%s"/></svg>',
        $path
    );

    return sprintf(
        '<div class="stars"%s style="--stars: %s"><div class="stars__row">%s</div><div class="stars__row stars__row--fill">%s</div></div>',
        $label ? ' role="img" aria-label="' . esc_attr( $label ) . '"' : '',
        esc_attr( (string) round( $rating / 5 * 100, 2 ) ),
        str_repeat( $outline, 5 ),
        str_repeat( $filled, 5 )
    );
}

/**
 * Инициалы для кружка в отзыве: «Ленар Гамора» → «ЛГ».
 *
 * @param string $name Имя автора отзыва.
 * @return string
 */
function promix_initials( string $name ): string {
    $words = preg_split( '/\s+/', trim( $name ), 2 );
    $out   = '';

    foreach ( (array) $words as $word ) {
        if ( '' !== $word ) {
            $out .= mb_strtoupper( mb_substr( $word, 0, 1 ) );
        }
    }

    return $out;
}
