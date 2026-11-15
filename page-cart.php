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

// Шаблон по слагу подхватится и без WooCommerce (плагин выключен на время
// неудачного обновления) — тогда это обычная страница, а не фатал.
if ( ! function_exists( 'WC' ) ) {
    get_template_part( 'page' );
    return;
}

get_header();
?>

<section class="section cart">
    <div class="container">

        <?php get_template_part( 'template-parts/crumbs', null, array( 'items' => array( __( 'Корзина', 'promix' ) => '' ) ) ); ?>

        <h1 class="section__title cart__title"><?php esc_html_e( 'Корзина', 'promix' ); ?></h1>

        <div class="cart__body" data-cart aria-live="polite">
            <?php get_template_part( 'template-parts/cart/items' ); ?>
        </div>

    </div>
</section>

<?php
get_footer();
