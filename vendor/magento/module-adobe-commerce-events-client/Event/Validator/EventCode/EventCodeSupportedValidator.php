<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Validator\EventCode;

use Magento\AdobeCommerceEventsClient\Event\Collector\AggregatedEventList;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriber;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 * Validates that event code exists in the list of supported events.
 */
class EventCodeSupportedValidator implements EventValidatorInterface
{
    /**
     * @var AggregatedEventList
     */
    private AggregatedEventList $aggregatedEventList;

    /**
     * @param AggregatedEventList $aggregatedEventList
     */
    public function __construct(AggregatedEventList $aggregatedEventList)
    {
        $this->aggregatedEventList = $aggregatedEventList;
    }

    /**
     * Validates that event code exists in the list of supported events.
     * Only applies for the plugin-type events.
     *
     * {@inheritDoc}
     */
    public function validate(Event $event, bool $force = false): void
    {
        $eventCode = $event->getParent() ?? $event->getName();
        $events = $this->aggregatedEventList->getList();

        $eventCode = str_replace(EventSubscriber::EVENT_PREFIX_COMMERCE, '', $eventCode);

        $eventCodeParts = explode('.', $eventCode, 2);

        if ($eventCodeParts[0] !== EventSubscriber::EVENT_TYPE_PLUGIN) {
            return;
        }

        if (!isset($events[$eventCode])) {
            throw new ValidatorException(__(
                'Event "%1" is not defined in the list of supported events',
                $eventCode
            ));
        }
    }
}
