<?php
/**
 * Список марок, с которыми работает PROMIX.
 *
 * Показывается, пока повторитель в админке пустой. Порядок — как в перечне
 * заказчика; цвет наведения задаётся только там, где фирменный оттенок узнаваем.
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
        'DLT'             => '',
        'RoxelPro'        => '',
        'STORCH'          => '#E2001A',
        'SUNMIGHT'        => '',
        'LEVEL 5'         => '',
        'OLEJNIK'         => '',
        "L'Outil Parfait" => '',
        'KORSHUN'         => '',
        'AKSU'            => '',
        'SILK PLASTER'    => '',
        'Ottinger'        => '',
        'Wagner'          => '',
        'Wooster'         => '',
        'ROLLINGDOG'      => '',
        'Dolphin'         => '',
        'Tesa'            => '',
        'ASPRO'           => '',
        'EMITENT'         => '',
        'TECMASTER'       => '',
        'Vagans'          => '',
        'Alva'            => '',
        'GOLDBLATT'       => '',
        'FLEX'            => '',
        'Howard'          => '',
        'SHEETROCK'       => '',
        'Pentrilo'        => '',
        'Perfect Line'    => '',
        'Prime Color'     => '',
        'Mirka'           => '#F2B705',
        'Folsen'          => '',
        'Alteritaly'      => '',
        'STMDECOR'        => '',
        'Bihui'           => '',
        'Ceresit'         => '#C8102E',
        'Olfa'            => '',
        'Grabo'           => '',
        'Bosch'           => '',
        'Knauf'           => '#006EB7',
        'Flugger'         => '',
        'Denalt'          => '',
        'Semin'           => '',
        'Schtaer'         => '',
        'Hansa'           => '',
        'Baumit'          => '',
        'Bergauf'         => '',
        'Dulux'           => '#00843D',
        'StMax'           => '',
        'Berizzi'         => '',
        'Strait-Flex'     => '',
        'Seal GP'         => '',
        'Graco'           => '#0057A8',
        'LOSSEW'          => '',
        'Titan'           => '',
        'Abrex'           => '',
        'Formel'          => '',
        'Corner Profi'    => '',
        'Mons'            => '',
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
