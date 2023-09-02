<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\EventStorageWriter;

use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleChecker;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventCode\EventCodeSupportedValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 * Checks if event can be created
 * - configuration is enabled
 * - event is supported
 * - verification passes for all rules
 */
class CreateEventValidator
{
    /**
     * @var Config
     */
    private Config $eventConfiguration;

    /**
     * @var EventCodeSupportedValidator
     */
    private EventCodeSupportedValidator $eventCodeSupportedValidator;

    /**
     * @var RuleChecker
     */
    private RuleChecker $ruleChecker;

    /**
     * @param Config $eventConfiguration
     * @param EventCodeSupportedValidator $eventCodeSupportedValidator
     * @param RuleChecker $ruleChecker
     */
    public function __construct(
        Config $eventConfiguration,
        EventCodeSupportedValidator $eventCodeSupportedValidator,
        RuleChecker $ruleChecker
    ) {
        $this->eventConfiguration = $eventConfiguration;
        $this->eventCodeSupportedValidator = $eventCodeSupportedValidator;
        $this->ruleChecker = $ruleChecker;
    }

    /**
     * Checks if event can be created
     *
     * @param Event $event
     * @param array $eventData
     * @return bool
     * @throws ValidatorException|OperatorException
     */
    public function validate(Event $event, array $eventData): bool
    {
        if (!$this->eventConfiguration->isEnabled()) {
            return false;
        }

        $this->eventCodeSupportedValidator->validate($event);

        return $this->ruleChecker->verify($event, $eventData);
    }
}
