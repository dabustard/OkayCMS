<?php


namespace Okay\Modules\Custom\ProductBottomText\Init;


use Okay\Admin\Requests\BackendProductsRequest;
use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Entities\ProductsEntity;
use Okay\Modules\Custom\ProductBottomText\Extenders\BackendProductsRequestExtender;

class Init extends AbstractInit
{
    public function install()
    {
        $this->migrateEntityTable(ProductsEntity::class, [
            (new EntityField('product_bottom_text'))->setTypeText(),
        ]);
    }

    public function init()
    {
        $this->registerEntityField(ProductsEntity::class, 'product_bottom_text');

        $this->addBackendBlock('product_custom_block', 'product_bottom_text.tpl');
        $this->addFrontBlock('front_before_footer_content', 'product_bottom_text.tpl');

        $this->registerChainExtension(
            [BackendProductsRequest::class, 'postProduct'],
            [BackendProductsRequestExtender::class, 'extendPostProduct']
        );
    }
}
