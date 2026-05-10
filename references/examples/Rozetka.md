-e <!--
TL;DR: Импорт/экспорт CSV + XML-фид для Rozetka + своя сущность + интеграция с внешней системой. Самый сложный пример. Читать только при задачах с импортом, экспортом или XML-фидами.
Размер: ~1987 строк
-->

# Пример модуля: Rozetka (OkayCMS/Rozetka)

## Информация о модуле

**Вендор/Модуль:** `OkayCMS/Rozetka`  
**Тип:** XML-фид + импорт/экспорт CSV + своя Entity + ExtendsEntities  
**Демонстрирует:**
- `setModuleType(MODULE_TYPE_XML)` — тип модуля
- Две собственных Entity (фиды + связи)
- Паттерн `ExtendsEntities` — кастомный фильтр через `AbstractModuleEntityFilter`
- Полный паттерн импорт/экспорт CSV (5 точек расширения)
- `addBackendBlock` с callback-функцией
- `registerEntityFilter` для кастомного JOIN
- Генерация XML-фида через небуферизированные запросы

---

## Структура файлов

```
Okay/Modules/OkayCMS/Rozetka/
├── Init/
│   ├── Init.php
│   ├── module.json
│   ├── routes.php
│   └── services.php
├── Entities/
│   ├── RozetkaFeedsEntity.php
│   └── RozetkaRelationsEntity.php
├── ExtendsEntities/
│   └── ProductsEntity.php              — кастомный фильтр через AbstractModuleEntityFilter
├── Extenders/
│   └── BackendExtender.php             — импорт/экспорт CSV
├── Helpers/
│   ├── BackendRozetkaHelper.php
│   └── RozetkaHelper.php
├── Backend/
│   ├── Controllers/
│   │   └── RozetkaXmlAdmin.php
│   ├── design/
│   │   └── html/
│   │       ├── import_fields_association.tpl
│   │       └── rozetka_xml.tpl
│   └── lang/
│       ├── ru.php
│       ├── en.php
│       └── ua.php
├── Controllers/
│   └── RozetkaController.php
└── design/
    └── html/
        ├── feed_head.xml.tpl
        └── feed_footer.xml.tpl
```

---

## Исходный код


### File: Okay/Modules/OkayCMS/Rozetka/Extenders/BackendExtender.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka\Extenders;


use Okay\Core\Design;
use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaFeedsEntity;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaRelationsEntity;
use Okay\Modules\OkayCMS\Rozetka\Init\Init;

class BackendExtender implements ExtensionInterface
{
    /** @var RozetkaFeedsEntity */
    private $feedsEntity;

    /** @var RozetkaRelationsEntity */
    private $relationsEntity;

    /** @var Design */
    private $design;


    /** @var array */
    private $currentFeeds  = [];

    public function __construct(
        EntityFactory $entityFactory,
        Design        $design
    )
    {
        $this->design = $design;

        $this->feedsEntity     = $entityFactory->get(RozetkaFeedsEntity::class);
        $this->relationsEntity = $entityFactory->get(RozetkaRelationsEntity::class);

        $this->currentFeeds = $this->feedsEntity->find(['limit' => $this->feedsEntity->count()]);
    }

    public function parseProductData($product)
    {
        $feeds = $this->currentFeeds;

        foreach ($feeds as $feed) {
            $columnName = Init::TO_FEED_FIELD . "@{$feed->id}";
            if (isset($product[$columnName])) {
                unset($product[$columnName]);
            }
        }

        return $product;
    }

    public function importItem($importedItem, $itemFromCsv)
    {
        $feeds = $this->currentFeeds;

        foreach ($feeds as $feed) {
            $columnName = Init::TO_FEED_FIELD . "@{$feed->id}";
            if (isset($itemFromCsv[$columnName])) {
                if (trim($itemFromCsv[$columnName])) {
                    $this->relationsEntity->add([
                        'feed_id'     => $feed->id,
                        'entity_id'   => $importedItem->product->id,
                        'entity_type' => 'product',
                        'include'     => 1
                    ]);
                }
            } else {
                continue;
            }
        }
    }

    public function extendExportColumnsNames($product)
    {
        $feeds = $this->currentFeeds;

        for ($i = 1; $i <= count($feeds); $i++) {
            $product[Init::TO_FEED_FIELD . '_' . $i] = Init::TO_FEED_FIELD . ' ' . $i;
        }

        return $product;
    }

    public function extendFilter($params)
    {
        list($filter, $page) = $params;

        $filter[Init::FILTER_FEEDS] = true;

        return [$filter, $page];
    }

