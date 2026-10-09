<?php

namespace Okay\Modules\Codex\OrdersRefererFilter;

use Okay\Core\Design;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\Request;
use Okay\Modules\Codex\OrdersRefererFilter\Extensions\BackendOrdersHelperExtension;

return [
    BackendOrdersHelperExtension::class => [
        'class' => BackendOrdersHelperExtension::class,
        'arguments' => [
            new SR(Request::class),
            new SR(Design::class),
        ],
    ],
];
