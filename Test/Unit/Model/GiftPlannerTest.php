<?php
declare(strict_types=1);

namespace Tonyapps\FreeGift\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Tonyapps\FreeGift\Model\GiftPlanner;

class GiftPlannerTest extends TestCase
{
    private GiftPlanner $planner;

    protected function setUp(): void
    {
        $this->planner = new GiftPlanner();
    }

    public function testAddsMissingGifts(): void
    {
        $plan = $this->planner->plan([7 => ['MUG', 'PEN']], [5 => ['rule_id' => 7, 'sku' => 'MUG']]);

        $this->assertSame([['rule_id' => 7, 'sku' => 'PEN']], $plan['add']);
        $this->assertSame([], $plan['remove']);
    }

    public function testRemovesGiftsWhoseRuleNoLongerMatches(): void
    {
        $plan = $this->planner->plan([], [5 => ['rule_id' => 7, 'sku' => 'MUG'], 6 => ['rule_id' => 8, 'sku' => 'PEN']]);

        $this->assertSame([], $plan['add']);
        $this->assertSame([5, 6], $plan['remove']);
    }

    public function testRemovesGiftDroppedFromRuleSkus(): void
    {
        $plan = $this->planner->plan([7 => ['PEN']], [5 => ['rule_id' => 7, 'sku' => 'MUG']]);

        $this->assertSame([['rule_id' => 7, 'sku' => 'PEN']], $plan['add']);
        $this->assertSame([5], $plan['remove']);
    }

    public function testRemovesDuplicateGiftLines(): void
    {
        $plan = $this->planner->plan([7 => ['MUG']], [5 => ['rule_id' => 7, 'sku' => 'MUG'], 9 => ['rule_id' => 7, 'sku' => 'MUG']]);

        $this->assertSame([], $plan['add']);
        $this->assertSame([9], $plan['remove']);
    }

    public function testSameSkuFromTwoRulesIsTwoGifts(): void
    {
        $plan = $this->planner->plan([7 => ['MUG'], 8 => ['MUG']], []);

        $this->assertCount(2, $plan['add']);
    }

    public function testParsesSkus(): void
    {
        $this->assertSame(['MUG', 'PEN'], $this->planner->parseSkus(' MUG, ,PEN,MUG '));
        $this->assertSame([], $this->planner->parseSkus(null));
    }
}