    public function getModulesColumnsNames($modulesColumnsNames)
    {
        $feeds = $this->currentFeeds;

        foreach ($feeds as $feed) {
            $modulesColumnsNames[] = Init::TO_FEED_FIELD . '@' . $feed->id;
        }

        return $modulesColumnsNames;
    }
}
```

### File: Okay/Modules/OkayCMS/Rozetka/Helpers/BackendRozetkaHelper.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka\Helpers;


use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\QueryFactory;
use Okay\Core\Request;
use Okay\Entities\BrandsEntity;
use Okay\Entities\CategoriesEntity;
use Okay\Helpers\ProductsHelper;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaFeedsEntity;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaRelationsEntity;

class BackendRozetkaHelper
{
    /** @var QueryFactory */
    private $queryFactory;

    /** @var Request */
    private $request;

    /** @var ProductsHelper */
    private $productsHelper;


    /** @var RozetkaFeedsEntity */
    private $feedsEntity;

    /** @var RozetkaRelationsEntity */
    private $relationsEntity;

    public function __construct(
        EntityFactory  $entityFactory,
        QueryFactory   $queryFactory,
        Request        $request,
        ProductsHelper $productsHelper
    )
    {
        $this->queryFactory   = $queryFactory;
        $this->request        = $request;
        $this->productsHelper = $productsHelper;

        $this->feedsEntity     = $entityFactory->get(RozetkaFeedsEntity::class);
        $this->relationsEntity = $entityFactory->get(RozetkaRelationsEntity::class);
    }

    /**
     * @param array $feed
     * Добавляем новый фид
     * @return integer|bool
     */
    public function addFeed($feed = [
        'name' => 'New Feed',
        'url' => '',
        'enabled' => 0
    ])
    {
        if (empty($feed['url'])) {
            $feed['url'] = $this->feedsEntity->count() + 1;

            while ($this->feedsEntity->findOne(['url' => $feed['url']])) {
                $feed['url']++;
            }
        }

        $feedId = $this->feedsEntity->add($feed);

        return ExtenderFacade::execute(__METHOD__, $feedId, func_get_args());
    }

    /**
     * @param string|integer $feedId
     * Удаляем фид
     */
    public function removeFeed($feedId)
    {
        $this->feedsEntity->delete($feedId);
    }

    /**
     * @param array $feeds
     * Обновляем полученные фиды
     */
    public function updateFeeds($feeds)
    {
        foreach ($feeds as $feedId => $feed) {
            $this->feedsEntity->update($feedId, $feed);
        }

        return ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }

    /**
     * @param string|integer|array $feeds
     * Валидируем фиды. Проверяем URL на уникальность
     * @return array
     * Возвращаем ошибки, индивидуальные для каждого фида
     */
    public function validateFeeds($feeds)
    {
        $errors = [];
        foreach ($feeds as $feedId => $feed) {
            if (($dbFeed = $this->feedsEntity->findOne(['url' => $feed['url']])) && ($dbFeed->id != $feedId)) {
                $errors['feeds'][$feedId]['url'] = true;
            } else if (preg_match('/[А-я]/', $feed['url'])) {
                $errors['feeds'][$feedId]['url_cyrillic'] = true;
            }
        }

        return ExtenderFacade::execute(__METHOD__, $errors, func_get_args());
    }

    /**
     * @param string|integer $feedId
     * Закрепляяем все категории за фидом
     */
    public function addAllCategories($feedId)
    {
        $this->relationsEntity->removeAllCategoriesByFeedId($feedId);

        $select = $this->queryFactory->newSelect();
        $select ->from(CategoriesEntity::getTable())
            ->cols(['id']);
        $categoriesIds = $select->results('id');
        $rows = [];
        foreach ($categoriesIds as $categoryId) {
            $rows[] = [
                'feed_id'     => $feedId,
                'entity_id'   => $categoryId,
                'entity_type' => 'category',
                'include'     => 1
            ];
        }

        $this->relationsEntity->addRelations($rows);

        return ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }

    /**
     * @param array $relatedCategories
     * Закрепляем за фидом вручуню отмеченные категории
     */
    public function updateRelatedCategories($relatedCategories)
    {
        $this->relationsEntity->removeAllCategories();

        if (!empty($relatedCategories)) {
            $rows = [];
            foreach ($relatedCategories as $feedId => $categoriesIds) {
                foreach ($categoriesIds as $categoryId) {
                    $rows[] = [
                        'feed_id'     => $feedId,
                        'entity_id'   => $categoryId,
                        'entity_type' => 'category',
                        'include'     => 1
                    ];
                }
            }

            $this->relationsEntity->addRelations($rows);
        }

        return ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }

    /**
     * @param string|integer $feedId
     * Закрепяем все бренды за фидом
     */
    public function addAllBrands($feedId)
    {
        $this->relationsEntity->removeAllBrandsByFeedId($feedId);

        $select = $this->queryFactory->newSelect();
        $select ->from(BrandsEntity::getTable())
            ->cols(['id']);
        $brandsIds = $select->results('id');
        $rows = [];
        foreach ($brandsIds as $brandId) {
            $rows[] = [
                'feed_id'     => $feedId,
                'entity_id'   => $brandId,
                'entity_type' => 'brand',
                'include'     => 1
            ];
        }

        $this->relationsEntity->addRelations($rows);

        return ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }

    /**
     * @param array $relatedBrands
     * Закрепляем за фидом вручуню отмеченные бренды
     */
    public function updateRelatedBrands($relatedBrands)
    {
        $this->relationsEntity->removeAllBrands();

        if (!empty($relatedBrands)) {
            $rows = [];
            foreach ($relatedBrands as $feedId => $brandsIds) {
                foreach ($brandsIds as $brandId) {
                    $rows[] = [
                        'feed_id'     => $feedId,
                        'entity_id'   => $brandId,
                        'entity_type' => 'brand',
                        'include'     => 1
                    ];
                }
            }

            $this->relationsEntity->addRelations($rows);
        }

        return ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }

    /**
     * Закрепляем за фидом вручуню отмеченные продукты
     */
    public function updateRelatedProducts()
    {
        $this->relationsEntity->removeAllRelatedProducts();

        $feeds = $this->feedsEntity->find(['limit' => $this->feedsEntity->count()]);

        $rows = [];
        foreach ($feeds as $feed) {
            $relatedProducts = $this->request->post("related_products_{$feed->id}");
            if (!empty($relatedProducts)) {
                $relatedProducts = array_unique($relatedProducts);
                foreach ($relatedProducts as $productId) {
                    $rows[] = [
                        'feed_id'     => $feed->id,
                        'entity_id'   => $productId,
                        'entity_type' => 'product',
                        'include'     => 1
                    ];
                }
            }
        }

        $this->relationsEntity->addRelations($rows);

        return ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }

    /**
     * Закрепляем за фидом вручуню отмеченные продукты не для выгрузки
     */
    public function updateNotRelatedProducts()
    {
        $this->relationsEntity->removeAllNotRelatedProducts();

        $feeds = $this->feedsEntity->find(['limit' => $this->feedsEntity->count()]);

        $rows = [];
        foreach ($feeds as $feed) {
            $notRelatedProducts = $this->request->post("not_related_products_{$feed->id}");
            if (!empty($notRelatedProducts)) {
                $notRelatedProducts = array_unique($notRelatedProducts);
                foreach ($notRelatedProducts as $productId) {
                    $rows[] = [
                        'feed_id'     => $feed->id,
                        'entity_id'   => $productId,
                        'entity_type' => 'product',
                        'include'     => 0
                    ];
                }
            }
        }

        $this->relationsEntity->addRelations($rows);

        return ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }

    /**
     * @return array
     * Достаем массив ids закрепённых категорий
     */
    public function getAllRelatedCategoriesIds()
    {
        $allCategoriesRelations = $this->relationsEntity->find([
            'limit' => $this->relationsEntity->count(),
            'entity_type' => 'category'
        ]);

        $relatedCategoriesIds = [];
        foreach ($allCategoriesRelations as $categoryRelation) {
            $relatedCategoriesIds[$categoryRelation->feed_id][] = $categoryRelation->entity_id;
        }

        return ExtenderFacade::execute(__METHOD__, $relatedCategoriesIds, func_get_args());
    }

    /**
     * @return array
     * Достаем массив ids закрепённых брендов
     */
    public function getAllRelatedBrandsIds()
    {
        $allBrandsRelations = $this->relationsEntity->find([
            'limit' => $this->relationsEntity->count(),
            'entity_type' => 'brand'
        ]);

        $relatedBrandsIds = [];
        foreach ($allBrandsRelations as $brandRelation) {
            $relatedBrandsIds[$brandRelation->feed_id][] = $brandRelation->entity_id;
        }

        return ExtenderFacade::execute(__METHOD__, $relatedBrandsIds, func_get_args());
    }

    /**
     * @return array
     * Достаем массив закрепённых продуктов
     * @throws \Exception
     */
    public function getAllRelatedProducts()
    {
        $allRelatedProductsRelations = $this->relationsEntity->find([
            'limit' => $this->relationsEntity->count(),
            'entity_type' => 'product',
            'include' => 1
        ]);

        $relatedProductsIds = [];
        foreach ($allRelatedProductsRelations as $relation) {
            $relatedProductsIds[] = $relation->entity_id;
        }

        $products = $this->productsHelper->getList(['id' => $relatedProductsIds]);

        $relatedProducts = [];
        foreach ($allRelatedProductsRelations as $relation) {
            $relatedProducts[$relation->feed_id][] = $products[$relation->entity_id];
        }

        return ExtenderFacade::execute(__METHOD__, $relatedProducts, func_get_args());

    }

    /**
     * @return array
     * Достаем массив закрепённых продуктов не для выгрузки
     * @throws \Exception
     */
    public function getAllNotRelatedProducts()
    {
        $allNotRelatedProductsRelations = $this->relationsEntity->find([
            'limit' => $this->relationsEntity->count(),
            'entity_type' => 'product',
            'include' => 0
        ]);

        $notRelatedProductsIds = [];
        foreach ($allNotRelatedProductsRelations as $relation) {
            $notRelatedProductsIds[] = $relation->entity_id;
        }

        $products = $this->productsHelper->getList(['id' => $notRelatedProductsIds]);

        $notRelatedProducts = [];
        foreach ($allNotRelatedProductsRelations as $relation) {
            $notRelatedProducts[$relation->feed_id][] = $products[$relation->entity_id];
        }

        return ExtenderFacade::execute(__METHOD__, $notRelatedProducts, func_get_args());
    }
}
```

### File: Okay/Modules/OkayCMS/Rozetka/Helpers/RozetkaHelper.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka\Helpers;


use Okay\Core\EntityFactory;
use Okay\Core\Image;
use Okay\Core\Languages;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\Money;
use Okay\Core\QueryFactory;
use Okay\Core\QueryFactory\Select;
use Okay\Core\Router;
use Okay\Core\Routes\ProductRoute;
use Okay\Core\Settings;
use Okay\Entities\BrandsEntity;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Entities\RouterCacheEntity;
use Okay\Entities\VariantsEntity;
use Okay\Helpers\XmlFeedHelper;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaRelationsEntity;

class RozetkaHelper
{

    /** @var Image */
    private $image;

    /** @var Money */
    private $money;

    /** @var Settings */
    private $settings;

    /** @var QueryFactory */
    private $queryFactory;

    /** @var Languages */
    private $languages;

    /** @var XmlFeedHelper */
    private $feedHelper;


    private $mainCurrency;

    private $allCurrencies;
    
    public function __construct(
        Image         $image,
        Money         $money,
        Settings      $settings,
        QueryFactory  $queryFactory,
        Languages     $languages,
        EntityFactory $entityFactory,
        XmlFeedHelper $feedHelper
    ) {
        $this->image        = $image;
        $this->money        = $money;
        $this->settings     = $settings;
        $this->queryFactory = $queryFactory;
        $this->languages    = $languages;
        $this->feedHelper   = $feedHelper;

        /** @var CurrenciesEntity $currenciesEntity */
        $currenciesEntity = $entityFactory->get(CurrenciesEntity::class);

        $this->mainCurrency  = $currenciesEntity->getMainCurrency();
        $this->allCurrencies = $currenciesEntity->mappedBy('id')->find();
    }

