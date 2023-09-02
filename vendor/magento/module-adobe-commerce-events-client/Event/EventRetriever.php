<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Model\Event;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\Event\Collection;

/**
 * Class for retrieving stored event data.
 */
class EventRetriever
{
    /**
     * @var Collection
     */
    private Collection $collection;

    /**
     * @param Collection $collection
     */
    public function __construct(
        Collection $collection
    ) {
        $this->collection = $collection;
    }

    /**
     * Retrieves a list of the stored events waiting to be sent.
     *
     * @return array
     * @throws EventException
     */
    public function getWaitingEvents(): array
    {
        $this->collection->addFieldToFilter('status', (string)EventInterface::WAITING_STATUS);

        $events = [];
        /** @var Event $event */
        foreach ($this->collection->getItems() as $event) {
            $events[$event->getId()] = [
                "eventCode" => $event->getEventCode(),
                "eventData" => $event->getEventData(),
                "metadata" => $event->getMetadata()
            ];
        }

        return $events;
    }
}
