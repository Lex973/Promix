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
 * Адрес, куда уходят письма о заявках.
 *
 * @return string
 */
function promix_lead_recipient(): string {
    $email = promix_option( 'lead_email' );

    return is_email( $email ) ? $email : (string) get_option( 'admin_email' );
}

/**
 * Приём формы.
 *
 * Отвечает JSON, чтобы форма отправлялась без перезагрузки страницы.
 */
function promix_handle_lead(): void {
    check_ajax_referer( 'promix_lead', 'nonce' );

    $name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $phone  = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
    $note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
    $source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
    $agree  = ! empty( $_POST['agree'] );

    // Ловушка для ботов: поле спрятано от людей и всегда должно быть пустым.
    $trap = isset( $_POST['company'] ) ? trim( (string) wp_unslash( $_POST['company'] ) ) : '';

    // Форму, отправленную быстрее трёх секунд, заполнял не человек.
    $opened = isset( $_POST['opened'] ) ? (int) $_POST['opened'] : 0;
    $fast   = $opened > 0 && ( time() - $opened ) < 3;

    if ( '' !== $trap || $fast ) {
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

    promix_notify_lead( $name, $phone, $note, $source );

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
 * Письмо о новой заявке.
 *
 * @param string $name   Имя.
 * @param string $phone  Телефон.
 * @param string $note   Сообщение.
 * @param string $source Кнопка, с которой пришла заявка.
 */
function promix_notify_lead( string $name, string $phone, string $note, string $source ): void {
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

    wp_mail(
        promix_lead_recipient(),
        sprintf( /* translators: %s — имя клиента. */ __( 'Заявка с сайта: %s', 'promix' ), $name ),
        implode( "\n", $lines )
    );
}
