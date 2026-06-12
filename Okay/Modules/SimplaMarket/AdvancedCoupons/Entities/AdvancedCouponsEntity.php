<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons\Entities;


use Okay\Core\Entity\Entity;
use Okay\Core\Modules\Extender\ExtenderFacade;

class AdvancedCouponsEntity extends Entity
{

    protected static $fields = [
        'id',
        'code',
        'feature_id',
        'feature_value_id',
        'expire',
        'type',
        'value',
        'min_order_price',
        'single',
        'usages',
        'summ',
    ];

    protected static $additionalFields = [
        '((DATE(NOW()) <= DATE(ac.expire) OR ac.expire IS NULL)) AS valid',
    ];

    protected static $searchFields = [
        'code',
    ];

    protected static $defaultOrderFields = [
        'valid DESC',
        'id DESC'
    ];

    protected static $table = '__advanced_coupons';
    protected static $tableAlias = 'ac';
    protected static $alternativeIdField = 'code';


    public function getRelatedEntities(array $filter = [])
    {
        if (empty($filter['coupon_id'])) {
            return ExtenderFacade::execute([static::class, __FUNCTION__], [], func_get_args());
        }

        $select = $this->queryFactory->newSelect();
        $select->cols([
            'coupon_id',
            'related_type',
            'related_id',
        ])->from('__advanced_coupons_entities')
            ->where('coupon_id IN (:coupons_ids)')
            ->bindValue('coupons_ids', (array)$filter['coupon_id']);

        if (!empty($filter['related_type'])) {
            $select->where('related_type=:related_type')
                ->bindValue('related_type', $filter['related_type']);
        }
        if(!empty($filter['related_id'])) {
            if (!empty((int)$filter['related_id'])) {
                $select->where('related_id=:related_id')
                    ->bindValue('related_id', (int)$filter['related_id']);
            }
        }
        $this->db->query($select);
        return ExtenderFacade::execute([static::class, __FUNCTION__], $this->db->results(), func_get_args());
    }

    /*Добавление связанных товаров*/
    public function addRelatedEntity($couponId, $relatedType, $relatedId) {
        $insert = $this->queryFactory->newInsert();
        $insert->into('__advanced_coupons_entities')
            ->cols([
                'coupon_id',
                'related_type',
                'related_id',
            ])
            ->bindValues([
                'coupon_id' => $couponId,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
            ])
            ->ignore();

        $this->db->query($insert);
        return ExtenderFacade::execute([static::class, __FUNCTION__], $relatedId, func_get_args());
    }

    /*Удаление связанных товаров*/
    public function deleteRelatedEntity($couponId, $relatedType = '', $relatedId = null)
    {
        $delete = $this->queryFactory->newDelete();
        $delete->from('__advanced_coupons_entities')
            ->where('coupon_id=:coupon_id')
            ->bindValue('coupon_id', (int)$couponId);

        if ($relatedId !== null) {
            $delete->where('related_id=:related_id')
                ->bindValue('related_id', (int)$relatedId);
        }

        if ($relatedType !== '') {
            $delete->where('related_type=:related_type')
                ->bindValue('related_type', $relatedType);
        }
        $this->db->query($delete);

        ExtenderFacade::execute([static::class, __FUNCTION__], null, func_get_args());
    }

    public function deleteRelatedEntities($couponIds, $relatedType, $relatedIds = null)
    {
        $delete = $this->queryFactory->newDelete();
        $delete->from('__advanced_coupons_entities')
            ->where('coupon_id IN(:coupons_ids)')
            ->bindValue('coupons_ids', (array)$couponIds);

        if ($relatedType !== '') {
            $delete->where('related_type=:related_type')
                ->bindValue('related_type', $relatedType);
        }

        if ($relatedIds === null) {
            $this->db->query($delete);
            ExtenderFacade::execute([static::class, __FUNCTION__], null, func_get_args());
        }

        $delete->where('related_id IN(:related_ids)')
            ->bindValue('related_ids', (int)$relatedIds);
        $this->db->query($delete);
        ExtenderFacade::execute([static::class, __FUNCTION__], null, func_get_args());
    }

    protected function filter__valid($valid)
    {
        $validFilter = '(DATE(NOW()) <= DATE(ac.expire) OR ac.expire IS NULL)';
        if (empty($valid)) {
            $validFilter = 'NOT ' . $validFilter;
        }
        $this->select->where($validFilter);
    }

}