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

// Больше — это уже не заказ с сайта, а разговор с менеджером.
const PROMIX_CART_MAX_QTY = 9999;

/**
 * Сколько единиц товара в корзине — для счётчика в шапке.
 */
function promix_cart_count(): int {
    return function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

/**
 * Строка корзины с этим товаром: ключ и количество, или null.
 *
 * @param int $product_id Товар.
 * @return array{key: string, qty: int}|null
 */
function promix_cart_item( int $product_id ): ?array {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return null;
    }

    foreach ( WC()->cart->get_cart() as $key => $item ) {
        if ( (int) $item['product_id'] === $product_id ) {
            return array(
                'key' => (string) $key,
                'qty' => (int) $item['quantity'],
            );
        }
    }

    return null;
}

/**
 * Кнопка «В корзину» или счётчик — разметкой, для ответа скрипту.
 *
 * @param int    $product_id Товар.
 * @param string $variant    card или single.
 * @return string
 */
function promix_cart_control( int $product_id, string $variant = 'card' ): string {
    $product = wc_get_product( $product_id );

    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    ob_start();
    get_template_part(
        'template-parts/cart/control',
        null,
        array(
            'product' => $product,
            'variant' => $variant,
        )
    );

    return (string) ob_get_clean();
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
    // HTML мини-корзины Woo скрипту не нужен — не гоняем его по сети.
    unset( $fragments['div.widget_shopping_cart_content'] );

    $fragments['promix_count'] = promix_cart_count();
    $fragments['promix_label'] = promix_cart_label();

    // Скрипт подменит кнопку «В корзину» счётчиком того товара, который только что добавили.
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- добавление в корзину у Woo без nonce намеренно.
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $variant    = isset( $_POST['promix_variant'] ) ? sanitize_key( $_POST['promix_variant'] ) : 'card';
    // phpcs:enable

    if ( $product_id ) {
        $fragments['promix_control'] = promix_cart_control( $product_id, $variant );
    }

    return $fragments;
}

/**
 * Шаблоны корзины и оформления — по назначению страницы, а не по слагу.
 *
 * page-cart.php нашёлся бы и сам через иерархию, но только пока адрес
 * страницы именно /cart/; переименуют в админке — тема молча отдаст
 * page.php с пустым содержимым.
 *
 * @param string $template Шаблон, выбранный WordPress.
 * @return string
 */
