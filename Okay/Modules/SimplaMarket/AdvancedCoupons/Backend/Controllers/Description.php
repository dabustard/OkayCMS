<?php


namespace Okay\Modules\SimplaMarket\AdvancedCoupons\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;

class Description extends IndexAdmin
{

    public function fetch(
    ){
        $this->response->setContent($this->design->fetch('description.tpl'));
    }

}