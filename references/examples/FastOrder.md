-e <!--
TL;DR: Быстрый заказ через модальное окно. Smarty-плагин + QueueExtender для сохранения заказа + роуты + JS. Читать при работе со Smarty-функциями и расширением процесса оформления заказа.
Размер: ~869 строк
-->

# Пример модуля: FastOrder (OkayCMS/FastOrder)

## Информация о модуле

**Вендор/Модуль:** `OkayCMS/FastOrder`  
**Тип:** Модуль быстрого заказа — Smarty-плагин, экстендер, Helper, фронт-контроллер, reCaptcha  
**Демонстрирует:**
- Smarty-плагин (`Plugins/FastOrderPlugin.php` + `Init/SmartyPlugins.php`)
- `ChainExtender` для расширения бекенд-логики (`Extenders/BackendExtender.php`)
- Отдельный Helper для валидации данных заказа
- Фронтенд-контроллер обработки AJAX-запроса
- Языковые файлы фронта в `design/lang/` (отдельно от `Backend/lang/`)
- Несколько шаблонов фронта (кнопка, форма, варианты reCaptcha)
- Настройки модуля через `$this->settings`
- `addBackendBlock` для вставки блока в страницу настроек

---

## Структура файлов

```
Okay/Modules/OkayCMS/FastOrder/
├── Init/
│   ├── Init.php
│   ├── SmartyPlugins.php
│   ├── module.json
│   ├── routes.php
│   └── services.php
├── Extenders/
│   └── BackendExtender.php            — ChainExtender бекенд-логики
├── Plugins/
│   └── FastOrderPlugin.php            — Smarty-плагин {fast_order_btn}
├── Helpers/
│   └── ValidateHelper.php             — валидация данных заказа
├── Backend/
│   ├── Controllers/
│   │   └── DescriptionAdmin.php
│   ├── design/html/
│   │   ├── activate_captcha_checkbox.tpl
│   │   └── description.tpl
│   └── lang/
│       ├── ru.php
│       ├── en.php
│       └── ua.php
├── Controllers/
│   └── FastOrderController.php        — фронтенд обработчик AJAX
└── design/
    ├── js.php
    ├── lang/
    │   ├── ru.php
    │   ├── en.php
    │   └── ua.php                     — языки фронта (НЕ в Backend/lang!)
    ├── html/
    │   ├── fast_order_btn.tpl
    │   ├── fast_order_form.tpl
    │   ├── recaptcha_init_invisible.tpl
    │   ├── recaptcha_init_v2.tpl
    │   └── recaptcha_init_v3.tpl
    └── js/
        └── fast_order.js
```

---

## Исходный код

### File: Okay/Modules/OkayCMS/FastOrder/Controllers/FastOrderController.php

