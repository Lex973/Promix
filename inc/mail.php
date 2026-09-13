<?php
/**
 * Почта через SMTP.
 *
 * На хостинге wp_mail() без SMTP уходит через локальный sendmail с адресом
 * вида noreply@домен — такие письма часто падают в спам или не доходят
 * вовсе. С ящика promix.kazan@mail.ru через SMTP mail.ru они доходят.
 * Настройки — константами в wp-config.php, не в базе:
 *
 *     define( 'PROMIX_SMTP_HOST', 'smtp.mail.ru' );
 *     define( 'PROMIX_SMTP_PORT', 465 );
 *     define( 'PROMIX_SMTP_SECURE', 'ssl' );          // ssl (465), tls (587) или '' (без шифрования)
 *     define( 'PROMIX_SMTP_USER', 'promix.kazan@mail.ru' );
 *     define( 'PROMIX_SMTP_PASS', '…' );              // пароль для внешних приложений, не от почты
 *     define( 'PROMIX_MAIL_FROM_NAME', 'PROMIX' );    // необязательно
 *
 * Пока PROMIX_SMTP_HOST не задан, ничего не меняется — письма идут как раньше.
 * Отправитель всех писем (заявки, заказы, письма Woo) становится SMTP-ящиком:
 * mail.ru не принимает письма с чужим From.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Настроен ли SMTP.
 */
function promix_smtp_ready(): bool {
    return defined( 'PROMIX_SMTP_HOST' ) && '' !== (string) PROMIX_SMTP_HOST;
}

/**
 * Адрес отправителя при SMTP — сам ящик.
 *
 * @return string
 */
function promix_smtp_from(): string {
    return defined( 'PROMIX_SMTP_USER' ) && is_email( PROMIX_SMTP_USER ) ? PROMIX_SMTP_USER : '';
}

/**
 * Переключить PHPMailer на SMTP.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $mailer Почтовик WordPress.
 */
function promix_smtp_setup( $mailer ): void {
    if ( ! promix_smtp_ready() ) {
        return;
    }

    $mailer->isSMTP();
    $mailer->Host    = (string) PROMIX_SMTP_HOST; // phpcs:ignore WordPress.NamingConventions.ValidVariableName -- свойства PHPMailer.
    $mailer->Port    = defined( 'PROMIX_SMTP_PORT' ) ? (int) PROMIX_SMTP_PORT : 465; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
    $mailer->Timeout = 10; // phpcs:ignore WordPress.NamingConventions.ValidVariableName -- дольше покупатель ждать не должен.

    $secure = defined( 'PROMIX_SMTP_SECURE' ) ? (string) PROMIX_SMTP_SECURE : 'ssl';

    $mailer->SMTPSecure  = $secure; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
    $mailer->SMTPAutoTLS = '' !== $secure; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

    $user = defined( 'PROMIX_SMTP_USER' ) ? (string) PROMIX_SMTP_USER : '';
    $pass = defined( 'PROMIX_SMTP_PASS' ) ? (string) PROMIX_SMTP_PASS : '';

    $mailer->SMTPAuth = '' !== $user && '' !== $pass; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
    $mailer->Username = $user; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
    $mailer->Password = $pass; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

    // From из заголовков письма (заявки, Woo) заменяется на ящик SMTP; ответ
    // покупателю при этом уходит туда же, отдельный Reply-To не нужен.
    $from = promix_smtp_from();

    if ( $from ) {
        $mailer->setFrom( $from, promix_mail_from_name(), false );
    }
}
add_action( 'phpmailer_init', 'promix_smtp_setup', 100 );

/**
 * Имя отправителя.
 *
 * @return string
 */
function promix_mail_from_name(): string {
    return defined( 'PROMIX_MAIL_FROM_NAME' ) && '' !== (string) PROMIX_MAIL_FROM_NAME ? (string) PROMIX_MAIL_FROM_NAME : 'PROMIX';
}

/**
 * Отправитель для писем без явного From (ядро WordPress).
 *
 * @param mixed $from Адрес по умолчанию (wordpress@домен).
 * @return mixed
 */
function promix_mail_from( $from ) {
    return promix_smtp_ready() && promix_smtp_from() ? promix_smtp_from() : $from;
}
add_filter( 'wp_mail_from', 'promix_mail_from' );

/**
 * Имя отправителя для писем ядра («WordPress» → «PROMIX»).
 *
 * @param mixed $name Имя по умолчанию.
 * @return mixed
 */
function promix_mail_from_name_filter( $name ) {
    return promix_smtp_ready() ? promix_mail_from_name() : $name;
}
add_filter( 'wp_mail_from_name', 'promix_mail_from_name_filter' );

/**
 * Письмо не ушло — запомнить ошибку и показать администратору.
 *
 * Иначе о сломанном SMTP узнают, когда менеджер спросит, почему нет заказов.
 *
 * @param WP_Error $error Ошибка PHPMailer.
 */
function promix_mail_failed( WP_Error $error ): void {
    update_option(
        'promix_mail_error',
        array(
            'time'    => time(),
            'message' => $error->get_error_message(),
        ),
        false
    );
}
add_action( 'wp_mail_failed', 'promix_mail_failed' );

/**
 * Плашка в админке про последнюю ошибку почты; пропадает после удачной отправки.
 */
function promix_mail_notice(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $error = get_option( 'promix_mail_error' );

    if ( ! is_array( $error ) || empty( $error['message'] ) ) {
        return;
    }

    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html(
            sprintf(
                /* translators: 1 — дата и время, 2 — текст ошибки. */
                __( 'Письмо с сайта не отправилось (%1$s): %2$s. Проверьте настройки SMTP в wp-config.php.', 'promix' ),
                wp_date( 'j.m.Y H:i', (int) $error['time'] ),
                $error['message']
            )
        )
    );
}
add_action( 'admin_notices', 'promix_mail_notice' );

/**
 * Удачная отправка снимает плашку.
 */
function promix_mail_succeeded(): void {
    if ( get_option( 'promix_mail_error' ) ) {
        delete_option( 'promix_mail_error' );
    }
}
add_action( 'wp_mail_succeeded', 'promix_mail_succeeded' );