    /**
     * Метод возвращает итоговый запрос, который достаёт все товары с изображениями с свойствами.
     * По результатам замеров, лучшие результаты производительности достигаются при джоине ленговых таблиц 
     * после фильтрации.
     * Фильтрация результатов и группировка свойств с изображениями вынесена в подзапрос, 
     * который формируется методом getSubSelect()
     *
     * @param string|integer $feedId
     * @param array $uploadCategories
     * @return Select
     */
    public function getQuery($feedId, $uploadCategories = []) : Select
    {
        $subSelect = $this->getSubSelect($feedId, $uploadCategories);
        if ($this->settings->get('use_full_description_in_upload_rozetka')) {
            $descriptionField = 'lp.description';
        } else {
            $descriptionField = 'lp.annotation';
        }
        
        $sql = $this->queryFactory->newSelect();
        $sql->cols([
            't.*',
            'lp.name as product_name',
            'lv.name as variant_name',
            'lb.name as brand_name',
            $descriptionField . ' AS description',
        ])->fromSubSelect($subSelect, 't')
            ->leftJoin(ProductsEntity::getLangTable().' AS lp', 'lp.product_id = t.product_id and lp.lang_id=' . $this->languages->getLangId())
            ->leftJoin(VariantsEntity::getLangTable().' AS lv', 'lv.variant_id = t.variant_id and lv.lang_id=' . $this->languages->getLangId())
            ->leftJoin(BrandsEntity::getLangTable().' AS lb', 'lb.brand_id = t.brand_id and lb.lang_id=' . $this->languages->getLangId());
        
        return ExtenderFacade::execute(__METHOD__, $sql, func_get_args());
    }

    /**
     * Метод возвращает подзапрос, который фильтрует и сортирует результаты, здесь достаются только не мультиязычные 
     * данные, кроме свойств. Свойства нужно доставать здесь, т.к. их группируем через GROUP_CONCAT()
     *
     * @param string|integer $feedId
     * @param array $uploadCategories
     * @return Select
     */
    private function getSubSelect($feedId, $uploadCategories = []) : Select
    {
        $sql = $this->queryFactory->newSelect();

        $categoryFilter = '';
        if (!empty($uploadCategories)) {
            $categoryFilter = "OR p.id IN (SELECT product_id FROM __products_categories WHERE category_id IN (:category_id))";
            $sql->bindValue('category_id', (array)$uploadCategories);
        }

        $sql->cols([
            'v.stock',
            'v.price',
            'v.compare_price',
            'v.sku',
            'v.currency_id',
            'v.id AS variant_id',
            'p.id AS product_id',
            'p.url',
            'r.slug_url',
            'p.main_category_id',
            'p.brand_id',
        ])  ->from(VariantsEntity::getTable() . ' AS v')
            ->leftJoin(ProductsEntity::getTable().' AS  p', 'v.product_id=p.id')
            ->leftJoin(RouterCacheEntity::getTable().' AS r', 'r.url = p.url AND r.type="product"')
            ->where('p.visible')
            ->where("p.id NOT IN (SELECT entity_id FROM " . RozetkaRelationsEntity::getTable() . " WHERE feed_id = :feed_id AND entity_type = 'product' AND include = 0)")
            ->where("(p.id IN (SELECT entity_id FROM " . RozetkaRelationsEntity::getTable() . " WHERE feed_id = :feed_id AND entity_type = 'product' AND include = 1) OR
                           p.brand_id IN (SELECT entity_id FROM " . RozetkaRelationsEntity::getTable() . " WHERE feed_id = :feed_id AND entity_type = 'brand')
                           {$categoryFilter})")
            ->bindValue('feed_id', $feedId)
            ->groupBy(['v.id'])
            ->orderBy(['p.position DESC']);

        if (!$this->settings->get('okaycms__rozetka_xml__upload_without_images')) {
            $sql->where('p.main_image_id != \'\' AND p.main_image_id IS NOT NULL');
        }
        
        if ($this->settings->get('upload_only_available_to_rozetka')) {
            $sql->where('(v.stock >0 OR v.stock is NULL)');
        }

        // Чтобы не писать запрос на группировку свойств и изображений, который может стать невалидным, используем
        // feedHelper чтобы он добавил этот запрос
        $sql = $this->feedHelper->joinImages($sql);
        $sql = $this->feedHelper->joinFeatures($sql);
        
        return ExtenderFacade::execute(__METHOD__, $sql, func_get_args());
    }

    /**
     * Формируем описание офера в виде массива
     * 
     * @param object $product строка выборки из базы (запрос формирующийся методом getQuery),
     * но после отработки методов attachFeatures и attachImages.
     * @param bool $addVariantUrl Если true будет добавлен урл на определенный вариант
     * @return array
     * @throws \Exception
     */
    public function getItem($product, $addVariantUrl = false) : array
    {
        // Указываем связку урла товара и его slug
        ProductRoute::setUrlSlugAlias($product->url, $product->slug_url);
        if ($addVariantUrl) {
            $result['url']['data'] = Router::generateUrl('product', ['url' => $product->url, 'variantId' => $product->variant_id], true);
        } else {
            $result['url']['data'] = Router::generateUrl('product', ['url' => $product->url], true);
        }
        
        $result['name']['data'] = $this->feedHelper->escape($product->product_name . (!empty($product->variant_name) ? ' ' . $product->variant_name : ''));

        $price = $product->price;
        $comparePrice = $product->compare_price;
        if (isset($this->allCurrencies[$product->currency_id])) {
            // Переводим в основную валюту сайта
            $variantCurrency = $this->allCurrencies[$product->currency_id];
            if (!empty($product->currency_id) && $variantCurrency->rate_from != $variantCurrency->rate_to) {
                $price = round($product->price * $variantCurrency->rate_to / $variantCurrency->rate_from, 2);
                if (!empty($product->compare_price)) {
                    $comparePrice = round($product->compare_price * $variantCurrency->rate_to / $variantCurrency->rate_from, 2);
                }
            }
        }
        
        $result['price']['data'] = $this->money->convert($price, $this->mainCurrency->id, false);
        if ($product->compare_price > 0) {
            $comparePrice = $this->money->convert($comparePrice, $this->mainCurrency->id, false);
            $result['oldprice']['data'] = $comparePrice;
        }

        $result['currencyId']['data'] = $this->mainCurrency->code;
        $result['categoryId']['data'] = $product->main_category_id;

        if (!empty($product->images)) {
            $iNum = 0;
            foreach ($product->images as $imageFilename) {
                $i['tag'] = 'picture';
                $i['data'] = $this->image->getResizeModifier($imageFilename, 800, 600);
                $result[] = $i;
                if ($iNum++ == 15) {
                    break;
                }
            }
        }
        
        $result['stock_quantity']['data'] = $product->stock;
        $result['delivery']['data'] = 'true';
        
        if (!empty($product->brand_name)) {
            $result['vendor']['data'] = $this->feedHelper->escape($product->brand_name);
        }
        
        if (!empty($product->sku)) {
            $result['vendorCode']['data'] = $this->feedHelper->escape($product->sku);
        }
        
        if (!empty($product->description)) {
            $result['description']['data'] = $this->feedHelper->escape($product->description);
        }

        if (!empty($product->features)) {
            foreach ($product->features as $feature) {
                foreach ($feature['values'] as $value) {
                    $result[] = [
                        'data' => $this->feedHelper->escape($value),
                        'tag' => 'param',
                        'attributes' => [
                            'name' => $this->feedHelper->escape($feature['name']),
                        ],
                    ];
                }
            }
        }

        return ExtenderFacade::execute(__METHOD__, $result, func_get_args());
    }
}
```

### File: Okay/Modules/OkayCMS/Rozetka/Backend/lang/en.php

```php
<?php

$lang['left_rezetka_xml'] = 'Upload (Rozetka)';
$lang['rezetka_xml_products'] = 'Products for upload';
$lang['rezetka_xml_products_remove'] = 'Products not for upload';
$lang['rozetka_xml'] = 'Upload (Rozetka)';
$lang['okaycms__rozetka_xml__select_all'] = 'Select All';
$lang['okaycms__rozetka_xml__categories'] = 'Categories';
$lang['okaycms__rozetka_xml__brands'] = 'Brands';
$lang['okaycms__rozetka_xml__select_none'] = 'Cancel All';
$lang['okaycms__rozetka_xml__generation_url'] = 'Url for access to upload';
$lang['okaycms__rozetka_xml__upload_products'] = 'Selection of goods for upload';
$lang['upload_non_exists_products_to_rozetka'] = 'Unload only items in stock';
$lang['use_full_description_to_rozetka'] = 'Use full description';
$lang['okaycms__rozetka_xml__products_for_upload'] = 'Products for upload';
$lang['okaycms__rozetka_xml__products_not_for_upload'] = 'Products not for upload';
$lang['okaycms__rozetka_xml__select_products'] = 'Select product';
$lang['okaycms__rozetka_xml__add_products'] = 'Add product';
$lang['okaycms__rozetka_xml__params'] = 'Upload Settings';
$lang['okaycms__rozetka_xml__import_field'] = 'Rozetka';
$lang['okaycms__rozetka_xml__edit_and_add_feeds'] = 'Editing and creating uploads';
$lang['okaycms__rozetka_xml__add_feed'] = 'Add upload';
$lang['okaycms__rozetka_xml__remove_feed'] = 'Remove upload';
$lang['okaycms__rozetka_xml__error_url_exist'] = 'An upload with the same URL already exists';
$lang['okaycms__rozetka_xml__error_url_cyrillic'] = 'The URL should contain only Latin letters and numbers';
$lang['okaycms__rozetka_xml__company'] = 'Company';
$lang['okaycms__rozetka_xml__upload_without_images'] = 'Unload goods without images';

