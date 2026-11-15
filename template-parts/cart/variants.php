<?php
/**
 * Выбор варианта под ценой: «50 м», «90 м», «120 м».
 *
 * Варианты — радиокнопки формы «В корзину» (template-parts/cart/variant-controls.php,
 * связаны атрибутом form): без скрипта выбор отправляется обычной формой,
 * а required не даёт отправить её пустой. Со скриптом (assets/js/variants.js)
 * выбор меняет цену, артикул и кнопку, а пустой выбор показывает подсказку.
 *
 * Когда вариантов больше порога (promix_variants_use_select), вместо кнопок
 * выпадающий список (template-parts/ui/select.php): он связан с той же
 * формой и работает так же. Первая
 * строка списка — название атрибута («Размер»), она же подпись поля:
 * так заранее ничего не выбрано, а в узкой карточке подсказка не режется.
 *
 * Метка «в корзине» стоит на вариантах, которые уже лежат в корзине:
 * зелёная точка, в системном списке — приписка к строке. После добавления
 * и удаления её переставляет скрипт.
 *
 * Аргументы: product (WC_Product), variations (WC_Product_Variation[]),
 * variant — card или single.
 *
 * @package promix
 */

defined( 'ABSPATH' ) || exit;

$product    = $args['product'] ?? null;
$variations = $args['variations'] ?? array();
$variant    = ( $args['variant'] ?? 'card' ) === 'single' ? 'single' : 'card';

if ( ! $product instanceof WC_Product || ! $variations ) {
    return;
}

$form   = 'promix-buy-' . $product->get_id() . '-' . $variant;
$title  = promix_variations_title( $product );
$select = promix_variants_use_select( count( $variations ), $variant );
$hidden = 'card' === $variant ? ' visually-hidden' : '';
?>
<?php if ( $select ) : ?>
    <?php
    $options = array();

    foreach ( $variations as $variation ) {
        $id      = $variation->get_id();
        $in_cart = (bool) promix_cart_item( $id );
        $price   = promix_product_price( $variation );
        $line    = promix_variation_label( $variation ) . ' — ' . $price;

        $options[] = array(
            'value' => $id,
            'text'  => $line . ( $in_cart ? ' · ' . __( 'в корзине', 'promix' ) : '' ),
            'label' => promix_variation_label( $variation ),
            'hint'  => $price,
            'class' => $in_cart ? 'is-in-cart' : '',
            'attrs' => array(
                'data-variant-option' => '',
                'data-variation'      => $id,
                'data-price'          => (string) (float) $variation->get_price(),
                'data-price-text'     => $price,
                'data-sku'            => $variation->get_sku(),
                'data-attrs'          => (string) wp_json_encode( $variation->get_variation_attributes() ),
                'data-line'           => $line,
                'data-label'          => promix_variation_label( $variation ),
                'data-image'          => (string) wp_json_encode( promix_variation_image_data( $variation, $variant ) ),
                'data-url'            => $variation->get_permalink(),
            ),
        );
    }
    ?>
    <div class="variants variants--<?php echo esc_attr( $variant ); ?> variants--select" data-variants>
        <?php
        get_template_part(
            'template-parts/ui/select',
            null,
            array(
                'id'           => $form . '-select',
                'name'         => 'add-to-cart',
                'form'         => $form,
                'label'        => $title,
                'label_hidden' => true,
                'placeholder'  => $title,
                'required'     => true,
                'attrs'        => array( 'data-variants-select' => '' ),
                'options'      => $options,
            )
        );
        ?>

        <p class="variants__error" data-variants-error role="alert" hidden><?php esc_html_e( 'Выберите вариант', 'promix' ); ?></p>
    </div>
<?php else : ?>
    <fieldset class="variants variants--<?php echo esc_attr( $variant ); ?>" data-variants>
        <legend class="variants__title<?php echo esc_attr( $hidden ); ?>"><?php echo esc_html( $title ); ?></legend>

        <div class="variants__list">
            <?php foreach ( $variations as $variation ) : ?>
                <?php $id = $variation->get_id(); ?>
                <label class="variants__opt<?php echo promix_cart_item( $id ) ? ' is-in-cart' : ''; ?>" data-variant-option data-variation="<?php echo esc_attr( (string) $id ); ?>"
                       data-price="<?php echo esc_attr( (string) (float) $variation->get_price() ); ?>"
                       data-price-text="<?php echo esc_attr( promix_product_price( $variation ) ); ?>"
                       data-sku="<?php echo esc_attr( $variation->get_sku() ); ?>"
                       data-image="<?php echo esc_attr( (string) wp_json_encode( promix_variation_image_data( $variation, $variant ) ) ); ?>"
                       data-url="<?php echo esc_url( $variation->get_permalink() ); ?>"
                       data-attrs="<?php echo esc_attr( (string) wp_json_encode( $variation->get_variation_attributes() ) ); ?>">
                    <input class="variants__input" type="radio" name="add-to-cart" value="<?php echo esc_attr( (string) $id ); ?>"
                           form="<?php echo esc_attr( $form ); ?>" required>
                    <span class="variants__text"><?php echo esc_html( promix_variation_label( $variation ) ); ?></span>
                    <span class="variants__mark"><span class="visually-hidden"><?php esc_html_e( 'в корзине', 'promix' ); ?></span></span>
                </label>
            <?php endforeach; ?>
        </div>

        <p class="variants__error" data-variants-error role="alert" hidden><?php esc_html_e( 'Выберите вариант', 'promix' ); ?></p>
    </fieldset>
<?php endif; ?>
