<?php
/**
 * Пункт фильтра по разделам: галочка, название, число товаров.
 *
 * @package promix
 *
 * @var array $args {
 *     @type array  $term  Пункт из promix_catalog_tree().
 *     @type string $class Модификатор: check--parent или check--child.
 *     @type bool   $count Показывать число товаров. У подразделов его нет:
 *                         два десятка чисел в столбик читались как шум.
 * }
 */

defined( 'ABSPATH' ) || exit;

$term = $args['term'];
?>
<label class="check <?php echo esc_attr( $args['class'] ?? '' ); ?>">
    <input type="checkbox" name="cat[]"
           value="<?php echo esc_attr( $term['slug'] ); ?>"
           data-filter-cat
           <?php echo $term['url'] ? 'data-url="' . esc_url( $term['url'] ) . '"' : ''; ?>
           <?php checked( $term['checked'] ); ?>>
    <span class="check__text"><?php echo esc_html( $term['name'] ); ?></span>
    <?php if ( $args['count'] ?? true ) : ?>
        <span class="check__count"><?php echo esc_html( (string) $term['count'] ); ?></span>
    <?php endif; ?>
</label>
