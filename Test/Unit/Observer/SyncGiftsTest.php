<?php
declare(strict_types=1);

namespace Tonyapps\FreeGift\Test\Unit\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tonyapps\FreeGift\Model\GiftPlanner;
use Tonyapps\FreeGift\Observer\SyncGifts;

class SyncGiftsTest extends TestCase
{
    public function testHidingGiftsDropsAddressItemListCachedEarlierInTheRequest(): void
    {
        $gift = $this->gift();
        $address = new DataObject(['cached_items_all' => [$gift]]);
        $quote = $this->quote();
        $quote->method('getAllVisibleItems')->willReturn([$gift]);
        $quote->method('getAllAddresses')->willReturn([$address]);

        $this->observer()->execute($this->event('sales_quote_collect_totals_before', $quote));

        self::assertTrue($gift->isDeleted());
        self::assertFalse($address->hasData('cached_items_all'));
    }

    public function testGiftThatFailedTheStockCheckIsRemovedAndStopsBlockingCheckout(): void
    {
        $gift = $this->gift();
        $gift->setData('has_error', true);
        $quote = $this->quote();
        $quote->setData('has_error', true);
        $quote->method('getItemsCollection')->willReturn([]);
        $quote->method('getAllVisibleItems')->willReturn([$gift]);
        $quote->method('getAllItems')->willReturn([]);
        $quote->method('getAllAddresses')->willReturn([]);
        $quote->expects(self::once())->method('deleteItem')->with($gift);
        $quote->expects(self::once())->method('removeErrorInfosByParams')->with(null, ['origin' => 'cataloginventory']);
        $quote->expects(self::once())->method('collectTotals');

        $this->observer()->execute($this->event('sales_quote_collect_totals_after', $quote));
    }

    private function observer(): SyncGifts
    {
        return new SyncGifts(
            $this->createMock(CollectionFactory::class),
            $this->createMock(ProductRepositoryInterface::class),
            new GiftPlanner(),
            $this->createMock(Json::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    private function event(string $name, Quote $quote): Observer
    {
        return new Observer(['event' => new Event(['name' => $name, 'quote' => $quote])]);
    }

    private function quote(): Quote&MockObject
    {
        return $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->onlyMethods([
            'getAllVisibleItems', 'getAllAddresses', 'getAllItems', 'getItemsCollection',
            'deleteItem', 'removeErrorInfosByParams', 'collectTotals',
        ])->getMock();
    }

    private function gift(): Item
    {
        $item = $this->getMockBuilder(Item::class)->disableOriginalConstructor()
            ->onlyMethods(['getOptionByCode', 'getBuyRequest'])->getMock();
        $item->method('getOptionByCode')->willReturn(null);
        $item->method('getBuyRequest')->willReturn(new DataObject([SyncGifts::OPTION_CODE => 7]));
        return $item;
    }
}
