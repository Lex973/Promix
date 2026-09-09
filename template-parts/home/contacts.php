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
                            <?php echo promix_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                        </span>
                        <div>
                            <h3 class="contact__title"><?php esc_html_e( 'Адрес', 'promix' ); ?></h3>
                            <p class="contact__value"><?php echo esc_html( $c['address'] ); ?></p>
                        </div>
                    </div>

                    <div class="contact">
                        <span class="contact__icon" aria-hidden="true">
                            <?php echo promix_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
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
                            <?php echo promix_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                        </span>
                        <div>
                            <h3 class="contact__title"><?php esc_html_e( 'Телефон', 'promix' ); ?></h3>
                            <p class="contact__value"><a href="tel:<?php echo esc_attr( $c['phone_raw'] ); ?>"><?php echo esc_html( $c['phone'] ); ?></a></p>
                            <a class="btn-max contact__max" href="<?php echo esc_url( $c['max_url'] ); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                                </svg>
                                <span><?php esc_html_e( 'Написать в MAX', 'promix' ); ?></span>
                            </a>
                        </div>
                    </div>

                    <div class="contacts__maps">
                        <a class="maplink" href="<?php echo esc_url( $c['gis_url'] ); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e( 'Открыть в 2ГИС', 'promix' ); ?>
                            <span class="maplink__icon" aria-hidden="true">
                                <?php echo promix_icon( 'external-link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                            </span>
                        </a>
                        <a class="maplink" href="<?php echo esc_url( $c['yandex_url'] ); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e( 'Открыть в Яндекс Картах', 'promix' ); ?>
                            <span class="maplink__icon" aria-hidden="true">
                                <?php echo promix_icon( 'external-link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
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
                                <?php echo promix_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                            </span>
                            <span class="mapbox__title"><?php esc_html_e( 'Показать карту', 'promix' ); ?></span>
                            <span class="mapbox__hint"><?php echo esc_html( $c['address'] ); ?></span>
                        </button>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </section>
