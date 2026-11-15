<?php
/**
 * Письмо покупателю о принятом заказе (статус on-hold).
 *
 * Штатный текст Woo обещает «ждём подтверждения платежа», а оплаты на сайте
 * нет — заказ ждёт звонка менеджера. Переопределяем только вступление,
 * состав заказа, реквизиты и подвал остаются за хуками Woo.
 *
 * Оригинал: woocommerce/templates/emails/customer-on-hold-order.php (10.4.0).
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$contacts = function_exists( 'promix_contacts' ) ? promix_contacts() : array();

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<div class="email-introduction">
    <p>
        <?php if ( $order->get_billing_first_name() ) : ?>
            <?php echo esc_html( sprintf( /* translators: %s — имя покупателя. */ __( 'Здравствуйте, %s!', 'promix' ), $order->get_billing_first_name() ) ); ?>
        <?php else : ?>
            <?php esc_html_e( 'Здравствуйте!', 'promix' ); ?>
        <?php endif; ?>
    </p>
    <p><?php esc_html_e( 'Заказ принят. Менеджер перезвонит в рабочее время, подтвердит наличие, срок и способ получения — платить сейчас ничего не нужно.', 'promix' ); ?></p>
    <?php if ( ! empty( $contacts['phone'] ) ) : ?>
        <p>
            <?php
            echo esc_html(
                sprintf(
                    /* translators: 1 — телефон, 2 — часы работы. */
                    __( 'Если нужно срочно — позвоните: %1$s, %2$s.', 'promix' ),
                    $contacts['phone'],
                    function_exists( 'promix_hours_full' ) ? promix_hours_full() : ''
                )
            );
            ?>
        </p>
    <?php endif; ?>
    <p><?php esc_html_e( 'Что в заказе:', 'promix' ); ?></p>
</div>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
    echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">';
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
    echo '</td></tr></table>';
}

do_action( 'woocommerce_email_footer', $email );
