<?php
/**
 * Товары с вариантами: одна лента — несколько длин.
 *
 * Варианты — штатные вариации Woo: у каждой своя цена и артикул (код
 * из 1С), фото общее у товара. В каталоге и на странице товара они
 * выводятся кнопками под ценой, а когда вариантов много — выпадающим
 * списком (пороги ниже). Пока вариант не выбран, показана цена первого,
 * а «В корзину» просит выбрать — заранее ничего не отмечено, чтобы не
 * купить 50 м, не заметив выбора.
 *
 * Поддержан один атрибут на товар («Длина», «Объём», «Ширина» — как
 * назовут в админке). Вариации «любое значение» не поддержаны: у каждой
 * должно быть своё значение.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

/*
 * Сколько вариантов показывать кнопками, больше — выпадающим списком.
 * В карточке четыре кнопки с длинной подписью («10 мм × 50 м») — это
 * две строки на компьютере и четыре на телефоне; дальше карточка
 * вытягивает за собой весь ряд. На странице товара восемь — две строки
 * на компьютере и четыре на телефоне.
 */
const PROMIX_VARIANTS_CARD_BUTTONS   = 4;
const PROMIX_VARIANTS_SINGLE_BUTTONS = 8;

/**
 * Выводить ли варианты выпадающим списком вместо кнопок.
 *
 * @param int    $count   Сколько вариантов.
 * @param string $variant card или single.
 */
function promix_variants_use_select( int $count, string $variant ): bool {
    return $count > ( 'single' === $variant ? PROMIX_VARIANTS_SINGLE_BUTTONS : PROMIX_VARIANTS_CARD_BUTTONS );
}

/**
 * Включённые варианты товара в порядке из админки.
 *
 * Выключенные (статус «частный») не показываются. Вариант без цены
 * остаётся: у него «Цена по запросу» и кнопка «Уточнить», как у
 * простых товаров без цены.
 *
 * @param WC_Product $product Товар.
 * @return WC_Product_Variation[]
 */
function promix_variations( WC_Product $product ): array {
    if ( ! $product->is_type( 'variable' ) ) {
        return array();
    }

    $list     = array();
    $children = $product->get_children();

    // Посты и мета всех вариантов — двумя запросами, а не по два на вариант.
    _prime_post_caches( $children, false, true );

    foreach ( $children as $child_id ) {
        $variation = wc_get_product( $child_id );

        if ( $variation instanceof WC_Product_Variation && 'publish' === $variation->get_status() ) {
            $list[] = $variation;
        }
    }

    return $list;
}

/**
 * Подпись варианта на кнопке: «90 м».
 *
 * @param WC_Product_Variation $variation Вариант.
 */
function promix_variation_label( WC_Product_Variation $variation ): string {
    return wp_strip_all_tags( wc_get_formatted_variation( $variation, true, false ) );
}

/**
 * Что выбирают: «Длина» — название атрибута из админки.
 *
 * @param WC_Product $product Товар с вариантами.
 */
function promix_variations_title( WC_Product $product ): string {
    $names = array();

    foreach ( array_keys( $product->get_variation_attributes() ) as $attribute ) {
        $names[] = wc_attribute_label( $attribute, $product );
    }

    return implode( ' / ', $names );
}

/**
 * Товар, у которого хранятся раздел, бренд и свои поля: у варианта — родитель.
 *
 * @param WC_Product $product Товар или вариант.
 */
function promix_base_product( WC_Product $product ): WC_Product {
    if ( $product->is_type( 'variation' ) ) {
        $parent = wc_get_product( $product->get_parent_id() );

        if ( $parent instanceof WC_Product ) {
            return $parent;
        }
    }

    return $product;
}

/**
 * Название позиции в корзине и заказе: «Лента малярная — 90 м».
 *
 * Woo сам называет вариант «Лента малярная - 90 м», но дефис в длинных
 * названиях из 1С теряется среди других дефисов. Пробелы в варианте и
 * перед тире неразрывные: в узком итоге корзины «м» не уезжает от «90».
 *
 * @param WC_Product $product Товар или вариант.
 */
function promix_cart_item_name( WC_Product $product ): string {
    if ( ! $product instanceof WC_Product_Variation ) {
        return $product->get_name();
    }

    $label = str_replace( ' ', "\u{00A0}", promix_variation_label( $product ) );

    return promix_base_product( $product )->get_name() . ( '' !== $label ? "\u{00A0}— " . $label : '' );
}

/**
 * Не повторять вариант строкой «Длина: 90 м» под названием позиции
 * в письмах и админке: он уже в названии («Лента малярная — 90 м»).
 *
 * Woo прячет такую строку сам, но ищет значение после обычного пробела,
 * а в названии пробелы неразрывные (см. promix_cart_item_name).
 *
 * @param array<int, object>    $meta Строки для вывода.
 * @param WC_Order_Item_Product $item Позиция.
 * @return array<int, object>
 */
function promix_order_item_hide_variant_meta( $meta, $item ) {
    if ( ! $item instanceof WC_Order_Item_Product || ! $item->get_variation_id() ) {
        return $meta;
    }

    $plain = static fn ( string $text ): string => str_replace( "\u{00A0}", ' ', wp_strip_all_tags( $text ) );
    $name  = $plain( $item->get_name() );

    foreach ( $meta as $id => $row ) {
        $value = trim( $plain( (string) $row->display_value ) );

        if ( '' !== $value && str_ends_with( $name, '— ' . $value ) ) {
            unset( $meta[ $id ] );
        }
    }

    return $meta;
}
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'promix_order_item_hide_variant_meta', 10, 2 );

/*
 * Адрес товара, который склеили в вариант другого: старая страница
 * не должна давать 404 — на неё ведут поиск и ссылки в мессенджерах.
 * Скрипт склейки записывает старый адрес (slug) в мету варианта.
 */
const PROMIX_VARIANTS_OLD_SLUG = '_promix_old_slug';

/**
 * 301 со старого адреса на товар с уже выбранным вариантом
 * (/product/lenta/?attribute_dlina=90 м). Вариант выключен — на сам товар.
 */
function promix_variants_old_slug_redirect(): void {
    $slug = (string) get_query_var( 'name' );

    if ( ! is_404() || '' === $slug || 'product' !== get_query_var( 'post_type' ) ) {
        return;
    }

    $ids = get_posts(
        array(
            'post_type'      => 'product_variation',
            'post_status'    => array( 'publish', 'private' ),
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => PROMIX_VARIANTS_OLD_SLUG, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- только на 404.
            'meta_value'     => $slug, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        )
    );

    $variation = $ids ? wc_get_product( $ids[0] ) : null;

    if ( ! $variation instanceof WC_Product_Variation ) {
        return;
    }

    $parent = wc_get_product( $variation->get_parent_id() );

    if ( ! $parent instanceof WC_Product || 'publish' !== $parent->get_status() ) {
        return;
    }

    wp_safe_redirect( 'publish' === $variation->get_status() ? $variation->get_permalink() : $parent->get_permalink(), 301 );
    exit;
}
add_action( 'template_redirect', 'promix_variants_old_slug_redirect', 9 );
