<?php
/**
 * Заявки с сайта: приём формы, хранение и уведомление.
 *
 * Заявки сохраняются в отдельный тип записей, поэтому не теряются,
 * даже если письмо не дойдёт. Отправка в MAX включается, когда в
 * wp-config.php появится PROMIX_MAX_TOKEN — токен на клиент не попадает.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

// Не чаще одной заявки в 30 секунд и не больше трёх за 10 минут с одного адреса.
const PROMIX_LEAD_PAUSE  = 30;
const PROMIX_LEAD_WINDOW = 600;
const PROMIX_LEAD_LIMIT  = 3;

/**
 * Тип записи для заявок: только чтение из админки, создаётся кодом.
 */
function promix_register_leads(): void {
    register_post_type(
        'promix_lead',
        array(
            'labels'          => array(
                'name'          => __( 'Заявки', 'promix' ),
                'singular_name' => __( 'Заявка', 'promix' ),
                'menu_name'     => __( 'Заявки', 'promix' ),
                'search_items'  => __( 'Искать заявки', 'promix' ),
                'not_found'     => __( 'Заявок пока нет', 'promix' ),
            ),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'menu_icon'       => 'dashicons-email-alt',
            'menu_position'   => 26,
            'supports'        => array( 'title' ),
            'capabilities'    => array(
                'create_posts' => 'do_not_allow',
            ),
            'map_meta_cap'    => true,
            'has_archive'     => false,
            'rewrite'         => false,
            'query_var'       => false,
        )
    );
}
add_action( 'init', 'promix_register_leads' );

/**
 * Колонки списка заявок: телефон и текст видны сразу, без открытия записи.
 *
 * @param array<string, string> $columns Колонки таблицы.
 * @return array<string, string>
 */
function promix_lead_columns( array $columns ): array {
    return array(
        'cb'            => isset( $columns['cb'] ) ? $columns['cb'] : '',
        'title'         => __( 'Кто', 'promix' ),
        'promix_phone'  => __( 'Телефон', 'promix' ),
        'promix_source' => __( 'Откуда', 'promix' ),
        'promix_note'   => __( 'Сообщение', 'promix' ),
        'date'          => __( 'Когда', 'promix' ),
    );
}
add_filter( 'manage_promix_lead_posts_columns', 'promix_lead_columns' );

/**
 * Содержимое колонок списка заявок.
 *
 * @param string $column  Имя колонки.
 * @param int    $post_id Идентификатор заявки.
 */
function promix_lead_column( string $column, int $post_id ): void {
    switch ( $column ) {
        case 'promix_phone':
            $phone = (string) get_post_meta( $post_id, '_promix_phone', true );
            if ( $phone ) {
                printf( '<a href="tel:%s">%s</a>', esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ), esc_html( $phone ) );
            }
            break;

        case 'promix_source':
            echo esc_html( (string) get_post_meta( $post_id, '_promix_source', true ) );
            break;

        case 'promix_note':
            echo esc_html( wp_trim_words( (string) get_post_meta( $post_id, '_promix_note', true ), 14 ) );
            break;
    }
}
add_action( 'manage_promix_lead_posts_custom_column', 'promix_lead_column', 10, 2 );

/**
 * Пометка у заявок, письмо о которых не ушло: их нужно разобрать руками.
 *
 * @param array<string, string> $states Пометки после заголовка.
 * @param WP_Post               $post   Запись.
 * @return array<string, string>
 */
function promix_lead_states( array $states, WP_Post $post ): array {
    if ( 'promix_lead' === $post->post_type && get_post_meta( $post->ID, '_promix_mail_failed', true ) ) {
        $states['promix_mail_failed'] = '<span class="promix-lead-failed">' . esc_html__( 'письмо не ушло', 'promix' ) . '</span>';
    }

    return $states;
}
add_filter( 'display_post_states', 'promix_lead_states', 10, 2 );

/**
 * Красная пометка в списке заявок.
 */
function promix_lead_admin_style(): void {
    $screen = get_current_screen();

    if ( ! $screen || 'promix_lead' !== $screen->post_type ) {
        return;
    }

    echo '<style>.promix-lead-failed{color:#b32d2e;font-weight:600}</style>';
}
add_action( 'admin_head-edit.php', 'promix_lead_admin_style' );

