<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons\Plugins;


use Okay\Core\QueryFactory;
use Okay\Core\SmartyPlugins\Func;

class ProductCouponCodesPlugin extends Func
{
    protected $tag = 'advanced_coupon_codes';

    private $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function run($params)
    {
        if (empty($params['product_id'])) {
            return '';
        }

        $productId = (int)$params['product_id'];
        if ($productId <= 0) {
            return '';
        }

        $sql = $this->queryFactory->newSqlQuery();
        $couponCode = $sql->setStatement("
            SELECT ac.code
            FROM __advanced_coupons ac
            INNER JOIN __products_features_values pfv ON pfv.product_id = :product_id
            INNER JOIN __features_values fv ON fv.id = pfv.value_id
            WHERE ac.feature_id > 0
                AND (DATE(NOW()) <= DATE(ac.expire) OR ac.expire IS NULL)
                AND (
                    (ac.feature_value_id > 0 AND ac.feature_value_id = pfv.value_id)
                    OR ((ac.feature_value_id = 0 OR ac.feature_value_id IS NULL) AND ac.feature_id = fv.feature_id)
                )
            ORDER BY ac.id DESC
            LIMIT 1
        ")->bindValue('product_id', $productId)->result('code');

        return !empty($couponCode) ? $couponCode : '';
    }
}
