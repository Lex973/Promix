# PROMIX

Тема WordPress для малярного центра PROMIX (Казань).
Репозиторий клонируется в `wp-content/themes/promix`.

## Структура

Корень репозитория — сама тема, поэтому шаблоны WordPress лежат прямо в нём.

    functions.php          настройки темы, подключение inc/ и ассетов
    header.php, footer.php шапка с мобильным меню, подвал
    front-page.php         главная — собирается из template-parts/home/
    index.php, page.php    запасной и обычная страница
    404.php                своя страница «не найдено»
    archive-product.php    каталог: разделы, бренды, поиск, фильтры
    single-product.php     страница товара
    page-cart.php          корзина
    page-checkout.php      оформление заказа
    templates/home.php     шаблон страницы «Главная PROMIX» (к нему привязаны поля)
    template-parts/        куски разметки: home/ — секции главной, catalog/ — карточка
                           товара, cart/ — список и счётчик; логотип, кнопка MAX,
                           окно заявки, плашка про cookie, крошки, уведомления
    inc/                   логика: catalog, cart, lead-form, mail (SMTP), max,
                           meta (описание и Open Graph), nav, woocommerce, icons;
                           fields.php и fields/ — поля админки на Carbon Fields,
                           *-default.php — запасные данные, пока поля пустые
    woocommerce/emails/    свои шаблоны писем о заказе
    assets/css/            variables — токены; base, header, footer, modal — везде;
                           home, catalog, product, cart, notfound — по своим страницам
    assets/js/             main (шапка, меню, якоря), lead, cookie, brands, reviews,
                           catalog, product, cart; vendor/lenis.min.js
    assets/fonts/          Onest и Unbounded, переменные woff2, кириллица и латиница
    assets/icons/          иконки Lucide (ISC), в разметку идут инлайном через inc/icons.php
    assets/img/            фон первого экрана в трёх размерах, логотип, og-картинка,
                           office/ — фото офиса (8.png — исходник фона)
    vendor/                Carbon Fields через composer, коммитится
    _content/              исходники текстов страниц, которые заводятся в админке
    _tools/                скрипты развёртывания и импорта товаров (см. _tools/README.md)
    docs/                  вне git: документы заказчика, выгрузки из 1С, отчёты

Папки с подчёркиванием — не часть темы: в архив `git archive` не попадают,
на сервере закрываются от прямых ссылок (см. «Перенос на сервер»).

## Стек

WordPress + WooCommerce + Carbon Fields. Содержимое главной и контакты редактируются
полями Carbon Fields, поля объявлены кодом в `inc/fields/`. Каталог — на WooCommerce.
Заявки с сайта сохраняются в разделе «Заявки» и дублируются письмом; точка подключения
для отправки в MAX или CRM — хук `promix_lead_created`.

## Перенос на сервер

Пошаговая инструкция, проверенная на чистом WordPress, — в `DEPLOY.md`.
Деплоить архивом, а не `git pull` на сервере:

    git archive --format=zip -o promix.zip HEAD

`export-ignore` в `.gitattributes` выкидывает из архива `_tools/` и `_content/` —
им на хостинге делать нечего. При `git pull` эти папки и
`composer.json`, `composer.lock`, `README.md` окажутся в `wp-content/themes/promix/`
и будут отдаваться по прямой ссылке. На Apache их закрывает `.htaccess` в корне
темы; под чистым nginx добавить в server-блок:

    location ~ ^/wp-content/themes/promix/(_|.*\.(json|lock|md)$) { return 404; }

После переноса проверить: `/wp-content/themes/promix/README.md` и
`/wp-content/themes/promix/_tools/` отвечают 404.

## Как поднять локально

1. Создать сайт в Local: PHP 8.2+, свежий WordPress.
2. Клонировать репозиторий в `app/public/wp-content/themes/promix`.
3. Активировать тему PROMIX. Carbon Fields лежит в `vendor/` и ставится вместе с темой,
   composer на хостинге не нужен.
4. В «Настройки → Чтение» выбрать статическую главную страницу.
5. В «Настройки → Конфиденциальность» назначить страницу политики — от неё зависят
   ссылки в подвале, в форме заявки и в плашке про cookie.