```

### File: Okay/Modules/OkayCMS/Rozetka/Backend/lang/ge.php

```php
<?php

$lang['left_rezetka_xml'] = 'Upload (Rozetka)';
$lang['rezetka_xml_products'] = 'Products for upload';
$lang['rezetka_xml_products_remove'] = 'Products not for upload';
$lang['rozetka_xml'] = 'Upload (Rozetka)';
$lang['okaycms__rozetka_xml__select_all'] = 'Select All';
$lang['okaycms__rozetka_xml__categories'] = 'Categories';
$lang['okaycms__rozetka_xml__brands'] = 'Brands';
$lang['okaycms__rozetka_xml__select_none'] = 'Cancel All';
$lang['okaycms__rozetka_xml__generation_url'] = 'Url for access to upload';
$lang['okaycms__rozetka_xml__upload_products'] = 'Selection of goods for upload';
$lang['upload_non_exists_products_to_rozetka'] = 'Unload only items in stock';
$lang['use_full_description_to_rozetka'] = 'Use full description';
$lang['okaycms__rozetka_xml__products_for_upload'] = 'Products for upload';
$lang['okaycms__rozetka_xml__products_not_for_upload'] = 'Products not for upload';
$lang['okaycms__rozetka_xml__select_products'] = 'Select product';
$lang['okaycms__rozetka_xml__add_products'] = 'Add product';
$lang['okaycms__rozetka_xml__params'] = 'Upload Settings';
$lang['okaycms__rozetka_xml__import_field'] = 'Rozetka';
$lang['okaycms__rozetka_xml__edit_and_add_feeds'] = 'Editing and creating uploads';
$lang['okaycms__rozetka_xml__add_feed'] = 'Add upload';
$lang['okaycms__rozetka_xml__remove_feed'] = 'Remove upload';
$lang['okaycms__rozetka_xml__error_url_exist'] = 'An upload with the same URL already exists';
$lang['okaycms__rozetka_xml__error_url_cyrillic'] = 'The URL should contain only Latin letters and numbers';
$lang['okaycms__rozetka_xml__company'] = 'Company';
$lang['okaycms__rozetka_xml__upload_without_images'] = 'Unload goods without images';

```

### File: Okay/Modules/OkayCMS/Rozetka/Backend/lang/ru.php

```php
<?php

$lang['left_rezetka_xml'] = 'Выгрузка (Rozetka)';
$lang['rezetka_xml_products'] = 'Товары для выгрузки';
$lang['rezetka_xml_products_remove'] = 'Товары которые не нужно выгружать';
$lang['rozetka_xml'] = 'Выгрузка (Rozetka)';
$lang['okaycms__rozetka_xml__select_all'] = 'Выбрать все';
$lang['okaycms__rozetka_xml__categories'] = 'Категории';
$lang['okaycms__rozetka_xml__brands'] = 'Бренды';
$lang['okaycms__rozetka_xml__select_none'] = 'Отменить все';
$lang['okaycms__rozetka_xml__generation_url'] = 'Ссылка по которой доступен файл выгрузки';
$lang['okaycms__rozetka_xml__upload_products'] = 'Выбор товаров для выгрузки';
$lang['upload_non_exists_products_to_rozetka'] = 'Выгружать только товары в наличии';
$lang['use_full_description_to_rozetka'] = 'Использовать полное описание';
$lang['okaycms__rozetka_xml__products_for_upload'] = 'Товары для выгрузки';
$lang['okaycms__rozetka_xml__products_not_for_upload'] = 'Товары не для выгрузки';
$lang['okaycms__rozetka_xml__select_products'] = 'Выбрать товар';
$lang['okaycms__rozetka_xml__add_products'] = 'Добавить товар';
$lang['okaycms__rozetka_xml__params'] = 'Настройки выгрузки';
$lang['okaycms__rozetka_xml__import_field'] = 'Rozetka';
$lang['okaycms__rozetka_xml__edit_and_add_feeds'] = 'Редактирование и создание выгрузок';
$lang['okaycms__rozetka_xml__add_feed'] = 'Добавить выгрузку';
$lang['okaycms__rozetka_xml__remove_feed'] = 'Удалить выгрузку';
$lang['okaycms__rozetka_xml__error_url_exist'] = 'Выгрузка с таким URL уже существует';
$lang['okaycms__rozetka_xml__error_url_cyrillic'] = 'В URL должны быть только латинские буквы и цифры';
$lang['okaycms__rozetka_xml__company'] = 'Полное наименование компании, владеющей магазином';
$lang['okaycms__rozetka_xml__upload_without_images'] = 'Выгружать товары без изображений';

```

### File: Okay/Modules/OkayCMS/Rozetka/Backend/lang/ua.php

```php
<?php

$lang['left_rezetka_xml'] = 'Вивантаження (Rozetka)';
$lang['rezetka_xml_products'] = 'Товари для вивантаження';
$lang['rezetka_xml_products_remove'] = 'Товари які не потрібно вивантажувати';
$lang['rozetka_xml'] = 'Вивантаження (Rozetka)';
$lang['okaycms__rozetka_xml__select_all'] = 'Вибрати все';
$lang['okaycms__rozetka_xml__categories'] = 'Категорії';
$lang['okaycms__rozetka_xml__brands'] = 'Бренди';
$lang['okaycms__rozetka_xml__select_none'] = 'Скасувати всі';
$lang['okaycms__rozetka_xml__generation_url'] = 'Посилання по якій доступний файл вивантаження';
$lang['okaycms__rozetka_xml__upload_products'] = 'Вибір товарів для вивантаження';
$lang['upload_non_exists_products_to_rozetka'] = 'Вивантажувати тільки товари в наявності';
$lang['use_full_description_to_rozetka'] = 'Використовувати повний опис';
$lang['okaycms__rozetka_xml__products_for_upload'] = 'Товари для вивантаження';
$lang['okaycms__rozetka_xml__products_not_for_upload'] = 'Товари не для вивантаження';
$lang['okaycms__rozetka_xml__select_products'] = 'Вибрати товар';
$lang['okaycms__rozetka_xml__add_products'] = 'Додати товар';
$lang['okaycms__rozetka_xml__params'] = 'Налаштування вивантаження';
$lang['okaycms__rozetka_xml__import_field'] = 'Rozetka';
$lang['okaycms__rozetka_xml__edit_and_add_feeds'] = 'Редагування та створення вивантажень';
$lang['okaycms__rozetka_xml__add_feed'] = 'Додати вивантаження';
$lang['okaycms__rozetka_xml__remove_feed'] = 'Видалити вивантаження';
$lang['okaycms__rozetka_xml__error_url_exist'] = 'Вивантаження з таким URL вже існує';
$lang['okaycms__rozetka_xml__error_url_cyrillic'] = 'В URL повинні бути тільки латинські букви і цифри';
$lang['okaycms__rozetka_xml__company'] = 'Повне найменування компанії, що володіє магазином';
$lang['okaycms__rozetka_xml__upload_without_images'] = 'Вивантажувати товари без зображень';
```

### File: Okay/Modules/OkayCMS/Rozetka/Backend/design/html/import_fields_association.tpl

```smarty
{foreach $rozetkaFeeds as $feed}
    <option value="{Okay\Modules\OkayCMS\Rozetka\Init\Init::TO_FEED_FIELD}@{$feed->id}" data-label="{$btr->getTranslation('okaycms__rozetka_xml__import_field')} {$feed@iteration}.{$feed->name|escape}">
        {$btr->getTranslation('okaycms__rozetka_xml__import_field')} {$feed@iteration}.{$feed->name|escape}
    </option>
{/foreach}
```

### File: Okay/Modules/OkayCMS/Rozetka/Backend/design/html/rozetka_xml.tpl

```smarty
{$meta_title = $btr->rozetka_xml|escape scope=global}

{*Название страницы*}
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">
                {$btr->rozetka_xml|escape}
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
                    {if $message_success == 'saved'}
                    {$btr->general_settings_saved|escape}
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
            <div class="boxed boxed_warning">
                <div class="heading_box">
                    {if $message_error=='empty_name'}
                        {$btr->general_enter_title|escape}
                    {else}
                        {$message_error|escape}
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/if}

