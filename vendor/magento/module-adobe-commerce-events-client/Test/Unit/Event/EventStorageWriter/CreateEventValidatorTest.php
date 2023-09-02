<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\EventStorageWriter;

use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventStorageWriter\CreateEventValidator;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleChecker;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventCode\EventCodeSupportedValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CreateEventValidator class
 */
class CreateEventValidatorTest extends TestCase
{
    /**
     * @var CreateEventValidator
     */
    private CreateEventValidator $createEventValidator;

    /**
     * @var Config|MockObject
     */
    private $eventConfigurationMock;

    /**
     * @var EventCodeSupportedValidator|MockObject
     */
    private $eventCodeSupportedValidatorMock;

    /**
     * @var RuleChecker|MockObject
     */
    private $ruleCheckerMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->eventConfigurationMock = $this->createMock(Config::class);
        $this->eventCodeSupportedValidatorMock = $this->createMock(EventCodeSupportedValidator::class);
        $this->ruleCheckerMock = $this->createMock(RuleChecker::class);
        $this->createEventValidator = new CreateEventValidator(
            $this->eventConfigurationMock,
            $this->eventCodeSupportedValidatorMock,
            $this->ruleCheckerMock,
        );
    }

    public function testConfigurationIsNotEnabled(): void
    {
        $this->eventConfigurationMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->eventCodeSupportedValidatorMock->expects(self::never())
            ->method('validate');

        self::assertFalse($this->createEventValidator->validate($this->eventMock, ['some_data']));
    }

    public function testEventIsNotSupported(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('error happened');

        $this->eventConfigurationMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->eventCodeSupportedValidatorMock->expects(self::once())
            ->method('validate')
            ->willThrowException(new ValidatorException(__('error happened')));
        $this->ruleCheckerMock->expects(self::never())
            ->method('verify');

        self::assertFalse($this->createEventValidator->validate($this->eventMock, ['some_data']));
    }

    public function testRuleCheckerException(): void
    {
        $this->expectException(OperatorException::class);
        $this->expectExceptionMessage('operator error happened');

        $this->eventConfigurationMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->eventCodeSupportedValidatorMock->expects(self::once())
            ->method('validate');
        $this->ruleCheckerMock->expects(self::once())
            ->method('verify')
            ->with($this->eventMock, ['some_data'])
            ->willThrowException(new OperatorException(__('operator error happened')));

        self::assertFalse($this->createEventValidator->validate($this->eventMock, ['some_data']));
    }

    public function testRuleCheckerFalse(): void
    {
        $this->eventConfigurationMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->eventCodeSupportedValidatorMock->expects(self::once())
            ->method('validate');
        $this->ruleCheckerMock->expects(self::once())
            ->method('verify')
            ->with($this->eventMock, ['some_data'])
            ->willReturn(false);

        self::assertFalse($this->createEventValidator->validate($this->eventMock, ['some_data']));
    }

    public function testSuccessValidation(): void
    {
        $this->eventConfigurationMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->eventCodeSupportedValidatorMock->expects(self::once())
            ->method('validate');
        $this->ruleCheckerMock->expects(self::once())
            ->method('verify')
            ->with($this->eventMock, ['some_data'])
            ->willReturn(true);

        self::assertTrue($this->createEventValidator->validate($this->eventMock, ['some_data']));
    }
}
