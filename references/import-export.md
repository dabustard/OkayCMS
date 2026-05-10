# Паттерн: Импорт/Экспорт CSV в модуле OkayCMS

Модуль может добавить свои колонки в стандартный CSV-импорт/экспорт товаров в админке.
Полный рабочий пример — `examples/Rozetka.md` (смотри `Extenders/BackendExtender.php` и `Init/Init.php`).

---

## Как это работает

При экспорте товаров в CSV → модуль добавляет свою колонку в заголовок файла.  
При импорте CSV → модуль читает значение из своей колонки и сохраняет его в БД.  
В интерфейсе импорта → модуль добавляет своё поле в список доступных для маппинга колонок.

---

## Шаг 1 — Константа имени колонки в Init.php

**Ключ колонки обязан совпадать с реальным именем поля в БД.**  
`BackendExportHelper` строит CSV-строки как `$product->{columnKey}` — если ключ не совпадает с именем поля на объекте товара, значения не экспортируются (пустая колонка).

```php
class Init extends AbstractInit
{
    const FIELD_NAME          = 'vendor__module__field';  // имя поля в БД

    // ПРАВИЛЬНО: ключ = имя поля
    const IMPORT_EXPORT_FIELD = self::FIELD_NAME;

    // НЕПРАВИЛЬНО: ключ отличается от имени поля
    // const IMPORT_EXPORT_FIELD = 'to__vendor__module'; // ← экспорт будет пустым!
}
```

---

## Шаг 2 — Пять точек расширения в init()

```php
use Okay\Admin\Helpers\BackendExportHelper;
use Okay\Admin\Helpers\BackendImportHelper;

public function init()
{
    // 1. Добавить колонку в заголовок CSV при экспорте
    $this->registerChainExtension(
        [BackendExportHelper::class, 'getColumnsNames'],
        [MyExtender::class, 'extendExportColumnsNames']
    );

    // 2. Расширить фильтр/запрос при экспорте (если нужны доп. данные в SELECT)
    $this->registerChainExtension(
        [BackendExportHelper::class, 'setUp'],
        [MyExtender::class, 'extendFilter']
    );

    // 3. Зарегистрировать имена колонок модуля для импортёра
    $this->registerChainExtension(
        [BackendImportHelper::class, 'getModulesColumnsNames'],
        [MyExtender::class, 'getModulesColumnsNames']
    );

    // 4. Обработать строку CSV при импорте (сохранить значение в БД)
    $this->registerQueueExtension(
        [BackendImportHelper::class, 'importItem'],
        [MyExtender::class, 'importItem']
    );

    // 5. Очистить данные товара от колонок модуля перед стандартной обработкой
    $this->registerQueueExtension(
        [BackendImportHelper::class, 'parseProductData'],
        [MyExtender::class, 'parseProductData']
    );

    // 6. Добавить поле модуля в UI маппинга колонок импорта
    $this->addBackendBlock(
        'import_fields_association',
        'import_fields_association.tpl'
    );
}
```

---

## Шаг 3 — Методы экстендера

```php
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Modules\Vendor\Module\Init\Init;

class MyExtender implements ExtensionInterface
{
    // ЭКСПОРТ: добавить колонку в заголовок CSV
    public function extendExportColumnsNames($columns)
    {
        $columns[Init::IMPORT_EXPORT_FIELD] = 'Моя колонка';
        return $columns; // ChainExtender — обязательно вернуть
    }

    // ЭКСПОРТ: расширить фильтр выборки товаров
    public function extendFilter($params)
    {
        list($filter, $page) = $params;
        // добавить нужные условия в $filter
        return [$filter, $page]; // ChainExtender — обязательно вернуть
    }

    // ИМПОРТ: зарегистрировать имена колонок модуля
    public function getModulesColumnsNames($modulesColumnsNames)
    {
        $modulesColumnsNames[] = Init::IMPORT_EXPORT_FIELD;
        return $modulesColumnsNames; // ChainExtender — обязательно вернуть
    }

    // ИМПОРТ: обработать строку из CSV (сохранить в БД)
    public function importItem($importedItem, $itemFromCsv)
    {
        if (isset($itemFromCsv[Init::IMPORT_EXPORT_FIELD])) {
            $value = trim($itemFromCsv[Init::IMPORT_EXPORT_FIELD]);
            if ($value) {
                // сохранить $value для $importedItem->product->id
                // например: $this->myEntity->add([...]);
            }
        }
        // QueueExtender — return НЕ нужен
    }

    // ИМПОРТ: убрать колонки модуля из данных товара
    // (чтобы стандартный импортёр не пытался записать их в таблицу товаров)
    public function parseProductData($product)
    {
        if (isset($product[Init::IMPORT_EXPORT_FIELD])) {
            unset($product[Init::IMPORT_EXPORT_FIELD]);
        }
        return $product; // ChainExtender — обязательно вернуть
    }
}
```

---

## Шаг 4 — Шаблон маппинга для UI импорта

Файл `Backend/design/html/import_fields_association.tpl` — добавляет поле модуля
в выпадающий список колонок на странице импорта.

**Обязательно добавляй атрибут `data-label`** — JS ядра читает его чтобы обновить
видимый текст в select после выбора пользователя. Без него select визуально не меняется
(импорт при этом работает корректно, но пользователь видит баг).

```smarty
<option value="{Okay\Modules\Vendor\Module\Init\Init::IMPORT_EXPORT_FIELD}"
        data-label="Человекочитаемое название поля">
    Человекочитаемое название поля
</option>
```

Пример из Rozetka (несколько полей через цикл):
```smarty
{foreach $rozetkaFeeds as $feed}
<option value="{Okay\Modules\OkayCMS\Rozetka\Init\Init::TO_FEED_FIELD}@{$feed->id}"
        data-label="{$btr->getTranslation('okaycms__rozetka_xml__import_field')} {$feed@iteration}.{$feed->name|escape}">
    {$btr->getTranslation('okaycms__rozetka_xml__import_field')} {$feed@iteration}. {$feed->name|escape}
</option>
{/foreach}
```

---

## Когда добавлять импорт/экспорт

Добавляй этот паттерн если модуль:
- Хранит **дополнительные данные для товаров** (доп. поля, флаги, связи)
- Эти данные нужно **массово редактировать через CSV**
- Используется **своя таблица** с привязкой к `product_id`

Не нужен если модуль работает только с настройками или выводит виджет без хранения данных по товарам.