```php
<?php


namespace Okay\Modules\OkayCMS\FastOrder\Controllers;


use Okay\Core\Cart;
use Okay\Core\FrontTranslations;
use Okay\Core\Notify;
use Okay\Core\Phone;
use Okay\Core\Router;
use Okay\Core\Languages;
use Okay\Core\EntityFactory;
use Okay\Core\Validator;
use Okay\Entities\VariantsEntity;
use Okay\Helpers\CartHelper;
use Okay\Helpers\OrdersHelper;
use Okay\Entities\OrdersEntity;
use Okay\Entities\PurchasesEntity;
use Okay\Controllers\AbstractController;
use Okay\Modules\OkayCMS\FastOrder\Extenders\BackendExtender;
use Okay\Modules\OkayCMS\FastOrder\Helpers\ValidateHelper;

class FastOrderController extends AbstractController
{
    public function createOrder(
        EntityFactory     $entityFactory,
        OrdersHelper      $ordersHelper,
        Languages         $languages,
        Notify            $notify,
        Validator         $validator,
        FrontTranslations $frontTranslations,
        CartHelper        $cartHelper,
        VariantsEntity    $variantsEntity,
        Cart              $cart,
        BackendExtender   $validateExtend
    ) {
        if (!$this->request->method('post')) {
            return $this->response->setContent(json_encode(['errors' => ['Request must be post']]), RESPONSE_JSON);
        }

        $order = new \stdClass();
        $order->name    = $this->request->post('name');
        $order->last_name = $this->request->post('last_name');
        $order->phone   = Phone::toSave($this->request->post('phone'));
        $order->email   = '';
        $order->comment = $frontTranslations->getTranslation('fast_order');
        $order->lang_id = $languages->getLangId();
        $order->ip      = $_SERVER['REMOTE_ADDR'];
        $variantId = $this->request->post('variant_id');

        $order = $ordersHelper->attachUserIfLogin($order, $this->user);

        $errors = $validateExtend->ValidateFastOrder($order,$variantId);

        if (!empty($errors)) {
            return $this->response->setContent(json_encode(['errors' => $errors]), RESPONSE_JSON);
        }
        
        /** @var OrdersEntity $ordersEntity */
        $ordersEntity = $entityFactory->get(OrdersEntity::class);
        $preparedOrder = $ordersHelper->prepareAdd($order);
        $orderId       = $ordersEntity->add($preparedOrder);

        $amount = $this->request->post('amount', 'integer');
        if ($amount <= 0) {
            $amount = 1;
        }

        if ($variantId && $amount) {
            $cart->addItem($variantId, $amount);
        }

        $preparedCart = $cartHelper->prepareCart($cart, $orderId);
        $preparedCart = $cartHelper->cartToOrder($preparedCart, $orderId);
        $preparedCart = $cartHelper->prepareDiscounts($preparedCart, $orderId);
        $cartHelper->discountsToDB($preparedCart);

        $order = $ordersEntity->findOne(['id' => $orderId]);
        $ordersEntity->updateTotalPrice($orderId);
        $ordersHelper->finalCreateOrderProcedure($order);

        $notify->emailOrderUser($order->id);
        $notify->emailOrderAdmin($order->id);

        $cart->clear();

        return $this->response->setContent(json_encode([
            'success'           => 1,
            'redirect_location' => Router::generateUrl('order', ['url' => $order->url], true)
        ]), RESPONSE_JSON);
    }
}

```

### File: Okay/Modules/OkayCMS/FastOrder/Helpers/ValidateHelper.php

```php
<?php

namespace Okay\Modules\OkayCMS\FastOrder\Helpers;

use Okay\Core\EntityFactory;
use Okay\Core\FrontTranslations;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Request;
use Okay\Core\Settings;
use Okay\Core\Validator;
use Okay\Entities\VariantsEntity;

class ValidateHelper implements ExtensionInterface
{
    /** @var Request $request */
    private $request;

    /** @var Validator $validator */
    private $validator;

    /** @var EntityFactory $entityFactory */
    private $entityFactory;

    /** @var FrontTranslations $frontTranslations */
    private $frontTranslations;

    /** @var Settings $settings */
    private $settings;

    public function __construct(
        Request                 $request,
        Validator               $validator,
        EntityFactory           $entityFactory,
        FrontTranslations       $frontTranslations,
        Settings                $settings
    )
    {
        $this->request              = $request;
        $this->validator            = $validator;
        $this->entityFactory        = $entityFactory;
        $this->frontTranslations    = $frontTranslations;
        $this->settings             = $settings;
    }

    public function validateFastOrderHeler($order,$variantId)
    {
        $errors = [];
        /** @var VariantsEntity $variantsEntity */
        $variantsEntity = $this->entityFactory->get(VariantsEntity::class);

        if (!$this->validator->isName($order->name, true)) {
            $errors[] = $this->frontTranslations->getTranslation('okay_cms__fast_order__form_name_error');
        }

        if (!$this->validator->isPhone($order->phone, true)) {
            $errors[] = $this->frontTranslations->getTranslation('okay_cms__fast_order__form_phone_error');
        }

        if (empty($variantId) || !$variantsEntity->findOne(['id' => $variantId])) {
            $errors[] = $this->frontTranslations->getTranslation('okay_cms__fast_order__wrong_variant');
        }

        $captchaCode =  $this->request->post('captcha_code', 'string');
        if ($this->settings->get('captcha_fast_order') && !$this->validator->verifyCaptcha('captcha_fast_order', $captchaCode)) {
            $errors[] = $this->frontTranslations->getTranslation('okay_cms__fast_order__form_captcha_error');
        }

        return $errors;
    }
}
```

