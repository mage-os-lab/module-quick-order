<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Model;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Store\Model\ScopeInterface;

/**
 * Says whether the visitor may use the quick order: the module is enabled and their customer group is allowed.
 */
class Access
{
    private const XML_ENABLED = 'mageos_quickorder/general/enabled';
    private const XML_GROUPS = 'mageos_quickorder/general/customer_groups';

    /**
     * @param ScopeConfigInterface $config
     * @param Session $customerSession
     */
    public function __construct(
        private readonly ScopeConfigInterface $config,
        private readonly Session $customerSession
    ) {
    }

    /**
     * Whether the quick order is enabled in this store and open to the visitor's customer group.
     */
    public function isAllowed(): bool
    {
        if (!$this->config->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return false;
        }
        $groups = explode(',', (string) $this->config->getValue(self::XML_GROUPS, ScopeInterface::SCOPE_STORE));
        return in_array((string) GroupInterface::CUST_GROUP_ALL, $groups, true)
            || in_array((string) $this->customerSession->getCustomerGroupId(), $groups, true);
    }

    /**
     * Whether a refused visitor is a guest of an enabled quick order: logging in may let them in.
     */
    public function needsLogin(): bool
    {
        return !$this->isAllowed()
            && $this->config->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE)
            && !$this->customerSession->isLoggedIn();
    }

    /**
     * Answer a 404 page to a visitor who may not use the quick order.
     *
     * @throws NotFoundException
     */
    public function check(): void
    {
        if (!$this->isAllowed()) {
            throw new NotFoundException(__('Page not found.'));
        }
    }
}
