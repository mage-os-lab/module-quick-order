<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Test\Integration;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Tests the SKU / name suggestions of the quick order table.
 */
#[DbIsolation(true)]
class SuggestTest extends AbstractController
{
    /**
     * A product is suggested from a part of its SKU or of its name; LIKE wildcards are plain characters.
     */
    #[DataFixture(ProductFixture::class, ['sku' => 'quick-order-simple', 'name' => 'Suggested Backpack'])]
    public function testSuggestsBySkuOrName(): void
    {
        $expected = [['sku' => 'quick-order-simple', 'name' => 'Suggested Backpack']];

        $this->assertSame($expected, $this->suggest('order-simp'));
        $this->assertSame($expected, $this->suggest('gested back'));
        $this->assertSame([], $this->suggest('quick%simple'));
    }

    /**
     * Too short or malformed queries answer an empty list instead of failing.
     */
    public function testIgnoresShortAndMalformedQueries(): void
    {
        $this->assertSame([], $this->suggest('q'));
        $this->assertSame([], $this->suggest(['quick']));
    }

    /**
     * Call the suggest endpoint and return its decoded answer.
     *
     * @param mixed $query
     * @return mixed
     */
    private function suggest(mixed $query): mixed
    {
        $this->resetRequest();
        $this->resetResponse();
        $this->getRequest()->setParam('q', $query);
        $this->dispatch('quickorder/index/suggest');
        return json_decode((string) $this->getResponse()->getBody(), true);
    }
}
