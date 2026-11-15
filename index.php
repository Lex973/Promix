<?php
/**
 * Запасной шаблон: WordPress требует его в любой теме.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="section">
    <div class="container">
        <?php if ( have_posts() ) : ?>
            <?php
            while ( have_posts() ) :
                the_post();
                ?>
                <article <?php post_class(); ?>>
                    <h2 class="section__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <div class="about__text"><?php the_excerpt(); ?></div>
                </article>
                <?php
            endwhile;
            ?>
        <?php else : ?>
            <p class="about__text"><?php esc_html_e( 'Записей пока нет.', 'promix' ); ?></p>
        <?php endif; ?>
    </div>
</section>

<?php
get_footer();
