-e <!--
TL;DR: Простое дополнительное поле к товару через EntityField. Нет своей таблицы, нет бекенда. Только: migrateEntityField + registerEntityField + отображение на фронте. ~150 строк, минимальный модуль.
Размер: ~404 строк
-->

# Пример модуля: AdditionalDescriptionField (SimplaMarket/AdditionalDescriptionField)

## Информация о модуле

**Вендор/Модуль:** `SimplaMarket/AdditionalDescriptionField`  
**Тип:** Простой модуль — дополнительное поле описания к товару  
**Демонстрирует:**
- Добавление поля к существующей сущности (`migrateEntityField` + `registerEntityField`)
- Расширение бекенд-реквеста (`ChainExtender` для `BackendProductsRequest`)
- Smarty-плагин для вывода поля на фронте
- Шорт-блок в карточке товара в админке (`addBackendBlock`)
- Страница настроек в админке

---

## Структура файлов

```
Okay/Modules/SimplaMarket/AdditionalDescriptionField/
├── Init/
│   ├── Init.php
│   ├── module.json
│   ├── services.php
│   └── SmartyPlugins.php
├── Extensions/
│   └── BackendProductsRequestExtension.php   — расширяет POST-обработку товара
├── Plugins/
│   └── AdditionalFieldDataPlugin.php          — Smarty-плагин {additional_field_data}
├── Backend/
│   ├── Controllers/
│   │   └── DescriptionAdmin.php               — страница настроек
│   ├── design/
│   │   └── html/
│   │       ├── additional_description_field.tpl — шорт-блок в карточке товара
│   │       └── description.tpl                  — страница настроек
│   └── lang/
│       ├── ru.php
│       ├── en.php
│       └── ua.php
└── design/
    └── html/
        └── additional_field_data.tpl           — шаблон вывода поля на фронте
```

---

## Исходный код

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Init/Init.php

```php
<?php


namespace Okay\Modules\SimplaMarket\AdditionalDescriptionField\Init;


use Okay\Admin\Requests\BackendProductsRequest;
use Okay\Entities\ProductsEntity;
use Okay\Core\Modules\EntityField;
use Okay\Core\Modules\AbstractInit;
use Okay\Modules\SimplaMarket\AdditionalDescriptionField\Extensions\BackendProductsRequestExtension;

class Init extends AbstractInit
{
    const ADDITIONAL_FIELD_NAME = 'simplamarket__additional_description_field__description';

    public function install()
    {
        $this->setBackendMainController('DescriptionAdmin');
        $this->migrateEntityField(ProductsEntity::class,
            (new EntityField(self::ADDITIONAL_FIELD_NAME))->setTypeText()->setNullable()->setIsLang());
    }

    public function init()
    {
        $this->registerBackendController('DescriptionAdmin');
        $this->addBackendControllerPermission('DescriptionAdmin', 'products');

        $this->registerEntityField(ProductsEntity::class, self::ADDITIONAL_FIELD_NAME, true);

        $this->registerChainExtension(
            [BackendProductsRequest::class,          'postProduct'],
            [BackendProductsRequestExtension::class, 'extendPostProduct']);

        $this->addBackendBlock('product_custom_block', 'additional_description_field.tpl');
    }
}
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Init/module.json

```json
{
  "userName": "Sam",
  "userEmail": "dabustard@gmail.com",
  "Okay": "4.5.2",
  "version": "1.3.10",
  "moduleName": "Дополнительное поле описания в товаре",
  "vendor": {
    "email": "my@simplamarket.com",
    "site": "https://simplamarket.com"
  },
  "modifications": {
    "front": [
      {
        "file": "product.tpl",
        "changes": [
          {
            "find": "<div id=\"fn_products_tab\" class=\"product-page__tabs\">",
            "appendBefore": "{additional_field_data}"
          }
        ]
      }
    ]
  }
}
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Init/services.php

