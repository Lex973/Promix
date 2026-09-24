<?php
/**
 * Список марок, с которыми работает PROMIX.
 *
 * Показывается, пока повторитель в админке пустой. Порядок — как в перечне
 * заказчика, своя марка первой; цвет наведения задаётся только там, где фирменный оттенок узнаваем.
 * Ссылка пустая: плитка сама найдёт страницу бренда Woo по названию
 * (см. promix_brand_url), в админке её можно переопределить.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Марки для лент на главной.
 *
 * @return array<int, array<string, string>>
 */
function promix_default_brands(): array {
    $names = array(
        'PROMIX'          => '#DF0101',
        'DLT'             => '',
        'RoxelPro'        => '',
        'STORCH'          => '#E2001A',
        'SUNMIGHT'        => '',
        'LEVEL 5'         => '',
        'OLEJNIK'         => '',
        'KORSHUN'         => '',
        'AKSU'            => '',
        'SILK PLASTER'    => '',
        'Ottinger'        => '',
        'Wagner'          => '',
        'Wooster'         => '',
        'ROLLINGDOG'      => '',
        'Dolphin'         => '',
        'TECMASTER'       => '',
        'Vagans'          => '',
        'GOLDBLATT'       => '',
        'FLEX'            => '',
        'Howard'          => '',
        'SHEETROCK'       => '',
        'Perfect Line'    => '',
        'Prime Color'     => '',
        'Mirka'           => '#F2B705',
        'Bihui'           => '',
        'Bosch'           => '',
        'Knauf'           => '#006EB7',
        'Schtaer'         => '',
        'Strait-Flex'     => '',
        'Graco'           => '#0057A8',
        'LOSSEW'          => '',
        'Abrex'           => '',
        'Formel'          => '',
        'Corner Profi'    => '',
    );

    $brands = array();

    foreach ( $names as $name => $color ) {
        $brands[] = array(
            'brand_name'  => $name,
            'brand_url'   => '',
            'brand_color' => $color,
            'brand_logo'  => '',
        );
    }

    return $brands;
}
