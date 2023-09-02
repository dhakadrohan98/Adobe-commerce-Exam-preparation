<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Event\Collector\AggregatedEventList;
use Magento\AdobeCommerceEventsClient\Event\Collector\EventData;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeCommerceEventsClient\Event\EventInfo;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use Magento\AdobeCommerceEventsClient\Util\ClassToArrayConverter;
use Magento\AdobeCommerceEventsClient\Util\EventCodeConverter;
use Magento\AdobeCommerceEventsClient\Util\ReflectionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for EventInfo class
 */
class EventInfoTest extends TestCase
{
    /**
     * @var Event|MockObject
     */
    private $eventMock;

    /**
     * @var EventValidatorInterface|MockObject
     */
    private $eventCodeValidatorMock;

    /**
     * @var EventInfo
     */
    private EventInfo $eventInfo;

    /**
     * @var AggregatedEventList|MockObject
     */
    private $aggregatedEventList;

    /**
     * @var ReflectionHelper|MockObject
     */
    private $reflectionHelperMock;

    /**
     * @var ClassToArrayConverter|MockObject
     */
    private $classToArrayConverterMock;
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->eventCodeValidatorMock = $this->getMockForAbstractClass(EventValidatorInterface::class);
        $this->aggregatedEventList = $this->createMock(AggregatedEventList::class);
        $this->reflectionHelperMock = $this->createMock(ReflectionHelper::class);
        $this->classToArrayConverterMock = $this->createMock(ClassToArrayConverter::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->eventInfo = new EventInfo(
            $this->eventCodeValidatorMock,
            new EventCodeConverter(),
            $this->aggregatedEventList,
            $this->reflectionHelperMock,
            $this->classToArrayConverterMock,
            $this->loggerMock
        );
    }

    public function testWrongEventName(): void
    {
        $this->expectException(ValidatorException::class);

        $this->eventCodeValidatorMock->expects(self::once())
            ->method('validate')
            ->with($this->eventMock)
            ->willThrowException(new ValidatorException(__('Wrong event prefix')));

        $this->eventInfo->getInfo($this->eventMock);
    }

    public function testObserverEventInfoNotFound(): void
    {
        $this->expectException(EventException::class);

        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('observer.some_event_code');
        $this->aggregatedEventList->expects(self::once())
            ->method('getList')
            ->willReturn([]);

        $this->eventInfo->getInfo($this->eventMock);
    }

    public function testObserverEventInfo(): void
    {
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('observer.some_event_code');
        $eventDataMock = $this->createMock(EventData::class);
        $eventDataMock->expects(self::once())
            ->method('getEventClassEmitter')
            ->willReturn('Path\To\Some\Class');
        $this->aggregatedEventList->expects(self::once())
            ->method('getList')
            ->willReturn([
                'observer.some_event_code' => $eventDataMock
            ]);
        $this->classToArrayConverterMock->expects(self::once())
            ->method('convert')
            ->with('Path\To\Some\Class')
            ->willReturn(['id' => 1]);
        $this->reflectionHelperMock->expects(self::never())
            ->method('getReturnType');

        self::assertEquals(
            ['id' => 1],
            $this->eventInfo->getInfo($this->eventMock)
        );
    }

    public function testPluginEventInfoNotFound(): void
    {
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('magento.catalog.model.resource_model.categor.save');
        $this->expectException(EventException::class);
        $this->expectExceptionMessage('Cannot get details for event');

        $this->loggerMock->expects(self::once())
            ->method('error');
        $this->eventInfo->getInfo($this->eventMock);
    }

    public function testCategoryPluginEventInfo(): void
    {
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('plugin.magento.adobe_commerce_events_client.api.event_repository.save');
        $returnType = 'Magento\AdobeCommerceEventsClient\Api\Data\EventInterface';
        $this->reflectionHelperMock->expects(self::once())
            ->method('getReturnType')
            ->willReturn($returnType);
        $this->classToArrayConverterMock->expects(self::once())
            ->method('convert')
            ->with($returnType, 3)
            ->willReturn([
                'id' => '1',
                'event_data' => 'test',
                'event_code' => 'test',
            ]);

        $info = $this->eventInfo->getInfo($this->eventMock, 3);

        self::assertArrayHasKey('id', $info);
        self::assertArrayHasKey('event_data', $info);
        self::assertArrayHasKey('event_code', $info);
    }
}
