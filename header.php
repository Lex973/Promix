<?php
/**
 * Шапка сайта: голова документа, хедер и мобильное меню.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#DF0101">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
wp_body_open();

$promix = promix_contacts();
?>

<a class="skip-link" href="#main"><?php esc_html_e( 'Перейти к содержимому', 'promix' ); ?></a>

<header class="header" data-header>
    <div class="container header__inner">

        <?php get_template_part( 'template-parts/logo' ); ?>

        <nav class="nav" aria-label="<?php esc_attr_e( 'Основная навигация', 'promix' ); ?>">
            <?php promix_menu( 'primary', 'nav__list' ); ?>
        </nav>

        <div class="header__actions">

            <div class="header__contact">
                <a class="header__phone" href="tel:<?php echo esc_attr( promix_tel_href() ); ?>"><?php echo esc_html( $promix['phone'] ); ?></a>
                <span class="header__hours"><?php echo esc_html( $promix['hours'] ); ?></span>
            </div>

            <?php
            get_template_part(
                'template-parts/btn-max',
                null,
                array(
                    'label' => __( 'MAX', 'promix' ),
                    'aria'  => __( 'Написать менеджеру в MAX', 'promix' ),
                )
            );
            ?>

            <a class="icon-btn" href="#cart" data-count="0" aria-label="<?php esc_attr_e( 'Корзина: товаров нет', 'promix' ); ?>">
                <?php echo promix_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                <span class="icon-btn__count">0</span>
            </a>

            <button class="burger" type="button"
                    data-menu-open
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    aria-label="<?php esc_attr_e( 'Открыть меню', 'promix' ); ?>">
                <span class="burger__box" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
            </button>

        </div>
    </div>
</header>

<div class="menu-overlay" data-menu-overlay aria-hidden="true"></div>

<aside class="mobile-menu" id="mobile-menu" data-menu data-lenis-prevent aria-hidden="true" aria-label="<?php esc_attr_e( 'Мобильное меню', 'promix' ); ?>">

    <div class="mobile-menu__head">
        <?php get_template_part( 'template-parts/logo' ); ?>

        <button class="mobile-menu__close" type="button" data-menu-close aria-label="<?php esc_attr_e( 'Закрыть меню', 'promix' ); ?>">
            <?php echo promix_icon( 'x', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
        </button>
    </div>

    <nav class="mobile-menu__nav" aria-label="<?php esc_attr_e( 'Мобильная навигация', 'promix' ); ?>">
        <?php promix_menu( 'primary', 'mobile-menu__list' ); ?>
    </nav>

    <div class="mobile-menu__foot">
        <a class="mobile-menu__phone" href="tel:<?php echo esc_attr( promix_tel_href() ); ?>"><?php echo esc_html( $promix['phone'] ); ?></a>
        <p class="mobile-menu__hint"><?php echo esc_html( $promix['hours'] . ' · ' . $promix['address'] ); ?></p>
        <?php get_template_part( 'template-parts/btn-max' ); ?>
    </div>
</aside>

<main id="main">
