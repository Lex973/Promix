<?php
/**
 * Поля секции «Где нас найти».
 *
 * Сами контакты берутся из общих настроек — здесь только тексты секции.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Field;

promix_front_container( __( 'Где нас найти', 'promix' ) )
    ->add_fields(
        array(
            Field::make( 'text', 'promix_contacts_kicker', __( 'Надзаголовок', 'promix' ) )
                ->set_width( 30 ),

            Field::make( 'textarea', 'promix_contacts_title', __( 'Заголовок', 'promix' ) )
                ->set_rows( 2 )
                ->set_width( 70 ),

            Field::make( 'textarea', 'promix_contacts_lead', __( 'Описание справа', 'promix' ) )
                ->set_rows( 3 ),

            Field::make( 'html', 'promix_contacts_note' )
                ->set_html(
                    '<p style="margin:0;padding:12px 14px;background:#f0f6fc;border-left:4px solid #2271b1">'
                    . esc_html__( 'Телефон, адрес, режим работы и ссылки на карты меняются в разделе «Контакты PROMIX» в боковом меню — они одни на всю страницу.', 'promix' )
                    . '</p>'
                ),

            Field::make( 'separator', 'promix_contacts_scheme_sep', __( 'Схема проезда', 'promix' ) ),

            Field::make( 'checkbox', 'promix_contacts_scheme_off', __( 'Скрыть схему', 'promix' ) )
                ->set_option_value( 'yes' ),

            Field::make( 'image', 'promix_contacts_scheme_image', __( 'Картинка схемы', 'promix' ) )
                ->set_value_type( 'id' )
                ->set_help_text( __( 'Пусто — схема из темы. Показывается во всю ширину, лучше от 1200 px.', 'promix' ) )
                ->set_width( 30 )
                ->set_conditional_logic(
                    array(
                        array(
                            'field' => 'promix_contacts_scheme_off',
                            'value' => false,
                        ),
                    )
                ),

            Field::make( 'text', 'promix_contacts_scheme_title', __( 'Заголовок схемы', 'promix' ) )
                ->set_width( 70 )
                ->set_conditional_logic(
                    array(
                        array(
                            'field' => 'promix_contacts_scheme_off',
                            'value' => false,
                        ),
                    )
                ),
        )
    );
