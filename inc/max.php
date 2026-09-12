<?php
/**
 * Заявки и заказы — менеджеру в MAX.
 *
 * Бот заводится в MAX через @MasterBot, он выдаёт токен. Токен и номер
 * чата живут в wp-config.php, а не в базе и не в теме:
 *
 *     define( 'PROMIX_MAX_TOKEN', '…' );
 *     define( 'PROMIX_MAX_CHAT', 123456789 );
 *
 * Номер чата — это диалог менеджера с ботом или групповой чат, куда бота
 * добавили; узнать его можно на странице «Инструменты → MAX» в админке:
 * менеджер пишет боту любое слово, страница показывает, откуда пришло.
 *
 * Отправка идёт прямо в момент заявки или заказа, с таймаутом в пять
 * секунд. Не ушло — заявка и заказ помечаются, менеджер увидит это
 * в списке; письмо при этом всё равно уходит.
 *
 * API: https://dev.max.ru/docs-api — POST /messages?chat_id=…,
 * токен в заголовке Authorization, текст без разметки.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

const PROMIX_MAX_API = 'https://platform-api2.max.ru';

/**
 * Настроен ли MAX: есть токен и чат.
 */
function promix_max_ready(): bool {
    return defined( 'PROMIX_MAX_TOKEN' ) && PROMIX_MAX_TOKEN
        && defined( 'PROMIX_MAX_CHAT' ) && (int) PROMIX_MAX_CHAT;
}

/**
 * Запрос к API бота.
 *
 * @param string               $method GET или POST.
 * @param string               $path   Путь вроде /messages.
 * @param array<string, mixed> $query  Параметры адреса.
 * @param array<string, mixed> $body   Тело запроса (JSON).
 * @return array<string, mixed>|WP_Error Разобранный ответ или ошибка.
 */
function promix_max_request( string $method, string $path, array $query = array(), array $body = array() ) {
    if ( ! defined( 'PROMIX_MAX_TOKEN' ) || ! PROMIX_MAX_TOKEN ) {
        return new WP_Error( 'promix_max_token', __( 'Токен MAX не задан.', 'promix' ) );
    }

    $args = array(
        'method'  => $method,
        'timeout' => 5,
        'headers' => array(
            'Authorization' => PROMIX_MAX_TOKEN,
            'Content-Type'  => 'application/json',
        ),
    );

    if ( $body ) {
        $args['body'] = wp_json_encode( $body, JSON_UNESCAPED_UNICODE );
    }

    $response = wp_remote_request( add_query_arg( $query, PROMIX_MAX_API . $path ), $args );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    $data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

    if ( $code < 200 || $code >= 300 ) {
        $message = is_array( $data ) && ! empty( $data['message'] ) ? (string) $data['message'] : 'HTTP ' . $code;

        return new WP_Error( 'promix_max_http', $message, array( 'status' => $code ) );
    }

    return is_array( $data ) ? $data : array();
}

/**
 * Сообщение в чат менеджера.
 *
 * Текст без разметки: в названиях товаров попадаются звёздочки
 * и подчёркивания, markdown их съел бы.
 *
 * @param string $text Текст.
 * @return true|WP_Error
 */
function promix_max_send( string $text ) {
    if ( ! promix_max_ready() ) {
        return new WP_Error( 'promix_max_off', __( 'MAX не настроен: нет токена или чата.', 'promix' ) );
    }

    // Цены приходят с &nbsp; из number_format_i18n — в мессенджере нужен обычный текст.
    $text = str_replace( "\u{a0}", ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) );

    $result = promix_max_request(
        'POST',
        '/messages',
        array( 'chat_id' => (int) PROMIX_MAX_CHAT ),
        array(
            'text'   => mb_substr( $text, 0, 4000 ),
            'notify' => true,
        )
    );

    return is_wp_error( $result ) ? $result : true;
}

/**
 * Заявка с сайта → в MAX.
 *
 * @param int    $lead_id Идентификатор заявки.
 * @param string $name    Имя.
 * @param string $phone   Телефон.
 * @param string $note    Сообщение.
 * @param string $source  Кнопка, с которой пришла заявка.
 */
