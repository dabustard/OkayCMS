<?php

namespace Okay\Modules\Codex\VariantFeedToggle\Extensions;

use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Request;
use Okay\Modules\Codex\VariantFeedToggle\Init\Init;

class BackendProductsRequestExtension implements ExtensionInterface
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function extendPostVariants(array $variants)
    {
        $postedVariants = (array) $this->request->post('variants');
        $feedValues = isset($postedVariants[Init::FIELD_NAME]) ? (array) $postedVariants[Init::FIELD_NAME] : [];

        foreach ($variants as $index => $variant) {
            $variant->{Init::FIELD_NAME} = isset($feedValues[$index]) ? 1 : 0;
        }

        return ExtenderFacade::execute(__METHOD__, $variants, func_get_args());
    }
}
