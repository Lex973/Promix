<?php
/**
 * Секция главной: hero.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$hero_image = promix_field( 'hero_image' );
$hero_title = promix_field( 'hero_title', "Профессиональные материалы\nдля идеального результата" );
$hero_lead  = promix_field( 'hero_lead', 'Подбор и колеровка материалов, техническая консультация, комплектация объектов под ключ — для частных клиентов, мастеров, дизайнеров и строительных компаний.' );
$cta_text   = promix_field( 'hero_cta_text', 'Перейти в каталог' );
$cta_url    = promix_field( 'hero_cta_url', '#catalog' );

$stats = promix_field(
    'hero_stats',
    array(
        array( 'stat_value' => '10+ лет', 'stat_label' => 'опыта в строительной сфере' ),
        array( 'stat_value' => '3 года',  'stat_label' => 'магазину PROMIX в Казани' ),
        array( 'stat_value' => '4,9',     'stat_label' => 'рейтинг в 2ГИС' ),
        array( 'stat_value' => '50+',     'stat_label' => 'отзывов клиентов' ),
    )
);

?>
<section class="hero">
        <?php if ( $hero_image ) : ?>
            <?php
            echo wp_get_attachment_image(
                (int) $hero_image,
                'full',
                false,
                array(
                    'class'         => 'hero__bg',
                    'alt'           => '',
                    'fetchpriority' => 'high',
                )
            );
            ?>
        <?php else : ?>
            <img class="hero__bg"
                 src="<?php echo esc_url( get_theme_file_uri( 'assets/img/main.webp' ) ); ?>"
                 srcset="<?php echo esc_url( get_theme_file_uri( 'assets/img/main-768.webp' ) ); ?> 768w,
                         <?php echo esc_url( get_theme_file_uri( 'assets/img/main.webp' ) ); ?> 1536w"
                 sizes="100vw"
                 alt="" width="1536" height="1024" fetchpriority="high">
        <?php endif; ?>
        <div class="hero__shade" aria-hidden="true"></div>

        <div class="container hero__inner">
            <h1 class="hero__title"><?php echo promix_nl2br( $hero_title ); ?></h1>

            <p class="hero__lead"><?php echo esc_html( $hero_lead ); ?></p>

            <?php if ( $cta_text ) : ?>
                <a class="hero__cta" href="<?php echo esc_url( $cta_url ); ?>">
                    <?php echo esc_html( $cta_text ); ?>
                    <?php echo promix_icon( 'arrow-right', 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                </a>
            <?php endif; ?>

            <?php if ( $stats ) : ?>
                <dl class="hero__stats">
                    <?php foreach ( $stats as $stat ) : ?>
                        <div class="hero__stat">
                            <dt><?php echo esc_html( $stat['stat_value'] ); ?></dt>
                            <dd><?php echo esc_html( $stat['stat_label'] ); ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </div>
    </section>
