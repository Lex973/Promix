<?php
/**
 * Карточка товара в каталоге.
 *
 * Главное — название: по нему и по артикулу ищет профессионал, цену
 * сверяет вторым шагом. Поэтому название стоит первым и тёмным, цена —
 * ниже, крупнее остального, но не громче названия. Бренд отдельно не
 * пишется — в прайсе он уже внутри названия. Кнопка «В корзину»
 * контурная: красной остаётся только кнопка «Применить» и шапка,
 * иначе красный в сетке из двадцати карточек перестаёт быть акцентом.
 *
 * Одна разметка на сетку и список: раскладку меняет класс на .products.
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
$sku   = $product->get_sku();
$price = (float) $product->get_price();
$cat   = promix_product_category( $product );
$url   = $product->get_permalink();

// Товары под своей маркой стоит отмечать: их больше нигде не купить.
$badge = ( 'PROMIX' === promix_product_brand( $product ) ) ? __( 'Своя марка', 'promix' ) : '';

?>
<article class="product" data-product data-id="<?php echo esc_attr( (string) $product->get_id() ); ?>">

    <?php if ( $badge ) : ?>
        <span class="product__badge"><?php echo esc_html( $badge ); ?></span>
    <?php endif; ?>

    <a class="product__media" href="<?php echo esc_url( $url ); ?>" aria-hidden="true" tabindex="-1">
        <?php if ( $product->get_image_id() ) : ?>
            <?php echo $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'product__img', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка картинки. ?>
        <?php else : ?>
            <?php echo promix_icon( promix_category_icon( $cat ), 1.2, 'product__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
        <?php endif; ?>
    </a>

    <div class="product__body">
        <h2 class="product__title">
            <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a>
        </h2>
    </div>

    <div class="product__foot">
        <p class="product__price">
            <?php echo esc_html( number_format_i18n( $price ) ); ?><span class="product__rub"> ₽</span>
        </p>

        <div class="product__actions">
            <button class="btn btn--outline product__buy" type="button"
                    data-add="<?php echo esc_attr( (string) $product->get_id() ); ?>"
                    aria-label="<?php echo esc_attr( sprintf( /* translators: %s — название товара. */ __( 'В корзину: %s', 'promix' ), $name ) ); ?>">
                <?php esc_html_e( 'В корзину', 'promix' ); ?>
            </button>

            <?php if ( $sku ) : ?>
                <button class="product__sku" type="button" data-copy="<?php echo esc_attr( $sku ); ?>"
                        aria-label="<?php echo esc_attr( sprintf( /* translators: %s — артикул. */ __( 'Скопировать артикул %s', 'promix' ), $sku ) ); ?>">
                    <span class="product__sku-label"><?php esc_html_e( 'Арт.', 'promix' ); ?></span>
                    <span data-copy-label><?php echo esc_html( $sku ); ?></span>
                    <?php echo promix_icon( 'copy', 1.7 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</article>