### File: Okay/Modules/OkayCMS/FastOrder/Plugins/FastOrderPlugin.php

```php
<?php


namespace Okay\Modules\OkayCMS\FastOrder\Plugins;


use Okay\Core\Design;
use Okay\Core\SmartyPlugins\Func;

class FastOrderPlugin extends Func
{
    protected $tag = 'fast_order_btn';

    protected $design;

    public function __construct(Design $design)
    {
        $this->design = $design;
    }

    public function run($vars)
    {
        $this->design->assign('fast_order_product_name', $vars['product']->name);
        $this->design->assign('fastOrderProduct', $vars['product']);
        return $this->design->fetch('fast_order_btn.tpl');
    }
}
```

### File: Okay/Modules/OkayCMS/FastOrder/Init/Init.php

```php
<?php


namespace Okay\Modules\OkayCMS\FastOrder\Init;


use Okay\Admin\Helpers\BackendSettingsHelper;
use Okay\Core\Modules\AbstractInit;
use Okay\Modules\OkayCMS\FastOrder\Extenders\BackendExtender;

class Init extends AbstractInit
{
    public function install()
    {
        $this->setBackendMainController('DescriptionAdmin');
    }

    public function init()
    {
        $this->addPermission('okaycms__fast_order');

        $this->registerBackendController('DescriptionAdmin');
        $this->addBackendControllerPermission('DescriptionAdmin', 'okaycms__fast_order');

        $this->addFrontBlock('front_after_footer_content', 'fast_order_form.tpl');
        
        $this->registerChainExtension(
            [BackendSettingsHelper::class, 'updateGeneralSettings'],
            [BackendExtender::class, 'updateSettings']
        );
    }
}
```

### File: Okay/Modules/OkayCMS/FastOrder/Init/SmartyPlugins.php

```php
<?php


use Okay\Core\Design;
use Okay\Modules\OkayCMS\FastOrder\Plugins\FastOrderPlugin;
use Okay\Core\OkayContainer\Reference\ParameterReference as PR;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;

return [
    FastOrderPlugin::class => [
        'class' => FastOrderPlugin::class,
        'arguments' => [
            new SR(Design::class)
        ],
    ],
];
```

### File: Okay/Modules/OkayCMS/FastOrder/Init/module.json

```json
{
  "version": "1.0.2",
  "vendor": {
    "email": "info@okay-cms.com",
    "site": "https://okay-cms.com"
  },
  "modifications": {
    "backend": [
      {
        "file": "settings_general.tpl",
        "changes": [
          {
            "find": "name=\"captcha_callback\"",
            "closestFind": "col-",
            "appendAfter": "activate_captcha_checkbox.tpl"
          }
        ]
      }
    ],
    "front": [
      {
        "file": "head.tpl",
        "changes": [
          {
            "find": "onloadCallback = function",
            "parent": "",
            "appendAfter": "recaptcha_init_v2.tpl"
          },
          {
            "find": "onloadReCaptchaInvisible = function()",
            "parent": "",
            "appendAfter": "recaptcha_init_invisible.tpl"
          },
          {
            "find": "grecaptcha.execute('{$settings->public_recaptcha_v3",
            "parent": "",
            "appendAfter": "recaptcha_init_v3.tpl"
          }
        ]
      }
    ]
  }
}
```

### File: Okay/Modules/OkayCMS/FastOrder/Init/routes.php

```php
<?php

use Okay\Modules\OkayCMS\FastOrder\Controllers\FastOrderController;

return [
    'OkayCMS.FastOrder.CreateOrder' => [
        'slug' => '/okay-cms/fast-order/create-order',
        'params' => [
            'controller' => FastOrderController::class,
            'method' => 'createOrder',
        ],
    ],
];
```

