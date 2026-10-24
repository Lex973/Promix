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
$cat   = promix_product_category( $product );
$url   = $product->get_permalink();

// С вариантами: цена первого, пока вариант не выбран; артикул — выбранного.
$variations = promix_variations( $product );
$shown      = $variations ? $variations[0] : $product;

// Товары под своей маркой стоит отмечать: их больше нигде не купить.
$badge = ( 'PROMIX' === promix_product_brand( $product ) ) ? __( 'Своя марка', 'promix' ) : '';

?>
<article class="product<?php echo $variations ? ' product--variants' : ''; ?>" data-product data-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"<?php echo $variations ? ' data-variants-scope' : ''; ?>>

    <?php if ( $badge ) : ?>
        <span class="product__badge"><?php echo esc_html( $badge ); ?></span>
    <?php endif; ?>

    <a class="product__media" href="<?php echo esc_url( $url ); ?>" aria-hidden="true" tabindex="-1">
        <?php if ( $product->get_image_id() ) : ?>
            <?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'product__img', 'loading' => 'lazy' ) ) ); ?>
        <?php else : ?>
            <?php promix_the_icon( promix_category_icon( $cat ), 1.2, 'product__icon' ); ?>
        <?php endif; ?>
    </a>

    <div class="product__body">
        <h2 class="product__title">
            <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a>
        </h2>
    </div>

    <div class="product__foot">
        <p class="product__price" data-variants-price><?php echo esc_html( promix_product_price( $shown ) ); ?></p>

        <?php if ( $variations ) : ?>
            <?php get_template_part( 'template-parts/cart/variants', null, array( 'product' => $product, 'variations' => $variations ) ); ?>
        <?php endif; ?>

        <div class="product__actions">
            <?php if ( $variations ) : ?>
                <?php get_template_part( 'template-parts/cart/variant-controls', null, array( 'product' => $product, 'variations' => $variations ) ); ?>
            <?php else : ?>
                <?php get_template_part( 'template-parts/cart/control', null, array( 'product' => $product ) ); ?>
            <?php endif; ?>

            <?php /* У товара с вариантами артикул появляется после выбора — кнопка нужна и пустой. */ ?>
            <?php if ( $sku || $variations ) : ?>
                <button class="product__sku" type="button" data-copy="<?php echo esc_attr( $sku ); ?>" title="<?php echo esc_attr( $sku ); ?>"<?php echo $sku ? '' : ' hidden'; ?> data-variants-sku
                        data-copy-done="<?php esc_attr_e( 'скопирован', 'promix' ); ?>"
                        aria-label="<?php echo esc_attr( sprintf( /* translators: %s — артикул. */ __( 'Скопировать артикул %s', 'promix' ), $sku ) ); ?>">
                    <span class="product__sku-label"><?php esc_html_e( 'Арт.', 'promix' ); ?></span>
                    <span data-copy-label><?php echo esc_html( $sku ); ?></span>
                    <?php promix_the_icon( 'copy', 1.7 ); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</article>