function promix_max_lead( int $lead_id, string $name, string $phone, string $note, string $source ): void {
    if ( ! promix_max_ready() ) {
        return;
    }

    $lines = array(
        __( '📩 Заявка с сайта', 'promix' ),
        '',
        $name,
        $phone,
    );

    if ( $source ) {
        $lines[] = sprintf( /* translators: %s — кнопка. */ __( 'Откуда: %s', 'promix' ), $source );
    }

    if ( $note ) {
        $lines[] = '';
        $lines[] = $note;
    }

    $lines[] = '';
    $lines[] = admin_url( 'edit.php?post_type=promix_lead' );

    $result = promix_max_send( implode( "\n", $lines ) );

    if ( is_wp_error( $result ) ) {
        update_post_meta( $lead_id, '_promix_max_failed', $result->get_error_message() );
    }
}
add_action( 'promix_lead_created', 'promix_max_lead', 10, 5 );

/**
 * Заказ с сайта → в MAX.
 *
 * @param WC_Order $order Заказ.
 */
function promix_max_order( WC_Order $order ): void {
    if ( ! promix_max_ready() ) {
        return;
    }

    $lines = array(
        sprintf( /* translators: %s — номер заказа. */ __( '🛒 Заказ №%s с сайта', 'promix' ), $order->get_order_number() ),
        '',
        trim( $order->get_billing_first_name() . ( $order->get_billing_company() ? ' — ' . $order->get_billing_company() : '' ) ),
        $order->get_billing_phone(),
    );

    if ( $order->get_billing_email() ) {
        $lines[] = $order->get_billing_email();
    }

    $lines[] = '';

    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        $sku     = $product instanceof WC_Product && $product->get_sku() ? ' (' . $product->get_sku() . ')' : '';

        $lines[] = sprintf(
            '• %s%s × %d — %s',
            $item->get_name(),
            $sku,
            $item->get_quantity(),
            promix_price( (float) $item->get_total() )
        );
    }

    $lines[] = '';
    $lines[] = sprintf( /* translators: %s — сумма. */ __( 'Итого: %s', 'promix' ), promix_price( (float) $order->get_total() ) );
    $lines[] = promix_order_delivery( $order ) . ( $order->get_shipping_address_1() ? ' — ' . $order->get_shipping_address_1() : '' );

    if ( $order->get_customer_note() ) {
        $lines[] = '';
        $lines[] = $order->get_customer_note();
    }

    $lines[] = '';
    $lines[] = $order->get_edit_order_url();

    $result = promix_max_send( implode( "\n", $lines ) );

    if ( is_wp_error( $result ) ) {
        $order->update_meta_data( '_promix_max_failed', $result->get_error_message() );
        $order->add_order_note( sprintf( /* translators: %s — ошибка. */ __( 'В MAX не ушло: %s', 'promix' ), $result->get_error_message() ) );
        $order->save();
    } else {
        $order->add_order_note( __( 'Отправлено менеджеру в MAX.', 'promix' ) );
    }
}
add_action( 'promix_order_created', 'promix_max_order' );

/**
 * Пометка у заявки, которая не дошла до MAX.
 *
 * @param array<string, string> $states Пометки после заголовка.
 * @param WP_Post               $post   Запись.
 * @return array<string, string>
 */
function promix_max_lead_state( array $states, WP_Post $post ): array {
    if ( 'promix_lead' === $post->post_type && get_post_meta( $post->ID, '_promix_max_failed', true ) ) {
        $states['promix_max_failed'] = '<span class="promix-lead-failed">' . esc_html__( 'в MAX не ушло', 'promix' ) . '</span>';
    }

    return $states;
}
add_filter( 'display_post_states', 'promix_max_lead_state', 10, 2 );

/**
 * Страница «Инструменты → MAX»: состояние, поиск номера чата, тестовое сообщение.
 */
function promix_max_admin_menu(): void {
    add_management_page( __( 'MAX', 'promix' ), __( 'MAX', 'promix' ), 'manage_options', 'promix-max', 'promix_max_admin_page' );
}
add_action( 'admin_menu', 'promix_max_admin_menu' );

/**
 * Разметка страницы.
 */
