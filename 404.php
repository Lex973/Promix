<?php
/**
 * Страница 404.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="nf">
    <div class="container nf__inner">

        <p class="nf__code" aria-hidden="true">4<span>0</span>4</p>

        <div>
            <p class="kicker"><?php esc_html_e( 'Ошибка', 'promix' ); ?></p>
            <h1 class="nf__title"><?php esc_html_e( 'Такой страницы нет', 'promix' ); ?></h1>
            <p class="nf__text"><?php esc_html_e( 'Возможно, ссылка устарела или в адресе опечатка. Загляните в каталог или напишите нам — подскажем, что нужно.', 'promix' ); ?></p>

            <div class="nf__actions">
                <a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'На главную', 'promix' ); ?></a>
                <a class="btn btn--outline" href="<?php echo esc_url( home_url( '/#catalog' ) ); ?>"><?php esc_html_e( 'В каталог', 'promix' ); ?></a>
            </div>
        </div>

    </div>
</section>

<?php
get_footer();
