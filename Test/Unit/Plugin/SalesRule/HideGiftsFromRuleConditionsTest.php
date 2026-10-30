<?php
declare(strict_types=1);

namespace Tonyapps\FreeGift\Test\Unit\Plugin\SalesRule;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Utility;
use PHPUnit\Framework\TestCase;
use Tonyapps\FreeGift\Observer\SyncGifts;
use Tonyapps\FreeGift\Plugin\SalesRule\HideGiftsFromRuleConditions;

class HideGiftsFromRuleConditionsTest extends TestCase
{
    public function testRuleSeesAddressWithoutGiftLinesAndAddressIsRestored(): void
    {
        $paid = $this->item(0, 2, 1.5);
        $gift = $this->item(7, 1, 4.0);
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getAllItems', 'isAddressMultilineAttribute'])->getMock();
        $address->method('getAllItems')->willReturnCallback(fn () => $address->getData('cached_items_all') ?? [$paid, $gift]);
        $address->setTotalQty(3)->setWeight(7.0);

        $seen = null;
        $result = (new HideGiftsFromRuleConditions())->aroundCanProcessRule(
            $this->createMock(Utility::class),
            function (Rule $rule, Address $address) use (&$seen): bool {
                $seen = [$address->getAllItems(), $address->getTotalQty(), $address->getWeight()];
                return true;
            },
            $this->createMock(Rule::class),
            $address
        );

        self::assertTrue($result);
        self::assertSame([[$paid], 2.0, 3.0], $seen);
        self::assertSame([$paid, $gift], $address->getAllItems());
        self::assertSame(3, $address->getTotalQty());
        self::assertSame(7.0, $address->getWeight());
    }

    public function testCartWithoutGiftsIsValidatedUnchanged(): void
    {
        $address = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->onlyMethods(['getAllItems', 'setData'])->getMock();
        $address->method('getAllItems')->willReturn([$this->item(0, 3, 1.0)]);
        $address->expects(self::never())->method('setData');

        $result = (new HideGiftsFromRuleConditions())->aroundCanProcessRule(
            $this->createMock(Utility::class),
            static fn (): bool => false,
            $this->createMock(Rule::class),
            $address
        );

        self::assertFalse($result);
    }

    private function item(int $giftRuleId, float $qty, float $weight): Item
    {
        $item = $this->getMockBuilder(Item::class)->disableOriginalConstructor()
            ->onlyMethods(['getOptionByCode', 'getBuyRequest'])->getMock();
        $item->method('getOptionByCode')->willReturn(null);
        $item->method('getBuyRequest')->willReturn(new DataObject($giftRuleId ? [SyncGifts::OPTION_CODE => $giftRuleId] : []));
        $item->setData(['qty' => $qty, 'weight' => $weight]);
        return $item;
    }
}
