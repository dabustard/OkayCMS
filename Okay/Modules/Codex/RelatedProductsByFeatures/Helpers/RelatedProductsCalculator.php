<?php

namespace Okay\Modules\Codex\RelatedProductsByFeatures\Helpers;

use Okay\Core\Database;
use Okay\Core\QueryFactory;
use Okay\Core\Settings;
use Okay\Modules\Codex\RelatedProductsByFeatures\Init\Init;

class RelatedProductsCalculator
{
    private $db;
    private $queryFactory;
    private $settings;

    public function __construct(Database $db, QueryFactory $queryFactory, Settings $settings)
    {
        $this->db = $db;
        $this->queryFactory = $queryFactory;
        $this->settings = $settings;
    }

    public function getTotalProducts(): int
    {
        $onlyVisible = (int)$this->settings->get(Init::SETTING_ONLY_VISIBLE_PRODUCTS);
        $query = $this->queryFactory->newSqlQuery();
        $query->setStatement('SELECT COUNT(*) AS count FROM __products p' . ($onlyVisible ? ' WHERE p.visible = 1' : ''));
        $this->db->query($query);
        return (int)$this->db->result('count');
    }

    public function resetRelatedProducts(): void
    {
        $query = $this->queryFactory->newSqlQuery();
        $query->setStatement('DELETE FROM __related_products');
        $this->db->query($query);
    }

    public function processBatch(int $offset, int $limit): array
    {
        @set_time_limit(0);
        ignore_user_abort(true);

        $limit = max(1, $limit);
        $products = $this->loadProducts($offset, $limit);
        if (empty($products)) {
            return ['processed' => 0, 'inserted' => 0];
        }

        $allProductIds = $this->loadAllProductIds();
        $candidateIds = $this->loadCandidateIds();
        $discontinuedIds = $this->loadDiscontinuedIds();
        $mainCategories = $this->loadMainCategories($allProductIds);
        $featureWeights = $this->loadFeatureWeights();
        $productValues = $this->loadProductValues($featureWeights);
        $valueProducts = $this->buildValueProducts($productValues);
        $categoryProducts = $this->buildCategoryProducts($mainCategories);

        $categoryWeight = max(0, (int)$this->settings->get(Init::SETTING_CATEGORY_WEIGHT));
        $defaultLimit = max(0, (int)$this->settings->get(Init::SETTING_DEFAULT_LIMIT));
        $discontinuedLimit = max(0, (int)$this->settings->get(Init::SETTING_DISCONTINUED_LIMIT));

        $inserted = 0;
        foreach ($products as $product) {
            $productId = (int)$product->id;
            $similarLimit = isset($discontinuedIds[$productId]) ? $discontinuedLimit : $defaultLimit;
            if ($similarLimit < 1) {
                continue;
            }

            $scores = [];
            if (isset($productValues[$productId])) {
                foreach ($productValues[$productId] as $valueId => $weight) {
                    if (empty($valueProducts[$valueId])) {
                        continue;
                    }
                    foreach ($valueProducts[$valueId] as $candidateId) {
                        if ($candidateId === $productId || !isset($candidateIds[$candidateId])) {
                            continue;
                        }
                        if (!isset($scores[$candidateId])) {
                            $scores[$candidateId] = 0;
                        }
                        $scores[$candidateId] += $weight;
                    }
                }
            }

            if ($categoryWeight > 0 && !empty($mainCategories[$productId])) {
                $categoryId = $mainCategories[$productId];
                if (!empty($categoryProducts[$categoryId])) {
                    foreach ($categoryProducts[$categoryId] as $candidateId) {
                        if ($candidateId === $productId || !isset($candidateIds[$candidateId])) {
                            continue;
                        }
                        if (!isset($scores[$candidateId])) {
                            $scores[$candidateId] = 0;
                        }
                        $scores[$candidateId] += $categoryWeight;
                    }
                }
            }

            $scores = array_filter($scores, static function ($score) {
                return $score > 0;
            });

            if (empty($scores)) {
                continue;
            }

            uksort($scores, static function ($leftId, $rightId) use ($scores) {
                if ($scores[$leftId] === $scores[$rightId]) {
                    return $leftId <=> $rightId;
                }
                return $scores[$rightId] <=> $scores[$leftId];
            });
            $relatedIds = array_slice(array_keys($scores), 0, $similarLimit);
            $inserted += $this->insertRelatedProducts($productId, $relatedIds);
        }

        return ['processed' => count($products), 'inserted' => $inserted];
    }