{*Главная форма страницы*}
<form method="post" enctype="multipart/form-data" class="fn_fast_button fn_is_translit_alpha">
    <input type=hidden name="session_id" value="{$smarty.session.id}">
    <input type="hidden" name="lang_id" value="{$lang_id}" />


    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="boxed fn_toggle_wrap">
                <div class="heading_box">
                    {$btr->okaycms__rozetka_xml__params|escape}
                    <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                        <a class="btn-minimize" href="javascript:;" ><i class="fa fn_icon_arrow fa-angle-down"></i></a>
                    </div>
                </div>
                <div class="toggle_body_wrap on fn_card">
                    <div class="permission_block">
                        <div class="permission_boxes row">
                            <div class="col-xl-12 col-lg-12 col-md-12">
                                <div class="permission_box permission_box--long">
                                    <span>{$btr->okaycms__rozetka_xml__upload_without_images|escape}</span>
                                    <label class="switch switch-default">
                                        <input class="switch-input" name="okaycms__rozetka_xml__upload_without_images" value='1' type="checkbox" {if $settings->okaycms__rozetka_xml__upload_without_images}checked=""{/if}/>
                                        <span class="switch-label"></span>
                                        <span class="switch-handle"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12">
                                <div class="permission_box permission_box--long">
                                    <span class="permission_box__label">{$btr->upload_non_exists_products_to_rozetka|escape}</span>
                                    <label class="switch switch-default">
                                        <input class="switch-input" name="upload_non_available" value='1' type="checkbox" id="visible_checkbox" {if $settings->upload_only_available_to_rozetka}checked=""{/if}/>
                                        <span class="switch-label"></span>
                                        <span class="switch-handle"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="col-xl-12 col-lg-12 col-md-12">
                                <div class="permission_box permission_box--long">
                                    <span class="permission_box__label">{$btr->use_full_description_to_rozetka|escape}</span>
                                    <label class="switch switch-default">
                                        <input class="switch-input" name="full_description" value="1" type="checkbox" id="featured_checkbox" {if $settings->use_full_description_in_upload_rozetka}checked=""{/if}/>
                                        <span class="switch-label"></span>
                                        <span class="switch-handle"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <div class="heading_label">
                                <strong>{$btr->okaycms__rozetka_xml__company}</strong>
                            </div>
                            <div class="mb-1">
                                <input class="form-control" type="text" name="okaycms__rozetka_xml__company" value="{$settings->okaycms__rozetka_xml__company|escape}" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-12 col-md-12 ">
                            <button type="submit" class="btn btn_small btn_blue float-md-right">
                                <span>{$btr->general_apply|escape}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {*Параметры элемента*}
    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="boxed fn_toggle_wrap">
                <div class="heading_box">
                    {$btr->okaycms__rozetka_xml__edit_and_add_feeds|escape}
                </div>

                {*Параметры элемента*}
                <div class="row">
                    <div class="col-md-12">
                        <button class="btn btn-info btn_big mb-1 fn_add_feed" type="submit" name="add_feed" value="1">
                            <span>{$btr->okaycms__rozetka_xml__add_feed}</span>
                        </button>
                    </div>
                    <div class="col-md-12">
                        <div class="tabs">
                            <div class="heading_tabs">
                                <div class="tab_navigation">
                                    {foreach $feeds as $feed}
                                        <a href="#tab{$feed@iteration}" class="heading_box tab_navigation_link">{$feed->name|escape}</a>
                                    {/foreach}
                                </div>
                            </div>
                            <div class="tab_container">
                                {foreach $feeds as $feed}
                                    <div id="tab{$feed@iteration}" class="tab">
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12">
                                                <div class="heading_box">
                                                    {$btr->okaycms__rozetka_xml__params|escape}
                                                </div>
                                                {*Вывод ошибок*}
                                                {if isset($errors['feeds'][$feed->id])}
                                                    <div class="row">
                                                        <div class="col-lg-12 col-md-12 col-sm-12">
                                                            <div class="alert alert--center alert--icon alert--error">
                                                                <div class="alert__content">
                                                                    <div class="alert__title">
                                                                        {if isset($errors['feeds'][$feed->id]['url'])}
                                                                            {$btr->okaycms__rozetka_xml__error_url_exist|escape}
                                                                        {elseif isset($errors['feeds'][$feed->id]['url_cyrillic'])}
                                                                            {$btr->okaycms__rozetka_xml__error_url_cyrillic|escape}
                                                                        {/if}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                {/if}
                                                {if $feeds|count > 1}
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <button class="btn btn-outline-danger btn_big float-md-right" name="remove_feed" value="{$feed->id}">
                                                                <span>{$btr->okaycms__rozetka_xml__remove_feed}</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                {/if}
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <div class="heading_label">
                                                                <span>Name</span>
                                                            </div>
                                                            <input class="form-control" type="text" placeholder="Feed name" name="feeds[{$feed->id}][name]" value="{$feed->name|escape}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="activity_of_switch activity_of_switch--left">
                                                            <div class="activity_of_switch_item">
                                                                <div class="okay_switch clearfix">
                                                                    <label class="switch_label">{$btr->general_enable|escape}</label>
                                                                    <label class="switch switch-default">
                                                                        <input type="hidden" name="feeds[{$feed->id}][enabled]" value="0">
                                                                        <input class="switch-input" name="feeds[{$feed->id}][enabled]" value="1" type="checkbox" {if $feed->enabled}checked{/if}>
                                                                        <span class="switch-label"></span>
                                                                        <span class="switch-handle"></span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <div class="heading_label">
                                                                <span>URL</span>
                                                                <i class="fn_tooltips" title="{$btr->okaycms__rozetka_xml__error_url_cyrillic|escape}">
                                                                <svg width="20px" height="20px" viewBox="0 0 438.533 438.533" ><path fill="currentColor" d="M409.133,109.203c-19.608-33.592-46.205-60.189-79.798-79.796C295.736,9.801,259.058,0,219.273,0c-39.781,0-76.47,9.801-110.063,29.407c-33.595,19.604-60.192,46.201-79.8,79.796C9.801,142.8,0,179.489,0,219.267c0,39.78,9.804,76.463,29.407,110.062c19.607,33.592,46.204,60.189,79.799,79.798c33.597,19.605,70.283,29.407,110.063,29.407s76.47-9.802,110.065-29.407c33.593-19.602,60.189-46.206,79.795-79.798c19.603-33.596,29.403-70.284,29.403-110.062C438.533,179.485,428.732,142.795,409.133,109.203z M255.82,356.309c0,2.662-0.862,4.853-2.573,6.563c-1.704,1.711-3.895,2.567-6.557,2.567h-54.823c-2.664,0-4.854-0.856-6.567-2.567c-1.714-1.711-2.57-3.901-2.57-6.563v-54.823c0-2.662,0.855-4.853,2.57-6.563c1.713-1.708,3.903-2.563,6.567-2.563h54.823c2.662,0,4.853,0.855,6.557,2.563c1.711,1.711,2.573,3.901,2.573,6.563V356.309z M325.338,187.574c-2.382,7.043-5.044,12.804-7.994,17.275c-2.949,4.473-7.187,9.042-12.709,13.703c-5.51,4.663-9.891,7.996-13.135,9.998c-3.23,1.995-7.898,4.713-13.982,8.135c-6.283,3.613-11.465,8.326-15.555,14.134c-4.093,5.804-6.139,10.513-6.139,14.126c0,2.67-0.862,4.859-2.574,6.571c-1.707,1.711-3.897,2.566-6.56,2.566h-54.82c-2.664,0-4.854-0.855-6.567-2.566c-1.715-1.712-2.568-3.901-2.568-6.571v-10.279c0-12.752,4.993-24.701,14.987-35.832c9.994-11.136,20.986-19.368,32.979-24.698c9.13-4.186,15.604-8.47,19.41-12.847c3.812-4.377,5.715-10.188,5.715-17.417c0-6.283-3.572-11.897-10.711-16.849c-7.139-4.947-15.27-7.421-24.409-7.421c-9.9,0-18.082,2.285-24.555,6.855c-6.283,4.565-14.465,13.322-24.554,26.263c-1.713,2.286-4.093,3.431-7.139,3.431c-2.284,0-4.093-0.57-5.424-1.709L121.35,145.89c-4.377-3.427-5.138-7.422-2.286-11.991c24.366-40.542,59.672-60.813,105.922-60.813c16.563,0,32.744,3.903,48.541,11.708c15.796,7.801,28.979,18.842,39.546,33.119c10.554,14.272,15.845,29.787,15.845,46.537C328.904,172.824,327.71,180.529,325.338,187.574z"/></svg>    </i>
                                                            </div>
                                                            <div class="input-group input-group--dabbl">
                                                                <span class="input-group-addon input-group-addon--left">URL</span>
                                                                <input class="form-control fn_url fn_disabled" type="text" name=feeds[{$feed->id}][url] value="{$feed->url|escape}" readonly="readonly">
                                                                <span class="input-group-addon fn_disable_url"><i class="fa fa-lock"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="alert alert--icon alert--info">
                                                            <div class="alert__content">
                                                                <div class="alert__title">{$btr->alert_info|escape}</div>
                                                                <p>{$btr->okaycms__rozetka_xml__generation_url|escape} <a href="{url_generator route='OkayCMS_Rozetka_feed' url=$feed->url absolute=1}" target="_blank">{url_generator route='OkayCMS_Rozetka_feed' url=$feed->url absolute=1}</a></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="heading_box">
                                                    {$btr->okaycms__rozetka_xml__upload_products|escape}
                                                </div>
                                                <div class="row">
                                                    {* Категории для выгрузки *}
                                                    <div class="col-lg-6 col-md-6">
                                                        <div class="boxed match fn_toggle_wrap">
                                                            <div class="heading_box">
                                                                {$btr->okaycms__rozetka_xml__categories}
                                                                <button class="btn btn_small btn-info" name="add_all_categories" value="{$feed->id}">{$btr->okaycms__rozetka_xml__select_all}</button>
                                                                <button class="btn btn_small" name="remove_all_categories" value="{$feed->id}">{$btr->okaycms__rozetka_xml__select_none}</button>
                                                            </div>
                                                            <div class="toggle_body_wrap on fn_card">
                                                                <select style="opacity: 0;" class="selectpicker_categories col-xs-12 px-0" multiple name="related_categories[{$feed->id}][]" size="10" data-selected-text-format="count" >
                                                                    {function name=category_select selected_id=$product_category level=0}
                                                                        {foreach $categories as $category}
                                                                            <option value='{$category->id}' class="category_to_xml" {if (isset($allRelatedCategoriesIds[$feed->id]) && in_array($category->id, $allRelatedCategoriesIds[$feed->id]))}selected{/if}>{section name=sp loop=$level}&nbsp;&nbsp;&nbsp;&nbsp;{/section}{$category->name|escape}</option>
                                                                            {category_select categories=$category->subcategories selected_id=$selected_id  level=$level+1}
                                                                        {/foreach}
                                                                    {/function}
                                                                    {category_select categories=$categories}
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {* Бренды для выгрузки *}
                                                    <div class="col-lg-6 col-md-6">
                                                        <div class="boxed match fn_toggle_wrap">
                                                            <div class="heading_box">
                                                                {$btr->okaycms__rozetka_xml__brands}
                                                                <button class="btn btn_small btn-info" name="add_all_brands" value="{$feed->id}">{$btr->okaycms__rozetka_xml__select_all}</button>
                                                                <button class="btn btn_small" name="remove_all_brands" value="{$feed->id}">{$btr->okaycms__rozetka_xml__select_none}</button>
                                                            </div>
                                                            <div class="toggle_body_wrap on fn_card">
                                                                <select style="opacity: 0;" class="selectpicker_brands col-xs-12 px-0" multiple name="related_brands[{$feed->id}][]" size="10" data-selected-text-format="count" >
                                                                    {foreach $brands as $brand}
                                                                        <option value='{$brand->id}' class="brand_to_xml" {if (isset($allRelatedBrandsIds[$feed->id]) && in_array($brand->id, $allRelatedBrandsIds[$feed->id]))}selected{/if}>{$brand->name|escape}</option>
                                                                    {/foreach}
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {* Товары для выгрузки *}
                                                    <div class="col-lg-6 col-md-12">
                                                        <div class="boxed fn_toggle_wrap min_height_210px">
                                                            {backend_compact_product_list
                                                            title=$btr->okaycms__rozetka_xml__products_for_upload
                                                            name="related_products_{$feed->id}"
                                                            products=$related_products[$feed->id]
                                                            label=$btr->okaycms__rozetka_xml__add_products
                                                            placeholder=$btr->okaycms__rozetka_xml__select_products
                                                            }
                                                        </div>
                                                    </div>

                                                    {* Товары не для выгрузки *}
                                                    <div class="col-lg-6 col-md-12">
                                                        <div class="boxed fn_toggle_wrap min_height_210px">
                                                            {backend_compact_product_list
                                                            title=$btr->okaycms__rozetka_xml__products_not_for_upload
                                                            name="not_related_products_{$feed->id}"
                                                            products=$not_related_products[$feed->id]
                                                            label=$btr->okaycms__rozetka_xml__add_products
                                                            placeholder=$btr->okaycms__rozetka_xml__select_products
                                                            }
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" style="margin-bottom: 200px;">
        <div class="col-lg-12 col-md-12 ">
            <button type="submit" class="btn btn_small btn_blue float-md-right">
                {*{include file='svg_icon.tpl' svgId='checked'}*}
                <span>{$btr->general_apply|escape}</span>
            </button>
        </div>
    </div>
