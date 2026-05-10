

================================================================================
# FILE: README.md
================================================================================

# Документация OkayCMS

## Основные положения

В документации часто может встречаться запись вида "Okay\Core\Response::setContent()", она значит, что имеется в виду 
метод "setContent()" класса "Okay\Core\Response". Это не означает что этот метод статический. Если метод статический,
об этом говорится отдельно.

Если встречаются пути, которые разделенные обратным слешем "\\" это имеется в виду namespace, если пути разделенные
прямым слешем "/" это имеется в виду путь в файловой системе.
Пример неймспейса `Okay\Admin\Controllers`, пример пути `backend/Controllers`.

## Основные типы классов

* [Ядро системы (Core)](./core/README.md)
* [Контроллеры](./controllers.md)
* [Классы сущностей (Entities)](./entities.md)
* [Helpers](./helpers.md)
* [Requests](./requests.md)
* [Маршруты](./routes.md)
* [Подключение внешних файлов дизайна](./js_css_files.md)
* [Smarty плагины](./smarty_plugins.md)
* [Модульность](./modules/README.md)
* [Модуль, быстрый старт](./modules/quick_start.md)
* [Режим разработчика](./dev_mode.md)
* [Пример конфигурации сервера Nginx](./nginx/nginx.conf)
* [Импорт](./import.md)
* [Экспорт](./export.md)
* [Модификация tpl файлов](./tpl_modifiers.md)
* [Работа со скидками](./discounts_management.md)


================================================================================
# FILE: core/README.md
================================================================================

# Ядро системы (Core)

Классы ядра располагаются в директории `Okay/Core/`.
Все их инстансы (экземпляры) содержатся в [DI контейнере](./../di_container.md "Dependency injection container").

### Основные классы ядра

* [ManagerMenu](./ManagerMenu.md)
* [Response](./Response.md)
* [Phone](./Phone.md)
* [Discount](./Discount.md)



# Класс Okay\Core\Classes\Discount

## Свойства

Свойство | Описание
---|---
$sign | Знак скидки.
$type | Тип скидки. Может быть только абсолютная(absolute) или процентная(percent).
$value | Значение скидки в абсолютном или процентном выражении.
$fromLastDiscount | Режим вычесления, от последней скидки или от начальной цены.
$priceBeforeDiscount | Цена до применения скидки.
$priceAfterDiscount | Цена после применения скидки.
$absoluteDiscount | Значение скидки в асболютном значении(денежные единицы).
$percentDiscount | Значение скидки в проценте от начальной цены.
$lang | Массив для временного хранения языковых переменных, которые мы указывали в name и description. Заполняется после выполенние метода `prepareDiscounts()`.
$langParts | Массив значений, которые необходимо подставить в название и описание. В названии/описании необходимо использовать переменную в формате `{$var}`, а в этот массив добавить элемент c сключём 'var' и значение. `$discount->langParts['var'] = 'value'`.
$name | Название скидки. При создании скидки, задаётся языковой переменной. После выполнения метода `prepareDiscounts()`, сюда подставляется значение из переводов.
$description | Описание скидки. При создании скидки, задаётся языковой переменной. После выполнения метода `prepareDiscounts()`, сюдапо дставляется значение из переводов.


================================================================================

# Класс Okay\Core\ManagerMenu

Данный класс предназначен для работы с меню менеджера в админ-панеле и в клиентской части меню быстрого редактирования.

<a name="addCounter"></a>
```php
addCounter( string $menuItemTitle, int $counter)
```

