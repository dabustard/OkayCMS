# AbstractInit — полный API справочник (из исходников OkayCMS)

Все методы доступны в `Init.php` через `$this->...`

---

## install() — только при установке

| Метод | Назначение |
|---|---|
| `setBackendMainController('AdminName')` | Контроллер главной страницы модуля в админке |
| `migrateEntityTable(MyEntity::class, $fields)` | Создать новую таблицу |
| `migrateEntityField(ProductsEntity::class, $field)` | Добавить поле в существующую таблицу |
| `migrateCustomTable('table_name', $fields)` | Создать произвольную таблицу (например, таблицу связей) |
| `setModuleType(MODULE_TYPE_DELIVERY)` | Тип: `MODULE_TYPE_PAYMENT`, `MODULE_TYPE_DELIVERY`, `MODULE_TYPE_XML` |
| `setSystem()` | Сделать модуль системным (скрыть из списка для менеджеров) |

---

## init() — при каждом запуске

| Метод | Назначение |
|---|---|
| `registerBackendController('MyAdmin')` | Зарегистрировать контроллер AdminKit |
| `addBackendControllerPermission('MyAdmin', 'permission_name')` | Привязать разрешение к контроллеру |
| `addPermission('permission_name')` | Добавить разрешение без контроллера |
| `registerEntityField(MyEntity::class, 'field_name', $isLang)` | **Обязательно** после `migrateEntityField` |
| `registerEntityAdditionalField(MyEntity::class, 'field')` | Поле не в таблице, только в SELECT |
| `registerEntityFilter(Entity::class, 'filter', FilterClass::class, 'method')` | Кастомный фильтр |
| `registerChainExtension($expandable, $extension)` | Цепочный экстендер (должен вернуть результат) |
| `registerQueueExtension($expandable, $extension)` | Очередь (не передаёт результат) |
| `addBackendBlock('block_name', 'template.tpl', $callback)` | Добавить блок в шорт-блок админки |
| `addFrontBlock('block_name', 'template.tpl', $callback)` | Добавить блок в шорт-блок фронта |
| `extendBackendMenu('menu_group', ['lang_key' => ['Controller']], $icon)` | Пункт меню в админке |
| `addFastMenuItem('data-attr', ...$menuItems)` | Меню быстрого редактирования |
| `addResizeObject('original_dir_config', 'resized_dir_config')` | Регистрация директорий изображений |
| `extendUpdateObject('alias', 'permission', EntityClass::class)` | AJAX-обновление сущности |
| `registerCartDiscountSign('sign', 'lang_name', 'lang_desc')` | Скидка на корзину |
| `registerPurchaseDiscountSign('sign', 'lang_name', 'lang_desc')` | Скидка на позицию |
| `registerSchedule($schedule)` | Задача планировщика |
| `setDefaultOrderFields(Entity::class, ['field ASC'], $redefine)` | Сортировка по умолчанию |
| `registerEntityLangInfo(Entity::class, 'lang_table', 'lang_obj')` | Добавить мультиязычность к существующей сущности |

---

## EntityField — все методы типов

```php
(new EntityField('field_name'))
    // Типы:
    ->setTypeVarchar(255)           // VARCHAR(255)
    ->setTypeInt(11)                // INT(11)
    ->setTypeTinyInt(1)             // TINYINT(1) — для boolean/флагов
    ->setTypeText()                 // TEXT
    ->setTypeMediumText()           // MEDIUMTEXT
    ->setTypeLongText()             // LONGTEXT
    ->setTypeFloat(10)              // FLOAT(10)
    ->setTypeDecimal(10)            // DECIMAL(10)
    ->setTypeEnum(['val1','val2'])   // ENUM('val1','val2')
    ->setTypeDatetime()             // DATETIME
    ->setTypeTimestamp()            // TIMESTAMP DEFAULT current_timestamp()

    // Модификаторы:
    ->setNullable()                 // NULL (по умолчанию)
    ->unsetNullable()               // NOT NULL
    ->setDefault(0)                 // DEFAULT 0
    ->setIsLang()                   // поле идёт в языковую таблицу
    ->setAutoIncrement()            // AUTO_INCREMENT (автоматически ставит PK)
    ->setIndexPrimaryKey()          // PRIMARY KEY
    ->setIndex()                    // INDEX
    ->setIndexUnique()              // UNIQUE INDEX
    ->setIndexFulltext()            // FULLTEXT INDEX
```

### Типичные комбинации:

```php
// ID первичный ключ:
(new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement()

// Строка обязательная:
(new EntityField('name'))->setTypeVarchar(255, false)

// Текст мультиязычный:
(new EntityField('description'))->setTypeText()->setIsLang()->setNullable()

// Флаг видимости:
(new EntityField('visible'))->setTypeTinyInt(1, false)->setDefault(1)

// Позиция сортировки:
(new EntityField('position'))->setTypeInt(11, false)->setDefault(0)

// Внешний ключ:
(new EntityField('product_id'))->setTypeInt(11)

// Файл (nullable):
(new EntityField('filename'))->setTypeVarchar(255, true)

// Дата:
(new EntityField('created_at'))->setTypeTimestamp(false)
```

---

## Форматы аргументов registerChainExtension / registerQueueExtension

```php
// Формат 1 (массивы — рекомендуется):
$this->registerChainExtension(
    ['class' => ProductAdmin::class, 'method' => 'fetch'],
    ['class' => MyExtension::class,  'method' => 'myMethod']
);

// Формат 2 (массивы с аргументами — для старого стиля):
$this->registerChainExtension(
    [BackendProductsRequest::class,     'postProduct'],
    [MyExtension::class, 'extendPostProduct']
);
```