function promix_cart_templates( string $template ): string {
    if ( ! function_exists( 'is_cart' ) ) {
        return $template;
    }

    if ( is_cart() ) {
        return get_theme_file_path( 'page-cart.php' );
    }

    if ( is_checkout() ) {
        return get_theme_file_path( 'page-checkout.php' );
    }

    return $template;
}
add_filter( 'template_include', 'promix_cart_templates', 20 );
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

    $key        = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
    $qty        = isset( $_POST['qty'] ) ? min( absint( $_POST['qty'] ), PROMIX_CART_MAX_QTY ) : 0;
    $do         = isset( $_POST['do'] ) ? sanitize_key( $_POST['do'] ) : 'update';
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $variant    = isset( $_POST['promix_variant'] ) ? sanitize_key( $_POST['promix_variant'] ) : 'card';

    if ( $key && WC()->cart->get_cart_item( $key ) ) {
        if ( 'remove' === $do || 0 === $qty ) {
            WC()->cart->remove_cart_item( $key );
        } else {
            WC()->cart->set_quantity( $key, $qty );
        }
    }

    WC()->cart->calculate_totals();

    $data = array(
        'count' => promix_cart_count(),
        'label' => promix_cart_label(),
    );

    // Со страницы корзины — перерисованный список; из карточки — кнопка или счётчик этого товара.
    if ( $product_id ) {
        $data['control'] = promix_cart_control( $product_id, $variant );
    } else {
        ob_start();
        get_template_part( 'template-parts/cart/items' );
        $data['html'] = ob_get_clean();
    }

    wp_send_json_success( $data );
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

    /*
     * Ловушка для ботов: спрятанное поле заполняют только скрипты.
     * Корзину при этом не трогаем — человек с автозаполнением браузера
     * не должен терять подобранные товары. Таймера «быстрее трёх секунд»
     * здесь нет: на оформление приходят с уже заполненными полями.
     */
    $trap = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';

    if ( '' !== $trap ) {
        promix_checkout_errors( array( __( 'Не получилось отправить. Попробуйте ещё раз.', 'promix' ) ) );
        return;
    }

    // Тот же лимит, что у заявок, со своим счётчиком: три заказа за десять минут.
    if ( promix_lead_rate_limited( 'order' ) ) {
        promix_checkout_errors( array( __( 'Заказ уже у нас — менеджер перезвонит. Если нужно срочно, позвоните.', 'promix' ) ) );
        return;
    }

    $errors = array();
    $digits = preg_replace( '/\D/', '', $v['phone'] );

    // Длину режем до проверки: обрезанный после is_email() адрес Woo не примет.
    $v['client']  = mb_substr( $v['client'], 0, 100 );
    $v['company'] = mb_substr( $v['company'], 0, 100 );
    $v['phone']   = mb_substr( $v['phone'], 0, 30 );
    $v['email']   = mb_substr( $v['email'], 0, 100 );
    $v['address'] = mb_substr( $v['address'], 0, 200 );
    $v['comment'] = mb_substr( $v['comment'], 0, 2000 );

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

    /*
     * Защита от двойной отправки. Два клика или два одновременных запроса
     * с одной сессией создавали два заказа: транзиент здесь не помогает,
     * оба запроса успевают прочитать «свободно». Поэтому замок в MySQL
     * (GET_LOCK — атомарный, второй запрос ждёт первого), а после него —
     * поиск заказа с той же корзиной за последние две минуты: нашёлся —
     * это тот же заказ, отправляем на его страницу, а не создаём новый.
     */
    global $wpdb;

    $session   = (string) WC()->session->get_customer_id();
    $dedup     = md5( $session . '|' . WC()->cart->get_cart_hash() );
    $lock_name = 'promix_order_' . md5( $session );

    $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- замок, а не данные.

    $same = wc_get_orders(
        array(
            'limit'        => 1,
            'created_via'  => 'promix',
            'date_created' => '>' . ( time() - 2 * MINUTE_IN_SECONDS ),
            'meta_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- один заказ по индексированному ключу.
                array(
                    'key'   => '_promix_dedup',
                    'value' => $dedup,
                ),
            ),
        )
    );

    if ( $same ) {
        $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- замок, а не данные.
        WC()->cart->empty_cart();
        wp_safe_redirect( $same[0]->get_checkout_order_received_url() );
        exit;
    }

    try {
        $order = wc_create_order(
            array(
                'created_via' => 'promix',
                'customer_id' => get_current_user_id(),
            )
        );

        if ( is_wp_error( $order ) ) {
            throw new RuntimeException( $order->get_error_message() );
        }

        foreach ( WC()->cart->get_cart() as $item ) {
            $order->add_product( $item['data'], min( (int) $item['quantity'], PROMIX_CART_MAX_QTY ) );
        }

        $order->set_address(
            array(
                'first_name' => $v['client'],
                'company'    => $v['company'],
                'phone'      => $v['phone'],
                'email'      => $v['email'],
                'country'    => 'RU',
            ),
            'billing'
        );

        if ( 'delivery' === $v['delivery'] ) {
            $order->set_address(
                array(
                    'first_name' => $v['client'],
                    'address_1'  => $v['address'],
                    'country'    => 'RU',
                ),
                'shipping'
            );
        }

        $order->set_customer_note( $v['comment'] );
        $order->update_meta_data( '_promix_delivery', $v['delivery'] );
        $order->update_meta_data( '_promix_dedup', $dedup );
        $order->calculate_totals( false );

        // «На удержании»: заказ ждёт звонка, а не оплаты — processing помечал бы его оплаченным.
        $order->update_status( 'on-hold', __( 'Заказ с сайта: ждёт звонка менеджера.', 'promix' ) );
    } catch ( Throwable $e ) {
        $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- замок, а не данные.
        promix_checkout_errors( array( __( 'Не получилось сохранить заказ. Позвоните нам, пожалуйста.', 'promix' ) ) );
        return;
    }

    $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- замок, а не данные.

    promix_lead_count_attempt( 'order' );

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
 * Письмо «Новый заказ» — на ту же почту, что и заявки с сайта.
 *
 * Иначе Woo шлёт его на admin_email, а заявки уходят менеджеру — и заказы
 * читает не тот человек.
 *
 * @param string $recipient Получатель по настройкам Woo.
 * @return string
 */
function promix_order_recipient( string $recipient ): string {
    return function_exists( 'promix_lead_recipient' ) ? promix_lead_recipient() : $recipient;
}
add_filter( 'woocommerce_email_recipient_new_order', 'promix_order_recipient' );

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
