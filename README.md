# PROMIX

Тема WordPress для малярного центра PROMIX (Казань).
Репозиторий клонируется в `wp-content/themes/promix`.

## Структура

- `functions.php` — настройки темы и подключение ассетов
- `header.php`, `footer.php` — шапка с мобильным меню и подвал
- `front-page.php` — главная, собирается из секций
- `template-parts/home/` — секции главной: hero, catalog, brands, about, why, reviews, contacts
- `assets/css/variables.css` — дизайн-токены: цвета, шрифты, сетка, радиусы
- `assets/css/style.css` — стили сайта
- `assets/js/` — `main.js` (шапка и меню), `brands.js` (бегущие строки), `reviews.js` (карусель отзывов)
- `assets/icons/` — исходники иконок Lucide (лицензия ISC), в разметку вставлены инлайном
- `assets/img/` — фотографии зала и логотип (по мере переноса секций уезжают в медиабиблиотеку)
- `_static/index.html` — согласованная статика, эталон для сверки вёрстки

## Стек

WordPress + WooCommerce + ACF Pro. Содержимое главной редактируется полями ACF,
каталог — на WooCommerce. Заказ уходит заявкой менеджеру в MAX через серверный
endpoint (токен только в `wp-config.php`, не на клиенте).

## Как поднять локально

1. Создать сайт в Local: PHP 8.2+, свежий WordPress.
2. Клонировать репозиторий в `app/public/wp-content/themes/promix`.
3. Активировать тему PROMIX, поставить ACF Pro.
4. В «Настройки → Чтение» выбрать статическую главную страницу.
