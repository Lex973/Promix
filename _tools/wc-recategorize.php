<?php
/**
 * Перевод каталога на разделы из документа заказчика «Для сайта.docx».
 *
 * Создаёт четыре раздела верхнего уровня и подразделы к ним, переносит
 * каждый товар (по артикулу) в свой подраздел, обновляет описания
 * (в них упоминается раздел), удаляет опустевшие старые разделы и
 * сбрасывает кэши каталога.
 *
 * Как применить: рядом со скриптом положить wc-categories.csv и
 * woo-descriptions.csv из docs/data/ (их делают wc-categories.py и
 * wc-descriptions.py), скрипт скопировать в корень сайта, заменить СЕКРЕТ,
 * открыть /wc-recategorize.php?k=СЕКРЕТ, после прогона удалить всё три файла.
 * Повторный запуск безопасен: разделы не дублируются, товары просто
 * переназначаются ещё раз.
 *
 * ?dry=1 — только показать, что будет сделано, без записи в базу.
 */

$key = 'СЕКРЕТ';

// Ключ должен быть заменён на случайную строку из 12+ латинских букв и цифр:
// с плейсхолдером из README скрипт не запускается, иначе ключ знает каждый.
if ( ! preg_match( '/^[a-z0-9]{12,}$/i', $key ) || ! hash_equals( $key, (string) ( $_GET['k'] ?? '' ) ) ) {
    http_response_code( 403 );
    exit( 'no' );
}

require __DIR__ . '/wp-load.php';

set_time_limit( 0 );
header( 'Content-Type: text/plain; charset=utf-8' );
wp_set_current_user( 1 ); // Без прав manage_product_terms разделы не создаются.

$dry = ! empty( $_GET['dry'] );

$categories_file   = __DIR__ . '/wc-categories.csv';
$descriptions_file = __DIR__ . '/woo-descriptions.csv';

if ( ! is_readable( $categories_file ) ) {
    exit( "Нет файла wc-categories.csv рядом со скриптом\n" );
}

// Порядок разделов — как в документе, чтобы в админке и фильтре шли так же.
$structure = array(
    'Оборудование'        => array( 'Окрасочное', 'Шлифовальное', 'Пылеудаляющее', 'Освещение', 'Аксессуары и запчасти' ),
    'Материалы'           => array( 'Шпатлевки', 'Грунты', 'Клей', 'Краски', 'Декоративные штукатурки', 'Стеклохолст и флизелин', 'Материалы для углов, швов и примыканий ГКЛ' ),
    'Инструмент'          => array( 'Валики для шпатлевки', 'Валики, рукоятки, держатели', 'Кисти', 'Лотки для краски и шпатлевки', 'Шпатели и правила', 'Инструменты для малярных лент и пленок', 'Шлифовальный инструмент', 'Пылеудаляющий инструмент', 'Инструменты для углоформирующих лент', 'Кельмы и гладилки' ),
    'Расходные материалы' => array( 'Ленты бумажные и композитные', 'Малярные ленты', 'Укрывные пленки и бумага', 'Шлифовальный материал', 'СИЗ' ),
);

/**
 * Раздел по названию и родителю: найти или создать.
 */
function promix_term( string $name, int $parent, int $order, bool $dry ): int {
    $existing = get_terms(
        array(
            'taxonomy'   => 'product_cat',
            'name'       => $name,
            'parent'     => $parent,
            'hide_empty' => false,
            'fields'     => 'ids',
        )
    );

    if ( ! is_wp_error( $existing ) && $existing ) {
        $id = (int) $existing[0];
    } elseif ( $dry ) {
        echo "  + создать «{$name}»\n";
        return 0;
    } else {
        $result = wp_insert_term( $name, 'product_cat', array( 'parent' => $parent ) );

        if ( is_wp_error( $result ) ) {
            exit( "Не удалось создать «{$name}»: " . $result->get_error_message() . "\n" );
        }

        $id = (int) $result['term_id'];
        echo "  + создан «{$name}» (#{$id})\n";
    }

    if ( ! $dry ) {
        update_term_meta( $id, 'order', $order ); // Порядок разделов WooCommerce хранит здесь.
    }

    return $id;
}

