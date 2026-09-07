<?php
/**
 * Поля секции «О компании» на главной странице.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', __( 'О компании', 'promix' ) )
    ->where( 'post_type', '=', 'page' )
    ->where( 'post_id', '=', (int) get_option( 'page_on_front' ) )
    ->add_fields(
        array(
            Field::make( 'text', 'promix_about_title', __( 'Заголовок', 'promix' ) ),

            Field::make( 'image', 'promix_about_photo', __( 'Фото зала', 'promix' ) )
                ->set_value_type( 'id' )
                ->set_width( 30 ),

            Field::make( 'text', 'promix_about_photo_alt', __( 'Описание фото', 'promix' ) )
                ->set_help_text( __( 'Короткая фраза о том, что на снимке: её читают поисковики и программы для незрячих.', 'promix' ) )
                ->set_width( 70 ),

            Field::make( 'textarea', 'promix_about_lead', __( 'Первый абзац', 'promix' ) )
                ->set_rows( 3 )
                ->set_help_text( __( 'Набран крупным жирным шрифтом.', 'promix' ) ),

            Field::make( 'textarea', 'promix_about_text', __( 'Второй абзац', 'promix' ) )
                ->set_rows( 4 ),

            Field::make( 'complex', 'promix_about_facts', __( 'Цифры о компании', 'promix' ) )
                ->set_layout( 'tabbed-horizontal' )
                ->set_help_text( __( 'Выводятся в две колонки под текстом.', 'promix' ) )
                ->setup_labels(
                    array(
                        'singular_name' => __( 'Цифру', 'promix' ),
                        'plural_name'   => __( 'Цифры', 'promix' ),
                    )
                )
                ->add_fields(
                    array(
                        Field::make( 'select', 'fact_icon', __( 'Иконка', 'promix' ) )
                            ->set_options( 'promix_icon_choices' )
                            ->set_width( 30 ),

                        Field::make( 'text', 'fact_value', __( 'Значение', 'promix' ) )
                            ->set_width( 30 ),

                        Field::make( 'text', 'fact_label', __( 'Подпись', 'promix' ) )
                            ->set_width( 40 ),
                    )
                ),

            Field::make( 'separator', 'promix_about_seminars_sep', __( 'Полоса про семинары', 'promix' ) ),

            Field::make( 'checkbox', 'promix_about_seminars_off', __( 'Скрыть полосу', 'promix' ) )
                ->set_option_value( 'yes' ),

            Field::make( 'textarea', 'promix_about_seminars_title', __( 'Заголовок полосы', 'promix' ) )
                ->set_rows( 2 )
                ->set_conditional_logic(
                    array(
                        array(
                            'field' => 'promix_about_seminars_off',
                            'value' => false,
                        ),
                    )
                ),

            Field::make( 'textarea', 'promix_about_seminars_text', __( 'Текст полосы', 'promix' ) )
                ->set_rows( 3 )
                ->set_conditional_logic(
                    array(
                        array(
                            'field' => 'promix_about_seminars_off',
                            'value' => false,
                        ),
                    )
                ),
        )
    );
