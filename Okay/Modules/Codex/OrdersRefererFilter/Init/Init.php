<?php

namespace Okay\Modules\Codex\OrdersRefererFilter\Init;

use Okay\Admin\Helpers\BackendOrdersHelper;
use Okay\Core\Modules\AbstractInit;
use Okay\Modules\Codex\OrdersRefererFilter\Extensions\BackendOrdersHelperExtension;

class Init extends AbstractInit
{
    public function install()
    {
    }

    public function init()
    {
        $this->registerChainExtension(
            [BackendOrdersHelper::class, 'buildFilter'],
            [BackendOrdersHelperExtension::class, 'extendBuildFilter']
        );

        $this->registerChainExtension(
            [BackendOrdersHelper::class, 'buildCountStatusesFilter'],
            [BackendOrdersHelperExtension::class, 'extendBuildCountStatusesFilter']
        );
    }
}
