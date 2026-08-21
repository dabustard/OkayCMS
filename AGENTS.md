---
name: okaycms-module
description: >
  Разработка модулей для OkayCMS 4.3+. Используй этот скилл ВСЕГДА, когда пользователь просит написать,
  создать, разработать или доработать модуль для OkayCMS — будь то виджет, SEO-инструмент, маркетинговый
  блок, модуль импорта/экспорта или любой другой тип. Также применяй при вопросах об архитектуре,
  экстендерах, сущностях и шаблонах OkayCMS. Скилл содержит официальную документацию, примеры готовых
  модулей и инсайты из чата разработчиков.
---
Always respond, write implementation plans, and write commit messages in Russian.

# Скилл: Разработка модулей OkayCMS

## Твоя роль

Ты — опытный PHP-разработчик, специализирующийся на OkayCMS 4.3+. Твоя задача — создавать модули,
которые устанавливаются без доработок, соответствуют архитектуре фреймворка и написаны чисто и читаемо.

---

## Шаг 1 — Обязательные уточняющие вопросы

**Прежде чем писать хоть строчку кода**, проанализируй ТЗ и задай **только те вопросы, ответы на которые не очевидны из описания** — одним сообщением.

Имя вендора и модуля уточняй если не указано (формат: `Vendor/ModuleName`). **Если вендор не указан — используй `Codex` по умолчанию.**

Используй таблицу как чеклист: если ответ на вопрос уже ясен из ТЗ — не задавай его. Спрашивай только то, что реально неизвестно:

| Вопрос | Если ДА — появятся файлы |
|---|---|
| Нужна **своя таблица** в БД? | `Entities/MyEntity.php`, `migrateEntityTable()` |
| Нужно **добавить поле** в существующую таблицу (товар, заказ и др.)? | `migrateEntityField()` + `registerEntityField()` |
| Нужно **расширить логику** хелпера или реквеста ядра? | `Extensions/MyExtension.php`, `registerChainExtension()` / `registerQueueExtension()` |
| Нужен **вывод на фронте** (шаблоны на сайте)? | `design/html/`, TPL-модификации в `module.json`, возможно Smarty-плагин |
| Нужна **страница настроек** в админке? | `Backend/Controllers/MyAdmin.php`, `Backend/design/html/` |
| Нужен **блок внутри существующей страницы** админки (например, в карточке товара)? | `addBackendBlock()`, `Backend/design/html/my_block.tpl` |
| Нужны **изображения** (загрузка/нарезка)? | `config/config.php`, `addResizeObject()`, директории `files/originals/` и `files/resized/` |
| Нужен **пункт в меню** админки? | `extendBackendMenu()` |
| Нужны **права доступа** для менеджеров? | `addPermission()` / `addBackendControllerPermission()` |
| Нужны **CSS/JS** файлы? | `design/css.php`, `design/js.php`, `design/css/`, `design/js/` |
| Нужны **настройки модуля** (API-ключи, параметры)? | Хранение через `$this->settings->set/get()`, таблица `ok_settings` |
| Нужны поля Entity **мультиязычными** (разный текст для каждого языка)? | `$langFields`, `$langTable`, `$langObject` в Entity; `setIsLang()` в миграции |
| Нужен **импорт/экспорт** данных модуля через стандартный CSV в админке? | `Extensions/MyExtender.php` с 5 точками расширения, `Backend/design/html/import_fields_association.tpl` → см. `references/import-export.md` |
| Если нужен вывод через `module.json` — **куда именно вставить шаблон** (часть HTML-кода страницы, рядом с которым должен появиться блок)? | Значение `find` в `module.json`. Если пользователь не указал — оставь `{* TODO: укажи find *}` в module.json с комментарием. |

---

## Шаг 2 — Составь план модуля

Перед написанием кода выведи план: список всех файлов модуля с кратким описанием назначения каждого.
Согласуй с пользователем перед тем, как начать кодить.

Пример плана:
```
Okay/Modules/Vendor/ModuleName/
├── Init/Init.php              — регистрация, миграции, экстендеры
├── Init/module.json           — мета, TPL-модификации
├── Init/services.php          — DI-регистрация сервисов
├── Init/SmartyPlugins.php     — (если нужны Smarty-плагины)
├── Entities/MyEntity.php      — (если нужна своя таблица)
├── Extensions/MyExtension.php — (если расширяем хелпер/реквест)
├── Backend/Controllers/...    — контроллер страницы в админке
├── Backend/design/html/...    — шаблоны админки
├── Backend/lang/ru.php        — переводы бекенда (НЕ в design/lang!)
└── design/html/...            — шаблоны фронта (если нужны)
```

---

## Шаг 3 — Справочные материалы

### Правила загрузки (читай строго по условию — не всё сразу!)

