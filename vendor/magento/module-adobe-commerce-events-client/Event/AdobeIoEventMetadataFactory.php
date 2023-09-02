<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeIoEventsClient\Model\Data\EventMetadataFactory;
use Magento\AdobeIoEventsClient\Model\Data\EventMetadata;

/**
 * Generates metadata for given event code
 */
class AdobeIoEventMetadataFactory
{
    /**
     * @var EventMetadataFactory
     */
    private EventMetadataFactory $eventMetadataFactory;

    /**
     * @param EventMetadataFactory $eventMetadataFactory
     */
    public function __construct(EventMetadataFactory $eventMetadataFactory)
    {
        $this->eventMetadataFactory = $eventMetadataFactory;
    }

    /**
     * Generates metadata info base on event type.
     *
     * @param string $eventCode
     * @return EventMetadata
     */
    public function generate(string $eventCode): EventMetadata
    {
        $data = [
            'event_code' => $eventCode,
            'description' => 'event ' . $eventCode,
            'label' => 'event' . $eventCode
        ];

        $eventCodeParts = explode('.', str_replace(EventSubscriber::EVENT_PREFIX_COMMERCE, '', $eventCode), 2);

        if ($eventCodeParts[0] === EventSubscriber::EVENT_TYPE_PLUGIN) {
            $data['description'] = 'Plugin event ' . $eventCodeParts[1];
            $data['label'] = 'Plugin event ' . $eventCodeParts[1];
        } else if ($eventCodeParts[0] === EventSubscriber::EVENT_TYPE_OBSERVER) {
            $data['label'] = 'Observer event ' . $eventCodeParts[1];
            $data['description'] = 'Observer event ' . $eventCodeParts[1];
        }

        return $this->eventMetadataFactory->create(['data' => $data]);
    }
}
