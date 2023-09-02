<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Api;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\Framework\Exception\AlreadyExistsException;

interface EventRepositoryInterface
{
    /**
     * @param int $entityId
     * @return EventInterface
     */
    public function getById(int $entityId): EventInterface;

    /**
     * @param EventInterface $event
     * @return EventInterface
     * @throws AlreadyExistsException
     */
    public function save(EventInterface $event): EventInterface;
}
