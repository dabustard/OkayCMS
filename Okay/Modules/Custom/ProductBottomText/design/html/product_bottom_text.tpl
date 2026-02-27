{if isset($product->id) && !empty($product->product_bottom_text)}
    <div class="block block--boxed block--border">
        <div class="product-bottom-text">{$product->product_bottom_text|escape|nl2br}</div>
    </div>
{/if}
