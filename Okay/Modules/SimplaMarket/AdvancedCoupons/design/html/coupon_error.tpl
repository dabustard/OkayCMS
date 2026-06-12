{if empty($cart->coupon->min_order_price) && $coupon_error != 'empty'}
    {$lang->cart_coupon_error}
{elseif $cart->coupon->min_order_price<basic_total_price AND ($cart->coupon->expire|date_format > $smarty.now|date_format OR empty($cart->coupon->expire))}
    Купон  {$lang->cart_coupon_min} {$cart->coupon->min_order_price|convert} {$currency->sign|escape}
{else}
    {$lang->cart_coupon_error}
{/if}
{if $coupon_error == 'empty'}
    {$lang->cart_empty_coupon_error}
{/if}
