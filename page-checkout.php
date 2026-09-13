<?php
/**
 * Оформление заказа и страница «Заказ принят».
 *
 * Оплаты на сайте нет: покупатель оставляет контакты, заказ уходит
 * менеджеру, тот перезванивает. Поэтому форма короткая — имя, телефон
 * и как забирать. Приём формы — в inc/cart.php (promix_checkout_submit).
 * Если WooCommerce увёл сюда с пустой корзиной, он сам вернёт в корзину.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

// Шаблон по слагу подхватится и без WooCommerce (плагин выключен на время
// неудачного обновления) — тогда это обычная страница, а не фатал.
if ( ! function_exists( 'WC' ) ) {
    get_template_part( 'page' );
    return;
}

get_header();

$received = absint( get_query_var( 'order-received' ) );
$order    = $received ? wc_get_order( $received ) : null;
$key      = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ключ заказа проверяется ниже.

if ( $order instanceof WC_Order && ! hash_equals( $order->get_order_key(), $key ) ) {
    $order = null;
}

$policy_url = get_privacy_policy_url();
$contacts   = function_exists( 'promix_contacts' ) ? promix_contacts() : array();
?>

<section class="section checkout">
    <div class="container">

        <?php if ( $received ) : ?>

            <?php if ( $order ) : ?>
                <div class="checkout__done">
                    <?php promix_the_icon( 'circle-check-big', 1.4, 'checkout__done-icon' ); ?>
                    <p class="kicker"><?php esc_html_e( 'Заказ принят', 'promix' ); ?></p>
                    <h1 class="section__title">
                        <?php
                        printf(
                            /* translators: %s — номер заказа. */
                            esc_html__( 'Заказ №%s у менеджера', 'promix' ),
                            esc_html( $order->get_order_number() )
                        );
                        ?>
                    </h1>
                    <p class="section__lead checkout__done-lead">
                        <?php
                        printf(
                            /* translators: %s — телефон покупателя. */
                            esc_html__( 'Перезвоним на %s в рабочее время, подтвердим наличие и договоримся о получении.', 'promix' ),
                            esc_html( $order->get_billing_phone() )
                        );
                        ?>
                    </p>

                    <ul class="checkout__done-list">
                        <?php foreach ( $order->get_items() as $item ) : ?>
                            <li>
                                <span><?php echo esc_html( $item->get_name() ); ?></span>
                                <span class="checkout__done-qty">× <?php echo esc_html( (string) $item->get_quantity() ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <p class="checkout__done-total">
                        <?php esc_html_e( 'Итого', 'promix' ); ?>
                        <strong><?php echo esc_html( promix_price( (float) $order->get_total() ) ); ?></strong>
                    </p>

                    <div class="checkout__done-actions">
                        <a class="btn btn--primary" href="<?php echo esc_url( promix_catalog_url() ); ?>"><?php esc_html_e( 'В каталог', 'promix' ); ?></a>
                        <a class="btn btn--outline" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'На главную', 'promix' ); ?></a>
                    </div>
                </div>
            <?php else : ?>
                <div class="checkout__done">
                    <h1 class="section__title"><?php esc_html_e( 'Заказ не найден', 'promix' ); ?></h1>
                    <p class="section__lead checkout__done-lead"><?php esc_html_e( 'Ссылка устарела или указана неверно. Если заказ оформляли вы — позвоните нам, найдём по телефону.', 'promix' ); ?></p>
                    <div class="checkout__done-actions">
                        <a class="btn btn--primary" href="<?php echo esc_url( promix_catalog_url() ); ?>"><?php esc_html_e( 'В каталог', 'promix' ); ?></a>
                    </div>
                </div>
            <?php endif; ?>

        <?php else : ?>

            <?php
            $v      = promix_checkout_values();
            $errors = promix_checkout_errors();
            $cart   = WC()->cart;
            ?>

            <?php
            get_template_part(
                'template-parts/crumbs',
                null,
                array(
                    'items' => array(
                        __( 'Корзина', 'promix' )    => wc_get_cart_url(),
                        __( 'Оформление', 'promix' ) => '',
                    ),
                )
            );
            ?>

            <h1 class="section__title cart__title"><?php esc_html_e( 'Оформление заказа', 'promix' ); ?></h1>

            <div class="checkout__layout">

                <form class="checkout__form lead" method="post" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" data-checkout-form>
                    <?php wp_nonce_field( 'promix-checkout' ); ?>
                    <input type="hidden" name="promix_checkout" value="1">

                    <?php get_template_part( 'template-parts/notices' ); ?>

                    <?php if ( $errors ) : ?>
                        <div class="checkout__errors" role="alert">
                            <ul>
                                <?php foreach ( $errors as $error ) : ?>
                                    <li><?php echo esc_html( $error ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <p class="checkout__group-title"><?php esc_html_e( 'Как с вами связаться', 'promix' ); ?></p>

                    <div class="checkout__grid">
                        <label class="lead__field">
                            <span class="lead__label"><?php esc_html_e( 'Как к вам обращаться', 'promix' ); ?></span>
                            <input class="lead__input" type="text" name="client" autocomplete="name" required value="<?php echo esc_attr( $v['client'] ); ?>">
                        </label>

                        <label class="lead__field">
                            <span class="lead__label"><?php esc_html_e( 'Телефон', 'promix' ); ?></span>
                            <input class="lead__input" type="tel" name="phone" autocomplete="tel" placeholder="+7 (___) ___-__-__" required value="<?php echo esc_attr( $v['phone'] ); ?>">
                        </label>

                        <label class="lead__field">
                            <span class="lead__label"><?php esc_html_e( 'Почта', 'promix' ); ?> <em><?php esc_html_e( 'необязательно', 'promix' ); ?></em></span>
                            <input class="lead__input" type="email" name="email" autocomplete="email" value="<?php echo esc_attr( $v['email'] ); ?>">
                        </label>

                        <label class="lead__field">
                            <span class="lead__label"><?php esc_html_e( 'Компания', 'promix' ); ?> <em><?php esc_html_e( 'если заказ на юрлицо', 'promix' ); ?></em></span>
                            <input class="lead__input" type="text" name="company" autocomplete="organization" value="<?php echo esc_attr( $v['company'] ); ?>">
                        </label>
                    </div>

                    <p class="checkout__group-title"><?php esc_html_e( 'Как забирать', 'promix' ); ?></p>

                    <div class="checkout__choice" data-delivery>
                        <label class="choice">
                            <input type="radio" name="delivery" value="pickup" <?php checked( 'pickup', $v['delivery'] ); ?>>
                            <span class="choice__body">
                                <span class="choice__title"><?php esc_html_e( 'Самовывоз', 'promix' ); ?></span>
                                <span class="choice__text">
                                    <?php echo esc_html( $contacts['address'] ?? __( 'Казань, из нашего магазина', 'promix' ) ); ?>
                                    <?php if ( ! empty( $contacts['hours'] ) ) : ?>
                                        · <?php echo esc_html( $contacts['hours'] ); ?>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </label>

                        <label class="choice">
                            <input type="radio" name="delivery" value="delivery" <?php checked( 'delivery', $v['delivery'] ); ?>>
                            <span class="choice__body">
                                <span class="choice__title"><?php esc_html_e( 'Доставка', 'promix' ); ?></span>
                                <span class="choice__text"><?php esc_html_e( 'По Казани и области. Стоимость и срок скажет менеджер при подтверждении.', 'promix' ); ?></span>
                            </span>
                        </label>
                    </div>

                    <label class="lead__field checkout__address" data-address <?php echo 'delivery' === $v['delivery'] ? '' : 'hidden'; ?>>
                        <span class="lead__label"><?php esc_html_e( 'Адрес доставки', 'promix' ); ?></span>
                        <textarea class="lead__input lead__input--area" name="address" rows="2" autocomplete="street-address"><?php echo esc_textarea( $v['address'] ); ?></textarea>
                    </label>

                    <label class="lead__field">
                        <span class="lead__label"><?php esc_html_e( 'Комментарий к заказу', 'promix' ); ?> <em><?php esc_html_e( 'необязательно', 'promix' ); ?></em></span>
                        <textarea class="lead__input lead__input--area" name="comment" rows="3"><?php echo esc_textarea( $v['comment'] ); ?></textarea>
                    </label>

                    <label class="lead__agree">
                        <input type="checkbox" name="agree" value="1" required>
                        <span>
                            <?php esc_html_e( 'Согласен на обработку персональных данных', 'promix' ); ?>
                            <?php if ( $policy_url ) : ?>
                                — <a href="<?php echo esc_url( $policy_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'политика', 'promix' ); ?></a>
                            <?php endif; ?>
                        </span>
                    </label>

                    <?php /* Поле-ловушка: спрятано от людей, боты его заполняют. */ ?>
                    <div class="lead__trap" aria-hidden="true">
                        <label>
                            <?php esc_html_e( 'Сайт', 'promix' ); ?>
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                        </label>
                    </div>

                    <button class="btn btn--primary checkout__submit" type="submit" data-checkout-submit><?php esc_html_e( 'Отправить заказ', 'promix' ); ?></button>
                    <p class="lead__note checkout__note"><?php esc_html_e( 'Без оплаты на сайте: менеджер перезвонит, подтвердит наличие и выставит счёт или примет оплату при получении.', 'promix' ); ?></p>
                </form>

                <aside class="checkout__summary">
                    <p class="cart__summary-title"><?php esc_html_e( 'Ваш заказ', 'promix' ); ?></p>

                    <ul class="checkout__items">
                        <?php foreach ( $cart->get_cart() as $item ) : ?>
                            <?php $product = $item['data']; ?>
                            <?php if ( $product instanceof WC_Product ) : ?>
                                <li class="checkout__item">
                                    <span class="checkout__item-name"><?php echo esc_html( $product->get_name() ); ?></span>
                                    <span class="checkout__item-qty">× <?php echo esc_html( (string) $item['quantity'] ); ?></span>
                                    <span class="checkout__item-sum"><?php echo esc_html( promix_price( (float) $item['line_subtotal'] ) ); ?></span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>

                    <dl class="cart__rows">
                        <div class="cart__row cart__row--total">
                            <dt><?php esc_html_e( 'Итого', 'promix' ); ?></dt>
                            <dd><?php echo esc_html( promix_price( (float) $cart->get_subtotal() ) ); ?></dd>
                        </div>
                    </dl>

                    <a class="cart__continue" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Изменить корзину', 'promix' ); ?></a>
                </aside>

            </div>

        <?php endif; ?>

    </div>
</section>

<?php
get_footer();
