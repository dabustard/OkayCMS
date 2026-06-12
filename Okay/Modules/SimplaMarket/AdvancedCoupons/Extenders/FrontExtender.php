<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons\Extenders;


use Okay\Core\Design;
use Okay\Core\EntityFactory;
use Okay\Core\QueryFactory;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Helpers\ProductsHelper;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Entities\AdvancedCouponsEntity;
use Okay\Core\Classes\Discount;
use Okay\Core\Request;

class FrontExtender implements ExtensionInterface
{
    private $design;
    private $queryFactory;
    private $advancedCouponsEntity;
    private $categoriesEntity;
    private $productsEntity;
    private $productsHelper;
    private $noErrorCoupon = false;

    public function __construct(
        Design $design,
        EntityFactory $entityFactory,
        QueryFactory $queryFactory,
        ProductsHelper $productsHelper,
        Request $request
    )
    {
        $this->design = $design;
        $this->request = $request;
        $this->entityFactory = $entityFactory;
        $this->queryFactory = $queryFactory;
        $this->productsHelper = $productsHelper;
        $this->advancedCouponsEntity = $entityFactory->get(AdvancedCouponsEntity::class);
        $this->categoriesEntity = $entityFactory->get(CategoriesEntity::class);
        $this->productsEntity = $entityFactory->get(ProductsEntity::class);
        if (count($this->advancedCouponsEntity->find([])) > 0) {
            $this->design->assign('coupon_request', true);
        }
    }
    /*Применение купона в корзине*/
    public function applyCoupon($cart, $couponCode)
    {
        $coupon = $this->advancedCouponsEntity->get($couponCode);
        if (!empty($coupon) || (!empty($cart->coupon) && !empty($this->advancedCouponsEntity->get($cart->coupon->code)))) {
            $this->noErrorCoupon = false;
            unset($cart->coupon);
        }
        if($coupon) {
                $_SESSION['advanced_coupon_code'] = $coupon->code;
        } else {
            unset($_SESSION['advanced_coupon_code']);
        }
        return $cart;
    }

    public function get($cart) {
        if ($this->noErrorCoupon) {
            $this->design->assign('coupon_error', false);
        }
        return $cart;
    }

    public function attachDiscounts($cart)
    {
        if ($this->couponCodeExists()) {
            $cart->coupon = $this->advancedCouponsEntity->get($_SESSION['advanced_coupon_code']);

            if($cart->coupon && $cart->coupon->valid && $cart->basic_total_price >= $cart->coupon->min_order_price) {
                if (empty($cart->coupon->single)) {
                    $used_advanced_coupon = false;
                } else {
                    $sql = $this->queryFactory->newSqlQuery();
                    $used_advanced_coupon = $sql->setStatement("SELECT coupon_id FROM __used_advanced_coupons WHERE `coupon_id` = :coupon_id AND `user_id` = :user_id LIMIT 1")->bindValues(['coupon_id' => (int)$cart->coupon->id,'user_id'=>(int)$_SESSION['user_id']])->result();
                }
                if (empty($used_advanced_coupon)) {
                    if ($cart->coupon->feature_id > 0) {
                        $products = [];
                        foreach ($cart->purchases as $purchase) {
                            $products[(int)$purchase->product->id] = $purchase->product;
                        }
                        if (!empty($cart->coupon->feature_id) && !empty($cart->coupon->feature_value_id)) {
                            $products = $this->productsHelper->attachFeaturesAll($products, ['id' => [(int)$cart->coupon->feature_id]], ['id' => [(int)$cart->coupon->feature_value_id]]);
                        } elseif (!empty($cart->coupon->feature_id)) {
                            $products = $this->productsHelper->attachFeaturesAll($products, ['id' => [(int)$cart->coupon->feature_id]]);
                        }

                        $products_for_coupon_ids = [];
                        if (!empty($products)) {
                            foreach ($products as $product) {
                                if (!empty($product->features)) {
                                    $products_for_coupon_ids[] = $product->id;
                                }
                            }
                        }
                    } else {
                        $products_for_coupon_ids = [];
                        $categories_for_coupon_ids = [];
                        $categoriesEntities = $this->advancedCouponsEntity->getRelatedEntities(['coupon_id'=>(int)$cart->coupon->id]);
                        foreach ($categoriesEntities as $categoriesEntity) {
                            if ($categoriesEntity->related_type == 'product') {
                                $products_for_coupon_ids[] = (int)$categoriesEntity->related_id;
                            } elseif ($categoriesEntity->related_type == 'category') {
                                $categories_for_coupon_ids[] = (int)$categoriesEntity->related_id;
                            }
                        }

                        foreach ($categories_for_coupon_ids as $categories_for_coupon_id) {
                            $category = $this->categoriesEntity->get((int)$categories_for_coupon_id);
                            if (!empty($category->children)) {
                                $products = $this->productsEntity->find(['main_category_id'=>$category->children]);
                                foreach ($products as $product) {
                                    $products_for_coupon_ids[] = $product->id;
                                }
                            }
                        }

                        $products_for_coupon_ids = array_unique($products_for_coupon_ids);
                    }

                    $total_price_for_coupon = 0;
                    foreach ($cart->purchases as $purchase) {
                        if (in_array($purchase->product->id,$products_for_coupon_ids) && (!empty($purchase->undiscounted_price) || $purchase->variant->compare_price == $purchase->variant->price)) {
                            $total_price_for_coupon += $purchase->undiscounted_price * $purchase->amount;
                            $purchase->discountID=1;
                        }
                        else{
                            $purchase->discountID=0;
                        }
                    }
                    if (!empty($total_price_for_coupon)) {
                        $this->noErrorCoupon = true;
                        $this->design->assign('coupon_error', false);
                        $this->design->assign('advanced_coupon', true);
                        $cart->coupon = $this->advancedCouponsEntity->get($_SESSION['advanced_coupon_code']);
                        $discount = new Discount();
                        $discount->sign = 'sm_advanced_coupons';
                        $discount->langParts['coupon'] = $cart->coupon->code;

                        if($cart->coupon->type == 'percentage') {
                            //$discount->type = 'percent';
                            $value = $total_price_for_coupon / 100 * $cart->coupon->value;
                        } else {
                            $value = $cart->coupon->value;
                        }
                        $discount->type = 'absolute';
                        $discount->value = $value;
                        // вывод процента  или рублей
                        $discount->custAbsoluteDiscount = $cart->coupon->value;
                        $discount->custPercentDiscount = $cart->coupon->type;
                        $discount->productsIDs = $products_for_coupon_ids;
                        //
                        $cart->availableDiscounts['sm_advanced_coupons'] = $discount;
                        } else {
                        $this->design->assign('coupon_error', 'not_found');
                        unset($_SESSION['advanced_coupon_code']);
                    }
                } else {
                    unset($_SESSION['advanced_coupon_code']);
                }
            } else {
                if ($cart->basic_total_price >= $cart->coupon->min_order_price) {
                    $this->design->assign('coupon_error2', 'min_order_price');
                }
                unset($_SESSION['advanced_coupon_code']);
            }
        }
        return $cart;
    }

