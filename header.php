<?php
/**
 * Шапка сайта: голова документа, хедер и мобильное меню.
 *
 * @package promix
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#DF0101">

    <meta name="description" content="PROMIX — малярный центр в Казани: краски, шпаклёвки, грунты, инструмент и оборудование. Подбор материалов, колеровка, семинары для мастеров.">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="PROMIX">
    <meta property="og:title" content="PROMIX — профессиональный малярный центр в Казани">
    <meta property="og:description" content="Краски, шпаклёвки, грунты, инструмент и оборудование для полного цикла малярных работ.">
    <meta property="og:locale" content="ru_RU">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main">Перейти к содержимому</a>

<header class="header" data-header>
    <div class="container header__inner">

        <a class="logo" href="/" aria-label="PROMIX — на главную">
            <img class="logo__mark" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo.png' ) ); ?>" alt="" width="78" height="52">
            <span class="logo__text">
                <span class="logo__name">PROMIX</span>
                <span class="logo__tagline">пространство для<br>профессионалов</span>
            </span>
        </a>

        <nav class="nav" aria-label="Основная навигация">
            <a class="nav__link" href="#catalog">Каталог</a>
            <a class="nav__link" href="#brands">Бренды</a>
            <a class="nav__link" href="#about">О компании</a>
            <a class="nav__link" href="#reviews">Отзывы</a>
            <a class="nav__link" href="#contacts">Контакты</a>
        </nav>

        <div class="header__actions">

            <div class="header__contact">
                <a class="header__phone" href="tel:+79534840000">+7 (953) 484-00-00</a>
                <span class="header__hours">Пн–Пт 9:00–18:00</span>
            </div>

            <a class="btn-max" href="#" aria-label="Написать менеджеру в MAX">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
                <span>MAX</span>
            </a>

            <a class="icon-btn" href="#cart" data-count="0" aria-label="Корзина: товаров нет">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="icon-btn__count">0</span>
            </a>

            <button class="burger" type="button"
                    data-menu-open
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    aria-label="Открыть меню">
                <span class="burger__box" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
            </button>

        </div>
    </div>
</header>

<div class="menu-overlay" data-menu-overlay aria-hidden="true"></div>

<aside class="mobile-menu" id="mobile-menu" data-menu data-lenis-prevent aria-hidden="true" aria-label="Мобильное меню">

    <div class="mobile-menu__head">
        <a class="logo" href="/" aria-label="PROMIX — на главную">
            <img class="logo__mark" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo.png' ) ); ?>" alt="" width="78" height="52">
            <span class="logo__text">
                <span class="logo__name">PROMIX</span>
                <span class="logo__tagline">пространство для<br>профессионалов</span>
            </span>
        </a>

        <button class="mobile-menu__close" type="button" data-menu-close aria-label="Закрыть меню">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <nav class="mobile-menu__nav" aria-label="Мобильная навигация">
        <a class="mobile-menu__link" href="#catalog">Каталог <span aria-hidden="true">→</span></a>
        <a class="mobile-menu__link" href="#brands">Бренды <span aria-hidden="true">→</span></a>
        <a class="mobile-menu__link" href="#about">О компании <span aria-hidden="true">→</span></a>
        <a class="mobile-menu__link" href="#reviews">Отзывы <span aria-hidden="true">→</span></a>
        <a class="mobile-menu__link" href="#contacts">Контакты <span aria-hidden="true">→</span></a>
    </nav>

    <div class="mobile-menu__foot">
        <a class="mobile-menu__phone" href="tel:+79534840000">+7 (953) 484-00-00</a>
        <p class="mobile-menu__hint">Пн–Пт 9:00–18:00 · Казань, ул. Габдуллы Тукая, 91</p>
        <a class="btn-max" href="#">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
            </svg>
            <span>Написать в MAX</span>
        </a>
    </div>
</aside>

<main id="main">
