<?php

namespace Okay\Modules\Codex\OrdersRefererFilter\ExtendsEntities;

use Okay\Core\Modules\AbstractModuleEntityFilter;

class OrdersEntityFilter extends AbstractModuleEntityFilter
{
    /**
     * Фильтр по каналу реферала для OrdersEntity
     *
     * @param string $channel
     * @param array $filter
     */
    public function filterRefererChannel($channel, $filter)
    {
        if ($channel === 'none') {
            $this->select->where('(o.referer_channel IS NULL OR o.referer_channel = "")');
        } else {
            $this->select->where('o.referer_channel = :filter_referer_channel')
                ->bindValue('filter_referer_channel', $channel);
        }
    }
}
