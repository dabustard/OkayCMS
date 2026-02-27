<?php


namespace Okay\Modules\Custom\ProductBottomText\Extenders;


use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Request;

class BackendProductsRequestExtender implements ExtensionInterface
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function extendPostProduct($product)
    {
        $product->product_bottom_text = $this->request->post('product_bottom_text');

        return ExtenderFacade::execute(__METHOD__, $product, func_get_args());
    }
}
