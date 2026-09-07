<?php
/**
 * Секция главной: hero.
 *
 * @package promix
 */

?>
<section class="hero">
        <img class="hero__bg" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/main.webp' ) ); ?>" alt="" width="1536" height="1024" fetchpriority="high">
        <div class="hero__shade" aria-hidden="true"></div>

        <div class="container hero__inner">
            <h1 class="hero__title">Профессиональные материалы<br>для идеального результата</h1>

            <p class="hero__lead">Подбор и колеровка материалов, техническая консультация, комплектация объектов под ключ — для частных клиентов, мастеров, дизайнеров и строительных компаний.</p>

            <a class="hero__cta" href="#catalog">
                Перейти в каталог
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6"></path>
                </svg>
            </a>

            <dl class="hero__stats">
                <div class="hero__stat">
                    <dt>10+ лет</dt>
                    <dd>опыта в строительной сфере</dd>
                </div>
                <div class="hero__stat">
                    <dt>3 года</dt>
                    <dd>магазину PROMIX в Казани</dd>
                </div>
                <div class="hero__stat">
                    <dt>4,9</dt>
                    <dd>рейтинг в 2ГИС</dd>
                </div>
                <div class="hero__stat">
                    <dt>50+</dt>
                    <dd>отзывов клиентов</dd>
                </div>
            </dl>
        </div>
    </section>
