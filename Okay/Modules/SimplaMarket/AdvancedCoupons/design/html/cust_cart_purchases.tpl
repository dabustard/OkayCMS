{* проверка на купон и ошибку сообщение на  счет купона*}
{if $purchase->discountID===1 && empty($coupon_error)}
    <!--div class="discount_approve">
        Купон применен
    </div-->
	<div class="discount_approve purchase_detail__price">
                        <i>{$cart->coupon->code|escape}</i>	
    </div>	
{/if}