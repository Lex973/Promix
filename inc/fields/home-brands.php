<?php
/**
 * Поля секции брендов на главной странице.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Field;

promix_front_container( __( 'Бренды', 'promix' ) )
    ->add_fields(
        array(
            Field::make( 'text', 'promix_brands_kicker', __( 'Надзаголовок', 'promix' ) )
                ->set_width( 30 ),

            Field::make( 'textarea', 'promix_brands_title', __( 'Заголовок', 'promix' ) )
                ->set_rows( 2 )
                ->set_help_text( __( 'Каждая строка — отдельная строка заголовка.', 'promix' ) )
                ->set_width( 70 ),

            Field::make( 'textarea', 'promix_brands_lead', __( 'Описание справа', 'promix' ) )
                ->set_rows( 3 ),

            Field::make( 'complex', 'promix_brands_items', __( 'Список марок', 'promix' ) )
                ->set_layout( 'tabbed-vertical' )
                ->set_help_text( __( 'Марки разъезжаются по двум лентам: первая половина списка едет влево, вторая вправо.', 'promix' ) )
                ->setup_labels(
                    array(
                        'singular_name' => __( 'Марку', 'promix' ),
                        'plural_name'   => __( 'Марки', 'promix' ),
                    )
                )
                ->add_fields(
                    array(
                        Field::make( 'text', 'brand_name', __( 'Название', 'promix' ) )
                            ->set_width( 40 ),

                        Field::make( 'text', 'brand_url', __( 'Ссылка', 'promix' ) )
                            ->set_help_text( __( 'Раздел каталога с товарами марки. Можно оставить пустым.', 'promix' ) )
                            ->set_width( 35 ),

                        Field::make( 'color', 'brand_color', __( 'Цвет при наведении', 'promix' ) )
                            ->set_help_text( __( 'Пусто — фирменный красный.', 'promix' ) )
                            ->set_width( 25 ),

                        Field::make( 'image', 'brand_logo', __( 'Логотип', 'promix' ) )
                            ->set_value_type( 'id' )
                            ->set_help_text( __( 'Необязательно. Пока логотипа нет, показывается название.', 'promix' ) ),
                    )
                ),

            Field::make( 'separator', 'promix_brands_cta_sep', __( 'Строка под лентами', 'promix' ) ),

            Field::make( 'text', 'promix_brands_note', __( 'Вопрос слева', 'promix' ) )
                ->set_width( 34 ),

            Field::make( 'text', 'promix_brands_cta_text', __( 'Текст кнопки', 'promix' ) )
                ->set_width( 33 ),

            Field::make( 'text', 'promix_brands_cta_url', __( 'Ссылка кнопки', 'promix' ) )
                ->set_width( 33 ),
        )
    );
