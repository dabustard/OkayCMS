<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons\Init;


use Okay\Core\Modules\EntityField;
use Okay\Core\Modules\AbstractInit;
use Okay\Core\Cart;
use Okay\Helpers\CartHelper;
use Okay\Helpers\MainHelper;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Extenders\FrontExtender;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Entities\AdvancedCouponsEntity;

class Init extends AbstractInit
{
    public function install()
    {
        $this->setBackendMainController('Description');
        $this->migrateEntityTable(AdvancedCouponsEntity::class, [
            (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('code'))->setTypeVarchar(255),
            (new EntityField('feature_id'))->setTypeInt(11, false)->setDefault(0)->setNullable(),
            (new EntityField('feature_value_id'))->setTypeInt(11, false)->setDefault(0)->setNullable(),
            (new EntityField('expire'))->setTypeTimestamp(),
            (new EntityField('type'))->setTypeEnum(['absolute','percentage'])->setDefault('absolute'),
            (new EntityField('value'))->setTypeDecimal('10,2')->setDefault(0),
            (new EntityField('min_order_price'))->setTypeDecimal('10,2',true),
            (new EntityField('single'))->setTypeTinyInt(1, false)->setDefault(0),
            (new EntityField('usages'))->setTypeInt(11, false)->setDefault(0),
            (new EntityField('summ'))->setTypeTinyInt(1, false)->setDefault(0),
        ]);

        $this->migrateCustomTable('__used_advanced_coupons', [
            (new EntityField('user_id'))->setTypeInt(11, false)->setDefault(0),
            (new EntityField('coupon_id'))->setTypeInt(11, false)->setDefault(0),
        ]);

        $this->migrateCustomTable('__advanced_coupons_entities', [
            (new EntityField('coupon_id'))->setTypeInt(11, false)->setDefault(0),
            (new EntityField('related_type'))->setTypeVarchar(255)->setDefault('product'),
            (new EntityField('related_id'))->setTypeInt(11, false)->setDefault(0),
        ]);
    }
    
    public function init()
    {
        $this->registerBackendController('AdvancedCouponsAdmin');
        $this->registerBackendController('AdvancedCouponAdmin');
        $this->registerBackendController('Description');
        $this->addBackendControllerPermission('AdvancedCouponsAdmin', 'advanced_coupons');
        $this->addBackendControllerPermission('AdvancedCouponAdmin', 'advanced_coupons');
        $this->addBackendControllerPermission('Description', 'advanced_coupons');

        $this->extendBackendMenu('left_users', [
            'left_advanced_coupons_title' => ['AdvancedCouponsAdmin','AdvancedCouponAdmin'],
        ]);


        $this->registerQueueExtension(
            ['class' => Cart::class, 'method' => 'applyCoupon'],
            ['class' => FrontExtender::class, 'method' => 'applyCoupon']
        );
        $this->registerCartDiscountSign(
            'sm_advanced_coupons',
            'sm_advanced_coupons_discount_coupon_name',
            'sm_advanced_coupons_discount_coupon_description'
        );


        $this->registerChainExtension(
            ['class' => Cart::class, 'method' => 'attachDiscounts'],
            ['class' => FrontExtender::class, 'method' => 'attachDiscounts']
        );

        $this->registerChainExtension(
            ['class' => Cart::class, 'method' => 'get'],
            ['class' => FrontExtender::class, 'method' => 'get']
        );

        $this->registerQueueExtension(
            ['class' => CartHelper::class, 'method' => 'cartToOrder'],
            ['class' => FrontExtender::class, 'method' => 'cartToOrderAddPromoCodeUsedInfo']
        );

        $this->registerQueueExtension(
            ['class' => MainHelper::class, 'method' => 'setDesignDataProcedure'],
            ['class' => FrontExtender::class, 'method' => 'findAdvansedCouponProducts']
        );
    }
}