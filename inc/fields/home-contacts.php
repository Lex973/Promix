<?php
/**
 * Поля секции «Где нас найти».
 *
 * Сами контакты берутся из общих настроек — здесь только тексты секции.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', __( 'Где нас найти', 'promix' ) )
    ->where( 'post_type', '=', 'page' )
    ->where( 'post_id', '=', (int) get_option( 'page_on_front' ) )
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
        )
    );
