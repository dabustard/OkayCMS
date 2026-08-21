<div class="col-lg-12 col-md-12">
    <div class="boxed fn_toggle_wrap min_height_210px">
        <div class="heading_box">
            {$btr->codex_related_products_by_features__feature_weight_title|escape}
            <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                <a class="btn-minimize" href="javascript:;"><i class="icon-arrow-down"></i></a>
            </div>
        </div>
        <div class="toggle_body_wrap on fn_card">
            <div class="heading_label">{$btr->codex_related_products_by_features__feature_weight|escape}</div>
            <input name="weight" class="form-control" type="number" min="0" step="1" value="{$feature->weight|default:0|escape}" />
            <div class="mt-q text-muted">{$btr->codex_related_products_by_features__feature_weight_hint|escape}</div>
        </div>
    </div>
</div>
