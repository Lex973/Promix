<?php
/**
 * Кнопка «В корзину» или счётчик количества — смотря есть ли товар в корзине.
 *
 * Товар добавляется один раз, дальше меняется количество: «−», число, «+».
 * Ноль убирает из корзины и возвращает кнопку. Тот же кусок разметки
 * отдаёт сервер после каждого изменения (см. inc/cart.php), поэтому
 * скрипт ничего не рисует сам — только подменяет.
 *
 * Аргументы: product (WC_Product), variant — card (в каталоге) или single
 * (страница товара: крупнее), hidden — спрятать (кнопки вариантов, кроме
 * выбранного, см. template-parts/cart/variants.php). Количество выбирают
 * уже после добавления.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$product = $args['product'] ?? null;
$variant = ( $args['variant'] ?? 'card' ) === 'single' ? 'single' : 'card';
$hidden  = ! empty( $args['hidden'] );

if ( ! $product instanceof WC_Product ) {
    return;
}

$id    = $product->get_id();
$name  = promix_cart_item_name( $product );
$price = (float) $product->get_price();
$item  = promix_cart_item( $id );
$url   = $product->get_permalink();
?>
<div class="cart-control cart-control--<?php echo esc_attr( $variant ); ?>" data-cart-control="<?php echo esc_attr( (string) $id ); ?>" data-variant="<?php echo esc_attr( $variant ); ?>"<?php echo $hidden ? ' hidden' : ''; ?>>
    <?php if ( $price <= 0 ) : ?>

        <?php
        // Цену называет менеджер: та же форма заявки, что у «Спросить у технолога», с товаром и артикулом.
        $sku    = $product->get_sku();
        $source = $sku
            ? sprintf( /* translators: 1 — название, 2 — артикул. */ __( 'Цена: %1$s (арт. %2$s)', 'promix' ), $name, $sku )
            : sprintf( /* translators: %s — название. */ __( 'Цена: %s', 'promix' ), $name );
        ?>
        <a class="btn btn--outline <?php echo 'single' === $variant ? 'single__ask' : 'product__buy'; ?>" href="<?php echo esc_url( $url ); ?>"
           data-lead-open="<?php echo esc_attr( $source ); ?>"
           data-lead-title="<?php esc_attr_e( 'Уточним цену', 'promix' ); ?>"><?php esc_html_e( 'Уточнить', 'promix' ); ?></a>

    <?php elseif ( $item ) : ?>

        <div class="qty qty--cart" data-cart-key="<?php echo esc_attr( $item['key'] ); ?>" data-product="<?php echo esc_attr( (string) $id ); ?>">
            <?php /* На единице «минус» убирает товар из корзины — иконка при этом та же. */ ?>
            <button class="qty__btn" type="button" data-cart-step="-1"
                    aria-label="<?php echo esc_attr( 1 === $item['qty'] ? __( 'Убрать из корзины', 'promix' ) : __( 'Меньше', 'promix' ) ); ?>">
                <?php promix_the_icon( 'minus', 2 ); ?>
            </button>
            <label class="visually-hidden" for="cart-qty-<?php echo esc_attr( (string) $id ); ?>"><?php esc_html_e( 'Количество в корзине', 'promix' ); ?></label>
            <input class="qty__input" id="cart-qty-<?php echo esc_attr( (string) $id ); ?>" type="text" inputmode="numeric" autocomplete="off"
                   value="<?php echo esc_attr( (string) $item['qty'] ); ?>" data-cart-qty>
            <button class="qty__btn" type="button" data-cart-step="1" aria-label="<?php esc_attr_e( 'Больше', 'promix' ); ?>">
                <?php promix_the_icon( 'plus', 2 ); ?>
            </button>
        </div>

    <?php elseif ( 'single' === $variant ) : ?>

        <?php /* Форма, а не ссылка: без скрипта её обработает Woo сам (add-to-cart), со скриптом перехватит cart.js. */ ?>
        <form class="single__buy" method="post" action="<?php echo esc_url( $url ); ?>" data-add-form>
            <button class="btn btn--primary single__add" type="submit" name="add-to-cart" value="<?php echo esc_attr( (string) $id ); ?>" data-add="<?php echo esc_attr( (string) $id ); ?>">
                <?php promix_the_icon( 'cart', 2 ); ?>
                <?php esc_html_e( 'В корзину', 'promix' ); ?>
            </button>
        </form>

    <?php else : ?>

        <?php /* Ссылка, а не кнопка: без скрипта её обработает Woo сам (add-to-cart), со скриптом перехватит cart.js. */ ?>
        <a class="btn btn--outline product__buy" href="<?php echo esc_url( add_query_arg( 'add-to-cart', $id, $url ) ); ?>"
           data-add="<?php echo esc_attr( (string) $id ); ?>" rel="nofollow"
           aria-label="<?php echo esc_attr( sprintf( /* translators: %s — название товара. */ __( 'В корзину: %s', 'promix' ), $name ) ); ?>">
            <?php esc_html_e( 'В корзину', 'promix' ); ?>
        </a>

    <?php endif; ?>
</div>
