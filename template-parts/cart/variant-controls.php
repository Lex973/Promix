<?php
/**
 * Кнопка «В корзину» товара с вариантами.
 *
 * Пока вариант не выбран, видна общая кнопка: это форма, к которой
 * привязаны радиокнопки вариантов (template-parts/cart/variants.php).
 * Без скрипта она отправляет выбранный вариант штатному add-to-cart Woo.
 * Со скриптом нажатие без выбора показывает подсказку.
 *
 * У каждого варианта своя кнопка или счётчик — обычный
 * template-parts/cart/control.php, спрятанный, пока вариант не выбран.
 * Так счётчик всегда относится к выбранному варианту, а cart.js работает
 * с ними так же, как с простыми товарами.
 *
 * Аргументы: product (WC_Product), variations (WC_Product_Variation[]),
 * variant — card или single.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$product    = $args['product'] ?? null;
$variations = $args['variations'] ?? array();
$variant    = ( $args['variant'] ?? 'card' ) === 'single' ? 'single' : 'card';

if ( ! $product instanceof WC_Product || ! $variations ) {
    return;
}

$form = 'promix-buy-' . $product->get_id() . '-' . $variant;
?>
<div class="cart-control cart-control--<?php echo esc_attr( $variant ); ?>" data-variants-none>
    <form class="variants__form<?php echo 'single' === $variant ? ' single__buy' : ''; ?>" id="<?php echo esc_attr( $form ); ?>" method="post" action="<?php echo esc_url( $product->get_permalink() ); ?>">
        <?php if ( 'single' === $variant ) : ?>
            <button class="btn btn--primary single__add" type="submit" data-variants-pick>
                <?php promix_the_icon( 'cart', 2 ); ?>
                <?php esc_html_e( 'В корзину', 'promix' ); ?>
            </button>
        <?php else : ?>
            <button class="btn btn--outline product__buy" type="submit" data-variants-pick
                    aria-label="<?php echo esc_attr( sprintf( /* translators: %s — название товара. */ __( 'В корзину: %s', 'promix' ), $product->get_name() ) ); ?>">
                <?php esc_html_e( 'В корзину', 'promix' ); ?>
            </button>
        <?php endif; ?>
    </form>
</div>
<?php
foreach ( $variations as $variation ) {
    get_template_part(
        'template-parts/cart/control',
        null,
        array(
            'product' => $variation,
            'variant' => $variant,
            'hidden'  => true,
        )
    );
}
