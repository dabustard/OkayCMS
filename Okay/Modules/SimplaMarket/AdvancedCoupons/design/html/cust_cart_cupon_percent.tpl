{if !empty($discount->custAbsoluteDiscount)}
    {if ($discount->custPercentDiscount === "percentage")}
        {$discount->custAbsoluteDiscount|string_format:"%.2f"} %
        {else}
        {$discount->custAbsoluteDiscount|string_format:"%.2f"} {$currency->sign|escape}
    {/if}
    {else}
        {$discount->percentDiscount|string_format:"%.2f"} %
{/if}
