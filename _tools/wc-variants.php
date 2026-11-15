<?php
/**
 * Склейка простых товаров в товары с вариантами по wc-variants.csv
 * (его собирает _tools/wc-variants.py из ответа заказчика).
 *
 * Как применить: рядом со скриптом положить wc-variants.csv, скрипт
 * скопировать в корень сайта, заменить СЕКРЕТ и открыть:
 *
 *     /wc-variants.php?k=СЕКРЕТ&dry=1     прогон без записи — что будет и что мешает
 *     /wc-variants.php?k=СЕКРЕТ           склейка
 *     /wc-variants.php?k=СЕКРЕТ&group=7   только группа 7 (с dry=1 тоже)
 *     /wc-variants.php?k=СЕКРЕТ&undo=1    откат склеенного этим скриптом
 *
 * После — удалить скрипт и CSV. Перед склейкой на сервере — бэкап базы.
 *
 * Что происходит с группой. Заводится товар с вариантами: название из CSV,
 * раздел, бренд, описание (без его артикула), «от» и единица цены — от
 * первого товара группы, фото — первое, какое нашлось. Каждый товар группы становится вариантом
 * со своими ценой и артикулом (по артикулу потом идут цены из 1С), сам
 * товар уходит в корзину, а его адрес запоминается в варианте: со старой
 * страницы тема отправляет 301 на товар с этим вариантом (inc/variants.php).
 *
 * Группа склеивается целиком или никак: нет товара с артикулом, товар не
 * простой или уже вариант, подписи повторяются — группа пропускается,
 * остальные идут дальше. Ошибка посреди записи откатывает эту группу.
 * Повторный запуск склеенные группы пропускает.
 *
 * Откат удаляет заведённый товар с вариантами и достаёт исходные товары
 * из корзины с прежними адресом, статусом и артикулом. Цену берёт ту,
 * что была до склейки: если 1С успела обновить цену варианта, её нужно
 * будет обновить и у восстановленного товара.
 * Откат возможен, пока исходные товары в корзине: WordPress очищает её
 * через 30 дней, и тогда скрипт отказывается откатывать эту группу.
 */

$key = 'СЕКРЕТ';

// Ключ должен быть заменён на случайную строку из 12+ латинских букв и цифр:
// с плейсхолдером скрипт не запускается, иначе ключ знает каждый.
if ( ! preg_match( '/^[a-z0-9]{12,}$/i', $key ) || ! hash_equals( $key, (string) ( $_GET['k'] ?? '' ) ) ) {
    http_response_code( 403 );
    exit( 'no' );
}

require __DIR__ . '/wp-load.php';

set_time_limit( 0 );
header( 'Content-Type: text/plain; charset=utf-8' );

// Термины бренда и корзина записей проверяют права.
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( (int) ( $admins[0] ?? 0 ) );

// Только «1»: dry=0 и undo=0 не должны включать режим.
$dry   = '1' === ( $_GET['dry'] ?? '' );
$undo  = '1' === ( $_GET['undo'] ?? '' );
$only  = isset( $_GET['group'] ) ? (string) $_GET['group'] : '';
$file  = __DIR__ . '/wc-variants.csv';

if ( ! is_readable( $file ) ) {
    exit( "Нет файла wc-variants.csv рядом со скриптом\n" );
}

$handle = fopen( $file, 'r' );

// CSV пишется с BOM — иначе Excel открывает его не в той кодировке.
if ( "\xEF\xBB\xBF" !== fread( $handle, 3 ) ) {
    rewind( $handle );
}

$head   = fgetcsv( $handle, 0, ';' );
$groups = array();

while ( ( $row = fgetcsv( $handle, 0, ';' ) ) !== false ) {
    if ( count( $row ) !== count( $head ) ) {
        continue;
    }

    $row = array_map( 'trim', array_combine( $head, $row ) );

    if ( '' === $only || $only === $row['Group'] ) {
        $groups[ $row['Group'] ][] = $row;
    }
}

