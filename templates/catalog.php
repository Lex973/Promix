<?php
/**
 * Template Name: Каталог PROMIX
 *
 * Вёрстка каталога: фильтры слева, поиск и сортировка сверху, товары в сетке.
 * Товары пока берутся из inc/catalog-demo.php — на их место встанет выборка
 * WooCommerce, разметка карточки от этого не изменится.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

get_header();

$products   = promix_demo_products();
$categories = promix_demo_categories();
$brands     = promix_demo_brands();
$total      = array_sum( $categories );
?>

<section class="section catalog">
    <div class="container">

        <div class="catalog__head">
            <p class="kicker"><?php esc_html_e( 'Каталог', 'promix' ); ?></p>
            <h1 class="section__title"><?php esc_html_e( 'Материалы и инструмент', 'promix' ); ?></h1>
            <p class="section__lead">
                <?php
                printf(
                    /* translators: %s — количество позиций. */
                    esc_html__( '%s позиций для малярных и отделочных работ. Не нашли нужное — спросите, привезём под заказ.', 'promix' ),
                    esc_html( number_format_i18n( $total ) )
                );
                ?>
            </p>
        </div>

        <div class="catalog__toolbar">
            <div class="search">
                <span class="search__icon">
                    <?php echo promix_icon( 'search', 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                </span>
                <label class="visually-hidden" for="catalog-search"><?php esc_html_e( 'Поиск по каталогу', 'promix' ); ?></label>
                <input class="search__input" id="catalog-search" type="search" data-catalog-search
                       placeholder="<?php esc_attr_e( 'Название или артикул', 'promix' ); ?>" autocomplete="off">
                <button class="search__clear" type="button" data-search-clear hidden
                        aria-label="<?php esc_attr_e( 'Очистить поиск', 'promix' ); ?>">
                    <?php echo promix_icon( 'x', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                </button>
            </div>

            <button class="btn btn--outline catalog__filters-btn" type="button"
                    data-filters-open aria-expanded="false" aria-controls="catalog-filters">
                <?php echo promix_icon( 'sliders', 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                <span><?php esc_html_e( 'Фильтры', 'promix' ); ?></span>
            </button>

            <div class="sort">
                <label class="sort__label" for="catalog-sort"><?php esc_html_e( 'Сортировка', 'promix' ); ?></label>
                <select class="sort__select" id="catalog-sort" data-catalog-sort>
                    <option value="default"><?php esc_html_e( 'по умолчанию', 'promix' ); ?></option>
                    <option value="price-asc"><?php esc_html_e( 'сначала дешевле', 'promix' ); ?></option>
                    <option value="price-desc"><?php esc_html_e( 'сначала дороже', 'promix' ); ?></option>
                    <option value="name"><?php esc_html_e( 'по названию', 'promix' ); ?></option>
                </select>
            </div>
        </div>

        <div class="catalog__layout">

            <div class="filters-overlay" data-filters-overlay hidden></div>

            <aside class="filters" id="catalog-filters" data-filters aria-label="<?php esc_attr_e( 'Фильтры каталога', 'promix' ); ?>">

                <div class="filters__head">
                    <p class="filters__title"><?php esc_html_e( 'Фильтры', 'promix' ); ?></p>
                    <button class="filters__close" type="button" data-filters-close
                            aria-label="<?php esc_attr_e( 'Закрыть фильтры', 'promix' ); ?>">
                        <?php echo promix_icon( 'x', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                    </button>
                </div>

                <fieldset class="filter">
                    <legend class="filter__title"><?php esc_html_e( 'Категория', 'promix' ); ?></legend>
                    <div class="filter__list">
                        <?php foreach ( $categories as $name => $count ) : ?>
                            <label class="check">
                                <input type="checkbox" name="cat" value="<?php echo esc_attr( $name ); ?>" data-filter-cat>
                                <span class="check__text"><?php echo esc_html( $name ); ?></span>
                                <span class="check__count"><?php echo esc_html( (string) $count ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="filter">
                    <legend class="filter__title"><?php esc_html_e( 'Бренд', 'promix' ); ?></legend>
                    <div class="filter__list filter__list--short" data-filter-more-list>
                        <?php foreach ( $brands as $name => $count ) : ?>
                            <label class="check">
                                <input type="checkbox" name="brand" value="<?php echo esc_attr( $name ); ?>" data-filter-brand>
                                <span class="check__text"><?php echo esc_html( $name ); ?></span>
                                <span class="check__count"><?php echo esc_html( (string) $count ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button class="filter__more" type="button" data-filter-more aria-expanded="false">
                        <?php esc_html_e( 'Показать все', 'promix' ); ?>
                    </button>
                </fieldset>

                <fieldset class="filter">
                    <legend class="filter__title"><?php esc_html_e( 'Цена, ₽', 'promix' ); ?></legend>
                    <div class="filter__price">
                        <label class="visually-hidden" for="price-min"><?php esc_html_e( 'Цена от', 'promix' ); ?></label>
                        <input class="filter__input" id="price-min" type="number" inputmode="numeric" min="0"
                               placeholder="<?php esc_attr_e( 'от', 'promix' ); ?>" data-filter-min>
                        <span class="filter__dash" aria-hidden="true">—</span>
                        <label class="visually-hidden" for="price-max"><?php esc_html_e( 'Цена до', 'promix' ); ?></label>
                        <input class="filter__input" id="price-max" type="number" inputmode="numeric" min="0"
                               placeholder="<?php esc_attr_e( 'до', 'promix' ); ?>" data-filter-max>
                    </div>
                </fieldset>

                <div class="filters__foot">
                    <button class="btn btn--outline filters__reset" type="button" data-filters-reset>
                        <?php esc_html_e( 'Сбросить', 'promix' ); ?>
                    </button>
                    <button class="btn btn--primary filters__apply" type="button" data-filters-close>
                        <?php esc_html_e( 'Показать', 'promix' ); ?>
                    </button>
                </div>
            </aside>

            <div class="catalog__main">

                <p class="catalog__count" data-catalog-count aria-live="polite">
                    <?php
                    $shown = count( $products );

                    printf(
                        /* translators: 1 — количество товаров, 2 — слово «товар» в нужной форме. */
                        esc_html__( 'Показано %1$s %2$s', 'promix' ),
                        esc_html( (string) $shown ),
                        esc_html( promix_plural( $shown, 'товар', 'товара', 'товаров' ) )
                    );
                    ?>
                </p>

                <div class="products" data-products>
                    <?php foreach ( $products as $product ) : ?>
                        <?php get_template_part( 'template-parts/catalog/card', null, array( 'product' => $product ) ); ?>
                    <?php endforeach; ?>
                </div>

                <p class="products__empty" data-products-empty hidden>
                    <?php esc_html_e( 'По такому запросу ничего не нашлось. Попробуйте изменить фильтры или спросите у менеджера — часть позиций возим под заказ.', 'promix' ); ?>
                </p>

                <div class="catalog__more">
                    <button class="btn btn--outline" type="button">
                        <?php esc_html_e( 'Показать ещё', 'promix' ); ?>
                    </button>
                </div>
            </div>
        </div>

    </div>
</section>

<?php
get_footer();
