-e <!--
TL;DR: Полноценный CRUD с фронтом. Своя таблица, роуты, контроллеры фронта и бекенда, мультиязычность. Официальный пример от OkayCMS. Читать при создании публичного CRUD.
Размер: ~818 строк
-->

# Пример модуля: FAQ (OkayCMS/FAQ)

## Информация о модуле

**Вендор/Модуль:** `OkayCMS/FAQ`  
**Тип:** CRUD-модуль с фронтендом, роутами и мультиязычной сущностью  
**Демонстрирует:**
- Собственная мультиязычная Entity (`$langFields`, `$langTable`, `$langObject`)
- Два контроллера в админке (список + редактирование одной записи)
- Фронтенд-контроллер с роутами
- Регистрация CSS/JS через `design/css.php` и `design/js.php`
- `setBackendMainController` + `registerBackendController` + `extendBackendMenu`
- Полные шаблоны бекенда и фронта

---

## Структура файлов

```
Okay/Modules/OkayCMS/FAQ/
├── Init/
│   ├── Init.php
│   ├── module.json
│   └── routes.php
├── Entities/
│   └── FAQEntity.php
├── Backend/
│   ├── Controllers/
│   │   ├── FAQAdmin.php         — редактирование одной записи
│   │   └── FAQsAdmin.php        — список записей
│   ├── design/
│   │   ├── html/
│   │   │   ├── faq.tpl          — форма редактирования
│   │   │   └── faqs.tpl         — список записей
│   │   └── css/
│   │       └── faq.css
│   └── lang/
│       ├── ru.php
│       ├── en.php
│       ├── ge.php
│       └── ua.php
├── Controllers/
│   └── FAQController.php        — фронтенд страница
└── design/
    ├── css.php
    ├── js.php
    ├── html/
    │   └── faq.tpl
    ├── css/
    │   └── faq.css
    └── js/
        └── faq.js
```

---

## Исходный код

### File: Okay/Modules/OkayCMS/FAQ/design/css.php

```php
<?php

use Okay\Core\TemplateConfig\Css;

return [
    (new Css('faq.css')),
];


```

### File: Okay/Modules/OkayCMS/FAQ/design/js.php

```php
<?php

use Okay\Core\TemplateConfig\Js;

return [
    (new Js('faq.js')),
];

```

### File: Okay/Modules/OkayCMS/FAQ/design/html/faq.tpl

```smarty
{$canonical="{url_generator route="OkayCMS_FAQ_main" url=$product->url absolute=1}" scope=global}

<div class="block">
    {* The page heading *}
    <div class="block__header block__header--boxed block__header--border">
        <h1 class="block__heading">
            <span>{if $page->name_h1|escape}{$page->name_h1|escape}{else}{$page->name|escape}{/if}</span>
        </h1>
    </div>
    <div class="block__body block--boxed block--border">
        {if $faqs|count}
        <div class="faq">
            <ul class="fn_faq faq__list">
                {foreach $faqs as $faq}
                <li class="faq__item faq__item--boxed {if $faq@first}visible{/if}">
                    <div class="faq__question {if $faq@first}active{/if}">
                        <svg class="faq__arrow" width="20px" height="20px" viewBox="0 0 512 512"><path fill="currentColor" d="m256 512c-68.378906 0-132.667969-26.628906-181.019531-74.980469-48.351563-48.351562-74.980469-112.640625-74.980469-181.019531s26.628906-132.667969 74.980469-181.019531c48.351562-48.351563 112.640625-74.980469 181.019531-74.980469s132.667969 26.628906 181.019531 74.980469c48.351563 48.351562 74.980469 112.640625 74.980469 181.019531s-26.628906 132.667969-74.980469 181.019531c-48.351562 48.351563-112.640625 74.980469-181.019531 74.980469zm0-472c-119.101562 0-216 96.898438-216 216s96.898438 216 216 216 216-96.898438 216-216-96.898438-216-216-216zm138.285156 182-28.285156-28.285156-110 110-110-110-28.285156 28.285156 138.285156 138.285156zm0 0"/></svg>
                        <span>{$faq->question|escape}</span>
                    </div>
                    <div class="faq__content" {if $faq@first} style="display: block;"{/if}>
                        <div class="faq__answer">
                            <div>{$faq->answer}</div>
                        </div>
                    </div>
                </li>
                {/foreach}
            </ul>
        </div>
        {/if}

        {* The page body *}
        {if $page->description}
            <div class="page-description__text boxed__description">{$page->description}</div>
        {/if}
    </div>
</div>

```

