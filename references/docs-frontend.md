-e # Документация: Фронтенд, шаблоны, CSS/JS

**Содержит:** routes.md, js_css_files.md, smarty_plugins.md, tpl_modifiers.md
**Читать:** при создании Smarty-плагинов, регистрации CSS/JS, настройке роутов, TPL-модификаций

---

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
