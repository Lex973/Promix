<?php
/**
 * Связка темы с WooCommerce.
 *
 * Сам магазин настраивается из админки; здесь только то, что должно
 * жить в коде: поддержка Woo темой и латинские адреса для товаров
 * и разделов, которые заводятся по-русски.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Кириллица в адресах — транслитом.
 *
 * WordPress сам кириллицу не переводит и делает из «Шпатели» адрес
 * вида %d0%a8%d0%bf…; такой адрес неудобно читать и копировать.
 * Сюда же попадают названия загружаемых файлов.
 *
 * Тип аргумента не объявлен: в фильтр любой плагин может передать null,
 * а строгий тип уронил бы сайт TypeError'ом.
 *
 * @param mixed $title Строка, из которой делается адрес.
 * @return string
 */
function promix_translit( $title ): string {
    $title = (string) $title;

    static $map = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
        'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo',
        'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M',
        'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U',
        'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '',
        'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
    );

    return strtr( $title, $map );
}
// Раньше стандартного sanitize_title_with_dashes (приоритет 10).
add_filter( 'sanitize_title', 'promix_translit', 9 );
add_filter( 'sanitize_file_name', 'promix_translit' );

/**
 * Тема знает про WooCommerce; галереи Woo не нужны — своя разметка.
 */
function promix_woocommerce_setup(): void {
    add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'promix_woocommerce_setup' );

/**
 * Стили и скрипты Woo на фронте не нужны: у каталога, карточки и корзины
 * своя разметка, а вместе с ними уезжает и jQuery.
 */
function promix_woocommerce_assets(): void {
    /*
     * Не перечисляем хэндлы поимённо: на корзине и оформлении Woo ставит
     * ещё wc-cart, wc-checkout, wc-country-select со 150 КБ списка регионов,
     * selectWoo — снимаем всё своё по префиксу. jQuery уйдёт сам: у него
     * не останется зависимых.
     */
    $own = static function ( string $handle ): bool {
        return 0 === strpos( $handle, 'wc-' )
            || 0 === strpos( $handle, 'woocommerce' )
            || in_array( $handle, array( 'selectWoo', 'select2', 'sourcebuster-js', 'jquery-blockui', 'js-cookie' ), true );
    };

    foreach ( wp_scripts()->queue as $handle ) {
        if ( $own( (string) $handle ) ) {
            wp_dequeue_script( (string) $handle );
        }
    }

    foreach ( wp_styles()->queue as $handle ) {
        if ( $own( (string) $handle ) ) {
            wp_dequeue_style( (string) $handle );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'promix_woocommerce_assets', 100 );
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Скрипт «есть ли JS» и класс woocommerce-no-js на body — от стилей Woo,
 * которых у нас нет.
 */
function promix_woocommerce_no_js(): void {
    remove_action( 'wp_footer', 'wc_no_js' );
}
add_action( 'wp_footer', 'promix_woocommerce_no_js', 0 );

/**
 * @param string[] $classes Классы body.
 * @return string[]
 */
function promix_woocommerce_body_class( array $classes ): array {
    return array_values( array_diff( $classes, array( 'woocommerce-no-js' ) ) );
}
add_filter( 'body_class', 'promix_woocommerce_body_class', 11 );

/**
 * Поля цены «от» в карточке товара, вкладка «Основные» под ценой.
 */
function promix_price_from_fields(): void {
    woocommerce_wp_checkbox(
        array(
            'id'          => '_promix_price_from',
            'label'       => __( 'Цена «от»', 'promix' ),
            'description' => __( 'Показывать «от» перед ценой на сайте, в корзине, письмах и MAX — цена минимальная, итог назовёт менеджер.', 'promix' ),
        )
    );

    woocommerce_wp_text_input(
        array(
            'id'          => '_promix_price_unit',
            'label'       => __( 'Единица цены', 'promix' ),
            'placeholder' => __( 'м²', 'promix' ),
            'desc_tip'    => true,
            'description' => __( 'За что цена: «м²» даст «от 350 ₽/м²». Пусто — за штуку.', 'promix' ),
        )
    );
}
add_action( 'woocommerce_product_options_pricing', 'promix_price_from_fields' );

/**
 * @param WC_Product $product Товар.
 */
function promix_price_from_save( WC_Product $product ): void {
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce проверил Woo до этого хука.
    $product->update_meta_data( '_promix_price_from', empty( $_POST['_promix_price_from'] ) ? 'no' : 'yes' );
    $product->update_meta_data( '_promix_price_unit', mb_substr( sanitize_text_field( wp_unslash( $_POST['_promix_price_unit'] ?? '' ) ), 0, 20 ) );
    // phpcs:enable
}
add_action( 'woocommerce_admin_process_product_object', 'promix_price_from_save' );

/**
 * «от» в письмах Woo: сумма позиции.
 *
 * @param string                $subtotal Сумма позиции (HTML).
 * @param WC_Order_Item_Product $item     Позиция.
 * @return string
 */
function promix_price_from_email_line( $subtotal, $item ) {
    return $item instanceof WC_Order_Item_Product && promix_price_is_from( $item ) ? __( 'от', 'promix' ) . ' ' . $subtotal : $subtotal;
}
add_filter( 'woocommerce_order_formatted_line_subtotal', 'promix_price_from_email_line', 10, 2 );

/**
 * «от» в письмах Woo: подытог и итог, если в заказе есть цена «от».
 *
 * @param array<string, array<string, string>> $rows  Строки итогов.
 * @param WC_Order                             $order Заказ.
 * @return array<string, array<string, string>>
 */
function promix_price_from_email_totals( $rows, $order ) {
    if ( ! $order instanceof WC_Order || ! array_filter( promix_order_products( $order ), 'promix_price_is_from' ) ) {
        return $rows;
    }

    foreach ( array( 'cart_subtotal', 'order_total' ) as $key ) {
        if ( isset( $rows[ $key ]['value'] ) ) {
            $rows[ $key ]['value'] = __( 'от', 'promix' ) . ' ' . $rows[ $key ]['value'];
        }
    }

    return $rows;
}
add_filter( 'woocommerce_get_order_item_totals', 'promix_price_from_email_totals', 10, 2 );
