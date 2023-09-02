<?php

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\PackageInfo;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Generates metadata to send in the message to the event pipeline
 */
class EventMetadataCollector
{
    /**
     * @var ProductMetadataInterface
     */
    private ProductMetadataInterface $commerceMetadata;

    /**
     * @var PackageInfo
     */
    private PackageInfo $packageInfo;

    /**
     * @var array
     */
    private array $metadata = [];

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param ProductMetadataInterface $commerceMetadata
     * @param PackageInfo $packageInfo
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductMetadataInterface $commerceMetadata,
        PackageInfo              $packageInfo,
        StoreManagerInterface    $storeManager
    ) {
        $this->commerceMetadata = $commerceMetadata;
        $this->packageInfo = $packageInfo;
        $this->storeManager = $storeManager;
    }

    /**
     * Loads and returns metadata to send in the message to the event pipeline
     *
     * @return string[]
     */
    public function getMetadata(): array
    {
        $this->loadMetadata();

        return $this->metadata;
    }

    /**
     * Loads and cache metadata into local variable
     *
     * @return void
     */
    private function loadMetadata(): void
    {
        if (empty($this->metadata)) {
            $metadata = [
                'commerceEdition' => $this->getCommerceEdition(),
                'commerceVersion' => $this->commerceMetadata->getVersion(),
                'eventsClientVersion' => $this->packageInfo->getVersion('Magento_AdobeCommerceEventsClient'),
            ];

            try {
                $store = $this->storeManager->getStore();
                $metadata['storeId'] = $store->getId();
                $metadata['websiteId'] = $store->getWebsiteId();
                $metadata['storeGroupId'] = $store->getStoreGroupId();
            } catch (NoSuchEntityException $exception) {
                $metadata['storeId'] = $metadata['websiteId'] = $metadata['storeGroupId'] = '';
            }

            $this->metadata = $metadata;
        }
    }

    /**
     * Returns commerce edition name
     *
     * @return string
     */
    private function getCommerceEdition(): string
    {
        $commerceEdition = '';

        switch ($this->commerceMetadata->getEdition()) {
            case 'Community':
                $commerceEdition = 'Open Source';
                break;
            case 'Enterprise':
                $commerceEdition = 'Adobe Commerce';
                break;
            case 'B2B':
                $commerceEdition = 'Adobe Commerce + B2B';
                break;
        }

        return $commerceEdition;
    }
}
