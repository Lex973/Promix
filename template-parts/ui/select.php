<?php
/**
 * Выпадающий список в стиле сайта.
 *
 * Внутри — обычный <select>: он отправляется с формой, держит required
 * и без скрипта работает сам (класс js на <html> ставится в <head>,
 * см. promix_image_placeholders). Со скриптом системный список спрятан,
 * вместо него кнопка и меню (assets/js/select.js). Меню строится из
 * <option> при каждом открытии, поэтому правки строк скриптом и подмена
 * разметки аяксом ничего не ломают.
 *
 * Аргументы:
 * - id          — id системного списка, обязателен;
 * - name, form  — как у <select>;
 * - label       — подпись поля; label_hidden — спрятать её визуально;
 * - placeholder — первая строка без значения: пока не выбрано, на кнопке она;
 * - required    — нужно ли выбрать;
 * - class       — доп. класс обёртки;
 * - attrs       — атрибуты <select>: [ 'data-x' => '' ];
 * - options     — [ value, text, label?, hint?, class?, attrs? ]: text —
 *   строка системного списка, label — короче, для кнопки и меню (по
 *   умолчанию text), hint — приглушённая приписка справа в меню.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$id = (string) ( $args['id'] ?? '' );

if ( '' === $id ) {
    return;
}

$attrs = function ( array $list ): string {
    $out = '';

    foreach ( $list as $key => $value ) {
        $out .= ' ' . esc_attr( $key ) . ( '' === $value ? '' : '="' . esc_attr( (string) $value ) . '"' );
    }

    return $out;
};

$select = array_merge(
    array_filter(
        array(
            'name' => $args['name'] ?? '',
            'form' => $args['form'] ?? '',
        )
    ),
    $args['attrs'] ?? array()
);

if ( ! empty( $args['required'] ) ) {
    $select['required'] = '';
}

$placeholder = (string) ( $args['placeholder'] ?? '' );
$options     = $args['options'] ?? array();
?>
<div class="select<?php echo ! empty( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : ''; ?>">
    <label class="select__label<?php echo ! empty( $args['label_hidden'] ) ? ' visually-hidden' : ''; ?>" id="<?php echo esc_attr( $id . '-label' ); ?>" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $args['label'] ?? '' ); ?></label>

    <div class="select__field">
        <select class="select__native" id="<?php echo esc_attr( $id ); ?>"<?php echo $attrs( $select ); // phpcs:ignore WordPress.Security.EscapeOutput -- экранировано в $attrs. ?>>
            <?php if ( '' !== $placeholder ) : ?>
                <option value="" disabled selected><?php echo esc_html( $placeholder ); ?></option>
            <?php endif; ?>
            <?php foreach ( $options as $option ) : ?>
                <option value="<?php echo esc_attr( (string) $option['value'] ); ?>"
                        data-text="<?php echo esc_attr( $option['label'] ?? $option['text'] ); ?>"<?php echo ! empty( $option['hint'] ) ? ' data-hint="' . esc_attr( $option['hint'] ) . '"' : ''; ?><?php echo ! empty( $option['class'] ) ? ' class="' . esc_attr( $option['class'] ) . '"' : ''; ?><?php echo $attrs( $option['attrs'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput -- экранировано в $attrs. ?>><?php echo esc_html( $option['text'] ); ?></option>
            <?php endforeach; ?>
        </select>

        <button class="select__btn<?php echo '' !== $placeholder ? ' is-placeholder' : ''; ?>" type="button"
                aria-haspopup="listbox" aria-expanded="false"
                aria-labelledby="<?php echo esc_attr( $id . '-label ' . $id . '-value' ); ?>">
            <span class="select__value" id="<?php echo esc_attr( $id . '-value' ); ?>"><?php echo esc_html( '' !== $placeholder ? $placeholder : ( $options[0]['label'] ?? $options[0]['text'] ?? '' ) ); ?></span>
            <?php promix_the_icon( 'chevron-down', 2, 'select__arrow' ); ?>
        </button>

        <ul class="select__list" role="listbox" aria-labelledby="<?php echo esc_attr( $id . '-label' ); ?>" data-lenis-prevent hidden></ul>
    </div>
</div>
