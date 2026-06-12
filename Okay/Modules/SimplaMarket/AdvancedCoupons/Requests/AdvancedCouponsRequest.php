<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons\Requests;


use Okay\Core\Request;
use Okay\Core\Modules\Extender\ExtenderFacade;

class AdvancedCouponsRequest
{
    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    public function postCoupon()
    {
        $coupon                     = new \stdClass();
        $coupon->id                 = $this->request->post('id', 'integer');
        $coupon->code               = $this->request->post('code', 'string');
        $coupon->value              = $this->request->post('value', 'float');
        $coupon->type               = $this->request->post('type', 'string');
        $coupon->single             = $this->request->post('single', 'integer');
        $coupon->summ               = $this->request->post('summ', 'integer');
        $coupon->min_order_price    = $this->request->post('min_order_price', 'float');

        if($this->request->post('feature_id')){
            $coupon->feature_id         = $this->request->post('feature_id', 'integer');
            $coupon->feature_value_id   = $this->request->post('feature_value_id', 'integer');
        } else {
            $coupon->feature_id         = 0;
            $coupon->feature_value_id   = 0;
        }

        $expired = $this->request->post('expire');
        if (!empty($expired)) {
            $coupon->expire = date('Y-m-d', strtotime($expired));
        } else {
            $coupon->expire = null;
        }

        return ExtenderFacade::execute(__METHOD__, $coupon, func_get_args());
    }

    public function postCheck()
    {
        $check = $this->request->post('check');
        return ExtenderFacade::execute(__METHOD__, $check, func_get_args());
    }

    public function postAction()
    {
        $check = $this->request->post('action');
        return ExtenderFacade::execute(__METHOD__, $check, func_get_args());
    }

    public function postCategories()
    {
        $productCategories = $this->request->post('categories');
        if (is_array($productCategories)) {
            $pc = [];
            foreach ($productCategories as $c) {
                if (!empty($c)) {
                    $x = new \stdClass();
                    $x->id = $c;
                    $pc[$x->id] = $x;
                }
            }
            $productCategories = $pc;
        }

        return ExtenderFacade::execute(__METHOD__, $productCategories, func_get_args());
    }

    public function postRelatedProducts()
    {

        if (is_array($this->request->post('related_products'))) {
            $rp = [];
            foreach($this->request->post('related_products') as $p) {
                if (!empty($p)) {
                    $rp[$p] = new \stdClass();
                    $rp[$p]->product_id = $this->request->post('id', 'integer');
                    $rp[$p]->related_id = $p;
                }
            }
            $relatedProducts = $rp;
        } else {
            $relatedProducts = [];
        }

        return ExtenderFacade::execute(__METHOD__, $relatedProducts, func_get_args());
    }
}