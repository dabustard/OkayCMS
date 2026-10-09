<?php

namespace Okay\Modules\Codex\OrdersRefererFilter\Extensions;

use Okay\Core\Design;
use Okay\Core\Request;

class BackendOrdersHelperExtension
{
    /** @var Request */
    private $request;

    /** @var Design */
    private $design;

    public function __construct(Request $request, Design $design)
    {
        $this->request = $request;
        $this->design = $design;
    }

    /**
     * Расширение метода buildFilter хелпера BackendOrdersHelper
     *
     * @param array $filter
     * @return array
     */
    public function extendBuildFilter(array $filter): array
    {
        $channel = $this->request->get('referer_channel');
        if (!empty($channel)) {
            $filter['referer_channel'] = $channel;
        }

        $this->design->assign('referer_channel', $channel);

        return $filter;
    }

    /**
     * Расширение метода buildCountStatusesFilter хелпера BackendOrdersHelper
     *
     * @param array $countStatusesFilter
     * @param array $filter
     * @return array
     */
    public function extendBuildCountStatusesFilter(array $countStatusesFilter, array $filter = []): array
    {
        if (!empty($filter['referer_channel'])) {
            $countStatusesFilter['referer_channel'] = $filter['referer_channel'];
        }

        return $countStatusesFilter;
    }
}
