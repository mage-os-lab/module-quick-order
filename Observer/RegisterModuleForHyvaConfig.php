<?php
declare(strict_types=1);

namespace MageOS\QuickOrder\Observer;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * Adds this module to app/etc/hyva-themes.json, so Hyvä's Tailwind build scans its templates.
 */
class RegisterModuleForHyvaConfig implements ObserverInterface
{
    /**
     * @param ComponentRegistrar $componentRegistrar
     * @param DirectoryList $directoryList
     */
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar,
        private readonly DirectoryList $directoryList
    ) {
    }

    /**
     * Append the module path, relative to the Magento root, to the "extensions" list.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $config = $observer->getData('config');
        $extensions = $config->hasData('extensions') ? $config->getData('extensions') : [];
        $path = (string) $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'MageOS_QuickOrder');
        $extensions[] = ['src' => substr($path, strlen($this->directoryList->getRoot()) + 1)];
        $config->setData('extensions', $extensions);
    }
}
