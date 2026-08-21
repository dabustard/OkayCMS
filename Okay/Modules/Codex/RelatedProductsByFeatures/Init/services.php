<?php

namespace Okay\Modules\Codex\RelatedProductsByFeatures;

use Okay\Core\Database;
use Okay\Core\Design;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\QueryFactory;
use Okay\Core\Request;
use Okay\Core\Settings;
use Okay\Modules\Codex\RelatedProductsByFeatures\Extenders\BackendExtender;
use Okay\Modules\Codex\RelatedProductsByFeatures\Helpers\RelatedProductsCalculator;

return [
    BackendExtender::class => [
        'class' => BackendExtender::class,
        'arguments' => [
            new SR(Request::class),
            new SR(Design::class),
            new SR(Database::class),
            new SR(QueryFactory::class),
        ],
    ],
    RelatedProductsCalculator::class => [
        'class' => RelatedProductsCalculator::class,
        'arguments' => [
            new SR(Database::class),
            new SR(QueryFactory::class),
            new SR(Settings::class),
        ],
    ],
];
