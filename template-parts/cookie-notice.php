<?php
/**
 * Уведомление про cookie.
 *
 * Сайт не ставит счётчиков и рекламных пикселей, поэтому выбирать здесь
 * нечего: это уведомление с одной кнопкой, а не окно с категориями.
 * Появляется, пока посетитель не нажал «Понятно».
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$policy_url = get_privacy_policy_url();

?>
<div class="cookie" data-cookie hidden>
    <p class="cookie__text">
        <?php esc_html_e( 'Сайт использует только технические cookie — они нужны, чтобы страницы работали. Счётчиков и рекламы у нас нет.', 'promix' ); ?>
        <?php if ( $policy_url ) : ?>
            <a href="<?php echo esc_url( $policy_url ); ?>"><?php esc_html_e( 'Подробнее', 'promix' ); ?></a>
        <?php endif; ?>
    </p>

    <button class="btn btn--primary cookie__btn" type="button" data-cookie-accept>
        <?php esc_html_e( 'Понятно', 'promix' ); ?>
    </button>
</div>
