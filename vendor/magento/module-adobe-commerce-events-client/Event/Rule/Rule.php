<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Rule;

/**
 * Rule data object
 */
class Rule
{
    public const RULE_FIELD = 'field';
    public const RULE_OPERATOR = 'operator';
    public const RULE_VALUE = 'value';

    /**
     * @var string
     */
    private string $field;

    /**
     * @var string
     */
    private string $operator;

    /**
     * @var string
     */
    private string $value;

    /**
     * @param string $field
     * @param string $operator
     * @param string $value
     */
    public function __construct(string $field, string $operator, string $value)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
