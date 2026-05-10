-e <!--
TL;DR: Своя таблица + CRUD в бекенде + загрузка/нарезка изображений + вывод на фронте через Smarty-плагин. Читать при создании модуля со своей таблицей и изображениями.
Размер: ~1156 строк
-->

# Пример модуля: ProductsAdvantages (SimplaMarket/ProductsAdvantages)

## Информация о модуле

**Вендор/Модуль:** `SimplaMarket/ProductsAdvantages`  
**Тип:** CRUD-модуль со своей таблицей, изображениями, экстендерами и фронтом  
**Демонстрирует:**
- Собственная Entity с мультиязычными полями
- Загрузка и удаление изображений (`Image`, `addResizeObject`)
- Два ChainExtender: расширение админ-страницы товара и фронт-контроллера
- Собственный Backend Helper + Request
- Шорт-блок в карточке товара в админке
- Языковые файлы и на бекенде и на фронте
- `config/config.php` для регистрации директорий изображений

---

## Структура файлов

```
Okay/Modules/SimplaMarket/ProductsAdvantages/
├── Init/
│   ├── Init.php
│   ├── module.json
│   └── services.php
├── config/
│   └── config.php
├── Entities/
│   └── ProductsAdvanatgesEntity.php
├── Extensions/
│   ├── ProductAdminExtension.php       — расширяет страницу товара в админке
│   └── ProductControllerExtension.php  — расширяет фронт-контроллер товара
├── Backend/
│   ├── Controllers/
│   │   └── DescriptionAdmin.php
│   ├── Helpers/
│   │   └── BackendProductsAdvantagesHelper.php
│   ├── Requests/
│   │   └── BackendProductsAdvantagesRequest.php
│   ├── design/
│   │   └── html/
│   │       ├── description.tpl
│   │       └── productAdvantages.tpl   — шорт-блок в карточке товара
│   └── lang/
│       ├── ru.php
│       ├── en.php
│       └── ua.php
└── design/
    ├── html/
    │   └── product_advantages.tpl
    ├── css/
    │   └── products_advantages.css
    ├── css.php
    └── lang/
        ├── ru.php
        ├── en.php
        └── ua.php
```

---

## Исходный код

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Init/Init.php

```php
<?php


namespace Okay\Modules\SimplaMarket\ProductsAdvantages\Init;


use Okay\Admin\Controllers\ProductAdmin;
use Okay\Admin\Helpers\BackendProductsHelper;
use Okay\Admin\Helpers\BackendValidateHelper;
use Okay\Admin\Requests\BackendProductsRequest;
use Okay\Controllers\ProductController;
use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Core\Router;
use Okay\Entities\ProductsEntity;
use Okay\Helpers\ProductsHelper;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Extensions\ProductAdminExtension;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Entities\ProductsAdvanatgesEntity;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Extensions\ProductControllerExtension;

class Init extends AbstractInit
{
    public function install()
    {
        $this->setBackendMainController('DescriptionAdmin');

        if (!is_dir('files/originals/products_advantages')) {
            mkdir('files/originals/products_advantages');
        }

        if (!is_dir('files/resized/products_advantages')) {
            mkdir('files/resized/products_advantages');
        }

        $this->migrateEntityTable(ProductsAdvanatgesEntity::class, [
            (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('product_id'))->setTypeInt(11),
            (new EntityField('filename'))->setTypeVarchar(255, true),
            (new EntityField('text'))->setTypeText()->setIsLang(),
            (new EntityField('position'))->setTypeInt(11, false)->setDefault(0)
        ]);
    }

    public function init()
    {
        $this->addPermission('simplamarket__products_advantages');
        $this->registerBackendController('DescriptionAdmin');
        $this->addBackendControllerPermission('DescriptionAdmin', 'simplamarket__products_advantages');

        $this->addResizeObject('original_products_advantages_dir', 'resized_products_advantages_dir');

        $this->registerQueueExtension(
            ['class' => ProductAdmin::class, 'method' => 'fetch'],
            ['class' => ProductAdminExtension::class, 'method' => 'setProductAdmin']
        );

        $this->registerQueueExtension(
            ['class' => BackendValidateHelper::class, 'method' => 'getProductValidateError'],
            ['class' => ProductAdminExtension::class, 'method' => 'handleExistingProductAdvantages']
        );

        $this->registerQueueExtension(
            ['class' => BackendProductsHelper::class, 'method' => 'getProduct'],
            ['class' => ProductAdminExtension::class, 'method' => 'getProduct']
        );

        $this->registerQueueExtension(
            ['class' => ProductsEntity::class, 'method' => 'delete'],
            ['class' => ProductAdminExtension::class, 'method' => 'deleteByProductsIds']
        );

        $this->registerQueueExtension(
            ['class' => ProductsHelper::class, 'method' => 'attachProductData'],
            ['class' => ProductControllerExtension::class, 'method' => 'assignProductAdvantages']
        );

        $this->addBackendBlock('product_custom_block', 'productAdvantages.tpl');
    }
}
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Init/module.json

```json
{
  "userName": "Sam",
  "userEmail": "dabustard@gmail.com",
  "Okay": "4.4.0",
  "version": "1.2.6",
  "moduleName": "Преимущества продуктов",
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
            "find": "<div class=\"fn_transfer",
            "appendAfter": "{$product_advantages_html}"
          }
        ]
      }
    ]
  }
}
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Init/services.php

