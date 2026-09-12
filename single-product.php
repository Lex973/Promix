<?php
/**
 * Страница товара.
 *
 * Слева фото (пока у большинства позиций его нет — стоит иконка раздела),
 * справа всё, что нужно для решения: название, артикул, бренд, раздел,
 * цена и количество. «В корзину» здесь единственная красная кнопка.
 * Вопрос технологу уходит в общую модалку заявки с подстановкой товара.
 * Внизу — четыре соседа по разделу и спокойная плашка с заявкой.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();

    $product = wc_get_product( get_the_ID() );

    if ( ! $product instanceof WC_Product ) {
        continue;
    }

    $name     = $product->get_name();
    $sku      = $product->get_sku();
    $price    = (float) $product->get_price();
    $brand    = promix_product_brand( $product );
    $terms    = get_the_terms( $product->get_id(), 'product_cat' );
    $category = $terms && ! is_wp_error( $terms ) ? $terms[0] : null;
    $cat_link = $category ? get_term_link( $category ) : '';
    $cat_link = is_wp_error( $cat_link ) ? '' : $cat_link;
    $shop_url = promix_catalog_url();
    $badge    = ( 'PROMIX' === $brand ) ? __( 'Своя марка', 'promix' ) : '';

    // Что уйдёт в заявку, если спросить технолога прямо отсюда.
    $lead_source = $sku
        ? sprintf( /* translators: 1 — название, 2 — артикул. */ __( 'Товар: %1$s (арт. %2$s)', 'promix' ), $name, $sku )
        : sprintf( /* translators: %s — название. */ __( 'Товар: %s', 'promix' ), $name );

    $related = $category ? wc_get_products(
        array(
            'status'     => 'publish',
            'visibility' => 'visible',
            'limit'      => 4,
            'category'   => array( $category->slug ),
            'exclude'    => array( $product->get_id() ),
            'orderby'    => 'menu_order',
            'order'      => 'ASC',
        )
    ) : array();

    // Разметка schema.org/Product для поисковиков: Woo соберёт и напечатает её в подвале.
    WC()->structured_data->generate_product_data( $product );
    ?>

    <section class="section single">
        <div class="container">

            <?php
            $crumbs = array( __( 'Каталог', 'promix' ) => $shop_url );

            if ( $category && $cat_link ) {
                $crumbs[ $category->name ] = $cat_link;
            }

            get_template_part( 'template-parts/crumbs', null, array( 'items' => $crumbs ) );
            ?>

            <div class="single__layout">

                <div class="single__media">
                    <?php if ( $badge ) : ?>
                        <span class="product__badge"><?php echo esc_html( $badge ); ?></span>
                    <?php endif; ?>

                    <?php if ( $product->get_image_id() ) : ?>
                        <?php echo $product->get_image( 'woocommerce_single', array( 'class' => 'single__img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка картинки. ?>
                    <?php else : ?>
                        <?php echo promix_icon( promix_category_icon( $category ? $category->name : '' ), 1, 'single__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                        <p class="single__nophoto"><?php esc_html_e( 'Фото скоро появится', 'promix' ); ?></p>
                    <?php endif; ?>
                </div>

                <div class="single__info">
                    <h1 class="single__title"><?php echo esc_html( $name ); ?></h1>

                    <dl class="single__meta">
                        <?php if ( $sku ) : ?>
                            <div class="single__meta-row">
                                <dt><?php esc_html_e( 'Артикул', 'promix' ); ?></dt>
                                <dd>
                                    <button class="product__sku single__sku" type="button" data-copy="<?php echo esc_attr( $sku ); ?>"
                                            aria-label="<?php echo esc_attr( sprintf( /* translators: %s — артикул. */ __( 'Скопировать артикул %s', 'promix' ), $sku ) ); ?>">
                                        <span data-copy-label><?php echo esc_html( $sku ); ?></span>
                                        <?php echo promix_icon( 'copy', 1.7 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                                    </button>
                                </dd>
                            </div>
                        <?php endif; ?>

                        <?php if ( $brand ) : ?>
                            <div class="single__meta-row">
                                <dt><?php esc_html_e( 'Бренд', 'promix' ); ?></dt>
                                <dd><?php echo esc_html( $brand ); ?></dd>
                            </div>
                        <?php endif; ?>

                        <?php if ( $category ) : ?>
                            <div class="single__meta-row">
                                <dt><?php esc_html_e( 'Раздел', 'promix' ); ?></dt>
                                <dd><a href="<?php echo esc_url( $cat_link ); ?>"><?php echo esc_html( $category->name ); ?></a></dd>
                            </div>
                        <?php endif; ?>
                    </dl>

                    <p class="single__price" data-price="<?php echo esc_attr( (string) $price ); ?>"><?php echo esc_html( promix_price( $price ) ); ?></p>

                    <?php get_template_part( 'template-parts/cart/control', null, array( 'product' => $product, 'variant' => 'single' ) ); ?>

                    <p class="single__note"><?php esc_html_e( 'Наличие и срок поставки подтвердит менеджер после заказа.', 'promix' ); ?></p>

                    <a class="btn btn--outline single__ask" href="#contacts"
                       data-lead-open="<?php echo esc_attr( $lead_source ); ?>"
                       data-lead-title="<?php esc_attr_e( 'Спросим у технолога', 'promix' ); ?>">
                        <?php esc_html_e( 'Спросить у технолога', 'promix' ); ?>
                    </a>
                </div>
            </div>

            <?php if ( $product->get_description() ) : ?>
                <?php $contacts = promix_contacts(); ?>
                <div class="single__about">
                    <div class="single__about-text">
                        <h2 class="single__about-title"><?php esc_html_e( 'Описание', 'promix' ); ?></h2>
                        <div class="single__desc doc">
                            <?php echo wp_kses_post( wpautop( $product->get_description() ) ); ?>
                        </div>
                    </div>

                    <?php /* Прямой контакт — в отличие от плашки внизу, где форма заявки. */ ?>
                    <aside class="single__help">
                        <p class="single__help-title"><?php esc_html_e( 'Вопрос по товару?', 'promix' ); ?></p>
                        <p class="single__help-text"><?php esc_html_e( 'Позвоните или напишите — подскажем по применению и совместимости, подберём аналог, если этого нет на складе.', 'promix' ); ?></p>
                        <div class="single__help-row">
                            <a class="single__help-phone" href="<?php echo esc_url( promix_tel_href() ); ?>"><?php echo esc_html( $contacts['phone'] ); ?></a>
                            <?php get_template_part( 'template-parts/btn-max', null, array( 'class' => 'single__help-max', 'label' => '', 'aria' => __( 'Написать в MAX', 'promix' ) ) ); ?>
                        </div>
                        <p class="single__help-hours"><?php echo esc_html( $contacts['hours'] ); ?></p>
                    </aside>
                </div>
            <?php endif; ?>

            <?php if ( $related ) : ?>
                <div class="single__related">
                    <div class="single__related-head">
                        <h2 class="single__related-title">
                            <?php
                            printf(
                                /* translators: %s — название раздела. */
                                esc_html__( 'Ещё из раздела «%s»', 'promix' ),
                                esc_html( $category->name )
                            );
                            ?>
                        </h2>
                        <a class="single__related-link" href="<?php echo esc_url( $cat_link ); ?>">
                            <?php esc_html_e( 'Весь раздел', 'promix' ); ?>
                            <?php echo promix_icon( 'arrow-right', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                        </a>
                    </div>

                    <div class="products">
                        <?php foreach ( $related as $item ) : ?>
                            <?php get_template_part( 'template-parts/catalog/card', null, array( 'product' => $item ) ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <section class="section section--alt cta">
        <div class="container">
            <div class="cta__box">
                <div class="cta__text">
                    <h2 class="cta__title"><?php esc_html_e( 'Нужен объём или подбор под объект?', 'promix' ); ?></h2>
                    <p class="cta__lead"><?php esc_html_e( 'Оставьте телефон — менеджер посчитает количество, подскажет по совместимости материалов и привезёт под заказ то, чего нет на складе.', 'promix' ); ?></p>
                </div>
                <a class="btn btn--primary cta__btn" href="#contacts"
                   data-lead-open="<?php echo esc_attr( $lead_source ); ?>"
                   data-lead-title="<?php esc_attr_e( 'Оставить заявку', 'promix' ); ?>">
                    <?php esc_html_e( 'Оставить заявку', 'promix' ); ?>
                </a>
            </div>
        </div>
    </section>

    <?php
endwhile;

get_footer();
