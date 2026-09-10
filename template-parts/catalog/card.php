<?php
/**
 * Карточка товара в каталоге.
 *
 * Порядок как в привычных магазинах инструмента: сверху бейдж и избранное,
 * потом фото, дальше цена, название, артикул и кнопка. Цена стоит выше
 * названия намеренно — по ней в списке ведут глазами.
 *
 * Фотографий товаров пока нет: вместо них — иконка по категории. Когда
 * приедет WooCommerce, на её место встанет миниатюра.
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

// Товары под своей маркой стоит отмечать: их больше нигде не купить.
$badge = ( 'PROMIX' === $brand ) ? __( 'Своя марка', 'promix' ) : '';

// Наличие приедет из 1С вместе с остатками; пока строка не выводится.
$stock = (string) ( $product['stock'] ?? '' );

?>
<article class="product"
         data-product
         data-cat="<?php echo esc_attr( $cat ); ?>"
         data-brand="<?php echo esc_attr( $brand ); ?>"
         data-price="<?php echo esc_attr( (string) $price ); ?>"
         data-search="<?php echo esc_attr( mb_strtolower( $name . ' ' . $sku . ' ' . $brand ) ); ?>">

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

    <a class="product__media" href="#" aria-hidden="true" tabindex="-1">
        <?php echo promix_icon( promix_category_icon( $cat ), 1.2, 'product__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
    </a>

    <div class="product__body">
        <p class="product__price">
            <?php echo esc_html( number_format_i18n( $price ) ); ?><span class="product__rub"> ₽</span>
        </p>

        <h2 class="product__title">
            <a href="#"><?php echo esc_html( $name ); ?></a>
        </h2>

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
                aria-label="<?php echo esc_attr( sprintf( /* translators: %s — название товара. */ __( 'В корзину: %s', 'promix' ), $name ) ); ?>">
            <?php esc_html_e( 'В корзину', 'promix' ); ?>
        </button>
    </div>
</article>
