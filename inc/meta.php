<?php
/**
 * Мета-теги страницы: описание для поиска и Open Graph для мессенджеров.
 *
 * Раньше они были прибиты в header.php, поэтому любая внутренняя страница
 * представлялась главной: и в выдаче, и в предпросмотре ссылки.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Описание страницы.
 *
 * На 404 и в поиске описания нет: там нечего описывать, а выдуманный текст
 * попадёт в поисковую выдачу.
 *
 * @return string Пустая строка, если описание не нужно.
 */
function promix_meta_description(): string {
    if ( is_404() || is_search() ) {
        return '';
    }

    if ( is_front_page() ) {
        return promix_option(
            'meta_description',
            'Малярный центр PROMIX в Казани: 500+ товаров для маляров и отделочников — краски, шпаклёвки, шпатели, валики, оборудование. Выгодные цены, колеровка, семинары для мастеров. Тукая, 91к1.'
        );
    }

    // Товар: краткое описание, если его писал человек, иначе — название и цена.
    if ( function_exists( 'is_product' ) && is_product() ) {
        $product = wc_get_product( get_queried_object_id() );

        if ( $product instanceof WC_Product ) {
            $short = trim( wp_strip_all_tags( $product->get_short_description(), true ) );

            if ( '' !== $short && ! promix_is_import_excerpt( $short ) ) {
                return wp_trim_words( $short, 28, '…' );
            }

            // «Цена по запросу» стоит посреди фразы — со строчной.
            $price = promix_product_price( $product );
            $price = (float) $product->get_price() > 0 ? $price : mb_strtolower( $price );

            return sprintf(
                /* translators: 1 — название, 2 — цена. */
                __( '%1$s: %2$s. Купить в малярном центре PROMIX в Казани — самовывоз с Габдуллы Тукая, 91к1, заказ на сайте или по телефону.', 'promix' ),
                $product->get_name(),
                $price
            );
        }
    }

    // Раздел каталога: поле «Описание в поиске», иначе шаблон по данным раздела.
    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $term = get_queried_object();

        if ( $term instanceof WP_Term ) {
            $manual = trim( (string) get_term_meta( $term->term_id, '_promix_seo_description', true ) );

            return '' !== $manual ? $manual : promix_term_description( $term );
        }
    }

    if ( function_exists( 'is_shop' ) && is_shop() ) {
        $total = promix_catalog_total();

        return sprintf(
            /* translators: 1 — число товаров, 2 — слово «товар». */
            __( '%1$s %2$s для малярных и отделочных работ: краски, шпаклёвки, шпатели, валики, ленты, абразивы, оборудование. Выгодные цены, самовывоз и доставка по Казани.', 'promix' ),
            number_format_i18n( $total ),
            promix_plural( $total, __( 'товар', 'promix' ), __( 'товара', 'promix' ), __( 'товаров', 'promix' ) )
        );
    }

    if ( is_singular() ) {
        $post = get_queried_object();

        if ( $post instanceof WP_Post ) {
            $text = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
            $text = wp_strip_all_tags( strip_shortcodes( (string) $text ), true );

            if ( '' !== trim( $text ) ) {
                return wp_trim_words( $text, 28, '…' );
            }
        }
    }

    return (string) get_bloginfo( 'description' );
}

/**
 * Число товаров раздела вместе с подразделами.
 *
 * $term->count считает только товары, привязанные к самому разделу, а у
 * корневых разделов своих товаров нет — выходило «0 позиций». Woo хранит
 * счёт с подразделами в мете термина; если её ещё нет, складываем сами.
 *
 * @param WP_Term $term Раздел каталога.
 * @return int
 */
function promix_term_total( WP_Term $term ): int {
    $meta = get_term_meta( $term->term_id, 'product_count_' . $term->taxonomy, true );

    if ( '' !== $meta && false !== $meta ) {
        return (int) $meta;
    }

    $total = (int) $term->count;

    foreach ( (array) get_term_children( $term->term_id, $term->taxonomy ) as $child_id ) {
        $child = get_term( (int) $child_id, $term->taxonomy );
        $total += $child instanceof WP_Term ? (int) $child->count : 0;
    }

    return $total;
}

