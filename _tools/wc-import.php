<?php
/**
 * Импорт товаров тем же импортёром Woo, что «Товары → Импорт», но из консоли.
 * Повторяет шаги 6.1–6.2 из DEPLOY.md: woo-import.csv — новые товары,
 * woo-descriptions.csv — обновление описаний по артикулу.
 *
 * Запуск на сервере из папки сайта, CSV рядом:
 *   wp eval-file wc-import.php woo-import.csv woo-descriptions.csv
 *
 * Колонку «Brands» Woo разбирает классом из админки (WC_Admin_Brands) —
 * в консоли он не загружен, без него бренды молча не проставятся;
 * поэтому класс подключается здесь.
 *
 * @package promix
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( "Только через wp eval-file\n" );
}

require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';

// Файл сам создаёт $GLOBALS['WC_Brands_Admin'] и вешает обработчики импорта.
if ( ! class_exists( 'WC_Brands_Admin' ) && is_readable( WC_ABSPATH . 'includes/admin/class-wc-admin-brands.php' ) ) {
    require_once WC_ABSPATH . 'includes/admin/class-wc-admin-brands.php';
}

if ( ! has_filter( 'woocommerce_product_importer_formatting_callbacks' ) ) {
    WP_CLI::error( 'Обработчик колонки «Brands» не подключился — бренды не проставятся.' );
}

// Импортёр проверяет права на создание разделов и брендов.
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( (int) ( $admins[0] ?? 0 ) );

$files = $args ?? array();

$jobs = array(
    array(
        'file'    => $files[0] ?? 'woo-import.csv',
        'update'  => false, // С «обновлять существующие» новые строки пропускаются.
        'mapping' => array(
            'Type'                  => 'type',
            'SKU'                   => 'sku',
            'Name'                  => 'name',
            'Published'             => 'published',
            'Visibility in catalog' => 'catalog_visibility',
            'In stock?'             => 'stock_status',
            'Regular price'         => 'regular_price',
            'Categories'            => 'category_ids',
            'Position'              => 'menu_order',
            'Brands'                => 'brand_ids',
        ),
    ),
    array(
        'file'    => $files[1] ?? 'woo-descriptions.csv',
        'update'  => true,
        'mapping' => array(
            'SKU'               => 'sku',
            'Short description' => 'short_description',
            'Description'       => 'description',
        ),
    ),
);

foreach ( $jobs as $job ) {
    if ( ! is_readable( $job['file'] ) ) {
        WP_CLI::error( "Нет файла {$job['file']}" );
    }

    $importer = new WC_Product_CSV_Importer(
        $job['file'],
        array(
            'mapping'          => $job['mapping'],
            'parse'            => true,
            'update_existing'  => $job['update'],
            'prevent_timeouts' => false,
        )
    );

    $result = $importer->import();
    $counts = array_map( 'count', $result );

    WP_CLI::log( sprintf( '%s: создано %d, обновлено %d, пропущено %d, ошибок %d', $job['file'], $counts['imported'], $counts['updated'], $counts['skipped'], $counts['failed'] ) );

    foreach ( array_slice( array_merge( $result['failed'], $result['skipped'] ), 0, 10 ) as $error ) {
        WP_CLI::warning( is_wp_error( $error ) ? $error->get_error_message() : (string) $error );
    }
}

wc_delete_product_transients();
