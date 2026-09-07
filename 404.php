<?php
/**
 * Страница 404.
 *
 * @package promix
 */

get_header();
?>

<section class="section">
    <div class="container">
        <p class="kicker"><?php esc_html_e( 'Ошибка 404', 'promix' ); ?></p>
        <h1 class="section__title"><?php esc_html_e( 'Такой страницы нет', 'promix' ); ?></h1>
        <p class="section__lead"><?php esc_html_e( 'Возможно, ссылка устарела. Загляните в каталог или напишите нам — подскажем, что нужно.', 'promix' ); ?></p>
        <p class="page404__cta"><a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'На главную', 'promix' ); ?></a></p>
    </div>
</section>

<?php
get_footer();
