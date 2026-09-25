<?php
/**
 * Каталог: адреса, параметры фильтров и выборка товаров.
 *
 * Страница каталога — это «магазин» WooCommerce, разделы — архивы
 * product_cat. Товары выбирает главный запрос WordPress, а здесь к нему
 * добавляются фильтры из адреса: раздел и поиск. Цена и сортировка
 * идут через штатные параметры Woo (min_price, max_price, orderby) —
 * их плагин обрабатывает сам.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

const PROMIX_CATALOG_PER_PAGE = 24;

/**
 * Путь ссылки без адреса сайта — для кэша ссылок, см. promix_catalog_url.
 *
 * @param string $url Полный адрес на этом сайте.
 * @return string Путь от корня сайта, например /catalog/instrument/.
 */
function promix_link_path( string $url ): string {
    $home = untrailingslashit( home_url() );

    return str_starts_with( $url, $home ) ? substr( $url, strlen( $home ) ) : $url;
}

/**
 * Обратно: путь из кэша — в полный адрес на текущем адресе сайта.
 *
 * @param string $path Путь от promix_link_path.
 * @return string
 */
function promix_link_url( string $path ): string {
    return str_starts_with( $path, '/' ) ? home_url( $path ) : $path;
}

/**
 * Адрес каталога или его раздела.
 *
 * Карта «название раздела → ссылка» лежит в транзиенте: get_term_by('name')
 * не кэшируется, а подвал спрашивает шесть разделов на каждой странице —
 * это было 12 запросов к базе на любой странице сайта. Сбрасывается,
 * когда разделы меняют (см. promix_catalog_links_flush).
 *
 * В транзиенте лежат пути без домена: сайт открывают и по своему адресу,
 * и через Live Link Local, и полный адрес из кэша уводил бы на чужой хост.
 *
 * @param string $category Название или слаг раздела; пустая строка — весь каталог.
 * @return string
 */
function promix_catalog_url( string $category = '' ): string {
    static $links = null;

    $url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/catalog/' );

    if ( ! $category ) {
        return $url;
    }

    if ( null === $links ) {
        $links = get_transient( 'promix_catalog_paths' );
        $links = is_array( $links ) ? $links : array();
    }

    if ( isset( $links[ $category ] ) ) {
        return promix_link_url( $links[ $category ] );
    }

    $term = get_term_by( 'name', $category, 'product_cat' ) ?: get_term_by( 'slug', $category, 'product_cat' );
    $link = $term instanceof WP_Term ? get_term_link( $term ) : null;

    $link = $link && ! is_wp_error( $link ) ? $link : add_query_arg( 'cat', rawurlencode( $category ), $url );

    $links[ $category ] = promix_link_path( $link );

    set_transient( 'promix_catalog_paths', $links, DAY_IN_SECONDS );

    return $link;
}

/**
 * Разделы или адреса поменяли — карта ссылок и счётчик товаров устарели.
 */
function promix_catalog_links_flush(): void {
    delete_transient( 'promix_catalog_paths' );
}
add_action( 'created_product_cat', 'promix_catalog_links_flush' );
add_action( 'edited_product_cat', 'promix_catalog_links_flush' );
add_action( 'delete_product_cat', 'promix_catalog_links_flush' );
add_action( 'update_option_permalink_structure', 'promix_catalog_links_flush' );
add_action( 'update_option_woocommerce_permalinks', 'promix_catalog_links_flush' );

/**
 * Своих страниц у брендов нет: бренд в каталоге убран, марка видна только
 * в карточке товара и в лентах на главной. Таксономия остаётся в админке,
 * но /brand/марка/ отдаёт 404 и в карту сайта не попадает.
 *
 * @param array<string, mixed> $args     Аргументы register_taxonomy.
 * @param string               $taxonomy Таксономия.
 * @return array<string, mixed>
 */
function promix_brand_taxonomy_args( array $args, string $taxonomy ): array {
    if ( 'product_brand' === $taxonomy ) {
        $args['publicly_queryable'] = false;
        $args['query_var']          = false;
        $args['rewrite']            = false;
    }

    return $args;
}
add_filter( 'register_taxonomy_args', 'promix_brand_taxonomy_args', 10, 2 );

