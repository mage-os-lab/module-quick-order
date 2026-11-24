<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Test\Integration;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\TestCase\AbstractController;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the SKU / name suggestions of the quick order table.
 *
 * No DB isolation: the category index the suggestions read is only filled when the product save is committed.
 */
#[DbIsolation(false)]
class SuggestTest extends AbstractController
{
    /**
     * A product is suggested from a part of its SKU or of its name; other queries answer an empty list.
     *
     * @param mixed $query
     * @param bool $found
     */
    #[DataFixture(ProductFixture::class, ['sku' => 'quick-order-simple', 'name' => 'Suggested Backpack'])]
    #[DataProvider('queries')]
    public function testSuggestsBySkuOrName(mixed $query, bool $found): void
    {
        $this->getRequest()->setParam('q', $query);
        $this->dispatch('quickorder/index/suggest');

        $this->assertSame(
            $found ? [['sku' => 'quick-order-simple', 'name' => 'Suggested Backpack']] : [],
            json_decode((string) $this->getResponse()->getBody(), true)
        );
    }

    /**
     * SKUs that start with the query come before those that only contain it.
     */
    #[DataFixture(ProductFixture::class, ['sku' => '0-has-qoprefix-inside', 'name' => 'Inside'])]
    #[DataFixture(ProductFixture::class, ['sku' => 'qoprefix-first', 'name' => 'First'])]
    public function testSkuPrefixComesFirst(): void
    {
        $this->getRequest()->setParam('q', 'qoprefix');
        $this->dispatch('quickorder/index/suggest');

        $this->assertSame(
            ['qoprefix-first', '0-has-qoprefix-inside'],
            array_column(json_decode((string) $this->getResponse()->getBody(), true), 'sku')
        );
    }

    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function queries(): array
    {
        return [
            'part of the SKU' => ['order-simp', true],
            'part of the name' => ['gested back', true],
            'LIKE wildcard is a plain character' => ['quick%simple', false],
            'too short' => ['q', false],
            'malformed' => [['quick'], false],
        ];
    }
}
