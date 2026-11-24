<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Test\Integration;

use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Tests the "Enabled" and "Allowed Customer Groups" settings.
 */
class AccessTest extends AbstractController
{
    /**
     * Every entry point of a disabled quick order answers a 404 page.
     */
    #[Config('mageos_quickorder/general/enabled', '0', 'store', 'default')]
    public function testDisabledQuickOrderIsNotFound(): void
    {
        // full path: the test framework redirects a GET whose URI has no slash to the base URL, before routing
        $this->dispatch('quickorder/index/index');
        $this->assert404NotFound();
    }

    /**
     * The suggestions of a disabled quick order answer a 404 page too.
     */
    #[Config('mageos_quickorder/general/enabled', '0', 'store', 'default')]
    public function testDisabledSuggestionsAreNotFound(): void
    {
        $this->getRequest()->setParam('q', 'simple');
        $this->dispatch('quickorder/index/suggest');
        $this->assert404NotFound();
    }

    /**
     * A guest whose group is not allowed is sent to the login page.
     */
    #[Config('mageos_quickorder/general/customer_groups', '1', 'store', 'default')]
    public function testRefusedGuestIsSentToLogin(): void
    {
        // full path: the test framework redirects a GET whose URI has no slash to the base URL, before routing
        $this->dispatch('quickorder/index/index');
        $this->assertRedirect($this->stringContains('customer/account/login'));
    }
}