/**
 * Адрес, куда уходят письма о заявках.
 *
 * @return string
 */
function promix_lead_recipient(): string {
    $email = promix_option( 'lead_email' );

    return is_email( $email ) ? $email : (string) get_option( 'admin_email' );
}

/**
 * Свежий ключ для формы.
 *
 * Страничный кэш на проде отдаёт HTML со старым ключом, и форма молча
 * падает с 403. Поэтому ключ выдаётся отдельным запросом перед отправкой,
 * а ответ помечен как некэшируемый.
 */
function promix_lead_nonce(): void {
    nocache_headers();

    wp_send_json_success( array( 'nonce' => wp_create_nonce( 'promix_lead' ) ) );
}
add_action( 'wp_ajax_promix_lead_nonce', 'promix_lead_nonce' );
add_action( 'wp_ajax_nopriv_promix_lead_nonce', 'promix_lead_nonce' );

/**
 * Адрес отправителя — только чтобы отличать посетителей друг от друга.
 *
 * Сам адрес нигде не сохраняется: в счётчик уходит хеш с солью.
 *
 * @return string
 */
function promix_lead_client_ip(): string {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

    // За nginx в REMOTE_ADDR приходит сам сервер, настоящий адрес — в заголовке.
    if ( ! in_array( $ip, array( '127.0.0.1', '::1' ), true ) ) {
        return $ip;
    }

    if ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
        return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
    }

    if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $forwarded = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );

        return trim( $forwarded[0] );
    }

    return $ip;
}

/**
 * Ключ счётчика отправок.
 *
 * @param string $window Название окна: пауза или серия.
 * @return string
 */
function promix_lead_rate_key( string $window ): string {
    $salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : '';

    return 'promix_lead_' . $window . '_' . md5( promix_lead_client_ip() . $salt );
}

/**
 * Не частит ли отправитель.
 *
 * @return bool
 */
function promix_lead_rate_limited(): bool {
    if ( get_transient( promix_lead_rate_key( 'pause' ) ) ) {
        return true;
    }

    $series = get_transient( promix_lead_rate_key( 'series' ) );

    return is_array( $series ) && $series['count'] >= PROMIX_LEAD_LIMIT;
}

/**
 * Засчитать отправку. Считаются только заполненные формы, чтобы опечатка
 * в телефоне не запирала человека на полминуты.
 */
function promix_lead_count_attempt(): void {
    set_transient( promix_lead_rate_key( 'pause' ), 1, PROMIX_LEAD_PAUSE );

    $series = get_transient( promix_lead_rate_key( 'series' ) );

    if ( ! is_array( $series ) ) {
        $series = array(
            'count' => 0,
            'until' => time() + PROMIX_LEAD_WINDOW,
        );
    }

    ++$series['count'];

    // Окно не продлевается: срок считается от первой отправки в серии.
    set_transient( promix_lead_rate_key( 'series' ), $series, max( 1, $series['until'] - time() ) );
}

/**
 * Приём формы.
 *
 * Отвечает JSON, чтобы форма отправлялась без перезагрузки страницы.
 */
