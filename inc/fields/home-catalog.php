<?php
/**
 * Поля секции каталога на главной странице.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Field;

promix_front_container( __( 'Каталог', 'promix' ) )
    ->add_fields(
        array(
            Field::make( 'text', 'promix_catalog_kicker', __( 'Надзаголовок', 'promix' ) )
                ->set_help_text( __( 'Мелкая красная строка над заголовком.', 'promix' ) )
                ->set_width( 30 ),

            Field::make( 'textarea', 'promix_catalog_title', __( 'Заголовок', 'promix' ) )
                ->set_rows( 2 )
                ->set_help_text( __( 'Каждая строка — отдельная строка заголовка.', 'promix' ) )
                ->set_width( 70 ),

            Field::make( 'textarea', 'promix_catalog_lead', __( 'Описание справа', 'promix' ) )
                ->set_rows( 3 ),

            Field::make( 'complex', 'promix_catalog_items', __( 'Категории', 'promix' ) )
                ->set_layout( 'tabbed-vertical' )
                ->set_help_text( __( 'Плитки в сетке. Номера проставляются сами по порядку.', 'promix' ) )
                ->setup_labels(
                    array(
                        'singular_name' => __( 'Категорию', 'promix' ),
                        'plural_name'   => __( 'Категории', 'promix' ),
                    )
                )
                ->add_fields(
                    array(
                        Field::make( 'text', 'cat_title', __( 'Название', 'promix' ) )
                            ->set_width( 40 ),

                        Field::make( 'text', 'cat_url', __( 'Ссылка', 'promix' ) )
                            ->set_help_text( __( 'Раздел каталога. Пока каталога нет, можно оставить #.', 'promix' ) )
                            ->set_width( 60 ),

                        Field::make( 'textarea', 'cat_text', __( 'Описание', 'promix' ) )
                            ->set_rows( 2 ),
                    )
                ),

            Field::make( 'separator', 'promix_catalog_wide_sep', __( 'Широкая плитка «Весь каталог»', 'promix' ) ),

            Field::make( 'image', 'promix_catalog_wide_image', __( 'Фото плитки', 'promix' ) )
                ->set_value_type( 'id' )
                ->set_help_text( __( 'Кадр затемняется, поверх ложится белый текст.', 'promix' ) )
                ->set_width( 34 ),

            Field::make( 'text', 'promix_catalog_wide_title', __( 'Заголовок плитки', 'promix' ) )
                ->set_width( 33 ),

            Field::make( 'text', 'promix_catalog_wide_url', __( 'Ссылка плитки', 'promix' ) )
                ->set_width( 33 ),

            Field::make( 'text', 'promix_catalog_wide_note', __( 'Подпись на плитке', 'promix' ) ),
        )
    );
