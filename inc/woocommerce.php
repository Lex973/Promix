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
 * @param string $title Строка, из которой делается адрес.
 * @return string
 */
function promix_translit( string $title ): string {
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
