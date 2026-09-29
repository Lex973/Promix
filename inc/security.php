<?php
/**
 * Защита входа в админку.
 *
 * Сайт маленький, админ один — плагин безопасности не ставим, закрываем
 * то, что реально используют для перебора паролей: XML-RPC (system.multicall
 * проверяет сотни паролей за запрос), логин из /wp-json/wp/v2/users и
 * ?author=N, подробные ошибки входа и бесконечные попытки.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

/*
 * XML-RPC целиком: сайту он не нужен (нет приложений и пингбэков).
 * Одного xmlrpc_enabled мало — методы без авторизации и multicall остаются.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
add_filter( 'pings_open', '__return_false' );

/**
 * Без X-Pingback в ответах — он указывает на xmlrpc.php.
 *
 * @param array<string, string> $headers Заголовки ответа.
 * @return array<string, string>
 */
function promix_no_pingback_header( array $headers ): array {
    unset( $headers['X-Pingback'] );

    return $headers;
}
add_filter( 'wp_headers', 'promix_no_pingback_header' );

/**
 * Список пользователей в REST — только тем, кто может их видеть:
 * гость по /wp-json/wp/v2/users узнавал логин администратора.
 *
 * @param array<string, mixed> $endpoints Маршруты REST.
 * @return array<string, mixed>
 */
function promix_rest_hide_users( array $endpoints ): array {
    if ( current_user_can( 'list_users' ) ) {
        return $endpoints;
    }

    foreach ( array_keys( $endpoints ) as $route ) {
        if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
            unset( $endpoints[ $route ] );
        }
    }

    return $endpoints;
}
add_filter( 'rest_endpoints', 'promix_rest_hide_users' );

/**
 * Архивов авторов на сайте нет: /author/… и ?author=N — 404.
 * Иначе ?author=1 редиректом выдаёт логин в адресе.
 */
function promix_no_author_archives(): void {
    if ( ! is_author() ) {
        return;
    }

    global $wp_query;

    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
    remove_action( 'template_redirect', 'redirect_canonical' );
}
add_action( 'template_redirect', 'promix_no_author_archives', 1 );

/**
 * Без имени и ссылки автора во встраиваемых карточках (oEmbed).
 *
 * @param array<string, mixed> $data Данные oEmbed.
 * @return array<string, mixed>
 */
function promix_oembed_no_author( array $data ): array {
    unset( $data['author_name'], $data['author_url'] );

    return $data;
}
add_filter( 'oembed_response_data', 'promix_oembed_no_author' );

/**
 * Одна ошибка входа на всё: по «неизвестное имя» и «неверный пароль»
 * подбирали существующие логины.
 *
 * @return string
 */
function promix_login_error(): string {
    // Во время паузы — честно о паузе, иначе человек будет вводить верный пароль впустую.
    if ( (int) get_transient( promix_login_key() ) >= PROMIX_LOGIN_TRIES ) {
        return __( 'Слишком много попыток входа. Попробуйте через 15 минут.', 'promix' );
    }

    return __( 'Неверный логин или пароль.', 'promix' );
}
add_filter( 'login_errors', 'promix_login_error' );

/*
 * Лимит попыток входа: пять неудачных с одного адреса — пауза 15 минут.
 * Считается по адресу посетителя так же, как лимит заявок.
 */
const PROMIX_LOGIN_TRIES = 5;
const PROMIX_LOGIN_LOCK  = 15 * MINUTE_IN_SECONDS;

/**
 * Ключ счётчика неудачных входов.
 *
 * @return string
 */
function promix_login_key(): string {
    $ip = function_exists( 'promix_lead_client_ip' ) ? promix_lead_client_ip() : (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- только в хэш.

    return 'promix_login_' . md5( $ip . ( defined( 'AUTH_SALT' ) ? AUTH_SALT : '' ) );
}

/**
 * Засчитать неудачный вход.
 */
function promix_login_failed(): void {
    $tries = (int) get_transient( promix_login_key() );

    set_transient( promix_login_key(), $tries + 1, PROMIX_LOGIN_LOCK );
}
add_action( 'wp_login_failed', 'promix_login_failed' );

/**
 * Пока адрес на паузе, вход не проходит даже с верным паролем.
 *
 * Приоритет 100 — после проверки пароля ядром (20): ранний WP_Error
 * ядро перезаписало бы найденным пользователем.
 *
 * @param WP_User|WP_Error|null $user Результат прошлых проверок.
 * @return WP_User|WP_Error|null
 */
function promix_login_locked( $user ) {
    if ( (int) get_transient( promix_login_key() ) >= PROMIX_LOGIN_TRIES ) {
        return new WP_Error( 'promix_login_locked', __( 'Слишком много попыток входа. Попробуйте через 15 минут.', 'promix' ) );
    }

    return $user;
}
add_filter( 'authenticate', 'promix_login_locked', 100 );

/**
 * Успешный вход обнуляет счётчик.
 */
function promix_login_succeeded(): void {
    delete_transient( promix_login_key() );
}
add_action( 'wp_login', 'promix_login_succeeded' );