### File: Okay/Modules/OkayCMS/FastOrder/Init/services.php

```php
<?php


namespace Okay\Modules\OkayCMS\FastOrder;


use Okay\Core\EntityFactory;
use Okay\Core\FrontTranslations;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\Request;
use Okay\Core\Settings;
use Okay\Core\Validator;
use Okay\Modules\OkayCMS\FastOrder\Extenders\BackendExtender;
use Okay\Modules\OkayCMS\FastOrder\Helpers\ValidateHelper;

return [
    BackendExtender::class => [
        'class' => BackendExtender::class,
        'arguments' => [
            new SR(Settings::class),
            new SR(Request::class),
            new SR(ValidateHelper::class),
        ],
    ],
    ValidateHelper::class => [
        'class' => ValidateHelper::class,
        'arguments' => [
            new SR(Request::class),
            new SR(Validator::class),
            new SR(EntityFactory::class),
            new SR(FrontTranslations::class),
            new SR(Settings::class),
        ],
    ],
];
```

### File: Okay/Modules/OkayCMS/FastOrder/design/js.php

```php
<?php

use Okay\Core\TemplateConfig\Js;

return [
    (new Js('fast_order.js')),
];

```

### File: Okay/Modules/OkayCMS/FastOrder/design/html/fast_order_btn.tpl

```smarty
<a class="fn_fast_order_button fast_order_button fa fa-rocket fn_is_stock"
   href="#fast_order" {if $fastOrderProduct->variant->stock < 1 && !$settings->is_preorder }style="display: none" {/if}
   title="{$lang->fast_order}" data-language="fast_order" data-name="{$fast_order_product_name}">{$lang->fast_order}
</a>
```

### File: Okay/Modules/OkayCMS/FastOrder/design/html/fast_order_form.tpl

```smarty
<div class="hidden">
    <form id="fn_fast_order" class="form form--boxed popup popup_animated fn_validate_fast_order" method="post" action="{url_generator route="OkayCMS.FastOrder.CreateOrder" absolute=1}"
          {if $settings->captcha_type == "invisible"}
              onsubmit="grecaptcha.execute(window.fastOrderRecaptcha); return false"
          {else}
              onsubmit="sendAjaxFastOrderForm(); return false"
          {/if}
    >
        {* The form heading *}
        <div class="form__header">
            <div class="form__title">
                <span data-language="fast_order">{$lang->fast_order}</span>
            </div>
        </div>

        {if $settings->captcha_type == "v3"}
            <input id="fn_fast_order_recaptcha_token" class="fn_recaptchav3" type="hidden" name="recaptcha_token" />
        {/if}

        <div class="form__body">
            <input id="fast_order_variant_id" value="" name="variant_id" type="hidden"/>
            <input value="" name="amount" type="hidden"/>
            <input type="hidden" name="IsFastOrder" value="true">

            <div class="message_error fn_fast_order_errors" style="display: none"></div>

            <div id="fast_order_product_name" class="h6"></div>

            <div class="form__group">
                <input class="fn_validate_fast_name form__input form__placeholder--focus" type="text" name="name" value="{if $request_data.name}{$request_data.name|escape}{elseif $user->name}{$user->name|escape}{/if}" />
                <span class="form__placeholder" data-language="form_name">{$lang->form_name}*</span>
            </div>

            <div class="form__group">
                <input class="fn_validate_fast_name form__input form__placeholder--focus" type="text" name="last_name" value="{if $request_data.last_name}{$request_data.last_name|escape}{elseif $user->last_name}{$user->last_name|escape}{/if}" />
                <span class="form__placeholder" data-language="form_name">{$lang->form_last_name}</span>
            </div>

            <div class="form__group">
                <input  class="fn_validate_fast_phone form__input form__placeholder--focus" type="text" name="phone" value="{if $request_data.phone}{$request_data.phone|escape}{elseif $user->phone}{$user->phone|escape}{/if}" />
                <span class="form__placeholder" data-language="form_phone">{$lang->form_phone}*</span>
            </div>
         </div>

        <div class="form__footer">
            {* Captcha *}
            {if $settings->captcha_fast_order}
                {if $settings->captcha_type == "v2" || $settings->captcha_type == "invisible"}
                    <div class="captcha">
                        <div id="recaptcha_fast_order"></div>
                    </div>
                {elseif $settings->captcha_type == "default"}
                    {get_captcha var="captcha_fast_order"}
                    <div class="captcha">
                        <div class="secret_number">{$captcha_fast_order[0]|escape} + ? =  {$captcha_fast_order[1]|escape}</div>
                        <span class="form__captcha">
                        <input class="form__input form__input_captcha form__placeholder--focus" type="text" name="captcha_code" value="" >
                        <span class="form__placeholder">{$lang->form_enter_captcha}*</span>
                    </span>
                    </div>
                {/if}
            {/if}

            <input class="form__button button--blick fn_fast_order_submit" type="submit" name="checkout" data-language="callback_order" value="{$lang->callback_order}"/>
        </div>
     </form>
</div>
```

