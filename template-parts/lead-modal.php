<?php
/**
 * Модальное окно заявки: одно на все кнопки страницы.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$policy_url = get_privacy_policy_url();

?>
<div class="modal" id="lead-modal" data-lead-modal hidden>
    <div class="modal__overlay" data-lead-close></div>

    <div class="modal__window" role="dialog" aria-modal="true" aria-labelledby="lead-modal-title">
        <button class="modal__close" type="button" data-lead-close aria-label="<?php esc_attr_e( 'Закрыть', 'promix' ); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12"></path>
            </svg>
        </button>

        <p class="kicker" data-lead-kicker><?php esc_html_e( 'Заявка', 'promix' ); ?></p>
        <h2 class="modal__title" id="lead-modal-title" data-lead-title><?php esc_html_e( 'Оставьте телефон', 'promix' ); ?></h2>
        <p class="modal__lead"><?php esc_html_e( 'Перезвоним в рабочее время, подскажем по материалам и посчитаем объём.', 'promix' ); ?></p>

        <form class="lead" data-lead-form novalidate>
            <label class="lead__field">
                <span class="lead__label"><?php esc_html_e( 'Как к вам обращаться', 'promix' ); ?></span>
                <input class="lead__input" type="text" name="name" autocomplete="name" required>
            </label>

            <label class="lead__field">
                <span class="lead__label"><?php esc_html_e( 'Телефон', 'promix' ); ?></span>
                <input class="lead__input" type="tel" name="phone" autocomplete="tel" placeholder="+7 (___) ___-__-__" required>
            </label>

            <label class="lead__field">
                <span class="lead__label"><?php esc_html_e( 'Что нужно подобрать', 'promix' ); ?></span>
                <textarea class="lead__input lead__input--area" name="note" rows="3"></textarea>
            </label>

            <label class="lead__agree">
                <input type="checkbox" name="agree" value="1" checked>
                <span>
                    <?php esc_html_e( 'Согласен на обработку персональных данных', 'promix' ); ?>
                    <?php if ( $policy_url ) : ?>
                        — <a href="<?php echo esc_url( $policy_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'политика', 'promix' ); ?></a>
                    <?php endif; ?>
                </span>
            </label>

            <?php /* Поле-ловушка: спрятано от людей, боты его заполняют. */ ?>
            <div class="lead__trap" aria-hidden="true">
                <label>
                    <?php esc_html_e( 'Компания', 'promix' ); ?>
                    <input type="text" name="company" tabindex="-1" autocomplete="off">
                </label>
            </div>

            <input type="hidden" name="source" data-lead-source value="">
            <input type="hidden" name="opened" data-lead-opened value="">
            <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'promix_lead' ) ); ?>">

            <button class="btn btn--primary lead__submit" type="submit"><?php esc_html_e( 'Отправить заявку', 'promix' ); ?></button>

            <p class="lead__note" data-lead-message role="status" aria-live="polite"></p>
        </form>
    </div>
</div>
