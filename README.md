# PROMIX

Тема WordPress для малярного центра PROMIX (Казань).
Репозиторий клонируется в `wp-content/themes/promix`.

## Структура

- `functions.php` — настройки темы и подключение ассетов
- `header.php`, `footer.php` — шапка с мобильным меню и подвал
- `front-page.php` — главная, собирается из секций
- `template-parts/home/` — секции главной: hero, catalog, brands, about, why, reviews, contacts
- `template-parts/` — общие части: логотип, кнопка MAX, окно заявки, плашка про cookie
- `inc/` — поля админки (`fields/`), форма заявки, меню, иконки, корзина и оформление,
  MAX, почта через SMTP (`mail.php`, константы в `wp-config.php`)
- `assets/css/variables.css` — дизайн-токены: цвета, шрифты, сетка, радиусы
- `assets/css/` — стили по кускам страницы: `base`, `header`, `footer`, `modal` подключаются
  везде, `home` — только на главной, `notfound` — только на 404
- `assets/js/` — `main.js` (шапка, меню, якоря), `lead.js` (форма заявки),
  `cookie.js` (плашка про cookie), `brands.js` и `reviews.js` (ленты на главной)
- `assets/fonts/` — Onest и Unbounded, переменные woff2, отдельно кириллица и латиница
- `assets/icons/` — исходники иконок Lucide (лицензия ISC), в разметку вставляются инлайном
- `assets/img/` — фотографии зала и логотип (по мере переноса секций уезжают в медиабиблиотеку)
- `_content/` — исходники текстов страниц, которые заводятся в админке
- `_static/index.html` — согласованная статика, архив (см. `_static/README.md`)

## Стек

WordPress + WooCommerce + Carbon Fields. Содержимое главной и контакты редактируются
полями Carbon Fields, поля объявлены кодом в `inc/fields/`. Каталог — на WooCommerce.
Заявки с сайта сохраняются в разделе «Заявки» и дублируются письмом; точка подключения
для отправки в MAX или CRM — хук `promix_lead_created`.

## Перенос на сервер

Пошаговая инструкция, проверенная на чистом WordPress, — в `DEPLOY.md`.
Деплоить архивом, а не `git pull` на сервере:

    git archive --format=zip -o promix.zip HEAD

`export-ignore` в `.gitattributes` выкидывает из архива `_tools/`, `_content/`,
`_static/` — им на хостинге делать нечего. При `git pull` эти папки и
`composer.json`, `composer.lock`, `README.md` окажутся в `wp-content/themes/promix/`
и будут отдаваться по прямой ссылке. На Apache их закрывает `.htaccess` в корне
темы; под чистым nginx добавить в server-блок:

    location ~ ^/wp-content/themes/promix/(_|.*\.(json|lock|md)$) { return 404; }

После переноса проверить: `/wp-content/themes/promix/README.md` и
`/wp-content/themes/promix/_static/index.html` отвечают 404.

## Как поднять локально

1. Создать сайт в Local: PHP 8.2+, свежий WordPress.
2. Клонировать репозиторий в `app/public/wp-content/themes/promix`.
3. Активировать тему PROMIX. Carbon Fields лежит в `vendor/` и ставится вместе с темой,
   composer на хостинге не нужен.
4. В «Настройки → Чтение» выбрать статическую главную страницу.
5. В «Настройки → Конфиденциальность» назначить страницу политики — от неё зависят
   ссылки в подвале, в форме заявки и в плашке про cookie.
