<?php
/**
 * Поля секции отзывов на главной странице.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Field;

promix_front_container( __( 'Отзывы', 'promix' ) )
    ->add_fields(
        array(
            Field::make( 'text', 'promix_reviews_kicker', __( 'Надзаголовок', 'promix' ) )
                ->set_width( 30 ),

            Field::make( 'textarea', 'promix_reviews_title', __( 'Заголовок', 'promix' ) )
                ->set_rows( 2 )
                ->set_width( 70 ),

            Field::make( 'separator', 'promix_reviews_rating_sep', __( 'Карточка с оценкой', 'promix' ) ),

            Field::make( 'text', 'promix_reviews_rating', __( 'Оценка', 'promix' ) )
                ->set_help_text( __( 'Например: 4,9', 'promix' ) )
                ->set_width( 20 ),

            Field::make( 'text', 'promix_reviews_count', __( 'Строка со счётчиками', 'promix' ) )
                ->set_help_text( __( 'Например: 44 отзыва · 53 оценки в 2ГИС', 'promix' ) )
                ->set_width( 40 ),

            Field::make( 'text', 'promix_reviews_btn_text', __( 'Текст кнопки', 'promix' ) )
                ->set_width( 40 ),

            Field::make( 'text', 'promix_reviews_url', __( 'Ссылка на 2ГИС', 'promix' ) )
                ->set_help_text( __( 'Одна ссылка для кнопки вверху и для строки под каруселью.', 'promix' ) )
                ->set_width( 60 ),

            Field::make( 'text', 'promix_reviews_all_text', __( 'Текст ссылки под каруселью', 'promix' ) )
                ->set_width( 40 ),

            Field::make( 'separator', 'promix_reviews_items_sep', __( 'Карусель отзывов', 'promix' ) ),

            Field::make( 'complex', 'promix_reviews_items', __( 'Отзывы', 'promix' ) )
                ->set_layout( 'tabbed-vertical' )
                ->set_help_text( __( 'Длинные отзывы обрезаются на карточке, полный текст остаётся здесь.', 'promix' ) )
                ->setup_labels(
                    array(
                        'singular_name' => __( 'Отзыв', 'promix' ),
                        'plural_name'   => __( 'Отзывы', 'promix' ),
                    )
                )
                ->add_fields(
                    array(
                        Field::make( 'text', 'review_name', __( 'Имя', 'promix' ) )
                            ->set_width( 40 ),

                        Field::make( 'text', 'review_date', __( 'Дата', 'promix' ) )
                            ->set_help_text( __( 'Как показать на карточке: май 2025.', 'promix' ) )
                            ->set_width( 30 ),

                        Field::make( 'select', 'review_rating', __( 'Оценка', 'promix' ) )
                            ->set_options(
                                array(
                                    '5' => '5',
                                    '4' => '4',
                                    '3' => '3',
                                    '2' => '2',
                                    '1' => '1',
                                )
                            )
                            ->set_width( 30 ),

                        Field::make( 'textarea', 'review_text', __( 'Текст отзыва', 'promix' ) )
                            ->set_rows( 4 ),

                        Field::make( 'textarea', 'review_reply', __( 'Ответ магазина', 'promix' ) )
                            ->set_rows( 3 )
                            ->set_help_text( __( 'Пусто — блок с ответом не показывается.', 'promix' ) ),
                    )
                ),
        )
    );
