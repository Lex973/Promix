<?php
/**
 * Карточка товара в каталоге.
 *
 * Порядок как в привычных магазинах инструмента: сверху бейдж и избранное,
 * потом фото, дальше цена, название, артикул и кнопка. Цена стоит выше
 * названия намеренно — по ней в списке ведут глазами.
 *
 * Фотографий у большинства товаров нет: вместо них — иконка по разделу.
 * Когда фото загрузят в админке, на её место встанет миниатюра.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$product = $args['product'] ?? null;

if ( ! $product instanceof WC_Product ) {
    return;
}

$name  = $product->get_name();
$brand = promix_product_brand( $product );
$sku   = $product->get_sku();
$price = (float) $product->get_price();
$cat   = promix_product_category( $product );
$url   = $product->get_permalink();

// Товары под своей маркой стоит отмечать: их больше нигде не купить.
$badge = ( 'PROMIX' === $brand ) ? __( 'Своя марка', 'promix' ) : '';

// Наличие приедет из 1С вместе с остатками; пока строка не выводится.
$stock = '';

?>
<article class="product" data-product data-id="<?php echo esc_attr( (string) $product->get_id() ); ?>">

    <div class="product__top">
        <?php if ( $badge ) : ?>
            <span class="product__badge"><?php echo esc_html( $badge ); ?></span>
        <?php endif; ?>

        <button class="product__fav" type="button" data-fav="<?php echo esc_attr( $sku ); ?>"
                aria-pressed="false"
                aria-label="<?php echo esc_attr( sprintf( /* translators: %s — название товара. */ __( 'Отложить: %s', 'promix' ), $name ) ); ?>">
            <?php echo promix_icon( 'heart', 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
        </button>
    </div>

    <a class="product__media" href="<?php echo esc_url( $url ); ?>" aria-hidden="true" tabindex="-1">
        <?php if ( $product->get_image_id() ) : ?>
            <?php echo $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'product__img', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка картинки. ?>
        <?php else : ?>
            <?php echo promix_icon( promix_category_icon( $cat ), 1.2, 'product__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
        <?php endif; ?>
    </a>

    <div class="product__body">
        <p class="product__price">
            <?php echo esc_html( number_format_i18n( $price ) ); ?><span class="product__rub"> ₽</span>
        </p>

        <h2 class="product__title">
            <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a>
        </h2>

        <div class="product__foot">
            <div class="product__meta">
                <?php if ( $brand ) : ?>
                    <span class="product__brand"><?php echo esc_html( $brand ); ?></span>
                <?php endif; ?>

                <?php if ( $sku ) : ?>
                    <button class="product__sku" type="button" data-copy="<?php echo esc_attr( $sku ); ?>"
                            aria-label="<?php echo esc_attr( sprintf( /* translators: %s — артикул. */ __( 'Скопировать артикул %s', 'promix' ), $sku ) ); ?>">
                        <span><?php echo esc_html( $sku ); ?></span>
                        <?php echo promix_icon( 'copy', 1.7 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if ( $stock ) : ?>
                <p class="product__stock"><?php echo esc_html( $stock ); ?></p>
            <?php endif; ?>

            <button class="btn btn--primary product__buy" type="button"
                    data-add="<?php echo esc_attr( (string) $product->get_id() ); ?>"
                    aria-label="<?php echo esc_attr( sprintf( /* translators: %s — название товара. */ __( 'В корзину: %s', 'promix' ), $name ) ); ?>">
                <?php esc_html_e( 'В корзину', 'promix' ); ?>
            </button>
        </div>
    </div>
</article>
