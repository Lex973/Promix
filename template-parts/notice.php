<?php
/**
 * Плашка срочного объявления под шапкой: режим работы, дорога, сбои.
 *
 * Включается и правится на странице «Объявление» в админке
 * (inc/fields/notice.php). Посетитель может закрыть плашку крестиком —
 * она не вернётся, пока текст объявления не поменяют.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$text = trim( promix_option( 'notice_text' ) );

if ( ! promix_option( 'notice_on' ) || '' === $text ) {
    return;
}

// Срок вышел — плашка скрывается сама, галочку снимать не нужно.
$until = promix_option( 'notice_until' );

if ( $until && wp_date( 'Y-m-d' ) > $until ) {
    return;
}

$link_text = trim( promix_option( 'notice_link_text' ) );
$link_url  = trim( promix_option( 'notice_link_url' ) );

// Закрытую плашку помним по содержимому: новый текст покажется снова.
// Ключ — до правки ссылки ниже, иначе на главной и в каталоге он разный.
$key = 'promix-notice-' . substr( md5( $text . '|' . $link_text . '|' . $link_url ), 0, 10 );

// Ссылка на раздел главной работает и с других страниц.
if ( str_starts_with( $link_url, '#' ) && ! is_front_page() ) {
    $link_url = home_url( '/' ) . $link_url;
}

?>
<div class="notice" id="notice" data-notice="<?php echo esc_attr( $key ); ?>" role="region" aria-label="<?php esc_attr_e( 'Объявление', 'promix' ); ?>">
    <div class="container notice__inner">
        <p class="notice__text">
            <?php echo esc_html( $text ); ?>
            <?php if ( $link_text && $link_url ) : ?>
                <a class="notice__link" href="<?php echo esc_url( $link_url ); ?>"><?php echo esc_html( $link_text ); ?></a>
            <?php endif; ?>
        </p>
        <button class="notice__close" type="button" data-notice-close aria-label="<?php esc_attr_e( 'Скрыть объявление', 'promix' ); ?>">
            <?php promix_the_icon( 'x', 2 ); ?>
        </button>
    </div>
</div>
<script>
<?php /* До отрисовки: закрытая раньше плашка не мигает при загрузке. */ ?>
try { if (localStorage.getItem(<?php echo wp_json_encode( $key ); ?>)) { document.getElementById('notice').hidden = true; } } catch (e) {}
</script>
