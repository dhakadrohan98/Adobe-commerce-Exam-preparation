<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Api\EventRepositoryInterface;
use Magento\AdobeCommerceEventsClient\Event\EventStorageWriter\CreateEventValidator;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use Magento\AdobeCommerceEventsClient\Model\EventFactory as EventModelFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Psr\Log\LoggerInterface;

/**
 * Writes new event data to storage and updates the status of existing stored events.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EventStorageWriter
{
    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var EventRepositoryInterface
     */
    private EventRepositoryInterface $eventRepository;

    /**
     * @var EventModelFactory
     */
    private EventModelFactory $eventModelFactory;

    /**
     * @var DataFilterInterface
     */
    private DataFilterInterface $eventDataFilter;

    /**
     * @var EventMetadataCollector
     */
    private EventMetadataCollector $metadataCollector;

    /**
     * @var CreateEventValidator
     */
    private CreateEventValidator $createEventValidator;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param EventList $eventList
     * @param CreateEventValidator $createEventValidator
     * @param EventRepositoryInterface $eventRepository
     * @param EventModelFactory $eventModelFactory
     * @param DataFilterInterface $eventDataFilter
     * @param EventMetadataCollector $metadataCollector
     * @param LoggerInterface $logger
     */
    public function __construct(
        EventList $eventList,
        CreateEventValidator $createEventValidator,
        EventRepositoryInterface $eventRepository,
        EventModelFactory $eventModelFactory,
        DataFilterInterface $eventDataFilter,
        EventMetadataCollector $metadataCollector,
        LoggerInterface $logger
    ) {
        $this->eventList = $eventList;
        $this->createEventValidator = $createEventValidator;
        $this->eventRepository = $eventRepository;
        $this->eventModelFactory = $eventModelFactory;
        $this->eventDataFilter = $eventDataFilter;
        $this->metadataCollector = $metadataCollector;
        $this->logger = $logger;
    }

    /**
     * Updates statuses for the stored events with the specified ids to match the specified status code.
     *
     * @param array $eventIds
     * @param int $statusCode
     * @return void
     */
    public function updateStatus(array $eventIds, int $statusCode): void
    {
        foreach ($eventIds as $eventId) {
            $storedEvent = $this->eventRepository->getById($eventId);
            $storedEvent->setStatus($statusCode);
            $this->eventRepository->save($storedEvent);
        }
    }

    /**
     * Updates stored events with the specified ids to reflect unsuccessful sending of event data by doing one of the
     * following:
     * - increments retries_count for an event if the incremented count is not greater than the configured maximum
     * number of retries
     * - otherwise sets the stored status for the event to reflect failure
     *
     * Returns an array of eventIds specifying the events whose statuses were updated to failed.
     *
     * @param array $eventIds
     * @param int $maxRetries
     * @return array
     */
    public function updateFailure(array $eventIds, int $maxRetries): array
    {
        $failedStatusEvents = [];

        foreach ($eventIds as $eventId) {
            $storedEvent = $this->eventRepository->getById((int)$eventId);
            $retries = $storedEvent->getRetriesCount() + 1;
            if ($retries > $maxRetries) {
                $storedEvent->setStatus(EventInterface::FAILURE_STATUS);
                $failedStatusEvents[] = $eventId;
            } else {
                $storedEvent->setStatus(EventInterface::WAITING_STATUS);
                $storedEvent->setRetriesCount($retries);
            }
            $this->eventRepository->save($storedEvent);
        }
        
        return $failedStatusEvents;
    }

    /**
     * Checks if there are registered events that depend on this eventCode.
     * Creates events for all appropriate registration.
     *
     * @param string $eventCode
     * @param array $eventData
     * @return void
     * @throws EventException|InvalidConfigurationException
     */
    public function createEvent(string $eventCode, array $eventData): void
    {
        $eventCode = $this->eventList->removeCommercePrefix($eventCode);
        foreach ($this->eventList->getAll() as $event) {
            if ($event->isEnabled() && $event->isBasedOn($eventCode)) {
                $this->saveEvent($event, $eventData);
            }
        }
    }

    /**
     * Creates an Event with the specified event code and event data and adds it to storage.
     *
     * @param Event $event
     * @param array $eventData
     * @return void
     * @throws EventException
     */
    private function saveEvent(Event $event, array $eventData): void
    {
        $eventCode = EventSubscriber::EVENT_PREFIX_COMMERCE . $event->getName();
        try {
            if (!$this->createEventValidator->validate($event, $eventData)) {
                return;
            }

            $eventModel = $this->eventModelFactory->create();
            $eventModel->setEventCode($eventCode);
            $eventModel->setEventData($this->eventDataFilter->filter($eventCode, $eventData));
            $eventModel->setMetadata($this->metadataCollector->getMetadata());

            $this->eventRepository->save($eventModel);
        } catch (AlreadyExistsException|ValidatorException $e) {
            $this->logger->error(sprintf(
                'Could not create event "%s": %s',
                $eventCode,
                $e->getMessage()
            ));
        } catch (OperatorException $e) {
            $this->logger->error(sprintf(
                'Could not check that event "%s" passed the rule, error: %s',
                $eventCode,
                $e->getMessage()
            ));
        }
    }
}