fclose( $handle );

if ( ! $groups ) {
    exit( '' !== $only ? "Группы {$only} нет в CSV\n" : "CSV пустой\n" );
}

/**
 * Товар с вариантами, который этот скрипт завёл для группы, — по артикулам её строк.
 *
 * @param array<int, array<string, string>> $rows Строки группы.
 */
$merged_parent = static function ( array $rows ): ?WC_Product_Variable {
    foreach ( $rows as $r ) {
        $id      = wc_get_product_id_by_sku( $r['SKU'] );
        $product = $id ? wc_get_product( $id ) : null;

        if ( $product instanceof WC_Product_Variation ) {
            $parent = wc_get_product( $product->get_parent_id() );

            if ( $parent instanceof WC_Product_Variable && $parent->get_meta( '_promix_variants_sources' ) ) {
                return $parent;
            }
        }
    }

    return null;
};

/**
 * Описание первого товара без его артикула: wc-descriptions.py вписывает
 * «…, артикул 493325.» в краткое и «Артикул 493325.» в полное, а у товара
 * с вариантами артикулов несколько — свой у каждого варианта.
 *
 * @param string   $text Описание.
 * @param string[] $skus Артикулы группы.
 */
$without_sku = static function ( string $text, array $skus ): string {
    foreach ( $skus as $sku ) {
        $sku  = preg_quote( $sku, '/' );
        $text = preg_replace( "/,\s*артикул\s+{$sku}\./u", '.', $text );
        $text = preg_replace( "/[ \t]*Артикул\s+{$sku}\./u", '', $text );
    }

    return trim( preg_replace( "/\n{3,}/", "\n\n", $text ) );
};

/**
 * Что мешает склеить группу. Пустой список — можно.
 *
 * @param array<int, array<string, string>> $rows    Строки группы.
 * @param WC_Product_Simple[]               $sources Сюда — найденные товары по порядку строк.
 * @param string[]                          $notes   Сюда — предупреждения, которые не мешают.
 * @return string[]
 */
