<?php
/**
 * Содержимое корзины: список позиций и итог.
 *
 * Выводится и на странице корзины, и в ответ на изменение количества —
 * поэтому здесь всё, что должно перерисоваться. Форма отправляется
 * штатному обработчику Woo (update_cart), если скрипт не загрузился.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$cart = WC()->cart;

if ( ! $cart || $cart->is_empty() ) :
    ?>
    <div class="cart__empty">
        <?php echo promix_icon( 'cart', 1.2, 'cart__empty-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
        <p class="cart__empty-title"><?php esc_html_e( 'В корзине пока пусто', 'promix' ); ?></p>
        <p class="cart__empty-text"><?php esc_html_e( 'Добавьте товары из каталога — и оформим заказ за пару минут.', 'promix' ); ?></p>
        <a class="btn btn--primary cart__empty-btn" href="<?php echo esc_url( promix_catalog_url() ); ?>"><?php esc_html_e( 'В каталог', 'promix' ); ?></a>
    </div>
    <?php
    return;
endif;

$count = (int) $cart->get_cart_contents_count();
?>
<form class="cart__form" method="post" action="<?php echo esc_url( wc_get_cart_url() ); ?>" data-cart-form>
    <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>

    <div class="cart__layout">

        <ul class="cart__list">
            <?php foreach ( $cart->get_cart() as $key => $item ) : ?>
                <?php
                $product = $item['data'];

                if ( ! $product instanceof WC_Product ) {
                    continue;
                }

                $qty  = (int) $item['quantity'];
                $sku  = $product->get_sku();
                $url  = $product->get_permalink();
                $cat  = promix_product_category( $product );
                $line = (float) $item['line_subtotal'];
                ?>
                <li class="cart-item" data-cart-item="<?php echo esc_attr( $key ); ?>">
                    <a class="cart-item__media" href="<?php echo esc_url( $url ); ?>" aria-hidden="true" tabindex="-1">
                        <?php if ( $product->get_image_id() ) : ?>
                            <?php echo $product->get_image( 'woocommerce_gallery_thumbnail', array( 'class' => 'cart-item__img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка картинки. ?>
                        <?php else : ?>
                            <?php echo promix_icon( promix_category_icon( $cat ), 1.3, 'cart-item__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                        <?php endif; ?>
                    </a>

                    <div class="cart-item__info">
                        <a class="cart-item__title" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
                        <p class="cart-item__meta">
                            <?php if ( $sku ) : ?>
                                <span><?php echo esc_html( sprintf( /* translators: %s — артикул. */ __( 'Арт. %s', 'promix' ), $sku ) ); ?></span>
                            <?php endif; ?>
                            <span><?php echo esc_html( promix_price( (float) $product->get_price() ) ); ?> / <?php esc_html_e( 'шт', 'promix' ); ?></span>
                        </p>
                    </div>

                    <div class="qty cart-item__qty" data-qty>
                        <button class="qty__btn" type="button" data-qty-minus aria-label="<?php esc_attr_e( 'Меньше', 'promix' ); ?>">
                            <?php echo promix_icon( 'minus', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                        </button>
                        <label class="visually-hidden" for="qty-<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'Количество', 'promix' ); ?></label>
                        <input class="qty__input" id="qty-<?php echo esc_attr( $key ); ?>" type="text" inputmode="numeric" autocomplete="off"
                               name="cart[<?php echo esc_attr( $key ); ?>][qty]" value="<?php echo esc_attr( (string) $qty ); ?>" data-qty-input>
                        <button class="qty__btn" type="button" data-qty-plus aria-label="<?php esc_attr_e( 'Больше', 'promix' ); ?>">
                            <?php echo promix_icon( 'plus', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                        </button>
                    </div>

                    <p class="cart-item__total"><?php echo esc_html( promix_price( $line ) ); ?></p>

                    <a class="cart-item__remove" href="<?php echo esc_url( wc_get_cart_remove_url( $key ) ); ?>" data-cart-remove
                       aria-label="<?php echo esc_attr( sprintf( /* translators: %s — название товара. */ __( 'Убрать: %s', 'promix' ), $product->get_name() ) ); ?>">
                        <?php echo promix_icon( 'x', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <aside class="cart__summary">
            <p class="cart__summary-title"><?php esc_html_e( 'Ваш заказ', 'promix' ); ?></p>

            <dl class="cart__rows">
                <div class="cart__row">
                    <dt><?php esc_html_e( 'Товаров', 'promix' ); ?></dt>
                    <dd><?php echo esc_html( number_format_i18n( $count ) ); ?></dd>
                </div>
                <div class="cart__row cart__row--total">
                    <dt><?php esc_html_e( 'Итого', 'promix' ); ?></dt>
                    <dd><?php echo esc_html( promix_price( (float) $cart->get_subtotal() ) ); ?></dd>
                </div>
            </dl>

            <p class="cart__note"><?php esc_html_e( 'Цены с НДС. Доставку и наличие подтвердит менеджер — позвонит после оформления.', 'promix' ); ?></p>

            <a class="btn btn--primary cart__checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Оформить заказ', 'promix' ); ?></a>

            <button class="btn btn--outline cart__update" type="submit" name="update_cart" value="1" data-cart-update>
                <?php esc_html_e( 'Пересчитать', 'promix' ); ?>
            </button>

            <a class="cart__continue" href="<?php echo esc_url( promix_catalog_url() ); ?>"><?php esc_html_e( 'Продолжить покупки', 'promix' ); ?></a>
        </aside>

    </div>
</form>
