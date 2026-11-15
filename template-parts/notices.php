<?php
/**
 * Уведомления WooCommerce своей разметкой.
 *
 * Woo копит сообщения в сессии (товар снят с продажи и убран из корзины,
 * «Пересчитать» без скрипта, ?add-to-cart без скрипта) и ждёт, что тема
 * их напечатает. Штатный wc_print_notices() отдаёт разметку под стили
 * Woo, которые мы не грузим, — поэтому забираем тексты и рисуем сами.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_notices' ) ) {
    return;
}

$notices = array();

foreach ( wc_get_notices() as $type => $items ) {
    foreach ( (array) $items as $item ) {
        $text = is_array( $item ) ? ( $item['notice'] ?? '' ) : $item;
        // Из разметки Woo оставляем только ссылки: «Отменить?» после удаления, «Просмотр корзины».
        $text = trim( wp_kses( (string) $text, array( 'a' => array( 'href' => true ) ) ) );

        if ( '' !== $text ) {
            $notices[] = array( 'type' => $type, 'text' => $text );
        }
    }
}

wc_clear_notices();

if ( ! $notices ) {
    return;
}
?>
<div class="notices" role="status">
    <ul>
        <?php foreach ( $notices as $notice ) : ?>
            <li class="notices__item notices__item--<?php echo esc_attr( sanitize_html_class( $notice['type'] ) ); ?>"><?php echo wp_kses( $notice['text'], array( 'a' => array( 'href' => true ) ) ); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