```php
<?php


namespace Okay\Modules\SimplaMarket\ProductsAdvantages\Init;


use Okay\Core\Config;
use Okay\Core\Design;
use Okay\Core\EntityFactory;
use Okay\Core\Image;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\Request;
use Okay\Core\Router;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Extensions\ProductAdminExtension;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Backend\Helpers\BackendProductsAdvantagesHelper;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Backend\Requests\BackendProductsAdvantagesRequest;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Extensions\ProductControllerExtension;

return [
    BackendProductsAdvantagesHelper::class => [
        'class' => BackendProductsAdvantagesHelper::class,
        'arguments' => [
            new SR(EntityFactory::class),
            new SR(Image::class),
            new SR(Config::class)
        ]
    ],
    BackendProductsAdvantagesRequest::class => [
        'class' => BackendProductsAdvantagesRequest::class,
        'arguments' => [
            new SR(Request::class)
        ]
    ],
    ProductAdminExtension::class => [
        'class' => ProductAdminExtension::class,
        'arguments' => [
            new SR(Request::class),
            new SR(BackendProductsAdvantagesRequest::class),
            new SR(BackendProductsAdvantagesHelper::class),
            new SR(EntityFactory::class),
            new SR(Design::class)
        ]
    ],
    ProductControllerExtension::class => [
        'class' => ProductControllerExtension::class,
        'arguments' => [
            new SR(EntityFactory::class),
            new SR(Design::class),
            new SR(Router::class)
        ]
    ]
];
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/config/config.php

```ini
;<?php exit(); ?>

[images]

;Директория оригиналов и нарезок фоток преимуществ товаров
original_products_advantages_dir = files/originals/products_advantages/
resized_products_advantages_dir = files/resized/products_advantages/
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Entities/ProductsAdvanatgesEntity.php

```php
<?php


namespace Okay\Modules\SimplaMarket\ProductsAdvantages\Entities;


use Okay\Core\Entity\Entity;

class ProductsAdvanatgesEntity extends Entity
{
    protected static $fields = [
        'id',
        'product_id',
        'filename',
        'position',
    ];

    protected static $langFields = [
        'text',
    ];

    protected static $defaultOrderFields = [
        'position ASC',
    ];

    protected static $table = '__simplamarket__products_advantages';
    protected static $langObject = 'product_advantage';
    protected static $langTable = 'simplamarket__products_advantages';
    protected static $tableAlias = 'sm_pa';

}
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Extensions/ProductAdminExtension.php

```php
<?php


namespace Okay\Modules\SimplaMarket\ProductsAdvantages\Extensions;


use Okay\Core\Design;
use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Request;
use Okay\Core\Router;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Entities\ProductsAdvanatgesEntity;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Backend\Helpers\BackendProductsAdvantagesHelper;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Backend\Requests\BackendProductsAdvantagesRequest;

class ProductAdminExtension implements ExtensionInterface
{
    private $request;
    private $design;

    private $productsAdvantagesRequest;
    private $productsAdvantagesHelper;

    private $productsAdvantagesEntity;

    private $productAdmin = false;
    private $product;

