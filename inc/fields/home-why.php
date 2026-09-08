<?php
/**
 * Поля секции «Почему выбирают PROMIX».
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', __( 'Почему выбирают', 'promix' ) )
    ->where( 'post_type', '=', 'page' )
    ->where( 'post_id', '=', (int) get_option( 'page_on_front' ) )
    ->add_fields(
        array(
            Field::make( 'text', 'promix_why_kicker', __( 'Надзаголовок', 'promix' ) )
                ->set_width( 30 ),

            Field::make( 'textarea', 'promix_why_title', __( 'Заголовок', 'promix' ) )
                ->set_rows( 2 )
                ->set_width( 70 ),

            Field::make( 'textarea', 'promix_why_lead', __( 'Описание справа', 'promix' ) )
                ->set_rows( 3 ),

            Field::make( 'complex', 'promix_why_items', __( 'Карточки', 'promix' ) )
                ->set_layout( 'tabbed-vertical' )
                ->set_help_text( __( 'Идут сеткой по три в ряд.', 'promix' ) )
                ->setup_labels(
                    array(
                        'singular_name' => __( 'Карточку', 'promix' ),
                        'plural_name'   => __( 'Карточки', 'promix' ),
                    )
                )
                ->add_fields(
                    array(
                        Field::make( 'select', 'why_icon', __( 'Иконка', 'promix' ) )
                            ->set_options( 'promix_icon_choices' )
                            ->set_width( 30 ),

                        Field::make( 'text', 'why_title', __( 'Заголовок', 'promix' ) )
                            ->set_width( 70 ),

                        Field::make( 'textarea', 'why_text', __( 'Текст', 'promix' ) )
                            ->set_rows( 2 ),
                    )
                ),

            Field::make( 'separator', 'promix_why_wide_sep', __( 'Широкая карточка внизу', 'promix' ) ),

            Field::make( 'checkbox', 'promix_why_wide_off', __( 'Скрыть карточку', 'promix' ) )
                ->set_option_value( 'yes' ),

            Field::make( 'select', 'promix_why_wide_icon', __( 'Иконка', 'promix' ) )
                ->set_options( 'promix_icon_choices' )
                ->set_default_value( 'users' )
                ->set_width( 30 )
                ->set_conditional_logic( array( array( 'field' => 'promix_why_wide_off', 'value' => false ) ) ),

            Field::make( 'text', 'promix_why_wide_title', __( 'Заголовок', 'promix' ) )
                ->set_width( 70 )
                ->set_conditional_logic( array( array( 'field' => 'promix_why_wide_off', 'value' => false ) ) ),

            Field::make( 'textarea', 'promix_why_wide_text', __( 'Текст', 'promix' ) )
                ->set_rows( 2 )
                ->set_conditional_logic( array( array( 'field' => 'promix_why_wide_off', 'value' => false ) ) ),

            Field::make( 'text', 'promix_why_wide_cta_text', __( 'Текст ссылки', 'promix' ) )
                ->set_width( 50 )
                ->set_conditional_logic( array( array( 'field' => 'promix_why_wide_off', 'value' => false ) ) ),

            Field::make( 'text', 'promix_why_wide_cta_url', __( 'Адрес ссылки', 'promix' ) )
                ->set_width( 50 )
                ->set_conditional_logic( array( array( 'field' => 'promix_why_wide_off', 'value' => false ) ) ),
        )
    );