/**
 * Краткое описание из импорта: «Укрывной материал RoxelPro. Раздел «…», артикул 336372.»
 * Для поиска в нём нет ничего полезного — вместо него описание собирается из цены.
 *
 * @param string $text Краткое описание без тегов.
 * @return bool
 */
function promix_is_import_excerpt( string $text ): bool {
    return (bool) preg_match( '/Раздел «[^»]+», артикул \S+\.?$/u', $text );
}

/**
 * H1 раздела: поле «Заголовок на странице», иначе название.
 *
 * @param WP_Term $term Раздел.
 * @return string
 */
function promix_term_h1( WP_Term $term ): string {
    $h1 = trim( (string) get_term_meta( $term->term_id, '_promix_seo_h1', true ) );

    return '' !== $h1 ? $h1 : $term->name;
}

/**
 * Самая низкая цена и самые частые марки раздела вместе с подразделами.
 *
 * Два запроса к базе на раздел, поэтому результат живёт в транзиенте; любое
 * сохранение товара меняет версию, и старые записи больше не читаются.
 *
 * @param WP_Term $term Раздел.
 * @return array{min: float, brands: string[]}
 */
function promix_term_stats( WP_Term $term ): array {
    global $wpdb;

    $key   = 'promix_term_stats_' . $term->term_id . '_' . (int) get_option( 'promix_term_stats_ver', 1 );
    $stats = get_transient( $key );

    if ( is_array( $stats ) ) {
        return $stats;
    }

    $ids    = array_merge( array( $term->term_id ), array_map( 'intval', (array) get_term_children( $term->term_id, $term->taxonomy ) ) );
    $tt_ids = get_terms(
        array(
            'taxonomy'   => $term->taxonomy,
            'include'    => $ids,
            'fields'     => 'tt_ids',
            'hide_empty' => false,
        )
    );
    $stats  = array( 'min' => 0.0, 'brands' => array() );

    if ( is_array( $tt_ids ) && $tt_ids ) {
        $in = implode( ',', array_map( 'intval', $tt_ids ) );

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- в $in только целые числа.
        $stats['min'] = (float) $wpdb->get_var(
            "SELECT MIN(l.min_price) FROM {$wpdb->prefix}wc_product_meta_lookup l
             INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = l.product_id
             INNER JOIN {$wpdb->posts} p ON p.ID = l.product_id
             WHERE tr.term_taxonomy_id IN ({$in}) AND p.post_status = 'publish' AND l.min_price > 0"
        );

        $stats['brands'] = array_map(
            'strval',
            (array) $wpdb->get_col(
                "SELECT t.name FROM {$wpdb->term_relationships} tr
                 INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id AND p.post_type = 'product' AND p.post_status = 'publish'
                 INNER JOIN {$wpdb->term_relationships} tb ON tb.object_id = p.ID
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tb.term_taxonomy_id AND tt.taxonomy = 'product_brand'
                 INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
                 WHERE tr.term_taxonomy_id IN ({$in})
                 GROUP BY t.term_id, t.name ORDER BY COUNT(*) DESC, t.name ASC LIMIT 3"
            )
        );
        // phpcs:enable
    }

    set_transient( $key, $stats, 12 * HOUR_IN_SECONDS );

    return $stats;
}

/**
 * Товар сохранили или удалили — цены и марки разделов пересчитаются.
 *
 * @param int $post_id Запись.
 */
function promix_term_stats_flush( int $post_id ): void {
    if ( 'product' === get_post_type( $post_id ) ) {
        update_option( 'promix_term_stats_ver', (int) get_option( 'promix_term_stats_ver', 1 ) + 1, false );
    }
}
add_action( 'save_post_product', 'promix_term_stats_flush' );
add_action( 'deleted_post', 'promix_term_stats_flush' );
add_action( 'trashed_post', 'promix_term_stats_flush' );
add_action( 'untrashed_post', 'promix_term_stats_flush' );