    public function __construct(
        Request $request,
        BackendProductsAdvantagesRequest $productsAdvantagesRequest,
        BackendProductsAdvantagesHelper $productsAdvantagesHelper,
        EntityFactory $entityFactory,
        Design $design
    )
    {
        $this->request = $request;
        $this->productsAdvantagesRequest = $productsAdvantagesRequest;
        $this->productsAdvantagesHelper = $productsAdvantagesHelper;
        $this->productsAdvantagesEntity = $entityFactory->get(ProductsAdvanatgesEntity::class);
        $this->design = $design;
    }

    public function setProductAdmin()
    {
        $this->productAdmin = true;
    }

    public function getProduct($product)
    {
        if (isset($product->id) ) {
            if ($this->productAdmin) {
                $this->product = $product;

                if ($this->request->method('POST')) {
                    $this->handleNewProductAdvantages();
                }

                $productAdvantages = $this->productsAdvantagesEntity->find(['product_id' => $product->id]);
                $this->design->assign('product_advantages', $productAdvantages);
            }
        }
    }

    public function handleExistingProductAdvantages($error)
    {
        if ($this->request->method('POST') && $this->productAdmin && !$error) {
            $PARequest = $this->productsAdvantagesRequest;
            $PAHelper = $this->productsAdvantagesHelper;

            $advantageImagesToUpload = $PARequest->filesProductAdvantagesImages();
            $advantageUpdates        = $PARequest->postProductAdvantagesUpdates();
            $advantageImagesToDelete = $PARequest->postProductAdvantagesImagesToDelete();
            foreach($advantageUpdates as $advantageId => $advantageUpdate) {
                $PAHelper->updateProductAdvantage(
                    $advantageId,
                    $advantageUpdate,
                    $advantageImagesToUpload,
                    $advantageImagesToDelete
                );
            }

            $positions = $PARequest->postPositionsProductAdvantages();
            list($ids, $positions) = $PAHelper->sortPositionsProductAdvantages($positions);
            $PAHelper->updatePositionsProductAdvantages($ids, $positions);

            // Действия с выбранными
            $ids = $PARequest->postCheckProductAdvantages();

            if(!empty($ids)) {
                switch($PARequest->postActionProductAdvantages()) {
                    case 'delete': {
                        $PAHelper->deleteProductAdvantages($ids);
                        break;
                    }
                }
            }
        }
    }

    private function handleNewProductAdvantages()
    {
        $PARequest = $this->productsAdvantagesRequest;
        $PAHelper = $this->productsAdvantagesHelper;
        $product = $this->product;

        $newAdvantages      = $PARequest->postNewProductAdvantages($product->id);
        $newAdvantageImages = $PARequest->filesNewProductAdvantagesImages();
        if (!empty($newAdvantages)) {
            foreach($newAdvantages as $key => $newAdvantage) {
                $advantageId = $this->productsAdvantagesEntity->add($newAdvantage);
                $PAHelper->uploadProductAdvantageImage($advantageId, $newAdvantageImages[$key]);
            }
        }
    }

    public function deleteByProductsIds($status, $ids)
    {
        if ($status) {
            $advantagesIds = [];
            $advantages = $this->productsAdvantagesEntity->find(['product_id' => $ids]);
            foreach ($advantages as $advantage) {
                $advantagesIds[] = $advantage->id;
            }
            $this->productsAdvantagesHelper->deleteProductAdvantages($advantagesIds);
        }
    }
}
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Extensions/ProductControllerExtension.php

```php
<?php


namespace Okay\Modules\SimplaMarket\ProductsAdvantages\Extensions;


use Okay\Core\Design;
use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Router;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Entities\ProductsAdvanatgesEntity;

class ProductControllerExtension implements ExtensionInterface
{
    private $design;

    private $productAdvantagesEntity;

    private $productController = false;

    public function __construct(
        EntityFactory $entityFactory,
        Design $design,
        Router $router
    )
    {
        $this->design = $design;
        $this->productAdvantagesEntity = $entityFactory->get(ProductsAdvanatgesEntity::class);
        if ($router->getCurrentRouteName() == 'product') {
            $this->productController = true;
        }
    }

    public function assignProductAdvantages($product)
    {
        if ($this->productController) {
            // Устанавливаем директорию HTML из модуля
            $this->design->setModuleDir(__CLASS__);

            $advantages = $this->productAdvantagesEntity->find(['product_id' => $product->id]);
            $this->design->assign('product_advantages', $advantages);
            $this->design->assign('product_advantages_html', $this->design->fetch('product_advantages.tpl'));

            // Вернём обратно стандартную директорию шаблонов
            $this->design->rollbackTemplatesDir();
        }
    }
}
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Backend/Controllers/DescriptionAdmin.php

```php
<?php


