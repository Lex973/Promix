<?php
/**
 * Текстовая версия письма покупателю о принятом заказе (статус on-hold).
 *
 * См. woocommerce/emails/customer-on-hold-order.php — та же причина.
 * Оригинал: woocommerce/templates/emails/plain/customer-on-hold-order.php (9.8.0).
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$contacts = function_exists( 'promix_contacts' ) ? promix_contacts() : array();

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( $order->get_billing_first_name() ) {
    echo esc_html( sprintf( /* translators: %s — имя покупателя. */ __( 'Здравствуйте, %s!', 'promix' ), $order->get_billing_first_name() ) ) . "\n\n";
} else {
    echo esc_html__( 'Здравствуйте!', 'promix' ) . "\n\n";
}

echo esc_html__( 'Заказ принят. Менеджер перезвонит в рабочее время, подтвердит наличие, срок и способ получения — платить сейчас ничего не нужно.', 'promix' ) . "\n\n";

if ( ! empty( $contacts['phone'] ) ) {
    echo esc_html(
        sprintf(
            /* translators: 1 — телефон, 2 — часы работы. */
            __( 'Если нужно срочно — позвоните: %1$s, %2$s.', 'promix' ),
            $contacts['phone'],
            $contacts['hours'] ?? ''
        )
    ) . "\n\n";
}

echo esc_html__( 'Что в заказе:', 'promix' ) . "\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
