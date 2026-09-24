<?php
/**
 * Секция главной: contacts.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$kicker = promix_field( 'contacts_kicker', 'Контакты' );
$title  = promix_field( 'contacts_title', 'Где нас найти' );
$lead   = promix_field( 'contacts_lead', 'Малярный центр в центре Казани. Приезжайте за материалами и консультацией или позвоните — подскажем по телефону.' );

$c = promix_contacts();

$scheme_hidden = (bool) promix_field( 'contacts_scheme_off', false );
$scheme_title  = promix_field( 'contacts_scheme_title', 'Схема проезда' );
$scheme_image  = promix_field( 'contacts_scheme_image' );
$scheme_alt    = 'Схема проезда к PROMIX: улица Габдуллы Тукая, 91, 1 этаж';

?>
<section class="section section--alt contacts" id="contacts">
        <div class="container">

            <div class="section__head">
                <div>
                    <?php if ( $kicker ) : ?>
                        <p class="kicker"><?php echo esc_html( $kicker ); ?></p>
                    <?php endif; ?>
                    <h2 class="section__title"><?php echo promix_nl2br( $title ); ?></h2>
                </div>
                <p class="section__lead"><?php echo esc_html( $lead ); ?></p>
            </div>

            <div class="contacts__grid">

                <div class="contacts__info">

                    <div class="contact">
                        <span class="contact__icon" aria-hidden="true">
                            <?php promix_the_icon( 'map-pin' ); ?>
                        </span>
                        <div>
                            <h3 class="contact__title"><?php esc_html_e( 'Адрес', 'promix' ); ?></h3>
                            <p class="contact__value"><?php echo esc_html( $c['address'] ); ?></p>
                        </div>
                    </div>

                    <div class="contact">
                        <span class="contact__icon" aria-hidden="true">
                            <?php promix_the_icon( 'clock' ); ?>
                        </span>
                        <div>
                            <h3 class="contact__title"><?php esc_html_e( 'Режим работы', 'promix' ); ?></h3>
                            <p class="contact__value"><?php echo esc_html( $c['hours'] ); ?></p>
                            <?php if ( $c['hours_extra'] ) : ?>
                                <p class="contact__extra"><?php echo esc_html( $c['hours_extra'] ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="contact">
                        <span class="contact__icon" aria-hidden="true">
                            <?php promix_the_icon( 'phone' ); ?>
                        </span>
                        <div>
                            <h3 class="contact__title"><?php esc_html_e( 'Телефон', 'promix' ); ?></h3>
                            <p class="contact__value"><a href="tel:<?php echo esc_attr( promix_tel_href() ); ?>"><?php echo esc_html( $c['phone'] ); ?></a></p>
                            <p class="contact__extra"><?php echo esc_html( sprintf( /* translators: %s — часы работы. */ __( 'Основной, %s', 'promix' ), $c['hours'] ) ); ?></p>
                            <?php if ( $c['phone_extra'] ) : ?>
                                <p class="contact__value contact__value--second"><a href="tel:<?php echo esc_attr( promix_tel_href( $c['phone_extra'] ) ); ?>"><?php echo esc_html( $c['phone_extra'] ); ?></a></p>
                                <p class="contact__extra"><?php esc_html_e( 'Дополнительный', 'promix' ); ?></p>
                            <?php endif; ?>
                            <?php
                            get_template_part(
                                'template-parts/btn-max',
                                null,
                                array( 'class' => 'contact__max' )
                            );
                            ?>
                        </div>
                    </div>

                    <div class="contacts__maps">
                        <a class="maplink" href="<?php echo esc_url( $c['gis_url'] ); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e( 'Открыть в 2ГИС', 'promix' ); ?>
                            <span class="maplink__icon" aria-hidden="true">
                                <?php promix_the_icon( 'external-link' ); ?>
                            </span>
                        </a>
                        <a class="maplink" href="<?php echo esc_url( $c['yandex_url'] ); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e( 'Открыть в Яндекс Картах', 'promix' ); ?>
                            <span class="maplink__icon" aria-hidden="true">
                                <?php promix_the_icon( 'external-link' ); ?>
                            </span>
                        </a>
                    </div>

                </div>

                <?php if ( $c['map_embed'] ) : ?>
                    <div class="contacts__map">
                        <?php /* Карту рисует Яндекс. Пока её не открыли, сайт наружу не ходит:
                                 ни запросов на чужой домен, ни его кук у посетителя. */ ?>
                        <button class="mapbox" type="button"
                                data-map="<?php echo esc_url( $c['map_embed'] ); ?>"
                                data-map-title="<?php echo esc_attr( sprintf( /* translators: %s — адрес магазина. */ __( 'Карта: %s', 'promix' ), $c['address'] ) ); ?>">
                            <span class="mapbox__icon" aria-hidden="true">
                                <?php promix_the_icon( 'map-pin' ); ?>
                            </span>
                            <span class="mapbox__title"><?php esc_html_e( 'Показать карту', 'promix' ); ?></span>
                            <span class="mapbox__hint"><?php echo esc_html( $c['address'] ); ?></span>
                        </button>
                    </div>
                <?php endif; ?>

            </div>

            <?php if ( ! $scheme_hidden ) : ?>
                <div class="scheme">
                    <h3 class="scheme__title"><?php echo esc_html( $scheme_title ); ?></h3>
                    <?php if ( $scheme_image ) : ?>
                        <?php
                        echo wp_get_attachment_image(
                            (int) $scheme_image,
                            'full',
                            false,
                            array(
                                'class'   => 'scheme__img',
                                'alt'     => $scheme_alt,
                                'loading' => 'lazy',
                            )
                        );
                        ?>
                    <?php else : ?>
                        <img class="scheme__img" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/scheme.webp' ) ); ?>"
                             alt="<?php echo esc_attr( $scheme_alt ); ?>" loading="lazy" width="1254" height="1254">
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </section>
