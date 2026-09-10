<?php
/**
 * Логотип со ссылкой на главную.
 *
 * Выводится в шапке, в мобильном меню и в подвале — правки в одном месте.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$logo_class = isset( $args['class'] ) ? ' ' . $args['class'] : '';

?>
<a class="logo<?php echo esc_attr( $logo_class ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"
   aria-label="<?php esc_attr_e( 'PROMIX — на главную', 'promix' ); ?>">
    <img class="logo__mark" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo.png' ) ); ?>"
         alt="" width="78" height="52">
    <span class="logo__text">
        <span class="logo__name">PROMIX</span>
        <span class="logo__tagline"><?php esc_html_e( 'пространство для', 'promix' ); ?><br><?php esc_html_e( 'профессионалов', 'promix' ); ?></span>
    </span>
</a>
