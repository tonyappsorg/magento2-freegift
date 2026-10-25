<?php
declare(strict_types=1);

namespace Tonyapps\FreeGift\Model\Rule\Action\Discount;

use Magento\SalesRule\Model\Rule\Action\Discount\DataFactory;
use Magento\SalesRule\Model\Rule\Action\Discount\DiscountInterface;

/**
 * Discounts nothing. Its only job is to let core mark the rule as applied on matching items;
 * Observer\SyncGifts reads those marks and puts the gifts in the cart.
 */
class FreeGift implements DiscountInterface
{
    public const ACTION = 'tonyapps_free_gift';

    public function __construct(private readonly DataFactory $discountFactory)
    {
    }

    public function calculate($rule, $item, $qty)
    {
        return $this->discountFactory->create();
    }

    public function fixQuantity($qty, $rule)
    {
        return $qty;
    }
}
