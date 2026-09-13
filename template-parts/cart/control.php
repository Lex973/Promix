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
 * (страница товара: крупнее). Количество выбирают уже после добавления.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$product = $args['product'] ?? null;
$variant = ( $args['variant'] ?? 'card' ) === 'single' ? 'single' : 'card';

if ( ! $product instanceof WC_Product ) {
    return;
}

$id    = $product->get_id();
$name  = $product->get_name();
$price = (float) $product->get_price();
$item  = promix_cart_item( $id );
$url   = $product->get_permalink();
?>
<div class="cart-control cart-control--<?php echo esc_attr( $variant ); ?>" data-cart-control="<?php echo esc_attr( (string) $id ); ?>" data-variant="<?php echo esc_attr( $variant ); ?>">
    <?php if ( $price <= 0 ) : ?>

        <a class="btn btn--outline product__buy" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Уточнить', 'promix' ); ?></a>

    <?php elseif ( $item ) : ?>

        <div class="qty qty--cart" data-cart-key="<?php echo esc_attr( $item['key'] ); ?>" data-product="<?php echo esc_attr( (string) $id ); ?>">
            <?php /* На единице «минус» становится крестиком: следующий шаг — убрать товар. */ ?>
            <button class="qty__btn" type="button" data-cart-step="-1"
                    aria-label="<?php echo esc_attr( 1 === $item['qty'] ? __( 'Убрать из корзины', 'promix' ) : __( 'Меньше', 'promix' ) ); ?>">
                <?php echo promix_icon( 1 === $item['qty'] ? 'x' : 'minus', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
            </button>
            <label class="visually-hidden" for="cart-qty-<?php echo esc_attr( (string) $id ); ?>"><?php esc_html_e( 'Количество в корзине', 'promix' ); ?></label>
            <input class="qty__input" id="cart-qty-<?php echo esc_attr( (string) $id ); ?>" type="text" inputmode="numeric" autocomplete="off"
                   value="<?php echo esc_attr( (string) $item['qty'] ); ?>" data-cart-qty>
            <button class="qty__btn" type="button" data-cart-step="1" aria-label="<?php esc_attr_e( 'Больше', 'promix' ); ?>">
                <?php echo promix_icon( 'plus', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
            </button>
        </div>

    <?php elseif ( 'single' === $variant ) : ?>

        <?php /* Форма, а не ссылка: без скрипта её обработает Woo сам (add-to-cart), со скриптом перехватит cart.js. */ ?>
        <form class="single__buy" method="post" action="<?php echo esc_url( $url ); ?>" data-add-form>
            <button class="btn btn--primary single__add" type="submit" name="add-to-cart" value="<?php echo esc_attr( (string) $id ); ?>" data-add="<?php echo esc_attr( (string) $id ); ?>">
                <?php echo promix_icon( 'cart', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
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