$problems = static function ( array $rows, ?array &$sources, ?array &$notes ) use ( $without_sku ): array {
    $errors  = array();
    $sources = array();
    $notes   = array();

    if ( count( $rows ) < 2 ) {
        $errors[] = 'в группе меньше двух вариантов';
    }

    $names = array_unique( array_column( $rows, 'Name' ) );
    $attrs = array_unique( array_column( $rows, 'Attribute' ) );

    if ( count( $names ) > 1 || count( $attrs ) > 1 ) {
        $errors[] = 'у строк группы разные название товара или «что выбирают»';
    }

    if ( '' === $rows[0]['Name'] || '' === $rows[0]['Attribute'] ) {
        $errors[] = 'пустое название товара или «что выбирают»';
    }

    $options = array_column( $rows, 'Option' );

    if ( count( array_unique( array_map( 'mb_strtolower', $options ) ) ) !== count( $options ) ) {
        $errors[] = 'подписи вариантов повторяются: ' . implode( ', ', $options );
    }

    foreach ( $rows as $r ) {
        // «|» — разделитель значений атрибута у Woo: подпись развалилась бы на две.
        if ( '' === $r['Option'] || false !== strpos( $r['Option'], '|' ) ) {
            $errors[] = "плохая подпись варианта «{$r['Option']}» у {$r['SKU']}";
        }

        $id      = '' !== $r['SKU'] ? wc_get_product_id_by_sku( $r['SKU'] ) : 0;
        $product = $id ? wc_get_product( $id ) : null;

        if ( ! $product ) {
            $errors[] = "нет товара с артикулом {$r['SKU']}";
            continue;
        }

        if ( ! $product->is_type( 'simple' ) ) {
            $errors[] = "{$r['SKU']} — не простой товар ({$product->get_type()}" . ( $product->get_parent_id() ? ', вариант товара ' . $product->get_parent_id() : '' ) . ')';
            continue;
        }

        if ( 'publish' !== $product->get_status() ) {
            $errors[] = "{$r['SKU']} не опубликован ({$product->get_status()})";
            continue;
        }

        $sources[] = $product;
    }

    if ( $errors ) {
        return $errors;
    }

    $first = $sources[0];

    foreach ( $sources as $product ) {
        if ( $product->get_category_ids() !== $first->get_category_ids() ) {
            $notes[] = "{$product->get_sku()} в другом разделе — товар встанет в раздел {$first->get_sku()}";
        }

        foreach ( array( '_promix_price_from', '_promix_price_unit' ) as $meta ) {
            if ( (string) $product->get_meta( $meta ) !== (string) $first->get_meta( $meta ) ) {
                $notes[] = "{$product->get_sku()}: «от»/единица цены не как у {$first->get_sku()} — берётся от {$first->get_sku()}";
                break;
            }
        }

        if ( $product->get_sale_price() ) {
            $notes[] = "{$product->get_sku()}: есть цена со скидкой — перенесётся в вариант";
        }
    }

    $skus = array_column( $rows, 'SKU' );
    $text = $without_sku( $first->get_short_description(), $skus ) . ' ' . $without_sku( $first->get_description(), $skus );

    foreach ( $skus as $sku ) {
        if ( false !== mb_stripos( $text, $sku ) ) {
            $notes[] = "в описании {$first->get_sku()} упомянут артикул {$sku} — поправить в админке после склейки";
        }
    }

    $images = array_values( array_unique( array_filter( array_map( static fn ( $p ) => (int) $p->get_image_id(), $sources ) ) ) );

    if ( count( $images ) > 1 ) {
        $with = array_values( array_filter( $sources, static fn ( $p ) => (int) $p->get_image_id() ) );
        $notes[] = 'у товаров разные фото — на товаре будет фото ' . $with[0]->get_sku() . ', остальные останутся в медиатеке';
    }

    return array();
};

/**
 * Склеить группу. Исключение — ошибка записи, вызывающий откатывает.
 *
 * @param array<int, array<string, string>> $rows    Строки группы.
 * @param WC_Product_Simple[]               $sources Исходные товары по порядку строк.
 * @param array<string, int[]>              $made    Сюда — что заведено и что в корзине, для отката.
 */
