<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Synchronizer;

use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriber;
use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadataFactory;
use Magento\AdobeIoEventsClient\Console\CreateEventProvider;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\IOEventsAPIClient;

/**
 * Register events metadata in Adobe I/O.
 */
class AdobeIoEventMetadataSynchronizer
{
    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var IOEventsAPIClient
     */
    private IOEventsAPIClient $IOEventsAPIClient;

    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var AdobeIoEventMetadataFactory
     */
    private AdobeIoEventMetadataFactory $ioMetadataFactory;

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param IOEventsAPIClient $IOEventsAPIClient
     * @param EventList $eventList
     * @param AdobeIoEventMetadataFactory $metadataFactory
     */
    public function __construct(
        AdobeIOConfigurationProvider $configurationProvider,
        IOEventsAPIClient $IOEventsAPIClient,
        EventList $eventList,
        AdobeIoEventMetadataFactory $metadataFactory
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->IOEventsAPIClient = $IOEventsAPIClient;
        $this->eventList = $eventList;
        $this->ioMetadataFactory = $metadataFactory;
    }

    /**
     * Register events metadata in Adobe I/O.
     *
     * @returns array list of messages
     * @throws SynchronizerException
     */
    public function run(): array
    {
        $events = $this->eventList->getAll();
        if (empty($events)) {
            return [];
        }

        $provider = $this->configurationProvider->retrieveProvider();
        if (is_null($provider)) {
            throw new SynchronizerException(__(
                sprintf(
                    'Cannot register events metadata during setup:upgrade. ' .
                    'Run bin/magento %s to configure an event provider.',
                    CreateEventProvider::COMMAND_NAME
                )
            ));
        }

        try {
            $registeredEventMetadata = $this->IOEventsAPIClient->listRegisteredEventMetadata($provider);
            $registeredEvents = [];
            foreach ($registeredEventMetadata as $eventMetadata) {
                $registeredEvents[] = $eventMetadata->getEventCode();
            }
        } catch (\Exception $e) {
            throw new SynchronizerException(__(
                sprintf(
                    'Cannot register events metadata during setup:upgrade. ' .
                    'An error occurred while fetching previously registered events. Error: %s',
                    $e->getMessage()
                )
            ));
        }

        try {
            $messages = [];

            foreach ($events as $event) {
                $eventCode = EventSubscriber::EVENT_PREFIX_COMMERCE . $event->getName();

                if (in_array($eventCode, $registeredEvents)) {
                    continue;
                }

                $this->IOEventsAPIClient->createEventMetadata(
                    $provider,
                    $this->ioMetadataFactory->generate($eventCode)
                );
                $messages[] = sprintf(
                    'Event metadata was registered for the event "%s"',
                    $event->getName()
                );
            }

            return $messages;
        } catch (\Exception $e) {
            throw new SynchronizerException(__(
                sprintf(
                    'An error occurred while registering metadata for event %s. Error: %s',
                    $event->getName(),
                    $e->getMessage()
                )
            ));
        }
    }
}
