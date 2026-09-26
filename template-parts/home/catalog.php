<?php
/**
 * Секция главной: catalog.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$kicker = promix_field( 'catalog_kicker', 'Каталог' );
$title  = promix_field( 'catalog_title', "Всё для полного\nцикла работ" );
$lead   = promix_field( 'catalog_lead', 'Материалы, инструмент и оборудование для всех этапов работы — от подготовки поверхности до финишного покрытия. Подскажем, что подойдёт под ваш объект.' );

/*
 * Четыре раздела каталога из структуры заказчика. Названия совпадают
 * с разделами в WooCommerce, ссылка ведёт на страницу раздела.
 */
$items = promix_field(
    'catalog_items',
    array(
        array(
            'cat_title' => 'Оборудование',
            'cat_text'  => 'Краскораспылители, шлифмашины, пылесосы и освещение. TECMASTER, Wagner, FLEX, MIRKA',
            'cat_url'   => promix_catalog_url( 'Оборудование' ),
        ),
        array(
            'cat_title' => 'Материалы',
            'cat_text'  => 'Шпатлёвки, грунты, краски и клей, стеклохолст и флизелин. PROMIX, OTTINGER, АКСУ',
            'cat_url'   => promix_catalog_url( 'Материалы' ),
        ),
        array(
            'cat_title' => 'Инструмент',
            'cat_text'  => 'Шпатели и правила, валики, кисти, кельмы, лотки и шлифовальный инструмент',
            'cat_url'   => promix_catalog_url( 'Инструмент' ),
        ),
        array(
            'cat_title' => 'Расходные материалы',
            'cat_text'  => 'Малярные ленты, укрывные плёнки и бумага, абразивы и средства защиты',
            'cat_url'   => promix_catalog_url( 'Расходные материалы' ),
        ),
    )
);

$wide_image = promix_field( 'catalog_wide_image' );
$wide_title = promix_field( 'catalog_wide_title', 'Весь каталог' );
$total      = promix_catalog_total();
$wide_note  = promix_field(
    'catalog_wide_note',
    sprintf(
        /* translators: 1 — число позиций, 2 — слово «позиция». */
        __( '%1$s %2$s с ценами', 'promix' ),
        number_format_i18n( $total ),
        promix_plural( $total, __( 'позиция', 'promix' ), __( 'позиции', 'promix' ), __( 'позиций', 'promix' ) )
    )
);
$wide_url   = promix_field( 'catalog_wide_url', promix_catalog_url() );

?>
<section class="section section--alt" id="catalog">
        <div class="container">

            <div class="section__head">
                <div>
                    <?php if ( $kicker ) : ?>
                        <p class="kicker"><?php echo esc_html( $kicker ); ?></p>
                    <?php endif; ?>
                    <h2 class="section__title"><?php echo promix_nl2br( $title ); ?></h2>
                </div>
                <p class="section__lead"><?php echo esc_html( $lead ); ?></p>
            </div>

            <div class="cats<?php echo 0 === count( $items ) % 4 ? ' cats--rows' : ''; ?>">
            <?php foreach ( $items as $index => $item ) : ?>
                <a class="cat" href="<?php echo esc_url( $item['cat_url'] ? $item['cat_url'] : '#' ); ?>">
                    <span class="cat__num"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
                    <h3 class="cat__title"><?php echo esc_html( $item['cat_title'] ); ?></h3>
                    <p class="cat__text"><?php echo esc_html( $item['cat_text'] ); ?></p>
                    <span class="cat__arrow" aria-hidden="true">
                    <?php promix_the_icon( 'arrow-up-right', 2 ); ?>
                </span>
                </a>
            <?php endforeach; ?>

                <a class="cat cat--wide" href="<?php echo esc_url( $wide_url ); ?>">
                    <?php if ( $wide_image ) : ?>
                        <?php
                        echo wp_get_attachment_image(
                            (int) $wide_image,
                            'large',
                            false,
                            array(
                                'class'   => 'cat__photo',
                                'alt'     => '',
                                'loading' => 'lazy',
                            )
                        );
                        ?>
                    <?php else : ?>
                        <img class="cat__photo" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/office/11.webp' ) ); ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <span class="cat__wide-text">
                        <span class="cat__wide-title"><?php echo esc_html( $wide_title ); ?></span>
                        <span class="cat__wide-note"><?php echo esc_html( $wide_note ); ?></span>
                    </span>
                    <span class="cat__arrow" aria-hidden="true">
                    <?php promix_the_icon( 'arrow-up-right', 2 ); ?>
                </span>
                </a>
            </div>

        </div>
    </section>