### File: Okay/Modules/OkayCMS/FastOrder/design/html/recaptcha_init_invisible.tpl

```smarty
<script>
    let baseOnloadReCaptchaInvisible = onloadReCaptchaInvisible;
    var fastOrderRecaptcha;
    onloadReCaptchaInvisible = function() {
        baseOnloadReCaptchaInvisible();
        if(document.querySelector("#recaptcha_fast_order") !== null){
            fastOrderRecaptcha = grecaptcha.render('recaptcha_fast_order', {
                'sitekey' : "{$settings->public_recaptcha_invisible|escape}",
                "callback": "sendAjaxFastOrderForm",
                "size":"invisible"
            });
        }
    };

    var resetFastOrderCaptcha = function() {
        grecaptcha.reset(fastOrderRecaptcha);
    }

</script>
```

### File: Okay/Modules/OkayCMS/FastOrder/design/html/recaptcha_init_v2.tpl

```smarty
<script>
    let baseOnloadCallback = onloadCallback;
    var fastOrderRecaptcha;
    onloadCallback = function() {
        baseOnloadCallback();
        if(document.querySelector("#recaptcha_fast_order") !== null){
            fastOrderRecaptcha = grecaptcha.render('recaptcha_fast_order', {
                'sitekey' : "{$settings->public_recaptcha|escape}"
            });
        }
    };
    
    var resetFastOrderCaptcha = function() {
        grecaptcha.reset(fastOrderRecaptcha);
    }
    
</script>
```

### File: Okay/Modules/OkayCMS/FastOrder/design/html/recaptcha_init_v3.tpl

```smarty
<script>
    var resetFastOrderCaptcha = function() {
        grecaptcha.execute('{$settings->public_recaptcha_v3|escape}', { action: 'cart' })
            .then(function (token) {
                let capture = document.getElementById('fn_fast_order_recaptcha_token');
                capture.value = token;
            });
    };
    
</script>
```

### File: Okay/Modules/OkayCMS/FastOrder/design/js/fast_order.js

```javascript
$(document).on('click', '.fn_fast_order_button', function (e) {
    e.preventDefault();

    let variant,
        form_obj = $(this).closest("form.fn_variants");

    $("#fast_order_product_name").html($(this).data('name'));
    if (form_obj.find('input[name=variant]:checked').length > 0) {
        variant = form_obj.find('input[name=variant]:checked').val();
    }

    if (form_obj.find('select[name=variant]').length > 0) {
        variant = form_obj.find('select').val();
    }

    $("#fast_order_variant_id").val(variant);

    $.fancybox.open({
        src: '#fn_fast_order',
        type : 'inline'
    });
});

function sendAjaxFastOrderForm() {
    
    let $form      = $("#fn_fast_order"),
        action     = $form.attr('action'),
        $errorBlock = $form.find('.fn_fast_order_errors');

    $.ajax({
        url: action,
        type: 'post',
        data: $form.serialize(),
        dataType: 'json'
    }).done(function(response) {
        if (response.hasOwnProperty('success') && response.hasOwnProperty('redirect_location')) {
            window.location = response.redirect_location;
        } else if (response.hasOwnProperty('errors')) {

            if (typeof resetFastOrderCaptcha === "function") {
                resetFastOrderCaptcha();
            }
            let errorString = '';
            for (let error in response.errors) {
                errorString += '<div>' + response.errors[error] + '</div>';
            }
            $errorBlock.html(errorString).show();
            
        }
    });
    
}



```