### File: Okay/Modules/OkayCMS/FAQ/design/js/faq.js

```javascript
$(document).on('click', '.faq__question', function() {
    var outerBox = $(this).parents('.fn_faq');
    var target = $(this).parents('.faq__item');

    if($(this).hasClass('active')!==true){
        $(outerBox).find('.faq__item .faq__question').removeClass('active');
    }

    if ($(this).next('.faq__content').is(':visible')){
        return false;
    }else{
        $(this).addClass('active');
        $(outerBox).children('.faq__item').removeClass('visible');
        $(outerBox).find('.faq__item').children('.faq__content').slideUp(300);
        target.addClass('visible');
        $(this).next('.faq__content').slideDown(300);
    }
});
```

### File: Okay/Modules/OkayCMS/FAQ/design/css/faq.css

```css
.faq__list{
    list-style-type: none;
    margin: 0;
    position: relative;
    padding: 0;
}
.faq__item{
    margin-bottom: 15px;
    position: relative;
}
.faq__item:last-child{
    margin-bottom: 0;
}
.faq__item--boxed{
    padding: 5px;
    background-color: #fff;
    border-radius: 4px;
    box-shadow: inset 0 0 6px #0000004d;
}
.faq__question{
    position: relative;
    padding: 15px 15px 15px 50px;
    transition: all 500ms ease;
    cursor: pointer;
    font-weight: 600;
    font-size: 16px;
    color: var(--okay-body-text);
    line-height: 1.2;
    text-transform: uppercase;
}
.faq__arrow{
    position: absolute;
    width: 20px;
    height: 20px;
    top: calc(50% - 10px);
    left: 15px;
    transition: all .3s ease;
}
.faq__question.active {
    background: #fff;
    color: var(--okay-basic-company);
}
.faq__question.active .faq__arrow{
    transform: rotate(-90deg);
}
.faq__content{
    position: relative;
    display: none;
    padding: 0 0 0 50px;
    border-bottom: 1px solid #f2f2f2;
}
.faq__answer{
    position: relative;
    font-size: 14px;
    margin-bottom: 20px;
    line-height: 1.6;
    font-weight: normal;
    font-family: inherit;
}
@media only screen and (max-width : 767px) {
    .faq__question{
        font-weight: 500;
        font-size: 14px;
    }
}

```

### File: Okay/Modules/OkayCMS/FAQ/Backend/design/html/faq.tpl

