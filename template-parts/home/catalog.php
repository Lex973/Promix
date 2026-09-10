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
 * Шесть самых больших разделов прайса. Названия и порядок совпадают
 * с фильтром в каталоге, ссылка открывает его с отмеченной категорией.
 */
$items = promix_field(
    'catalog_items',
    array(
        array(
            'cat_title' => 'Шпатели и лезвия',
            'cat_text'  => 'Японские и фасадные шпатели, сменные лезвия, кельмы. STORCH, OLEJNIK, GOLDBLATT',
            'cat_url'   => promix_catalog_url( 'Шпатели и лезвия' ),
        ),
        array(
            'cat_title' => 'Ленты и укрывные материалы',
            'cat_text'  => 'Малярные ленты, плёнка, флис и укрывная бумага для защиты помещения',
            'cat_url'   => promix_catalog_url( 'Ленты и укрывные материалы' ),
        ),
        array(
            'cat_title' => 'Валики и ручки',
            'cat_text'  => 'Валики под любую фактуру, бюгели, телескопические ручки и удлинители',
            'cat_url'   => promix_catalog_url( 'Валики и ручки' ),
        ),
        array(
            'cat_title' => 'Окрасочное оборудование',
            'cat_text'  => 'Краскораспылители, сопла, фильтры и запчасти. TECMASTER, Graco, Wagner',
            'cat_url'   => promix_catalog_url( 'Окрасочное оборудование' ),
        ),
        array(
            'cat_title' => 'Шлифование и абразивы',
            'cat_text'  => 'Круги, сетки и шлифблоки, зерно от P24 до P800. SUNMIGHT, RoxelPro, DLT',
            'cat_url'   => promix_catalog_url( 'Шлифование и абразивы' ),
        ),
        array(
            'cat_title' => 'Краски, грунты, лаки',
            'cat_text'  => 'Интерьерные и фасадные краски, грунты и лазури. Ottinger, PROMIX',
            'cat_url'   => promix_catalog_url( 'Краски, грунты, лаки' ),
        ),
    )
);

$wide_image = promix_field( 'catalog_wide_image' );
$wide_title = promix_field( 'catalog_wide_title', 'Весь каталог' );
$wide_note  = promix_field( 'catalog_wide_note', '1 451 позиция с ценами' );
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

            <div class="cats">
            <?php foreach ( $items as $index => $item ) : ?>
                <a class="cat" href="<?php echo esc_url( $item['cat_url'] ? $item['cat_url'] : '#' ); ?>">
                    <span class="cat__num"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
                    <h3 class="cat__title"><?php echo esc_html( $item['cat_title'] ); ?></h3>
                    <p class="cat__text"><?php echo esc_html( $item['cat_text'] ); ?></p>
                    <span class="cat__arrow" aria-hidden="true">
                    <?php echo promix_icon( 'arrow-up-right', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
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
                    <?php echo promix_icon( 'arrow-up-right', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                </span>
                </a>
            </div>

        </div>
    </section>
