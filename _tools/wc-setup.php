<?php
$key = 'СЕКРЕТ';
// Ключ должен быть заменён на случайную строку из 12+ латинских букв и цифр — с плейсхолдером скрипт не работает.
if (!preg_match('/^[a-z0-9]{12,}$/i', $key) || ($_GET['k'] ?? '') !== $key) { http_response_code(403); exit('no'); }
require __DIR__ . '/wp-load.php';
header('Content-Type: text/plain; charset=utf-8');

$set = function (string $k, $v) { update_option($k, $v); echo "$k = " . (is_scalar($v) ? var_export($v, true) : json_encode($v, JSON_UNESCAPED_UNICODE)) . "\n"; };

// Страница каталога: слаг catalog, она же — «магазин» для Woo.
$catalog = get_page_by_path('katalog') ?: get_page_by_path('catalog');
if (!$catalog) exit("no catalog page\n");
wp_update_post(['ID' => $catalog->ID, 'post_name' => 'catalog']);
echo "catalog page {$catalog->ID} → /" . get_post($catalog->ID)->post_name . "/\n";
$set('woocommerce_shop_page_id', $catalog->ID);

// Лишние страницы: shop от Woo, черновик возвратов, «Пример страницы», личный кабинет (регистрации нет).
foreach (['shop', 'refund_returns', 'sample-page', 'my-account'] as $slug) {
    $p = get_page_by_path($slug, OBJECT, 'page') ?: get_page_by_path($slug);
    if (!$p) { $q = get_posts(['post_type'=>'page','name'=>$slug,'post_status'=>'any','numberposts'=>1]); $p = $q[0] ?? null; }
    if ($p && (int)$p->ID !== (int)$catalog->ID) { wp_trash_post($p->ID); echo "trashed {$slug} ({$p->ID})\n"; }
}

// Адреса.
$set('woocommerce_permalinks', [
    'product_base' => 'product',
    'category_base' => 'catalog',
    'tag_base' => 'product-tag',
    'attribute_base' => '',
    'use_verbose_page_rules' => false,
]);

// Магазин: Россия, Казань, рубли без копеек.
$set('woocommerce_default_country', 'RU');
$set('woocommerce_store_city', 'Казань');
$set('woocommerce_store_address', function_exists('carbon_get_theme_option') ? (string) carbon_get_theme_option('promix_address') : '');
$set('woocommerce_allowed_countries', 'specific');
$set('woocommerce_specific_allowed_countries', ['RU']);
$set('woocommerce_ship_to_countries', 'disabled');
$set('woocommerce_currency', 'RUB');
$set('woocommerce_currency_pos', 'right_space');
$set('woocommerce_price_thousand_sep', ' ');
$set('woocommerce_price_decimal_sep', ',');
$set('woocommerce_price_num_decimals', '0');
$set('woocommerce_weight_unit', 'kg');
$set('woocommerce_dimension_unit', 'cm');
$set('woocommerce_calc_taxes', 'no');

// Что не нужно: отзывы, рейтинги, купоны, учёт остатков.
$set('woocommerce_enable_reviews', 'no');
$set('woocommerce_enable_review_rating', 'no');
$set('woocommerce_enable_coupons', 'no');
$set('woocommerce_manage_stock', 'no');

// Оформление гостем, без регистрации.
$set('woocommerce_enable_guest_checkout', 'yes');
$set('woocommerce_enable_checkout_login_reminder', 'no');
$set('woocommerce_enable_signup_and_login_from_checkout', 'no');
$set('woocommerce_enable_myaccount_registration', 'no');
$set('woocommerce_myaccount_page_id', 0);

// Страницы Woo по-русски (перевод плагина на момент установки мог ещё не стоять).
foreach (['cart' => 'Корзина', 'checkout' => 'Оформление заказа'] as $k => $t) {
    $id = wc_get_page_id($k);
    if ($id > 0) { wp_update_post(['ID' => $id, 'post_title' => $t, 'post_content' => '']); echo "$k #$id → $t
"; }
}

// Служебное: заказы в своих таблицах, без мастера, подсказок и слежки.
$set('woocommerce_custom_orders_table_enabled', 'yes');
$set('woocommerce_custom_orders_table_data_sync_enabled', 'no');
$set('woocommerce_task_list_hidden', 'yes');
$set('woocommerce_extended_task_list_hidden', 'yes');
$set('woocommerce_onboarding_profile', ['skipped' => true, 'completed' => true]);
$set('woocommerce_show_marketplace_suggestions', 'no');
$set('woocommerce_allow_tracking', 'no');
$set('woocommerce_feature_order_attribution_enabled', 'no');
$set('woocommerce_remote_logging_enabled', 'no');
$set('woocommerce_analytics_enabled', 'no');
$set('woocommerce_coming_soon', 'no');
$set('woocommerce_admin_notices', []);
$set('woocommerce_demo_store', 'no');

flush_rewrite_rules();
echo "\nrewrite flushed\n";
