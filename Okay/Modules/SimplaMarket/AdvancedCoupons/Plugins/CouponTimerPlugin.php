<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons\Plugins;


use Okay\Core\Design;
use Okay\Core\SmartyPlugins\Func;

class CouponTimerPlugin extends Func
{
    protected $tag = 'advanced_coupon_timer';

    protected $design;

    public function __construct(Design $design)
    {
        $this->design = $design;
    }

    public function run($vars)
    {
        $this->design->assign('product_for_coupon', $vars['product']);
        $this->design->assign('advanced_coupon', $vars['advanced_coupon']);
        return $this->design->fetch('front_sale_timer.tpl');

    }
}