/**
 * Сколько товаров видно в каталоге — для подводки «N позиций».
 *
 * wp_count_posts() считает все опубликованные, включая скрытые из каталога,
 * и разошёлся бы с «Найдено N» при первом же скрытом товаре. Считаем тем же
 * условием, что и выдача, и держим в транзиенте: товары меняются редко.
 *
 * @return int
 */
function promix_catalog_total(): int {
    $total = get_transient( 'promix_catalog_total' );

    if ( false !== $total ) {
        return (int) $total;
    }

    $query = new WP_Query(
        array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => 1,
            'no_found_rows'  => false,
            'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- то же условие, что у выдачи каталога.
                array(
                    'taxonomy' => 'product_visibility',
                    'field'    => 'slug',
                    'terms'    => array( 'exclude-from-catalog' ),
                    'operator' => 'NOT IN',
                ),
            ),
        )
    );

    $total = (int) $query->found_posts;

    set_transient( 'promix_catalog_total', $total, HOUR_IN_SECONDS );

    return $total;
}

/**
 * Товар сохранили или удалили — счётчик пересчитается при следующем показе.
 *
 * @param int $post_id Запись.
 */
function promix_catalog_total_flush( int $post_id ): void {
    if ( 'product' === get_post_type( $post_id ) ) {
        delete_transient( 'promix_catalog_total' );
    }
}
add_action( 'save_post_product', 'promix_catalog_total_flush' );
add_action( 'deleted_post', 'promix_catalog_total_flush' );
add_action( 'trashed_post', 'promix_catalog_total_flush' );
add_action( 'untrashed_post', 'promix_catalog_total_flush' );

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
    return function_exists( 'is_shop' ) && ( is_shop() || is_product_category() );
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
 * @return array{cats: string[], q: string, min: string, max: string, orderby: string}
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

    $orderby = sanitize_key( (string) wp_unslash( $_GET['orderby'] ?? '' ) );

    $state = array(
        'cats'    => $cats,
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
 * tax_query по видимости и цене, сюда добавляются раздел и поиск.
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
 * Пункты фильтра по разделам каталога — деревом.
 *
 * Разделы двухуровневые («Инструмент → Кисти»), и плоский список по числу
 * товаров эту связь теряет. Здесь родители идут в порядке, заданном при
 * заведении разделов (мета `order`), подразделы лежат в `children` своего
 * родителя. `open` — внутри что-то отмечено, и в фильтре раздел
 * показывается сразу раскрытым.
 *
 * @param string[] $checked Отмеченные слаги.
 * @return array<int, array{slug: string, name: string, count: int, url: string, checked: bool, open: bool, children: array}>
 */
function promix_catalog_tree( array $checked ): array {
    $terms = get_terms(
        array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
        )
    );

    if ( is_wp_error( $terms ) ) {
        return array();
    }

    $children = array();

    foreach ( $terms as $term ) {
        $children[ (int) $term->parent ][] = $term;
    }

    /*
     * Порядок из меты `order` — её ставит WooCommerce и наш wc-recategorize.php.
     * Сортируем уже здесь, а не запросом: с meta_key разделы без этой меты
     * (например, заведённые импортом CSV) выпали бы из фильтра совсем.
     */
    foreach ( $children as &$group ) {
        usort(
            $group,
            static function ( WP_Term $a, WP_Term $b ): int {
                $order = (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true );

                return $order ?: strcoll( $a->name, $b->name );
            }
        );
    }

    unset( $group );

    $walk = static function ( int $parent ) use ( &$walk, $children, $checked ): array {
        $items = array();

        foreach ( $children[ $parent ] ?? array() as $term ) {
            $link = get_term_link( $term );
            $kids = $walk( (int) $term->term_id );

            $items[] = array(
                'slug'     => $term->slug,
                'name'     => $term->name,
                'count'    => (int) $term->count,
                'url'      => is_wp_error( $link ) ? '' : $link,
                'checked'  => in_array( $term->slug, $checked, true ),
                'open'     => (bool) array_filter( $kids, static fn ( array $kid ): bool => $kid['checked'] || $kid['open'] ),
                'children' => $kids,
            );
        }

        return $items;
    };

    return $walk( 0 );
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
