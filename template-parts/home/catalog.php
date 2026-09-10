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

$items = promix_field(
    'catalog_items',
    array(
        array( 'cat_title' => 'Краски',           'cat_text' => 'Интерьерные, фасадные и декоративные покрытия',    'cat_url' => '#' ),
        array( 'cat_title' => 'Шпаклёвки и клеи', 'cat_text' => 'Составы для выравнивания и подготовки поверхностей', 'cat_url' => '#' ),
        array( 'cat_title' => 'Грунты',           'cat_text' => 'Системы под разные основания и условия нанесения',   'cat_url' => '#' ),
        array( 'cat_title' => 'Инструмент',       'cat_text' => 'Валики, кисти, шпатели и малярная оснастка',         'cat_url' => '#' ),
        array( 'cat_title' => 'Оборудование',     'cat_text' => 'Краскопульты и техника для профессиональных работ',  'cat_url' => '#' ),
        array( 'cat_title' => 'Расходники',       'cat_text' => 'Ленты, плёнка, абразивы, средства для очистки',      'cat_url' => '#' ),
    )
);

$wide_image = promix_field( 'catalog_wide_image' );
$wide_title = promix_field( 'catalog_wide_title', 'Весь каталог' );
$wide_note  = promix_field( 'catalog_wide_note', 'Более 1 800 позиций с ценами и наличием' );
$wide_url   = promix_field( 'catalog_wide_url', '#' );

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
                        <img class="cat__photo" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/office/11.png' ) ); ?>" alt="" loading="lazy">
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
