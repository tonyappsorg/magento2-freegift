<?php
declare(strict_types=1);

namespace Tonyapps\FreeGift\Model;

/**
 * Pure diff between the gifts the cart should have and the gifts it has. No Magento, unit-tested.
 */
class GiftPlanner
{
    /**
     * @param array<int, string[]> $wanted rule id => gift SKUs
     * @param array<int|string, array{rule_id: int, sku: string}> $current item key => gift it carries
     * @return array{add: array<int, array{rule_id: int, sku: string}>, remove: array<int, int|string>}
     */
    public function plan(array $wanted, array $current): array
    {
        $want = [];
        foreach ($wanted as $ruleId => $skus) {
            foreach ($skus as $sku) {
                $want[$ruleId . '|' . $sku] = ['rule_id' => (int) $ruleId, 'sku' => $sku];
            }
        }

        $remove = [];
        $have = [];
        foreach ($current as $key => $gift) {
            $id = $gift['rule_id'] . '|' . $gift['sku'];
            if (!isset($want[$id]) || isset($have[$id])) {
                $remove[] = $key;
                continue;
            }
            $have[$id] = true;
        }

        return ['add' => array_values(array_diff_key($want, $have)), 'remove' => $remove];
    }

    /** @return string[] */
    public function parseSkus(?string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $value)), 'strlen')));
    }
}
