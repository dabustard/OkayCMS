<?php

namespace Okay\Modules\Codex\RelatedProductsByFeatures\Extenders;

use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Request;
use Okay\Modules\Codex\RelatedProductsByFeatures\Init\Init;

class BackendExtender implements ExtensionInterface
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function extendPostFeature($feature)
    {
        $feature->{Init::FEATURE_WEIGHT_FIELD} = $this->request->post(Init::FEATURE_WEIGHT_FIELD, 'integer', 0);
        return $feature;
    }
}