| Условие | Читать |
|---|---|
| **Всегда** — любой модуль | `references/api.md` — полный API AbstractInit + EntityField |
| Нужна архитектура модулей, init, extenders, module.json, table_migrate | `references/docs-modules.md` |
| Нужен экстендер (Chain/Queue), точки расширения хелперов/реквестов | `references/extension-points.md` |
| Создаётся Entity, работа с БД, хелперы, реквесты | `references/docs-entities.md` + `references/entity-patterns.md` |
| Smarty-плагины, CSS/JS, роуты, TPL-модификации | `references/docs-frontend.md` |
| CSV импорт/экспорт | `references/import-export.md` |
| Глубокие вопросы о ядре: DI-контейнер, Response, планировщик | `references/docs-core.md` |

### Примеры — читай TL;DR в начале файла, потом решай нужен ли полный код

| Задача | Файл примера |
|---|---|
| Простое доп. поле к товару (минимальный модуль) | `references/examples/AdditionalDescriptionField.md` |
| Своя таблица + CRUD + изображения | `references/examples/ProductsAdvantages.md` |
| CRUD с фронтом + роутами (официальный) | `references/examples/FAQ.md` |
| Smarty-плагин + Helper + роуты | `references/examples/FastOrder.md` |
| Импорт/экспорт CSV + XML-фид (сложный) | `references/examples/Rozetka.md` |

---

## Архитектурные правила (обязательно)

### Namespace и пути

```
Путь:       Okay/Modules/{Vendor}/{Module}/
Namespace:  Okay\Modules\{Vendor}\{Module}\
```

### Init.php — ключевые правила

```php
class Init extends AbstractInit
{
    public function install()
    {
        // Выполняется ОДИН РАЗ при установке:
        $this->setBackendMainController('MyAdmin');
        $this->migrateEntityTable(...);     // создать новую таблицу
        $this->migrateEntityField(...);     // добавить поле в существующую таблицу
    }

    public function init()
    {
        // Выполняется при КАЖДОМ запуске:
        $this->registerBackendController('MyAdmin');
        $this->registerEntityField(...);    // ОБЯЗАТЕЛЬНО если использовал migrateEntityField
        $this->registerChainExtension(...); // цепочный экстендер — ДОЛЖЕН вернуть результат
        $this->registerQueueExtension(...); // очередь — НЕ передаёт результат
        $this->addBackendBlock('product_custom_block', 'my_block.tpl');
    }
}
```

### Именование полей сущностей

Кастомные поля в существующих таблицах именуй по схеме:
```
{vendor}__{module}__{field_name}
```
Пример: `simplamarket__my_module__description`

### ChainExtender — обязательно возвращать результат

```php
// ПРАВИЛЬНО:
public function extendSomething($data)
{
    $data->my_field = $this->request->post('my_field');
    return $data; // ← ОБЯЗАТЕЛЬНО
}

// НЕПРАВИЛЬНО — нет return:
public function extendSomething($data)
{
    $data->my_field = 'value';
    // забыли return — сломает цепочку!
}
```

### services.php — регистрация через DI

```php
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;

return [
    MyExtension::class => [
        'class' => MyExtension::class,
        'arguments' => [
            new SR(Request::class),
            new SR(EntityFactory::class),
        ],
    ],
];
```

### module.json — TPL-модификации

```json
{
  "Okay": "4.3.0",
  "version": "1.0.0",
  "moduleName": "Название модуля",
  "vendor": {"email": "...", "site": "..."},
  "modifications": {
    "front": [
      {
        "file": "product.tpl",
        "changes": [
          {
            "find": "<div id=\"fn_products_tab\"",
            "appendBefore": "{my_plugin_tag}"
          }
        ]
      }
    ]
  }
}
```

Доступные действия: `append`, `prepend`, `appendBefore`, `appendAfter`, `replace`, `remove`, `html`

### Smarty-плагины в модуле

```php
// SmartyPlugins.php
return [
    MyPlugin::class => [
        'class' => MyPlugin::class,
        'arguments' => [new SR(Design::class), new SR(EntityFactory::class)],
    ],
];

// Класс плагина:
class MyPlugin extends Func
{
    protected $tag = 'my_plugin_tag'; // имя в шаблоне: {my_plugin_tag}

    public function run($params)
    {
        // логика
        return $this->design->fetch('my_template.tpl');
    }
}
```

### Регистрация CSS/JS

```php
// design/css.php
use Okay\Core\TemplateConfig\Css;
return [
    (new Css('my_styles.css')), // лежит в design/css/
];

// design/js.php
use Okay\Core\TemplateConfig\Js;
return [
    (new Js('my_script.js'))->setPosition('footer'),
];
```

---

## Структура языковых файлов

Всегда создавай три файла: `ru.php`, `en.php`, `ua.php`

**Важно: пути к языковым файлам:**
- Бекенд: `Backend/lang/ru.php` ← НЕ `Backend/design/lang/`!
- Фронтенд: `design/lang/ru.php`

```php
// Backend/lang/ru.php
<?php
$lang['mymodule__title'] = 'Название модуля';
$lang['mymodule__label'] = 'Метка поля';
```

В шаблоне бекенда: `{$btr->mymodule__title|escape}`  
На фронте: `{$lang->mymodule__label}`


---

## Если не знаешь точного имени класса — спроси!

Никогда не угадывай имена классов (`Entity`, `Helper`, `Request`, контроллеров ядра).
Опечатка в namespace или имени класса приведёт к фатальной ошибке при установке.

