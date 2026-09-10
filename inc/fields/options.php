<?php
/**
 * Общие настройки сайта: контакты и ссылки.
 *
 * Телефон, адрес и режим работы повторяются в шапке, меню, секции контактов
 * и подвале, поэтому живут в одном месте — меняются разом.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'theme_options', __( 'Контакты PROMIX', 'promix' ) )
    ->set_page_menu_title( __( 'Контакты PROMIX', 'promix' ) )
    ->set_icon( 'dashicons-store' )
    ->add_fields(
        array(
            Field::make( 'text', 'promix_phone', __( 'Телефон', 'promix' ) )
                ->set_help_text( __( 'Как показывать на сайте: +7 (953) 484-00-00. Ссылка «позвонить» соберётся сама.', 'promix' ) )
                ->set_width( 50 ),

            Field::make( 'text', 'promix_address', __( 'Адрес', 'promix' ) )
                ->set_width( 50 ),

            Field::make( 'text', 'promix_hours', __( 'Режим работы', 'promix' ) )
                ->set_help_text( __( 'Основная строка: Пн–Пт 9:00–18:00', 'promix' ) )
                ->set_width( 50 ),

            Field::make( 'text', 'promix_hours_extra', __( 'Режим работы, вторая строка', 'promix' ) )
                ->set_help_text( __( 'Например: Сб 9:00–14:00 · Вс — выходной', 'promix' ) )
                ->set_width( 50 ),

            Field::make( 'text', 'promix_lead_email', __( 'Почта для заявок', 'promix' ) )
                ->set_attribute( 'type', 'email' )
                ->set_help_text( __( 'Пусто — письма уходят на адрес администратора сайта.', 'promix' ) ),

            Field::make( 'separator', 'promix_seo_sep', __( 'Для поиска и мессенджеров', 'promix' ) ),

            Field::make( 'textarea', 'promix_meta_description', __( 'Описание главной', 'promix' ) )
                ->set_rows( 2 )
                ->set_help_text( __( 'Показывается в результатах поиска под заголовком. Две-три строки о магазине.', 'promix' ) ),

            Field::make( 'image', 'promix_og_image', __( 'Картинка для ссылок', 'promix' ) )
                ->set_help_text( __( 'Видна, когда ссылку на сайт отправляют в мессенджер. Лучше от 1200×630. Если пусто — логотип.', 'promix' ) ),

            Field::make( 'separator', 'promix_links_sep', __( 'Ссылки', 'promix' ) ),

            Field::make( 'text', 'promix_max_url', __( 'Ссылка на MAX', 'promix' ) )
                ->set_help_text( __( 'Кнопка «Написать в MAX» в шапке, меню, контактах и подвале.', 'promix' ) ),

            Field::make( 'text', 'promix_2gis_url', __( 'Карточка в 2ГИС', 'promix' ) )
                ->set_width( 50 ),

            Field::make( 'text', 'promix_yandex_url', __( 'Карточка в Яндекс Картах', 'promix' ) )
                ->set_width( 50 ),

            Field::make( 'text', 'promix_map_embed', __( 'Адрес виджета карты', 'promix' ) )
                ->set_help_text( __( 'Ссылка вида https://yandex.ru/map-widget/v1/org/... — её показывает карта в секции контактов.', 'promix' ) ),
        )
    );
