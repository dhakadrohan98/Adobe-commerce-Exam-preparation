<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Api\EventRepositoryInterface;
use Magento\AdobeCommerceEventsClient\Event\DataFilterInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventMetadataCollector;
use Magento\AdobeCommerceEventsClient\Event\EventStorageWriter;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriber;
use Magento\AdobeCommerceEventsClient\Model\Event as EventModel;
use Magento\AdobeCommerceEventsClient\Model\EventFactory as EventModelFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for EventStorageWriter class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EventStorageWriterTest extends TestCase
{
    /**
     * @var EventStorageWriter
     */
    private EventStorageWriter $storageWriter;

    /**
     * @var EventList|MockObject
     */
    private $eventListMock;

    /**
     * @var EventStorageWriter\CreateEventValidator|MockObject
     */
    private $validatorMock;

    /**
     * @var EventModelFactory|MockObject
     */
    private $eventModelFactoryMock;

    /**
     * @var DataFilterInterface|MockObject
     */
    private $dataFilterMock;

    /**
     * @var EventRepositoryInterface|MockObject
     */
    private $eventRepositoryMock;

    /**
     * @var EventMetadataCollector|MockObject
     */
    private $metadataCollectorMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->eventListMock = $this->createPartialMock(EventList::class, ['getAll']);
        $this->validatorMock = $this->createMock(EventStorageWriter\CreateEventValidator::class);
        $this->eventRepositoryMock = $this->getMockForAbstractClass(EventRepositoryInterface::class);
        $this->eventModelFactoryMock = $this->createMock(EventModelFactory::class);
        $this->dataFilterMock = $this->getMockForAbstractClass(DataFilterInterface::class);
        $this->metadataCollectorMock = $this->createMock(EventMetadataCollector::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->storageWriter = new EventStorageWriter(
            $this->eventListMock,
            $this->validatorMock,
            $this->eventRepositoryMock,
            $this->eventModelFactoryMock,
            $this->dataFilterMock,
            $this->metadataCollectorMock,
            $this->loggerMock
        );
    }

    /**
     * Checks the updating of events with a success status.
     *
     * @return void
     */
    public function testUpdateStatus()
    {
        $eventModelOne = $this->createMock(EventModel::class);
        $eventModelOne->expects(self::once())
            ->method("setStatus")
            ->with(EventInterface::SUCCESS_STATUS);

        $eventModelTwo = $this->createMock(EventModel::class);
        $eventModelTwo->expects(self::once())
            ->method('setStatus')
            ->with(EventInterface::SUCCESS_STATUS);

        $this->eventRepositoryMock->expects(self::exactly(2))
            ->method('getById')
            ->willReturnOnConsecutiveCalls($eventModelOne, $eventModelTwo);

        $this->eventRepositoryMock->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive([$eventModelOne], [$eventModelTwo]);
        $this->storageWriter->updateStatus([1, 2], EventInterface::SUCCESS_STATUS);
    }

    /**
     * Checks the updating of events after a failure to send event data.
     *
     * @return void
     */
    public function testUpdateFailure()
    {
        $maxRetries = 5;
        $eventModelOne = $this->createMock(EventModel::class);
        $eventModelOne->expects(self::once())
            ->method('getRetriesCount')
            ->willReturn($maxRetries);
        $eventModelOne->expects(self::once())
            ->method('setStatus')
            ->with(EventInterface::FAILURE_STATUS);
        $eventModelOne->expects(self::never())
            ->method('setRetriesCount');

        $eventModelTwo = $this->createMock(EventModel::class);
        $eventModelTwo->expects(self::once())
            ->method('getRetriesCount')
            ->willReturn(0);
        $eventModelTwo->expects(self::once())
            ->method('setRetriesCount')
            ->with(1);
        $eventModelTwo->expects(self::once())
            ->method('setStatus')
            ->with(EventInterface::WAITING_STATUS);

        $this->eventRepositoryMock->expects(self::exactly(2))
            ->method('getById')
            ->willReturnOnConsecutiveCalls($eventModelOne, $eventModelTwo);

        $this->eventRepositoryMock->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive([$eventModelOne], [$eventModelTwo]);
        $this->storageWriter->updateFailure([1, 2], $maxRetries);
    }

    /**
     * Tests that event is not saved in the case when validation failed.
     *
     * @return void
     */
    public function testCreateEventValidationFailed()
    {
        $eventCode = 'some_code';
        $eventData = [];

        $eventMock = $this->createEventMock($eventCode);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);
        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($eventMock, $eventData)
            ->willReturn(false);
        $this->eventModelFactoryMock->expects(self::never())
            ->method('create');

        $this->storageWriter->createEvent($eventCode, $eventData);
    }

    /**
     * Tests the saving of new event data in the case that the event data does not contain a key to be ignored.
     *
     * @return void
     */
    public function testCreateEvent()
    {
        $eventCode = "test.code";
        $eventCodeWithCommerce = EventSubscriber::EVENT_PREFIX_COMMERCE . "test.code";
        $eventData = [
            "images" => [
                [
                    "id" => "1",
                    "file" => "image.jpg"
                ],
                [
                    "id" => "2",
                    "position" => "1"
                ]
            ]
        ];

        $eventMock = $this->createEventMock($eventCode);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);
        $eventModelMock = $this->createMock(EventModel::class);
        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($eventMock, $eventData)
            ->willReturn(true);
        $this->eventModelFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($eventModelMock);
        $eventModelMock->expects(self::once())
            ->method('setEventCode')
            ->with($eventCodeWithCommerce);
        $eventModelMock->expects(self::once())
            ->method('setEventData')
            ->with($eventData);
        $this->dataFilterMock->expects(self::once())
            ->method('filter')
            ->with($eventCodeWithCommerce, $eventData)
            ->willReturn($eventData);
        $this->eventRepositoryMock->expects(self::once())
            ->method('save')
            ->with($eventModelMock);
        $this->loggerMock->expects(self::never())
            ->method('error');

        $this->metadataCollectorMock->expects(self::once())
            ->method('getMetadata')
            ->willReturn([
                'commerceEdition' => 'Adobe Commerce',
                'commerceVersion' => '2.4.5',
                'eventsClientVersion' => '100.0.0'
            ]);

        $this->storageWriter->createEvent($eventCode, $eventData);
    }

    /**
     * Tests the saving of new event data in the case that the event data contains a key to be ignored.
     *
     * @return void
     */
    public function testCreateEventWithFilteredData()
    {
        $eventCode = "test.code";
        $eventCodeWithCommerce = EventSubscriber::EVENT_PREFIX_COMMERCE . "test.code";
        $inputEventData = [
            'key1' => 'value1',
            'key2' => 'value1',
        ];
        $filteredEventData = [
            'key1' => 'value1',
        ];

        $eventMock = $this->createEventMock($eventCode);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);
        $this->validatorMock->expects(self::once())
            ->method('validate')
            ->with($eventMock, $inputEventData)
            ->willReturn(true);
        $eventModelMock = $this->createMock(EventModel::class);
        $this->eventModelFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($eventModelMock);
        $eventModelMock->expects(self::once())
            ->method('setEventCode')
            ->with($eventCodeWithCommerce);
        $eventModelMock->expects(self::once())
            ->method('setEventData')
            ->with($filteredEventData);
        $this->eventRepositoryMock->expects(self::once())
            ->method('save')
            ->with($eventModelMock);
        $this->dataFilterMock->expects(self::once())
            ->method('filter')
            ->with($eventCodeWithCommerce, $inputEventData)
            ->willReturn($filteredEventData);
        $this->metadataCollectorMock->expects(self::once())
            ->method('getMetadata')
            ->willReturn([
                'commerceEdition' => 'Adobe Commerce',
                'commerceVersion' => '2.4.5',
                'eventsClientVersion' => '100.0.0'
            ]);

        $this->storageWriter->createEvent($eventCode, $inputEventData);
    }

    /**
     * Tests that saveEvent method is called for each event that registered as alias.
     * Tests that not enabled events are skipped.
     *
     * @return void
     */
    public function testEventHasAliases()
    {
        $eventData = ['key' => 'value'];
        $eventCodeOne = "test.code.one";
        $eventCodeTwo = "test.code.two";
        $eventCodeThree = "test.code.three";
        $eventMockOne = $this->createMock(Event::class);
        $eventMockTwo = $this->createMock(Event::class);
        $eventMockThree = $this->createMock(Event::class);
        $eventMockOne->expects(self::never())
            ->method('getName')
            ->willReturn($eventCodeOne);
        $eventMockOne->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);
        $eventMockOne->expects(self::never())
            ->method('isBasedOn');
        $eventMockTwo->expects(self::once())
            ->method('getName')
            ->willReturn($eventCodeTwo);
        $eventMockTwo->expects(self::once())
            ->method('isBasedOn')
            ->willReturn(true);
        $eventMockTwo->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $eventMockThree->expects(self::once())
            ->method('getName')
            ->willReturn($eventCodeThree);
        $eventMockThree->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $eventMockThree->expects(self::once())
            ->method('isBasedOn')
            ->willReturn(true);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMockOne, $eventMockTwo, $eventMockThree]);

        $this->validatorMock->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive(
                [$eventMockTwo, $eventData],
                [$eventMockThree, $eventData],
            )
            ->willReturn(true);

        $this->storageWriter->createEvent($eventCodeOne, $eventData);
    }

    /**
     * Tests that an event is not saved when the event code input to createEvent is not based on custom
     * event code.
     *
     * @return void
     */
    public function testEventNotSaved()
    {
        $eventCode = 'observer.some_code';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');
        $eventMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $eventMock->expects(self::once())
            ->method('isBasedOn')
            ->with($eventCode)
            ->willReturn(false);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);
        $this->validatorMock->expects(self::never())
            ->method('validate');

        $this->storageWriter->createEvent($eventCode, []);
    }

    /**
     * @param string $eventCode
     * @return MockObject
     */
    private function createEventMock(string $eventCode): MockObject
    {
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $eventMock->expects(self::once())
            ->method('isBasedOn')
            ->with($eventCode)
            ->willReturn(true);

        return $eventMock;
    }
}
