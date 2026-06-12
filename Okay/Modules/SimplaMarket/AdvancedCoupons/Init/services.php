<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons;


use Okay\Core\Classes\Discount;
use Okay\Core\Database;
use Okay\Core\Design;
use Okay\Core\EntityFactory;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\OkayContainer\Reference\ParameterReference as PR;
use Okay\Core\QueryFactory;
use Okay\Helpers\ProductsHelper;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Helpers\AdvancedCouponsHelper;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Requests\AdvancedCouponsRequest;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Extenders\FrontExtender;
use Okay\Core\Request;

return [
    AdvancedCouponsHelper::class => [
        'class' => AdvancedCouponsHelper::class,
        'arguments' => [
            new SR(EntityFactory::class),
            new SR(QueryFactory::class),
            new SR(Request::class),
            new SR(Database::class),
        ]
    ],
    AdvancedCouponsRequest::class => [
        'class' => AdvancedCouponsRequest::class,
        'arguments' => [
            new SR(Request::class),
        ]
    ],
    FrontExtender::class => [
        'class' => FrontExtender::class,
        'arguments' => [
            new SR(Design::class),
            new SR(EntityFactory::class),
            new SR(QueryFactory::class),
            new SR(ProductsHelper::class),
            new SR(Request::class),
//            new SR(Discount::class),
        ],
    ],
];