/**
 * Описание раздела по шаблону.
 *
 * Корень: число позиций и три крупных подраздела. Подраздел: число, цена «от»
 * и марки — то, что человек сравнивает в выдаче.
 *
 * @param WP_Term $term Раздел.
 * @return string
 */
function promix_term_description( WP_Term $term ): string {
    $h1    = promix_term_h1( $term );
    $total = promix_term_total( $term );
    $count = number_format_i18n( $total ) . ' ' . promix_plural( $total, __( 'позиция', 'promix' ), __( 'позиции', 'promix' ), __( 'позиций', 'promix' ) );

    if ( 0 === (int) $term->parent ) {
        $children = get_terms(
            array(
                'taxonomy' => $term->taxonomy,
                'parent'   => $term->term_id,
                'orderby'  => 'count',
                'order'    => 'DESC',
                'number'   => 3,
            )
        );
        // «Шпатели и правила» посреди фразы — со строчной; «СИЗ» и марки не трогаем.
        $names = is_array( $children ) ? array_map(
            static function ( WP_Term $child ): string {
                $second = mb_substr( $child->name, 1, 1 );

                return mb_strtolower( $second ) === $second ? mb_strtolower( mb_substr( $child->name, 0, 1 ) ) . mb_substr( $child->name, 1 ) : $child->name;
            },
            $children
        ) : array();

        return sprintf(
            /* translators: 1 — H1 раздела, 2 — «192 позиции», 3 — подразделы через запятую. */
            __( '%1$s: %2$s с ценами — %3$s. Магазин PROMIX на Габдуллы Тукая, 91к1.', 'promix' ),
            $h1,
            $count,
            implode( ', ', $names )
        );
    }

    $stats = promix_term_stats( $term );
    $text  = $stats['min'] > 0
        /* translators: 1 — H1 раздела, 2 — «13 позиций», 3 — цена. */
        ? sprintf( __( '%1$s в PROMIX: %2$s от %3$s.', 'promix' ), $h1, $count, promix_price( $stats['min'] ) )
        /* translators: 1 — H1 раздела, 2 — «13 позиций». */
        : sprintf( __( '%1$s в PROMIX: %2$s с ценами.', 'promix' ), $h1, $count );

    if ( $stats['brands'] ) {
        /* translators: %s — марки через запятую. */
        $text .= ' ' . sprintf( __( 'Бренды: %s.', 'promix' ), implode( ', ', $stats['brands'] ) );
    }

    return $text . ' ' . __( 'Самовывоз из магазина на Габдуллы Тукая.', 'promix' );
}

/**
 * Заголовок вкладки и первой строки в выдаче.
 *
 * Штатный «Название — PROMIX» не говорит ни что продаём, ни где: главная,
 * каталог, разделы и товары получают запрос и город. Остальные страницы —
 * как у WordPress.
 *
 * @param string $title Пусто — WordPress соберёт сам.
 * @return string
 */