### File: Okay/Modules/OkayCMS/FastOrder/design/lang/en.php

```php
<?php

$lang['fast_order'] = "Fast Order";
$lang['okay_cms__fast_order__form_required_error'] = 'Required field';
$lang['okay_cms__fast_order__form_name_error'] = 'Enter your name';
$lang['okay_cms__fast_order__form_phone_error'] = 'Incorrect phone number';
$lang['okay_cms__fast_order__order_submit'] = 'Order';
$lang['okay_cms__fast_order__form_captcha_error'] = 'Captcha entered incorrectly';
$lang['okay_cms__fast_order__wrong_variant'] = 'Selected product not found';

```

### File: Okay/Modules/OkayCMS/FastOrder/design/lang/ru.php

```php
<?php

$lang['fast_order'] = "Быстрый заказ";
$lang['okay_cms__fast_order__form_required_error'] = 'Обязательное поле';
$lang['okay_cms__fast_order__form_name_error'] = 'Введите имя';
$lang['okay_cms__fast_order__form_phone_error'] = 'Введите номер телефона';
$lang['okay_cms__fast_order__order_submit'] = 'Заказать';
$lang['okay_cms__fast_order__form_captcha_error'] = 'Неверно введена капча';
$lang['okay_cms__fast_order__wrong_variant'] = 'Выбранный товар не найден';

```

### File: Okay/Modules/OkayCMS/FastOrder/design/lang/ua.php

```php
<?php

$lang['fast_order'] = "Швидке замовлення";
$lang['okay_cms__fast_order__form_required_error'] = 'Обов\'язкове поле';
$lang['okay_cms__fast_order__form_name_error'] = 'Введіть ім\'я';
$lang['okay_cms__fast_order__form_phone_error'] = 'Введіть номер телефону';
$lang['okay_cms__fast_order__order_submit'] = 'Замовити';
$lang['okay_cms__fast_order__form_captcha_error'] = 'Невірно введена капча';
$lang['okay_cms__fast_order__wrong_variant'] = 'Обраний товар не знайдено';

```

### File: Okay/Modules/OkayCMS/FastOrder/Extenders/BackendExtender.php

```php
<?php


namespace Okay\Modules\OkayCMS\FastOrder\Extenders;


use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Request;
use Okay\Core\Settings;
use Okay\Modules\OkayCMS\FastOrder\Helpers\ValidateHelper;

class BackendExtender implements ExtensionInterface
{
    /** @var Settings $settings */
    private $settings;

    /** @var Request $request */
    private $request;

    /** @var ValidateHelper $validateHelper */
    private $validateHelper;

    public function __construct(
        Settings          $settings,
        Request           $request,
        ValidateHelper    $validateHelper
    )
    {
        $this->settings = $settings;
        $this->request = $request;
        $this->validateHelper = $validateHelper;
    }
    
    public function updateSettings()
    {
        $this->settings->update('captcha_fast_order', $this->request->post('captcha_fast_order'));
    }

    public function  ValidateFastOrder($order,$variantId)
    {

        $errors = $this->validateHelper->validateFastOrderHeler($order,$variantId);

        return ExtenderFacade::execute(__METHOD__, $errors, func_get_args());
    }
}
```

### File: Okay/Modules/OkayCMS/FastOrder/Backend/Controllers/DescriptionAdmin.php

```php
<?php


namespace Okay\Modules\OkayCMS\FastOrder\Backend\Controllers;


use Okay\Admin\Controllers\IndexAdmin;

class DescriptionAdmin extends IndexAdmin
{
    public function fetch()
    {
        $this->response->setContent($this->design->fetch('description.tpl'));
    }
}
```

