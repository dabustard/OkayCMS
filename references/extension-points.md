# Точки расширения OkayCMS — все Entities, Helpers, Requests

Используй эти классы в `registerChainExtension()` / `registerQueueExtension()` и в `services.php`.
Все namespace начинаются с `Okay\` соответственно пути.

---

## Entities (Okay\Entities\)

```
ProductsEntity          VariantsEntity          CategoriesEntity
BrandsEntity            ImagesEntity            FeaturesEntity
FeaturesValuesEntity    OrdersEntity            PurchasesEntity
OrderStatusEntity       OrderHistoryEntity      OrderLabelsEntity
UsersEntity             UserGroupsEntity        CouponsEntity
DeliveriesEntity        PaymentsEntity          CurrenciesEntity
FeedbacksEntity         CommentsEntity          PagesEntity
BlogEntity              BlogCategoriesEntity    AuthorsEntity
MenuEntity              MenuItemsEntity         LanguagesEntity
TranslationsEntity      ModulesEntity           ManagersEntity
SubscribesEntity        DiscountsEntity         SEOFilterPatternsEntity
FeaturesAliasesEntity   FeaturesValuesAliasesValuesEntity
FeaturesAliasesValuesEntity                     SpecialImagesEntity
CallbacksEntity         ReportStatEntity        RouterCacheEntity
UserCartItemsEntity     UserBrowsedProductsEntity
UserWishlistItemsEntity UserComparisonItemsEntity
LessonsEntity           SupportInfoEntity
```

---

## Helpers — фронтенд (Okay\Helpers\)

```
ProductsHelper          CategoriesHelper        BrandsHelper
CartHelper              OrdersHelper            CatalogHelper
FilterHelper            CommonHelper            UserHelper
ValidateHelper          BlogHelper              AuthorsHelper
CommentsHelper          PagesHelper             MainHelper
CanonicalHelper         DeliveriesHelper        PaymentsHelper
MoneyHelper             DiscountsHelper         CouponHelper
NotifyHelper            RelatedProductsHelper   WishListHelper
ComparisonHelper        SiteMapHelper           ResizeHelper
MetaRobotsHelper        XmlFeedHelper
```

---

## Helpers — бекенд (Okay\Admin\Helpers\)

```
BackendProductsHelper       BackendVariantsHelper       BackendCategoriesHelper
BackendBrandsHelper         BackendOrdersHelper         BackendOrderHistoryHelper
BackendOrderSettingsHelper  BackendUsersHelper          BackendUserGroupsHelper
BackendFeaturesHelper       BackendFeaturesValuesHelper BackendCouponsHelper
BackendDeliveriesHelper     BackendPaymentsHelper       BackendCurrenciesHelper
BackendFeedbacksHelper      BackendCommentsHelper       BackendPagesHelper
BackendBlogHelper           BackendBlogCategoriesHelper BackendAuthorsHelper
BackendMenuHelper           BackendManagersHelper       BackendValidateHelper
BackendImportHelper         BackendExportHelper         BackendSettingsHelper
BackendMainHelper           BackendModulesHelper        BackendNotifyHelper
BackendCallbacksHelper      BackendCategoryStatsHelper  BackendSpecialImagesHelper
BackendFeaturesValuesHelper BackendMenuHelper
```

---

## Requests — фронтенд (Okay\Requests\)

```
CommonRequest       CartRequest         UserRequest
```

## Requests — бекенд (Okay\Admin\Requests\)

```
BackendProductsRequest      BackendVariantsRequest      BackendCategoriesRequest
BackendBrandsRequest        BackendOrdersRequest        BackendOrderSettingsRequest
BackendUsersRequest         BackendUserGroupsRequest    BackendFeaturesRequest
BackendFeaturesValuesRequest BackendCouponsRequest      BackendDeliveriesRequest
BackendPaymentsRequest      BackendCurrenciesRequest    BackendFeedbacksRequest
BackendCommentsRequest      BackendPagesRequest         BackendBlogRequest
BackendBlogCategoriesRequest BackendAuthorsRequest      BackendMenuRequest
BackendDiscountsRequest     BackendSettingsRequest      BackendCallbacksRequest
```

---

## Контроллеры — фронтенд (Okay\Controllers\)

```
ProductController       ProductsController      CategoryController
BrandController         BrandsController        CartController
OrderController         UserController          FeedbackController
BlogController          MainController          PageController
```

## Контроллеры — бекенд (Okay\Admin\Controllers\)

Наследоваться от `IndexAdmin` для контроллеров модуля.

---

## Типичные точки расширения

### Товар в админке:
```php
use Okay\Admin\Controllers\ProductAdmin;
use Okay\Admin\Helpers\BackendProductsHelper;
use Okay\Admin\Helpers\BackendValidateHelper;
use Okay\Admin\Requests\BackendProductsRequest;
use Okay\Helpers\ProductsHelper;

// Расширить данные на странице редактирования товара:
$this->registerQueueExtension(
    ['class' => ProductAdmin::class, 'method' => 'fetch'],
    ['class' => MyExtension::class,  'method' => 'extendProductAdmin']
);

// Добавить поле из POST при сохранении товара:
$this->registerChainExtension(
    [BackendProductsRequest::class,  'postProduct'],
    [MyExtension::class, 'extendPostProduct']
);

// Добавить данные к товару при загрузке:
$this->registerQueueExtension(
    [BackendProductsHelper::class, 'getProduct'],
    [MyExtension::class, 'getProduct']
);

// Действие при удалении товара:
$this->registerQueueExtension(
    [ProductsEntity::class, 'delete'],
    [MyExtension::class, 'deleteByProductsIds']
);
```

### Товар на фронте:
```php
use Okay\Helpers\ProductsHelper;

// Прикрепить доп. данные к товару:
$this->registerQueueExtension(
    [ProductsHelper::class, 'attachProductData'],
    [MyExtension::class, 'assignData']
);
```

### Заказ:
```php
use Okay\Helpers\OrdersHelper;
use Okay\Admin\Helpers\BackendOrdersHelper;

$this->registerQueueExtension(
    [OrdersHelper::class, 'finalCreateOrderProcedure'],
    [MyExtension::class, 'afterOrderCreated']
);
```

### Меню и счётчики в админке:
```php
use Okay\Admin\Helpers\BackendMainHelper;

$this->registerChainExtension(
    [BackendMainHelper::class, 'evensCounters'],
    [MyExtension::class, 'setCounters']
);
```