    public function cartToOrderAddPromoCodeUsedInfo($cart, $orderId)
    {
//        if (!empty($_SESSION['advanced_coupon_code']) && !empty($_SESSION['user_id'])) {
            $sql = $this->queryFactory->newSqlQuery();
            $used_advanced_coupon = $sql->setStatement("SELECT coupon_id FROM __used_advanced_coupons WHERE `coupon_id` = :coupon_id AND `user_id` = :user_id LIMIT 1")->bindValues(['coupon_id' => (int)$cart->coupon->id,'user_id'=>(int)$_SESSION['user_id']])->result();
            if (empty($used_advanced_coupon)) {
                $sql = $this->queryFactory->newSqlQuery();
                $sql->setStatement("INSERT INTO __used_advanced_coupons SET coupon_id = :coupon_id, user_id = :user_id ")->bindValues(['coupon_id' => (int)$cart->coupon->id,'user_id'=>(int)$_SESSION['user_id']])->execute();
                $this->advancedCouponsEntity->update((int)$cart->coupon->id,['usages'=>((int)$cart->coupon->usages+1)]);
            }

//        }
    }

    private function couponCodeExists()
    {
        if(empty($_SESSION['advanced_coupon_code'])) {
            return false;
        }

        return true;
    }
    public function findAdvansedCouponProducts()
    {
        $products_ids = [];

        $sql = $this->queryFactory->newSqlQuery();
        $products_products_ids = $sql->setStatement("
            SELECT ace.related_id as product_id, (UNIX_TIMESTAMP(ac.expire)-UNIX_TIMESTAMP(NOW())) as expire  FROM __advanced_coupons ac 
            INNER JOIN __advanced_coupons_entities ace ON ac.id=ace.coupon_id 
            WHERE ace.related_type = 'product' AND DATE(NOW()) <= DATE(ac.expire) AND ac.expire IS NOT NULL")
            ->results();

        $sql = $this->queryFactory->newSqlQuery();
        $products_categories_ids = $sql->setStatement("SELECT p.id as product_id, (UNIX_TIMESTAMP(new.expire)-UNIX_TIMESTAMP(NOW())) as expire FROM __products p INNER JOIN 
            (SELECT ace.related_id as cat_id, ac.expire  FROM __advanced_coupons ac 
            INNER JOIN __advanced_coupons_entities ace ON ac.id=ace.coupon_id 
            WHERE ace.related_type = 'category' AND DATE(NOW()) <= DATE(ac.expire) AND ac.expire IS NOT NULL) AS new ON new.cat_id=p.main_category_id")
            ->results();

        $sql = $this->queryFactory->newSqlQuery();
        $products_values_ids = $sql->setStatement("
            SELECT pfv.product_id, (UNIX_TIMESTAMP(ac.expire)-UNIX_TIMESTAMP(NOW())) as expire  FROM __advanced_coupons ac 
            INNER JOIN __products_features_values pfv ON ac.feature_value_id=pfv.value_id 
            WHERE DATE(NOW()) <= DATE(ac.expire) AND ac.expire IS NOT NULL AND ac.feature_id > 0 AND ac.feature_value_id > 0")
            ->results();

        $sql = $this->queryFactory->newSqlQuery();
        $products_features_ids = $sql->setStatement("
            SELECT pfv.product_id, (UNIX_TIMESTAMP(ac.expire)-UNIX_TIMESTAMP(NOW())) as expire FROM __advanced_coupons ac 
            INNER JOIN __features_values fv ON ac.feature_id=fv.feature_id
            INNER JOIN __products_features_values pfv ON fv.id=pfv.value_id
            WHERE DATE(NOW()) <= DATE(ac.expire) AND ac.expire IS NOT NULL AND ac.feature_id > 0  AND ac.feature_value_id = 0")
            ->results();

        $products_array = array_merge($products_products_ids, $products_categories_ids, $products_values_ids, $products_features_ids);

        foreach ($products_array as $product) {
            if(!isset($products_ids[$product->product_id])){
                $products_ids[$product->product_id] = $product->expire;
            } else {
                $products_ids[$product->product_id] = max($product->expire, $products_ids[$product->product_id]);
            }
        }

        $this->design->assign('advanced_coupon_products',  $products_ids);

    }
}