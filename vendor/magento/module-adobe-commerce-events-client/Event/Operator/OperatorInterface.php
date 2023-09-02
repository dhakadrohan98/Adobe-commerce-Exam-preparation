<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Operator;

/**
 * Interface for operator classes
 */
interface OperatorInterface
{
    /**
     * @param string $ruleValue
     * @param $fieldValue
     * @return bool
     * @throws OperatorException
     */
    public function verify(string $ruleValue, $fieldValue): bool;
}