function promix_handle_lead(): void {
    if ( ! check_ajax_referer( 'promix_lead', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => __( 'Страница открыта давно. Обновите её и отправьте ещё раз.', 'promix' ) ), 403 );
    }

    if ( promix_lead_rate_limited() ) {
        wp_send_json_error( array( 'message' => __( 'Заявка уже у нас. Если нужно срочно — позвоните.', 'promix' ) ), 429 );
    }

    $name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $phone  = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
    $note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
    $source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
    $agree  = ! empty( $_POST['agree'] );

    // Длину режем на входе: в базу не должно попадать сочинение на мегабайт.
    $name   = mb_substr( $name, 0, 100 );
    $phone  = mb_substr( $phone, 0, 30 );
    $note   = mb_substr( $note, 0, 2000 );
    $source = mb_substr( $source, 0, 100 );

    // Ловушка для ботов: поле спрятано от людей и всегда должно быть пустым.
    $trap = isset( $_POST['company'] ) ? trim( (string) wp_unslash( $_POST['company'] ) ) : '';

    // Форму, отправленную быстрее трёх секунд, заполнял не человек.
    $opened = isset( $_POST['opened'] ) ? (int) $_POST['opened'] : 0;
    $fast   = $opened > 0 && ( time() - $opened ) < 3;

    if ( '' !== $trap || $fast ) {
        promix_lead_count_attempt();
        wp_send_json_success( array( 'message' => __( 'Спасибо, заявка отправлена.', 'promix' ) ) );
    }

    if ( ! $name || ! $phone ) {
        wp_send_json_error( array( 'message' => __( 'Заполните имя и телефон.', 'promix' ) ), 400 );
    }

    if ( ! $agree ) {
        wp_send_json_error( array( 'message' => __( 'Нужно согласие на обработку данных.', 'promix' ) ), 400 );
    }

    $digits = preg_replace( '/\D/', '', $phone );

    if ( strlen( (string) $digits ) < 10 ) {
        wp_send_json_error( array( 'message' => __( 'Проверьте номер телефона.', 'promix' ) ), 400 );
    }

    promix_lead_count_attempt();

    $lead_id = wp_insert_post(
        array(
            'post_type'   => 'promix_lead',
            'post_status' => 'publish',
            'post_title'  => $name,
        ),
        true
    );

    if ( is_wp_error( $lead_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Не получилось сохранить заявку. Позвоните нам, пожалуйста.', 'promix' ) ), 500 );
    }

    update_post_meta( $lead_id, '_promix_phone', $phone );
    update_post_meta( $lead_id, '_promix_note', $note );
    update_post_meta( $lead_id, '_promix_source', $source );

    // Письмо могло не уйти — такую заявку в списке видно сразу.
    if ( ! promix_notify_lead( $name, $phone, $note, $source ) ) {
        update_post_meta( $lead_id, '_promix_mail_failed', 1 );
    }

    /**
     * Заявка сохранена: сюда вешается отправка в MAX или CRM.
     *
     * @param int    $lead_id Идентификатор заявки.
     * @param string $name    Имя.
     * @param string $phone   Телефон.
     * @param string $note    Сообщение.
     * @param string $source  Кнопка, с которой пришла заявка.
     */
    do_action( 'promix_lead_created', $lead_id, $name, $phone, $note, $source );

    wp_send_json_success( array( 'message' => __( 'Спасибо! Перезвоним в рабочее время.', 'promix' ) ) );
}
add_action( 'wp_ajax_promix_lead', 'promix_handle_lead' );
add_action( 'wp_ajax_nopriv_promix_lead', 'promix_handle_lead' );

/**
 * Обратный адрес письма: ящик на своём домене, иначе письмо уходит в спам.
 *
 * @return string
 */
function promix_lead_from(): string {
    $host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
    $host = (string) preg_replace( '/^www\./i', '', $host );

    return 'noreply@' . $host;
}

/**
 * Письмо о новой заявке.
 *
 * @param string $name   Имя.
 * @param string $phone  Телефон.
 * @param string $note   Сообщение.
 * @param string $source Кнопка, с которой пришла заявка.
 * @return bool Ушло ли письмо.
 */
function promix_notify_lead( string $name, string $phone, string $note, string $source ): bool {
    $lines = array(
        __( 'Новая заявка с сайта PROMIX', 'promix' ),
        '',
        sprintf( /* translators: %s — имя клиента. */ __( 'Имя: %s', 'promix' ), $name ),
        sprintf( /* translators: %s — телефон клиента. */ __( 'Телефон: %s', 'promix' ), $phone ),
    );

    if ( $source ) {
        $lines[] = sprintf( /* translators: %s — название кнопки. */ __( 'Откуда: %s', 'promix' ), $source );
    }

    if ( $note ) {
        $lines[] = '';
        $lines[] = __( 'Сообщение:', 'promix' );
        $lines[] = $note;
    }

    $lines[] = '';
    $lines[] = admin_url( 'edit.php?post_type=promix_lead' );

    $headers = array(
        'From: PROMIX <' . promix_lead_from() . '>',
        'Content-Type: text/plain; charset=UTF-8',
    );

    return wp_mail(
        promix_lead_recipient(),
        sprintf( /* translators: %s — имя клиента. */ __( 'Заявка с сайта: %s', 'promix' ), $name ),
        implode( "\n", $lines ),
        $headers
    );
}
