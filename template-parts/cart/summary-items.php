<?php
/**
 * Позиции заказа в боковом итоге: название, количество, сумма.
 *
 * Общий для корзины и оформления. Длинные названия из 1С обрезаются
 * до двух строк (полное — в подсказке). В корзине список короткий:
 * позиции целиком и так видны слева.
 *
 * @package promix
 *
 * @var array $args {
 *     @type int $limit Сколько позиций показать; 0 — все.
 * }
 */

defined( 'ABSPATH' ) || exit;

$items = array_filter(
    WC()->cart->get_cart(),
    static fn ( array $item ): bool => $item['data'] instanceof WC_Product
);
$limit = (int) ( $args['limit'] ?? 0 );
$rest  = $limit ? count( $items ) - $limit : 0;
$items = $limit ? array_slice( $items, 0, $limit ) : $items;
?>
<ul class="checkout__items">
    <?php foreach ( $items as $item ) : ?>
        <?php $name = promix_cart_item_name( $item['data'] ); ?>
        <li class="checkout__item">
            <span class="checkout__item-name" title="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></span>
            <span class="checkout__item-qty">× <?php echo esc_html( (string) $item['quantity'] ); ?></span>
            <span class="checkout__item-sum"><?php echo esc_html( promix_product_price( $item['data'], (float) $item['line_subtotal'] ) ); ?></span>
        </li>
    <?php endforeach; ?>

    <?php if ( $rest > 0 ) : ?>
        <li class="checkout__item checkout__item--more">
            <?php
            printf(
                /* translators: 1 — число позиций, 2 — слово «позиция» в нужной форме. */
                esc_html__( 'и ещё %1$s %2$s', 'promix' ),
                esc_html( number_format_i18n( $rest ) ),
                esc_html( promix_plural( $rest, __( 'позиция', 'promix' ), __( 'позиции', 'promix' ), __( 'позиций', 'promix' ) ) )
            );
            ?>
        </li>
    <?php endif; ?>
</ul>
