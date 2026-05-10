<?php

use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\Request;
use Okay\Modules\Codex\VariantFeedToggle\Extensions\BackendProductsRequestExtension;

return [
    BackendProductsRequestExtension::class => [
        'class' => BackendProductsRequestExtension::class,
        'arguments' => [
            new SR(Request::class),
        ],
    ],
];