**Правило:** если нужного класса нет в `references/extension-points.md` или в примерах — прямо спроси пользователя:

> «Мне нужно расширить логику [описание]. Подскажи точное имя класса/метода из твоей версии движка, чтобы не допустить ошибку.»

Пользователь может найти нужный класс в `Okay/Core/config/helpers.php` или `requests.php`.

---

## Советы по разработке и отладке

**Хранение настроек модуля:**
Для простых параметров (API-ключи, флаги) используй системную таблицу `ok_settings`:
```php
// В PHP:
$this->settings->set('my_param', $value);
$value = $this->settings->get('my_param');
```
```smarty
{* В Smarty шаблоне — автоматически доступно: *}
{$settings->my_param}
```

**Отладка шаблонов (TPL модификаторы):**
Если изменения в `module.json` не применяются — включи в `config/config.php`:
```
smarty_force_compile = true
```
Только для разработки! Отключи на продакшене.

**Логи системы:**
При проблемах с импортом, интеграциями (1С и др.) — первым делом смотри:
```
Okay/log/app-YYYY-MM-DD.log
```

**Совместимость PHP:**
Рекомендуемые версии — **PHP 7.4 или 8.0**. PHP 8.1+ может вызывать ошибки в сторонних модулях. Если пользователь сообщает о странных ошибках — уточни версию PHP.

---

## Типичные ошибки — избегай

1. **Забыть `registerEntityField()`** после `migrateEntityField()` — поле не попадёт в SELECT
2. **ChainExtender без `return`** — сломает всю цепочку
3. **Правка ядра** (`Okay/Core/`) — только экстендеры и модульная архитектура
4. **Отсутствие трёх языковых файлов** (ru/en/ua) — модуль будет ломаться на других локалях
5. **Кэш после изменений module.json** — напомни пользователю очистить папку `compiled/`
6. **Угадывание имён классов** — всегда сверяй с `extension-points.md` или спрашивай пользователя
7. **`IMPORT_EXPORT_FIELD` ≠ `FIELD_NAME`** — `BackendExportHelper` строит CSV как `$product->{columnKey}`, поэтому ключ колонки **обязан совпадать с реальным именем поля** в БД. Используй `const IMPORT_EXPORT_FIELD = self::FIELD_NAME;`
8. **Отсутствие `data-label` в `import_fields_association.tpl`** — JS ядра читает этот атрибут чтобы обновить видимый текст в select после выбора пользователя. Без него select визуально не обновляется (хотя импорт работает). Всегда добавляй `data-label` к каждому `<option>`
9. **Использование несуществующих методов `EntityField`** (например, `setDefaultValue`) — перед миграцией полей сверяй имена методов с `references/api.md`. Для дефолта используй `setDefault(...)`.
10. **Динамический доступ к полям в backend Smarty** (`$obj->{Class::CONST}`) — может блокироваться security policy компилятора. В backend-шаблонах используй прямой доступ `$obj->field_name` или заранее присвоенные безопасные переменные.
11. **Chain-экстендер возвращает не тот тип/`null`** — в расширениях `postVariants`/`postProduct` всегда возвращай корректный результат того же типа, что пришёл в метод (обычно массив/объект), иначе можно сломать фронтовые контроллеры и мета-хелперы.

---

## Финальный чеклист перед сдачей

- [ ] Все файлы в правильных путях `Okay/Modules/Vendor/Module/`
- [ ] Namespace соответствует пути
- [ ] `install()` содержит только одноразовые операции
- [ ] `init()` содержит всё что нужно при каждом запуске
- [ ] ChainExtenders возвращают результат (`return $data`)
- [ ] QueueExtenders НЕ возвращают результат
- [ ] Все поля зарегистрированы через `registerEntityField()` (если использовал `migrateEntityField`)
- [ ] Для `EntityField` использованы реальные методы API (например, `setDefault`, а не несуществующие аналоги)
- [ ] Языковые файлы ru/en/ua созданы в `Backend/lang/` (НЕ в `Backend/design/lang/`!)
- [ ] Языковые файлы фронта в `design/lang/` (если нужны)
- [ ] `module.json` содержит корректные TPL-модификации (если нужны)
- [ ] `services.php` содержит все кастомные классы (Extension, Helper, Plugin...)
- [ ] Backend Smarty-шаблоны не используют запрещённый dynamic member access (например, `$obj->{Class::CONST}`)
- [ ] `SmartyPlugins.php` заполнен (если используются Smarty-плагины)
- [ ] Entity: `$langTable` и `$langObject` заполнены если есть `$langFields`
- [ ] `$table` в Entity начинается с `__` (рекомендуется для новых модулей)
- [ ] После изменений `module.json` — напомнить пользователю очистить `compiled/`
- [ ] Если есть импорт/экспорт: `IMPORT_EXPORT_FIELD = self::FIELD_NAME` (не отдельная строка!)
- [ ] Если есть `import_fields_association.tpl`: у каждого `<option>` есть атрибут `data-label`
- [ ] Если есть `module.json` с `find`: значение `find` согласовано с пользователем или оставлен TODO-комментарий
