<?php


use Okay\Core\Design;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Plugins\CouponTimerPlugin;
use Okay\Core\OkayContainer\Reference\ParameterReference as PR;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;

return [
    CouponTimerPlugin::class => [
        'class' => CouponTimerPlugin::class,
        'arguments' => [
            new SR(Design::class)
        ],
    ],
];