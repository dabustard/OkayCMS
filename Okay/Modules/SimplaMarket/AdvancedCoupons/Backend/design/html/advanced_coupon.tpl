{if $product->id}
    {$meta_title = $coupon->code scope=global}
{else}
    {$meta_title = $btr->coupons_new scope=global}
{/if}

{*Название страницы*}
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">
                {if empty($coupon->id)}
                    {$btr->coupons_add|escape}
                {else}
                    {$btr->coupons_coupon|escape} {$coupon->code|escape}
                {/if}
            </div>
        </div>
    </div>
</div>

{*Вывод успешных сообщений*}
{if $message_success}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="alert alert--center alert--icon alert--success">
                <div class="alert__content">
                    <div class="alert__title">
                    {if $message_success=='added'}
                        {$btr->coupon_added|escape}
                    {elseif $message_success=='updated'}
                        {$btr->coupon_updated|escape}
                    {else}
                        {$message_success|escape}
                    {/if}
                    </div>
                </div>
                {if $smarty.get.return}
                    <a class="alert__button" href="{$smarty.get.return}">
                        {include file='svg_icon.tpl' svgId='return'}
                        <span>{$btr->general_back|escape}</span>
                    </a>
                {/if}
            </div>
        </div>
    </div>
{/if}

{*Вывод ошибок*}
{if $message_error}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="alert alert--center alert--icon alert--error">
                <div class="alert__content">
                    <div class="alert__title">
                        {if $message_error == 'code_exists'}
                            {$btr->coupons_exists|escape}
                        {elseif $message_error=='empty_code'}
                            {$btr->coupons_enter_code|escape}
                        {else}
                            {$message_error|escape}
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}

{*Главная форма страницы*}
<form method="post" id="product" enctype="multipart/form-data" class="clearfix fn_fast_button">
    <input type=hidden name="session_id" value="{$smarty.session.id}">
    <input type="hidden" name="lang_id" value="{$lang_id}" />
    <div class="row">
        <div class="col-xs-12">
            <div class="boxed">
                {*Название элемента сайта*}
                <div class="row d_flex">
                    <div class="col-lg-8 col-md-8 col-sm-12">
                        <div class="fn_step-1">
                            <div class="heading_label heading_label--required">
                                <span>{$btr->coupons_coupon|escape}</span>
                            </div>
                            <div class="form-group">
                                <input class="form-control" name="code" type="text" value="{$coupon->code|escape}" placeholder="{$btr->coupons_enter_name|escape}"/>
                                <input id="product_id" name="id" type="hidden" value="{$coupon->id|escape}"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-12">
                        <div class="activity_of_switch">

                            <div class="fn_step-3 activity_of_switch_item">
                                <div class="okay_switch clearfix">
                                    <label class="switch_label">
                                        {$btr->coupons_one_off|escape}
                                    </label>
                                    <label class="switch switch-default">
                                        <input class="switch-input" name="single" value='1' type="checkbox" {if $coupon->single}checked=""{/if}/>
                                        <span class="switch-label"></span>
                                        <span class="switch-handle"></span>
                                    </label>
                                </div>
                            </div>

