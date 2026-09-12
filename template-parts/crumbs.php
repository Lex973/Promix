<?php
/**
 * Хлебные крошки.
 *
 * Принимает список «подпись => адрес»; пункт с пустым адресом — текущая
 * страница. «Главная» добавляется сама. Страница товара передаёт только
 * ссылки: её название и так стоит заголовком.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$items = array( __( 'Главная', 'promix' ) => home_url( '/' ) ) + (array) ( $args['items'] ?? array() );
$first = true;
?>
<nav class="crumbs" aria-label="<?php esc_attr_e( 'Вы здесь', 'promix' ); ?>">
    <?php foreach ( $items as $label => $url ) : ?>
        <?php if ( ! $first ) : ?>
            <?php echo promix_icon( 'chevron-right', 2, 'crumbs__sep' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
        <?php endif; ?>
        <?php if ( $url ) : ?>
            <a class="crumbs__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
        <?php else : ?>
            <span class="crumbs__current" aria-current="page"><?php echo esc_html( $label ); ?></span>
        <?php endif; ?>
        <?php $first = false; ?>
    <?php endforeach; ?>
</nav>