```php
<?php


use Okay\Core\Request;
use Okay\Core\EntityFactory;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Modules\SimplaMarket\AdditionalDescriptionField\Extensions\BackendProductsRequestExtension;

return [
    BackendProductsRequestExtension::class => [
        'class' => BackendProductsRequestExtension::class,
        'arguments' => [
            new SR(Request::class),
            new SR(EntityFactory::class),
        ],
    ],
];
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Init/SmartyPlugins.php

```php
<?php


namespace Okay\Modules\SimplaMarket\AdditionalDescriptionField\Init;


use Okay\Core\Design;
use Okay\Core\EntityFactory;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Modules\SimplaMarket\AdditionalDescriptionField\Plugins\AdditionalFieldDataPlugin;

return [
    AdditionalFieldDataPlugin::class => [
        'class' => AdditionalFieldDataPlugin::class,
        'arguments' => [
            new SR(Design::class),
            new SR(EntityFactory::class),
        ],
    ],
];
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Extensions/BackendProductsRequestExtension.php

```php
<?php


namespace Okay\Modules\SimplaMarket\AdditionalDescriptionField\Extensions;

use Okay\Core\Request;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Modules\SimplaMarket\AdditionalDescriptionField\Init\Init;
use Okay\Core\EntityFactory;

class BackendProductsRequestExtension implements ExtensionInterface
{
    /**
     * @var
     */
    private $request;
    private $entityFactory;

    public function __construct(Request $request, EntityFactory $entityFactory)
    {
        $this->request = $request;
        $this->entityFactory = $entityFactory;
    }

    public function extendPostProduct($product)
    {
        $product->{Init::ADDITIONAL_FIELD_NAME} = $this->request->post('simplamarket__additional_description_field__description');

        return $product;
    }


}
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Plugins/AdditionalFieldDataPlugin.php

```php
<?php


namespace Okay\Modules\SimplaMarket\AdditionalDescriptionField\Plugins;


use Okay\Core\Design;
use Okay\Core\EntityFactory;
use Okay\Core\SmartyPlugins\Func;
use Okay\Entities\ProductsEntity;
use Okay\Modules\SimplaMarket\AdditionalDescriptionField\Init\Init;

class AdditionalFieldDataPlugin extends Func
{
    protected $tag = 'additional_field_data';

    /**
     * @var Design
     */
    private $design;

    /**
     * @var ProductsEntity
     */
    private $productsEntity;

    public function __construct(Design $design, EntityFactory $entityFactory)
    {
        $this->design = $design;
        $this->productsEntity = $entityFactory->get(ProductsEntity::class);
    }

    public function run($params)
    {
        if (empty($params['product_id'])) {
            $product = $this->design->getVar('product');
            if (empty($product->id)) {
                return null;
            }
            $productId = $product->id;
        } else {
            $productId = $params['product_id'];
        }

        $product = $this->productsEntity->get((int) $productId);
        if (empty($product->{Init::ADDITIONAL_FIELD_NAME})) {
            return null;
        }

        $this->design->assign('simplamarket__additional_description_field__description', $product->{Init::ADDITIONAL_FIELD_NAME});
        return $this->design->fetch('additional_field_data.tpl');
    }
}
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Backend/Controllers/DescriptionAdmin.php

```php
<?php


namespace Okay\Modules\SimplaMarket\AdditionalDescriptionField\Backend\Controllers;


use Okay\Admin\Controllers\IndexAdmin;

class DescriptionAdmin extends IndexAdmin
{
    public function fetch()
    {
        $this->response->setContent($this->design->fetch('description.tpl'));
    }
}
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Backend/design/html/additional_description_field.tpl

```smarty
<div class="col-lg-12 col-md-12">
    <div class="fn_step-14 boxed match fn_toggle_wrap tabs">
        <div class="heading_tabs">
            <div class="tab_navigation">
                <a href="#tab_additional_description_field__title"
                   class="heading_box tab_navigation_link">
                    {$btr->additional_description_field__label|escape}
                </a>
            </div>
            <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                <a class="btn-minimize" href="javascript:;"><i class="icon-arrow-down"></i></a>
            </div>
        </div>
        <div class="toggle_body_wrap on fn_card">
            <div class="tab_container">
                <div id="tab_additional_description_field__title" class="tab">
                    <textarea name="simplamarket__additional_description_field__description"
                              id="simplamarket__additional_description_field__description"
                              class="editor_small">{$product->simplamarket__additional_description_field__description|escape}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Backend/design/html/description.tpl

