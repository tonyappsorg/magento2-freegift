<?php
declare(strict_types=1);

namespace Tonyapps\FreeGift\Test\Unit\Plugin;

use Magento\SalesRule\Model\Rule\Action\SimpleActionOptionsProvider;
use PHPUnit\Framework\TestCase;
use Tonyapps\FreeGift\Model\Rule\Action\Discount\FreeGift;
use Tonyapps\FreeGift\Plugin\AddFreeGiftAction;

class AddFreeGiftActionTest extends TestCase
{
    public function testAddsFreeGiftActionAndPreservesExistingOptions(): void
    {
        $existing = [['label' => __('Percent discount'), 'value' => 'by_percent']];

        $result = (new AddFreeGiftAction())->afterToOptionArray(
            $this->createMock(SimpleActionOptionsProvider::class),
            $existing
        );

        self::assertCount(2, $result);
        self::assertSame($existing[0], $result[0]);
        self::assertSame(FreeGift::ACTION, $result[1]['value']);
        self::assertSame('Add free gift', (string)$result[1]['label']);
    }
}
