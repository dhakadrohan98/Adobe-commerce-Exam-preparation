<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Config;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\Framework\Config\ConverterInterface;

/**
 * Converts data from io_events.xml files to the array of events
 */
class Converter implements ConverterInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     * @throws \InvalidArgumentException
     */
    public function convert($source)
    {
        $output = [];
        /** @var \DOMNodeList $events */
        $events = $source->getElementsByTagName('event');
        /** @var \DOMElement $eventConfig */
        foreach ($events as $eventConfig) {
            $fields = $rules = [];
            foreach ($eventConfig->getElementsByTagName('field') as $field) {
                if ($field->parentNode->nodeName == 'fields') {
                    $fields[] = $field->getAttribute('name');
                }
            }

            foreach ($eventConfig->getElementsByTagName('rule') as $ruleNode) {
                $ruleData = [];
                foreach ($ruleNode->getElementsByTagName('*') as $ruleField) {
                    $ruleData[$ruleField->nodeName] = $ruleField->nodeValue;
                }

                $rules[] = $ruleData;
            }

            $eventName = strtolower($eventConfig->getAttribute('name'));
            $eventParent = strtolower($eventConfig->getAttribute('parent')) ?: null;

            $output[$eventName] = [
                Event::EVENT_NAME => $eventName,
                Event::EVENT_FIELDS => $fields,
                Event::EVENT_RULES => $rules,
                Event::EVENT_PARENT => $eventParent
            ];
        }

        return $output;
    }
}
