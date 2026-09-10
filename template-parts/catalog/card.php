<?php
/**
 * Карточка товара в каталоге.
 *
 * Фотографий товаров пока нет: вместо них — иконка по категории.
 * Когда приедет WooCommerce, сюда встанет миниатюра, остальная
 * разметка не изменится.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$product = $args['product'] ?? array();

if ( ! $product ) {
    return;
}

$name  = (string) ( $product['name'] ?? '' );
$brand = (string) ( $product['brand'] ?? '' );
$sku   = (string) ( $product['sku'] ?? '' );
$price = (float) ( $product['price'] ?? 0 );
$cat   = (string) ( $product['cat'] ?? '' );

?>
<article class="product"
         data-product
         data-cat="<?php echo esc_attr( $cat ); ?>"
         data-brand="<?php echo esc_attr( $brand ); ?>"
         data-price="<?php echo esc_attr( (string) $price ); ?>"
         data-search="<?php echo esc_attr( mb_strtolower( $name . ' ' . $sku . ' ' . $brand ) ); ?>">

    <a class="product__media" href="#" aria-hidden="true" tabindex="-1">
        <?php echo promix_icon( promix_category_icon( $cat ), 1.2, 'product__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
    </a>

    <div class="product__body">
        <?php if ( $brand ) : ?>
            <p class="product__brand"><?php echo esc_html( $brand ); ?></p>
        <?php endif; ?>

        <h2 class="product__title">
            <a href="#"><?php echo esc_html( $name ); ?></a>
        </h2>

        <p class="product__sku">
            <?php
            printf(
                /* translators: %s — артикул товара. */
                esc_html__( 'Артикул: %s', 'promix' ),
                esc_html( $sku )
            );
            ?>
        </p>

        <div class="product__foot">
            <p class="product__price">
                <?php echo esc_html( number_format_i18n( $price ) ); ?><span class="product__rub"> ₽</span>
            </p>

            <button class="btn btn--primary product__buy" type="button"
                    aria-label="<?php echo esc_attr( sprintf( /* translators: %s — название товара. */ __( 'В корзину: %s', 'promix' ), $name ) ); ?>">
                <?php echo promix_icon( 'cart', 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                <span><?php esc_html_e( 'В корзину', 'promix' ); ?></span>
            </button>
        </div>
    </div>
</article>