### File: Okay/Modules/OkayCMS/FastOrder/Backend/lang/en.php

```php
<?php

$lang['okay_cms__fast_order__title'] = 'Fast order';
$lang['okay_cms__fast_order__code'] = 'Code';
$lang['okay_cms__fast_order__description'] = "After installing the module in the panel, you should add the code indicated under the description in the subject of your project next to the button for adding goods to the basket. This must be done in the list of goods, as well as on the product card page.";
$lang['okay_cms__fast_order__captcha'] = "In quick order";

```

### File: Okay/Modules/OkayCMS/FastOrder/Backend/lang/ge.php

```php
<?php

$lang['okay_cms__fast_order__title'] = 'შვიდკე ზამოვალნია';
$lang['okay_cms__fast_order__code'] = 'კოდი';
$lang['okay_cms__fast_order__description'] = "პანელში მოდულის დაყენების შემდეგ, თქვენ უნდა დაამატოთ აღწერილობის ქვეშ მითითებული კოდი, თქვენი პროექტის საგანი, რომელიც შეიცავს კალათაში საქონლის დამატების ღილაკს. ეს უნდა გაკეთდეს საქონლის ჩამონათვალში, ასევე პროდუქტის ბარათის გვერდზე.";
$lang['okay_cms__fast_order__captcha'] = "სწრაფი შეკვეთით";

```

### File: Okay/Modules/OkayCMS/FastOrder/Backend/lang/ru.php

```php
<?php

$lang['okay_cms__fast_order__title'] = 'Быстрый заказ';
$lang['okay_cms__fast_order__code'] = 'Код';
$lang['okay_cms__fast_order__description'] = "После установки модуля в панели следует добавить код указанный под данным описанием в теме вашего проекта рядом с кнопкой добавления товара в корзину. Сделать это необходимо в списке товаров, а также на странице карточки товара.";
$lang['okay_cms__fast_order__captcha'] = "В быстром заказе";

```

### File: Okay/Modules/OkayCMS/FastOrder/Backend/lang/ua.php

```php
<?php

$lang['okay_cms__fast_order__title'] = 'Швидке замовлення';
$lang['okay_cms__fast_order__code'] = 'Код';
$lang['okay_cms__fast_order__description'] = "Після установки модуля в панелі слід додати код вказаний під даним описом в темі вашого проекту поруч з кнопкою додавання товару в корзину. Зробити це необхідно в списку товарів, а також на сторінці картки товару.";
$lang['okay_cms__fast_order__captcha'] = "У швидкому замовленні";

```

### File: Okay/Modules/OkayCMS/FastOrder/Backend/design/html/activate_captcha_checkbox.tpl

```smarty
<div class="col-xl-3 col-lg-4 col-md-6">
    <div class="permission_box">
        <span>{$btr->okay_cms__fast_order__captcha|escape}</span>
        <label class="switch switch-default">
            <input class="switch-input" name="captcha_fast_order" value='1' type="checkbox" {if $settings->captcha_fast_order}checked=""{/if}/>
            <span class="switch-label"></span>
            <span class="switch-handle"></span>
        </label>
    </div>
</div>
```

### File: Okay/Modules/OkayCMS/FastOrder/Backend/design/html/description.tpl

```smarty
{$meta_title = $btr->okay_cms__fast_order__title|escape scope=global}

{*Название страницы*}
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">
                {$btr->okay_cms__fast_order__title|escape}
            </div>
        </div>
    </div>
</div>

<div class="row d_flex">
    <div class="col-lg-12 col-md-12">
        <div class="alert alert--icon">
            <div class="alert__content">
                <div class="alert__title">{$btr->alert_description|escape}</div>
                <p>{$btr->okay_cms__fast_order__description|escape}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="boxed">
            <h3 class="">{$btr->okay_cms__fast_order__code|escape}: {literal}{fast_order_btn product=$product}{/literal}</h3>
        </div>
    </div>
</div>

```