{*                            <div class="fn_step-4 activity_of_switch_item"> *}{* row block *}
{*                                <div class="okay_switch clearfix">*}
{*                                    <label class="switch_label">*}
{*                                        {$btr->coupons_summ|escape}*}
{*                                    </label>*}
{*                                    <label class="switch switch-default">*}
{*                                        <input class="switch-input" name="summ" value="1" type="checkbox" {if $coupon->summ}checked=""{/if}/>*}
{*                                        <span class="switch-label"></span>*}
{*                                        <span class="switch-handle"></span>*}
{*                                    </label>*}
{*                                </div>*}
{*                            </div>*}
                            <div class="fn_step-4 activity_of_switch_item"> {* row block *}
                                <div class="okay_switch clearfix">
                                    <label class="switch_label">
                                        На свойства
                                    </label>
                                    <label class="switch switch-default">
                                        <input class="switch-input fn_for_feature" name="for_feature" value="1" type="checkbox" {if intval($coupon->feature_id) > 0}checked=""{/if}/>
                                        <span class="switch-label"></span>
                                        <span class="switch-handle"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {*Параметры элемента*}
    <div class="row">
        <div class="col-lg-5 col-md-12 pr-0 ">
            <div class="fn_step-5 boxed fn_toggle_wrap min_height_230px" style="min-height: 300px;">
                <div class="heading_box col-lg-12 col-md-12 pr-0">
                    {$btr->coupon_info|escape}
                    <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                        <a class="btn-minimize" href="javascript:;" ><i class="fa fn_icon_arrow fa-angle-down"></i></a>
                    </div>
                </div>

                <div class="toggle_body_wrap fn_card on ">
                    <div class="col-lg-8 col-md-8 pr-0 ">
                        <div class="heading_label">{$btr->general_discount|escape}</div>
                        <input class="form-control" name="value" type="text" value="{$coupon->value}" />
                    </div>
                    <div class="col-lg-4 col-md-4 pr-0 ">
                        <div class="heading_label">{$btr->general_discount_type_placeholder|escape}</div>
                        <select class="selectpicker form-control" name="type">
                            <option value="percentage" {if $coupon->type == 'percentage'} selected{/if}>%</option>
                            <option value="absolute" {if $coupon->type == 'absolute'} selected{/if}>{$currency->sign}</option>
                        </select>
                    </div>
                    <div class="col-lg-12 col-md-12 pr-0 ">
                        <div class="heading_label">{$btr->coupons_order_price|escape}</div>
                        <input class="form-control" type="text" name="min_order_price"
                               value="{$coupon->min_order_price}">
                    </div>
                    <div class="col-lg-12 col-md-12 pr-0 ">
                        <div class="heading_label">{$btr->coupons_terms|escape}</div>
                        <div class="input-group">
                            <input class="form-control" type=text name="expire"
                                   value="{$coupon->expire|date_format:'%d.%m.%Y'}">
                            <div class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {*Привязка купона*}
        <div class="col-lg-7 col-md-12 fn_not_feature_box" {if $coupon->feature_id > 0}style="display: none"{/if}>
            <div class="boxed min_height_230px">
                <div class="fn_step-7">
                    <div class="heading_box">
                        {$btr->coupon_for_categories|escape}
                        <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                            <a class="btn-minimize" href="javascript:;" ><i class="fa fn_icon_arrow fa-angle-down"></i></a>
                        </div>
                    </div>
                    <div id="product_cats" class="clearfix">
                        {assign var ='first_category' value=reset($coupon_categories)}
                        <select class="selectpicker form-control  mb-1 fn_product_category fn_meta_categories" data-live-search="true">
                            <option value="0" selected="" disabled="" data-category_name="">{$btr->product_select_category}</option>
                            {function name=category_select level=0}
                                {foreach $categories as $category}
                                    {if !$category->in_filter}
                                        <option value="{$category->id}" {if $category->id == $first_category->id}selected{/if} data-category_name="{$category->name|escape}">{section sp $level}- {/section}{$category->name|escape}</option>
                                        {category_select categories=$category->subcategories level=$level+1}
                                    {/if}
                                {/foreach}
                            {/function}
                            {category_select categories=$categories}
                        </select>
                        <div id="sortable_cat" class="fn_product_categories_list clearfix">
                            {foreach $coupon_categories as $coupon_category}
                                <div class="fn_category_item product_category_item {if $coupon_category@first}first_category{/if}">
                                    <span class="product_cat_name">{$coupon_category->name|escape}</span>
                                    <label class="fn_delete_product_cat fa fa-times" for="id_{$coupon_category->id}"></label>
                                    <input id="id_{$coupon_category->id}" type="checkbox" value="{$coupon_category->id}" data-cat_name="{$coupon_category->name|escape}" checked="" name="categories[]">
                                </div>
                            {/foreach}
                        </div>
                        <div class="fn_category_item fn_new_category_item product_category_item">
                            <span class="product_cat_name"></span>
                            <label class="fn_delete_product_cat fa fa-times" for=""></label>
                            <input id="" type="checkbox" value="" name="categories[]" data-cat_name="">
                        </div>
                    </div>
                </div>
                <div class="fn_step-8">
                    {*Связанные товары*}
                    <div class="heading_box">
                        {$btr->coupon_for_products|escape}
                        <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                            <a class="btn-minimize" href="javascript:;" ><i class="fa fn_icon_arrow fa-angle-down"></i></a>
                        </div>
                    </div>
                    <div class="toggle_body_wrap on fn_card fn_sort_list">
                        <div class="okay_list ok_related_list">
                            <div class="okay_list_body related_products sortable">
                                {foreach $related_products as $related_product}
                                    <div class="fn_row okay okay_list_body_item fn_sort_item">
                                        <div class="okay_list_row">
                                            <div class="okay_list_boding okay_list_drag move_zone">
                                                {include file='svg_icon.tpl' svgId='drag_vertical'}
                                            </div>
                                            <div class="okay_list_boding okay_list_related_photo">
                                                <input type="hidden" name=related_products[] value='{$related_product->id}'>
                                                <a href="{url controller=ProductAdmin id=$related_product->id}">
                                                    {if $related_product->images[0]}
                                                        <img class="product_icon" src='{$related_product->images[0]->filename|resize:40:40}'>
                                                    {else}
                                                        <img class="product_icon" src="design/images/no_image.png" width="40">
                                                    {/if}
                                                </a>
                                            </div>
                                            <div class="okay_list_boding okay_list_related_name">
                                                <a class="link" href="{url controller=ProductAdmin id=$related_product->id}">{$related_product->name|escape}</a>
                                            </div>
                                            <div class="okay_list_boding okay_list_close">
                                                <button data-hint="{$btr->general_delete_product|escape}" type="button" class="btn_close fn_remove_item hint-bottom-right-t-info-s-small-mobile  hint-anim">
                                                    {include file='svg_icon.tpl' svgId='trash'}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                                <div id="new_related_product" class="fn_row okay okay_list_body_item fn_sort_item" style='display:none;'>
                                    <div class="okay_list_row">
                                        <div class="okay_list_boding okay_list_drag move_zone">
                                            {include file='svg_icon.tpl' svgId='drag_vertical'}
                                        </div>
                                        <div class="okay_list_boding okay_list_related_photo">
                                            <input type="hidden" name="related_products[]" value="">
                                            <img class=product_icon src="">
                                        </div>
                                        <div class="okay_list_boding okay_list_related_name">
                                            <a class="link related_product_name" href=""></a>
                                        </div>
                                        <div class="okay_list_boding okay_list_close">
                                            <button data-hint="{$btr->general_delete_product|escape}" type="button" class="btn_close fn_remove_item hint-bottom-right-t-info-s-small-mobile  hint-anim">
                                                {include file='svg_icon.tpl' svgId='trash'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="autocomplete_arrow">
                            <input type=text name=related id="related_products" class="form-control" placeholder='{$btr->general_add_product|escape}'>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-md-12 fn_feature_box" {if !($coupon->feature_id > 0)}style="display: none"{/if}>
            <div class="boxed min_height_230px">
                <div class="fn_step-7">
                    <div class="heading_box">
                        {$btr->coupons_feature|escape}
                        <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                            <a class="btn-minimize" href="javascript:;" ><i class="fa fn_icon_arrow fa-angle-down"></i></a>
                        </div>
                    </div>
                    <div id="product_features" class="clearfix">
                        <select class="selectpicker form-control mb-1 fn_feature" name="feature_id">
                            <option selected value="0">Выберите свойство</option>
                            {foreach $features as $feature}
                                <option value="{$feature->id}" {if $feature->id == $coupon->feature_id}selected{/if}>{$feature->name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="fn_step-8">
                    <div class="heading_box">
                        {$btr->coupons_feature_value|escape}
                        <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                            <a class="btn-minimize" href="javascript:;" ><i class="fa fn_icon_arrow fa-angle-down"></i></a>
                        </div>
                    </div>
                    <div class="toggle_body_wrap on fn_card fn_sort_list">
                        <select class="selectpicker form-control fn_feature_value" name="feature_value_id" disabled>
                            <option selected value="0">{$btr->advanced_coupons_feature_value_all|escape}</option>
                        </select>
                        <input type="hidden" class="fn_fv_id" value="{$coupon->feature_value_id}">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12 col-md-12 mt-1">
            <button type="submit" class="fn_step-15 btn btn_small btn_blue float-md-right">
                {include file='svg_icon.tpl' svgId='checked'}
                <span>{$btr->general_apply|escape}</span>
            </button>
        </div>
    </div>
</form>
<script>
    $('input[name="expire"]').datepicker();
</script>

{* Learning script *}
{include file='learning_hints.tpl' hintId='hint_product'}

{* Подключаем Tiny MCE *}
{include file='tinymce_init.tpl'}
{* On document load *}
{literal}
    <script src="design/js/autocomplete/jquery.autocomplete-min.js"></script>
    <script src="design/js/chosen/chosen.jquery.js"></script>
    <link rel="stylesheet" type="text/css" href="design/js/chosen/chosen.min.css" media="screen" />
<script>
    $(window).on("load", function() {
        // Удаление товара
        $(document).on( "click", ".fn_remove_item", function() {
            $(this).closest(".fn_row").fadeOut(200, function() { $(this).remove(); });
            return false;
        });
        $(".chosen").chosen('chosen-select');

        var clone_cat = $(".fn_new_category_item").clone();
        $(".fn_new_category_item").remove();
        clone_cat.removeClass("fn_new_category_item");
        $(document).on("change", ".fn_product_category select", function () {
            var clone = clone_cat.clone();
            clone.find("label").attr("for","id_"+$(this).find("option:selected").val());
            clone.find("span").html($(this).find("option:selected").data("category_name"));
            clone.find("input").attr("id","id_"+$(this).find("option:selected").val());
            clone.find("input").val($(this).find("option:selected").val());
            clone.find("input").attr("checked",true);
            clone.find("input").attr("data-cat_name",$(this).find("option:selected").data("category_name"));
            $(".fn_product_categories_list").append(clone);
            if ($(".fn_category_item").size() == 1) {
                change_product_category();
            }
        });
        $(document).on("click", ".fn_delete_product_cat", function () {
            var item = $(this).closest(".fn_category_item"),
                is_first = item.hasClass("first_category");
            item.remove();
            if (is_first && $(".fn_category_item").size() > 0) {
                change_product_category();
            }
        });

        var el = document.getElementById('sortable_cat');
        var sortable = Sortable.create(el, {
            handle: ".product_cat_name",  // Drag handle selector within list items
            sort: true,  // sorting inside list
            animation: 150,  // ms, animation speed moving items when sorting, `0` — without animation

            ghostClass: "sortable-ghost",  // Class name for the drop placeholder
            chosenClass: "sortable-chosen",  // Class name for the chosen item
            dragClass: "sortable-drag",  // Class name for the dragging item
            scrollSensitivity: 30, // px, how near the mouse must be to an edge to start scrolling.
            scrollSpeed: 10, // px
            // Changed sorting within list
            onUpdate: function (evt) {
                change_product_category();
            }
        });

        function change_product_category() {
            var wrapper = $(".fn_product_categories_list");
            wrapper.find("div.first_category").removeClass("first_category");
            wrapper.find("div.fn_category_item:first ").addClass("first_category");
        }

        var features_values_json = {/literal}{if !empty($features_values_json)}{$features_values_json}{else}null{/if}{literal};

        $(document).on("change", "select.fn_feature", function () {
            let feature_values_select = $(this).parents('.fn_feature_box').find('select.fn_feature_value');
            feature_values_select.find('option').remove();
            feature_values_select.append($('<option>', {value:0, text:'{/literal}{$btr->advanced_coupons_feature_value_all}{literal}'}));
            if (typeof features_values_json[$(this).val()] !== 'undefined' ) {
                $.each(features_values_json[$(this).val()], function (index,value) {
                    feature_values_select.append($('<option>', {value:value.id, text:value.value}));
                });
            } else {
                feature_values_select.prop("disabled", true);
            }
            feature_values_select.removeAttr('disabled');
            feature_values_select.selectpicker('refresh');
        });

        if($('.fn_fv_id').val() > 0){
            $("select.fn_feature").trigger('change');
            var feature_value_id = $('.fn_fv_id').val();
            $('.fn_feature_box').find('select.fn_feature_value option[value='+feature_value_id+']').attr("selected", "selected");
            $('select.fn_feature_value').selectpicker('refresh');
        }

        $(document).on('change', '.fn_for_feature', function () {
            if($(this).prop('checked')){
                $('.fn_feature_box').show().find('select, input').prop('disabled', false);
                $('.fn_not_feature_box').hide().find('select, input').prop('disabled', true);
            } else {
                $('.fn_not_feature_box').show().find('select, input').prop('disabled', false);
                $('.fn_feature_box').hide().find('select, input').prop('disabled', true);
            }
        });

        // Добавление связанного товара
        var new_related_product = $('#new_related_product').clone(true);
        $('#new_related_product').remove();
        new_related_product.removeAttr('id');
        $("input#related_products").devbridgeAutocomplete({
            serviceUrl:'ajax/search_products.php',
            minChars:0,
            type:'POST',
            orientation:'auto',
            noCache: false,
            onSelect:
                function(suggestion){

                    $("input#related_products").val('').focus().blur();
                    new_item = new_related_product.clone().appendTo('.related_products');
                    new_item.find('a.related_product_name').html(suggestion.data.name);
                    new_item.find('a.related_product_name').attr('href', 'index.php?controller=ProductAdmin&id='+suggestion.data.id);
                    new_item.find('input[name*="related_products"]').val(suggestion.data.id);
                    if(suggestion.data.image)
                        new_item.find('img.product_icon').attr("src", suggestion.data.image);
                    else
                        new_item.find('img.product_icon').remove();
                    new_item.show();
                },
            formatResult:
                function(suggestions, currentValue){
                    var reEscape = new RegExp('(\\' + ['/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\'].join('|\\') + ')', 'g');
                    var pattern = '(' + currentValue.replace(reEscape, '\\$1') + ')';
                    return "<div>" + (suggestions.data.image?"<img align=absmiddle src='"+suggestions.data.image+"'> ":'') + "</div>" +  "<span>" + suggestions.value.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>') + "</span>";
                }

        });
    });

</script>
{/literal}