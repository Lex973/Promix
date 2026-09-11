<?php
/**
 * Каталог: страница магазина WooCommerce и архивы разделов.
 *
 * Фильтры слева, поиск и сортировка сверху, товары в сетке. Товары уже
 * выбраны главным запросом с учётом адреса (см. inc/catalog.php), здесь
 * они только выводятся. Фильтры — обычная GET-форма: без скрипта она
 * перезагружает страницу, со скриптом результаты подменяются на месте.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

get_header();

$state      = promix_catalog_state();
$categories = promix_catalog_terms( 'product_cat', $state['cats'] );
$brands     = promix_catalog_terms( 'pa_brand', $state['brands'] );
$shop_url   = promix_catalog_url();
$found      = (int) $GLOBALS['wp_query']->found_posts;
$next_url   = $GLOBALS['wp_query']->max_num_pages > max( 1, (int) get_query_var( 'paged' ) ) ? get_next_posts_page_link() : '';

$sort_options = array(
    ''           => __( 'по умолчанию', 'promix' ),
    'price'      => __( 'сначала дешевле', 'promix' ),
    'price-desc' => __( 'сначала дороже', 'promix' ),
    'title'      => __( 'по названию', 'promix' ),
);

$total    = (int) wp_count_posts( 'product' )->publish;
$category = is_product_category() ? get_queried_object() : null;
$title    = $category instanceof WP_Term ? $category->name : __( 'Материалы и инструмент', 'promix' );
$lead     = $category instanceof WP_Term && $category->description
    ? $category->description
    : sprintf(
        /* translators: 1 — количество позиций, 2 — слово «позиция» в нужной форме. */
        __( '%1$s %2$s для малярных и отделочных работ. Не нашли нужное — спросите, привезём под заказ.', 'promix' ),
        number_format_i18n( $total ),
        promix_plural( $total, 'позиция', 'позиции', 'позиций' )
    );
?>

