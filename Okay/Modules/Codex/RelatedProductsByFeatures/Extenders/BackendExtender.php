<?php

namespace Okay\Modules\Codex\RelatedProductsByFeatures\Extenders;

use Okay\Core\Database;
use Okay\Core\Design;
use Okay\Core\QueryFactory;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Request;
use Okay\Modules\Codex\RelatedProductsByFeatures\Init\Init;

class BackendExtender implements ExtensionInterface
{
    private $request;
    private $design;
    private $db;
    private $queryFactory;

    public function __construct(Request $request, Design $design, Database $db, QueryFactory $queryFactory)
    {
        $this->request = $request;
        $this->design = $design;
        $this->db = $db;
        $this->queryFactory = $queryFactory;
    }

    public function assignFeatureWeight()
    {
        $feature = $this->design->getVar('feature');
        $weight = 0;

        if (!empty($feature->id)) {
            $query = $this->queryFactory->newSqlQuery();
            $query->setStatement('SELECT weight FROM __features WHERE id = :feature_id LIMIT 1')
                ->bindValue('feature_id', (int)$feature->id);
            $this->db->query($query);
            $weight = (int)$this->db->result('weight');
        } elseif (!empty($feature) && isset($feature->{Init::FEATURE_WEIGHT_FIELD})) {
            $weight = (int)$feature->{Init::FEATURE_WEIGHT_FIELD};
        }

        $this->design->assign('codex_related_products_by_features_weight', $weight);
    }

    public function extendPostFeature($feature)
    {
        $feature->{Init::FEATURE_WEIGHT_FIELD} = $this->request->post(Init::FEATURE_WEIGHT_FIELD, 'integer', 0);
        return $feature;
    }
}