</form>

{literal}
    <script>
        $('.selectpicker_categories').selectpicker();
        $('.selectpicker_brands').selectpicker();
    </script>
{/literal}
```

### File: Okay/Modules/OkayCMS/Rozetka/Backend/Controllers/RozetkaXmlAdmin.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka\Backend\Controllers;


use Okay\Admin\Controllers\IndexAdmin;
use Okay\Entities\BrandsEntity;
use Okay\Entities\CategoriesEntity;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaFeedsEntity;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaRelationsEntity;
use Okay\Modules\OkayCMS\Rozetka\Helpers\BackendRozetkaHelper;

class RozetkaXmlAdmin extends IndexAdmin
{

    public function fetch(
        CategoriesEntity       $categoriesEntity,
        BrandsEntity           $brandsEntity,
        BackendRozetkaHelper   $backendRozetkaHelper,
        RozetkaRelationsEntity $relationsEntity,
        RozetkaFeedsEntity     $feedsEntity
    ) {
        if ($this->request->method('post')) {
            $postFeeds = $this->request->post('feeds');

            if ($errors = $backendRozetkaHelper->validateFeeds($postFeeds)) {
                $this->design->assign('errors', $errors);
            } else {

                $this->settings->update('okaycms__rozetka_xml__company', $this->request->post('okaycms__rozetka_xml__company'));
                
                $postRelatedCategories = $this->request->post('related_categories');
                $postRelatedBrands = $this->request->post('related_brands');

                $backendRozetkaHelper->updateFeeds($postFeeds);
                $backendRozetkaHelper->updateRelatedCategories($postRelatedCategories);
                $backendRozetkaHelper->updateRelatedBrands($postRelatedBrands);
                $backendRozetkaHelper->updateRelatedProducts();
                $backendRozetkaHelper->updateNotRelatedProducts();

                if ($this->request->post('add_feed')) {
                    $backendRozetkaHelper->addFeed();
                } else if ($feedId = $this->request->post('remove_feed')) {
                    $backendRozetkaHelper->removeFeed($feedId);
                } else if ($feedId = $this->request->post('add_all_categories')) {
                    $backendRozetkaHelper->addAllCategories($feedId);
                } else if($feedId = $this->request->post('remove_all_categories')) {
                    $relationsEntity->removeAllCategoriesByFeedId($feedId);
                } else if ($feedId = $this->request->post('add_all_brands')) {
                    $backendRozetkaHelper->addAllBrands($feedId);
                } else if($feedId = $this->request->post('remove_all_brands')) {
                    $relationsEntity->removeAllBrandsByFeedId($feedId);
                }

                $this->updateCheckboxes();
            }
        }

        $allFeeds                = $feedsEntity->find();
        $allCategories           = $categoriesEntity->getCategoriesTree();
        $allBrands               = $brandsEntity->find(['limit' => $brandsEntity->count()]);
        $allRelatedCategoriesIds = $backendRozetkaHelper->getAllRelatedCategoriesIds();
        $allRelatedBrandsIds     = $backendRozetkaHelper->getAllRelatedBrandsIds();
        $allRelatedProducts      = $backendRozetkaHelper->getAllRelatedProducts();
        $allNotRelatedProducts   = $backendRozetkaHelper->getAllNotRelatedProducts();

        $this->design->assign('feeds', $allFeeds);
        $this->design->assign('categories', $allCategories);
        $this->design->assign('brands', $allBrands);
        $this->design->assign('allRelatedCategoriesIds', $allRelatedCategoriesIds);
        $this->design->assign('allRelatedBrandsIds', $allRelatedBrandsIds);
        $this->design->assign('related_products', $allRelatedProducts);
        $this->design->assign('not_related_products', $allNotRelatedProducts);

        $this->response->setContent($this->design->fetch('rozetka_xml.tpl'));
    }

    private function updateCheckboxes()
    {

        $this->settings->set('upload_only_available_to_rozetka', $this->request->post('upload_non_available', 'integer'));
        $this->settings->set('use_full_description_in_upload_rozetka', $this->request->post('full_description', 'integer'));
        $this->settings->set('okaycms__rozetka_xml__upload_without_images', $this->request->post('okaycms__rozetka_xml__upload_without_images', 'integer'));
        
    }
    
}

```

