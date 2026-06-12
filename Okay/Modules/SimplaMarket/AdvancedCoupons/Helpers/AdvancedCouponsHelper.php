<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons\Helpers;


use Okay\Core\Database;
use Okay\Core\EntityFactory;
use Okay\Core\QueryFactory;
use Okay\Core\Request;
use Okay\Entities\CategoriesEntity;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Entities\AdvancedCouponsEntity;
use Okay\Core\Modules\Extender\ExtenderFacade;

class AdvancedCouponsHelper
{
    /**
     * @var AdvancedCouponsEntity
     */
    private $couponsEntity;

    /**
     * @var Request
     */
    private $request;
    private $db;
    private $queryFactory;
    private $categoriesEntity;

    public function __construct(EntityFactory $entityFactory,QueryFactory $queryFactory, Request $request, Database $db)
    {
        $this->couponsEntity = $entityFactory->get(AdvancedCouponsEntity::class);
        $this->categoriesEntity = $entityFactory->get(CategoriesEntity::class);
        $this->queryFactory = $queryFactory;
        $this->request = $request;
        $this->db = $db;
    }

    public function buildFilter()
    {
        $filter = [];
        $filter['page'] = max(1, $this->request->get('page', 'integer'));
        $filter['limit'] = 20;

        $keyword = $this->request->get('keyword', 'string');
        if (!empty($keyword)) {
            $filter['keyword'] = $keyword;
        }

        return ExtenderFacade::execute(__METHOD__, $filter, func_get_args());
    }

    public function delete($ids)
    {
        $this->couponsEntity->delete($ids);
        ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }

    public function findCoupons($filter)
    {
        $coupons = $this->couponsEntity->find($filter);
        return ExtenderFacade::execute(__METHOD__, $coupons, func_get_args());
    }

    public function findCoupon($filter)
    {
        $coupons = $this->couponsEntity->findOne($filter);
        return ExtenderFacade::execute(__METHOD__, $coupons, func_get_args());
    }

    public function count($filter)
    {
        $coupons = $this->couponsEntity->count($filter);
        return ExtenderFacade::execute(__METHOD__, $coupons, func_get_args());
    }

    public function prepareAdd($coupon)
    {
        return ExtenderFacade::execute(__METHOD__, $coupon, func_get_args());
    }
    public function prepareUpdate($coupon)
    {
        return ExtenderFacade::execute(__METHOD__, $coupon, func_get_args());
    }

    public function add($coupon)
    {
        $insertId = $this->couponsEntity->add($coupon);
        return ExtenderFacade::execute(__METHOD__, $insertId, func_get_args());
    }

    public function update($id,$coupon)
    {
        $this->couponsEntity->update($id,$coupon);

        ExtenderFacade::execute(__METHOD__, $coupon, func_get_args());
    }

    public function updateCouponCategories($coupon, $coupon_Categories)
    {
        /*
        $delete = $this->queryFactory->newDelete();
        $delete->from('__advanced_coupons_entities')
            ->where('coupon_id=:coupon_id')
            ->where('related_type=:related_type')
            ->bindValue('coupon_id', $coupon->id)
            ->bindValue('related_type', 'category');

            $this->db->query($delete);
        */

        $this->couponsEntity->deleteRelatedEntity($coupon->id,'category');
        if (is_array($coupon_Categories)) {
            foreach($coupon_Categories as $category) {
                $this->couponsEntity->addRelatedEntity($coupon->id, 'category', $category->id);
            }
        }

        ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }

    public function updateRelatedProducts($coupon, $relatedProducts)
    {
        $this->couponsEntity->deleteRelatedEntity($coupon->id,'product');
        if (is_array($relatedProducts)) {
            foreach($relatedProducts  as $i=>$relatedProduct) {
                $this->couponsEntity->addRelatedEntity($coupon->id, 'product', $relatedProduct->related_id);
            }
        }

        ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }
}