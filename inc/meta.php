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
            'PROMIX — малярный центр в Казани: краски, шпаклёвки, грунты, инструмент и оборудование. Подбор материалов, колеровка, семинары для мастеров.'
        );
    }

    // Товар: краткое описание из админки, а пока его нет — название, артикул и цена.
    if ( function_exists( 'is_product' ) && is_product() ) {
        $product = wc_get_product( get_queried_object_id() );

        if ( $product instanceof WC_Product ) {
            $short = wp_strip_all_tags( $product->get_short_description(), true );

            if ( '' !== trim( $short ) ) {
                return wp_trim_words( $short, 28, '…' );
            }

            return sprintf(
                /* translators: 1 — название, 2 — артикул, 3 — цена. */
                __( '%1$s — артикул %2$s, цена %3$s. Купить в PROMIX, малярный центр в Казани: самовывоз и доставка.', 'promix' ),
                $product->get_name(),
                $product->get_sku(),
                promix_price( (float) $product->get_price() )
            );
        }
    }

    // Раздел каталога: описание раздела или число позиций.
    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $term = get_queried_object();

        if ( $term instanceof WP_Term ) {
            if ( '' !== trim( $term->description ) ) {
                return wp_trim_words( wp_strip_all_tags( $term->description, true ), 28, '…' );
            }

            return sprintf(
                /* translators: 1 — название раздела, 2 — число позиций, 3 — слово «позиция». */
                __( '%1$s в PROMIX: %2$s %3$s с ценами. Малярный центр в Казани, самовывоз и доставка.', 'promix' ),
                $term->name,
                number_format_i18n( (int) $term->count ),
                promix_plural( (int) $term->count, 'позиция', 'позиции', 'позиций' )
            );
        }
    }

    // Страница бренда: число позиций марки.
    if ( function_exists( 'is_shop' ) && is_tax( 'product_brand' ) ) {
        $term = get_queried_object();

        if ( $term instanceof WP_Term ) {
            return sprintf(
                /* translators: 1 — бренд, 2 — число позиций, 3 — слово «позиция». */
                __( '%1$s в PROMIX: %2$s %3$s с ценами. Малярный центр в Казани, самовывоз и доставка.', 'promix' ),
                $term->name,
                number_format_i18n( (int) $term->count ),
                promix_plural( (int) $term->count, 'позиция', 'позиции', 'позиций' )
            );
        }
    }

    if ( function_exists( 'is_shop' ) && is_shop() ) {
        $total = (int) wp_count_posts( 'product' )->publish;

        return sprintf(
            /* translators: 1 — число позиций, 2 — слово «позиция». */
            __( 'Каталог PROMIX: %1$s %2$s для малярных и отделочных работ с ценами. Шпатели, ленты, валики, окрасочное оборудование, абразивы, краски.', 'promix' ),
            number_format_i18n( $total ),
            promix_plural( $total, 'позиция', 'позиции', 'позиций' )
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
    $image_id = function_exists( 'carbon_get_theme_option' )
        ? (int) carbon_get_theme_option( 'promix_og_image' )
        : 0;

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

    // Каталог с фильтрами каноничен без параметров: адрес раздела или самого каталога.
    if ( function_exists( 'promix_is_catalog' ) && promix_is_catalog() ) {
        $term = is_product_category() ? get_queried_object() : null;
        $link = $term instanceof WP_Term ? get_term_link( $term ) : promix_catalog_url();

        return is_wp_error( $link ) ? promix_catalog_url() : $link;
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

    if ( $description ) {
        printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
    }

    // WordPress ставит canonical только записям; каталогу и разделам — сами.
    if ( function_exists( 'promix_is_catalog' ) && promix_is_catalog() ) {
        printf( '<link rel="canonical" href="%s">' . "\n", esc_url( promix_canonical_url() ) );
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