```smarty
{if $faq->id}
    {$meta_title = $faq->question scope=global}
{else}
    {$meta_title = $btr->page_new scope=global}
{/if}

{*Название страницы*}
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">
                {if !$faq->id}
                    {$btr->faq_add|escape}
                {else}
                    {$faq->question|escape}
                {/if}
            </div>
            {if $faq->id}
                <div class="box_btn_heading">
                    <a class="btn btn_small btn-info add" target="_blank" href="{url_generator route='OkayCMS_FAQ_main' absolute=1}">
                        {include file='svg_icon.tpl' svgId='icon_desktop'}
                        <span>{$btr->general_open|escape}</span>
                    </a>
                </div>
            {/if}
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
                        {if $message_success == 'added'}
                        {$btr->faq_added|escape}
                        {elseif $message_success == 'updated'}
                        {$btr->faq_updated|escape}
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

{*Главная форма страницы*}
<form method="post" enctype="multipart/form-data">
    <input type=hidden name="session_id" value="{$smarty.session.id}">
    <input type="hidden" name="lang_id" value="{$lang_id}" />

    <div class="row">
        <div class="col-xs-12 ">
            <div class="boxed match_matchHeight_true">
                {*Название элемента сайта*}
                <div class="row d_flex">
                    <div class="col-lg-10 col-md-9 col-sm-12">
                        <div class="heading_label">
                            {$btr->faq_question|escape}
                        </div>
                        <div class="form-group">
                            <input class="form-control" name="question" type="text" value="{$faq->question|escape}"/>
                            <input name="id" type="hidden" value="{$faq->id|escape}"/>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-12">
                        <div class="activity_of_switch">
                            <div class="activity_of_switch_item"> {* row block *}
                                <div class="okay_switch clearfix">
                                    <label class="switch_label">{$btr->general_enable|escape}</label>
                                    <label class="switch switch-default">
                                        <input class="switch-input" name="visible" value='1' type="checkbox" id="visible_checkbox" {if $faq->visible}checked=""{/if}/>
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
    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="boxed match fn_toggle_wrap tabs">
                <div class="heading_tabs">
                    <div class="tab_navigation">
                        <a href="#tab1" class="heading_box tab_navigation_link">{$btr->faq_answer|escape}</a>
                    </div>
                    <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                        <a class="btn-minimize" href="javascript:;" ><i class="icon-arrow-down"></i></a>
                    </div>
                </div>
                <div class="toggle_body_wrap on fn_card">
                    <div class="tab_container">
                        <div id="tab1" class="tab">
                            <textarea name="answer" id="fn_editor" class="editor_small">{$faq->answer|escape}</textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 mt-1">
                        <button type="submit" class="btn btn_small btn_blue float-md-right">
                            {include file='svg_icon.tpl' svgId='checked'}
                            <span>{$btr->general_apply|escape}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
{* Подключаем Tiny MCE *}
{include file='tinymce_init.tpl'}

```

### File: Okay/Modules/OkayCMS/FAQ/Backend/design/html/faqs.tpl

