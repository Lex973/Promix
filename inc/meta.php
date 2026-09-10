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
 * потом логотип — чтобы ссылка никогда не уходила голой.
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

    return get_theme_file_uri( 'assets/img/logo.png' );
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

    if ( is_singular() ) {
        $url = wp_get_canonical_url();

        if ( $url ) {
            return $url;
        }
    }

    return home_url( add_query_arg( array() ) );
}

/**
 * Печать мета-тегов в head.
 *
 * @return void
 */
function promix_meta_tags(): void {
    $description = promix_meta_description();
    $title       = wp_get_document_title();

    if ( $description ) {
        printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
    }

    printf( '<meta property="og:type" content="%s">' . "\n", is_singular() && ! is_front_page() ? 'article' : 'website' );
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
