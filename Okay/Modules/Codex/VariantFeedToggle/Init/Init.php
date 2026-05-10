<?php

namespace Okay\Modules\Codex\VariantFeedToggle\Init;

use Okay\Admin\Requests\BackendProductsRequest;
use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Entities\VariantsEntity;
use Okay\Modules\Codex\VariantFeedToggle\Extensions\BackendProductsRequestExtension;

class Init extends AbstractInit
{
    public const FIELD_NAME = 'codex__variant_feed_toggle__feed';

    public function install()
    {
        $this->migrateEntityField(
            VariantsEntity::class,
            (new EntityField(self::FIELD_NAME))->setTypeTinyInt(1)->setDefault(1)
        );
    }

    public function init()
    {
        $this->registerEntityField(VariantsEntity::class, self::FIELD_NAME);

        $this->addBackendBlock('product_variant', 'variant_feed.tpl');

        $this->registerChainExtension(
            [BackendProductsRequest::class, 'postVariants'],
            [BackendProductsRequestExtension::class, 'extendPostVariants']
        );
    }
}