    private function loadProducts(int $offset, int $limit): array
    {
        $onlyVisible = (int)$this->settings->get(Init::SETTING_ONLY_VISIBLE_PRODUCTS);
        $query = $this->queryFactory->newSqlQuery();
        $query->setStatement('SELECT p.id FROM __products p' . ($onlyVisible ? ' WHERE p.visible = 1' : '') . ' ORDER BY p.id LIMIT ' . (int)$offset . ', ' . (int)$limit);
        $this->db->query($query);
        return $this->db->results();
    }

    private function loadAllProductIds(): array
    {
        $onlyVisible = (int)$this->settings->get(Init::SETTING_ONLY_VISIBLE_PRODUCTS);
        $query = $this->queryFactory->newSqlQuery();
        $query->setStatement('SELECT p.id FROM __products p' . ($onlyVisible ? ' WHERE p.visible = 1' : ''));
        $this->db->query($query);
        return array_map('intval', $this->db->results('id'));
    }

    private function loadCandidateIds(): array
    {
        $ids = array_fill_keys($this->loadAllProductIds(), true);
        if ((int)$this->settings->get(Init::SETTING_INCLUDE_DISCONTINUED_RELATED)) {
            return $ids;
        }

        foreach (array_keys($this->loadDiscontinuedIds()) as $productId) {
            unset($ids[$productId]);
        }
        return $ids;
    }

    private function loadDiscontinuedIds(): array
    {
        $query = $this->queryFactory->newSqlQuery();
        $query->setStatement('SELECT DISTINCT product_id FROM __variants WHERE stock < 0');
        $this->db->query($query);
        return array_fill_keys(array_map('intval', $this->db->results('product_id')), true);
    }

    private function loadMainCategories(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }
        $query = $this->queryFactory->newSqlQuery();
        $query->setStatement('SELECT id, main_category_id FROM __products WHERE id IN (:product_ids) AND main_category_id > 0');
        $query->bindValue('product_ids', $productIds);
        $this->db->query($query);
        $categories = [];
        foreach ($this->db->results() as $row) {
            $categories[(int)$row->id] = (int)$row->main_category_id;
        }
        return $categories;
    }

    private function loadFeatureWeights(): array
    {
        $query = $this->queryFactory->newSqlQuery();
        $query->setStatement('SELECT id, weight FROM __features WHERE weight > 0');
        $this->db->query($query);
        $weights = [];
        foreach ($this->db->results() as $row) {
            $weights[(int)$row->id] = (int)$row->weight;
        }
        return $weights;
    }

    private function loadProductValues(array $featureWeights): array
    {
        if (empty($featureWeights)) {
            return [];
        }
        $query = $this->queryFactory->newSqlQuery();
        $query->setStatement('SELECT pfv.product_id, pfv.value_id, fv.feature_id FROM __products_features_values pfv INNER JOIN __features_values fv ON fv.id = pfv.value_id WHERE fv.feature_id IN (:feature_ids)');
        $query->bindValue('feature_ids', array_keys($featureWeights));
        $this->db->query($query);
        $values = [];
        foreach ($this->db->results() as $row) {
            $productId = (int)$row->product_id;
            $valueId = (int)$row->value_id;
            $featureId = (int)$row->feature_id;
            $values[$productId][$valueId] = $featureWeights[$featureId];
        }
        return $values;
    }

    private function buildValueProducts(array $productValues): array
    {
        $valueProducts = [];
        foreach ($productValues as $productId => $values) {
            foreach (array_keys($values) as $valueId) {
                $valueProducts[$valueId][] = (int)$productId;
            }
        }
        return $valueProducts;
    }

    private function buildCategoryProducts(array $mainCategories): array
    {
        $categoryProducts = [];
        foreach ($mainCategories as $productId => $categoryId) {
            $categoryProducts[$categoryId][] = (int)$productId;
        }
        return $categoryProducts;
    }

    private function insertRelatedProducts(int $productId, array $relatedIds): int
    {
        if (empty($relatedIds)) {
            return 0;
        }
        $values = [];
        $bindValues = ['product_id' => $productId];
        foreach ($relatedIds as $position => $relatedId) {
            $key = 'related_id_' . $position;
            $posKey = 'position_' . $position;
            $values[] = '(:product_id, :' . $key . ', :' . $posKey . ')';
            $bindValues[$key] = (int)$relatedId;
            $bindValues[$posKey] = $position + 1;
        }
        $query = $this->queryFactory->newSqlQuery();
        $query->setStatement('INSERT INTO __related_products (product_id, related_id, position) VALUES ' . implode(', ', $values));
        $query->bindValues($bindValues);
        $this->db->query($query);
        return count($relatedIds);
    }
}
