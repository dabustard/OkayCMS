<?php


use Okay\Core\Design;
use Okay\Core\QueryFactory;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Plugins\CouponTimerPlugin;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Plugins\ProductCouponCodesPlugin;
use Okay\Core\OkayContainer\Reference\ParameterReference as PR;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;

return [
    CouponTimerPlugin::class => [
        'class' => CouponTimerPlugin::class,
        'arguments' => [
            new SR(Design::class)
        ],
    ],
    ProductCouponCodesPlugin::class => [
        'class' => ProductCouponCodesPlugin::class,
        'arguments' => [
            new SR(QueryFactory::class)
        ],
    ],
];
