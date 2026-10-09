<div class="col-md-3 col-lg-3 col-sm-12">
    <select name="referer_channel" class="selectpicker form-control" onchange="location = this.value;">
        <option value="{url referer_channel=null page=null}" {if !$referer_channel}selected{/if}>{$btr->orders_referer_channel_all|escape}</option>
        <option value="{url referer_channel='search' page=null}" {if $referer_channel == 'search'}selected{/if}>{$btr->orders_referer_channel_search|escape}</option>
        <option value="{url referer_channel='social' page=null}" {if $referer_channel == 'social'}selected{/if}>{$btr->orders_referer_channel_social|escape}</option>
        <option value="{url referer_channel='email' page=null}" {if $referer_channel == 'email'}selected{/if}>{$btr->orders_referer_channel_email|escape}</option>
        <option value="{url referer_channel='referral' page=null}" {if $referer_channel == 'referral'}selected{/if}>{$btr->orders_referer_channel_referral|escape}</option>
        <option value="{url referer_channel='unknown' page=null}" {if $referer_channel == 'unknown'}selected{/if}>{$btr->orders_referer_channel_unknown|escape}</option>
    </select>
</div>
