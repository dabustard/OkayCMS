<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons\Backend\Controllers;


use Okay\Admin\Helpers\BackendFeaturesValuesHelper;
use Okay\Admin\Helpers\BackendProductsHelper;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Helpers\AdvancedCouponsHelper;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Requests\AdvancedCouponsRequest;
use Okay\Admin\Helpers\BackendValidateHelper;
use Okay\Admin\Helpers\BackendFeaturesHelper;
use Okay\Admin\Controllers\IndexAdmin;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Entities\AdvancedCouponsEntity;
use Okay\Admin\Helpers\BackendCategoriesHelper;

class AdvancedCouponAdmin extends IndexAdmin
{

    public function fetch(
        BackendValidateHelper  $backendValidateHelper,
        AdvancedCouponsRequest $couponsRequest,
        AdvancedCouponsHelper  $backendCouponsHelper,
        BackendFeaturesHelper  $backendFeaturesHelper,
        BackendFeaturesValuesHelper $backendFeaturesValuesHelper,
        AdvancedCouponsEntity  $advancedCouponsEntity,
        BackendCategoriesHelper $backendCategoriesHelper,
        BackendProductsHelper $backendProductsHelper
    ){
        $id = $this->request->get('id','integer');
        if ($this->request->method('post')) {
            $coupon = $couponsRequest->postCoupon();

            if($error = $backendValidateHelper->getCouponsValidateError($coupon)) {
                $this->design->assign('message_error', $error);
                //todo вывод ошибки
            } elseif (empty($coupon->id)) {
                $coupon     = $backendCouponsHelper->prepareAdd($coupon);
                $id = $backendCouponsHelper->add($coupon);
                $this->design->assign('message_success', 'added');
            } else {
                $coupon     = $backendCouponsHelper->prepareUpdate($coupon);
                $id = $coupon->id;
                unset($coupon->id);
                $backendCouponsHelper->update($id,$coupon);
                $this->design->assign('message_success', 'updated');
            }
            if (!empty((int)$id)) {
                $coupon->id = $id;
                $couponCategories = $couponsRequest->postCategories();
                $backendCouponsHelper->updateCouponCategories($coupon, $couponCategories);
                $couponProducts   = $couponsRequest->postRelatedProducts();
                $backendCouponsHelper->updateRelatedProducts($coupon, $couponProducts);
            }

        }
        $coupon = $backendCouponsHelper->findCoupon(['id'=>$id]);

        $features = $backendFeaturesHelper->findFeatures();
        $features_names = [];
        $feature_values_names = [];
        $features_values_json = [];
        foreach ($features as $feature) {
            $features_names[$feature->id] = $feature->name;
            $features_values_json[$feature->id] = $backendFeaturesValuesHelper->findFeaturesValues(['feature_id'=>(int)$feature->id]);
            foreach($features_values_json[$feature->id] as $features_value) {
                $feature_values_names[$features_value->id] = $features_value->value;
            }
        }

        $categoriesTree    = $backendCategoriesHelper->getCategoriesTree();

        $couponCategoriesEntities = $advancedCouponsEntity->getRelatedEntities(['coupon_id'=>$id,'related_type'=>'category']);
        $couponProductsEntities = $advancedCouponsEntity->getRelatedEntities(['coupon_id'=>$id,'related_type'=>'product']);

        $couponCategories = [];
        foreach ($couponCategoriesEntities as $couponCategoriesEntity) {
            $couponCategories[(int)$couponCategoriesEntity->related_id] = $backendCategoriesHelper->getCategory((int)$couponCategoriesEntity->related_id);
        }

        $couponProducts = [];
        foreach ($couponProductsEntities as $couponProductsEntity) {
            $couponProducts[(int)$couponProductsEntity->related_id] = $backendProductsHelper->getProduct((int)$couponProductsEntity->related_id);
            if ($couponProducts[(int)$couponProductsEntity->related_id]) {
                $couponProducts[(int)$couponProductsEntity->related_id]->images = $backendProductsHelper->findProductImages($couponProducts[(int)$couponProductsEntity->related_id]);
            }
        }

        $this->design->assign('coupon_categories', $couponCategories);
        $this->design->assign('related_products', $couponProducts);
        $this->design->assign('categories', $categoriesTree);
        $this->design->assign('coupon',       $coupon);
        $this->design->assign('features',      $features);
        $this->design->assign('features_names',$features_names);
        $this->design->assign('feature_values_names',$feature_values_names);
        $this->design->assign('features_values_json',json_encode($features_values_json));
        $this->response->setContent($this->design->fetch('advanced_coupon.tpl'));
    }

}