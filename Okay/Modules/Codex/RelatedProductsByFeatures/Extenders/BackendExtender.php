<?php

namespace Okay\Modules\Codex\RelatedProductsByFeatures\Extenders;

use Okay\Core\Design;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Request;
use Okay\Modules\Codex\RelatedProductsByFeatures\Init\Init;

class BackendExtender implements ExtensionInterface
{
    private $request;
    private $design;

    public function __construct(Request $request, Design $design)
    {
        $this->request = $request;
        $this->design = $design;
    }

    public function assignFeatureWeight()
    {
        $feature = $this->design->getVar('feature');
        $weight = !empty($feature) && isset($feature->{Init::FEATURE_WEIGHT_FIELD}) ? (int)$feature->{Init::FEATURE_WEIGHT_FIELD} : 0;
        $this->design->assign('codex_related_products_by_features_weight', $weight);
    }

    public function extendPostFeature($feature)
    {
        $feature->{Init::FEATURE_WEIGHT_FIELD} = $this->request->post(Init::FEATURE_WEIGHT_FIELD, 'integer', 0);
        return $feature;
    }
}
