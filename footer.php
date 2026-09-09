<?php
/**
 * Подвал сайта.
 *
 * @package promix
 */

$promix = promix_contacts();
?>
</main>

<footer class="footer">
    <div class="container">

        <div class="footer__top">

            <div class="footer__brand">
                <a class="logo" href="/" aria-label="PROMIX — на главную">
                    <img class="logo__mark" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo.png' ) ); ?>" alt="" width="78" height="52">
                    <span class="logo__text">
                        <span class="logo__name">PROMIX</span>
                        <span class="logo__tagline">пространство для<br>профессионалов</span>
                    </span>
                </a>

                <p class="footer__about">Малярный центр в Казани: краски, инструмент и оборудование для мастеров, дизайнеров, строительных компаний и частных клиентов.</p>

                <a class="btn-max footer__max" href="<?php echo esc_url( $promix['max_url'] ); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                    <span>Написать в MAX</span>
                </a>
            </div>

            <nav class="footer__col" aria-label="Разделы сайта">
                <h2 class="footer__title">Разделы</h2>
                <ul class="footer__list">
                    <li><a href="#catalog">Каталог</a></li>
                    <li><a href="#brands">Бренды</a></li>
                    <li><a href="#about">О компании</a></li>
                    <li><a href="#why">Почему PROMIX</a></li>
                    <li><a href="#reviews">Отзывы</a></li>
                    <li><a href="#contacts">Контакты</a></li>
                </ul>
            </nav>

            <nav class="footer__col" aria-label="Категории каталога">
                <h2 class="footer__title">Каталог</h2>
                <ul class="footer__list">
                    <li><a href="#">Краски</a></li>
                    <li><a href="#">Шпаклёвки и клеи</a></li>
                    <li><a href="#">Грунты</a></li>
                    <li><a href="#">Инструмент</a></li>
                    <li><a href="#">Оборудование</a></li>
                    <li><a href="#">Расходники</a></li>
                </ul>
            </nav>

            <div class="footer__col footer__col--contacts">
                <h2 class="footer__title">Контакты</h2>
                <ul class="footer__list">
                    <li><a class="footer__phone" href="tel:<?php echo esc_attr( $promix['phone_raw'] ); ?>"><?php echo esc_html( $promix['phone'] ); ?></a></li>
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
            <a class="footer__policy" href="#">Политика конфиденциальности</a>
        </div>

    </div>
</footer>

<?php get_template_part( 'template-parts/lead-modal' ); ?>

<?php wp_footer(); ?>
</body>
</html>
