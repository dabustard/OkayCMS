# Entity — паттерны создания и расширения

---

## 1. Своя Entity (новая таблица)

```php
<?php

namespace Okay\Modules\Vendor\Module\Entities;

use Okay\Core\Entity\Entity;

class MyEntity extends Entity
{
    // Обычные поля таблицы (не языковые)
    protected static $fields = [
        'id',
        'product_id',
        'filename',
        'visible',
        'position',
    ];

    // Мультиязычные поля (хранятся в отдельной lang-таблице)
    // Указывай только если модуль мультиязычный
    protected static $langFields = [
        'title',
        'description',
    ];

    // Сортировка по умолчанию
    protected static $defaultOrderFields = [
        'position ASC',
    ];

    // Название таблицы. Рекомендуется с __ (плейсхолдер для префикса ok_)
    // Формат: __{vendor}__{module}__{entity}
    protected static $table = '__vendor__module__items';

    // Алиас таблицы в SQL-запросах (короткий, уникальный)
    protected static $tableAlias = 'vmi';

    // Только для мультиязычных Entity:
    // langObject — ключ связи в lang-таблице (например, в таблице lang хранится item_id)
    protected static $langObject = 'item';
    // langTable — название lang-таблицы без __ и без _lang_ префикса
    protected static $langTable = 'vendor__module__items';
}
```

---

## 2. Свойства Entity — полный справочник

| Свойство | Обязательно | Описание |
|---|---|---|
| `$fields` | Да | Все поля таблицы (не языковые) |
| `$table` | Да | Название таблицы (с `__` рекомендуется) |
| `$tableAlias` | Да | Алиас для SQL (короткий, уникальный) |
| `$langFields` | Нет | Поля из lang-таблицы |
| `$langObject` | Нет* | Ключ в lang-таблице (если есть `$langFields`) |
| `$langTable` | Нет* | Название lang-таблицы (если есть `$langFields`) |
| `$defaultOrderFields` | Нет | Сортировка по умолчанию |
| `$additionalFields` | Нет | Доп. поля из других таблиц или подзапросов (без префикса) |
| `$searchFields` | Нет | Поля для текстового поиска |
| `$alternativeIdField` | Нет | Поле для get() по строке (url, code и т.д.) |

*Обязательно при наличии `$langFields`

---

## 3. Правила именования `$table`

**`__` — это плейсхолдер для системного префикса** (по умолчанию `ok_`).
`__vendor__module__entity` → система заменяет на `ok_vendor__module__entity`

```php
// Рекомендуется для новых модулей:
protected static $table = '__vendor__module__items';
// → итоговое имя в БД: ok_vendor__module__items

// Допустимо (старый стиль):
protected static $table = 'vendor__module__items';
// → итоговое имя в БД: vendor__module__items (без системного префикса!)
```

**Правило:** всегда используй `__` в начале `$table` — это гарантирует корректную работу при смене префикса таблиц в конфиге.

**В кастомных SQL-запросах через `queryFactory`** — тоже используй `__`:
```php
$select->from('__vendor__module__items AS vmi');
// НЕ пиши: ->from('ok_vendor__module__items AS vmi')
```

**Исключение — `migrateCustomTable()`:** передавай имя без `__` и без `ok_`:
```php
// Правильно:
$this->migrateCustomTable('vendor__module__relations', $fields);
// Неправильно:
$this->migrateCustomTable('__vendor__module__relations', $fields); // спорно
$this->migrateCustomTable('ok_vendor__module__relations', $fields); // нельзя
```

---

## 4. Примеры: мультиязычная vs обычная Entity

```php
// Мультиязычная (с lang-таблицей):
class FAQEntity extends Entity
{
    protected static $fields = ['id', 'question', 'answer', 'visible', 'position'];
    protected static $langFields = ['question', 'answer'];
    protected static $defaultOrderFields = ['position'];
    protected static $table = '__okaycms__faq__faq';
    protected static $langTable = 'okaycms__faq__faq';
    protected static $langObject = 'faq';
    protected static $tableAlias = 'of_f';
}

// Обычная (без мультиязычности):
class RozetkaFeedsEntity extends Entity
{
    protected static $fields = ['id', 'name', 'url', 'enabled'];
    protected static $table = '__okaycms__rozetka__feeds';
    protected static $tableAlias = 'rxf';
}
```

---

## 5. ExtendsEntities — кастомный фильтр для существующей Entity

Используется когда нужно добавить **кастомный JOIN или колонку** в SELECT существующей сущности через фильтр.

```php
<?php

namespace Okay\Modules\Vendor\Module\ExtendsEntities;

use Okay\Core\Modules\AbstractModuleEntityFilter;

// Класс называется так же как расширяемая Entity (ProductsEntity, OrdersEntity...)
class ProductsEntity extends AbstractModuleEntityFilter
{
    // Метод = имя фильтра (тот же что в registerEntityFilter)
    public function vendor__module__my_filter($value, $filter)
    {
        if ($value) {
            // $this->select — объект Select от QueryFactory
            // Можно делать JOIN, добавлять колонки, условия
            $this->select->cols(['my_table.my_col AS my_field']);
            $this->select->join(
                'LEFT',
                '__vendor__module__data AS md',
                'md.product_id = p.id'
            );
            $this->select->where('md.value = :my_value');
            $this->select->bindValue('my_value', $value);
        }
    }
}
```

Регистрация в `Init.php`:
```php
use Okay\Entities\ProductsEntity;
use Okay\Modules\Vendor\Module\ExtendsEntities\ProductsEntity as ProductsEntityFilter;

// В init():
$this->registerEntityFilter(
    ProductsEntity::class,
    'vendor__module__my_filter',      // имя фильтра
    ProductsEntityFilter::class,       // класс фильтра
    'vendor__module__my_filter'        // метод фильтра
);
```

Использование фильтра:
```php
$productsEntity->find(['vendor__module__my_filter' => $someValue]);
```

---

## 6. Когда использовать ExtendsEntities vs registerEntityField

| Задача | Решение |
|---|---|
| Добавить простое поле из миграции к SELECT | `migrateEntityField()` + `registerEntityField()` |
| Добавить поле через сложный JOIN/подзапрос | `ExtendsEntities` + `registerEntityFilter()` |
| Добавить условие фильтрации через JOIN | `ExtendsEntities` + `registerEntityFilter()` |
