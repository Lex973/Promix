<?php
/**
 * Подвал сайта.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$promix        = promix_contacts();
$promix_policy = get_privacy_policy_url();
?>
</main>

<footer class="footer">
    <div class="container">

        <div class="footer__top">

            <div class="footer__brand">
                <?php get_template_part( 'template-parts/logo' ); ?>

                <p class="footer__about">Малярный центр в Казани: краски, инструмент и оборудование для мастеров, дизайнеров, строительных компаний и частных клиентов.</p>

                <?php
                get_template_part(
                    'template-parts/btn-max',
                    null,
                    array( 'class' => 'footer__max' )
                );
                ?>
            </div>

            <nav class="footer__col" aria-label="<?php esc_attr_e( 'Разделы сайта', 'promix' ); ?>">
                <h2 class="footer__title"><?php esc_html_e( 'Разделы', 'promix' ); ?></h2>
                <?php promix_menu( 'footer', 'footer__list' ); ?>
            </nav>

            <nav class="footer__col" aria-label="<?php esc_attr_e( 'Категории каталога', 'promix' ); ?>">
                <h2 class="footer__title"><?php esc_html_e( 'Каталог', 'promix' ); ?></h2>
                <?php promix_menu( 'footer_catalog', 'footer__list' ); ?>
            </nav>

            <div class="footer__col footer__col--contacts">
                <h2 class="footer__title"><?php esc_html_e( 'Контакты', 'promix' ); ?></h2>
                <ul class="footer__list">
                    <li><a class="footer__phone" href="tel:<?php echo esc_attr( promix_tel_href() ); ?>"><?php echo esc_html( $promix['phone'] ); ?></a></li>
                    <li><?php echo esc_html( $promix['address'] ); ?></li>
                    <li><?php echo esc_html( $promix['hours'] ); ?><br><?php echo esc_html( $promix['hours_extra'] ); ?></li>
                </ul>

                <div class="footer__maps">
                    <a href="<?php echo esc_url( $promix['gis_url'] ); ?>" target="_blank" rel="noopener">2ГИС</a>
                    <a href="<?php echo esc_url( $promix['yandex_url'] ); ?>" target="_blank" rel="noopener">Яндекс Карты</a>
                </div>
            </div>

        </div>

        <div class="footer__bottom">
            <p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> PROMIX — малярный центр в Казани</p>
            <?php if ( $promix_policy ) : ?>
                <a class="footer__policy" href="<?php echo esc_url( $promix_policy ); ?>"><?php esc_html_e( 'Политика конфиденциальности', 'promix' ); ?></a>
            <?php endif; ?>
        </div>

    </div>
</footer>

<?php get_template_part( 'template-parts/lead-modal' ); ?>

<?php get_template_part( 'template-parts/cookie-notice' ); ?>

<?php wp_footer(); ?>
</body>
</html>
