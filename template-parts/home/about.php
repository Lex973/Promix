<?php
/**
 * Секция главной: about.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$title     = promix_field( 'about_title', 'О компании PROMIX' );
$photo     = promix_field( 'about_photo' );
$photo_alt = promix_field( 'about_photo_alt', 'Торговый зал малярного центра PROMIX' );
$lead      = promix_field( 'about_lead', 'PROMIX — профессиональный малярный центр в Казани для мастеров, строительных компаний, дизайнеров и частных клиентов.' );
$text      = promix_field( 'about_text', 'В зале — лакокрасочные материалы, инструмент и оборудование от ведущих производителей. Помогаем подобрать решение под задачу: от ремонта квартиры до большого объекта.' );

$facts = promix_field(
    'about_facts',
    array(
        array( 'fact_icon' => 'circle-check-big', 'fact_value' => '10+ лет',  'fact_label' => 'опыта в стройсфере' ),
        array( 'fact_icon' => 'star',             'fact_value' => '4,9 из 5', 'fact_label' => '44 отзыва в 2ГИС' ),
        array( 'fact_icon' => 'package',          'fact_value' => '1 800+',   'fact_label' => 'товаров в наличии' ),
        array( 'fact_icon' => 'map-pin',          'fact_value' => 'Казань',   'fact_label' => 'Габдуллы Тукая, 91' ),
    )
);

$seminars_hidden = (bool) promix_field( 'about_seminars_off', false );
$seminars_title  = promix_field( 'about_seminars_title', 'Мы делаем пространство, в которое мастеру удобно заезжать снова' );
$seminars_text   = promix_field( 'about_seminars_text', 'Проводим семинары и мастер-классы в учебном классе, обучаем работе с материалами и оборудованием, помогаем в подборе и комплектации объектов.' );

/* Рисунки инструментов — фон полосы, порядок задаёт их расстановку по краям. */
$doodles = array( 'paint-bucket', 'paint-roller', 'brush', 'palette', 'spray-can', 'ruler' );

?>
<section class="section section--divided about" id="about">
        <div class="container">

            <h2 class="section__title about__title"><?php echo esc_html( $title ); ?></h2>

            <div class="about__intro">
                <?php if ( $photo ) : ?>
                    <?php
                    echo wp_get_attachment_image(
                        (int) $photo,
                        'large',
                        false,
                        array(
                            'class'   => 'about__photo',
                            'alt'     => $photo_alt,
                            'loading' => 'lazy',
                        )
                    );
                    ?>
                <?php else : ?>
                    <img class="about__photo" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/office/1.webp' ) ); ?>"
                         alt="<?php echo esc_attr( $photo_alt ); ?>" loading="lazy" width="1280" height="853">
                <?php endif; ?>

                <div class="about__copy">
                    <p class="about__lead"><?php echo esc_html( $lead ); ?></p>

                    <p class="about__text"><?php echo esc_html( $text ); ?></p>

                    <?php if ( $facts ) : ?>
                        <dl class="facts">
                            <?php foreach ( $facts as $fact ) : ?>
                                <div class="fact">
                                    <span class="fact__icon" aria-hidden="true">
                                        <?php echo promix_icon( $fact['fact_icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                                    </span>
                                    <div class="fact__body">
                                        <dt class="fact__value"><?php echo esc_html( $fact['fact_value'] ); ?></dt>
                                        <dd class="fact__label"><?php echo esc_html( $fact['fact_label'] ); ?></dd>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ( ! $seminars_hidden ) : ?>
            <!-- Полоса про семинары: рисунки инструментов — фон, поэтому aria-hidden -->
            <div class="seminars">
                <div class="seminars__decor" aria-hidden="true">
                    <?php foreach ( $doodles as $doodle ) : ?>
                        <?php echo promix_icon( $doodle, 1.1, 'doodle doodle--' . $doodle ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                    <?php endforeach; ?>
                </div>

                <div class="container seminars__inner">
                    <h3 class="seminars__title"><?php echo promix_nl2br( $seminars_title ); ?></h3>
                    <p class="seminars__text"><?php echo esc_html( $seminars_text ); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </section>
