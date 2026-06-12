<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons\Backend\Controllers;


use Okay\Modules\SimplaMarket\AdvancedCoupons\Helpers\AdvancedCouponsHelper;
use Okay\Modules\SimplaMarket\AdvancedCoupons\Requests\AdvancedCouponsRequest;
use Okay\Admin\Helpers\BackendValidateHelper;
use Okay\Admin\Helpers\BackendFeaturesHelper;
use Okay\Admin\Helpers\BackendFeaturesValuesHelper;
use Okay\Admin\Controllers\IndexAdmin;

class AdvancedCouponsAdmin extends IndexAdmin
{

    public function fetch(
        BackendValidateHelper  $backendValidateHelper,
        AdvancedCouponsRequest $couponsRequest,
        AdvancedCouponsHelper  $backendCouponsHelper,
        BackendFeaturesHelper  $backendFeaturesHelper,
        BackendFeaturesValuesHelper  $backendFeaturesValuesHelper
    ){
        if ($this->request->method('post')) {
            $ids = $couponsRequest->postCheck();
            switch ($couponsRequest->postAction()) {
                case 'delete': {
                    $backendCouponsHelper->delete($ids);
                    break;
                }
            }
        }

        $filter         = $backendCouponsHelper->buildFilter();
        $couponsCount   = $backendCouponsHelper->count($filter);
        $pagesCount     = ceil($couponsCount/$filter['limit']);
        $filter['page'] = min($filter['page'], $pagesCount);
        $coupons        = $backendCouponsHelper->findCoupons($filter);

        if (isset($filter['keyword'])) {
            $this->design->assign('keyword', $filter['keyword']);
        }

        $this->design->assign('coupons_count', $couponsCount);
        $this->design->assign('pages_count',   $pagesCount);
        $this->design->assign('current_page',  $filter['page']);
        $this->design->assign('coupons',       $coupons);
        $this->response->setContent($this->design->fetch('advanced_coupons.tpl'));
    }

}