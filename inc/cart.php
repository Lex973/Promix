<?php
/**
 * Корзина и оформление заказа.
 *
 * Добавление в корзину идёт через штатный wc-ajax=add_to_cart, сюда
 * добавляются только свои фрагменты ответа (число товаров для шапки).
 * Изменение количества на странице корзины — свой wc-ajax-обработчик,
 * который отдаёт перерисованный список. Оформление — обычная форма
 * без оплаты: заказ создаётся в Woo, менеджер получает письмо, а хук
 * promix_order_created ждёт отправку в MAX.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Сколько единиц товара в корзине — для счётчика в шапке.
 */
function promix_cart_count(): int {
    return function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

/**
 * Подпись к иконке корзины для программ чтения с экрана.
 */
function promix_cart_label(): string {
    $count = promix_cart_count();

    if ( ! $count ) {
        return __( 'Корзина: товаров нет', 'promix' );
    }

    return sprintf(
        /* translators: 1 — количество, 2 — слово «товар» в нужной форме. */
        __( 'Корзина: %1$s %2$s', 'promix' ),
        number_format_i18n( $count ),
        promix_plural( $count, 'товар', 'товара', 'товаров' )
    );
}

/**
 * Свои поля в ответе add_to_cart: скрипту нужны число и сумма, а не HTML Woo.
 *
 * @param array<string, string> $fragments Фрагменты Woo.
 * @return array<string, mixed>
 */
function promix_cart_fragments( array $fragments ): array {
    $fragments['promix_count'] = promix_cart_count();
    $fragments['promix_label'] = promix_cart_label();
    $fragments['promix_total'] = wp_strip_all_tags( WC()->cart->get_cart_total() );

    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'promix_cart_fragments' );

/**
 * Данные для скрипта корзины.
 */
function promix_cart_script(): void {
    if ( ! function_exists( 'WC' ) ) {
        return;
    }

    wp_localize_script(
        'promix-cart',
        'PROMIX_CART',
        array(
            'endpoint' => WC_AJAX::get_endpoint( '%%endpoint%%' ),
            'nonce'    => wp_create_nonce( 'promix-cart' ),
            'cartUrl'  => wc_get_cart_url(),
            'added'    => __( 'Добавлено', 'promix' ),
            'toast'    => __( 'Товар в корзине', 'promix' ),
            'open'     => __( 'Перейти в корзину', 'promix' ),
            'error'    => __( 'Не получилось добавить. Попробуйте ещё раз.', 'promix' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'promix_cart_script', 20 );

/**
 * Изменение количества и удаление со страницы корзины.
 *
 * Отвечает перерисованным списком: считать суммы на клиенте незачем,
 * когда сервер всё равно их знает.
 */
function promix_cart_ajax(): void {
    check_ajax_referer( 'promix-cart', 'nonce' );

    $key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
    $qty = isset( $_POST['qty'] ) ? absint( $_POST['qty'] ) : 0;
    $do  = isset( $_POST['do'] ) ? sanitize_key( $_POST['do'] ) : 'update';

    if ( $key && WC()->cart->get_cart_item( $key ) ) {
        if ( 'remove' === $do || 0 === $qty ) {
            WC()->cart->remove_cart_item( $key );
        } else {
            WC()->cart->set_quantity( $key, $qty );
        }
    }

    WC()->cart->calculate_totals();

    ob_start();
    get_template_part( 'template-parts/cart/items' );
    $html = ob_get_clean();

    wp_send_json_success(
        array(
            'html'  => $html,
            'count' => promix_cart_count(),
            'label' => promix_cart_label(),
        )
    );
}
add_action( 'wc_ajax_promix_cart', 'promix_cart_ajax' );

/**
 * Поля формы оформления и их значения из отправки.
 *
 * @return array<string, string>
 */
function promix_checkout_values(): array {
    /*
     * Поле имени называется client, а не name: name — публичная переменная
     * запроса WordPress, и POST с ней превращает страницу в 404.
     */
    $values = array(
        'client'   => '',
        'phone'    => '',
        'email'    => '',
        'company'  => '',
        'delivery' => 'pickup',
        'address'  => '',
        'comment'  => '',
    );

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- значения только для повторного показа формы.
    foreach ( $values as $field => $default ) {
        if ( isset( $_POST[ $field ] ) ) {
            $raw = (string) wp_unslash( $_POST[ $field ] );

            $values[ $field ] = in_array( $field, array( 'address', 'comment' ), true )
                ? sanitize_textarea_field( $raw )
                : sanitize_text_field( $raw );
        }
    }
    // phpcs:enable

    $values['delivery'] = 'delivery' === $values['delivery'] ? 'delivery' : 'pickup';

    return $values;
}

/**
 * Ошибки последней попытки оформить заказ.
 *
 * @param string[]|null $set Записать список ошибок.
 * @return string[]
 */
function promix_checkout_errors( ?array $set = null ): array {
    static $errors = array();

    if ( null !== $set ) {
        $errors = $set;
    }

    return $errors;
}

/**
 * Приём заказа.
 *
 * Работает до вывода страницы: при успехе уходит на страницу «Заказ принят»,
 * при ошибке оставляет форму с введёнными данными и списком, что не так.
 */
function promix_checkout_submit(): void {
    if ( ! function_exists( 'WC' ) || ! is_page( wc_get_page_id( 'checkout' ) ) || ! isset( $_POST['promix_checkout'] ) ) {
        return;
    }

    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'promix-checkout' ) ) {
        promix_checkout_errors( array( __( 'Форма устарела. Обновите страницу и попробуйте ещё раз.', 'promix' ) ) );
        return;
    }

    if ( WC()->cart->is_empty() ) {
        promix_checkout_errors( array( __( 'Корзина пуста.', 'promix' ) ) );
        return;
    }

    $v = promix_checkout_values();

    // Ловушка для ботов и слишком быстрая отправка — как в форме заявки.
    $trap   = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';
    $opened = isset( $_POST['opened'] ) ? (int) $_POST['opened'] : 0;

    if ( '' !== $trap || ( $opened > 0 && ( time() - $opened ) < 3 ) ) {
        WC()->cart->empty_cart();
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }

    $errors = array();
    $digits = preg_replace( '/\D/', '', $v['phone'] );

    if ( '' === $v['client'] ) {
        $errors[] = __( 'Напишите, как к вам обращаться.', 'promix' );
    }

    if ( strlen( (string) $digits ) < 10 ) {
        $errors[] = __( 'Проверьте номер телефона.', 'promix' );
    }

    if ( '' !== $v['email'] && ! is_email( $v['email'] ) ) {
        $errors[] = __( 'Проверьте адрес почты.', 'promix' );
    }

    if ( 'delivery' === $v['delivery'] && '' === $v['address'] ) {
        $errors[] = __( 'Для доставки нужен адрес.', 'promix' );
    }

    if ( empty( $_POST['agree'] ) ) {
        $errors[] = __( 'Нужно согласие на обработку данных.', 'promix' );
    }

    if ( $errors ) {
        promix_checkout_errors( $errors );
        return;
    }

    $order = wc_create_order(
        array(
            'created_via' => 'promix',
            'customer_id' => get_current_user_id(),
        )
    );

    if ( is_wp_error( $order ) ) {
        promix_checkout_errors( array( __( 'Не получилось сохранить заказ. Позвоните нам, пожалуйста.', 'promix' ) ) );
        return;
    }

    foreach ( WC()->cart->get_cart() as $item ) {
        $order->add_product( $item['data'], (int) $item['quantity'] );
    }

    $order->set_address(
        array(
            'first_name' => mb_substr( $v['client'], 0, 100 ),
            'company'    => mb_substr( $v['company'], 0, 100 ),
            'phone'      => mb_substr( $v['phone'], 0, 30 ),
            'email'      => mb_substr( $v['email'], 0, 100 ),
            'country'    => 'RU',
        ),
        'billing'
    );

    if ( 'delivery' === $v['delivery'] ) {
        $order->set_address(
            array(
                'first_name' => mb_substr( $v['client'], 0, 100 ),
                'address_1'  => mb_substr( $v['address'], 0, 200 ),
                'country'    => 'RU',
            ),
            'shipping'
        );
    }

    $order->set_customer_note( mb_substr( $v['comment'], 0, 2000 ) );
    $order->update_meta_data( '_promix_delivery', $v['delivery'] );
    $order->set_customer_ip_address( WC_Geolocation::get_ip_address() );
    $order->set_customer_user_agent( wc_get_user_agent() );
    $order->calculate_totals( false );
    $order->update_status( 'processing', __( 'Заказ с сайта: ждёт звонка менеджера.', 'promix' ) );

    /**
     * Заказ создан — сюда встанет отправка менеджеру в MAX.
     *
     * @param WC_Order $order Заказ.
     */
    do_action( 'promix_order_created', $order );

    WC()->cart->empty_cart();

    wp_safe_redirect( $order->get_checkout_order_received_url() );
    exit;
}
add_action( 'template_redirect', 'promix_checkout_submit', 5 );

/**
 * Способ получения словами — для списка заказов и письма.
 *
 * @param WC_Order $order Заказ.
 */
function promix_order_delivery( WC_Order $order ): string {
    return 'delivery' === $order->get_meta( '_promix_delivery' )
        ? __( 'Доставка', 'promix' )
        : __( 'Самовывоз', 'promix' );
}

/**
 * Способ получения и адрес — в письме менеджеру и на странице заказа в админке.
 *
 * @param WC_Order $order Заказ.
 */
function promix_order_delivery_details( WC_Order $order ): void {
    echo '<p><strong>' . esc_html__( 'Получение:', 'promix' ) . '</strong> ' . esc_html( promix_order_delivery( $order ) );

    if ( $order->get_shipping_address_1() ) {
        echo ' — ' . esc_html( $order->get_shipping_address_1() );
    }

    echo '</p>';
}
add_action( 'woocommerce_email_after_order_table', 'promix_order_delivery_details' );
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'promix_order_delivery_details' );

/**
 * Заголовок вкладки на странице «Заказ принят».
 *
 * @param array<string, string> $parts Части заголовка.
 * @return array<string, string>
 */
function promix_checkout_title( array $parts ): array {
    if ( function_exists( 'is_checkout' ) && is_checkout() && get_query_var( 'order-received' ) ) {
        $parts['title'] = __( 'Заказ принят', 'promix' );
    }

    return $parts;
}
add_filter( 'document_title_parts', 'promix_checkout_title' );
