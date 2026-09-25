<?php
/**
 * Плашка срочного объявления между шапкой и первым экраном.
 *
 * Отдельный пункт меню, а не поле в «Контактах»: объявление меняют чаще
 * и в спешке — смена режима на праздники, перекрытая дорога, сбой оплаты.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'theme_options', __( 'Объявление на сайте', 'promix' ) )
    ->set_page_file( 'promix-notice' )
    ->set_page_menu_title( __( 'Объявление', 'promix' ) )
    ->set_icon( 'dashicons-megaphone' )
    ->add_fields(
        array(
            Field::make( 'checkbox', 'promix_notice_on', __( 'Показывать объявление', 'promix' ) )
                ->set_option_value( '1' )
                ->set_help_text( __( 'Узкая плашка под шапкой на всех страницах сайта. Сняли галочку — плашка пропала, текст сохранится.', 'promix' ) ),

            Field::make( 'textarea', 'promix_notice_text', __( 'Текст', 'promix' ) )
                ->set_rows( 2 )
                ->set_attribute( 'maxLength', 200 )
                ->set_attribute( 'placeholder', __( '31 декабря работаем до 14:00, 1–8 января — выходные', 'promix' ) )
                ->set_help_text( __( 'Одно-два предложения, до 200 знаков. Длинный текст на телефоне займёт полэкрана.', 'promix' ) ),

            Field::make( 'text', 'promix_notice_link_text', __( 'Текст ссылки', 'promix' ) )
                ->set_attribute( 'placeholder', __( 'Как проехать', 'promix' ) )
                ->set_help_text( __( 'Необязательно. Пусто — объявление без ссылки.', 'promix' ) )
                ->set_width( 40 ),

            Field::make( 'text', 'promix_notice_link_url', __( 'Куда ведёт ссылка', 'promix' ) )
                ->set_attribute( 'placeholder', '#contacts' )
                ->set_help_text( __( 'Адрес страницы или раздел главной: #contacts — контакты и карта.', 'promix' ) )
                ->set_width( 60 ),

            Field::make( 'date', 'promix_notice_until', __( 'Показывать до', 'promix' ) )
                ->set_storage_format( 'Y-m-d' )
                ->set_input_format( 'd.m.Y', 'd.m.Y' )
                ->set_help_text( __( 'Включительно. На следующий день плашка скроется сама. Пусто — пока не снимут галочку.', 'promix' ) ),
        )
    );