<section class="section catalog" data-catalog data-catalog-url="<?php echo esc_url( $shop_url ); ?>">
    <div class="container">

        <div class="catalog__head" data-catalog-head>
            <p class="kicker"><?php esc_html_e( 'Каталог', 'promix' ); ?></p>
            <h1 class="section__title"><?php echo esc_html( $title ); ?></h1>
            <p class="section__lead"><?php echo esc_html( $lead ); ?></p>
        </div>

        <form class="catalog__form" id="catalog-form" method="get" action="<?php echo esc_url( $shop_url ); ?>" data-catalog-form>

            <div class="catalog__toolbar">
                <div class="search">
                    <span class="search__icon">
                        <?php echo promix_icon( 'search', 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                    </span>
                    <label class="visually-hidden" for="catalog-search"><?php esc_html_e( 'Поиск по каталогу', 'promix' ); ?></label>
                    <input class="search__input" id="catalog-search" type="search" name="q" data-catalog-search
                           value="<?php echo esc_attr( $state['q'] ); ?>"
                           placeholder="<?php esc_attr_e( 'Название или артикул', 'promix' ); ?>" autocomplete="off">
                    <button class="search__clear" type="button" data-search-clear <?php echo $state['q'] ? '' : 'hidden'; ?>
                            aria-label="<?php esc_attr_e( 'Очистить поиск', 'promix' ); ?>">
                        <?php echo promix_icon( 'x', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                    </button>
                </div>

                <button class="btn btn--outline catalog__filters-btn" type="button"
                        data-filters-open aria-expanded="false" aria-controls="catalog-filters">
                    <?php echo promix_icon( 'sliders', 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                    <span><?php esc_html_e( 'Фильтры', 'promix' ); ?></span>
                </button>

                <div class="sort" data-sort>
                    <input type="hidden" name="orderby" value="<?php echo esc_attr( $state['orderby'] ); ?>" data-sort-input>
                    <button class="sort__btn" type="button" data-sort-toggle
                            aria-haspopup="listbox" aria-expanded="false"
                            aria-label="<?php esc_attr_e( 'Сортировка', 'promix' ); ?>">
                        <span class="sort__value" data-sort-value><?php echo esc_html( $sort_options[ $state['orderby'] ] ); ?></span>
                        <?php echo promix_icon( 'chevron-down', 2, 'sort__arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                    </button>

                    <ul class="sort__list" role="listbox" data-sort-list hidden
                        aria-label="<?php esc_attr_e( 'Способ сортировки', 'promix' ); ?>">
                        <?php foreach ( $sort_options as $value => $label ) : ?>
                            <li class="sort__option" role="option" tabindex="-1"
                                data-sort-option="<?php echo esc_attr( $value ); ?>"
                                aria-selected="<?php echo $state['orderby'] === $value ? 'true' : 'false'; ?>">
                                <span><?php echo esc_html( $label ); ?></span>
                                <?php echo promix_icon( 'check', 2.2, 'sort__check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="catalog__layout">

                <div class="filters-overlay" data-filters-overlay hidden></div>

                <aside class="filters" id="catalog-filters" data-filters data-lenis-prevent aria-label="<?php esc_attr_e( 'Фильтры каталога', 'promix' ); ?>">

                    <div class="filters__head">
                        <p class="filters__title"><?php esc_html_e( 'Фильтры', 'promix' ); ?></p>
                        <button class="filters__close" type="button" data-filters-close
                                aria-label="<?php esc_attr_e( 'Закрыть фильтры', 'promix' ); ?>">
                            <?php echo promix_icon( 'x', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                        </button>
                    </div>

                    <div class="filters__actions">
                        <button class="btn btn--primary filters__apply" type="submit" data-filters-apply>
                            <?php esc_html_e( 'Применить', 'promix' ); ?>
                        </button>
                        <a class="btn btn--outline filters__reset" href="<?php echo esc_url( $shop_url ); ?>" data-filters-reset>
                            <?php esc_html_e( 'Сбросить', 'promix' ); ?>
                        </a>
                    </div>

                    <?php
                    $groups = array(
                        'cat'   => array( __( 'Категория', 'promix' ), $categories ),
                        'brand' => array( __( 'Бренд', 'promix' ), $brands ),
                    );

                    foreach ( $groups as $field => list( $legend, $terms ) ) :
                        // Отмеченный пункт в скрытой части списка — список открыт сразу.
                        $hidden_checked = false;

                        foreach ( array_slice( $terms, 6 ) as $term ) {
                            $hidden_checked = $hidden_checked || $term['checked'];
                        }
                        ?>
                        <fieldset class="filter">
                            <legend class="filter__title"><?php echo esc_html( $legend ); ?></legend>
                            <div class="filter__list filter__list--short<?php echo $hidden_checked ? ' is-open' : ''; ?>" data-filter-more-list>
                                <?php foreach ( $terms as $term ) : ?>
                                    <label class="check">
                                        <input type="checkbox" name="<?php echo esc_attr( $field ); ?>[]"
                                               value="<?php echo esc_attr( $term['slug'] ); ?>"
                                               data-filter-<?php echo esc_attr( $field ); ?>
                                               <?php echo $term['url'] ? 'data-url="' . esc_url( $term['url'] ) . '"' : ''; ?>
                                               <?php checked( $term['checked'] ); ?>>
                                        <span class="check__text"><?php echo esc_html( $term['name'] ); ?></span>
                                        <span class="check__count"><?php echo esc_html( (string) $term['count'] ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if ( count( $terms ) > 6 ) : ?>
                                <button class="filter__more" type="button" data-filter-more aria-expanded="<?php echo $hidden_checked ? 'true' : 'false'; ?>">
                                    <?php echo $hidden_checked ? esc_html__( 'Свернуть', 'promix' ) : esc_html__( 'Показать все', 'promix' ); ?>
                                </button>
                            <?php endif; ?>
                        </fieldset>
                    <?php endforeach; ?>

                    <fieldset class="filter">
                        <legend class="filter__title"><?php esc_html_e( 'Цена, ₽', 'promix' ); ?></legend>
                        <div class="filter__price">
                            <label class="visually-hidden" for="price-min"><?php esc_html_e( 'Цена от', 'promix' ); ?></label>
                            <input class="filter__input" id="price-min" type="text" inputmode="numeric" autocomplete="off" name="min_price"
                                   value="<?php echo esc_attr( $state['min'] ); ?>"
                                   placeholder="<?php esc_attr_e( 'от', 'promix' ); ?>" data-filter-min>
                            <span class="filter__dash" aria-hidden="true">—</span>
                            <label class="visually-hidden" for="price-max"><?php esc_html_e( 'Цена до', 'promix' ); ?></label>
                            <input class="filter__input" id="price-max" type="text" inputmode="numeric" autocomplete="off" name="max_price"
                                   value="<?php echo esc_attr( $state['max'] ); ?>"
                                   placeholder="<?php esc_attr_e( 'до', 'promix' ); ?>" data-filter-max>
                        </div>
                    </fieldset>
                </aside>

                <div class="catalog__main" data-catalog-results aria-live="polite">

                    <p class="catalog__count">
                        <?php
                        printf(
                            /* translators: 1 — количество товаров, 2 — слово «товар» в нужной форме. */
                            esc_html__( 'Найдено %1$s %2$s', 'promix' ),
                            esc_html( number_format_i18n( $found ) ),
                            esc_html( promix_plural( $found, 'товар', 'товара', 'товаров' ) )
                        );
                        ?>
                    </p>

                    <?php if ( have_posts() ) : ?>
                        <div class="products" data-products>
                            <?php
                            while ( have_posts() ) :
                                the_post();
                                get_template_part( 'template-parts/catalog/card', null, array( 'product' => wc_get_product( get_the_ID() ) ) );
                            endwhile;
                            ?>
                        </div>
                    <?php else : ?>
                        <p class="products__empty">
                            <?php esc_html_e( 'По такому запросу ничего не нашлось. Попробуйте изменить фильтры или спросите у менеджера — часть позиций возим под заказ.', 'promix' ); ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( $next_url ) : ?>
                        <div class="catalog__more">
                            <a class="btn btn--outline" href="<?php echo esc_url( $next_url ); ?>" data-more>
                                <?php esc_html_e( 'Показать ещё', 'promix' ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </form>

    </div>
</section>

<?php
get_footer();