Добавления счетчика новых событий в 
[админ-меню](./../dev_mode.md#backendMenu)

Аргумент | Описание
---|---
$menuItemTitle | [Название пункта меню](./../dev_mode.md#backendMenu), в который стоит добавить счётчик событий. К группе меню счётчик добавляется автоматически.
$counter | Количество новых событий, которое нужно вывести в меню.

Для добавления счетчика, следует создать [экстендер](./../modules/extenders.md), который расширит [хелпер](./../helpers.md) 
`Okay\Admin\Helpers\BackendMainHelper::evensCounters()`.

Пример экстендера:
```php
class BackendExtender implements ExtensionInterface
{
    private $managerMenu;
    private $entityFactory;
    
    public function __construct(ManagerMenu $managerMenu, EntityFactory $entityFactory)
    {
        $this->managerMenu = $managerMenu;
        $this->entityFactory = $entityFactory;
    }

    public function setNewEventsProcedure()
    {
        /** @var SomeEntity $someEntity */
        $someEntity = $this->entityFactory->get(SomeEntity::class);
        $this->managerMenu->addCounter('left_custom_form_data_title', $someEntity->count(['processed' => 0]));
    }
}
```

Пример инициализации:
```php
class Init extends AbstractInit
{
    public function init()
    {
        // ...abstract
        $this->registerChainExtension(
            ['class' => BackendMainHelper::class, 'method' => 'evensCounters'],
            ['class' => BackendExtender::class, 'method' => 'setNewEventsProcedure']
        );
    }
}
```

<a name="addFastMenuItem"></a>
```php
addFastMenuItem( string $dataProperty,  array $...)
```

Добавление элементов в меню быстрого редактирования (admintooltip).

Аргумент | Описание
---|---
$dataProperty | data-атрибут по которому нужно открыть именно это меню
... | Двумерный массив с описанием ссылок, которые стоит добавить в меню.

Описание ссылки должно быть в виде ассоциативного массива.
Параметры:

Параметр | Описание
---|---
controller | Название контроллера на который нужно перевести пользователя в админ-панеле. Обратите внимание, контроллеры модулей в админ-панеле именуются как Vendor.Module.Controller
translation | Название перевода из админ-панели
params | Ассоциативный массив, где ключ имя GET параметра, который нужно добавить, значение - название js переменной, значение которой нужно подставить. На данный момент поддерживается только id (значение указанное в атрибуте data-...)
action | Вариант стилизации ссылки. Возможные значения: edit, add. Если передали params['id'], система по умолчанию установит action=edit

Пример добавления элемента меню быстрого редактирования:

```php
class Init extends AbstractInit
{
    public function init()
    {
        // ...abstract
        $this->addFastMenuItem('property', [
            'controller' => 'Vendor.Module.Controller',
            'translation' => 'translation_var_add',
        ], [
            'controller' => 'Vendor.Module.Controller',
            'translation' => 'translation_var_edit',
            'params' => [
                'id' => 'id',
            ],
            'action' => 'edit',
        ]);
    }
}
```



# Класс Okay\Core\Phone

Класс предназначен для работы с номерами телефонов.

<a name="format"></a>
```php
format( string $phoneNumber [, int $numberFormat = null])
```

Метод форматирует телефон в соответствии с указанным форматом. Если формат не указан, он берется из настроек сайта.
Также этот метод можно вызвать в дизайне через Smarty модификатор `|phone`. 

Пример:
```smarty
{$user->phone|phone}
```

Аргумент | Описание
---|---
$phoneNumber | Номер телефона, который нужно отформатировать
$numberFormat | Одна из констант класса \libphonenumber\PhoneNumberFormat

Варианты констант:

Константа | Пример номера телефона
---|---
E164 | +380442903833
INTERNATIONAL | +380 44 290 3833
NATIONAL | 044 290 3833
RFC3966 | tel:+380-44-290-3833

<a name="toSave"></a>
```php
toSave( string $phoneNumber)
```

Метод подготавливает номер телефона для сохранения в базу, в базе они хранятся в стандарте E164.

Аргумент | Описание
---|---
$phoneNumber | Номер телефона, который будет сохранятся в базу

Пример сохранения телефона в заказе:
```php
use Okay\Core\Phone;

//...abstract

$order = new \stdClass;
$order->name  = $this->request->post('name');
$order->email = $this->request->post('email');
$order->phone = Phone::toSave($this->request->post('phone'));
```

<a name="clear"></a>
```php
clear( string $phoneNumber)
```

Метод очищает телефон от всех лишних символов, которые не могут быть номером телефона

Аргумент | Описание
---|---
$phoneNumber | Номер телефона, который нужно очистить

<a name="isValid"></a>
```php
isValid( string $phoneNumber)
```

Метод валидирует телефон с учетом страны по умолчанию указанной в настройках сайта 

Аргумент | Описание
---|---
$phoneNumber | Номер телефона, который нужно провалидировать


================================================================================
# FILE: core/Response.md
================================================================================

# Класс Okay\Core\Response

<a name="setContent"></a>
```php
setContent( string $content [, string $type = RESPONSE_HTML])
```

Установка данных, которые должны попасть в ответ.

Аргумент | Описание
---|---
$content | Контент, который нужно отдать пользователю.
$type | Одна из [констант типов ответов](#contentTypesConstants)


<a name="contentTypesConstants"></a>
#### Типы ответов

От типа ответа зависит какой адаптер респонса использовать. Все адаптеры находятся в `Okay\Core\Adapters\Response`.
Также каждый адаптер добавляет свои индивидуальные HTTP заголовки (Content-Type etc).

Константа | Тип ответа
---|---
RESPONSE_HTML | Ответ в виде HTML кода. При таком типе ответа в качестве контента можно передавать название tpl файла, результат компиляции которого нужно установить в качестве ответа.
RESPONSE_JSON | Ответ в JSON формате.
RESPONSE_XML  | Ответ в XML формате.
RESPONSE_JAVASCRIPT | Ответ JavaScript. Используется когда отдаются компилированные JS файлы.
RESPONSE_IMAGE | Ответ изображение.
RESPONSE_TEXT | Ответ в виде просто текста.


================================================================================
# FILE: core/Schedule.md
================================================================================

# Класс Okay\Core\Schedule

Класс предназначен для регистрации задачи в планировщике задач.

Принимает в конструктор задачу, которая может иметь следующий вид:

Вид | Формат | Пример
---|---|---
Команда для выполнения в терминале. | Строка | whoami
Callable объект. Класс может быть как сервисом так и обычным классом. Метод может быть статическим или обычным. | Массив [Class, Method]| [NovaposhtaCost::class, 'parseCitiesToCache']
Анонимная функция | Closure | function (ProductsEntity $productsEntity) {$productsEntity->update(1, ['visible' => 1])}

Аргументы метода или анонимной функции, будут резолвиться автоматически на основании тайп хинтов и дефолтных значений.

### Методы

```php
name(string $name)
```
Метод принимает название задачи

```php
time(string $time)
```
Метод принимает паттерн времени выполнения задачи. Формат времени и правила выполнения аналогичны задачам в CRON.
Примеры:

0 * * * * - Выполнение задачи 1 раз в час в 0 минут

0 0 * * * - Выполнение задачи 1 раз в сутки в 00:00 минут

```php
timeout(int $timeout)
```
Максимальное время выполнения задачи в секундах. По истечению времени задача будет завершена в аварийном режиме.

```php
overlap(bool $value)
```
По-умолчанию допускается выполнение более одного экземпляра задачи. Если вызвать метод и передать false, то одновременно будет выполняться не более 1 экземпляра.


================================================================================
# FILE: controllers.md
================================================================================

# Контроллеры

Контроллеры в OkayCMS нужны для обработки маршрутов.
Контроллеры делятся на фронт-контроллеры (клиентская часть) и бек-контроллеры (админ часть).
Также эти контроллеры делятся на стандартные (которые присутствуют в системе по умолчанию) и модульные (которые
добавляются из модулей).

Общий порядок работы контроллеров. 
Сразу зайдя на страницу роутер создает экземпляр контроллера, затем вызывает
метод `onInit` после него вызывает метод контроллера, указанный в роуте.

В методе контроллера стоит установить контент, который должен возвращаться пользователю. Для этого вызовите метод
[Response::setContent()](./core/Response.md#setContent).

### Фронт-контроллеры <a name="frontControllers"></a>

По умолчанию контроллеры лежат в неймспейсе `Okay\Controllers\` и МОГУТ наследоваться от 
`Okay\Controllers\AbstractController`.

В большинстве случаем желательно наследоваться от AbstractController, чтобы у вас уже был проинициализирован дизайн,
валюты, языки etc. 
Но в случае, когда нужно сделать "лёгкий" контроллер (например для обработки не сложных ajax запросов), можно
не наследоваться, но в таком случае если нужен дизайн или еще что-то что инициализировалось в AbstractController,
его нужно получать отдельно в методе контроллера через [инъекцию зависимости](./di_container.md#GetService).
Также можно определить свои методы onInit() и afterController().

В контроллере может быть описано несколько методов, и несколько [роутов](./routes.md) может ссылаться на разные 
методы одного контроллера.

#### Получение сервисов из контейнера <a name="DIVars"></a>

В методе контроллера можно получать в виде зависимостей экземпляры классов [ядра](./core/README.md), [хелперов](./helpers.md),
[реквесты](./requests.md), [entity](./entities.md), а также параметры, указанные в [роуте](./routes.md). 
Чтобы получить экземпляр определённого класса, нужно принять в методе контроллера аргумент, с указанием type hint,
и система автоматически передаст туда экземпляр запрашиваемого класса.

Например:
```php
namespace Okay\Controllers;

use Okay\Entities\BlogEntity;
use Okay\Helpers\CommentsHelper;

class BlogController extends AbstractController
{
    public function fetchPost(
        BlogEntity $blogEntity,
        CommentsHelper $commentsHelper
    ) {
        //...abstract
    }
}
```

#### Получение параметров из марштута <a name="routeVars"></a>

Чтобы получить параметры из [маршрута](./routes.md) в контроллере, нужно в методе контроллера указать аргумент,
одноимённый с параметром маршрута (указанным в поле slug), при этом type hint не указывается.
Если роут предполагает что параметр является опциональным, в методе стоит ему тоже задать значение по умолчанию.

Например для slug роута `/cart/{$variantId}` в методе можно ловить переменную `$variantId`

Пример:
```php
namespace Okay\Controllers;

use Okay\Core\Cart;

class CartController extends AbstractController
{
    public function addItem(Cart $cartCore, $variantId)
    {
        //...abstract
    }
}
```

### Бек-контроллеры <a name="backendControllers"></a>

Контроллеры админ-части по умолчанию лежат в неймспейсе `Okay\Admin\Controllers` (это директория `backend/Controllers`).
Все контроллеры админки должны наследоваться от `Okay\Admin\Controllers\IndexAdmin`.
У контроллеров админ-части по умолчанию вызывается метод `fetch()`.
Если нужно вызвать другой метод контроллера, нужно в урле название контроллера указать как ControllerName@methodName.
Например: `backend/index.php?controller=OrdersAdmin@myFunc` вызовется метод myFunc контроллера OrdersAdmin.
Имя метода может содержать только символы `a-zA-Z0-9`
Зависимости можно получать также, как и для [фронт-контроллеров](#DIVars). Параметров маршрута для контроллера админки
не бывает. Разве что $_GET параметры.

### Фронт-контроллеры модулей

Контроллеры модулей стоит размещать в директории `Okay/Modules/Vendor/Module/Controllers`, в остальном, они ничем не
отличаются от [стандартных фронт-контроллеров](#frontControllers). Роут к ним также прописывается в [файле 
`Init/routes.php`](./modules/README.md#configuratinFiles).

### Бек-контроллеры модулей <a name="backendControllersModules"></a>

Бек-контроллеры модулей, полностью соответствуют [стандартным бек-контроллерам](#backendControllers), но располагаются
в директории `Okay/Modules/Vendor/Module/Backend/Controllers`.
Еще одно отличие в использовании: стандартный контроллер в админ панели доступен по урлу 
`https://demookay.com/backend/?controller=ProductsAdmin`, контроллеры модулей имеют немного инной формат.
Название контроллера должно состоять из имени поставщика, имени модуля и названия самого контроллера разделённых точкой.

Например: `https://demookay.com/backend/index.php?controller=OkayCMS.FAQ.FAQsAdmin`, об этом стоит помнить, когда пишете
URL на контроллер в tpl файле модуля.


================================================================================
# FILE: entities.md
================================================================================

# Entities

Классы сущностей нужны для управления наборами данных хранящихся в постоянной памяти.
Большинство классов Entities в OkayCMS работают с базой данных, но есть некоторые, которые хранят записи в файловой 
системе.

По умолчанию классы сущностей лежат в Okay/Entities/. Сущности из модулей нужно хранить в 
`Okay/Modules/Vendor/Module/Entities`.

Все классы реализовывают интерфейс `Okay\Core\Entity\EntityInterface`
Каждый класс сущности должен наследоваться от класса `Okay\Core\Entity\Entity`.

В классе `Okay\Core\Entity\Entity` уже есть базовая реализация класса Entity для работы с БД. Для корректной работы
нужно произвести первоначальную настройку.

### Настройка Entity для работы с БД

Для настройки нужно указать некоторые защищенные статические (protected static) свойства.

Обязательные свойства:
* `$table` - string название таблицы, в которой нужно сохранять данные (можно с префиксом `__`, можно без него)
* `$tableAlias` - string алиас для основной таблицы, который стоит использовать в SQL запросах
* `$fields` - array список полей, которые нужно доставать из БД

Необязательные свойства:
* `$langTable` - string название таблицы, в которой хранятся переводы (без `__lang_`)
* `$langFields` - array список мультиязычных полей, которые нужно доставать из БД
* `$langObject` - string используется для связи с мультиязычными данными (в языковых таблицах blog_id, product_id)
* `$searchFields` - array список полей по которым происходит текстовый поиск (можно указывать и ленговые и нет). Будет
использоваться если передать ['keyword' => 'name of entity item'].
* `$additionalFields` - array список дополнительных полей сущности, с других таблиц или которые как подзапросы идут 
(к ним префикс таблицы не добавляется).
* `$defaultOrderFields` - array список полей по которым происходит сортировка по умолчанию (с указанием направления).
* `$alternativeIdField` - string поле по которому может происходить get() если id передали строкой (url, code etc...)
Предпочтительнее использовать метод findOne(['field' => $value]).

Пример настройки:

```php
namespace Okay\Entities;
use Okay\Core\Entity\Entity;
class SomeEntity extends Entity
{
    protected static $fields = [
        'id',
        'url',
        'visible',
    ];
    
    protected static $langFields = [
        'name',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'annotation',
        'description',
    ];
    
    protected static $searchFields = [
        'name',
        'meta_keywords',
    ];

    protected static $table = 'some_entities';
    protected static $langObject = 'some_entity';
    protected static $langTable = 'some_entities';
    protected static $tableAlias = 's';
}
```

### Фильтрация выборки Entity из БД

Каждый экземпляр класса Entity содержит приватное свойство $select, в котором лежит экземпляр класса 
[Aura\SqlQuery\Common\SelectInterface](https://github.com/auraphp/Aura.SqlQuery/blob/3.x/docs/select.md).

Сброс состояния производится вызовом метода Entity::flush(). По умолчанию состояние сбрасывается автоматически после
вызова методов find(), count(), get() ect. Сбрасывать его вручную может потребоваться в каких-то особых случаях.

#### "Магические" фильтры

Методы find(), count() etc принимают ассоциативный массив данных, по которым нужно фильтровать, где ключ массива - это 
название фильтра. "Магические" фильтры работают в случае если передали фильтр с названием как название колонки,
и при этом данный фильтр не переопределён. Эти фильтры также строят разные запросы в случае если передали строку
или другое единичное значение, и если передали массив значений.

Например:
```php
namespace Okay\Entities;
use Okay\Core\Entity\Entity;
class SomeEntity extends Entity
{
    protected static $fields = [
        'id',
        'url',
    ];
    
    protected static $langFields = [
        'name',
    ];

    // ...abstract 
}
```

Вызов с единичными значениями:
```php
$someEntity->find([
    'url' => 'some/url',
]);

$someEntity->find([
    'name' => 'name of entity item',
]);
```

построит запросы `SELECT ... WHERE entity_table.url = 'some/url'` и `SELECT ... WHERE lang_entity_table.name = 'name of entity item'`.

Вызов с множеством значений:
```php
$someEntity->find([
    'id' => [1, 2, 3, 4, 5],
]);
```

построит запрос `SELECT ... WHERE entity_table.id IN (1,2,3,4,5)`.

#### Пользовательские фильтры <a name="usersFilters"></a>

Если поведение "магических" фильтров не устраивает, или его нужно по какой-то причине отменить вообще, или вы фильтруете
не по полю, а скажем по таблице связей, нужно объявить свой пользовательский фильтр в вашем классе Entity.

Это должен быть защищенный (protected) метод, название которого состоит из ключевого слова `filter__` (обратите
внимание на два символа подчёркивания) и самого названия фильтра (он же будет ключем массива фильтра при вызове find(), 
count() ...). Внутри этого метода мы работаем с объёктом 
[QueryBuilder](https://github.com/auraphp/Aura.SqlQuery/blob/3.x/docs/select.md), который лежит в свойстве $select.
Метод может принимать два аргумента, первым будет значение которое передали в этот фильтр при вызове find() или count(), 
вторым будет полностью весь массив $filter (который не обязательно принимать).

Пример вызова:
```php
$someEntity->find([
    'url' => 'some/url',
    'field' => 'value',
]);
```

Пример пользовательского фильтра:
```php
namespace Okay\Entities;

use Okay\Core\Entity\Entity;
use Aura\SqlQuery\Common\Select;

class SomeEntity extends Entity
{
    /** @var Select */
    protected $select;
    protected static $tableAlias = 'e';

    // ...abstract 

    protected function filter__field($val, $filter)
    {
        // $val = 'value';
        // $filter = [
        //               'url' => 'some/url',
        //               'field' => 'value',
        //           ];
        
        $this->select->join('inner', '__second_table AS st', 'e.id = st.entity_id AND st.field=:value')
            ->bindValue('value', $val);
        
        $this->select->groupBy(['e.id']);
    }
}
```

#### Пользовательские фильтры созданные из модулей для существующего Entity <a name="usersFiltersFromModules"></a>

Если нужно добавить пользовательский фильтр в существующий Entity, его можно добавить через модуль.
Для этого нужно описать метод, который будет выполнять роль пользовательского фильтра в классе
наследнике `Okay\Core\Modules\AbstractModuleEntityFilter`. Метод такого фильтра должен быть публичным (public).

Пример:
```php
namespace Okay\Modules\OkayCMS\GoogleMerchant\ExtendsEntities;


use Okay\Core\Modules\AbstractModuleEntityFilter;
use Okay\Modules\OkayCMS\GoogleMerchant\Init\Init;

class ProductsEntity extends AbstractModuleEntityFilter
{
    public function okaycms__google_merchant__only($categoriesIds, $filter)
    {
        $categoryFilter = '';
        if (!empty($categoriesIds)) {
            $categoryFilter = "OR p.id IN (SELECT product_id FROM __products_categories WHERE category_id IN (:category_id))";
            $this->select->bindValue('category_id', (array)$categoriesIds);
        }

        $this->select->where('not_to__okaycms__google_merchant != 1');
        $this->select->where("(
            p.".Init::TO_FEED_FIELD."=1 
            OR p.brand_id IN (SELECT id FROM __brands WHERE ".Init::TO_FEED_FIELD." = 1)
            {$categoryFilter}
        )");
    }
}
```
Внутри метода фильтра работа выполняется так же как и в [обычном пользовательском фильтре](#usersFilters).
Но данный метод хоть чуть более сложный, но он позволяет из модуля добавить пользовательский фильтр к существующему 
Entity не правя файл, где описан их класс.

Пример регистрации данного фильтра:
```php
namespace Okay\Modules\OkayCMS\GoogleMerchant\Init;

use Okay\Core\Modules\AbstractInit;
use Okay\Entities\ProductsEntity;

class Init extends AbstractInit
{
    public function install()
    {
        // ...abstract
    }

    public function init()
    {
        // ...abstract
        $this->registerEntityFilter(
            ProductsEntity::class,
            'okaycms__google_merchant__only',
            \Okay\Modules\OkayCMS\GoogleMerchant\ExtendsEntities\ProductsEntity::class,
            'okaycms__google_merchant__only'
        );
    }
}
```

### Сортировка выборки Entity из БД

Для указания сортировки выборки отличной от указанной в свойстве `$defaultOrderFields` нужно при вызове метода find()
вызвать дополнительный метод order(), который принимает два параметра. Первый - название сортировки, второй это массив
который может использоваться для пользовательских целей, по умолчанию не влияет на функциональность.

#### "Магические" сортировки

Если указали сортировку с именем, совпадающем с именем поля в Entity, применится "магическая" сортировка по данному 
полю, если не задана пользовательская сортировка с таким именем. Также можно передавать название сортировки и 
её направление. Можно передавать `name`, `name_asc` (одно и то же) или `name_desc` и если у Entity есть колонки 'name' 
(не важно она ленговая или нет) автоматически добавится `SELECT ... ORDER BY name ASC`.
Их недостаток, работают они только с одним полем.

#### Пользовательские сортировки

Если же нужно использовать более сложную сортировку, нужно её отдельно описать.
Чтобы её описать, нужно в классе вашего Entity перегрузить метод customOrder().
Он может содержать три параметра:
* `$order` - string название сортировки, которое передали
* `$orderFields` - array массив полей, которые определили сортировки "выше" (чаще всего это "магическия" сортировка)
* `$additionalData` - array массив пользовательских данных, которые передали в метод order().

Пример:
```php
namespace Okay\Entities;

use Okay\Core\Entity\Entity;
use Okay\Core\Modules\Extender\ExtenderFacade;

class ProductsEntity extends Entity
{
    // ...abstract 

    protected function customOrder($order = null, array $orderFields = [], array $additionalData = [])
    {
        switch ($order) {
            case 'rand':
                $orderFields = ['RAND()'];
                break;
            case 'position':
                $orderFields = ['p.position DESC'];
                break;
        }
        
        return ExtenderFacade::execute([static::class, __FUNCTION__], $orderFields, func_get_args());
    }
}
```

### Маппинг результатов выборки Entity из БД

Если нужно чтобы массив результатов выборки из БД в качестве ключей массива содержал значение из определенной колонки,
нужно вызвать метод mappedBy($columnName) с указанием колонки, данные которой будут являться ключами.

Пример:
```php
$products = [];
foreach ($productsEntity->find(['category_id' => 1]) as $product) {
    $products[$product->id] = $product;
}

// то же самое что и
$products = $productsEntity->mappedBy('id')->find(['category_id' => 1]);
```

### Ограничение выбираемых данных из БД

Иногда при поиске набора сущностей, нужно ограничить объем доставаемых данных (получать не все колонки).
Для этого нужно вызвать метод cols(), он принимает массив колонок, которые нужно достать.

Например в списке товаров не нужно доставать описание и метаданные всех товаров:
```php
$products = $productsEntity->cols([
        'id',
        'name',
        'url',
        'name',
        'special',
        'annotation',
    ])->find($filter);
```

### Получить объект Select класса Entity

Если нужно получить объект запроса, который строит Entity, его можно получить с помощью метода YourEntity::getSelect()
(имеется ввиду метод getSelect() нужного Entity). Он вернет объект запроса, который бы метод find или findOne отправил
в базу. Имейте в виду, если вы перегружаете метод find базового класса Entity, для корректной работы getSelect вам может
понадобиться также перегрузить метод getSelect() в этом Entity. 

Например: если у вас метод перед отработкой родительского метода добавляет к запросу еще какие-то данные, это может быть
нужно повторить для метода getSelect()
```php
class ProductsEntity extends Entity

    public function find(array $filter = [])
    {
        $this->select->leftJoin(RouterCacheEntity::getTable() . ' AS r', 'r.url=p.url AND r.type="product"');
        
        return parent::find($filter);
    }
    
    public function getSelect(array $filter = [])
    {
        $this->select->leftJoin(RouterCacheEntity::getTable() . ' AS r', 'r.url=p.url AND r.type="product"');
        
        return parent::getSelect($filter);
    }
}
```

Метод getSelect() принимает массив фильтра так же как методы find() или findOne() и возвращает полноценный объект Select
который можно в дальнейшем модифицировать и выполнить.

Пример:
```php
$query = $commentsEntity->getSelect(['type' => 'post', 'object_id' => $postsIds]);
$query->groupBy(['object_id'])->resetCols()->cols(["COUNT( DISTINCT id) as count", "object_id"]);

foreach ($query->results() as $result) {
    if (isset($posts[$result->object_id])) {
        $posts[$result->object_id]->comments_count = $result->count;
    }
}
```

### Дебаг запросов в классе Entity

Если необходимо увидеть текст запроса класса Entity, который летит в БД, можно перед вызовом метода find() или findOne() 
вызвать метод debug() и текст запроса будет передан на вывод (осторожно на проде.).

Пример:

```php
$productsEntity->debug()->find($filter);
```

Такой запрос будет выполнен и передан в вывод.
Также у класса Select можно вызвать метод debugPrint() который сделает то же самое.

Пример:
```php
$select = $productsEntity->getSelect($filter);
$select->debugPrint();

// тоже самое
$productsEntity->getSelect($filter)->debugPrint();
```

### Лимит выборки результатов

По умолчанию все SELECT запросы через классы Entity выполняются с SQL лимитом, даже если его явно не передали, он по умолчанию
для безопасности устанавливается в 100. Но если вам нужно достать вообще все данные и не важно сколько это строк,
на свой страх и риск можно вызвать метод noLimit() класса Entity перед вызовом find().

Например:

```php
$productsEntity->noLimit()->find($filter);
```

Но будьте осторожны, при большом количестве данных запрос может существенно "тормозить".


================================================================================
# FILE: helpers.md
================================================================================

# Helpers

Хелперы предназначены для того, чтобы вынести часть логики (бизнес логики, или логики приложения) из контроллера.
Методы хелперов могут переиспользоваться в различных частях системы. Например Okay\Helpers\ProductsHelper::getProductList.
Также методы любого хелпера могут [расширяться из модуля](./modules/extenders.md).

Название всех сервисов хелперов заканчиваются на ключевое слово Helper.
По умолчанию все хелперы хранятся в директории Okay/Helpers/ и backend/Helpers/.

Хелперы могут возвращать результат выполнения. Но результат выполенния дложен возвращаться не напрямую,
а через ExtenderFacade::execute();.
Метод execute() принимает три параметра, имя метода (строка или массив), в котором он запускается, данные которые нужно вернуть
и массив аргументов данного метода.

Пример возвращения результата в хелпером:
```php
return ExtenderFacade::execute(__METHOD__, $result, func_get_args());
return ExtenderFacade::execute([static::class, __FUNCTION__], $result, func_get_args());
```

Пример хелпера:
```php
class BrandsHelper
{

    //...abstract 

    public function getBrandsList($filter = [])
    {
        /** @var BrandsEntity $brandsEntity */
        $brandsEntity = $this->entityFactory->get(BrandsEntity::class);
        $brands = $brandsEntity->find($filter);
        return ExtenderFacade::execute(__METHOD__, $brands, func_get_args());
    }
}
```
Данный хелпер достает из базы список брендов. По большому счёту, это можно считать декоратором к методу 
BrandsEntity::find().

Более интересный пример:
```php
class ProductsHelper
{

    //...abstract 
    
    public function getProductList($filter = [])
    {
        /** @var ProductsEntity $productsEntity */
        $productsEntity = $this->entityFactory->get(ProductsEntity::class);
        
        if ($this->settings->get('missing_products') === MISSING_PRODUCTS_HIDE) {
            $filter['in_stock'] = true;
        }
    
        $products = $productsEntity->mappedBy('id')->find($filter);
    
        if (empty($products)) {
            return ExtenderFacade::execute(__METHOD__, [], func_get_args());
        }
    
        $products = $this->attachVariants($products);
    
        return ExtenderFacade::execute(__METHOD__, $products, func_get_args());
    }
}
```
данный хелпер не только достает список товаров, а и добавляет к ним варианты, тем самым декорируя результат 
ProductsEntity::find().

### ValidateHelper

Хелпер валидации требует отдельного внимания.
Если все хелперы подроблены каждый под свою сущность, то хелпер валидации собрал 
в себе валидации всех [реквестов](./requests.md).
Методы там называются от обратного getFeedbackValidateError() и подобные.

Пример: 
```php
use Okay\Core\Validator;
//...abstract
class ValidateHelper
{

    //...abstract 
    /** @var Validator  */
    private $validator;
    //...abstract 

    public function getFeedbackValidateError($feedback)
    {
        $captchaCode =  $this->request->post('captcha_code', 'string');
        
        $error = null;
        if (!$this->validator->isName($feedback->name, true)) {
            $error = 'empty_name';
        } elseif (!$this->validator->isEmail($feedback->email, true)) {
            $error = 'empty_email';
        } elseif (!$this->validator->isComment($feedback->message, true)) {
            $error = 'empty_text';
        } elseif ($this->settings->get('captcha_feedback') && !$this->validator->verifyCaptcha('captcha_feedback', $captchaCode)) {
            $error = 'captcha';
        }
    
        return ExtenderFacade::execute(__METHOD__, $error, func_get_args());
    }
}
```

Пример использования:
```php
use Okay\Helpers\ValidateHelper;
//...abstract
class FeedbackController extends AbstractController
{
    //...abstract
    public function render(
        //...abstract
        CommonRequest $commonRequest,
        ValidateHelper $validateHelper
    ) {
        if (($feedback = $commonRequest->postFeedback()) !== null) {
            if ($error = $validateHelper->getFeedbackValidateError($feedback)) {
                // Обработка ошибки
            } else {
                //...abstract
            }
        }
        //...abstract
    }
}
```

#### Хелперы модулей <a name="modulesHelpers"></a>
Модуль также может содержать свои хелперы. Рекомендуется по возможности, все логические части кода выносить в хелперы.
Это обеспечит более гибкое взаимодействие между модулями. Хелперы модуля регистрируются также как и 
[сервисы модуля](./modules/README.md#Initservices)


================================================================================
# FILE: requests.md
================================================================================

# Requests

Классы реквестов предназначены для сбора сгруппированных данных из запроса (в частности POST).
Реквесты регистрируются, также как и стандартные [сервисы ядра](./di_container.md#serviceRegister)
и могут [расширяться из модуля](./modules/extenders.md).

Название всех сервисов реквестов заканчиваются на ключевое слово Request.
По умолчанию все реквесты хранятся в директории Okay/Requests/ и backend/Requests/.

Реквесты обязательно должны возвращать результат (даже пустой). Но результат выполнения должен возвращаться не напрямую,
а через ExtenderFacade::execute();.
Метод execute() принимает три параметра:
* имя метода (строка или массив) в котором он запускается,
* данные которые нужно вернуть, 
* массив аргументов данного метода.

Пример:
```php
use Okay\Core\Request;
//...abstract
class CommonRequest
{
    //...abstract
    /** @var Request  */
    private $request;
    //...abstract

    public function postComment()
    {
        $comment = null;
        if ($this->request->post('comment')) {
            $comment = new \stdClass;
            $comment->name = $this->request->post('name');
            $comment->email = $this->request->post('email');
            $comment->text = $this->request->post('text');
        }
    
        return ExtenderFacade::execute(__METHOD__, $comment, func_get_args());
    }
}
```

Таким образом, данный метод реквеста возвращает данные полученные из $_POST. Также этот метод можно
[расширить из модуля](./modules/extenders.md).

Пример использования:
```php
use Okay\Requests\CommonRequest;
//...abstract

class FeedbackController extends AbstractController
{
    
    //...abstract

    public function render(
        //...abstract
        CommonRequest $commonRequest
    ) {
        if (($feedback = $commonRequest->postFeedback()) !== null) {
            //...abstract
        }
        //...abstract
    }
}
```


================================================================================
# FILE: routes.md
================================================================================

# Маршруты (Routes)

С помощью маршрутов система узнаёт какому контроллеру нужно передать управление для обработки запроса.
Маршруты в системе прописываются в файле `Okay/Core/config/routes.php`.
 Ключ массива - это имя маршрута.
 Значение - описание маршрута.

Маршрут представляет собой массив вида:
```php
[
    'slug' => 'урл_роута{$именованный_параметр}',
    'patterns' => [
        '{$именованный_параметр}' => 'регулярное_выражение_параметра',
    ],
    'params' => [
        'controller' => 'имя_используемого_класса_контроллера',
        'method'     => 'имя_вызываемого_метода_контроллера',
    ],
    'defaults' => [
        '{$имя_параметра_по-умолчанию}' => 'значение_параметра_по-умолчанию',
    ],
    'to_front' => true|false, // нужен ли будет этот роут как JS переменная на фронте. В JS его можно будет увидеть как okay.router['<route_name>'],
    'overwrite' => true|false, // может ли этот роут переопределять роут, расположенный выше, при совпадающих названиях,
    'always_active' => true|false, // всегда активен. Когда установлено в true, даже при выключении сайта данный роут будет активен,
];
```

Пример:
```php
'cart_remove_item' => [
    'slug' => '/cart/remove/{$variantId}',
    'params' => [
        'controller' => 'CartController',
        'method' => 'removeItem',
    ],
    'patterns' => [
        '{$variantId}' => '([0-9]+)',
    ],
],
```

Все маршруты обрабатываются сверху вниз, до первого, чей slug соответствует текущему URL.
Все параметры, которые объявлены как `{$var}` и не указан для них pattern, он по умолчанию равен выражению `([^/]+)`.
Также параметры указанные как `{$var}` можно [получать в методе контроллера](./controllers.md#routeVars).

Также маршруты можно добавлять из [модуля](./modules/README.md). Для этого нужно описать маршруты по такой же структуре
как и стандартный, но в файле `Okay/Modules/Vendor/Module/Init/routes.php`.

### Генерация урлов

Роутер может на основании данных маршрута и генерировать урлы. Благодаря этому можно абстрагироваться от структуры
урлов, что делает систему более гибкой. 
Для генерации урлов в OkayCMS есть статический метод Router::generateUrl().
Метод generateUrl()
Принимает следующие аргументы:
* `$routeName` - string название маршрута, на основании которого нужно строить урл
* `$params` - array ассоциативный массив данных, которые нужно подставить в маршрут.
Ключем должно быть имя параметра. Напр. для slug `/cart/remove/{$variantId}` массив должен быть `['variantId' => 1]`
* `$isAbsolute` - bool признак абсолютного урл. По умолчанию false
* `$langId` - int id языка, для которого нужно построить маршрут. Если для текущего, можно не указывать.

Также для Smarty есть плагин-обёртка для данного метода `{url_generator}`.
Который может принимать параметры:
* `route` - string название маршрута
* `absolute` - bool|int признак абсолютного урл. По умолчанию false
* прочие параметры, которые передадутся в аргумент $params метода generateUrl().

Пример построения урла для роута `cart_remove_item` (описание выше):

PHP
```php
use Okay\Core\Router;

$url = Router::generateUrl('cart_remove_item', ['variantId' => 1], true); // http://domain.com/cart/remove/123
```

Smarty
```smarty
{url_generator route='cart_remove_item' variantId=132 absolute=1}
```

### Получение информации по текущему маршруту

Для получения информации по текущему маршруту, у класса Router есть следующие методы:
* getCurrentRouteName() - возвращает имя текущего роута
* getCurrentRouteRequiredParams() - возвращает все обязательные параметры, для которых не задан type hint (текстовые)
в виде ассоциативного массива, которые указаны в поле slug роута
* getCurrentRouteParams() - возвращает все параметры, для которых не задан type hint (текстовые)
в виде ассоциативного массива, который указаны в поле slug роута
* getRouteByName($routeName) - получить всю информацию по указанному маршруту


================================================================================
# FILE: js_css_files.md
================================================================================

# Подключение Js и Css файлов

В OkayCMS Js и Css файлы не подключаются напрямую через тег `<script></script>` или `<link />`, их нужно регистрировать.
Все зарегистрированные файлы собираются в несколько (зависит от параметров) общих, которые минифицируются, и 
подключаются в шаблон.
Регистрация JavaScript для клиентской части происходит в файле `design/<theme name>/js.php`, Css соответственно в 
`design/<theme name>/css.php`. 
Для админ части регистрация происходит в `backend/design/js.php` и `backend/design/css.php`.

Для подключения Js файлов, нужно создать файл `design/<theme name>/js.php`, который возвращает массив объектов
[Okay\Core\TemplateConfig\Js](#TemplateConfigJs). Или файл `design/<theme name>/css.php` с массивом 
[Okay\Core\TemplateConfig\Css](#TemplateConfigCss) соответственно.

Из модуля также эти файлы можно подключать, расположив регистрационные файлы в директории 
`Okay/Modules/Vendor/Module/design/` для подключения файлов в клиентский шаблон и в директорию
`Okay/Modules/Vendor/Module/Backend/design/` для подключения файлов в админ часть.


<a name="commonScript"></a>
#### Общее описание классов Okay\Core\TemplateConfig\Js и Okay\Core\TemplateConfig\Css

Класс в конструктор принимает название файла, который нужно зарегистрировать (без пути).
Если путь не указать, это имеется в виду, что файл лежит в `design/<theme name>/js/` или `design/<theme name>/css/`.
В случае если подключается файл из модуля, имеется в виду директория 
`Okay/Modules/Vendor/Module/design/js/` или `Okay/Modules/Vendor/Module/design/css/`.
По умолчанию все зарегистрированные скрипты выводятся в одном общем файле в head шаблона.
Оба класса (`Okay\Core\TemplateConfig\Js` и `Okay\Core\TemplateConfig\Css`) имеют общую реализацию
(в `Okay\Core\TemplateConfig\Common`) следующих методов:


<a name="setDir"></a>
```php
setDir( string $dir)
```

Установка директории скрипта.
Если скрипт находится в теме (директория js или css соответственно), директорию можно не указывать.

Аргумент | Описание
---|---
$dir | Путь к директории скрипта, относительно корня сайта.


<a name="setPosition"></a>
```php
setPosition( string $position)
```

Установка позиции, где нужно выводить скрипт (head/footer)

Аргумент | Описание
---|---
$position | Позиция скрипта (head/footer).


<a name="setIndividual"></a>
```php
setIndividual( bool $individual)
```

Установка флага что файл должен подключиться индивидуально, не в общем скомпилированном файле

Аргумент | Описание
---|---
$individual | true - подключаем индивидуально, false - файл будет подключен в общем скомпилированном файле.


<a name="preload"></a>
```php
preload()
```

Установка флага, что нужно добавить для этого файла предзагрузчик link rel="preload". Работает только для файлов 
отмеченных через setIndividual, предзагрузка общими файлами управляется в файле config/config.php директивами
`preload_head_css`, `preload_head_js`, `preload_footer_css` и `preload_footer_js`

<a name="TemplateConfigCss"></a>
#### Класс Okay\Core\TemplateConfig\Css

Класс `Okay\Core\TemplateConfig\Css` не имеет индивидуальной реализации, содержит только 
[общие методы](#commonScript).

Пример регистрации:
```php
use Okay\Core\TemplateConfig\Css;

return [
    (new Css('font.css')),
    (new Css('font-awesome.min.css'))->setPosition('footer'),
    (new Css('grid.css'))->setDir('/custom_js/')->setIndividual(true),
];
```


<a name="TemplateConfigJs"></a>
#### Класс Okay\Core\TemplateConfig\Js

Класс `Okay\Core\TemplateConfig\Js` имеет индивидуальную реализацию следующего метода, в остальном он соответствует 
[общей реализации](#commonScript).

<a name="setDefer"></a>
```php
setDefer( string $defer)
```

Установка JavaScript файлу флага defer. Флаг defer будет добавлен в случае [individual](#setIndividual) = true

Аргумент | Описание
---|---
$defer | Путь к директории скрипта, относительно корня сайта.

Пример регистрации:
```php
use Okay\Core\TemplateConfig\Js;

return [
    (new Js('jquery-3.4.1.min.js')),
    (new Js('owl.carousel.min.js'))->setIndividual(true)->setDefer(true),
    (new Js('select2.min.js'))->setPosition('footer'),
];
```

<a name="TemplateConfigSmarty"></a>
#### Подключение файлов через Smarty

Подключение файлов через Smarty может понадобиться если нужно подключить файл по условию.
Для подключения файла нужно вызвать один из плагинов Smarty {css} или {js}. 
Возможные аргументы плагина:

Аргумент | Описание
---|---
filename | Имя подключаемого файла. То же что передается в конструктор Okay\Core\TemplateConfig\Js или Okay\Core\TemplateConfig\Css
file | Синоним filename
dir | Аналог метода Okay\Core\TemplateConfig\Js::setDir() или Okay\Core\TemplateConfig\Css::setDir()
backend | Булев тип. Указание что подключаем файл для админ части. По умолчанию считается что подключается файл для клиентской части
admin | Синоним backend
defer | Булев тип. Указывает нужно ли добавлять атрибут defer. Доступно только для плагина {js}


================================================================================
# FILE: smarty_plugins.md
================================================================================

# Smarty плагины

Плагины для смарти в OkayCMS нужны для расширения функциональности дизайна.
Планины могут работать в режиме модификатора или функции.

<a name="pluginRegister"></a>
#### Регистрация плагинов

В системе плагины регистрируются в файле `Okay/Core/SmartyPlugins/SmartyPlugins.php`, и являются по сути сервисами
[DI контейнера](./di_container.md). Сами реализации плагинов располагаются в `Okay\Core\SmartyPlugins\Plugins` и должны
быть наследником `Okay\Core\SmartyPlugins\Func` (для работы в режиме функции) или `Okay\Core\SmartyPlugins\Modifier` 
(для работы в режиме модификатора).

Класс плагина должен реализовать метод `run()`, который и будет реализацией функциональности плагина.
Также класс должен объявить одно защищеное (protected) свойство `$tag`, значение которого и будет названием функции
в tpl файле.

<a name="funcArguments"></a>
##### В аргументы метода в режиме функции

В режиме функции все аргументы вызова будут передаваться в метод `run()` в виде ассоциативного массива.
Также вторым аргументом можно ловить экземпляр `Smarty`.

Пример вызова:
```smarty
{some_plugin var1=foo var2=bar}
```

в методе плагина мы получим так:
```php
public function run($params)
{
    // $params = [
    //    'var1' => 'foo',
    //    'var2' => bar,
    //];
    
    // ...abstract
}
```

`Best practices: в плагин передавать переменную "var", значение которой будет названием переменной - результатом работы`

Пример:
```smarty
{get_new_products var=new_products limit=5}
{if $new_products}
    {foreach $new_products as $product}
        // ...abstract
    {/foreach}
{/if}
```

в методе плагина мы получим так:
```php
public function run($params)
{
    // ...abstract
    if (!empty($params['var'])) {
        $smarty->assign($params['var'], $products);
    }
}
```

<a name="modifierArguments"></a>
##### В аргументы метода в режиме модификатора

В режиме модификатора аргументы вызова будут передаваться в метод `run()` в следующем порядке:

Первый аргумент, это будет собственно то, к чему применили модификатор, вторым и последующими аргументами будут 
параметры, переданные модификатору. Передача параметров происходит последовательно с разделением параметров 
двоеточием ":". 

Пример вызова:
```smarty
{$product->name|some_modifier:foo:bar}
```

в методе модификатора мы получим так:
```php
public function run($productName, $param1, $param2 = null)
{
    // $param1 = 'foo';
    // $param2 = 'bar';
    // ...abstract
}
```

#### Пример плагина

```php
namespace Okay\Core\SmartyPlugins\Plugins;

use Okay\Core\EntityFactory;
use Okay\Entities\ProductsEntity;
use Okay\Helpers\ProductsHelper;
use Okay\Core\SmartyPlugins\Func;

class GetNewProducts extends Func
{

    protected $tag = 'get_new_products';
    
    /** @var ProductsEntity */
    private $productsEntity;
    
    /** @var ProductsHelper */
    private $productsHelper;

    
    public function __construct(EntityFactory $entityFactory, ProductsHelper $productsHelper)
    {
        $this->productsEntity = $entityFactory->get(ProductsEntity::class);
        $this->productsHelper = $productsHelper;
    }

    public function run($params, \Smarty_Internal_Template $smarty)
    {
        if (!empty($params['var'])) {
            $products = $this->productsHelper->getProductList($params);
            $smarty->assign($params['var'], $products);
        }
    }
}
```

#### Пример модификатора

```php
namespace Okay\Core\SmartyPlugins\Plugins;

use Okay\Core\Money;
use Okay\Core\SmartyPlugins\Modifier;

class Convert extends Modifier
{

    /** @var Money */
    private $money;
    protected $tag = 'convert';

    public function __construct(Money $money)
    {
        $this->money = $money;
    }

    public function run($price, $currency_id = null, $format = true)
    {
        return $this->money->convert($price, $currency_id, $format);
    }
}
```

#### Smarty плагины в модулях

Плагины в модулях регистрируются, также как и системные плагины, но их регистрация происходит в файле
`Okay/Core/Modules/Vendor/Module/Init/SmartyPlugins.php`.

`Best practices: реализации плагинов хранить в директории 'Okay/Core/Modules/Vendor/Module/Plugins'`


================================================================================
# FILE: di_container.md
================================================================================

# Dependency injection container

Контейнер реализовывает интерфейс [Psr\Container\ContainerInterface](https://www.php-fig.org/psr/psr-11/).
Описание всех сервисов и их зависимостей описано в файлах:
+ Okay/Core/config/services.php ([основные сервисы ядра](./core/README.md))
+ Okay/Core/config/requests.php ([сервисы Requests](./requests.md))
+ Okay/Core/config/helpers.php ([сервисы хелперов](./helpers.md))

Не допускается использования циклических зависимостей.

### Регистрация сервиса <a name="serviceRegister"></a>
Чтобы зарегистрировать сервис, нужно в одном из файлов описания сервисов добавить его.

Рассмотрим пример регистрации сервиса ядра. Регистрировать его нужно в файле Okay/Core/config/services.php
([версия для модулей](./modules/README.md#Initservices)).

Файл services.php возвращает массив с описанием сервисов, где ключ - это название сервиса, значение - описание сервиса.

`Best practices: в качестве имени сервиса использовать полное имя класса`

Пример:
```php
use Okay\Core\OkayContainer\Reference\ParameterReference as PR;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;

[
    MyClass::class => [ // Имя сервиса
        'class' => MyClass::class, // Имя класса, из которого создавать экземпляр сервиса
        'arguments' => [ // Аргументы конструктора класса MyClass. Принимать в порядке, как здесь передаём
            new SR(OtherClass::class),
            new PR('db.driver'),
        ],
    ],
];
```
Описание классов [ParameterReference](#ParameterReference) и [ServiceReference](#ServiceReference)

### <a name="GetService"></a>Получение сервиса

Чтобы получить экземпляр сервиса нужно получить его через инъекцию в [классе контроллера](./controllers.md),
или воспользоваться [локатором служб](./service_locator.md). Также при регистрации сервиса, можно указать ему 
зависимость.

#### <a name="ParameterReference"></a> ParameterReference

Класс ParameterReference нужен когда сервис зависит от параметров (конфигов).
Параметры для сервисов описываются в файле `Okay/Core/config/parameters.php`.
Параметры это многомерный ассоциативный массив, который может содержать в конечных значениях как статические значения,
так и значения из конфигурационного файла системы. Чтобы указать что нужно подставить значение из конфига,
нужно в значении параметра указать это как переменную.

Пример:
```php
$parameters = [
    'root_dir' => '{$root_dir}',
    'logger' => [
        'file' => __DIR__ . '/../../log/app.log',
    ],
    'db' => [
        'driver'   => '{$db_driver}',
        'dsn'      => '{$db_driver}:host={$db_server};dbname={$db_name};charset={$db_charset}',
        'user'     => '{$db_user}',
        'password' => '{$db_password}',
        'prefix'   => '{$db_prefix}',
        'db_sql_mode' => '{$db_sql_mode}',
        'db_timezone' => '{$db_timezone}',
        'db_names' => '{$db_names}',
    ],
];
```

Чтобы передать в сервис параметр, нужно в блоке arguments передать экземпляр класса ParameterReference (PR) 
который в конструктор принимает имя параметра-зависимости. В имени стоит через точку разделять вложенность массива
параметров. [Пример](#serviceRegister) передачи в качестве зависимости значения `$parameters['db']['driver']`.

#### <a name="ServiceReference"></a> ServiceReference

Класс ServiceReference нужен когда сервис зависит от других сервисов.
Чтобы передать сервис как зивисимость другому сервису, нужно в описании сервиса в блоке arguments передать
экземпляр класса ServiceReference (SR) который в конструктор принимает имя сервиса-зависимости 
([пример выше](#serviceRegister)).


================================================================================

# Service Locator

Локатор служб нужен для получения зависимостей, которые зарегистрированы в [DI контейнере](./di_container.md).
Если по каким-то причинам, не получается прокинуть зависимость через инъекцию зависимости, или же это не рационально 
(если зависимость нужна одному методу), можно использовать локатор служб.

Пример использования:

```php

use Okay\Core\ServiceLocator;
use Okay\Core\EntityFactory;

class SomeClass {
    public function someMethod()
    {
        $SL = ServiceLocator::getInstance();
        $entityFactory = $SL->getService(EntityFactory::class);
        //...abstract
    }
}
```


================================================================================
# FILE: dev_mode.md
================================================================================

# Режим разработчика

Режим разработчика нужен для более удобной разработки. Он добавляет на сайт вспомогательную информацию.
Для активации режима разработчика, нужно в конфиге (config/config.php) установить директиву dev_mode = true.

<a name="backendMenu"></a>
#### Меню админ-части

Для добавления пункта меню в админ-панеле, нужно воспользоваться методом 
[extendBackendMenu()](./modules/init.md#extendBackendMenu), 
Чтобы посмотреть существующие группы меню, нужно включить режим разработчика, и в админ-части к группам меню будет 
добавлено, красным цветом, название группы.

Также в меню можно добавлять счётчики новых событий. Для этого нужно использовать зелёное название счетчика для
пункта меню и воспользоваться методом [ManagerMenu::addCounter()](./core/ManagerMenu.md#addCounter)

![Пример названий пунктов меню](./images/admin_menu.png)

<a name="shortBLock"></a>
#### Шорт-блоки

Шорт-блоки в OkayCMS нужны чтобы модули могли вставлять туда свой HTML код. Админ-панель размечена шорт-блоками,
название которых всегда уникально. Модули добавляющие свою вёрстку в блок, всегда добавляют её в конец блока.
Если несколько модулей добавляется в один и тот же блок, они добавляются последовательно, как они расположены
в разделе модулей.

Также несколько блоков (но их очень ограниченое количество) есть в клиентской части сайта.

Чтобы просмотреть доступные шорт-блоки, нужно включить режим разработчика, и перейти в админ-панель сайта.
Будет видно красные надписи, при наведении на которые будет подсвечиваться граница блока.

![Пример шорт-блоков](./images/admin_short_blocks.png)

Чтобы добавить шорт-блок, нужно воспользоваться методом [Init::addBackendBlock()](./modules/init.md#addBackendBlock) или
[Init::addFrontBlock()](./modules/init.md#addFrontBlock).


================================================================================
# FILE: scheduler.md
================================================================================

# Планировщик задач

В системе присутствует планировщик задач, который позволяет настраивать выполнение определённых задач в назначенное время.

Для работы планировщика, в системный крон необходимо добавить выполнение следующей задачи:
```
* * * * * php path-to-your-project/ok scheduler:run
```
Где path-to-your-project является абсолютным путем к директории вашего проекта

Для регистрации задачи в модуле нужно использовать метод registerSchedule внутри метода Init. Метод принимает объект класса [Schedule](./core/Schedule.md).

Пример:
```php
class Init extends AbstractInit
{
    public function init()
    {
        $this->registerSchedule(
            (new Schedule([NovaposhtaCost::class, 'parseCitiesToCache']))
                ->name('Parses NP cities to the db cache')
                ->time('0 0 * * *')
                ->overlap(false)
                ->timeout(3600)
        );
    }
}
```

Логи работы можно найти в директории Okay/log/scheduler.

### Взаимодействие с планировщиком
Для взаимодействия с планировщиком необходимо использовать консольный помощник в директории проекта.

```
php ok scheduler:run [-f|--force]
```
Запускает выполнение всех задач, которые могут быть выполнены исходя из правил времени и пересечения. Если использовать ключ -f, то все задачи будут выполнены "насильно", в обход правил.

```
php ok scheduler:list
```
Выводит список всех зарегистрированных задач в табличном виде.
Пример:
```
+----+--------------------------------------+-----------+----------------------------------------------------------------------------+
| id | Name                                 | Time      | Command                                                                    |
+----+--------------------------------------+-----------+----------------------------------------------------------------------------+
| 1  | Parses NP cities to the db cache     | 0 0 * * * | Okay\Modules\OkayCMS\NovaposhtaCost\NovaposhtaCost::parseCitiesToCache     |
| 2  | Parses NP warehouses to the db cache | 0 0 * * * | Okay\Modules\OkayCMS\NovaposhtaCost\NovaposhtaCost::parseWarehousesToCache |
+----+--------------------------------------+-----------+----------------------------------------------------------------------------+
```

```
php ok scheduler:task [-f|--force] <task_id>
```
Запускает выполнение конкретной задачи если она может быть выполнена исходя из правил времени и пересечения. Принимает id задачи. Если использовать ключ -f, то задача будет выполнена "насильно", в обход правил.


================================================================================
# FILE: import.md
================================================================================

# Импорт (Import)

Из csv файлов можно призводить импорт товаров, категорий и свойств товаров.
Также можно импортировать данные для [модуля](./modules/README.md). 

### Расширение импорта из модуля
Для того чтобы дополнить список импортируемх полей, которые выводятся перечнем при запуске импорта, полем из модуля необходимо использовать шортблок import_fields_association.
Для того чтобы считать из импортируемого файла необходимую информацию можно в [модуле](./modules/README.md) реализовать [экстендер](./modules/extenders.md), который будет расширять метод parseProductData() класса BackendImportHelper.
В методе экстендера принять вторым аргументом $itemFromCsv и считать необходимую информацию.

Пример:

```php
public function extendParseProductData($product, $itemFromCsv)
{
    if (!empty($itemFromCsv['supplier'])) {
        //...abstract
    }
}
```


Для того чтобы поля модуля при импорте не добавлялись в качестве новых свойств необходимо расширить метод getModulesColumnsNames() класса BackendImportHelper.
Метод экстендера принимает в качестве аргумента массив полей из модулей и добавляет свои поля.

Пример:

```php
public function extendModulesColumnsNames($modulesColumnsNames)
{
    $modulesColumnsNames['supplier'] = 'supplier';
    return $modulesColumnsNames;
}
```
 
Для того чтобы из модуля внести изменения после импорта, необходимо расширить метод afterImportProductProcedure() класса BackendImportHelper.


================================================================================
# FILE: export.md
================================================================================

# Экспорт (Export)

Экспорт производится в файл csv. 
В дефолном функционале возможен экспорт всех товаров, товаров определенной категории или бренда.
Для того, чтобы из модуля добавить возможность экспортировать товары по какому-то своему признаку, можно расширить метод getCategoriesForExportFilter() класса BackendExportHelper и в методе своего [экстендера](./modules/extenders.md) передать в дизайн необходимую переменную.
Далее можно расширить метод setUp() класса BackendExportHelper, приняв в качестве аргумента массив. Нулевым элементом данного массива будет фильтр, по которому выбираются товары для экспорта.

 Пример:

```php
     public function extendSetUp($array)
    {
        $supplier_id = //...abstract

        $array[0] = $array[0] + ['supplier_id' => $supplier_id];
        return $array;
    }
```


Для того, чтобы отрабатывал фильтр, допленный как указано в примере в методе extendSetUp необходимо создать пользовательский фильтр для [сущности](./entities.md) ProductsEntity.  

Чтобы добвить колонки из модуля в экспорт товаров, необходимо расширить метод getColumnsNames() класса BackendExportHelper.

Пример:

```php
    public function extendExportColumnsNames($columnsNames)
    {
        $columnsNames['supplier'] = 'Supplier';
        return $columnsNames;
    }
```


================================================================================
# FILE: files.md
================================================================================

# Структура файлов системы

### Файлы админ-части <a name="backendFIles"></a>

### Файлы дизайна клиентского шаблона <a name="frontDesign"></a>


================================================================================

================================================================================
# FILE: tpl_modifiers.md
================================================================================

# Модификация tpl файлов

Помимо встраивания в [шорт-блоки](./dev_mode.md#shortBLock), в OkayCMS есть функционал модификации tpl файлов без
их изменения. Данный функционал работает так, что оригинальные файлы остаются неизмененными, но в compiled попадает файл
в измененном состоянии, будто был изменен оригинальный tpl файл.

`compiled` - это специальные php файлы, которые генерирует шаблонизатор Smarty на основе содержимого tpl файла.
Нужны они для того, чтобы интерпретатор мог обработать файл шаблона (поскольку интерпретатор php не умеет обрабатывать
напрямую tpl файлы). Располагаются они по умолчанию в директориях `compiled/<themeName>` для клиентской части и 
`bachend/design/compiled` для админ-части.

<a name="DOMNodes"></a>
#### DOM nodes

Файл разбирается на некое DOM дерево, но в качестве ноды может быть также Smarty элемент.
На данный момент из Smarty элементов в качестве ноды имеющей дочерние элементы поддерживаются только foreach, function 
и if (foreachelse и else будут просто текстовыми дочерними элементами). Все остальные Smarty элементы будут как 
текстовые ноды.

`Нода` - это элемент DOM (Document Object Model) дерева. Нода может содержать дочерние ноды, только если это не 
самозакрывающаяся (`<img>`, `<br>`, `<input>` etc) и не текстовая нода.

В качестве имени ноды используется открывающий элемент блочного тега или весь элемент, если это самозакрывающийся тег
или текстовая нода.

Например для кода:
```smarty
<div class="some-class">
    {foreach $arr as $item}
        {$item|escape}
    {/foreach}
</div>
```

Будет создано три ноды: 
Html блочная нода `<div class="some-class">` её дочерняя smarty foreach нода `{foreach $arr as $item}` 
и её дочерняя текстовая нода `{$item|escape}`.

<a name="registerModifications"></a>
#### Регистрация изменений шаблона

Все регистрации изменений шаблона производятся в блоке modifications файла [module.json](./modules/module_json.md) вашего модуля 
(файл должен располагаться в директории Init модуля).

Общая структура блока modifications:

```json
{
  "modifications": {
    "backend": [
      {
        "file": "product.tpl",
        "changes": [
          {
            "find": "{foreach $product_images as $image}",
            "closestFind": "<div class=\"row",
            "appendBefore": "{if $product->id}",
            "appendAfter": "{/if}"
          }
        ]
      }
    ],
    "front": [
      {
        "file": "product.tpl",
        "changes": [
          {
            "like": "<select .*? class=\"fn_variant variant_select .*",
            "appendBefore": "<span>appendBefore</span>",
            "html": "test.tpl"
          }
        ]
      }
    ]
  }
}
```

Блок modifications содержит два свойства backend и front. В backend описываются модификации файлов админ-части, во 
front соответственно модификации клиентской части.
Оба этих блока содержат внутри массив модификаций, каждый из которых содержит свойство file содержащее имя файла
который хотим модифицировать и свойство changes содержащее уже сами изменения.

Если файл лежит в поддиректории от стандартной директории html (например шаблоны писем) имя файла указываем как 
`email/admin_email.tpl`

Само изменение должно содержать одно из свойств find или like в котором указывается какую ноду нужно найти.

`find` ищет по вхождению подстроки в строку названия открывающей ноды.

`like` ищет по регулярному выражению строки названия открывающей ноды 
(для отладки регулярок рекомендуем сервис [regex101.com](https://regex101.com)).

После поиска элемента можно дополнительно указать свойство `parent` (без значения) для внесения изменений в 
непосредственного родителя элемента или можно указать `closestFind`/`closestLike` для поиска первого родителя, 
удовлетворяющего критериям поиска. `closestFind` и `closestLike` работают по принципу `find` и `like` но идут вверх 
по дереву относительно найденного элемента.

Также можно искать дочерние ноды относительно текущей. Для этого используйте свойство `childrenFind` или `childrenLike`,
которые также работают по принципу `find` и `like` но производят поиск первой дочерней ноды удовлетворяющей условиям поиска.

`Совет`: свойства `find`/`like`, `parent`/`closestFind`/`closestLike` и `childrenFind`/`childrenLike` можно комбинировать,
но отработают они в последовательности как указано выше.

Например: найти ноду, затем её родителя и уже внутри родителя найти другую ноду, в которую нужно внести изменения.

```json
{
  "modifications": {
    "front": [
      {
        "file": "main.tpl",
        "changes": [
          {
            "find": "{$lang->main_new_products}",
            "closestFind": "{if $new_products}",
            "childrenLike": "{foreach \\$new.+?uct",
            "prepend": "some elements"
          }
        ]
      }
    ]
  }
}
```

Для внесения самих изменений есть несколько свойств.
В качестве значения можно указать как сам код, так и имя файла в вашем модуле, где лежат изменения.
Для изменений фронтенда файл изменения должен лежать в директории `Okay/Modules/Vendor/Module/design/html/`,
для изменений бекенда файл должен находиться в директории `Okay/Modules/Vendor/Module/Backend/design/html/`.

Возможные свойства:

Свойство | Значение | Описание
---|---|---
append | текст или имя файла с содержимым | добавляет содержимое в конец указанной ноды
prepend | текст или имя файла с содержимым | добавляет содержимое в начало указанной ноды
appendBefore | текст или имя файла с содержимым | добавляет содержимое в родирельскую ноду но перед текущей
appendAfter | текст или имя файла с содержимым | добавляет содержимое в родирельскую ноду но после текущей
html | текст или имя файла с содержимым | заменяет содержимое выбраной ноды на указанное
text | текст или имя файла с содержимым | синоним html
replace | текст или имя файла с содержимым | позволяет изменить текст открывающей ноды (может понадобиться для добавления/изменения атрибутов etc)
remove | значение не принимается | удаляет текущую ноду со всеми её потомками

В примере выше показано как можно весь row в котором выводятся изображения обернуть в `{if $product->id}`.

`Важно` - после внесения изменений в блок modifications нужно очистить директорию compiled чтобы увидеть изменения.

`Совет`: для отладки модификаторов, можно в файле `config/config.php` (`config/config.local.php`) включить параметр `smarty_force_compile` чтобы файлы постоянно
перекомпилировались. Важно не забыть выключить этот параметр для production.

`Совет 2`: для более лёгкого внедрения модификаций их можно внести прямо в оригинальный файл, полностью отладить работу
модуля с этим кодом и уже после этого перенести данное изменение в файл module.json.

<a name="examples"></a>
#### Примеры использования

###### Пример №1

в файл product_list.tpl добавить к названию товара что-то

Содержимое tpl файла:

```smarty
...
<img src="{$product->image->filename|resize:300:180}" alt="{$product->name|escape}" title="{$product->name|escape}"/>
...
<div class="product_preview__name">
    {* Product name *}
    <a class="product_preview__name_link" data-product="{$product->id}" href="{url_generator route="product" url=$product->url}">
        {$product->name|escape}
    </a>
</div>
...
``` 

Как видим, искать ноду через find по содержимому "{$product->name|escape}" нельзя, т.к. под поиск подпадёт и изображение
товара, здесь два варианта решения: искать через родителя или по регулярному выражению.

```json
{
  "modifications": {
    "front": [
      {
        "file": "product_list.tpl",
        "changes": [
          {
            "find": "product_preview__name_link",
            "childrenFind": "{$product->name|escape}",
            "appendAfter": "Добавили через родителя"
          },
          {
            "like": "^\\s+?{\\$product->name\\|escape}",
            "appendAfter": "Нашли по регулярному выражению"
          }
        ]
      }
    ]
  }
}
```

###### Пример №2

Добавить в файл products_sort.tpl ещё одну кнопку сортировки. Располагаться она должна перед сортировкой по цене.

Содержимое tpl файла `products_sort.tpl`:

```smarty
...
<div class="fn_ajax_buttons d-flex flex-wrap align-items-center products_sort">
    <span class="product_sort__title hidden-sm-down" data-language="products_sort_by">{$lang->products_sort_by}:</span>

    <form class="product_sort__form" method="post">
        <button type="submit" name="prg_seo_hide" class="d-inline-flex align-items-center product_sort__link{if $sort=='position'} active_up{/if} no_after" value="{furl sort=position page=null absolute=1}">
            <span data-language="products_by_default">{$lang->products_by_default}</span>
        </button>
    </form>

    <form class="product_sort__form" method="post">
        <button type="submit" name="prg_seo_hide" class="d-inline-flex align-items-center product_sort__link{if $sort=='price'} active_up{elseif $sort=='price_desc'} active_down{/if}" value="{if $sort=='price'}{furl sort=price_desc page=null absolute=1}{else}{furl sort=price page=null absolute=1}{/if}">
            <span data-language="products_by_price">{$lang->products_by_price}</span>
            {include file="svg.tpl" svgId="sort_icon"}
        </button>
    </form>
...
``` 

Здесь будем искать кнопку сортировки по цене, затем её родителя с классом "product_sort__form" и перед ним вставлять
нашу кнопку. Сама кнопка будет храниться в файле `Okay/Modules/<Vendor>/<Module>/design/html/sort_button.tpl` в виде
tpl кода:

```smarty
<form class="product_sort__form" method="post">
    <button type="submit" name="prg_seo_hide" class="d-inline-flex align-items-center product_sort__link {if $sort=='my_sort'} active_up{elseif $sort=='my_sort_desc'} active_down{/if}" value="{if $sort=='my_sort'}{furl sort=my_sort_desc page=null absolute=1}{else}{furl sort=my_sort page=null absolute=1}{/if}">
        <span>Моя сортировка</span>
        {include file="svg.tpl" svgId="sort_icon"}
    </button>
</form>
```

Регистрация изменения будет выглядеть так:

```json
{
  "modifications": {
    "front": [
      {
        "file": "products_sort.tpl",
        "changes": [
          {
            "find": "data-language=\"products_by_price\"",
            "closestFind": "class=\"product_sort__form\"",
            "appendBefore": "sort_button.tpl"
          }
        ]
      }
    ]
  }
}
```


================================================================================
# FILE: modules/README.md
================================================================================

# Создание модуля

Модули позволяют расширять функционал OkayCMS и вмешиваться в стандартный ход выполнения различных операций.
Каждый модуль включает в себя классы контроллеров, моделей, шаблоны отображения, изображения, CSS стили,
JS файлы, языковые файлы.
Такая инкапсуляция позволяет легко переносить модуль между приложениями на платформе OkayCMS.
Модуль в OkayCMS - это папка с определенной структурой данных внутри. 
Модуль обязательно должен располагаться в каталоге для модулей Okay/Modules/VendorModule/NameModule/
и иметь следующую структуру.

##### Структура файлов модуля

    .
    ├── Init
    |   ├── Init.php
    |   ├── module.json
    |   ├── routes.php
    |   ├── SmartyPlugins.php
    |   └── services.php
    ├── Backend
    |   ├── Controllers
    |   |   └── Файлы контроллеров бекенда модуля
    |   ├── design
    |   |   ├── html
    |   |   |   └── Файлы дизайна бекенда
    |   |   ├── css
    |   |   |   └── Файлы стилей бекенда
    |   |   ├── js
    |   |   |   └── Файлы стилей бекенда
    |   |   ├── images
    |   |   |   └── Файлы изображений бекенда
    |   |   ├── css.php
    |   |   └── js.php
    |   └── lang
    |       └── Файлы переводов бекенда
    ├── config
    |   └── config.php
    ├── Controllers
    |   └── Файлы контроллеров модуля
    ├── Entities
    |   └── Файлы сущностей модуля
    ├── Extenders
    |   └── Файлы экстендеров модуля
    ├── design
    |   ├── html
    |   |   └── Файлы дизайна
    |   ├── css
    |   |   └── Файлы стилей модуля
    |   ├── js
    |   |   └── Файлы скриптов модуля
    |   ├── images
    |   |   └── Файлы изображений модуля
    |   ├── lang
    |   |   └── Файлы переводов клиентской части модуля
    |   ├── css.php
    |   └── js.php
    ├── settings.xml
    └── preview.(jpeg|jpg|png|gif|svg)

##### Конфигурационные файлы модуля <a name="configuratinFiles"></a>

Файл `Init/Init.php` является самым главным конфигурационным файлом. Он обязательно должен унаследоваться от 
Okay\Core\Modules\AbstractInit.
В классе Init должны быть реализованы методы install() и init(). Базовый класс AbstractInit предоставляет средства
для инициализации модуля в системе. Метод install() выполняется один раз, во время установки модуля, метод init() 
вызывается при каждом запуске системы.
В методе install() стоит вызывать такие методы как setBackendMainController(), 
[migrateEntityTable()](./table_migrate.md), [setModuleType()](#typesOfModules).
[Пример инициализации модуля](./quick_start.md#InitInitphp) и [полное описание инициализации](./init.md).

Файл `Init/module.json` Файл содержащий мета информацию об модуле. [Подробнее](./module_json.md)

Файл `Init/routes.php` содержит роуты для текущего модуля. Структура файла полностью повторяет структуру 
[системных роутов](./../routes.md)

Файл `Init/services.php` <a name="Initservices"></a> содержит сервисы для текущего модуля.
Регистрация сервисов в модуле осуществляется также как и [системные сервисы](./../di_container.md#serviceRegister),
но в файле Init/services.php.
Все они должны быть частью [DI контейнера](./../di_container.md "Dependency injection container").

Файл `Init/SmartyPlugins.php` содержит Smarty плагины для текущего модуля.
Регистрация плагинов в модуле осуществляется также как и [системные Smarty плагины](./../smarty_plugins.md),
но в файле Init/SmartyPlugins.php.

Файл `preview.(jpeg|jpg|png|gif|svg)` может присутствовать в корневой директории модуля, он будет автоматически
отображаться в админ-панеле, в списке модулей.

Файл `settings.xml` нужен для модулей доставок и оплат. Файл содержит структуру настроек, которые нужно вывести
в админ-панели в способе доставки или способе оплаты. (Используется при [установке типа модуля](#typesOfModules)
MODULE_TYPE_PAYMENT или MODULE_TYPE_DELIVERY).

Файлы `design/js.php`, `design/css.php`, `Backend/design/js.php` и `Backend/design/css.php` нужны для [регистрации js
и css файлов](./../js_css_files.md).

Структура файла:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<module>
    <settings><!--Если указать больше одного <options> будет выведено как HTML select (выпадающий список)-->
        <variable>service_type</variable><!--Название переменной-->
        <name>{$lang->settings_np_service_type}</name><!--Название параметра (поддерживается из переводов)-->
        <options>
            <name>{$lang->settings_np_service_dd}</name>
            <value>DoorsDoors</value>
        </options>
        <options>
            <name>{$lang->settings_np_service_wd}</name>
            <value>WarehouseDoors</value>
        </options>
    </settings>
    <settings><!--Так будет выведено текстовое поле-->
        <variable>wayforpay_merchant</variable>
        <name>{$lang->way_for_pay_merchant}</name>
    </settings>
    <settings type="hidden|text|date|checkbox"><!--Так будет выведено как инпут указанного типа-->
        <variable>wayforpay_merchant</variable>
        <name>{$lang->way_for_pay_merchant}</name>
    </settings>
</module>
```

Файл `config/config.php` может содержать директивы, такие же как и в системном конфиге. Можно только добавлять
директивы, переопределять системные директивы нельзя.

##### Типы модулей <a name="typesOfModules"></a>

Тип модуля может влиять на некоторое его поведение в системе. На данный момент существуют такие типы модулей:
* MODULE_TYPE_PAYMENT - модуль оплаты
* MODULE_TYPE_DELIVERY - модуль доставки
* MODULE_TYPE_XML - модуль создающий выгрузку в xml файл.

Тип модуля можно установить в методе install() класса Init с помощью метода setModuleType (всегда используйте константы начинающиеся
на `MODULE_TYPE_`)
```php
$this->setModuleType(MODULE_TYPE_DELIVERY);
```
Например модуль с типом MODULE_TYPE_DELIVERY будет выводить настройки, определённые в файле `settings.xml` 
в админ-панеле в способе доставки. Также этот модуль будет выводиться в списке доступных модулей доставки 
[в настройках способа доставки](https://demookay.com/backend/index.php?controller=DeliveryAdmin).

[Модуль, быстрый старт](./quick_start.md)


================================================================================
# FILE: modules/quick_start.md
================================================================================

# Создание модуля

Рассмотрим пример созданим модуля FAQ разработчика OkayCMS.
Все действия в этом гайде (если инного не указанно) выполняются в директории `Okay/Modules/OkayCMS/FAQ`
(и в namespace `Okay\Modules\OkayCMS\FAQ`).

##### Инициализация модуля <a name="InitInitphp"></a>

Для начала нужно создать класс `Init\Init`, в котором нужно описать установку и инициализацию модуля.
В методе install() выполняем [миграцию таблицы для модуля](./table_migrate.md#migrateEntityTable).
и прочие первоначальные настройки. [Подробнее о классе Init](init.md).
```php
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
```

* В методе init() выполняем настройку работы модуля.
* Регистрируем [бек-контроллеры](./../controllers.md#backendControllersModules), и указываем их разрешения для менеджера.
```php
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
```

* Далее создаем директорию `Backend` и в ней создаём [контроллеры](./../controllers.md#backendControllersModules), 
файлы шаблона, стили, переводы, в точно такой же структуре [как в стандартном OkayCMS](./../files.md#backendFIles)
* Создаем [фронт-контроллеры](./../controllers.md#frontControllersModules)
* Создаем дизайн модуля, который распологается в директории `design` и повторяет 
[стандартный дизайн OkayCMS](./../files.md#frontDesign)
* Описываем классы [Entity](./../entities.md) модуля
* Создаем файл `Init/routes.php` в котором [описываем маршруты](./../routes.md)
* Переходим в админ-часть сайта, в раздел модулей и напротив этого модуля нажимаем установить.


================================================================================
# FILE: modules/init.md
================================================================================

# Инициализация модуля (класс Init)

Класс `Init\Init` является самым главным конфигурационным классом. Он обязательно должен наследоваться от 
`Okay\Core\Modules\AbstractInit`.
В классе `Init\Init` должны быть реализованы методы install() и init(). Базовый класс `Okay\Core\Modules\AbstractInit` 
предоставляет средства для инициализации модуля в системе. 
Метод install() выполняется один раз, во время установки модуля, метод init() вызывается при каждом запуске системы.

## Обновление модуля

Опционально в классе `Init\Init` можно описывать методы, с названием вида `update_1_2_0()`. Данные методы будут 
выполняться при обновлении модуля в порядке возрастания версии. Когда установленная в системе версия модуля ниже чем 
указанная в [module.json](./module_json.md) в свойстве version, в списке модулей в админ части предлагается его обновить.
Когда пользователь нажмет обновить модуль, выполнятся все методы, для версии модуля выше текущей установленной и ниже
версии, указанной в свойстве version файла [module.json](./module_json.md).

Для получения зависимостей в методах обновления можно использовать [локатор служб](./../service_locator.md).

Для выполнения SQL запросов нужно получить экземпляр одного из классов `Okay\Core\QueryFactory\Insert`, 
`Okay\Core\QueryFactory\Select`, `Okay\Core\QueryFactory\Update` , `Okay\Core\QueryFactory\Delete` 
 или `Okay\Core\QueryFactory\SqlQuery`.

### Методы класса AbstractInit

<a name="registerChainExtension"></a>
```php
registerChainExtension( array $expandable, array $extension)
```
Регистрирует [экстендер](./extenders.md) в режиме Chain.
Вызывать в методе `init()`.

Аргумент | Описание
---|---
$expandable | массив из двух элементов, имени класса [хелпера](./../helpers.md) или [реквеста](./../requests.md) и его метода, который нужно расширить.
$extension | массив из двух элементов, имени класса [экстендера](./extenders.md) и его метода, каким нужно расширить метод хелпера.


<a name="registerQueueExtension"></a>
```php
registerQueueExtension( array $expandable, array $extension)
```

Регистрирует [экстендер](./extenders.md) в режиме Queue.
Вызывать в методе `init()`.

Аргумент | Описание
---|---
$expandable | массив из двух элементов, имени класса [хелпера](./../helpers.md) или [реквеста](./../requests.md) и его метода, который нужно расширить.
$extension | массив из двух элементов, имени класса [экстендера](./extenders.md) и его метода, каким нужно расширить метод хелпера.


<a name="migrateEntityTable"></a>
```php
migrateEntityTable( string $entityClassName, array $fields)
```

Создание таблицы нового [Entity](./../entities.md) модуля. [Пример миграции](./table_migrate.md). 
Вызывать в методе `install()`.

Аргумент | Описание
---|---
$entityClassName | полное имя класса Entity
$fields | массив экземпляров класса [Okay\Core\Modules\EntityField](./table_migrate.md#EntityField)


<a name="migrateEntityField"></a>
```php
migrateEntityField( string $entityClassName, EntityField $field)
```

Добавление дополнительных полей в БД к существующим [сущностям](./../entities.md). Вызывать в методе `install()`.

Аргумент | Описание
---|---
$entityClassName | полное имя существующего класса Entity
$field | экземпляр класса [Okay\Core\Modules\EntityField](./table_migrate.md#EntityField)


<a name="migrateCustomTable"></a>
```php
migrateCustomTable( string $tableName, array $fields)
```

Создание таблицы в БД. В основном используется для создания таблиц связей. Вызывать в методе `install()`.

Аргумент | Описание
---|---
$tableName | название таблицы, которую нужно создать (без префиксов "ok_" или "__")
$fields | массив экземпляров класса [Okay\Core\Modules\EntityField](./table_migrate.md#EntityField)


<a name="registerEntityField"></a>
```php
registerEntityField( string $entityClassName, string $fieldName[, bool $isLang = false])
```

Регистрация дополнительных полей к существующим сущностям.
В базу не добавляются, только учавствуют в селекте и фильтрации. Вызывать в методе `init()`.

Аргумент | Описание
---|---
$entityClassName | Полное имя класса существующего [Entity](./../entities.md)
$fieldName | Название колонки, которую стоит добавить в [Entity](./../entities.md)
$isLang | является ли это поле ленговым


<a name="registerEntityFilter"></a>
```php
registerEntityFilter( string $entityClassName, string $filterName, string $filterClassName, string $filterMethod)
```

Регистрация [пользовательского фильтра для уже существующих](./../entities.md#usersFiltersFromModules) в 
системе [Entities](./../entities.md). Вызывать в методе `init()`.

Аргумент | Описание
---|---
$entityClassName | Полное имя класса существующего [Entity](./../entities.md), для которого регистрируется новый фильтр
$filterName | Имя нового фильтра, которое будет использоваться в массиве совместно с остальными фильтрами
$filterClassName | Класс, в котором описана реализация нового фильтра
$filterMethod | Метод описывающий реализацию нового фильтра


<a name="registerBackendController"></a>
```php
registerBackendController( string $controllerClass)
```

Добавление [бек-контроллера](./../controllers.md#backendControllersModules) в общий список контроллеров. 
Вызывать в методе `init()`.

Аргумент | Описание
---|---
$controllerClass | Имя класса бек-контроллера


<a name="setBackendMainController"></a>
```php
setBackendMainController( string $className)
```

Установка [бек-контроллер](./../controllers.md#backendControllersModules), который будет в админке обрабатываться как 
основной (когда со списка модулей происходит переход внутрь модуля, попадаем на этот контроллер).
Вызывать в методе `install()`.

Аргумент | Описание
---|---
$className | Имя класса бек-контроллера


<a name="addBackendControllerPermission"></a>
```php
addBackendControllerPermission( string $controllerClass, string $permission)
```

Добавление связки разрешения для админа и [бек-контроллера](./../controllers.md#backendControllersModules).
Вызывать в методе `init()`.

Аргумент | Описание
---|---
$controllerClass | Имя класса бек-контроллера
$permission | Название разрешения


<a name="addPermission"></a>
```php
addPermission( string $permission)
```

Добавление разрешения, в общий массив разрешений для менеджеров.
Нужно использовать если нужно разрешение, но [бек-контроллера](./../controllers.md#backendControllersModules) 
для него нет. Вызывать в методе `init()`.

Аргумент | Описание
---|---
$permission | Название разрешения


<a name="setModuleType"></a>
```php
setModuleType( string $type)
```

Установка [типа модуля](./README.md#typesOfModules). Вызывать в методе `install()`.

Аргумент | Описание
---|---
$type | Тип модуля. Константы типов начинаются на MODULE_TYPE_. [Типы модулей](./README.md#typesOfModules).


<a name="extendBackendMenu"></a>
```php
extendBackendMenu( string $firstLevelName, array $menuItemsByControllers[, string $icon = null])
```

Добавить новый пункт
меню в админ-части.
Вызывать в методе `init()`.

Аргумент | Описание
---|---
$firstLevelName | [Название группы меню](./../dev_mode.md#backendMenu), в которую стоит добавить новый пункт. Если указать несуществующую группу, тогда создастся новая.
$menuItemsByControllers | Массив, в котором ключ является названием пункта меню, и должен быть перевод с таким же названием. В виде значения должен быть массив названий [бек-контроллеров](./../controllers.md#backendControllersModules), которые будут в этом пункте меню (обычно это контроллер списка записей и редактирования одной записи).
$icon | Иконка группы меню. Стоит использовать если создаёте новую группу. В виде значения может быть код SVG изображения, или же путь к изображению, относительно директории `Okay/Modules/Vendor/Module/` (напр. 'Backend/design/images/menu_logo.png').

Если указать новый пункт меню, нужно обязательно добавить перевод для админ части, с таким же названием как и пункт 
меню.

Пример Init:
```php
$this->extendBackendMenu('left_faq_title', [
    'left_faq_menu_item' => ['FAQsAdmin', 'FAQAdmin']
],
'Backend/design/images/faq_icon.png');
```
Переводы:
```php
$lang['left_faq_title'] = 'FAQ';
$lang['left_faq_menu_item'] = 'FAQ Item';
```


<a name="addResizeObject"></a>
```php
addResizeObject( string $originalImgDirDirective, string $resizedImgDirDirective)
```

Добавление ресайза сущностей.
Если ваш модуль подразумевает что будут нарезаться изображения, которых в системе ранее не было, то нужно добавить в 
систему информацию об этом. Не забыть в таком случае еще в методе install создать директорию для изображений 
(через функцию mkdir()).
Вызывать в методе `init()`.

Аргумент | Описание
---|---
$originalImgDirDirective | Название директивы из [конфига модуля](./README.md), которая содержит путь к директории оригиналов изображений
$resizedImgDirDirective | Название директивы из [конфига модуля](./README.md), которая содержит путь к директории нарезок изображений

Пример Init:
```php
class Init extends AbstractInit
{   
    public function install()
    {
        if (!is_dir('files/originals/slides')) {
            mkdir('files/originals/slides');
        }
        
        if (!is_dir('files/resized/slides')) {
            mkdir('files/resized/slides');
        }
        // ...abstract
    }
    
    public function init()
    {
        // ...abstract
        $this->addResizeObject('banners_images_dir', 'resized_banners_images_dir');
    }
}
```
Пример конфига:
```ini
banners_images_dir = files/originals/slides/
resized_banners_images_dir = files/resized/slides/
```


<a name="extendUpdateObject"></a>
```php
extendUpdateObject( string $alias, string $permission, string $entityClassName)
```

Метод расширяет коллекцию объектов 
доступную для использования в файле ajax/update_object.php, который обновляет определенную по алиасу сущность 
повредством AJAX запроса из админ панели сайта.

Аргумент | Описание
---|---
$alias | Уникальный псевдоним, который идентифицирует сущность (указывается в атрибуте data-controller="алиас" тега в админ панели)
$permission | Название разрешения доступа к псевдониму для менеджера, добавленые через [addBackendControllerPermission](#addBackendControllerPermission) или [addPermission](#addPermission)
$entityClassName | Полное имя [сущности](./../entities.md), которая будет обновляться.

Пример Init:
```php
class Init extends AbstractInit
{
    const PERMISSION = 'okaycms_banners';
    // ...abstract
    public function init()
    {
        // ...abstract
        $this->addBackendControllerPermission('BannersAdmin', self::PERMISSION);
        $this->extendUpdateObject('okay_cms__banners', self::PERMISSION, BannersEntity::class);
    }
}
```

Пример banners.tpl (добавляем data-controller):
```smarty
// ...abstract
{foreach $banners as $banner}
    <div class="fn_row okay_list_body_item fn_sort_item">
        <div class="okay_list_row">
            <div class="okay_list_boding okay_list_features_name">
                <a class="link" href="{url controller=[OkayCMS,Banners,BannerAdmin] id=$banner->id return=$smarty.server.REQUEST_URI}">
                    {$banner->name|escape}
                </a>
            </div>
            // ...abstract
            <div class="okay_list_boding okay_list_status">
                {*visible*}
                <div class="col-lg-4 col-md-3">
                    <label class="switch switch-default">
                        <input class="switch-input fn_ajax_action {if $banner->visible}fn_active_class{/if}" data-controller="okay_cms__banners" data-action="visible" data-id="{$banner->id}" name="visible" value="1" type="checkbox"  {if $banner->visible}checked=""{/if}/>
                        <span class="switch-label"></span>
                        <span class="switch-handle"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
{/foreach}
// ...abstract
```

<a name="addBackendBlock"></a>
```php
addBackendBlock( string $blockName, string $blockTplFile, callable $callback = null)
```

Добавление [шорт-блока](./../dev_mode.md#shortBLock)
в админ-панель сайта.

Аргумент | Описание
---|---
$blockName | Имя [шорт-блока](./../dev_mode.md#shortBLock) админ-панели.
$blockTplFile | Путь к tpl файлу (относительно директории `Okay/Modules/Vendor/Module/Backend/design/html/`), в котором размещается верстка блока. В блоке работаем, как будто его добавят в основной файл через include (все переменные поддерживаются).
$callback | Ф-ция которую нужно вызвать перед отрисовкой шортблока. Может использоваться для передачи в дизайн данных, нужных для отрисовки шортблока. Можно указывать как аргументы с указанием type hint Services, Entities etc.


<a name="addFrontBlock"></a>
```php
addFrontBlock( string $blockName, string $blockTplFile, callable $callback = null)
```

Добавление [шорт-блока](./../dev_mode.md#shortBLock)
на клиентскую часть сайта.

Аргумент | Описание
---|---
$blockName | Имя [шорт-блока](./../dev_mode.md#shortBLock) клиентской части сайта.
$blockTplFile | Путь к tpl файлу (относительно директории `Okay/Modules/Vendor/Module/design/html/`), в котором размещается верстка блока. В блоке работаем, как будто его добавят в основной файл через include (все переменные поддерживаются).
$callback | Ф-ция которую нужно вызвать перед отрисовкой шортблока. Может использоваться для передачи в дизайн данных, нужных для отрисовки шортблока. Можно указывать как аргументы с указанием type hint Services, Entities etc.


<a name="registerPurchaseDiscountSign"></a>
```php
registerPurchaseDiscountSign( string $sign, string $name, string $description)
```

Регистрация знака скидки для позиции.\
Вызывать в методе `init()`.

Аргумент | Описание
---|---
$sign|Знак скидки. Должен быть уникальным в рамках обеих сущностей(корзина и позиция корзины).
$name|Название скидки. Необходимо для подсказки администратору. Представляет собой языковую переменную.
$description|Описание скидки. Необходимо для подсказки администратору. Представляет собой backend языковую переменную.


<a name="registerCartDiscountSign"></a>
```php
registerCartDiscountSign( string $sign, string $name, string $description)
```

Регистрация знака скидки корзины.\
Вызывать в методе `init()`.

Аргумент | Описание
---|---
$sign|Знак скидки. Должен быть уникальным в рамках обеих сущностей(корзина и позиция корзины).
$name|Название скидки. Необходимо для подсказки администратору. Представляет собой языковую переменную.
$description|Описание скидки. Необходимо для подсказки администратору. Представляет собой backend языковую переменную.


================================================================================
# FILE: modules/module_json.md
================================================================================

# module.json

Файл module.json предназначен для добавления модулю сопроводительной мета-информации такой, как версия модуля,
контактный данные разработчика, [модификации tpl файлов](./../tpl_modifiers.md).

Располагается данный файл в директории [Init](./init.md) вашего модуля. Файл должен содержать json описание модуля.
Обязательные директивы:

Директива | Описание
---|---
version | Текущая версия модуля. Если версия у модуля не указана, считается что это версия 1.0.0
vendor->email | Контактный e-mail разработчика.
vendor->site | Сайт разработчика.

Опциональные директивы:

Директива | Описание
---|---
modifications | Содержит [модификации tpl фалов](./../tpl_modifiers.md) модуля.

### Версионирование модуля

Версии модуля должны состоять из трёх целых чисел, разделённых точкой. Первой считается версия 1.0.0

Если изменения модуля касаются только небольших фиксов работы, увеличивается третья цифра версии на единицу.

Если изменения касаются уже логики работы модуля или модуль не совместим с предыдущими версиями окая, нужно инкрементировать на единицу вторую цифру, а третью сбросить в 0. 

Если полностью переделывается модуль, нужно инкрементировать первую цифру, а вторую и третью сбросить в 0.


================================================================================
# FILE: modules/extenders.md
================================================================================

# Extenders

Классы-расширители нужны чтобы расширять функциональность стандартных [хелперов](./../helpers.md),
[реквестов](./../requests.md), [Entities](./../entities.md) или [сервисов ядра](./../core/README.md).
Расширять можно те методы, в которых есть вызов метода `Okay\Core\Modules\Extender\ExtenderFacade::execute()`.
Хелперы и реквесты покрыты максимальным количеством экстендеров. Entities по умолчанию покрыты только стандартные
CRUD операции (у некоторых Entities могут быть дополнительные методы покрыты экстендерами). Классы ядра покрыты 
небольшим количеством экстендеров, только там, где может потребоваться вмешательство из модуля

Экстендеры могу работать как в режиме ChainExtender (цепочный вызов)так и QueueExtender (поочерёдный вызов).

Экстендеры, которые работают в режиме Chain, передают друг другу модифицированный результат.
Они ОБЯЗАТЕЛЬНО должны возвращать результат, который передал вышестоящий хелпер или экстендер.

Например: есть метод CommentsHelper::getList(), он возвращает массив комментариев.
Есть два модуля, которые расширяют функциональность этого метода.
Сразу отработает метод CommentsHelper::getList(), который возвращает результат.
Затем отработает Module1Extender::getList($result), который может изменить данные в $result и ОБЯЗАТЕЛЬНО
должен вернуть $result, чтобы он передался в Module2Extender::getList($result) и соответственно вернулся
в место, где его вызвали (чаще всего в контроллере).

Экстендеры работающие в режиме Queue, ничего не возвращают. Они просто вызываются по очереди.
В них можно описывать какие-то процедуры, которые не модифицируют данные возвращаемые хелпером.

### Аргументы экстендера

В экстендере аргументы нужно принимать по типу 1+N. Т.е. первым аргументом экстендера будет значение, возвращаемое
хелпером, вторым аргументом экстендера будет первый аргумент хелпера (или реквеста, одно и то же).

Если в хелпере аргумент объявлен как не обязательный, в экстендере его тоже нужно объявлять необязательным. 

Пример хелпера:
```php
use Okay\Core\Validator;
//...abstract
class ValidateHelper
{

    //...abstract 
    /** @var Validator  */
    private $validator;
    //...abstract 

    public function getFeedbackValidateError($feedback)
    {
        $error = null;
        if (!$this->validator->isName($feedback->name, true)) {
            $error = 'empty_name';
        } elseif (!$this->validator->isEmail($feedback->email, true)) {
            $error = 'empty_email';
        } else {
            //...abstract 
        }
    
        return ExtenderFacade::execute(__METHOD__, $error, func_get_args());
    }
}
```
Он принимает как аргумент $feedback, который сформировал [CommonRequest](./../requests.md) и возвращает строку,
с именем ошибки.

Пример экстендера для данного хелпера:
```php
namespace Okay\Modules\Vendor\Module\Extenders;

use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Request;use Okay\Core\Validator;

class FrontExtender implements ExtensionInterface
{
    
    private $request;
    private $validator;
    
    public function __construct(Request $request, Validator $validator)
    {
        $this->request = $request;
        $this->validator = $validator;
    }

    public function getFeedbackValidateError($error, $feedback)
    {
        if ($error == 'empty_email' && $this->validator->isEmail($feedback->email)) { // Перевалидируем поле email
            $error = '';
        }

        if (!$this->validator->isPhone($feedback->phone, true)) {
            $error = 'empty_phone';
        }
        return $error;
    }
}
```
Допустим нам нужно сделать чтобы поле email стало необязательным, а телефон обязательным.

Пример регистрации:
```php
$this->registerQueueExtension(
    ['class' => ValidateHelper::class, 'method' => 'getFeedbackValidateError'],
    ['class' => FrontExtender::class, 'method' => 'getFeedbackValidateError']
);
```

### Регистрация экстендера <a name="registerExtender">
Чтобы зарегистрировать экстендер, нужно описать его в классе.
`Best practices: Описывать экстендеры в классах Okay\Modules\Vendor\Module\Extenders\FrontExtender 
и Okay\Modules\Vendor\Module\Extenders\BackendExtender`
Класс экстендера должен реализовывать интерфейс `Okay\Core\Modules\Extender\ExtensionInterface`.
Если класс экстендера содержит зависимости,
нужно его объявить как [сервис в DI контейнере](./../di_container.md#serviceRegister) или же использовать в методе
экстендера [ServiceLocator](./../service_locator.md).

Пример класса экстендера:
```php
namespace Okay\Modules\Vendor\Module\Extenders;

use Okay\Core\Design;
use Okay\Core\Modules\Extender\ExtensionInterface;

class FrontExtender implements ExtensionInterface
{
    private $design;
    
    public function __construct(Design $design)
    {
        $this->design = $design;
    }

    public function extenderMethod()
    {
        //...abstract
        $this->design->assign('param', 'value');
    }
}
```

Чтобы зарегистрировать экстендер к выполнению после определённого метода хелпера, нужно в методе Init::Init()
вызвать метод registerChainExtension() или registerQueueExtension() в соответствии с нуждами.

Пример инициализации:
```php
$this->registerQueueExtension(
    ['class' => MainHelper::class, 'method' => 'commonAfterControllerProcedure'],
    ['class' => FrontExtender::class, 'method' => 'assignCurrentBanners']
);
```
Теперь метод FrontExtender::assignCurrentBanners() будет выполняться 
после метода MainHelper::commonAfterControllerProcedure().

#### Как определить какой метод какого хелпера нужно расширять?
Чтобы определить какой метод нужно расширять, нужно зайти в контроллер, и посмотреть какой хелпер используется в месте,
которое вы хотите расширить.

Пример задачи:
При добавлении комментария пользователем на сайт, если пользователь залогинен в личном кабинете и у него в профиле
указан номер телефона, нужно отправить ему сообщение в телеграмм "Спасибо за отзыв..."

Решение:
Смотрим на контроллер BlogController и ProductController, видим что для добавления комментария используется
один и тот же хелпер CommentsHelper в котором вызывается метод addCommentProcedure().

```php
$commentsHelper->addCommentProcedure('product', $product->id);
```

Следовательно в модуле нужно расширить метод addCommentProcedure() хелпера CommentsHelper;

Пишем экстендер:
```php
namespace Okay\Modules\Vendor\Module\Extenders;

use Okay\Core\Design;
use Okay\Core\Modules\Extender\ExtensionInterface;

class FrontExtender implements ExtensionInterface
{
    private $design;
    private $telegramNotify;
    
    public function __construct(Design $design, TelegramNotify $telegramNotify)
    {
        $this->design = $design;
        $this->telegramNotify = $telegramNotify;
    }

    public function sendTelegramMessage()
    {
        if (($user = $this->design->getVar('user')) && !empty($user->phone)) {
            $this->telegramNotify->sendCommentsThanks($user->phone);
        }
    }
}
```
Как внутренне будет устроен класс TelegramNotify и метод sendCommentsThanks() зависит уже от разработчика. Но пример его
использования таков.

Объявляем класс FrontExtender в Okay/Modules/Vendor/Module/services.php:
```php
namespace Okay\Modules\Vendor\Module;

return [
    Extenders\FrontExtender::class => [
        'class' => Extenders\FrontExtender::class,
        'arguments' => [
            new SR(Design::class),
            new SR(TelegramNotify::class),
        ],
    ],
    TelegramNotify::class => [
        'class' => TelegramNotify::class,
        'arguments' => [
            //...abstract
        ],
    ],
];
```

Далее инициализируем выполнения этого экстендера:
```php
$this->registerQueueExtension(
    ['class' => CommentsHelper::class, 'method' => 'addCommentProcedure'],
    ['class' => FrontExtender::class, 'method' => 'sendTelegramMessage']
);
```


================================================================================
# FILE: modules/table_migrate.md
================================================================================

# Миграции БД модулей

Миграции для модулей могут быть трёх видов:
* [Добавление поля к существующему классу Entity](#migrateEntityField)
* [Создание новой таблицы для Entity модуля](#migrateEntityTable)
* [Создание новой таблицы связи](#migrateCustomTable)

#### Добавление поля к существующему классу Entity <a name="migrateEntityField"></a>
Чтобы добавить новое поле к существующему классу [Entity](./../entities.md),
нужно в методе [install() класса Init](./README.md#configuratinFiles) вызвать метод migrateEntityField(),
который принимает два параметра:
* Имя класса Entity, к которому нужно добавить поле
* Экземпляр класса [Okay\Core\Modules\EntityField](#EntityField)

Пример:
```php
$this->migrateEntityField(VariantsEntity::class, (new EntityField('field_name'))->setTypeVarchar(255)->setIndex());
```

Также при добавлении поля к уже существующим сущностям, нужно его зарегистрировать в системе, чтобы оно учавствовало
в SELECT и фильтрации ([подробнее об Entities](./../entities.md)).

Пример:
```php
$this->registerEntityField(VariantsEntity::class, 'field_name');
```
Это тоже самое, если бы это поле было прописано в одно из свойств класса VariantsEntity
```php
use Okay\Core\Entity\Entity;

class VariantsEntity extends Entity
{
    protected static $fields = [
        'id',
        'product_id',
        'sku',
        'price',
    ];
    
    protected static $additionalFields = [
        '(v.stock IS NULL) as infinity',
        'c.rate_from',
        'c.rate_to',
    ];
    
    protected static $langFields = [
        'name',
        'units',
    ];
    //...abstract
}
```

#### Создание новой таблицы для Entity <a name="migrateEntityTable"></a>
Чтобы создать таблицу для нового Entity (который добавляет модуль),
нужно в методе [install() класса Init](./README.md#configuratinFiles) вызвать метод migrateEntityTable(),
который принимает два параметра:
* Имя класса Entity, к которому нужно добавить поле
* Массив экземпляров класса [Okay\Core\Modules\EntityField](#EntityField)

В массиве полей, нужно описать каждое поле, которое объявлено в Entity модуля.

Пример:
```php
$this->migrateEntityTable(NPCostDeliveryDataEntity::class, [
    (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
    (new EntityField('order_id'))->setTypeInt(11)->setIndex(),
    (new EntityField('city_id'))->setTypeVarchar(255, true),
    (new EntityField('warehouse_id'))->setTypeVarchar(255, true),
    (new EntityField('delivery_term'))->setTypeVarchar(8, true),
    (new EntityField('redelivery'))->setTypeTinyInt(1, true),
    (new EntityField('name'))->setTypeVarchar(255)->setIsLang(),
]);
```

<a name="compositeIndex"></a>

Чтобы создать составной индекс, нужно в метод setIndex() или setIndexUnique() передать в виде второго и последующих 
аргументов поля (объекты класса EntityField), по которым в паре с текущим полем должен быть составной индекс.

Пример:

```php
$cityIdField = (new EntityField('city_id'))->setTypeVarchar(255, true);

$this->migrateEntityTable(NPCostDeliveryDataEntity::class, [
    (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
    (new EntityField('order_id'))->setTypeInt(11)->setIndex(null, $cityIdField),
    $cityIdField,
]);
```

Таким образом будет создан индекс order_id, city_id (`order_id_city_id`).

#### Создание новой таблицы связи <a name="migrateCustomTable"></a>
Чтобы создать таблицу связи, нужно в методе [install() класса Init](./README.md#configuratinFiles)
вызвать метод migrateCustomTable(), который принимает два параметра:
* Название таблицы (без приставки ok_, можно с приставкой __)
* Массив экземпляров класса [Okay\Core\Modules\EntityField](#EntityField)

В массиве полей, нужно описать каждое поле, которое объявлено в Entity модуля.

Пример:
```php
$this->migrateCustomTable('some_table_name', [
    (new EntityField('redelivery'))->setTypeTinyInt(1),
    (new EntityField('name'))->setTypeVarchar(255)->setIsLang(),
]);
$this->migrateCustomTable('__second_some_table_name', [
    (new EntityField('redelivery'))->setTypeTinyInt(1),
    (new EntityField('name'))->setTypeVarchar(255)->setIsLang(),
]);
```

### Класс Okay\Core\Modules\EntityField <a name="EntityField"></a>

Данный класс нужен для настройки поля (колонки) в базе данных, для их последующей миграции.
Документацию по методам, см. в аннотации к методам.
Метод в конструктор принимает название колонки, далее вся настройка происходит через fluent interface.

Пример:
```php
$notLangField = (new EntityField('not_lang_field'))->setTypeVarchar(255)->setIndex();
$langField = (new EntityField('lang_field'))->setTypeVarchar(255)->setIndex()->setIsLang();
```



# Внимание, этот файл конфигурации является лишь рекомендацией по настройке сервера Nginx для работы с OkayCMS 3.
# Это не конечная настройка web сервера. За более тонкой настройкой всего сервера,
# обратитесь к Вашему системному администратору.

server {
    listen 80;
    server_name DOMAIN_SERVER_NAME;
    
    #add_header Strict-Transport-Security "max-age=31536000;";
    
    set $root_path /application/public;
    root $root_path;
    
    charset off;
    index index.php index.html;
    disable_symlinks if_not_owner from=$root_path;
    
    access_log /application/log/application.access.log;
    error_log /application/log/application.error.log;
        
    gzip on;
    gzip_comp_level 9;
    gzip_disable "msie6";
    gzip_types text/plain text/css application/json application/x-javascript text/xml application/xml application/xml+rss text/javascript application/javascript;
    gzip_vary on;
    
    if ($host ~* ^www\.(.*)$) {
        return 301 https://$server_name$request_uri;
    }
    
    if ($request_uri ~* "^(.*/)index\.php$") {
        return 301 $1;
    }
    
    if ($request_uri ~* "^(.*/)index\.html") {
        return 301 $1;
    }
    
    location / {
        location ~* \.(tpl.?)$ {
            return 404;
        }
        
        location ~* \.(htaccess|htpasswd|git|svn) {
            return 404;
        }
        
        location ~* ^/admin$ {
            rewrite /admin /backend/ redirect;
        }
        
        location ~* backend/design/js/admintooltip/admintooltip.js {
            rewrite ^(/backend/design/js/admintooltip)/admintooltip.js$ $1/admintooltip.php break;
            try_files /no_file @php;
        }
        
        location ~* backend/files/ {
            rewrite ^(/backend/files)/export/(.+)\.csv$ $1/index.php?file=$2&folder=export&ext=csv break;
            rewrite ^(/backend/files)/export_users/(.+)\.csv$ $1/index.php?file=$2&folder=export_users&ext=csv break;
            rewrite ^(/backend/files)/import/(.+)\.csv$ $1/index.php?file=$2&folder=import&ext=csv break;
            rewrite ^(/backend/files)/watermark/(.+)\.(png|jpg|jpeg|gif|tif|bmp|ico)$ $1/index.php?file=$2&folder=watermark&ext=$3 break;
            rewrite ^(\w+)$ /backend/files/index.php?file= break;
            try_files /does_not_exists @php;
        }
        
        location ~* files/originals {
            return 404;
        }
    
        location ~* docs/ {
            return 404;
        }
        
        location ~* /(\w+/)?(\w+/)?(.+\.(jpg|jpeg|gif|png|webp|svg|js|css|mp3|ogg|mpe?g|avi|zip|gz|bz2?|rar|swf|woff|woff2|ttf|xls|xlsx|doc|docx|pdf))$ {
            try_files $uri $uri/ /$2$3 /$3 /index.php?$args;
            allow all;
            access_log off;
            expires max;
            add_header Cache-Control public;
            add_header Access-Control-Allow-Origin *;
        }
        
        location ~* Okay/ {
            return 404;
        }
        
        location ~* backend/design/js/filemanager/config {
            return 404;
        }
        
        location ~* backend/lang {
            return 404;
        }
        
        location ~* build/bitbucket.php {
            try_files /does_not_exists @php;
        }
        
        location ~* build/ {
            return 404;
        }
        
        location ~* config/ {
            return 404;
        }
        
        location ~* ^design/.+/(.+\.(phtml|php|php3|php4|php5|php6|phps|cgi|exe|pl|asp|aspx|shtml|shtm|fcgi|fpl|jsp|htm|html|wml)) {
            return 404;
        }
        
        location ~* ^files/(.+\.(phtml|php|php3|php4|php5|php6|phps|cgi|exe|pl|asp|aspx|shtml|shtm|fcgi|fpl|jsp|htm|html|wml))$ {
            return 404;
        }
        
        location ~ [^/]\.ph(p\d*|tml)$ {
            try_files /does_not_exists @php;
        }
        
        try_files $uri $uri/ /index.php?$args;
        
        location ~* ^.+\.(jpg|jpeg|gif|png|webp|svg|js|css|mp3|ogg|mpe?g|avi|zip|gz|bz2?|rar|swf)$ {
            expires max;
        }
    }
    
    location @php {
        try_files $uri $uri/ /$2$3 /$3 /index.php =404;
        fastcgi_pass php-fpm:9000;
        #fastcgi_pass unix:/run/php-fpm/shopdev.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        fastcgi_param DOCUMENT_ROOT $root_path;
        fastcgi_split_path_info ^((?U).+\.ph(?:p\d*|tml))(/?.+)$;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        include fastcgi_params;
    }
}