function promix_max_admin_page(): void {
    $has_token = defined( 'PROMIX_MAX_TOKEN' ) && PROMIX_MAX_TOKEN;
    $chat      = defined( 'PROMIX_MAX_CHAT' ) ? (int) PROMIX_MAX_CHAT : 0;
    $notice    = '';
    $error     = '';

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- проверка ниже.
    if ( isset( $_POST['promix_max_test'] ) && check_admin_referer( 'promix-max' ) ) {
        $result = promix_max_send( __( 'Проверка связи: сайт PROMIX подключён к MAX.', 'promix' ) );

        if ( is_wp_error( $result ) ) {
            $error = $result->get_error_message();
        } else {
            $notice = __( 'Сообщение ушло — проверьте чат.', 'promix' );
        }
    }
    // phpcs:enable

    $me      = $has_token ? promix_max_request( 'GET', '/me' ) : null;
    $updates = $has_token && ! is_wp_error( $me ) ? promix_max_request( 'GET', '/updates', array( 'limit' => 20 ) ) : null;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'MAX', 'promix' ); ?></h1>

        <?php if ( $notice ) : ?>
            <div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
        <?php endif; ?>
        <?php if ( $error ) : ?>
            <div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tr>
                <th><?php esc_html_e( 'Токен бота', 'promix' ); ?></th>
                <td>
                    <?php if ( ! $has_token ) : ?>
                        <?php esc_html_e( 'Не задан. Добавьте в wp-config.php:', 'promix' ); ?>
                        <code>define( 'PROMIX_MAX_TOKEN', '…' );</code>
                    <?php elseif ( is_wp_error( $me ) ) : ?>
                        <span style="color:#b32d2e"><?php echo esc_html( sprintf( /* translators: %s — ошибка. */ __( 'Задан, но API не отвечает: %s', 'promix' ), $me->get_error_message() ) ); ?></span>
                    <?php else : ?>
                        <?php echo esc_html( sprintf( /* translators: 1 — имя бота, 2 — его username. */ __( 'Задан. Бот: %1$s (@%2$s)', 'promix' ), (string) ( $me['name'] ?? '' ), (string) ( $me['username'] ?? '' ) ) ); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Чат менеджера', 'promix' ); ?></th>
                <td>
                    <?php if ( $chat ) : ?>
                        <code><?php echo esc_html( (string) $chat ); ?></code>
                    <?php else : ?>
                        <?php esc_html_e( 'Не задан. Найдите номер в списке ниже и добавьте в wp-config.php:', 'promix' ); ?>
                        <code>define( 'PROMIX_MAX_CHAT', 123 );</code>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <?php if ( is_array( $updates ) ) : ?>
            <h2><?php esc_html_e( 'Кто писал боту недавно', 'promix' ); ?></h2>
            <p><?php esc_html_e( 'Чтобы найти номер чата: менеджер пишет боту любое слово (или добавляет бота в групповой чат и пишет там), потом обновите эту страницу.', 'promix' ); ?></p>
            <?php $seen = array(); ?>
            <?php foreach ( (array) ( $updates['updates'] ?? array() ) as $update ) : ?>
                <?php
                $message = $update['message'] ?? array();
                $chat_id = (int) ( $message['recipient']['chat_id'] ?? $update['chat_id'] ?? 0 );
                $sender  = (string) ( $message['sender']['name'] ?? $update['user']['name'] ?? '' );
                $text    = (string) ( $message['body']['text'] ?? $update['update_type'] ?? '' );

                if ( ! $chat_id || isset( $seen[ $chat_id ] ) ) {
                    continue;
                }

                $seen[ $chat_id ] = true;
                ?>
                <p>
                    <code><?php echo esc_html( (string) $chat_id ); ?></code>
                    — <?php echo esc_html( $sender ); ?>: <?php echo esc_html( wp_trim_words( $text, 8 ) ); ?>
                </p>
            <?php endforeach; ?>
            <?php if ( ! $seen ) : ?>
                <p><em><?php esc_html_e( 'Пока никто не писал.', 'promix' ); ?></em></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ( promix_max_ready() ) : ?>
            <form method="post">
                <?php wp_nonce_field( 'promix-max' ); ?>
                <p><button class="button button-primary" type="submit" name="promix_max_test" value="1"><?php esc_html_e( 'Отправить тестовое сообщение', 'promix' ); ?></button></p>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
