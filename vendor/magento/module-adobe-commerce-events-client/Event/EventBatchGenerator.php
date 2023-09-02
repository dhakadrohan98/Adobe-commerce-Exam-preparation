<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

/**
 * Class for generating batches of event data.
 */
class EventBatchGenerator
{
    // Maximum number of bytes that can be sent in a batch of messages. Pipeline limits the size of message batches to
    // 2 MB, and headers will need to be added to each event message. This value leaves 10% of the limit for headers.
    private const BATCH_SIZE_LIMIT = 2097152 * 0.9;

    // Maximum number of messages that can be sent in a batch.
    private const MAX_BATCH_SIZE = 100;

    /**
     * Given an array of event data, generates a batch of events to send containing a number of events that does not
     * exceed MAX_MATCH_SIZE and a total number of bytes that does not exceed BATCH_SIZE_LIMIT.
     *
     * @param array $events
     * @return array
     */
    public function generateBatch(array $events): array
    {
        $totalBytes = 0;
        $eventBatch = [];
        foreach ($events as $eventId => $event) {
            $totalBytes += strlen(json_encode($event));
            if ($totalBytes > self::BATCH_SIZE_LIMIT) {
                return $eventBatch;
            }

            $eventBatch[$eventId] = $event;
            if (count($eventBatch) == self::MAX_BATCH_SIZE) {
                return $eventBatch;
            }
        }

        return $eventBatch;
    }
}