namespace Okay\Modules\SimplaMarket\ProductsAdvantages\Backend\Controllers;


use Okay\Admin\Controllers\IndexAdmin;

class DescriptionAdmin extends IndexAdmin
{
    public function fetch()
    {
        $this->response->setContent($this->design->fetch('description.tpl'));
    }
}
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Backend/Helpers/BackendProductsAdvantagesHelper.php

```php
<?php


namespace Okay\Modules\SimplaMarket\ProductsAdvantages\Backend\Helpers;


use Okay\Core\Config;
use Okay\Core\EntityFactory;
use Okay\Core\Image;
use Okay\Modules\SimplaMarket\ProductsAdvantages\Entities\ProductsAdvanatgesEntity;

class BackendProductsAdvantagesHelper
{
    private $productsAdvantagesEntity;
    private $imageCore;
    private $config;

    public function __construct(
        EntityFactory $entityFactory,
        Image $imageCore,
        Config $config
    )
    {
        $this->productsAdvantagesEntity = $entityFactory->get(ProductsAdvanatgesEntity::class);
        $this->imageCore = $imageCore;
        $this->config = $config;
    }

    public function updateProductAdvantage(
        $advantageId,
        $updates,
        $advantageImagesToUpload,
        $advantageImagesToDelete
    )
    {
        if (in_array($advantageId, $advantageImagesToDelete)) {
            $this->deleteProductAdvantageImage($advantageId);
        }

        if (in_array($advantageId, array_keys($advantageImagesToUpload))) {
            $this->uploadProductAdvantageImage($advantageId, $advantageImagesToUpload[$advantageId]);
        }

        $this->productsAdvantagesEntity->update($advantageId, $updates);
    }

    public function sortPositionsProductAdvantages($positions)
    {
        $positions = (array) $positions;
        $ids       = array_keys($positions);
        sort($positions);
        return [$ids, $positions];
    }

    public function updatePositionsProductAdvantages($ids, $positions)
    {
        foreach ($positions as $i => $position) {
            $this->productsAdvantagesEntity->update($ids[$i], ['position' => (int)$position]);
        }
    }

    public function deleteProductAdvantages($ids)
    {
        foreach ($ids as $id) {
            $this->deleteProductAdvantageImage((int) $id);
        }
        $result = $this->productsAdvantagesEntity->delete($ids);

        return $result;
    }

    public function uploadProductAdvantageImage($advantageId, $fileImage)
    {
        if (!empty($fileImage['name']) &&
            ($filename = $this->imageCore->uploadImage(
                $fileImage['tmp_name'],
                $fileImage['name'],
                $this->config->original_products_advantages_dir))
        ) {
            $this->imageCore->deleteImage(
                $advantageId,
                'filename',
                ProductsAdvanatgesEntity::class,
                $this->config->original_products_advantages_dir,
                $this->config->resized_products_advantages_dir
            );

            $this->productsAdvantagesEntity->update($advantageId, ['filename' => $filename]);
        }
    }

    public function deleteProductAdvantageImage($advantageId)
    {
        $this->imageCore->deleteImage(
            $advantageId,
            'filename',
            ProductsAdvanatgesEntity::class,
            $this->config->original_products_advantages_dir,
            $this->config->resized_products_advantages_dir
        );

        $this->productsAdvantagesEntity->update($advantageId, ['filename' => '']);
    }
}
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Backend/Requests/BackendProductsAdvantagesRequest.php

```php
<?php


namespace Okay\Modules\SimplaMarket\ProductsAdvantages\Backend\Requests;


use Okay\Core\Request;

class BackendProductsAdvantagesRequest
{
    private $request;

    public function __construct(
        Request $request
    )
    {
        $this->request = $request;
    }

    public function filesProductAdvantagesImages()
    {
        $images = (array) $this->request->files('product_advantages_image');

        if (empty($images['name'])) {
            return [];
        }

        $advantagesImages = [];
        foreach($images['name'] as $advantageId => $imageName) {
            $advantageImage = [];
            $advantageImage['name']        = $imageName;
            $advantageImage['tmp_name']    = $images['tmp_name'][$advantageId];
            $advantagesImages[$advantageId] = $advantageImage;
        }

        return $advantagesImages;
    }

