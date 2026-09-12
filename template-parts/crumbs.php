<?php
/**
 * Хлебные крошки.
 *
 * Принимает список «подпись => адрес»; у последнего пункта адрес пустой —
 * это текущая страница. «Главная» добавляется сама.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$items = array( __( 'Главная', 'promix' ) => home_url( '/' ) ) + (array) ( $args['items'] ?? array() );
$last  = array_key_last( $items );
?>
<nav class="crumbs" aria-label="<?php esc_attr_e( 'Вы здесь', 'promix' ); ?>">
    <?php foreach ( $items as $label => $url ) : ?>
        <?php if ( $label !== $last && $url ) : ?>
            <a class="crumbs__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
            <?php echo promix_icon( 'chevron-right', 2, 'crumbs__sep' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
        <?php else : ?>
            <span class="crumbs__current" aria-current="page"><?php echo esc_html( $label ); ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
