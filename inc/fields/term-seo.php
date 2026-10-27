<?php
/**
 * Поля раздела каталога для поиска: заголовок вкладки, описание, H1.
 *
 * Пустое поле — работает шаблон из inc/meta.php. Заполнять стоит у корневых
 * и самых ходовых разделов: шаблон для них выходит слишком общим.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'term_meta', __( 'Для поиска', 'promix' ) )
    ->where( 'term_taxonomy', '=', 'product_cat' )
    ->add_fields(
        array(
            Field::make( 'text', 'promix_seo_title', __( 'Заголовок в поиске (title)', 'promix' ) )
                ->set_attribute( 'maxLength', 80 )
                ->set_help_text( __( 'До 60–65 знаков: что продаём + «в Казани». Пусто — «Название в Казани — цены в PROMIX».', 'promix' ) ),

            Field::make( 'textarea', 'promix_seo_description', __( 'Описание в поиске', 'promix' ) )
                ->set_rows( 2 )
                ->set_attribute( 'maxLength', 220 )
                ->set_help_text( __( '140–180 знаков: какие товары и марки есть в разделе. Пусто — собирается из числа позиций, цен и марок.', 'promix' ) ),

            Field::make( 'text', 'promix_seo_h1', __( 'Заголовок на странице (H1)', 'promix' ) )
                ->set_attribute( 'maxLength', 80 )
                ->set_help_text( __( 'Пусто — название раздела. В крошках и меню остаётся название.', 'promix' ) ),
        )
    );