    public function postProductAdvantagesUpdates()
    {
        $advantages = [];

        $position = 0;
        foreach((array) $this->request->post('product_advantages_text') as $id => $advantageText) {
            $advantage           = new \stdClass();
            $advantage->text     = $advantageText;
            $advantage->position = $position;

            $advantages[$id] = $advantage;

            $position++;
        }

        return $advantages;
    }

    public function postProductAdvantagesImagesToDelete()
    {
        $deleteAdvantagesImages = (array) $this->request->post('product_advantages_images_to_delete');
        $preparedDeleteAdvantagesImages = [];
        foreach($deleteAdvantagesImages as $advantageId => $deleteAdvantageImage) {
            if (!empty($deleteAdvantageImage)) {
                $preparedDeleteAdvantagesImages[] = $advantageId;
            }
        }

        return $preparedDeleteAdvantagesImages;
    }

    public function postPositionsProductAdvantages()
    {
        $positions = $this->request->post('product_advantages_positions');
        return $positions;
    }

    public function postCheckProductAdvantages()
    {
        $ids = $this->request->post('product_advantages_check');
        return $ids;
    }

    public function postActionProductAdvantages()
    {
        $action = $this->request->post('product_advantages_action');
        return $action;
    }

    public function postNewProductAdvantages($productId)
    {
        $newAdvantages = $this->request->post('new_product_advantages');

        if (empty($newAdvantages)) {
            return [];
        }

        $preparedNewAdvantages = [];
        foreach($newAdvantages['text'] as $key => $text) {
            $newAdvantage = new \stdClass();
            $newAdvantage->product_id = $productId;
            $newAdvantage->text = $text;
            $preparedNewAdvantages[] = $newAdvantage;
        }

        return $preparedNewAdvantages;
    }

    public function filesNewProductAdvantagesImages()
    {
        $images = $this->request->files('new_product_advantages_images');

        if (empty($images)) {
            return [];
        }

        $newAdvantagesImages = [];
        foreach($images['name'] as $key => $imageName) {
            $newAdvantageImage = [];
            $newAdvantageImage['name']     = $imageName;
            $newAdvantageImage['tmp_name'] = $images['tmp_name'][$key];
            $newAdvantagesImages[$key]      = $newAdvantageImage;
        }

        return $newAdvantagesImages;
    }
}
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Backend/design/html/description.tpl