$old_ids = get_terms(
    array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'fields'     => 'ids',
    )
);
$old_ids = is_wp_error( $old_ids ) ? array() : array_map( 'intval', $old_ids );

echo "Разделы:\n";
$term_ids = array(); // «Подраздел» => term_id.
$order    = 0;

foreach ( $structure as $parent_name => $children ) {
    $parent_id = promix_term( $parent_name, 0, ++$order, $dry );

    foreach ( $children as $child_name ) {
        $term_ids[ $child_name ] = promix_term( $child_name, $parent_id, ++$order, $dry );
    }
}

// --- Описания: артикул => [краткое, полное].
$descriptions = array();

if ( is_readable( $descriptions_file ) ) {
    $fh = fopen( $descriptions_file, 'r' );
    fgetcsv( $fh );

    while ( ( $row = fgetcsv( $fh ) ) !== false ) {
        if ( count( $row ) >= 3 ) {
            $descriptions[ $row[0] ] = array( $row[1], $row[2] );
        }
    }

    fclose( $fh );
}

// --- Товары.
$fh = fopen( $categories_file, 'r' );
$header = fgetcsv( $fh, 0, ';' );
$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] ); // BOM от Excel/Python.

$done = $missing = $unknown = $described = 0;

while ( ( $row = fgetcsv( $fh, 0, ';' ) ) !== false ) {
    list( $sku, , $sub ) = $row;

    if ( ! isset( $term_ids[ $sub ] ) ) {
        echo "  ? неизвестный подраздел «{$sub}» у {$sku}\n";
        ++$unknown;
        continue;
    }

    $product_id = wc_get_product_id_by_sku( $sku );

    if ( ! $product_id ) {
        echo "  ? нет товара с артикулом {$sku}\n";
        ++$missing;
        continue;
    }

    if ( $dry ) {
        ++$done;
        continue;
    }

    wp_set_object_terms( $product_id, array( $term_ids[ $sub ] ), 'product_cat', false );

    if ( isset( $descriptions[ $sku ] ) ) {
        $product = wc_get_product( $product_id );
        $product->set_short_description( $descriptions[ $sku ][0] );
        $product->set_description( $descriptions[ $sku ][1] );
        $product->save();
        ++$described;
    }

    ++$done;
}

fclose( $fh );

echo "\nТоваров перенесено: {$done}, описаний обновлено: {$described}, не найдено: {$missing}, с неизвестным подразделом: {$unknown}\n";

if ( $dry ) {
    exit( "\nПробный прогон, база не тронута.\n" );
}

// --- Пересчёт и уборка старых разделов.
if ( function_exists( 'wc_recount_all_terms' ) ) {
    wc_recount_all_terms();
}

$default = (int) get_option( 'default_product_cat' );
$keep    = array_merge( array( $default ), array_values( $term_ids ) );

foreach ( $structure as $parent_name => $children ) {
    $parent = get_term_by( 'name', $parent_name, 'product_cat' );
    if ( $parent ) {
        $keep[] = (int) $parent->term_id;
    }
}

echo "\nСтарые разделы:\n";

foreach ( $old_ids as $id ) {
    if ( in_array( $id, $keep, true ) ) {
        continue;
    }

    $term    = get_term( $id, 'product_cat' );
    $objects = get_objects_in_term( $id, 'product_cat' );
    $count   = is_wp_error( $objects ) ? 0 : count( $objects );

    if ( $count > 0 ) {
        echo "  ! «{$term->name}» не удалён: в нём ещё {$count} товаров\n";
        continue;
    }

    wp_delete_term( $id, 'product_cat' );
    echo "  - удалён пустой «{$term->name}»\n";
}

// --- Кэши каталога и WooCommerce.
if ( function_exists( 'promix_catalog_links_flush' ) ) {
    promix_catalog_links_flush();
}
delete_transient( 'promix_catalog_total' );
delete_transient( 'wc_term_counts' );
wc_delete_product_transients();
clean_taxonomy_cache( 'product_cat' );
delete_option( 'rewrite_rules' ); // Новые адреса разделов: правила перестроятся при первом запросе.

echo "\nГотово.\n";
