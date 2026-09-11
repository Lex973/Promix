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
            'status'   => 'publish',
            'limit'    => 4,
            'category' => array( $category->slug ),
            'exclude'  => array( $product->get_id() ),
            'orderby'  => 'menu_order',
            'order'    => 'ASC',
        )
    ) : array();
    ?>

    <section class="section single">
        <div class="container">

            <nav class="crumbs" aria-label="<?php esc_attr_e( 'Вы здесь', 'promix' ); ?>">
                <a class="crumbs__link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Главная', 'promix' ); ?></a>
                <?php echo promix_icon( 'chevron-right', 2, 'crumbs__sep' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                <a class="crumbs__link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Каталог', 'promix' ); ?></a>
                <?php if ( $category && $cat_link ) : ?>
                    <?php echo promix_icon( 'chevron-right', 2, 'crumbs__sep' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                    <a class="crumbs__link" href="<?php echo esc_url( $cat_link ); ?>"><?php echo esc_html( $category->name ); ?></a>
                <?php endif; ?>
            </nav>

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

                    <p class="single__price">
                        <?php echo esc_html( number_format_i18n( $price ) ); ?><span class="single__rub"> ₽</span>
                    </p>

                    <form class="single__buy" method="post" action="<?php echo esc_url( $product->get_permalink() ); ?>" data-add-form>
                        <div class="qty" data-qty>
                            <button class="qty__btn" type="button" data-qty-minus aria-label="<?php esc_attr_e( 'Меньше', 'promix' ); ?>">−</button>
                            <label class="visually-hidden" for="quantity"><?php esc_html_e( 'Количество', 'promix' ); ?></label>
                            <input class="qty__input" id="quantity" type="text" inputmode="numeric" name="quantity" value="1" autocomplete="off" data-qty-input>
                            <button class="qty__btn" type="button" data-qty-plus aria-label="<?php esc_attr_e( 'Больше', 'promix' ); ?>">+</button>
                        </div>

                        <button class="btn btn--primary single__add" type="submit" name="add-to-cart" value="<?php echo esc_attr( (string) $product->get_id() ); ?>" data-add="<?php echo esc_attr( (string) $product->get_id() ); ?>">
                            <?php echo promix_icon( 'cart', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                            <?php esc_html_e( 'В корзину', 'promix' ); ?>
                        </button>
                    </form>

                    <p class="single__note"><?php esc_html_e( 'Наличие и срок поставки подтвердит менеджер после заказа.', 'promix' ); ?></p>

                    <a class="btn btn--outline single__ask" href="#contacts"
                       data-lead-open="<?php echo esc_attr( $lead_source ); ?>"
                       data-lead-title="<?php esc_attr_e( 'Спросим у технолога', 'promix' ); ?>">
                        <?php esc_html_e( 'Спросить у технолога', 'promix' ); ?>
                    </a>

                    <?php if ( $product->get_description() ) : ?>
                        <div class="single__desc doc">
                            <?php echo wp_kses_post( wpautop( $product->get_description() ) ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

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