```smarty
{$meta_title = $btr->simplamarket__products_advantages__description_title|escape scope=global}

{*Название страницы*}
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">
                {$btr->simplamarket__products_advantages__description_title|escape}
            </div>
        </div>
    </div>
    <div class="col-md-12 col-lg-12 col-sm-12 float-xs-right"></div>
</div>


<div class="alert alert--icon">
    <div class="alert__content">
        <div class="alert__title">{$btr->general_module_description}</div>
        <p>{$btr->simplamarket__products_advantages__description_description}</p>
    </div>
</div>


<div class="row">
    <div class="col-xs-12">
        <div class="boxed">
            <div style="margin: 10px 0;">
                <a style="display: inline-block;vertical-align: middle;margin: 0 10px 10px 0;" href="{$rootUrl}/Okay/Modules/SimplaMarket/ProductsAdvantages/Backend/design/images/product_front.png">
                    <img style="max-height: 120px" src="{$rootUrl}/Okay/Modules/SimplaMarket/ProductsAdvantages/Backend/design/images/product_front.png">
                </a>
            </div>
        </div>
    </div>
</div>
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Backend/design/html/productAdvantages.tpl

```smarty
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="boxed fn_toggle_wrap min_height_210px">
            <div class="heading_box">
                {$btr->settings_advantages}
            </div>

            <div class="okay_list products_list">
                {*Шапка таблицы*}
                <div class="fn_step_sorting okay_list_head">
                    <div class="okay_list_boding okay_list_drag"></div>
                    <div class="okay_list_heading okay_list_check">
                        <input class="hidden_check fn_check_all" type="checkbox" id="check_all_1" name="" value="" />
                        <label class="okay_ckeckbox" for="check_all_1"></label>
                    </div>
                    <div class="okay_list_heading okay_list_advantage_image">{$btr->advantage_image_title}</div>

                    <div class="okay_list_heading okay_list_advantage_description">{$btr->advantage_description_title}</div>

                    <div class="okay_list_heading okay_list_close hidden-sm-down"></div>
                </div>

                <div class="okay_list_body sort_extended fn_advantage_list">
                    {foreach $product_advantages as $advantage}
                        <div class="fn_step-1 fn_row okay_list_body_item fn_sort_item">
                            <div class="okay_list_row">
                                <input type="hidden" name="product_advantages_positions[{$advantage->id}]" value="{$advantage->position}">

                                <div class="okay_list_boding okay_list_drag move_zone">
                                    {include file='svg_icon.tpl' svgId='drag_vertical'}
                                </div>

                                <div class="okay_list_boding okay_list_check">
                                    <input class="hidden_check" type="checkbox" id="id_{$advantage->id}" name="product_advantages_check[]" value="{$advantage->id}"/>
                                    <label class="okay_ckeckbox" for="id_{$advantage->id}"></label>
                                </div>

                                <div class="okay_list_boding okay_list_advantage_image">
                                    <div class="fn_image_block">

                                        {if $advantage->filename}
                                            <input type="hidden" class="fn_accept_delete" name="product_advantages_images_to_delete[{$advantage->id}]" value="">
                                            <div class="fn_parent_image">
                                                <div class="advantage__image image_wrapper fn_image_wrapper text-xs-center">
                                                    <a href="javascript:;" class="fn_delete_item remove_image"></a>
                                                    <img src="{$advantage->filename|resize:120:120:false:$config->resized_products_advantages_dir}" alt="" />
                                                </div>
                                            </div>
                                        {else}
                                            <div class="fn_parent_image"></div>
                                        {/if}

                                        <div class="fn_upload_image dropzone_block_image {if $advantage->filename} hidden{/if}">
                                            <i class="fa fa-plus font-5xl" aria-hidden="true"></i>
                                            <input class="dropzone_image" name="product_advantages_image[{$advantage->id}]" type="file" />
                                        </div>

                                        <div class="brand_image image_wrapper fn_image_wrapper fn_new_image text-xs-center hidden">
                                            <a href="javascript:;" class="fn_delete_item remove_image"></a>
                                            <img style="max-height: 73px;" src="" alt="" />
                                        </div>
                                    </div>
                                </div>

                                <div class="okay_list_boding okay_list_advantage_description">
                                    <textarea class="editor_small advantage_textarea form-control short_textarea" name="product_advantages_text[{$advantage->id}]">{$advantage->text}</textarea>
                                </div>

                                <div class="okay_list_boding okay_list_close hidden-sm-down">
                                    {*delete*}
                                    <button data-hint="{$btr->general_delete_product|escape}" type="button" class="btn_close fn_remove hint-bottom-right-t-info-s-small-mobile  hint-anim" data-toggle="modal" data-target="#fn_action_modal" onclick="success_action($(this));">
                                        {include file='svg_icon.tpl' svgId='trash'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                    <div class="okay_list_body_item">
                        <button class="fn_add_new_advantage btn btn_small btn-info" type="button">
                            {include file='svg_icon.tpl' svgId='plus'}
                            <span>Добавить преимущество</span>
                        </button>
                    </div>
                    <div class="heading_label" style="margin-top: 12px;">
                        <span>Рекомендуемый размер изображения 600х380px.</span>
                    </div>
                </div>


                <div id="new_advantage" class="fn_step-1 fn_row okay_list_body_item fn_sort_item hidden">
                    <div class="okay_list_row">
                        <div class="okay_list_boding okay_list_drag "></div>
                        <div class="okay_list_boding okay_list_check"></div>
                        <div class="okay_list_boding okay_list_advantage_image">
                            <div class="fn_image_block">
                                <div class="fn_parent_image"></div>
                                <div class="fn_upload_image dropzone_block_image">
                                    <i class="fa fa-plus font-5xl" aria-hidden="true"></i>
                                    <input class="dropzone_image" name="new_product_advantages_images[]" type="file" disabled />
                                </div>

                                <div class="advantage__image image_wrapper fn_image_wrapper fn_new_image text-xs-center hidden">
                                    <a href="javascript:;" class="fn_delete_item remove_image"></a>
                                    <img style="max-height: 73px;" src="" alt="" />
                                </div>
                            </div>
                        </div>

                        <div class="okay_list_boding okay_list_advantage_description">
                            <textarea class="advantage__textarea form-control short_textarea" disabled name="new_product_advantages[text][]"></textarea>
                        </div>

                        <div class="okay_list_boding okay_list_close hidden-sm-down">
                            {*delete*}
                            <button data-hint="{$btr->general_delete_product|escape}" type="button" class="btn_close fn_remove_new hint-bottom-right-t-info-s-small-mobile  hint-anim"">
                            {include file='svg_icon.tpl' svgId='trash'}
                            </button>
                        </div>
                    </div>
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
                            <select name="product_advantages_action" class="selectpicker form-control products_action">
                                <option value="delete">{$btr->general_delete|escape}</option>
                            </select>
                        </div>

                        <div class="fn_additional_params">
                            <div class="fn_move_to_page col-lg-12 col-md-12 col-sm-12 hidden fn_hide_block">
                                <select name="target_page" class="selectpicker form-control dropup">
                                    {section target_page $pages_count}
                                        <option value="{$smarty.section.target_page.index+1}">{$smarty.section.target_page.index+1}</option>
                                    {/section}
                                </select>
                            </div>
                            <div class="fn_move_to_category col-lg-12 col-md-12 col-sm-12 hidden fn_hide_block">
                                <select name="target_category" class="selectpicker form-control dropup" data-live-search="true" data-size="10">
                                    {function name=category_select_btn level=0}
                                        {foreach $categories as $category}
                                            <option value='{$category->id}'>{section sp $level}&nbsp;&nbsp;&nbsp;&nbsp;{/section}{$category->name|escape}</option>
                                            {category_select_btn categories=$category->subcategories selected_id=$selected_id level=$level+1}
                                        {/foreach}
                                    {/function}
                                    {category_select_btn categories=$categories}
                                </select>
                            </div>
                            <div class="fn_move_to_brand col-lg-12 col-md-12 col-sm-12 hidden fn_hide_block">
                                <select name="target_brand" class="selectpicker form-control dropup" data-live-search="true" data-size="{if $brands|count<10}{$brands|count}{else}10{/if}">
                                    <option value="0">{$btr->general_not_set|escape}</option>
                                    {foreach $all_brands as $b}
                                        <option value="{$b->id}">{$b->name|escape}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn_small btn_blue">
                        {include file='svg_icon.tpl' svgId='checked'}
                        <span>{$btr->general_apply|escape}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        $(document).on('click', '.fn_remove_new', function() {
            $(this).closest('.fn_row').remove();
        });

        $('.fn_add_new_advantage').on('click', function() {
            const new_advantage = $('#new_advantage').clone();
            new_advantage.removeAttr('id');
            new_advantage.find('[name]').prop('disabled', false);
            new_advantage.removeClass('hidden');
            $('.fn_advantage_list').append(new_advantage);
        });

        $(document).on("mouseenter click", ".fn_color", function () {
            var elem = $(this);
            elem.ColorPicker({
                onChange: function (hsb, hex, rgb) {
                    elem.css('backgroundColor', '#' + hex);
                    elem.next().val('#' + hex);
                },
                onBeforeShow: function () {
                    $(this).ColorPickerSetColor($(this).next().val());
                }
            });
        });

        $(".fn_submit_delete").on("click",function () {
            setTimeout(function(){
                $("form#product").submit();
            }, 1)
        });
    });
