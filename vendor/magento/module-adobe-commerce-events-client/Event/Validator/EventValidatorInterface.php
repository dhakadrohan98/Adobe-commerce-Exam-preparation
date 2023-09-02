<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Validator;

use Magento\AdobeCommerceEventsClient\Event\Event;

/**
 * Validator interface for event code
 */
interface EventValidatorInterface
{
    /**
     * @param Event $event
     * @param bool $force
     * @return void
     * @throws ValidatorException
     */
    public function validate(Event $event, bool $force = false): void;
}