```smarty
{* Title *}
{$meta_title = $btr->faq_title scope=global}

<link rel="stylesheet" href="{$rootUrl}/Okay/Modules/OkayCMS/FAQ/Backend/design/css/faq.css">

{*Название страницы*}
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">
                {$btr->faq_title|escape}
            </div>
            <div class="box_btn_heading">
                <a class="btn btn_small btn-info" href="{url controller='OkayCMS.FAQ.FAQAdmin' return=$smarty.server.REQUEST_URI}">
                    {include file='svg_icon.tpl' svgId='plus'}
                    <span>{$btr->faq_add|escape}</span>
                </a>
            </div>
        </div>
    </div>
</div>

{*Главная форма страницы*}
<div class="boxed fn_toggle_wrap">
    {if $faqs}
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <form id="list_form" method="post" class="fn_form_list">
                    <input type="hidden" name="session_id" value="{$smarty.session.id}">

                    <div class="pages_wrap okay_list">
                        {*Шапка таблицы*}
                        <div class="okay_list_head">
                            <div class="okay_list_boding okay_list_drag"></div>
                            <div class="okay_list_heading okay_list_check">
                                <input class="hidden_check fn_check_all" type="checkbox" id="check_all_1" name="" value=""/>
                                <label class="okay_ckeckbox" for="check_all_1"></label>
                            </div>
                            <div class="okay_list_heading okay_list_faq_name">{$btr->pages_name|escape}</div>
                            <div class="okay_list_heading okay_list_status">{$btr->general_enable|escape}</div>
                            <div class="okay_list_heading okay_list_close"></div>
                        </div>

                        {*Параметры элемента*}
                        <div id="sortable" class="okay_list_body sortable">
                            {foreach $faqs as $faq}
                                <div class="fn_row okay_list_body_item">
                                    <div class="okay_list_row">
                                        <input type="hidden" name="positions[{$faq->id}]" value="{$faq->position|escape}">

                                        <div class="okay_list_boding okay_list_drag move_zone">
                                            {include file='svg_icon.tpl' svgId='drag_vertical'}
                                        </div>

                                        <div class="okay_list_boding okay_list_check">
                                            <input class="hidden_check" type="checkbox" id="id_{$faq->id}" name="check[]" value="{$faq->id}"/>
                                            <label class="okay_ckeckbox" for="id_{$faq->id}"></label>
                                        </div>

                                        <div class="okay_list_boding okay_list_faq_name">
                                            <a href="{url controller="OkayCMS.FAQ.FAQAdmin" id=$faq->id return=$smarty.server.REQUEST_URI}">
                                                {$faq->question|escape}
                                            </a>
                                        </div>

                                        <div class="okay_list_boding okay_list_status">
                                            {*visible*}
                                            <label class="switch switch-default ">
                                                <input class="switch-input fn_ajax_action {if $faq->visible}fn_active_class{/if}" data-controller="OkayCMS.FAQ.FAQEntity" data-action="visible" data-id="{$faq->id}" name="visible" value="1" type="checkbox"  {if $faq->visible}checked=""{/if}/>
                                                <span class="switch-label"></span>
                                                <span class="switch-handle"></span>
                                            </label>
                                        </div>

                                        <div class="okay_list_boding okay_list_close">
                                            {*delete*}
                                            <button data-hint="{$btr->pages_delete|escape}" type="button" class="btn_close fn_remove hint-bottom-right-t-info-s-small-mobile  hint-anim" data-toggle="modal" data-target="#fn_action_modal" onclick="success_action($(this));">
                                                {include file='svg_icon.tpl' svgId='trash'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            {/foreach}
                        </div>

                        {*Блок массовых действий*}
                        <div class="okay_list_footer fn_action_block">
                            <div class="okay_list_foot_left">
                                <div class="okay_list_boding okay_list_drag"></div>
                                <div class="okay_list_heading okay_list_check">
                                    <input class="hidden_check fn_check_all" type="checkbox" id="check_all_2" name="" value=""/>
                                    <label class="okay_ckeckbox" for="check_all_2"></label>
                                </div>
                                <div class="okay_list_option">
                                    <select name="action" class="selectpicker form-control">
                                        <option value="enable">{$btr->general_do_enable|escape}</option>
                                        <option value="disable">{$btr->general_do_disable|escape}</option>
                                        <option value="delete">{$btr->general_delete|escape}</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn_small btn_blue">
                                {include file='svg_icon.tpl' svgId='checked'}
                                <span>{$btr->general_apply|escape}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-lg-12 col-md-12 col-sm 12 txt_center">
                {include file='pagination.tpl'}
            </div>
        </div>
    {else}
        <div class="heading_box mt-1">
            <div class="text_grey">{$btr->no_faqs|escape}</div>
        </div>
    {/if}
</div>
```

### File: Okay/Modules/OkayCMS/FAQ/Backend/design/css/faq.css

```css
.okay_list .okay_list_faq_name {
    width: calc(100% - 240px);
    position: relative;
    text-align: left;
}
@media only screen and (max-width : 767px) {
    .okay_list .okay_list_faq_name {
        width: calc(100% - 60px);
    }
}
```

### File: Okay/Modules/OkayCMS/FAQ/Backend/lang/en.php

```php
<?php

$lang['left_faq_title'] = 'FAQ';
$lang['faq_title'] = 'FAQ';
$lang['faq_add'] = 'Add FAQ';
$lang['no_faqs'] = 'No records';
$lang['faq_added'] = 'FAQ added';
$lang['faq_updated'] = 'FAQ updated';
$lang['faq_answer'] = 'Answer';
$lang['faq_question'] = 'Question';
```

### File: Okay/Modules/OkayCMS/FAQ/Backend/lang/ge.php

```php
<?php

$lang['left_faq_title'] = 'ხშირად დასმული კითხვები';
$lang['faq_title'] = 'ხშირად დასმული კითხვები';
$lang['faq_add'] = 'დაამატეთ ხშირად დასმული კითხვები';
$lang['no_faqs'] = 'ჩანაწერები არ არის';
$lang['faq_added'] = 'დასძინა კითხვები';
$lang['faq_updated'] = 'კითხვები განახლებულია';
$lang['faq_answer'] = 'პასუხი';
$lang['faq_question'] = 'კითხვა';
```

### File: Okay/Modules/OkayCMS/FAQ/Backend/lang/ru.php