</script>
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Backend/lang/ru.php

```php
<?php

$lang['simplamarket__products_advantages__description_title'] = 'Преимущества продуктов';
$lang['simplamarket__products_advantages__description_description'] = 'Модуль реализует возможность добавлять "Преимущества" (набор изображение и описание) для каждого продукта отдельно и потом выводить их на сайте.';
$lang['simplamarket__products_advantages__description_instruction'] = '<p>После установки модуля в админ панели, добавьте код, указанный под данным описанием, в теме вашего проекта в <strong> product.tpl</strong>.</p> <p> В дефолтном шаблоне по умолчанию рекомендуем вставить как указано на скриншоте:</p>';
$lang['general_module_description'] = 'Описание';
$lang['general_module_instruction'] = 'Инструкция';
$lang['general_module_code'] = 'Код';
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Backend/lang/en.php

```php
<?php

$lang['simplamarket__products_advantages__description_title'] = 'Product Advantages';
$lang['simplamarket__products_advantages__description_description'] = 'The module implements the ability to add "Benefits" (a set of images and descriptions) for each product separately and then display them on the site.';
$lang['simplamarket__products_advantages__description_instruction'] = '<p> After installing the module in the admin panel, add the code specified under this description in the theme of your project in <strong> product.tpl </strong>. </p> <p> In the default template, we recommend inserting as indicated in the screenshot: </p>';
$lang['general_module_description'] = 'Description';
$lang['general_module_instruction'] = 'Instruction';
$lang['general_module_code'] = 'Code';
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/Backend/lang/ua.php

