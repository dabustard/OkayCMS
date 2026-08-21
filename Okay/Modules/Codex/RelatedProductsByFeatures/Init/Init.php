<?php

namespace Okay\Modules\Codex\RelatedProductsByFeatures\Init;

use Okay\Admin\Requests\BackendFeaturesRequest;
use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Entities\FeaturesEntity;
use Okay\Modules\Codex\RelatedProductsByFeatures\Extenders\BackendExtender;

class Init extends AbstractInit
{
    public const FEATURE_WEIGHT_FIELD = 'weight';
    public const PERMISSION = 'codex__related_products_by_features';

    public const SETTING_CATEGORY_WEIGHT = 'codex_related_products_by_features_category_weight';
    public const SETTING_DEFAULT_LIMIT = 'codex_related_products_by_features_default_limit';
    public const SETTING_DISCONTINUED_LIMIT = 'codex_related_products_by_features_discontinued_limit';
    public const SETTING_INCLUDE_DISCONTINUED_RELATED = 'codex_related_products_by_features_include_discontinued_related';
    public const SETTING_ONLY_VISIBLE_PRODUCTS = 'codex_related_products_by_features_only_visible_products';
    public const SETTING_BATCH_SIZE = 'codex_related_products_by_features_batch_size';

    public function install()
    {
        $this->setBackendMainController('RelatedProductsByFeaturesAdmin');
        $this->migrateEntityField(
            FeaturesEntity::class,
            (new EntityField(self::FEATURE_WEIGHT_FIELD))->setTypeInt(11, false)->setDefault(0)->setIndex()
        );
    }

    public function init()
    {
        $this->registerBackendController('RelatedProductsByFeaturesAdmin');
        $this->addBackendControllerPermission('RelatedProductsByFeaturesAdmin', self::PERMISSION);

        $this->registerEntityField(FeaturesEntity::class, self::FEATURE_WEIGHT_FIELD);

        $this->registerChainExtension(
            [BackendFeaturesRequest::class, 'postFeature'],
            [BackendExtender::class, 'extendPostFeature']
        );

        $this->addBackendBlock('feature_custom_block', 'feature_weight_block.tpl');
        $this->extendBackendMenu('left_catalog', [
            'codex_related_products_by_features__menu' => ['RelatedProductsByFeaturesAdmin'],
        ]);
    }
}
