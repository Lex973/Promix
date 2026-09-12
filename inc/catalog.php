<?php
/**
 * Каталог: адреса, параметры фильтров и выборка товаров.
 *
 * Страница каталога — это «магазин» WooCommerce, разделы — архивы
 * product_cat. Товары выбирает главный запрос WordPress, а здесь к нему
 * добавляются фильтры из адреса: раздел, бренд, поиск. Цена и сортировка
 * идут через штатные параметры Woo (min_price, max_price, orderby) —
 * их плагин обрабатывает сам.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

const PROMIX_CATALOG_PER_PAGE = 24;

/**
 * Адрес каталога или его раздела.
 *
 * @param string $category Название или слаг раздела; пустая строка — весь каталог.
 * @return string
 */
function promix_catalog_url( string $category = '' ): string {
    static $cache = array();

    $url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/catalog/' );

    if ( ! $category ) {
        return $url;
    }

    // get_term_by('name') не кэшируется, а подвал спрашивает шесть разделов на каждой странице.
    if ( isset( $cache[ $category ] ) ) {
        return $cache[ $category ];
    }

    $term = get_term_by( 'name', $category, 'product_cat' ) ?: get_term_by( 'slug', $category, 'product_cat' );
    $link = $term instanceof WP_Term ? get_term_link( $term ) : null;

    $cache[ $category ] = $link && ! is_wp_error( $link ) ? $link : add_query_arg( 'cat', rawurlencode( $category ), $url );

    return $cache[ $category ];
}

/**
 * Цена словами: «2 890 ₽», «Цена по запросу» для нуля.
 *
 * Одно место на каталог, товар, корзину и мета-теги — и одно место,
 * где решается, что делать с позицией без цены из следующей выгрузки 1С.
 *
 * @param float $price Цена.
 * @return string
 */
function promix_price( float $price ): string {
    if ( $price <= 0 ) {
        return __( 'Цена по запросу', 'promix' );
    }

    return number_format_i18n( $price ) . ' ₽';
}

/**
 * Это страница каталога или его раздела?
 */
function promix_is_catalog(): bool {
    return function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_tax( 'product_brand' ) );
}

/**
 * Список слагов из параметра адреса: и `cat=a,b`, и `cat[]=a&cat[]=b`.
 *
 * @param mixed $raw Значение из $_GET.
 * @return string[]
 */
function promix_catalog_slugs( $raw ): array {
    $values = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
    $values = array_map( 'sanitize_title', array_map( 'wp_unslash', $values ) );

    return array_values( array_unique( array_filter( $values ) ) );
}

/**
 * Что выбрано в фильтрах сейчас.
 *
 * @return array{cats: string[], brands: string[], q: string, min: string, max: string, orderby: string}
 */
function promix_catalog_state(): array {
    static $state = null;

    if ( null !== $state ) {
        return $state;
    }

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- фильтры каталога, ничего не меняют.
    $cats = promix_catalog_slugs( $_GET['cat'] ?? '' );

    // На странице раздела он и есть выбранный фильтр.
    if ( is_product_category() ) {
        $term = get_queried_object();

        if ( $term instanceof WP_Term ) {
            $cats = array( $term->slug );
        }
    }

    $brands = promix_catalog_slugs( $_GET['brand'] ?? '' );

    // На странице бренда он и есть выбранный фильтр.
    if ( is_tax( 'product_brand' ) ) {
        $term = get_queried_object();

        if ( $term instanceof WP_Term ) {
            $brands = array( $term->slug );
        }
    }

    $orderby = sanitize_key( (string) wp_unslash( $_GET['orderby'] ?? '' ) );

    $state = array(
        'cats'    => $cats,
        'brands'  => $brands,
        'q'       => trim( sanitize_text_field( (string) wp_unslash( $_GET['q'] ?? '' ) ) ),
        'min'     => isset( $_GET['min_price'] ) && '' !== $_GET['min_price'] ? (string) absint( $_GET['min_price'] ) : '',
        'max'     => isset( $_GET['max_price'] ) && '' !== $_GET['max_price'] ? (string) absint( $_GET['max_price'] ) : '',
        'orderby' => in_array( $orderby, array( 'price', 'price-desc', 'title' ), true ) ? $orderby : '',
    );
    // phpcs:enable

    return $state;
}

/**
 * Фильтры из адреса — в главный запрос товаров.
 *
 * Работает после WC_Query::product_query (приоритет 10): тот уже собрал
 * tax_query по видимости и цене, сюда добавляются раздел, бренд и поиск.
 *
 * @param WP_Query $query Запрос.
 */
