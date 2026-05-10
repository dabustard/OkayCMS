-e # Документация: Архитектура модулей OkayCMS

**Содержит:** modules/README, quick_start, init.md (AbstractInit API), module_json, extenders, table_migrate
**Читать:** при разработке любого модуля — это основной справочник

---

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