```php
<?php

$lang['left_faq_title'] = 'FAQ';
$lang['faq_title'] = 'FAQ';
$lang['faq_add'] = 'Добавить FAQ';
$lang['no_faqs'] = 'Нет записей';
$lang['faq_added'] = 'FAQ добавлен';
$lang['faq_updated'] = 'FAQ обновлен';
$lang['faq_answer'] = 'Ответ';
$lang['faq_question'] = 'Вопрос';
```

### File: Okay/Modules/OkayCMS/FAQ/Backend/lang/ua.php

```php
<?php

$lang['left_faq_title'] = 'FAQ';
$lang['faq_title'] = 'FAQ';
$lang['faq_add'] = 'Додати FAQ';
$lang['no_faqs'] = 'Немає записів';
$lang['faq_added'] = 'FAQ добавлен';
$lang['faq_updated'] = 'FAQ доданий';
$lang['faq_answer'] = 'Відповідь';
$lang['faq_question'] = 'Питання';
```

### File: Okay/Modules/OkayCMS/FAQ/Backend/Controllers/FAQAdmin.php

```php
<?php


namespace Okay\Modules\OkayCMS\FAQ\Backend\Controllers;


use Okay\Modules\OkayCMS\FAQ\Entities\FAQEntity;
use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\EntityFactory;

class FAQAdmin extends IndexAdmin
{
    public function fetch(EntityFactory $entityFactory)
    {
        /** @var FAQEntity $FAQEntity */
        $FAQEntity = $entityFactory->get(FAQEntity::class);

        $faq = new \stdClass();
        if ($this->request->method('post')) {
            $faq->id       = $this->request->post('id', 'integer');
            $faq->question = $this->request->post('question');
            $faq->visible  = $this->request->post('visible', 'boolean');
            $faq->answer   = $this->request->post('answer');

            if (empty($faq->id)) {
                $faq->id = $FAQEntity->add($faq);
                $faq = $FAQEntity->get($faq->id);
                $this->design->assign('message_success', 'added');
            } else {
                $FAQEntity->update($faq->id, $faq);
                $faq = $FAQEntity->get($faq->id);
                $this->design->assign('message_success', 'updated');
            }
        } else {
            $faq->id = $this->request->get('id', 'integer');
            $faq = $FAQEntity->get(intval($faq->id));
        }

        $this->design->assign('faq', $faq);
        $this->response->setContent($this->design->fetch('faq.tpl'));
    }
}
```

### File: Okay/Modules/OkayCMS/FAQ/Backend/Controllers/FAQsAdmin.php

```php
<?php


namespace Okay\Modules\OkayCMS\FAQ\Backend\Controllers;


use Okay\Modules\OkayCMS\FAQ\Entities\FAQEntity;
use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\EntityFactory;

class FAQsAdmin extends IndexAdmin
{
    public function fetch(EntityFactory $entityFactory)
    {
        /** @var FAQEntity $FAQEntity */
        $FAQEntity = $entityFactory->get(FAQEntity::class);

        if ($this->request->method('post')) {
            $ids = $this->request->post('check');
            if (is_array($ids)) {
                switch ($this->request->post('action')) {
                    case 'disable': {
                        $FAQEntity->update($ids, ['visible'=>0]);
                        break;
                    }
                    case 'enable': {
                        $FAQEntity->update($ids, ['visible'=>1]);
                        break;
                    }
                    case 'delete': {
                        $FAQEntity->delete($ids);
                        break;
                    }
                }
            }

            // Сортировка
            $positions = $this->request->post('positions');
            if (!empty($positions)) {
                $ids = array_keys($positions);
                sort($positions);
                foreach($positions as $i=>$position) {
                    $FAQEntity->update($ids[$i], ['position'=>$position]);
                }
            }
        }

        $filter = [];
        $filter['page'] = max(1, $this->request->get('page', 'integer'));
        $filter['limit'] = 20;

        $keyword = $this->request->get('keyword', 'string');
        if(!empty($keyword)) {
            $filter['keyword'] = $keyword;
            $this->design->assign('keyword', $keyword);
        }

        $faqs_count = $FAQEntity->count($filter);
        if($this->request->get('page') == 'all') {
            $filter['limit'] = $faqs_count;
        }

        $faqs = $FAQEntity->find($filter);
        $this->design->assign('faqs_count', $faqs_count);
        $this->design->assign('pages_count', ceil($faqs_count/$filter['limit']));
        $this->design->assign('current_page', $filter['page']);
        $this->design->assign('faqs', $faqs);
        $this->response->setContent($this->design->fetch('faqs.tpl'));
    }
}
```

