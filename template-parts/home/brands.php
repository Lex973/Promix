<?php
/**
 * Секция главной: brands.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$kicker = promix_field( 'brands_kicker', 'Бренды' );
$title  = promix_field( 'brands_title', "Работаем с проверенными\nпроизводителями" );
$lead   = promix_field( 'brands_lead', 'Держим марки, которые мастера берут в работу постоянно. Привозим их стабильно и подсказываем, как с ними работать.' );

$note     = promix_field( 'brands_note', 'Не нашли нужную марку?' );
$cta_text = promix_field( 'brands_cta_text', 'Спросить у технолога' );
$cta_url  = promix_field( 'brands_cta_url', '#contacts' );

$brands = promix_field( 'brands_items', promix_default_brands() );

/* Первая половина списка едет в верхней ленте, вторая — в нижней навстречу. */
$middle = (int) ceil( count( $brands ) / 2 );
$rows   = array(
    array_slice( $brands, 0, $middle ),
    array_slice( $brands, $middle ),
);

?>
<section class="section brands" id="brands" data-brands>
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
        </div>

        <!-- Две бегущие строки едут навстречу друг другу; дубли для бесшовности добавляет brands.js -->
        <div class="brands__rows">
            <?php foreach ( $rows as $index => $row ) : ?>
                <?php if ( ! $row ) : ?>
                    <?php continue; ?>
                <?php endif; ?>
                <div class="brands__row<?php echo 1 === $index ? ' brands__row--reverse' : ''; ?>">
                    <div class="brands__track" data-brands-track>
                        <?php foreach ( $row as $brand ) : ?>
                            <?php
                            $name  = isset( $brand['brand_name'] ) ? $brand['brand_name'] : '';
                            $url   = isset( $brand['brand_url'] ) ? $brand['brand_url'] : '';
                            $color = isset( $brand['brand_color'] ) ? $brand['brand_color'] : '';
                            $logo  = isset( $brand['brand_logo'] ) ? $brand['brand_logo'] : '';

                            if ( ! $name ) {
                                continue;
                            }

                            $tag = $url ? 'a' : 'span';

                            /* Содержимое плитки печатается без отступов: лишние пробелы
                               попадали бы внутрь названия марки. */
                            $inner = $logo
                                ? wp_get_attachment_image(
                                    (int) $logo,
                                    'medium',
                                    false,
                                    array(
                                        'class'   => 'brand__logo',
                                        'alt'     => $name,
                                        'loading' => 'lazy',
                                    )
                                )
                                : esc_html( $name );

                            printf(
                                '<%1$s class="brand"%2$s%3$s>%4$s</%1$s>',
                                esc_html( $tag ),
                                $url ? ' href="' . esc_url( $url ) . '"' : '',
                                $color ? ' style="--brand-color:' . esc_attr( $color ) . '"' : '',
                                $inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше.
                            );
                            ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="container">
            <div class="brands__cta">
                <?php if ( $note ) : ?>
                    <p class="brands__note"><?php echo esc_html( $note ); ?></p>
                <?php endif; ?>
                <?php if ( $cta_text ) : ?>
                    <a class="btn btn--outline brands__btn" href="<?php echo esc_url( $cta_url ); ?>">
                        <?php echo esc_html( $cta_text ); ?>
                        <span class="brands__btn-arrow" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M13 6l6 6-6 6"></path>
                            </svg>
                        </span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
