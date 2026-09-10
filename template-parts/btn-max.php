<?php
/**
 * Кнопка «Написать в MAX».
 *
 * Аргументы: class — дополнительный класс, label — подпись,
 * aria — подпись для программ чтения, когда видимого текста мало.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$max_url   = promix_contacts()['max_url'];
$max_class = isset( $args['class'] ) ? ' ' . $args['class'] : '';
$max_label = $args['label'] ?? __( 'Написать в MAX', 'promix' );
$max_aria  = $args['aria'] ?? '';

if ( ! $max_url ) {
    return;
}

?>
<a class="btn-max<?php echo esc_attr( $max_class ); ?>" href="<?php echo esc_url( $max_url ); ?>"
    <?php echo $max_aria ? 'aria-label="' . esc_attr( $max_aria ) . '"' : ''; ?>>
    <?php echo promix_icon( 'max', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
    <span><?php echo esc_html( $max_label ); ?></span>
</a>