### File: Okay/Modules/OkayCMS/Rozetka/Init/Init.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka\Init;


use Okay\Admin\Helpers\BackendExportHelper;
use Okay\Admin\Helpers\BackendImportHelper;
use Okay\Core\Design;
use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Entities\BrandsEntity;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaFeedsEntity;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaRelationsEntity;
use Okay\Modules\OkayCMS\Rozetka\Extenders\BackendExtender;

class Init extends AbstractInit
{
    const TO_FEED_FIELD = 'to__okaycms__rozetka';
    const FILTER_FEEDS  = 'okaycms__rozetka__feeds';
    const PERMISSION    = 'okaycms__rozetka';

    public function install()
    {
        $this->setModuleType(MODULE_TYPE_XML);
        $this->setBackendMainController('RozetkaXmlAdmin');

        $this->migrateEntityTable(RozetkaFeedsEntity::class, [
            (new EntityField('id'))->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('name'))->setTypeVarchar(100, false),
            (new EntityField('url'))->setTypeVarchar(100, false)->setIndexUnique(),
            (new EntityField('enabled'))->setTypeTinyInt(1, false)->setDefault(0),
        ]);

        $entityTypeField = (new EntityField('entity_type'))->setTypeEnum(['product', 'category', 'brand'], false);
        $includeField = (new EntityField('include'))->setTypeTinyInt(1, false);
        $entityIdField = (new EntityField('entity_id'))->setTypeInt(11, false);
        $this->migrateEntityTable(RozetkaRelationsEntity::class, [
            (new EntityField('id'))->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('feed_id'))->setTypeInt(11, false)->setIndexUnique(null, $entityTypeField, $includeField, $entityIdField),
            $entityIdField,
            $entityTypeField,
            $includeField,
        ]);
    }

    public function init()
    {
        $this->addPermission(self::PERMISSION);
        $this->registerBackendController('RozetkaXmlAdmin');
        $this->addBackendControllerPermission('RozetkaXmlAdmin', self::PERMISSION);

        $this->addBackendBlock('import_fields_association',
            'import_fields_association.tpl',
            function(
                RozetkaFeedsEntity $feedsEntity,
                Design             $design
            ) {
                $design->assign('rozetkaFeeds', $feedsEntity->find());
            }
        );

        $this->registerQueueExtension(
            [BackendImportHelper::class, 'importItem'],
            [BackendExtender::class, 'importItem']
        );

        $this->registerQueueExtension(
            [BackendImportHelper::class, 'parseProductData'],
            [BackendExtender::class, 'parseProductData']
        );

        $this->registerChainExtension(
            [BackendExportHelper::class, 'getColumnsNames'],
            [BackendExtender::class, 'extendExportColumnsNames']
        );

        $this->registerChainExtension(
            [BackendExportHelper::class, 'setUp'],
            [BackendExtender::class, 'extendFilter']
        );

        $this->registerChainExtension(
            [BackendImportHelper::class, 'getModulesColumnsNames'],
            [BackendExtender::class, 'getModulesColumnsNames']
        );

        $this->registerEntityFilter(
            ProductsEntity::class,
            self::FILTER_FEEDS,
            \Okay\Modules\OkayCMS\Rozetka\ExtendsEntities\ProductsEntity::class,
            self::FILTER_FEEDS
        );
    }
}
```

### File: Okay/Modules/OkayCMS/Rozetka/Init/module.json

```json
{
  "version": "1.0.0",
  "vendor": {
    "email": "info@okay-cms.com",
    "site": "https://okay-cms.com"
  }
}
```

### File: Okay/Modules/OkayCMS/Rozetka/Init/routes.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka;


return [
    'OkayCMS_Rozetka_feed' => [
        'slug' => 'rozetka/{$url}.xml',
        'patterns' => [
            '{$url}' => '([0-9A-z\-]+)?',
        ],
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\RozetkaController',
            'method' => 'render',
        ],
    ],
];
```

### File: Okay/Modules/OkayCMS/Rozetka/Init/services.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka;


use Okay\Core\Design;
use Okay\Core\EntityFactory;
use Okay\Core\Image;
use Okay\Core\Languages;
use Okay\Core\Money;
use Okay\Core\OkayContainer\Reference\ParameterReference as PR;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\QueryFactory;
use Okay\Core\Request;
use Okay\Core\Settings;
use Okay\Helpers\ProductsHelper;
use Okay\Helpers\XmlFeedHelper;
use Okay\Modules\OkayCMS\Rozetka\Extenders\BackendExtender;
use Okay\Modules\OkayCMS\Rozetka\Helpers\BackendRozetkaHelper;
use Okay\Modules\OkayCMS\Rozetka\Helpers\RozetkaHelper;

return [
    BackendExtender::class => [
        'class' => BackendExtender::class,
        'arguments' => [
            new SR(EntityFactory::class),
            new SR(Design::class)
        ],
    ],
    RozetkaHelper::class => [
        'class' => RozetkaHelper::class,
        'arguments' => [
            new SR(Image::class),
            new SR(Money::class),
            new SR(Settings::class),
            new SR(QueryFactory::class),
            new SR(Languages::class),
            new SR(EntityFactory::class),
            new SR(XmlFeedHelper::class),
        ],
    ],
    BackendRozetkaHelper::class => [
        'class' => BackendRozetkaHelper::class,
        'arguments' => [
            new SR(EntityFactory::class),
            new SR(QueryFactory::class),
            new SR(Request::class),
            new SR(ProductsHelper::class)
        ],
    ],
];
```

### File: Okay/Modules/OkayCMS/Rozetka/ExtendsEntities/ProductsEntity.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka\ExtendsEntities;


use Okay\Core\Modules\AbstractModuleEntityFilter;
use Okay\Core\QueryFactory;
use Okay\Core\ServiceLocator;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaFeedsEntity;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaRelationsEntity;
use Okay\Modules\OkayCMS\Rozetka\Init\Init;

class ProductsEntity extends AbstractModuleEntityFilter
{
    public function okaycms__rozetka__feeds($status, $filter)
    {
        if ($status) {
            /** @var ServiceLocator $SL */
            $SL = ServiceLocator::getInstance();

            /** @var QueryFactory $queryFactory */
            $queryFactory = $SL->getService(QueryFactory::class);

            $select = $queryFactory->newSelect();
            $select ->from(RozetkaFeedsEntity::getTable())
                ->cols(['*']);
            $feeds = $select->results();

            $cols = [];
            $i = 1;
            foreach ($feeds as $feed) {
                $tableName = Init::TO_FEED_FIELD . '_' . $i;

                $cols[] =
                    "CASE
                    WHEN {$tableName}.feed_id IS NULL
                        THEN 0
                    ELSE 1
                END AS " . $tableName;

                $subSelect = $queryFactory->newSelect();
                $subSelect  ->from(RozetkaRelationsEntity::getTable())
                    ->cols([
                        'feed_id',
                        'entity_id'
                    ])
                    ->where("feed_id = :feed_id_{$i} AND entity_type = 'product' AND include = 1");

                $this->select->joinSubSelect(
                    'LEFT',
                    $subSelect->getStatement(),
                    $tableName,
                    "{$tableName}.entity_id = p.id"
                );

                $this->select->bindValue("feed_id_{$i}", $feed->id);
                $i++;
            }
            $this->select->cols($cols);
        }
    }
}
```

### File: Okay/Modules/OkayCMS/Rozetka/Entities/RozetkaFeedsEntity.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka\Entities;


use Okay\Core\Entity\Entity;

class RozetkaFeedsEntity extends Entity
{
    protected static $fields = [
        'id',
        'name',
        'url',
        'enabled'
    ];

    protected static $table = 'okaycms__rozetka__feeds';

    protected static $tableAlias = 'rxf';