### File: Okay/Modules/OkayCMS/FAQ/Entities/FAQEntity.php

```php
<?php


namespace Okay\Modules\OkayCMS\FAQ\Entities;


use Okay\Core\Entity\Entity;

class FAQEntity extends Entity
{
    protected static $fields = [
        'id',
        'question',
        'answer',
        'visible',
        'position',
    ];

    protected static $langFields = [
        'question',
        'answer',
    ];

    protected static $defaultOrderFields = [
        'position',
    ];

    protected static $table = '__okaycms__faq__faq';
    protected static $langTable = 'okaycms__faq__faq';
    protected static $langObject = 'faq';
    protected static $tableAlias = 'of_f';
}
```

### File: Okay/Modules/OkayCMS/FAQ/Controllers/FAQController.php

```php
<?php


namespace Okay\Modules\OkayCMS\FAQ\Controllers;


use Okay\Modules\OkayCMS\FAQ\Entities\FAQEntity;
use Okay\Controllers\AbstractController;
use Okay\Core\EntityFactory;

class FAQController extends AbstractController
{
    public function render(EntityFactory $entityFactory)
    {
        /** @var FAQEntity $FAQEntity */
        $FAQEntity = $entityFactory->get(FAQEntity::class);

        $faqs = $FAQEntity->find(['visible' => 1]);
        $this->design->assign('faqs', $faqs);

        if ($this->page) {
            $this->design->assign('meta_title', $this->page->meta_title);
            $this->design->assign('meta_keywords', $this->page->meta_keywords);
            $this->design->assign('meta_description', $this->page->meta_description);
            $this->design->assign('breadcrumbs', [$this->page->name]);
        }
        
        $this->response->setContent('faq.tpl');
    }
}

```

### File: Okay/Modules/OkayCMS/FAQ/Init/Init.php

```php
<?php


namespace Okay\Modules\OkayCMS\FAQ\Init;


use Okay\Core\Modules\EntityField;
use Okay\Core\Modules\AbstractInit;
use Okay\Modules\OkayCMS\FAQ\Entities\FAQEntity;

class Init extends AbstractInit
{
    public function install()
    {
        $this->setBackendMainController('FAQsAdmin');
        $this->migrateEntityTable(FAQEntity::class, [
            (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('question'))->setTypeText()->setIsLang(),
            (new EntityField('answer'))->setTypeText()->setIsLang()->setNullable(),
            (new EntityField('visible'))->setTypeTinyInt(1),
            (new EntityField('position'))->setTypeInt(11),
        ]);
    }
    
    public function init()
    {
        $this->registerBackendController('FAQsAdmin');
        $this->addBackendControllerPermission('FAQsAdmin', 'okaycms__faq__faq');

        $this->registerBackendController('FAQAdmin');
        $this->addBackendControllerPermission('FAQAdmin', 'okaycms__faq__faq');

        $this->extendUpdateObject('OkayCMS.FAQ.FAQEntity', 'okaycms__faq__faq', FAQEntity::class);

        $this->extendBackendMenu('left_faq_title', [
            'left_faq_title' => ['FAQsAdmin', 'FAQAdmin'],
        ]);
    }
}
```

### File: Okay/Modules/OkayCMS/FAQ/Init/module.json

```json
{
  "version": "1.0.0",
  "vendor": {
    "email": "info@okay-cms.com",
    "site": "https://okay-cms.com"
  }
}
```

### File: Okay/Modules/OkayCMS/FAQ/Init/routes.php

```php
<?php

namespace Okay\Modules\OkayCMS\FAQ;

return [
    'OkayCMS_FAQ_main' => [
        'slug' => '/faq',
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\FAQController',
            'method' => 'render',
        ],
    ],
];
```