$merge = static function ( array $rows, array $sources, array &$made ) use ( $without_sku ): WC_Product_Variable {
    $first = $sources[0];
    $attr  = $rows[0]['Attribute'];
    $key   = sanitize_title( $attr );
    $image = 0;

    foreach ( $sources as $product ) {
        $image = $image ?: (int) $product->get_image_id();
    }

    // Данные исходных — до корзины: она меняет адрес на «…__trashed».
    $data = array();

    foreach ( $sources as $product ) {
        $data[] = array(
            'id'      => $product->get_id(),
            'slug'    => $product->get_slug(),
            'sku'     => $product->get_sku(),
            'regular' => $product->get_regular_price(),
            'sale'    => $product->get_sale_price(),
            'from'    => $product->get_date_on_sale_from(),
            'to'      => $product->get_date_on_sale_to(),
            'stock'   => $product->get_stock_status(),
            'order'   => $product->get_menu_order(),
        );
    }

    $attribute = new WC_Product_Attribute();
    $attribute->set_name( $attr );
    $attribute->set_options( array_column( $rows, 'Option' ) );
    $attribute->set_visible( true );
    $attribute->set_variation( true );
    $attribute->set_position( 0 );

    $parent = new WC_Product_Variable();
    $parent->set_name( $rows[0]['Name'] );
    $parent->set_status( 'publish' );
    $parent->set_catalog_visibility( $first->get_catalog_visibility() );
    $parent->set_category_ids( $first->get_category_ids() );
    $parent->set_tag_ids( $first->get_tag_ids() );
    $parent->set_short_description( $without_sku( $first->get_short_description(), array_column( $rows, 'SKU' ) ) );
    $parent->set_description( $without_sku( $first->get_description(), array_column( $rows, 'SKU' ) ) );
    $parent->set_menu_order( min( array_column( $data, 'order' ) ) );
    $parent->set_attributes( array( $attribute ) );
    $parent->set_image_id( $image );

    foreach ( array( '_promix_price_from', '_promix_price_unit' ) as $meta ) {
        if ( '' !== (string) $first->get_meta( $meta ) ) {
            $parent->update_meta_data( $meta, $first->get_meta( $meta ) );
        }
    }

    $parent->update_meta_data( '_promix_variants_sources', array_column( $data, 'id' ) );
    $parent->save();

    if ( ! $parent->get_id() ) {
        throw new RuntimeException( 'товар с вариантами не сохранился' );
    }

    $made['parent'] = $parent->get_id();

    // Бренд — термин Woo, у товара своей колонки нет.
    if ( taxonomy_exists( 'product_brand' ) ) {
        $brands = wp_get_object_terms( $first->get_id(), 'product_brand', array( 'fields' => 'ids' ) );

        if ( ! is_wp_error( $brands ) && $brands ) {
            wp_set_object_terms( $parent->get_id(), array_map( 'intval', $brands ), 'product_brand' );
        }
    }

    // В корзину — до заведения вариантов: артикул занят, пока товар не в корзине.
    foreach ( $data as $d ) {
        if ( ! wp_trash_post( $d['id'] ) ) {
            throw new RuntimeException( "товар {$d['sku']} не ушёл в корзину" );
        }

        $made['trashed'][] = $d['id'];
    }

    // Пока исходный товар с тем же названием был жив, слаг родителя получил «-2».
    wp_update_post( array( 'ID' => $parent->get_id(), 'post_name' => '' ) );

    foreach ( $data as $i => $d ) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id( $parent->get_id() );
        $variation->set_attributes( array( $key => $rows[ $i ]['Option'] ) );
        $variation->set_regular_price( $d['regular'] );
        $variation->set_sale_price( $d['sale'] );
        $variation->set_date_on_sale_from( $d['from'] );
        $variation->set_date_on_sale_to( $d['to'] );
        $variation->set_stock_status( $d['stock'] );
        $variation->set_menu_order( $i );
        $variation->set_status( 'publish' );
        $variation->set_sku( $d['sku'] ); // Занятый артикул бросает исключение — группа откатится.
        $variation->update_meta_data( PROMIX_VARIANTS_OLD_SLUG, $d['slug'] );
        $variation->update_meta_data( '_promix_variants_source', $d['id'] );
        $variation->save();

        if ( ! $variation->get_id() ) {
            throw new RuntimeException( "вариант {$d['sku']} не сохранился" );
        }

        $made['variations'][] = $variation->get_id();
    }

    WC_Product_Variable::sync( $parent->get_id() );
    wc_delete_product_transients( $parent->get_id() );

    return wc_get_product( $parent->get_id() );
};

/**
 * Достать исходные товары из корзины с прежним статусом (WordPress по
 * умолчанию возвращает их черновиками) и адресом.
 *
 * @param int[] $ids Исходные товары.
 */
$restore = static function ( array $ids ): void {
    $previous = static fn ( $status, $post_id, $was ) => $was ? $was : $status;
    add_filter( 'wp_untrash_post_status', $previous, 10, 3 );

    foreach ( $ids as $id ) {
        if ( 'trash' === get_post_status( $id ) ) {
            wp_untrash_post( $id );
        }

        wc_delete_product_transients( $id );
    }

    remove_filter( 'wp_untrash_post_status', $previous, 10 );
};

if ( ! defined( 'PROMIX_VARIANTS_OLD_SLUG' ) ) {
    exit( "Тема не та или старая: нет PROMIX_VARIANTS_OLD_SLUG (inc/variants.php) — без неё старые адреса дадут 404\n" );
}