    public function delete($ids)
    {
        $ids = (array)$ids;

        $delete = $this->queryFactory->newDelete();
        $delete ->from(RozetkaRelationsEntity::getTable())
                ->where('feed_id IN (:ids)')
                ->bindValue('ids', $ids)
                ->execute();

        parent::delete($ids);
    }
}
```

### File: Okay/Modules/OkayCMS/Rozetka/Entities/RozetkaRelationsEntity.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka\Entities;


use Okay\Core\Entity\Entity;
use Okay\Core\Modules\Extender\ExtenderFacade;

class RozetkaRelationsEntity extends Entity
{
    protected static $fields = [
        'id',
        'feed_id',
        'entity_id',
        'entity_type',
        'include'
    ];

    protected static $table = 'okaycms__rozetka__relations';

    protected static $tableAlias = 'rxr';

    /**
     * Удаляем все категории
     */
    public function removeAllCategories()
    {
        $delete = $this->queryFactory->newDelete();
        $delete ->from($this->getTable())
                ->where("entity_type = 'category'")
                ->execute();

        return ExtenderFacade::execute([static::class, __FUNCTION__], null, func_get_args());

    }

    /**
     * @param string|integer $feedId
     * Удаляем все категории закреплённые за определенным фидом
     */
    public function removeAllCategoriesByFeedId($feedId)
    {
        $delete = $this->queryFactory->newDelete();
        $delete ->from($this->getTable())
                ->where("entity_type = 'category'")
                ->where('feed_id = :feed_id')
                ->bindValue('feed_id', $feedId)
                ->execute();

        return ExtenderFacade::execute([static::class, __FUNCTION__], null, func_get_args());
    }

    /**
     * Удаляем все бренды
     */
    public function removeAllBrands()
    {
        $delete = $this->queryFactory->newDelete();
        $delete ->from($this->getTable())
                ->where("entity_type = 'brand'")
                ->execute();

        return ExtenderFacade::execute([static::class, __FUNCTION__], null, func_get_args());
    }

    /**
     * @param string|integer $feedId
     * Удаляем все бренды закреплённые за определенным фидом
     */
    public function removeAllBrandsByFeedId($feedId)
    {
        $delete = $this->queryFactory->newDelete();
        $delete ->from($this->getTable())
                ->where("entity_type = 'brand'")
                ->where('feed_id = :feed_id')
                ->bindValue('feed_id', $feedId)
                ->execute();

        return ExtenderFacade::execute([static::class, __FUNCTION__], null, func_get_args());
    }

    /**
     * Удаляем все продукты
     */
    public function removeAllRelatedProducts()
    {
        $delete = $this->queryFactory->newDelete();
        $delete ->from($this->getTable())
                ->where("entity_type = 'product' AND include = 1")
                ->execute();

        return ExtenderFacade::execute([static::class, __FUNCTION__], null, func_get_args());
    }

    /**
     * Удаляем все закрепленные продукты не для выгрузки
     */
    public function removeAllNotRelatedProducts()
    {
        $delete = $this->queryFactory->newDelete();
        $delete ->from($this->getTable())
                ->where("entity_type = 'product' AND include = 0")
                ->execute();

        return ExtenderFacade::execute([static::class, __FUNCTION__], null, func_get_args());
    }

    /**
     * @param array $rows
     * Добавляем отношения
     */
    public function addRelations($rows)
    {
        if (!empty($rows)) {
            $insert = $this->queryFactory->newInsert();
            $insert ->into($this->getTable())
                    ->addRows($rows);
            $insert ->getStatement(); //Todo баг либы, только 1 или больше 2 записей
            $insert ->execute();
        }

        return ExtenderFacade::execute([static::class, __FUNCTION__], null, func_get_args());
    }
}
```

### File: Okay/Modules/OkayCMS/Rozetka/design/html/feed_footer.xml.tpl

```smarty
</offers>
{get_design_block block=OkayCMS_Rozetka_footer}
</shop>
</yml_catalog>
```

### File: Okay/Modules/OkayCMS/Rozetka/design/html/feed_head.xml.tpl

```smarty
<?xml version='1.0' encoding='UTF-8'?>
<!DOCTYPE yml_catalog SYSTEM 'shops.dtd'>
<yml_catalog date="{date('Y-m-d H:i')}">
    <shop>
        <name>{$settings->site_name|escape}</name>
        {if $settings->okaycms__rozetka_xml__company}
            <company>{$settings->okaycms__rozetka_xml__company|escape}</company>
        {else}
            <company>{$settings->site_name|escape}</company>
        {/if}
        <url>{$rootUrl}</url>
        <platform>OkayCMS</platform>
        <version>{$config->version|escape} {$config->version_type|escape}</version>
        <currencies>
            {foreach $currencies as $c}
                <currency id="{$c->code|escape}" rate="{$c->rate_to/$c->rate_from*$main_currency->rate_from/$main_currency->rate_to}"/>
            {/foreach}
        </currencies>

        <categories>
            {function name=categories_tree}
                {if $categories}
                    {foreach $categories as $c}
                        <category id="{$c->id}"{if $c->parent_id>0} parentId="{$c->parent_id|escape}"{/if}>{$c->name|escape}</category>
                        {if $c->subcategories && $c->count_children_visible && $level < 3}
                            {categories_tree categories=$c->subcategories}
                        {/if}
                    {/foreach}
                {/if}
            {/function}
            {categories_tree categories=$categories}
        </categories>
        {get_design_block block=OkayCMS_Rozetka_head}
            <offers>
                
```

### File: Okay/Modules/OkayCMS/Rozetka/Controllers/RozetkaController.php

```php
<?php


namespace Okay\Modules\OkayCMS\Rozetka\Controllers;


use Aura\Sql\ExtendedPdo;
use Okay\Controllers\AbstractController;
use Okay\Core\Money;
use Okay\Core\QueryFactory;
use Okay\Core\Router;
use Okay\Core\Routes\ProductRoute;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\CurrenciesEntity;
use Okay\Helpers\XmlFeedHelper;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaFeedsEntity;
use Okay\Modules\OkayCMS\Rozetka\Entities\RozetkaRelationsEntity;
use Okay\Modules\OkayCMS\Rozetka\Helpers\RozetkaHelper;
use PDO;

class RozetkaController extends AbstractController
{
    public function render(
        CategoriesEntity   $categoriesEntity,
        QueryFactory       $queryFactory,
        ExtendedPdo        $pdo,
        RozetkaHelper      $rozetkaHelper,
        XmlFeedHelper      $feedHelper,
        RozetkaFeedsEntity $feedsEntity,
        Money              $money,
        CurrenciesEntity   $currenciesEntity,
        $url
    ) {
        if (!($feed = $feedsEntity->findOne(['url' => $url])) || !$feed->enabled) {
            return false;
        }

        if ($currencies = $currenciesEntity->find()) {
            $this->design->assign('main_currency', reset($currencies));

            // Передаем валюты, чтобы класс потом не лез в базу за валютами, т.к. мы работаем с небуферизированными запросами
            foreach ($currencies as $c) {
                $money->setCurrency($c);
            }
        }

        $sql = $queryFactory->newSqlQuery();
        $sql->setStatement('SET SQL_BIG_SELECTS=1');
        $sql->execute();

        $select = $queryFactory->newSelect();
        $select ->from(RozetkaRelationsEntity::getTable())
                ->cols(['entity_id'])
                ->where("feed_id = :feed_id AND entity_type = 'category'")
                ->bindValue('feed_id', $feed->id);

        $categoriesToFeed = $select->results('entity_id');
        $uploadCategories = $feedHelper->addAllChildrenToList($categoriesToFeed);

        $this->design->assign('all_categories', $categoriesEntity->find());

        $this->response->setContentType(RESPONSE_XML);
        $this->response->sendHeaders();
        $this->response->sendStream($this->design->fetch('feed_head.xml.tpl'));
        
        // На всякий случай наполним кеш роутов
        Router::generateRouterCache();

        // Запрещаем выполнять запросы в БД во время генерации урла т.к. мы работаем с небуферизированными запросами
        ProductRoute::setNotUseSqlToGenerate();

        // Увеличиваем лимит ф-ции GROUP_CONCAT()
        $query = $queryFactory->newSqlQuery();
        $query->setStatement('SET SESSION group_concat_max_len = 1000000;')->execute();
        
        // Для экономии памяти работаем с небуферизированными запросами
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $query = $rozetkaHelper->getQuery($feed->id, $uploadCategories);

        $prevProductId = null;
        while ($product = $query->result()) {
            $product = $feedHelper->attachFeatures($product);
            $metaParts = $feedHelper->getMetadataParts($product);
            $product = $feedHelper->attachDescriptionByTemplate(
                $product,
                $metaParts,
                $feedHelper->getDescriptionTemplate($product),
                XmlFeedHelper::DESCRIPTION_FIELD
            );
            $product = $feedHelper->attachDescriptionByTemplate(
                $product,
                $metaParts,
                $feedHelper->getAnnotationTemplate($product),
                XmlFeedHelper::ANNOTATION_FIELD
            );
            $product = $feedHelper->attachProductImages($product);

            $addVariantUrl = false;
            if ($prevProductId === $product->product_id) {
                $addVariantUrl = true;
            }
            $prevProductId = $product->product_id;
            $item = $rozetkaHelper->getItem($product, $addVariantUrl);
            $xmlProduct = $feedHelper->compileItem($item, 'offer', [
                'id' => $product->variant_id,
                'available' => ($product->stock > 0 || $product->stock === null ? 'true' : 'false'),
            ]);

            $this->response->sendStream($xmlProduct);
        }

        $this->response->sendStream($this->design->fetch('feed_footer.xml.tpl'));
    }
}

```

