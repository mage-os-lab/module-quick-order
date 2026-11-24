<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Test\Unit;

use MageOS\QuickOrder\Model\Access;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests who may use the quick order.
 */
class AccessTest extends TestCase
{
    /**
     * The quick order is open when enabled and the visitor's group is selected; only refused guests are sent to login.
     *
     * @param bool $enabled
     * @param string $groups
     * @param int $group
     * @param bool $allowed
     * @param bool $needsLogin
     */
    #[DataProvider('visitors')]
    public function testAccess(bool $enabled, string $groups, int $group, bool $allowed, bool $needsLogin): void
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn($enabled);
        $config->method('getValue')->willReturn($groups);
        $session = $this->createStub(Session::class);
        $session->method('getCustomerGroupId')->willReturn($group);
        $session->method('isLoggedIn')->willReturn($group !== 0);
        $access = new Access($config, $session);

        $this->assertSame($allowed, $access->isAllowed());
        $this->assertSame($needsLogin, $access->needsLogin());
        if (!$allowed) {
            $this->expectException(NotFoundException::class);
        }
        $access->check();
    }

    /**
     * @return array<string, array{bool, string, int, bool, bool}>
     */
    public static function visitors(): array
    {
        return [
            'all groups, guest' => [true, '32000', 0, true, false],
            'all groups among others' => [true, '2,32000', 1, true, false],
            'group selected' => [true, '1,2', 2, true, false],
            'group not selected' => [true, '2', 1, false, false],
            'group 1 is not group 11' => [true, '11', 1, false, false],
            'guest refused' => [true, '1,2', 0, false, true],
            'guest selected' => [true, '0,2', 0, true, false],
            'no group selected' => [true, '', 0, false, true],
            'disabled' => [false, '32000', 1, false, false],
            'disabled, guest' => [false, '32000', 0, false, false],
        ];
    }
}
