<?php
declare(strict_types=1);

namespace Tonyapps\FreeGift\Plugin\SalesRule;

use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Utility;
use Tonyapps\FreeGift\Observer\SyncGifts;

/**
 * SyncGifts re-collects totals with the gifts visible, so they are billed at 0 and saved. Every cart price rule
 * condition check (discount, cart-fixed totals, shipping discount, free shipping) goes through
 * Utility::canProcessRule(), which validates the rule against the address: its item list, total_qty and weight.
 * For that check only, the gift lines are taken out of all three.
 */
class HideGiftsFromRuleConditions
{
    public function aroundCanProcessRule(Utility $subject, callable $proceed, Rule $rule, Address $address): bool
    {
        $items = $address->getAllItems();
        $gifts = array_filter($items, static fn ($item): bool => $item instanceof Item && SyncGifts::giftRuleId($item));
        if (!$gifts) {
            return $proceed($rule, $address);
        }

        $giftQty = 0.0;
        $giftWeight = 0.0;
        foreach ($gifts as $gift) {
            $giftQty += (float) $gift->getQty();
            if (!$gift->getIsVirtual()) {
                $giftWeight += (float) $gift->getWeight() * (float) $gift->getQty();
            }
        }
        $totalQty = $address->getTotalQty();
        $weight = $address->getWeight();
        $address->setData('cached_items_all', array_values(array_diff_key($items, $gifts)))
            ->setTotalQty(max(0.0, (float) $totalQty - $giftQty))
            ->setWeight(max(0.0, (float) $weight - $giftWeight));
        try {
            return $proceed($rule, $address);
        } finally {
            $address->setData('cached_items_all', $items)->setTotalQty($totalQty)->setWeight($weight);
        }
    }
}