function promix_document_title( string $title ): string {
    $is_catalog = function_exists( 'promix_is_catalog' ) && promix_is_catalog();

    if ( is_front_page() ) {
        $title = promix_option( 'meta_title', 'PROMIX — малярный центр в Казани: краски, инструмент, колеровка' );
    } elseif ( function_exists( 'is_product_category' ) && is_product_category() && get_queried_object() instanceof WP_Term ) {
        $term   = get_queried_object();
        $manual = trim( (string) get_term_meta( $term->term_id, '_promix_seo_title', true ) );
        $h1     = promix_term_h1( $term );

        if ( '' !== $manual ) {
            $title = $manual;
        } elseif ( 0 === (int) $term->parent ) {
            /* translators: %s — H1 раздела. */
            $title = sprintf( __( '%s в Казани — цены в PROMIX', 'promix' ), $h1 );
        } else {
            /* translators: %s — H1 раздела. */
            $title = sprintf( __( '%s купить в Казани — PROMIX', 'promix' ), $h1 );
        }
    } elseif ( function_exists( 'is_shop' ) && is_shop() ) {
        $title = __( 'PROMIX — каталог малярного инструмента и красок в Казани', 'promix' );
    } elseif ( function_exists( 'is_product' ) && is_product() ) {
        $name = get_the_title( get_queried_object_id() );
        $name = html_entity_decode( wp_strip_all_tags( $name ), ENT_QUOTES, 'UTF-8' );

        // Длинное название из 1С и так съест строку выдачи — хвост с городом не влезет.
        /* translators: %s — название товара. */
        $title = mb_strlen( $name ) <= 44 ? sprintf( __( '%s — купить в Казани, PROMIX', 'promix' ), $name ) : sprintf( __( '%s — PROMIX', 'promix' ), $name );
    } else {
        return $title;
    }

    $paged = max( 1, (int) get_query_var( 'paged' ) );

    if ( $is_catalog && $paged > 1 ) {
        /* translators: %d — номер страницы. */
        $title .= ' ' . sprintf( __( '— страница %d', 'promix' ), $paged );
    }

    return esc_html( $title );
}
add_filter( 'pre_get_document_title', 'promix_document_title' );

/**
 * Наличие на сайте не показывается — «подтвердит менеджер», — поэтому и в
 * разметке его нет: InStock у всех противоречил бы странице.
 *
 * @param array<string, mixed> $offer Разметка предложения.
 * @return array<string, mixed>
 */
function promix_structured_offer( array $offer ): array {
    unset( $offer['availability'] );

    return $offer;
}
add_filter( 'woocommerce_structured_data_product_offer', 'promix_structured_offer' );

/**
 * Картинка для предпросмотра ссылки.
 *
 * Порядок такой: заданная в настройках, потом изображение записи,
 * потом общая картинка 1200×630 — чтобы ссылка никогда не уходила голой.
 * Логотип для этого не годится: он маленький и с прозрачным фоном,
 * который мессенджеры заливают чёрным.
 *
 * @return string
 */
function promix_og_image(): string {
    $image_id = function_exists( 'promix_option' ) ? (int) promix_option( 'og_image' ) : 0;

    if ( $image_id ) {
        $src = wp_get_attachment_image_url( $image_id, 'full' );

        if ( $src ) {
            return $src;
        }
    }

    if ( is_singular() && has_post_thumbnail() ) {
        return (string) get_the_post_thumbnail_url( null, 'full' );
    }

    return get_theme_file_uri( 'assets/img/og-default.jpg' );
}

/**
 * Канонический адрес страницы.
 *
 * @return string
 */
function promix_canonical_url(): string {
    if ( is_front_page() ) {
        return home_url( '/' );
    }

    // Каталог с фильтрами каноничен без параметров: адрес раздела, бренда
    // или самого каталога. Страницы пагинации — сами себе канонические:
    // иначе поисковик считает вторую и дальше копиями первой.
    if ( function_exists( 'promix_is_catalog' ) && promix_is_catalog() ) {
        return promix_catalog_page_url( max( 1, (int) get_query_var( 'paged' ) ) );
    }

    if ( is_singular() ) {
        $url = wp_get_canonical_url();

        if ( $url ) {
            return $url;
        }
    }

    return home_url( add_query_arg( array() ) );
}

/**
 * Адрес N-й страницы текущего раздела каталога без параметров фильтров.
 *
 * @param int $page Номер страницы, с единицы.
 * @return string
 */
