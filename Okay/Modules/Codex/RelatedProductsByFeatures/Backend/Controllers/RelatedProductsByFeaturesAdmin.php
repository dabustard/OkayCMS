<?php

namespace Okay\Modules\Codex\RelatedProductsByFeatures\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Modules\Codex\RelatedProductsByFeatures\Helpers\RelatedProductsCalculator;
use Okay\Modules\Codex\RelatedProductsByFeatures\Init\Init;

class RelatedProductsByFeaturesAdmin extends IndexAdmin
{
    public function fetch(RelatedProductsCalculator $calculator)
    {
        $this->initDefaultSettings();

        if ($this->request->method('post')) {
            $this->saveSettings();
            $this->design->assign('message_success', 'saved');
        }

        $this->assignSettings();
        $this->design->assign('products_total', $calculator->getTotalProducts());
        $this->response->setContent($this->design->fetch('related_products_by_features.tpl'));
    }

    public function startRecalculate(RelatedProductsCalculator $calculator)
    {
        $this->initDefaultSettings();
        $calculator->resetRelatedProducts();

        $result = [
            'success' => true,
            'total' => $calculator->getTotalProducts(),
            'offset' => 0,
            'inserted' => 0,
        ];
        $this->response->setContent(json_encode($result), RESPONSE_JSON);
    }

    public function processRecalculate(RelatedProductsCalculator $calculator)
    {
        $this->initDefaultSettings();
        $offset = max(0, $this->request->get('offset', 'integer'));
        $inserted = max(0, $this->request->get('inserted', 'integer'));
        $batchSize = max(1, (int)$this->settings->get(Init::SETTING_BATCH_SIZE));
        $total = $calculator->getTotalProducts();
        $batchResult = $calculator->processBatch($offset, $batchSize);
        $offset += $batchResult['processed'];
        $inserted += $batchResult['inserted'];

        $result = [
            'success' => true,
            'end' => $offset >= $total || $batchResult['processed'] === 0,
            'total' => $total,
            'offset' => $offset,
            'inserted' => $inserted,
        ];
        $this->response->setContent(json_encode($result), RESPONSE_JSON);
    }

    private function saveSettings(): void
    {
        $this->settings->set(Init::SETTING_CATEGORY_WEIGHT, max(0, $this->request->post('category_weight', 'integer')));
        $this->settings->set(Init::SETTING_DEFAULT_LIMIT, max(0, $this->request->post('default_limit', 'integer')));
        $this->settings->set(Init::SETTING_DISCONTINUED_LIMIT, max(0, $this->request->post('discontinued_limit', 'integer')));
        $this->settings->set(Init::SETTING_INCLUDE_DISCONTINUED_RELATED, (int)$this->request->post('include_discontinued_related', 'integer'));
        $this->settings->set(Init::SETTING_ONLY_VISIBLE_PRODUCTS, (int)$this->request->post('only_visible_products', 'integer'));
        $this->settings->set(Init::SETTING_BATCH_SIZE, max(1, $this->request->post('batch_size', 'integer')));
    }

    private function assignSettings(): void
    {
        $this->design->assign('category_weight', (int)$this->settings->get(Init::SETTING_CATEGORY_WEIGHT));
        $this->design->assign('default_limit', (int)$this->settings->get(Init::SETTING_DEFAULT_LIMIT));
        $this->design->assign('discontinued_limit', (int)$this->settings->get(Init::SETTING_DISCONTINUED_LIMIT));
        $this->design->assign('include_discontinued_related', (int)$this->settings->get(Init::SETTING_INCLUDE_DISCONTINUED_RELATED));
        $this->design->assign('only_visible_products', (int)$this->settings->get(Init::SETTING_ONLY_VISIBLE_PRODUCTS));
        $this->design->assign('batch_size', (int)$this->settings->get(Init::SETTING_BATCH_SIZE));
    }

    private function initDefaultSettings(): void
    {
        $defaults = [
            Init::SETTING_CATEGORY_WEIGHT => 100,
            Init::SETTING_DEFAULT_LIMIT => 10,
            Init::SETTING_DISCONTINUED_LIMIT => 20,
            Init::SETTING_INCLUDE_DISCONTINUED_RELATED => 0,
            Init::SETTING_ONLY_VISIBLE_PRODUCTS => 1,
            Init::SETTING_BATCH_SIZE => 50,
        ];
        foreach ($defaults as $name => $value) {
            if ($this->settings->get($name) === null) {
                $this->settings->set($name, $value);
            }
        }
    }
}