function promix_catalog_query( WP_Query $query ): void {
    if ( is_admin() || ! $query->is_main_query() || ! promix_is_catalog() ) {
        return;
    }

    $state     = promix_catalog_state();
    $tax_query = (array) $query->get( 'tax_query' );

    // На странице раздела WordPress уже ограничил выборку этим разделом.
    if ( $state['cats'] && ! is_product_category() ) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $state['cats'],
        );
    }

    if ( $state['brands'] && ! is_tax( 'product_brand' ) ) {
        $tax_query[] = array(
            'taxonomy' => 'product_brand',
            'field'    => 'slug',
            'terms'    => $state['brands'],
        );
    }

    $query->set( 'tax_query', $tax_query );

    /*
     * Поиск — через хранилище Woo: оно ищет и по названию, и по артикулу.
     * Пустой результат отдаёт [0], чтобы post__in не превратился в «все».
     */
    if ( $state['q'] ) {
        $ids = WC_Data_Store::load( 'product' )->search_products( $state['q'], '', false, false );

        $query->set( 'post__in', $ids ? $ids : array( 0 ) );
    }
}
add_action( 'pre_get_posts', 'promix_catalog_query', 20 );

/**
 * Поля цены из адреса: только цифры, пустые — как будто их нет.
 *
 * Woo считает max_price= нулём и не находит ничего; чистим до того,
 * как WC_Query::product_query (приоритет 10) прочитает $_GET.
 */
function promix_catalog_empty_prices( WP_Query $query ): void {
    if ( is_admin() || ! $query->is_main_query() || ! promix_is_catalog() ) {
        return;
    }

    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    foreach ( array( 'min_price', 'max_price' ) as $key ) {
        if ( ! isset( $_GET[ $key ] ) ) {
            continue;
        }

        // Без скрипта поле приходит как есть — пробелы и прочее убираем здесь.
        $digits = preg_replace( '/\D+/', '', (string) wp_unslash( $_GET[ $key ] ) );

        if ( '' === $digits ) {
            unset( $_GET[ $key ] );
        } else {
            $_GET[ $key ] = $digits;
        }
    }
    // phpcs:enable
}
add_action( 'pre_get_posts', 'promix_catalog_empty_prices', 5 );

/**
 * Товаров на страницу.
 */
function promix_catalog_per_page(): int {
    return PROMIX_CATALOG_PER_PAGE;
}
add_filter( 'loop_shop_per_page', 'promix_catalog_per_page' );

/**
 * Пункты фильтра по таксономии: слаг, название, число товаров, отмечен ли.
 *
 * @param string   $taxonomy Таксономия.
 * @param string[] $checked  Отмеченные слаги.
 * @return array<int, array{slug: string, name: string, count: int, url: string, checked: bool}>
 */
function promix_catalog_terms( string $taxonomy, array $checked ): array {
    $terms = get_terms(
        array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
        )
    );

    if ( is_wp_error( $terms ) ) {
        return array();
    }

    $items = array();

    foreach ( $terms as $term ) {
        $link = get_term_link( $term );

        $items[] = array(
            'slug'    => $term->slug,
            'name'    => $term->name,
            'count'   => (int) $term->count,
            'url'     => is_wp_error( $link ) ? '' : $link,
            'checked' => in_array( $term->slug, $checked, true ),
        );
    }

    return $items;
}

/**
 * Первый раздел товара — по нему подбирается иконка вместо фото.
 *
 * @param WC_Product $product Товар.
 * @return string Название раздела или пустая строка.
 */
function promix_product_category( WC_Product $product ): string {
    $terms = get_the_terms( $product->get_id(), 'product_cat' );

    if ( ! $terms || is_wp_error( $terms ) ) {
        return '';
    }

    return $terms[0]->name;
}

/**
 * Бренд товара — из штатной таксономии брендов WooCommerce («Товары → Бренды»).
 *
 * get_the_terms() берёт из кэша, который WP_Query уже прогрел для всей
 * страницы, — ни одного лишнего запроса на карточку.
 *
 * @param WC_Product $product Товар.
 * @return string
 */
function promix_product_brand( WC_Product $product ): string {
    $terms = get_the_terms( $product->get_id(), 'product_brand' );

    if ( ! $terms || is_wp_error( $terms ) ) {
        return '';
    }

    return $terms[0]->name;
}
