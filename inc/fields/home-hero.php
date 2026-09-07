<?php
/**
 * Поля первого экрана главной страницы.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', __( 'Первый экран', 'promix' ) )
    ->where( 'post_type', '=', 'page' )
    ->where( 'post_id', '=', (int) get_option( 'page_on_front' ) )
    ->add_fields(
        array(
            Field::make( 'image', 'promix_hero_image', __( 'Фоновое фото', 'promix' ) )
                ->set_value_type( 'id' )
                ->set_help_text( __( 'Широкий кадр зала. Поверх фото ложится затемнение, поэтому светлые снимки читаются лучше.', 'promix' ) )
                ->set_width( 40 ),

            Field::make( 'textarea', 'promix_hero_title', __( 'Заголовок', 'promix' ) )
                ->set_rows( 2 )
                ->set_help_text( __( 'Каждая строка — отдельная строка заголовка на сайте.', 'promix' ) )
                ->set_width( 60 ),

            Field::make( 'textarea', 'promix_hero_lead', __( 'Подзаголовок', 'promix' ) )
                ->set_rows( 3 ),

            Field::make( 'text', 'promix_hero_cta_text', __( 'Текст кнопки', 'promix' ) )
                ->set_width( 50 ),

            Field::make( 'text', 'promix_hero_cta_url', __( 'Ссылка кнопки', 'promix' ) )
                ->set_help_text( __( 'Якорь вида #catalog или полный адрес страницы.', 'promix' ) )
                ->set_width( 50 ),

            Field::make( 'complex', 'promix_hero_stats', __( 'Цифры', 'promix' ) )
                ->set_layout( 'tabbed-horizontal' )
                ->set_max( 4 )
                ->set_help_text( __( 'До четырёх цифр в ряд под кнопкой.', 'promix' ) )
                ->add_fields(
                    array(
                        Field::make( 'text', 'value', __( 'Значение', 'promix' ) )
                            ->set_help_text( __( 'Например: 10+ лет', 'promix' ) )
                            ->set_width( 40 ),

                        Field::make( 'text', 'label', __( 'Подпись', 'promix' ) )
                            ->set_width( 60 ),
                    )
                )
                ->set_header_template( '<%- value %>' ),
        )
    );
