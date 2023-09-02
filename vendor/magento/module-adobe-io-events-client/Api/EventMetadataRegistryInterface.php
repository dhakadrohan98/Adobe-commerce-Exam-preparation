<?php

namespace Magento\AdobeIoEventsClient\Api;

interface EventMetadataRegistryInterface
{
    /**
     * @return EventMetadataInterface[]
     */
    public function getDeclaredEventMetadataList(): array;

    /**
     * @return EventProviderInterface
     */
    public function getDeclaredEventProvider(): EventProviderInterface;
}
