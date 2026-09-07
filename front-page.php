<?php
/**
 * Главная страница: секции идут в порядке согласования.
 *
 * @package promix
 */

get_header();

foreach ( array( 'hero', 'catalog', 'brands', 'about', 'why', 'reviews', 'contacts' ) as $section ) {
    get_template_part( 'template-parts/home/' . $section );
}

get_footer();
