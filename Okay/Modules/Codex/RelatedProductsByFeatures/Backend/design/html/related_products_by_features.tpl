{$meta_title = $btr->codex_related_products_by_features__title scope=global}

<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">{$btr->codex_related_products_by_features__title|escape}</div>
        </div>
    </div>
</div>

{if $message_success == 'saved'}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="boxed boxed_success"><div class="heading_box">{$btr->general_settings_saved|escape}</div></div>
        </div>
    </div>
{/if}

<form method="post">
    <input type="hidden" name="session_id" value="{$smarty.session.id}">
    <div class="row">
        <div class="col-lg-6 col-md-12">
            <div class="boxed fn_toggle_wrap">
                <div class="heading_box">{$btr->codex_related_products_by_features__settings|escape}</div>
                <div class="toggle_body_wrap on fn_card">
                    <div class="heading_label">{$btr->codex_related_products_by_features__category_weight|escape}</div>
                    <input class="form-control mb-1" name="category_weight" type="number" min="0" step="1" value="{$category_weight|escape}">

                    <div class="heading_label">{$btr->codex_related_products_by_features__default_limit|escape}</div>
                    <input class="form-control mb-1" name="default_limit" type="number" min="0" step="1" value="{$default_limit|escape}">

                    <div class="heading_label">{$btr->codex_related_products_by_features__discontinued_limit|escape}</div>
                    <input class="form-control mb-1" name="discontinued_limit" type="number" min="0" step="1" value="{$discontinued_limit|escape}">

                    <div class="heading_label">{$btr->codex_related_products_by_features__batch_size|escape}</div>
                    <input class="form-control mb-1" name="batch_size" type="number" min="1" step="1" value="{$batch_size|escape}">

                    <div class="mb-1">
                        <label class="switch switch-default">
                            <input class="switch-input" name="include_discontinued_related" value="1" type="checkbox" {if $include_discontinued_related}checked{/if}>
                            <span class="switch-label"></span><span class="switch-handle"></span>
                        </label>
                        <span>{$btr->codex_related_products_by_features__include_discontinued_related|escape}</span>
                    </div>

                    <div class="mb-1">
                        <label class="switch switch-default">
                            <input class="switch-input" name="only_visible_products" value="1" type="checkbox" {if $only_visible_products}checked{/if}>
                            <span class="switch-label"></span><span class="switch-handle"></span>
                        </label>
                        <span>{$btr->codex_related_products_by_features__only_visible_products|escape}</span>
                    </div>

                    <button type="submit" class="btn btn_small btn_blue">{$btr->general_apply|escape}</button>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-12">
            <div class="boxed fn_toggle_wrap">
                <div class="heading_box">{$btr->codex_related_products_by_features__recalculate|escape}</div>
                <div class="toggle_body_wrap on fn_card">
                    <p>{$btr->codex_related_products_by_features__products_total|escape}: <b class="fn_total">{$products_total|escape}</b></p>
                    <p>{$btr->codex_related_products_by_features__recalculate_hint|escape}</p>
                    <progress id="progressbar" class="progress" value="0" max="100" style="display:none;width:100%;"></progress>
                    <div id="fn_recalculate_status" class="mt-q"></div>
                    <button type="button" id="fn_start_recalculate" class="btn btn_small btn-warning">{$btr->codex_related_products_by_features__start|escape}</button>
                </div>
            </div>
        </div>
    </div>
</form>

{literal}
<script>
$(function() {
    var progress = $('#progressbar');
    var status = $('#fn_recalculate_status');
    var button = $('#fn_start_recalculate');

    button.on('click', function() {
        if (!confirm('{/literal}{$btr->codex_related_products_by_features__confirm|escape}{literal}')) {
            return;
        }
        button.prop('disabled', true);
        status.text('{/literal}{$btr->codex_related_products_by_features__started|escape}{literal}');
        progress.val(0).show();
        $.ajax({
            url: {/literal}"{url controller='Codex.RelatedProductsByFeatures.RelatedProductsByFeaturesAdmin@startRecalculate'}"{literal},
            dataType: 'json',
            success: function(data) {
                processBatch(data.offset || 0, data.inserted || 0, data.total || 0);
            },
            error: showError
        });
    });

    function processBatch(offset, inserted, total) {
        $.ajax({
            url: {/literal}"{url controller='Codex.RelatedProductsByFeatures.RelatedProductsByFeaturesAdmin@processRecalculate'}"{literal},
            data: {offset: offset, inserted: inserted},
            dataType: 'json',
            success: function(data) {
                var percent = data.total > 0 ? Math.round(100 * data.offset / data.total) : 100;
                progress.val(percent);
                status.text('{/literal}{$btr->codex_related_products_by_features__progress|escape}{literal}: ' + data.offset + '/' + data.total + ', {/literal}{$btr->codex_related_products_by_features__inserted|escape}{literal}: ' + data.inserted);
                if (data.end) {
                    progress.val(100).fadeOut(500);
                    button.prop('disabled', false);
                    status.text('{/literal}{$btr->codex_related_products_by_features__finished|escape}{literal}: ' + data.inserted);
                } else {
                    processBatch(data.offset, data.inserted, data.total);
                }
            },
            error: showError
        });
    }

    function showError(xhr, statusText, errorThrown) {
        button.prop('disabled', false);
        progress.fadeOut(500);
        status.text(errorThrown + '\n' + xhr.responseText);
    }
});
</script>
{/literal}
