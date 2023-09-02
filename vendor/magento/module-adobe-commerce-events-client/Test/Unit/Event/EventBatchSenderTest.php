<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Event\Client;
use Magento\AdobeCommerceEventsClient\Event\EventBatchGenerator;
use Magento\AdobeCommerceEventsClient\Event\EventBatchSender;
use Magento\AdobeCommerceEventsClient\Event\EventRetriever;
use Magento\AdobeCommerceEventsClient\Event\EventStorageWriter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for EventBatchSender class
 */
class EventBatchSenderTest extends TestCase
{
    /**
     * @var EventBatchSender
     */
    private EventBatchSender $batchSender;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Client|MockObject
     */
    private $clientMock;

    /**
     * @var EventBatchGenerator|MockObject
     */
    private $eventBatchGeneratorMock;

    /**
     * @var EventRetriever|MockObject
     */
    private $eventRetrieverMock;

    /**
     * @var EventStorageWriter|MockObject
     */
    private $eventStorageWriterMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var array
     */
    private $events = [
        '1' => ['data' => 'dataOne', 'eventCode' => 'codeOne'],
        '2' => ['data' => 'dataTwo', 'eventCode' => 'codeTwo']
    ];

    /**
     * @var integer
     */
    private $maxRetries = 5;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->clientMock = $this->createMock(Client::class);
        $this->eventBatchGeneratorMock = $this->createMock(EventBatchGenerator::class);
        $this->eventRetrieverMock = $this->createMock(EventRetriever::class);
        $this->eventStorageWriterMock = $this->createMock(EventStorageWriter::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->batchSender = new EventBatchSender(
            $this->scopeConfigMock,
            $this->clientMock,
            $this->eventBatchGeneratorMock,
            $this->eventRetrieverMock,
            $this->eventStorageWriterMock,
            $this->loggerMock
        );
    }

    /**
     * Tests successful sending of a batch of event data.
     *
     * @return void
     */
    public function testSendEventDataBatchSuccess()
    {
        $eventIds = [1, 2];
        $this->eventRetrieverMock->expects(self::once())
            ->method('getWaitingEvents')
            ->willReturn($this->events);
        $this->eventBatchGeneratorMock->expects(self::once())
            ->method('generateBatch')
            ->with($this->events)
            ->willReturn($this->events);
        $this->eventStorageWriterMock->expects(self::exactly(2))
            ->method('updateStatus')
            ->withConsecutive([$eventIds, EventInterface::SENDING_STATUS], [$eventIds, EventInterface::SUCCESS_STATUS]);
        $this->scopeConfigMock->expects(self::never())
            ->method('getValue');
        $this->clientMock->expects(self::once())
            ->method('sendEventDataBatch')
            ->with(array_values($this->events))
            ->willReturn(new Response(200));
        $this->eventStorageWriterMock->expects(self::never())
            ->method('updateFailure');

        $this->batchSender->sendEventDataBatches();
    }

    /**
     * Tests failed sending of a batch of event data that leads to resending of only some of the events.
     *
     * @return void
     */
    public function testSendEventDataBatchFailure()
    {
        $eventIds = [1, 2];
        $this->eventRetrieverMock->expects(self::once())
            ->method('getWaitingEvents')
            ->willReturn($this->events);
        $this->eventBatchGeneratorMock->expects(self::exactly(2))
            ->method('generateBatch')
            ->withConsecutive([$this->events], [[1 => $this->events[1]]])
            ->willReturnOnConsecutiveCalls($this->events, [1 => $this->events[1]]);
        $this->eventStorageWriterMock->expects(self::exactly(2))
            ->method('updateStatus')
            ->withConsecutive([$eventIds, EventInterface::SENDING_STATUS], [[1], EventInterface::SENDING_STATUS]);
        $this->scopeConfigMock->expects(self::exactly(2))
            ->method('getValue')
            ->willReturn($this->maxRetries);
        $this->clientMock->expects(self::exactly(2))
            ->method('sendEventDataBatch')
            ->withConsecutive([array_values($this->events)], [[0 => $this->events[1]]])
            ->willReturn(new Response(400, [], '{"message": "msg"}'));
        $this->eventStorageWriterMock->expects(self::exactly(2))
            ->method('updateFailure')
            ->withConsecutive([$eventIds, $this->maxRetries], [[1], $this->maxRetries])
            ->willReturnOnConsecutiveCalls([2], [1]);

        $this->batchSender->sendEventDataBatches();
    }

    /**
     * Tests failed sending of a batch of event data that is caused by issues with connecting to the events service.
     *
     * @return void
     */
    public function testSendEventDataBatchConnectionFailure()
    {
        $eventIds = [1, 2];
        $this->eventRetrieverMock->expects(self::once())
            ->method('getWaitingEvents')
            ->willReturn($this->events);
        $this->eventBatchGeneratorMock->expects(self::once())
            ->method('generateBatch')
            ->with($this->events)
            ->willReturn($this->events);
        $this->eventStorageWriterMock->expects(self::once())
            ->method('updateStatus')
            ->with($eventIds, EventInterface::SENDING_STATUS);
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->willReturn($this->maxRetries);
        $this->clientMock->expects(self::once())
            ->method('sendEventDataBatch')
            ->willThrowException(new ConnectException("", new Request("POST", "")));
        $this->eventStorageWriterMock->expects(self::once())
            ->method('updateFailure')
            ->with($eventIds, $this->maxRetries)
            ->willReturn([1, 2]);

        $this->batchSender->sendEventDataBatches();
    }
}
