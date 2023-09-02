<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Event\EventRetriever;
use Magento\AdobeCommerceEventsClient\Model\Event;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\Event\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EventRetriever class
 */
class EventRetrieverTest extends TestCase
{
    /**
     * @var EventRetriever
     */
    private EventRetriever $eventRetriever;

    /**
     * @var Collection|MockObject
     */
    private $collectionMock;

    protected function setUp(): void
    {
        $this->collectionMock = $this->createMock(Collection::class);
        $this->eventRetriever = new EventRetriever($this->collectionMock);
    }

    /**
     * Checks the retrieval and returning of stored event data.
     *
     * @return void
     */
    public function testGetBatch()
    {
        $eventOneData = ['key' => 'test1'];
        $eventCodeOne = 'code1';
        $eventOneMetadata = [
            'commerceEdition' => 'Adobe Commerce',
            'commerceVersion' => '2.4.5',
            'eventsClientVersion' => '100.0.0'
        ];
        $eventOne = $this->createMock(Event::class);
        $eventOne->expects(self::once())
            ->method('getId')
            ->willReturn('1');
        $eventOne->expects(self::once())
            ->method('getEventData')
            ->willReturn($eventOneData);
        $eventOne->expects(self::once())
            ->method('getEventCode')
            ->willReturn($eventCodeOne);
        $eventOne->expects(self::once())
            ->method('getMetadata')
            ->willReturn($eventOneMetadata);

        $eventCodeTwo = 'code2';
        $eventTwoData = ['key' => 'test1'];
        $eventTwoMetadata = [
            'commerceEdition' => 'Adobe Commerce + B2B',
            'commerceVersion' => '2.4.5-p2',
            'eventsClientVersion' => '100.0.1'
        ];
        $eventTwo = $this->createMock(Event::class);
        $eventTwo->expects(self::once())
            ->method('getId')
            ->willReturn('2');
        $eventTwo->expects(self::once())
            ->method('getEventData')
            ->willReturn($eventTwoData);
        $eventTwo->expects(self::once())
            ->method('getEventCode')
            ->willReturn($eventCodeTwo);
        $eventTwo->expects(self::once())
            ->method('getMetadata')
            ->willReturn($eventTwoMetadata);
        
        $this->collectionMock->expects(self::once())
            ->method('addFieldToFilter')
            ->with('status', EventInterface::WAITING_STATUS);
        $this->collectionMock->expects(self::once())
            ->method('getItems')
            ->willReturn([$eventOne, $eventTwo]);

        $events = $this->eventRetriever->getWaitingEvents();
        $this->assertEquals(
            [
                '1' => ['eventCode' => $eventCodeOne, 'eventData' => $eventOneData, 'metadata' => $eventOneMetadata],
                '2' => ['eventCode' => $eventCodeTwo, 'eventData' => $eventTwoData, 'metadata' => $eventTwoMetadata]
            ],
            $events
        );
    }
}
