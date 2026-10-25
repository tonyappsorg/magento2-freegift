<?php
declare(strict_types=1);

namespace Tonyapps\FreeGift\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Psr\Log\LoggerInterface;
use Tonyapps\FreeGift\Model\GiftPlanner;
use Tonyapps\FreeGift\Model\Rule\Action\Discount\FreeGift;

/**
 * After every totals run: add gifts for "Add free gift" rules that matched, drop gifts whose rule no longer does,
 * then re-collect once so the totals include the change.
 *
 * Matched rules are read from the items, not the quote: core only resets quote.applied_rule_ids on the first
 * totals run of a request, while item applied_rule_ids are reset on every run.
 */
class SyncGifts implements ObserverInterface
{
    public const OPTION_CODE = 'tonyapps_free_gift';

    private const GIFT_TYPES = [Type::TYPE_SIMPLE, Type::TYPE_VIRTUAL];

    private bool $running = false;

    public function __construct(
        private readonly RuleCollectionFactory $ruleCollectionFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly GiftPlanner $planner,
        private readonly Json $json,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        if ($this->running || !$quote) {
            return;
        }

        $this->running = true;
        try {
            if ($this->sync($quote)) {
                // Addresses cache their item list and core never clears it, so the re-collect would miss the change.
                foreach ($quote->getAllAddresses() as $address) {
                    $address->unsetData('cached_items_all');
                }
                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals();
            }
        } finally {
            $this->running = false;
        }
    }

    /** @return bool whether the cart changed */
    private function sync(Quote $quote): bool
    {
        $gifts = [];
        $current = [];
        $ruleIds = [];
        foreach ($quote->getAllVisibleItems() as $key => $item) {
            $option = $item->getOptionByCode(self::OPTION_CODE);
            if ($option) {
                $gifts[$key] = $item;
                $current[$key] = ['rule_id' => (int) $option->getValue(), 'sku' => (string) $item->getProduct()->getData('sku')];
                continue;
            }
            foreach (explode(',', (string) $item->getAppliedRuleIds()) as $ruleId) {
                if ((int) $ruleId) {
                    $ruleIds[(int) $ruleId] = (int) $ruleId;
                }
            }
        }
        if (!$ruleIds && !$current) {
            return false;
        }

        $plan = $this->planner->plan($this->wantedGifts($ruleIds), $current);
        $changed = false;

        foreach ($plan['remove'] as $key) {
            $quote->deleteItem($gifts[$key]);
            unset($gifts[$key]);
            $changed = true;
        }
        foreach ($plan['add'] as $gift) {
            $item = $this->addGift($quote, $gift['rule_id'], $gift['sku']);
            if ($item) {
                $gifts[] = $item;
                $changed = true;
            }
        }
        // Shoppers can't buy more free gifts by editing qty in the cart.
        foreach ($gifts as $item) {
            if ((float) $item->getQty() !== 1.0 || (float) $item->getCustomPrice() !== 0.0) {
                $this->makeFree($item->setQty(1));
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * @param int[] $ruleIds
     * @return array<int, string[]>
     */
    private function wantedGifts(array $ruleIds): array
    {
        if (!$ruleIds) {
            return [];
        }
        $rules = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('rule_id', ['in' => array_values($ruleIds)])
            ->addFieldToFilter('simple_action', FreeGift::ACTION);

        $wanted = [];
        foreach ($rules as $rule) {
            $wanted[(int) $rule->getId()] = $this->planner->parseSkus($rule->getData('tonyapps_gift_skus'));
        }
        return $wanted;
    }

    private function addGift(Quote $quote, int $ruleId, string $sku): ?Item
    {
        try {
            // Fresh instance: custom options would otherwise stick to the repository's cached product.
            $product = $this->productRepository->get($sku, false, (int) $quote->getStoreId(), true);
        } catch (\Exception $e) {
            $this->logger->warning('Free gift SKU "' . $sku . '" from rule ' . $ruleId . ' not found.');
            return null;
        }
        if (!in_array($product->getTypeId(), self::GIFT_TYPES, true)) {
            $this->logger->warning('Free gift SKU "' . $sku . '" from rule ' . $ruleId . ' is not a simple or virtual product.');
            return null;
        }

        $product->addCustomOption(self::OPTION_CODE, (string) $ruleId);
        $product->addCustomOption('additional_options', $this->json->serialize([
            ['label' => (string) __('Promotion'), 'value' => (string) __('Free gift')],
        ]));

        try {
            $item = $quote->addProduct($product, new DataObject(['qty' => 1]));
        } catch (\Exception $e) {
            $item = $e->getMessage();
        }
        if (is_string($item)) {
            // Usually out of stock. The cart carries on without the gift.
            $this->logger->info('Free gift SKU "' . $sku . '" not added: ' . $item);
            return null;
        }

        $this->makeFree($item);
        return $item;
    }

    private function makeFree(Item $item): void
    {
        $item->setCustomPrice(0)->setOriginalCustomPrice(0);
        $item->getProduct()->setIsSuperMode(true);
    }
}
