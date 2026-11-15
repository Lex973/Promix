<?php
/**
 * Кнопка «Написать в Telegram» — пара к кнопке MAX, светлая:
 * главным каналом остаётся MAX. Ссылка — «Контакты PROMIX» → Telegram;
 * пустое поле убирает кнопку.
 *
 * Аргументы: class — дополнительный класс, label — подпись,
 * aria — подпись для программ чтения, когда видимого текста мало.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$tg_url   = promix_contacts()['tg_url'];
$tg_class = isset( $args['class'] ) ? ' ' . $args['class'] : '';
$tg_label = $args['label'] ?? __( 'Написать в Telegram', 'promix' );
$tg_aria  = $args['aria'] ?? '';

if ( ! $tg_url ) {
    return;
}

?>
<a class="btn-max btn-max--tg<?php echo esc_attr( $tg_class ); ?>" href="<?php echo esc_url( $tg_url ); ?>" target="_blank" rel="noopener"
    <?php echo $tg_aria ? 'aria-label="' . esc_attr( $tg_aria ) . '"' : ''; ?>>
    <?php promix_the_icon( 'telegram', 2 ); ?>
    <?php if ( $tg_label ) : ?>
        <span><?php echo esc_html( $tg_label ); ?></span>
    <?php endif; ?>
</a>
