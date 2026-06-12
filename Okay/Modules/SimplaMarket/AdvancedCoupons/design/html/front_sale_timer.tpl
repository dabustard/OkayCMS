{if $advanced_coupon && !($product_for_coupon->variant->compare_price > 0)}
<div class="d-flex flex-wrap align-items-center details_boxed__price_amount sale_timer">
	<div class="d-flex align-items-center details_boxed__prices">
		<div class="info_discount fn_col">
			<p data-language="simplamarket_sales_left"><b>{$lang->simplamarket_sales_left}</b></p>
			<div class="fn_time_to" data-lang_label="{$language->label}" data-time_to="{$advanced_coupon}"></div>
		</div>
	</div>
</div>
{/if}