function promix_catalog_page_url( int $page ): string {
    global $wp_rewrite;

    $term = is_product_category() ? get_queried_object() : null;
    $base = $term instanceof WP_Term ? get_term_link( $term ) : promix_catalog_url();
    $base = is_wp_error( $base ) ? promix_catalog_url() : $base;

    if ( $page <= 1 ) {
        return $base;
    }

    if ( $wp_rewrite instanceof WP_Rewrite && $wp_rewrite->using_permalinks() ) {
        return user_trailingslashit( trailingslashit( $base ) . $wp_rewrite->pagination_base . '/' . $page, 'paged' );
    }

    return add_query_arg( 'paged', $page, $base );
}

/**
 * Каталог с параметрами фильтров — это не отдельная страница для поиска.
 *
 * Без этого поисковик получит бесконечные сочетания брендов, цен и
 * сортировок с одинаковым содержимым.
 */
function promix_is_filtered_catalog(): bool {
    if ( ! function_exists( 'promix_is_catalog' ) || ! promix_is_catalog() ) {
        return false;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- только проверяем наличие параметров.
    return (bool) array_intersect_key( $_GET, array_flip( array( 'q', 'cat', 'brand', 'min_price', 'max_price', 'orderby' ) ) );
}

/**
 * Печать мета-тегов в head.
 *
 * @return void
 */
function promix_meta_tags(): void {
    // Разделитель тысяч в русской локали — сущность &nbsp;; в описании нужен обычный пробел.
    $description = str_replace( array( '&nbsp;', "\u{a0}" ), ' ', promix_meta_description() );
    $title       = wp_get_document_title();
    $is_product  = function_exists( 'is_product' ) && is_product();

    // Страницы серии каталога различаются и описанием, а не только заголовком.
    if ( $description && function_exists( 'promix_is_catalog' ) && promix_is_catalog() && (int) get_query_var( 'paged' ) > 1 ) {
        /* translators: %d — номер страницы. */
        $description .= ' ' . sprintf( __( 'Страница %d.', 'promix' ), (int) get_query_var( 'paged' ) );
    }

    if ( $description ) {
        printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
    }

    // Подтверждение прав в Яндекс Вебмастере: робот ищет тег только на главной.
    if ( is_front_page() ) {
        echo '<meta name="yandex-verification" content="1d6b6ff77d4ba2ca">' . "\n";
    }

    // WordPress ставит canonical только записям; каталогу и разделам — сами.
    // Соседние страницы выдачи — через prev/next, чтобы поисковик прошёл всю серию.
    if ( function_exists( 'promix_is_catalog' ) && promix_is_catalog() ) {
        $paged = max( 1, (int) get_query_var( 'paged' ) );
        $pages = (int) $GLOBALS['wp_query']->max_num_pages;

        printf( '<link rel="canonical" href="%s">' . "\n", esc_url( promix_canonical_url() ) );

        // С параметрами фильтров серия другая, и она noindex — соседей не печатаем.
        if ( promix_is_filtered_catalog() ) {
            $pages = 1;
            $paged = 1;
        }

        if ( $paged > 1 ) {
            printf( '<link rel="prev" href="%s">' . "\n", esc_url( promix_catalog_page_url( $paged - 1 ) ) );
        }

        if ( $paged < $pages ) {
            printf( '<link rel="next" href="%s">' . "\n", esc_url( promix_catalog_page_url( $paged + 1 ) ) );
        }
    }

    if ( $is_product ) {
        $og_type = 'product';
    } elseif ( is_singular() && ! is_front_page() ) {
        $og_type = 'article';
    } else {
        $og_type = 'website';
    }

    printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $og_type ) );
    printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
    printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
    printf( '<meta property="og:url" content="%s">' . "\n", esc_url( promix_canonical_url() ) );
    printf( '<meta property="og:image" content="%s">' . "\n", esc_url( promix_og_image() ) );
    printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) );

    if ( $description ) {
        printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
    }
}
add_action( 'wp_head', 'promix_meta_tags', 5 );

/**
 * Логотип PROMIX на вкладке, пока отдельная иконка не задана в WordPress.
 */
