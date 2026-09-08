<?php
/**
 * Секция главной: why.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$kicker = promix_field( 'why_kicker', 'Почему PROMIX' );
$title  = promix_field( 'why_title', 'Почему выбирают PROMIX' );
$lead   = promix_field( 'why_lead', 'Помогаем выбрать материал, подобрать оттенок, посчитать количество и собрать всё для объекта.' );

$items = promix_field(
    'why_items',
    array(
        array(
            'why_icon'  => 'message-circle',
            'why_title' => 'Консультация специалиста',
            'why_text'  => 'Расскажем, какие материалы подойдут под вашу задачу, и поможем выбрать.',
        ),
        array(
            'why_icon'  => 'layers',
            'why_title' => 'Широкий ассортимент',
            'why_text'  => 'Товары для всех этапов малярных работ — от подготовки до финишного покрытия.',
        ),
        array(
            'why_icon'  => 'house',
            'why_title' => 'Интерьер и экстерьер',
            'why_text'  => 'Подберём краску для работ внутри помещения и на улице.',
        ),
        array(
            'why_icon'  => 'droplet',
            'why_title' => 'Индивидуальная колеровка',
            'why_text'  => 'Заколеруем нужный оттенок и подскажем по цветам и покрытиям.',
        ),
        array(
            'why_icon'  => 'paint-roller',
            'why_title' => 'Технология нанесения',
            'why_text'  => 'Подскажем, как подготовить поверхность, чем наносить и в какой последовательности.',
        ),
        array(
            'why_icon'  => 'package',
            'why_title' => 'Комплектация объектов',
            'why_text'  => 'Соберём материалы на весь объект и посчитаем, сколько нужно.',
        ),
    )
);

$wide_hidden   = (bool) promix_field( 'why_wide_off', false );
$wide_icon     = promix_field( 'why_wide_icon', 'users' );
$wide_title    = promix_field( 'why_wide_title', 'Индивидуальные условия' );
$wide_text     = promix_field( 'why_wide_text', 'Мастерам, бригадам, постоянным и оптовым клиентам — цены и условия обсуждаем отдельно.' );
$wide_cta_text = promix_field( 'why_wide_cta_text', 'Обсудить условия' );
$wide_cta_url  = promix_field( 'why_wide_cta_url', '#contacts' );

?>
<section class="section why" id="why">
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

            <div class="reasons">

                <?php foreach ( $items as $item ) : ?>
                    <article class="reason">
                        <span class="reason__icon" aria-hidden="true">
                            <?php echo promix_icon( $item['why_icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                        </span>
                        <h3 class="reason__title"><?php echo esc_html( $item['why_title'] ); ?></h3>
                        <p class="reason__text"><?php echo esc_html( $item['why_text'] ); ?></p>
                    </article>
                <?php endforeach; ?>

                <?php if ( ! $wide_hidden ) : ?>
                    <article class="reason reason--wide">
                        <span class="reason__icon" aria-hidden="true">
                            <?php echo promix_icon( $wide_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка иконки. ?>
                        </span>
                        <div class="reason__body">
                            <h3 class="reason__title"><?php echo esc_html( $wide_title ); ?></h3>
                            <p class="reason__text"><?php echo esc_html( $wide_text ); ?></p>
                        </div>
                        <?php if ( $wide_cta_text ) : ?>
                            <a class="reason__cta" href="<?php echo esc_url( $wide_cta_url ); ?>">
                                <?php echo esc_html( $wide_cta_text ); ?>
                                <span aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14M13 6l6 6-6 6"></path>
                                    </svg>
                                </span>
                            </a>
                        <?php endif; ?>
                    </article>
                <?php endif; ?>

            </div>
        </div>
    </section>