$done   = 0;
$skip   = 0;
$failed = 0;

foreach ( $groups as $no => $rows ) {
    $title  = "группа {$no} «{$rows[0]['Name']}»";
    $parent = $merged_parent( $rows );

    if ( $undo ) {
        if ( ! $parent ) {
            echo "{$title}: не склеена — откатывать нечего\n";
            ++$skip;
            continue;
        }

        $ids = array_map( 'intval', (array) $parent->get_meta( '_promix_variants_sources' ) );

        // WordPress сам чистит корзину через 30 дней (EMPTY_TRASH_DAYS). Без всех
        // исходных товаров откат удалил бы товар с вариантами и ничего не вернул.
        $gone = array_filter( $ids, static fn ( $id ) => 'trash' !== get_post_status( $id ) );

        if ( $gone ) {
            echo "{$title}: откат невозможен — исходных товаров нет в корзине (" . implode( ', ', $gone ) . '), товар ' . $parent->get_id() . " оставлен как есть\n";
            ++$failed;
            continue;
        }

        echo "{$title}: " . ( $dry ? 'будет откат' : 'откат' ) . " товара {$parent->get_id()}, вернутся " . count( $ids ) . " товара\n";

        if ( ! $dry ) {
            $parent->delete( true ); // С вариантами: освобождает артикулы.
            $restore( $ids );
        }

        ++$done;
        continue;
    }

    if ( $parent ) {
        echo "{$title}: уже склеена — товар {$parent->get_id()}\n";
        ++$skip;
        continue;
    }

    $errors = $problems( $rows, $sources, $notes );

    if ( $errors ) {
        echo "{$title}: не склеиваем\n";

        foreach ( $errors as $e ) {
            echo "    ✗ {$e}\n";
        }

        ++$failed;
        continue;
    }

    $parts = array();

    foreach ( $sources as $i => $product ) {
        $parts[] = "{$rows[ $i ]['Option']} ({$product->get_sku()}, " . ( '' !== $product->get_regular_price() ? $product->get_regular_price() . ' ₽' : 'без цены' ) . ')';
    }

    echo "{$title}: " . ( $dry ? 'будет склеена' : 'склеиваю' ) . " — {$rows[0]['Attribute']}: " . implode( ', ', $parts ) . "\n";

    foreach ( $notes as $n ) {
        echo "    ! {$n}\n";
    }

    if ( $dry ) {
        ++$done;
        continue;
    }

    $made = array( 'parent' => 0, 'trashed' => array(), 'variations' => array() );

    try {
        $product = $merge( $rows, $sources, $made );
        echo "    готово: товар {$product->get_id()} " . get_permalink( $product->get_id() ) . "\n";
        ++$done;
    } catch ( Throwable $e ) {
        // Откат группы: заведённое удалить, исходные вернуть.
        foreach ( $made['variations'] as $id ) {
            wp_delete_post( $id, true );
        }

        if ( $made['parent'] ) {
            wp_delete_post( $made['parent'], true );
        }

        $restore( $made['trashed'] );
        echo "    ✗ ошибка записи, группа откачена: {$e->getMessage()}\n";
        ++$failed;
    }
}

if ( ! $dry && $done ) {
    delete_transient( 'promix_catalog_total' );
    delete_transient( 'promix_catalog_paths' );
    wc_delete_product_transients();
}

if ( $undo ) {
    echo "\n" . ( $done ? ( $dry ? 'прогон без записи: ' : '' ) . "откачено {$done}" : 'нечего откатывать' ) . ( $failed ? ", откатить нельзя: {$failed}" : '' ) . "\n";
} else {
    echo "\n" . ( $dry ? 'прогон без записи: можно склеить ' : 'склеено ' ) . "{$done}, уже склеено {$skip}, с ошибками {$failed}\n";
}