function promix_favicon(): void {
    if ( has_site_icon() ) {
        return;
    }

    $url = add_query_arg(
        'ver',
        promix_asset_version( 'assets/img/favicon.svg' ),
        get_theme_file_uri( 'assets/img/favicon.svg' )
    );

    // ICO — для Яндекса (он берёт иконку сниппета из /favicon.ico) и старых
    // браузеров, PNG 120 — рекомендация Яндекса, PNG 180 — для закладок на
    // iPhone, SVG — всем остальным.
    printf( '<link rel="icon" href="%s" sizes="32x32">' . "\n", esc_url( home_url( '/favicon.ico' ) ) );
    printf( '<link rel="icon" type="image/png" sizes="120x120" href="%s">' . "\n", esc_url( get_theme_file_uri( 'assets/img/favicon-120.png' ) ) );
    printf( '<link rel="icon" type="image/svg+xml" sizes="any" href="%s">' . "\n", esc_url( $url ) );
    printf(
        '<link rel="apple-touch-icon" href="%s">' . "\n",
        esc_url( add_query_arg( 'ver', promix_asset_version( 'assets/img/apple-touch-icon.png' ), get_theme_file_uri( 'assets/img/apple-touch-icon.png' ) ) )
    );
}
add_action( 'wp_head', 'promix_favicon', 99 );

/**
 * /favicon.ico из темы. Без этого WordPress уводит запрос на свой логотип,
 * и в выдаче Яндекса рядом с сайтом стоит значок WordPress.
 */
function promix_favicon_ico(): void {
    $file = get_theme_file_path( 'assets/img/favicon.ico' );

    if ( has_site_icon() || ! is_readable( $file ) ) {
        return;
    }

    header( 'Content-Type: image/x-icon' );
    header( 'Content-Length: ' . filesize( $file ) );
    header( 'Cache-Control: public, max-age=604800' );
    readfile( $file );
    exit;
}
add_action( 'do_faviconico', 'promix_favicon_ico' );

/**
 * Опечатка в адресе — честный 404. Иначе WordPress ищет запись по началу
 * адреса и уводит, например, /product/roxelpro/ на первый попавшийся товар.
 * Переименованные товары по-прежнему находит wp_old_slug_redirect.
 */
add_filter( 'do_redirect_guess_404_permalink', '__return_false' );

/**
 * robots.txt целиком. Фильтры каталога здесь не закрыты: на них стоит
 * noindex, follow, а закрытую страницу робот не прочтёт и noindex не увидит.
 * Clean-param понимает только Яндекс: метки и сортировка не меняют страницу.
 * Пока индексация закрыта, карта сайта отдаёт 404 — строку Sitemap не печатаем,
 * иначе Вебмастер покажет ошибку карты.
 *
 * @param string     $output Текст от WordPress и Woo.
 * @param bool       $public Открыта ли индексация (ядро передаёт bool).
 * @return string
 */
function promix_robots_txt( string $output, $public ): string {
    unset( $output );

    $lines = array(
        'User-agent: *',
        'Disallow: /wp-admin/',
        'Allow: /wp-admin/admin-ajax.php',
        'Disallow: /wp-content/uploads/wc-logs/',
        'Disallow: /wp-content/uploads/woocommerce_transient_files/',
        'Disallow: /wp-content/uploads/woocommerce_uploads/',
        'Disallow: /cart/',
        'Disallow: /checkout/',
        'Disallow: /my-account/',
        'Disallow: /*add-to-cart=',
        'Disallow: /*?s=',
        'Disallow: /search/',
        'Disallow: /feed/',
        'Disallow: /*/feed/',
        'Disallow: /readme.html',
        '',
        'Clean-param: utm_source&utm_medium&utm_campaign&utm_content&utm_term&yclid&ysclid&gclid&fbclid&openstat&_openstat&from&etext /',
        'Clean-param: orderby /catalog/',
    );

    if ( ! empty( $public ) ) {
        $lines[] = '';
        $lines[] = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );
    }

    return implode( "\n", $lines ) . "\n";
}
add_filter( 'robots_txt', 'promix_robots_txt', 100, 2 );

