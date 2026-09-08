<?php
/**
 * Секция главной: reviews.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$kicker = promix_field( 'reviews_kicker', 'Отзывы' );
$title  = promix_field( 'reviews_title', "Что говорят\nнаши клиенты" );

$rating   = promix_field( 'reviews_rating', '4,9' );
$count    = promix_field( 'reviews_count', '44 отзыва · 53 оценки в 2ГИС' );
$btn_text = promix_field( 'reviews_btn_text', 'Открыть в 2ГИС' );
$all_text = promix_field( 'reviews_all_text', 'Все 44 отзыва в 2ГИС' );
$url      = promix_field( 'reviews_url', 'https://2gis.ru/kazan/firm/70000001060590384/tab/reviews' );

$reviews = promix_field( 'reviews_items', promix_default_reviews() );

?>
<section class="section reviews" id="reviews" data-reviews>
        <div class="container">

            <div class="section__head reviews__head">
                <div>
                    <?php if ( $kicker ) : ?>
                        <p class="kicker"><?php echo esc_html( $kicker ); ?></p>
                    <?php endif; ?>
                    <h2 class="section__title"><?php echo promix_nl2br( $title ); ?></h2>
                </div>

                <div class="rating">
                    <p class="rating__value"><?php echo esc_html( $rating ); ?></p>

                    <div class="rating__meta">
                        <?php
                        echo promix_stars( 5, sprintf( /* translators: %s — оценка компании. */ __( 'Рейтинг %s из 5', 'promix' ), $rating ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка звёзд.
                        ?>
                        <p class="rating__count"><?php echo esc_html( $count ); ?></p>
                    </div>

                    <?php if ( $btn_text ) : ?>
                        <a class="rating__btn" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $btn_text ); ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="reviews__viewport" data-reviews-viewport tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Отзывы клиентов', 'promix' ); ?>">
                <div class="reviews__track" data-reviews-track>
                    <?php foreach ( $reviews as $review ) : ?>
                        <?php
                        $name   = isset( $review['review_name'] ) ? $review['review_name'] : '';
                        $text   = isset( $review['review_text'] ) ? $review['review_text'] : '';
                        $reply  = isset( $review['review_reply'] ) ? $review['review_reply'] : '';
                        $stars  = isset( $review['review_rating'] ) ? (int) $review['review_rating'] : 5;
                        $date   = isset( $review['review_date'] ) ? $review['review_date'] : '';

                        if ( ! $name || ! $text ) {
                            continue;
                        }
                        ?>
                        <article class="review">
                            <div class="review__head">
                                <span class="review__avatar"><?php echo esc_html( promix_initials( $name ) ); ?></span>
                                <div class="review__who">
                                    <p class="review__name"><?php echo esc_html( $name ); ?></p>
                                    <p class="review__source"><?php esc_html_e( 'Отзыв в 2ГИС', 'promix' ); ?></p>
                                </div>
                                <?php if ( $date ) : ?>
                                    <span class="review__date"><?php echo esc_html( $date ); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php
                            echo promix_stars( $stars, sprintf( /* translators: %d — оценка в отзыве. */ __( 'Оценка: %d из 5', 'promix' ), $stars ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая разметка звёзд.
                            ?>

                            <p class="review__text">«<?php echo esc_html( $text ); ?>»</p>

                            <?php if ( $reply ) : ?>
                                <div class="review__reply">
                                    <span class="review__reply-logo">PM</span>
                                    <div>
                                        <p class="review__reply-title"><?php esc_html_e( 'Ответ PROMIX', 'promix' ); ?></p>
                                        <p class="review__reply-text"><?php echo esc_html( $reply ); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="reviews__foot">
                <div class="reviews__ctrls">
                    <button class="ctrl" type="button" data-reviews-prev aria-label="<?php esc_attr_e( 'Предыдущие отзывы', 'promix' ); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M11 18l-6-6 6-6"></path>
                        </svg>
                    </button>
                    <button class="ctrl" type="button" data-reviews-next aria-label="<?php esc_attr_e( 'Следующие отзывы', 'promix' ); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"></path>
                        </svg>
                    </button>
                </div>

                <?php if ( $all_text ) : ?>
                    <a class="reviews__all" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html( $all_text ); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"></path>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </section>