```smarty
{$meta_title = $btr->additional_description_field__title|escape scope=global}

{*Название страницы*}
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">
                {$btr->additional_description_field__title|escape}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="alert alert--icon">
            <div class="alert__content">
                <div class="alert__title">{$btr->additional_description_field__info|escape}</div>
                <p>{$btr->additional_description_field__description_1}</p>
            </div>
        </div>
    </div>
</div>
<div class="col-xs-12 boxed">
    <div class="row">
        <div class="col-lg-7 mb-2" style="text-align: center">
            <a href="{$rootUrl}/Okay/Modules/SimplaMarket/AdditionalDescriptionField/Backend/design/images/description.jpg">
                <img style="max-height: 300px" src="{$rootUrl}/Okay/Modules/SimplaMarket/AdditionalDescriptionField/Backend/design/images/description.jpg">
            </a>
        </div>

        <div class="col-lg-5 mb-2">
            <div class="alert alert--icon alert--warning">
                <div class="alert__content">
                    <div class="alert__title">{$btr->additional_description_field__warning}</div>
                    <p>{$btr->additional_description_field__description_2}</p>
                </div>
            </div>
        </div>
    </div>
</div>
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Backend/lang/ru.php

```php
<?php

$lang['additional_description_field__title'] = 'Дополнительное поле описания в товаре';
$lang['additional_description_field__label'] = 'Дополнительное описание';
$lang['additional_description_field__info'] = 'Описание';
$lang['additional_description_field__description_1'] = 'Данный модуль позволяет добавить к товару еще одно поле описания, которое отображается в товаре перед табами.';
$lang['additional_description_field__warning'] = 'Внимание';
$lang['additional_description_field__description_2'] = 'Если в карточке товара, после установки модуля, блок с дополнительным описанием не появился и текущая тема отличается от дефолтного шаблона - нужно вставить код <b>{additional_field_data}</b> в файл текущей темы product.tpl.';
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Backend/lang/en.php

```php
<?php

$lang['additional_description_field__title'] = 'Additional description field in the product';
$lang['additional_description_field__label'] = 'Additional Description';
$lang['additional_description_field__info'] = 'Description';
$lang['additional_description_field__description_1'] = 'This module allows you to add another description field to the product, which is displayed in the product before the tabs.';
$lang['additional_description_field__warning'] = 'Warning';
$lang['additional_description_field__description_2'] = 'If, after installing the module, a block with an additional description did not appear in the product card and the current theme differs from the default template - you need to insert the code <b> {additional_field_data} </b> into the current theme file product.tpl. ';
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/Backend/lang/ua.php

```php
<?php

$lang['additional_description_field__title'] = 'Додаткове поле опису в товарі';
$lang['additional_description_field__label'] = 'Додатковий опис';
$lang['additional_description_field__info'] = 'Опис';
$lang['additional_description_field__description_1'] = 'Даний модуль дозволяє додати до товару ще одне поле опису, яке відображається в товарі перед табами.';
$lang['additional_description_field__warning'] = 'Увага';
$lang['additional_description_field__description_2'] = 'Якщо в картці товару, після установки модуля, блок з додатковим описом не з\'явився і поточна тема відрізняється від дефолтного шаблону - потрібно вставити код <b> {additional_field_data} </b> в файл поточної теми product.tpl. ';
```

### File: Okay/Modules/SimplaMarket/AdditionalDescriptionField/design/html/additional_field_data.tpl

```smarty
<div class="block block--boxed block--border ">
    {$simplamarket__additional_description_field__description}
</div>
```

