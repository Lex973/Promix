<?php
/**
 * Декоративные покрытия AlterItaly: заводит или обновляет товары
 * из decor.csv (его собирает _tools/wc-decor.py) и грузит образцы цвета.
 *
 * Как применить: рядом со скриптом положить decor.csv и папку img/
 * из docs/data/decor/, скрипт скопировать в корень сайта, заменить СЕКРЕТ,
 * открыть /wc-decor.php?k=СЕКРЕТ (с &dry=1 — прогон без записи),
 * после — удалить скрипт, CSV и img/.
 *
 * Товар ищется по артикулу: повторный запуск обновляет те же товары,
 * а не заводит копии. Картинка грузится в медиатеку один раз на файл —
 * у пяти систем ROVIGO и двух TERMOLI она общая.
 */

$key = 'СЕКРЕТ';

// Ключ должен быть заменён на случайную строку из 12+ латинских букв и цифр:
// с плейсхолдером скрипт не запускается, иначе ключ знает каждый.
if ( ! preg_match( '/^[a-z0-9]{12,}$/i', $key ) || ! hash_equals( $key, (string) ( $_GET['k'] ?? '' ) ) ) {
    http_response_code( 403 );
    exit( 'no' );
}

require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

set_time_limit( 0 );
header( 'Content-Type: text/plain; charset=utf-8' );
wp_set_current_user( 1 ); // Без прав на загрузку медиа и термины ничего не создастся.

$dry  = ! empty( $_GET['dry'] );
$file = __DIR__ . '/decor.csv';

if ( ! is_readable( $file ) ) {
    exit( "Нет файла decor.csv рядом со скриптом\n" );
}

$handle = fopen( $file, 'r' );
$head   = fgetcsv( $handle, 0, ';' );
$rows   = array();

while ( ( $row = fgetcsv( $handle, 0, ';' ) ) !== false ) {
    if ( count( $row ) === count( $head ) ) {
        $rows[] = array_combine( $head, $row );
    }
}

fclose( $handle );

/**
 * Раздел «Родитель > Подраздел»: найти или завести.
 */
$category_id = static function ( string $path ) use ( $dry ): int {
    $parent = 0;

    foreach ( array_map( 'trim', explode( '>', $path ) ) as $name ) {
        $found = get_terms( array( 'taxonomy' => 'product_cat', 'name' => $name, 'parent' => $parent, 'hide_empty' => false, 'number' => 1 ) );

        if ( $found && ! is_wp_error( $found ) ) {
            $parent = (int) $found[0]->term_id;
            continue;
        }

        if ( $dry ) {
            echo "  раздел «{$name}» будет создан\n";
            return 0;
        }

        $made   = wp_insert_term( $name, 'product_cat', array( 'parent' => $parent ) );
        $parent = is_wp_error( $made ) ? 0 : (int) $made['term_id'];
    }

    return $parent;
};

/**
 * Образец из img/: уже в медиатеке — вернуть его, нет — загрузить.
 */
$image_id = static function ( string $name, string $alt ) use ( $dry ): int {
    $found = get_posts(
        array(
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'numberposts' => 1,
            'fields'      => 'ids',
            'meta_key'    => '_promix_decor_image', // phpcs:ignore WordPress.DB.SlowDBQuery -- разовый скрипт.
            'meta_value'  => $name, // phpcs:ignore WordPress.DB.SlowDBQuery
        )
    );

    if ( $found ) {
        return (int) $found[0];
    }

    $path = __DIR__ . '/img/' . $name;

    if ( ! is_readable( $path ) ) {
        echo "  нет картинки img/{$name}\n";
        return 0;
    }

    if ( $dry ) {
        return 0;
    }

    // media_handle_sideload переносит файл — отдаём ему копию.
    $tmp = wp_tempnam( $name );
    copy( $path, $tmp );

    $id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, $alt );

    if ( is_wp_error( $id ) ) {
        echo '  картинка не загрузилась: ' . $id->get_error_message() . "\n";
        return 0;
    }

    update_post_meta( $id, '_promix_decor_image', $name );
    update_post_meta( $id, '_wp_attachment_image_alt', $alt );

    return (int) $id;
};

$made = 0;
$updated = 0;

foreach ( $rows as $r ) {
    $id      = wc_get_product_id_by_sku( $r['SKU'] );
    $product = $id ? wc_get_product( $id ) : new WC_Product_Simple();

    echo ( $id ? 'обновить' : 'завести ' ) . " {$r['SKU']} — {$r['Name']} — от {$r['Price']} ₽/{$r['Unit']}\n";

    if ( $dry ) {
        $category_id( $r['Category'] );
        $image_id( $r['Image'], $r['Name'] );
        continue;
    }

    $product->set_sku( $r['SKU'] );
    $product->set_name( $r['Name'] );
    $product->set_status( 'publish' );
    $product->set_catalog_visibility( 'visible' );
    $product->set_regular_price( $r['Price'] );
    $product->set_short_description( $r['Short'] );
    $product->set_description( $r['Description'] );
    $product->set_stock_status( 'instock' );
    $product->set_category_ids( array_filter( array( $category_id( $r['Category'] ) ) ) );
    $product->update_meta_data( '_promix_price_from', 'yes' );
    $product->update_meta_data( '_promix_price_unit', $r['Unit'] );

    $image = $image_id( $r['Image'], $r['Name'] );

    if ( $image ) {
        $product->set_image_id( $image );
    }

    $saved = $product->save();

    // Бренд — термин Woo; своей колонки у товара нет.
    if ( taxonomy_exists( 'product_brand' ) && '' !== $r['Brand'] ) {
        wp_set_object_terms( $saved, $r['Brand'], 'product_brand' );
    }

    $id ? ++$updated : ++$made;
}

if ( ! $dry ) {
    delete_transient( 'promix_catalog_total' );
    delete_transient( 'promix_catalog_paths' );
    wc_delete_product_transients();
}

echo "\n" . ( $dry ? 'прогон без записи' : "заведено {$made}, обновлено {$updated}" ) . "\n";
