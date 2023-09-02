<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Filter;

use Magento\AdobeCommerceEventsClient\Event\DataFilterInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;

/**
 * Filters event payload according to the list of configured fields
 */
class EventFieldsFilter implements DataFilterInterface
{
    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var NestedArrayOperator
     */
    private NestedArrayOperator $nestedArrayOperator;

    /**
     * @param EventList $eventList
     * @param NestedArrayOperator $nestedArrayOperator
     */
    public function __construct(EventList $eventList, NestedArrayOperator $nestedArrayOperator)
    {
        $this->eventList = $eventList;
        $this->nestedArrayOperator = $nestedArrayOperator;
    }

    /**
     * @inheritDoc
     */
    public function filter(string $eventCode, array $eventData): array
    {
        $event = $this->eventList->get($eventCode);

        if (!$event instanceof Event || empty($event->getFields())) {
            return $eventData;
        }

        $filteredData = [];

        foreach ($event->getFields() as $field) {
            if (strpos($field, '.') !== false) {
                $fieldParts = explode('.', $field);

                $value = $this->nestedArrayOperator->getNestedElement($eventData, $fieldParts);
                $this->nestedArrayOperator->setNestedElement($filteredData, $fieldParts, $value);
            } else {
                $filteredData[$field] = $eventData[$field] ?? null;
            }
        }

        return $filteredData;
    }
}
