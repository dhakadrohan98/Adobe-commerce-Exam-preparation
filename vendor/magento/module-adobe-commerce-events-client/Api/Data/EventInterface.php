<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Api\Data;

use Magento\AdobeCommerceEventsClient\Model\EventException;

/**
 * Defines the event database model
 */
interface EventInterface
{
    public const FIELD_ID = 'event_id';
    public const FIELD_CODE = 'event_code';
    public const FIELD_DATA = 'event_data';
    public const FIELD_METADATA = 'metadata';
    public const FIELD_RETRIES = 'retries_count';
    public const FIELD_STATUS = 'status';

    public const WAITING_STATUS = 0;
    public const SUCCESS_STATUS = 1;
    public const FAILURE_STATUS = 2;
    public const SENDING_STATUS = 3;

    /**
     * @return ?string
     */
    public function getId(): ?string;

    /**
     * @return ?string
     */
    public function getEventCode(): ?string;

    /**
     * @param string $eventCode
     * @return EventInterface
     */
    public function setEventCode(string $eventCode): EventInterface;

    /**
     * @return array
     * @throws EventException
     */
    public function getEventData(): array;

    /**
     * @param array $eventData
     * @return EventInterface
     * @throws EventException
     */
    public function setEventData(array $eventData): EventInterface;

    /**
     * @return array
     * @throws EventException
     */
    public function getMetadata(): array;

    /**
     * @param array $metadata
     * @return EventInterface
     * @throws EventException
     */
    public function setMetadata(array $metadata): EventInterface;

    /**
     * @param int $statusCode
     * @return EventInterface
     */
    public function setStatus(int $statusCode): EventInterface;

    /**
     * @return int
     */
    public function getRetriesCount(): int;

    /**
     * @param int $retriesCount
     * @return EventInterface
     */
    public function setRetriesCount(int $retriesCount): EventInterface;
}
