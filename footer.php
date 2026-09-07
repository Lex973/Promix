<?php
/**
 * Подвал сайта.
 *
 * @package promix
 */

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

                <a class="btn-max footer__max" href="#">
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

            <div class="footer__col">
                <h2 class="footer__title">Контакты</h2>
                <ul class="footer__list">
                    <li><a class="footer__phone" href="tel:+79534840000">+7 (953) 484-00-00</a></li>
                    <li>Казань, ул. Габдуллы Тукая, 91</li>
                    <li>Пн–Пт 9:00–18:00<br>Сб 9:00–14:00 · Вс — выходной</li>
                </ul>

                <div class="footer__maps">
                    <a href="https://2gis.ru/kazan/firm/70000001060590384" target="_blank" rel="noopener">2ГИС</a>
                    <a href="https://yandex.ru/maps/org/promix/59684652364/" target="_blank" rel="noopener">Яндекс Карты</a>
                </div>
            </div>

        </div>

        <div class="footer__bottom">
            <p>© 2026 PROMIX — малярный центр в Казани</p>
            <a class="footer__policy" href="#">Политика конфиденциальности</a>
        </div>

    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
