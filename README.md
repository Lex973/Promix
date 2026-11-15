# PROMIX

Тема WordPress для малярного центра PROMIX (Казань).
Репозиторий клонируется в `wp-content/themes/promix`.

**Первый запуск на другом ПК:** [пошаговая инструкция LOCAL-SETUP.md](LOCAL-SETUP.md)
— установка программ, клонирование, запуск темы, восстановление наполненного
магазина и сохранение изменений в GitHub.

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
                           cert*.webp — образец сертификата, scheme*.webp — схема
                           проезда (исходники в docs/client), office/ — фото офиса
                           (8.png — исходник фона)
    vendor/                Carbon Fields через composer, коммитится
    _content/              исходники текстов страниц, которые заводятся в админке
    _tools/                скрипты развёртывания и импорта товаров (см. _tools/README.md)
    docs/                  документы заказчика, выгрузки из 1С, отчёты — вне git,
                           кроме docs/data/wc-variants.csv (защита вариантов при импорте)

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

`export-ignore` в `.gitattributes` выкидывает из архива `_tools/`, `_content/`,
`README.md`, `DEPLOY.md` и `composer.*` — им на хостинге делать нечего. При `git pull` эти папки и
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

## Продолжение работы на другом ПК

Актуальная объединённая версия проекта хранится в ветке `main`:

    git clone https://github.com/Lex973/Promix.git promix

Разместить клонированную папку в `wp-content/themes/promix` локального сайта.
Для окружения использовать PHP 8.2+ и установить WooCommerce. Carbon Fields
уже включён в `vendor/`; отдельная сборка JavaScript не требуется.

Репозиторий содержит тему, служебные скрипты, инструкции и память проекта
в `.codex/memory.md`. База данных, ядро WordPress, плагины, медиатека
`wp-content/uploads` и `wp-config.php` переносятся отдельно. Без восстановления
актуальной базы и медиатеки клонирование не воспроизводит наполненный магазин.
Для точной копии нужны свежие база и загрузки с рабочего сайта; старые дампы
до миграций не отражают текущий каталог. После восстановления базы на локальном
адресе заменить URL средствами WordPress с поддержкой сериализованных данных.
Доступы SMTP, MAX и другие секреты настроить отдельно в локальном `wp-config.php`.

`docs/data/wc-variants.csv` хранится в Git: он защищает объединённые SKU от
повторного импорта простыми товарами. Сохранять этот файл рядом с используемым
прайсом; подробности в `_tools/README.md`. Остальные документы, исходные прайсы,
отчёты с рабочими снимками и дампы остаются вне публичного репозитория.
Итоги объединения каталога сохранены в `REPORT-VARIANT-PUBLISHED-2026-11-13.md`;
исходный аудит и спорные группы — в двух соседних отчётах `REPORT-VARIANT-*.md`.

Для переноса темы на хостинг использовать `git archive`, как описано выше:
память, отчёты и данные импорта исключены из архива темы через `.gitattributes`.
