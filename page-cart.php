<?php
/**
 * Страница корзины (страница WooCommerce со слагом cart).
 *
 * Список позиций живёт в template-parts/cart/items.php — тот же кусок
 * приходит по AJAX после изменения количества.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="section cart">
    <div class="container">

        <nav class="crumbs" aria-label="<?php esc_attr_e( 'Вы здесь', 'promix' ); ?>">
            <a class="crumbs__link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Главная', 'promix' ); ?></a>
            <?php echo promix_icon( 'chevron-right', 2, 'crumbs__sep' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
            <span class="crumbs__current" aria-current="page"><?php esc_html_e( 'Корзина', 'promix' ); ?></span>
        </nav>

        <h1 class="section__title cart__title"><?php esc_html_e( 'Корзина', 'promix' ); ?></h1>

        <div class="cart__body" data-cart aria-live="polite">
            <?php get_template_part( 'template-parts/cart/items' ); ?>
        </div>

    </div>
</section>

<?php
get_footer();