/**
 * Каталог с фильтрами — noindex, follow.
 *
 * @param array<string, bool> $robots Директивы.
 * @return array<string, bool>
 */
function promix_robots( array $robots ): array {
    if ( promix_is_filtered_catalog() ) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }

    return $robots;
}
add_filter( 'wp_robots', 'promix_robots' );

/**
 * Защитные заголовки на страницах сайта. Отдаёт тема, а не конфиг сервера:
 * так они едут на хостинг вместе с ней и не зависят от панели TimeWeb.
 * Геолокацию не запрещаем — ею пользуется встроенная карта Яндекса.
 *
 * @param array<string, string> $headers Заголовки ответа.
 * @return array<string, string>
 */
function promix_security_headers( array $headers ): array {
    // HSTS — только по HTTPS: браузер год не пустит на сайт по http.
    // Без includeSubDomains: у домена заказчика могут быть поддомены без SSL.
    if ( is_ssl() ) {
        $headers['Strict-Transport-Security'] = 'max-age=31536000';
    }

    return array_merge(
        $headers,
        array(
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            'Permissions-Policy'     => 'camera=(), microphone=(), payment=(), usb=()',
        )
    );
}
add_filter( 'wp_headers', 'promix_security_headers' );

/**
 * Карта сайта без пользователей и служебных страниц магазина.
 *
 * @param WP_Sitemaps_Provider|false $provider Провайдер.
 * @param string                     $name     Имя провайдера.
 * @return WP_Sitemaps_Provider|false
 */
function promix_sitemap_providers( $provider, string $name ) {
    return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'promix_sitemap_providers', 10, 2 );

/**
 * Блога на сайте нет: записи и их рубрики в карту не идут — иначе туда
 * попадают «Привет, мир!» и «Без рубрики» из свежей установки.
 *
 * @param array<string, WP_Post_Type> $post_types Типы записей.
 * @return array<string, WP_Post_Type>
 */
function promix_sitemap_post_types( array $post_types ): array {
    unset( $post_types['post'] );

    return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'promix_sitemap_post_types' );

/**
 * @param array<string, WP_Taxonomy> $taxonomies Таксономии.
 * @return array<string, WP_Taxonomy>
 */
function promix_sitemap_taxonomies( array $taxonomies ): array {
    unset( $taxonomies['category'], $taxonomies['post_tag'] );

    return $taxonomies;
}
add_filter( 'wp_sitemaps_taxonomies', 'promix_sitemap_taxonomies' );

/**
 * Корневые разделы каталога в карте. Своих товаров у них нет — всё лежит
 * в подразделах, — и ядро (hide_empty без hierarchical) их выбрасывало.
 * С hierarchical пустой раздел остаётся, если в подразделах есть товары.
 *
 * @param array<string, mixed> $args     Аргументы WP_Term_Query.
 * @param string               $taxonomy Таксономия.
 * @return array<string, mixed>
 */
function promix_sitemap_category_parents( array $args, string $taxonomy ): array {
    if ( 'product_cat' === $taxonomy ) {
        $args['hierarchical'] = true;
    }

    return $args;
}
add_filter( 'wp_sitemaps_taxonomies_query_args', 'promix_sitemap_category_parents', 10, 2 );

/**
 * @param array<string, mixed> $args      Аргументы WP_Query.
 * @param string               $post_type Тип записи.
 * @return array<string, mixed>
 */
function promix_sitemap_pages( array $args, string $post_type ): array {
    if ( 'page' === $post_type && function_exists( 'wc_get_page_id' ) ) {
        $args['post__not_in'] = array_merge(
            (array) ( $args['post__not_in'] ?? array() ),
            array_filter( array( wc_get_page_id( 'cart' ), wc_get_page_id( 'checkout' ), wc_get_page_id( 'myaccount' ) ) )
        );
    }

    return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'promix_sitemap_pages', 10, 2 );