```php
<?php

$lang['simplamarket__products_advantages__description_title'] = 'Переваги продуктів';
$lang['simplamarket__products_advantages__description_description'] = 'Модуль реалізує можливість додавати "Переваги" (набір зображення і опис) для кожного продукту окремо і потім виводити їх на сайті.';
$lang['simplamarket__products_advantages__description_instruction'] = '<p> Після установки модуля в адмін панелі, додайте код, вказаний під даним описом, в темі вашого проекту в <strong> product.tpl </strong>. </p> <p> В дефолтному шаблоні за замовчуванням рекомендуємо вставити як зазначено на скріншоті: </p>';
$lang['general_module_description'] = 'Опис';
$lang['general_module_instruction'] = 'Інструкція';
$lang['general_module_code'] = 'Код';
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/design/html/product_advantages.tpl

```smarty
<div class="products_advantages_block">
    <div class="products_advantages_title">{$lang->advantages}</div>
    <div class="products_advantages_block_inner">
        {foreach $product_advantages as $advantage}
            <div class="products_advantages_l">
                <div class="products_advantages_img_block">
                    <img class="products_advantages_img"src="{$advantage->filename|resize:600:380:false:$config->resized_products_advantages_dir:'center':'center'}" alt="{$advantage->text}">
                </div>
                <div class="products_advantages_txt_block">
                    {$advantage->text}
                </div>
            </div>
        {/foreach}
    </div>
</div>
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/design/css/products_advantages.css

```css
.products_advantages_block {
	position: relative;
	margin-bottom: 15px;
	background-color: rgb(255, 255, 255);
	border: 1px solid #dbdbdb;
}
.products_advantages_title {
	cursor: pointer;
	font-weight: 600;
	font-size: 16px;
	user-select: none;
	padding: 15px;
	color: #222;
	background: #fff;
	overflow: hidden;
	position: relative;
	margin: 0;
	border-bottom: 3px solid #e9eaed;
}
.products_advantages_l {
	display: flex;
	padding: 15px;
	flex-direction: column;
	align-items: center;
	text-transform: uppercase;
	width: 33%;
}
.products_advantages_img_block {
	height: 100%;
	overflow: hidden;
	display: flex;
	justify-content: center;
	align-items: flex-start;
}
.products_advantages_img {
	height: auto;
	width: auto;
	max-width: 100%;
	max-height: 100%;
}
.products_advantages_txt_block {
	padding-left: 15px;
	font-size: 16px;
	line-height: 1.2;
	font-weight: 600;
	margin-top: 15px;
}
.products_advantages_block_inner {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-around;
}
@media (max-width: 768px) {
	.products_advantages_l {
		width: 50%;
	}
}
@media (max-width: 576px) {
	.products_advantages_txt_block {
		font-size: 14px;
	}
}
@media (max-width: 425px) {
	.products_advantages_l {
		width: 100%;
	}
}
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/design/css.php

```php
<?php
/**
 * Нужно вернуть массив объектов типа Okay\Core\TemplateConfig\Css
 * В конструктор объекта нужно передать один обязательный параметр - название файла
 * Если скрипт лежит не в стандартном месте (design/theme_name/css/)
 * нужно указать новое место, вызвав метод setDir() и передать путь к файл относительно корня сайта (DOCUMENT_ROOT)
 * Также можно вызвать метод setPosition() и указать head или footer (по умолчанию head)
 * todo ссылка на документацию
 */

use Okay\Core\TemplateConfig\Css;

return [
	(new Css('products_advantages.css'))
];
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/design/lang/ru.php

```php
<?php

$lang = array();
$lang['advantages'] = "Преимущества";
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/design/lang/en.php

```php
<?php

$lang = array();
$lang['advantages'] = "Аdvantages";
```

### File: Okay/Modules/SimplaMarket/ProductsAdvantages/design/lang/ua.php

```php
<?php

$lang = array();
$lang['advantages'] = "Переваги";
```

