<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 *  Interface for event subscribing/unsubscribing
 */
interface EventSubscriberInterface
{
    /**
     * @param Event $event
     * @param bool $force
     * @return void
     * @throws ValidatorException
     */
    public function subscribe(Event $event, bool $force = false): void;

    /**
     * @param Event $event
     * @return void
     * @throws ValidatorException
     */
    public function unsubscribe(Event $event): void;
}
