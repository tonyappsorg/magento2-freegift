<?php
declare(strict_types=1);

namespace Tonyapps\FreeGift\Plugin;

use Magento\SalesRule\Model\Rule\Action\SimpleActionOptionsProvider;
use Tonyapps\FreeGift\Model\Rule\Action\Discount\FreeGift;

/**
 * Adds "Add free gift" to the Apply dropdown on the cart price rule form.
 */
class AddFreeGiftAction
{
    public function afterToOptionArray(SimpleActionOptionsProvider $subject, array $result): array
    {
        $result[] = ['label' => __('Add free gift'), 'value' => FreeGift::ACTION];
        return $result;
    }
}
