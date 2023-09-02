<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use GuzzleHttp\Exception\GuzzleException;
use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Class for sending event data in batches to the configured events service
 */
class EventBatchSender
{
    private const CONFIG_PATH_MAX_RETRIES = 'adobe_io_events/eventing/max_retries';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var EventBatchGenerator
     */
    private EventBatchGenerator $batchGenerator;

    /**
     * @var EventRetriever
     */
    private EventRetriever $eventRetriever;

    /**
     * @var EventStorageWriter
     */
    private EventStorageWriter $storageWriter;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ScopeConfigInterface $config
     * @param Client $client
     * @param EventBatchGenerator $batchGenerator
     * @param EventRetriever $eventRetriever
     * @param EventStorageWriter $storageWriter
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $config,
        Client $client,
        EventBatchGenerator $batchGenerator,
        EventRetriever $eventRetriever,
        EventStorageWriter $storageWriter,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->client = $client;
        $this->batchGenerator = $batchGenerator;
        $this->eventRetriever = $eventRetriever;
        $this->storageWriter = $storageWriter;
        $this->logger = $logger;
    }

    /**
     * Reads stored event data waiting to be sent, sends the data in batches to the Events Service, and updates stored
     * events based on the success or failure of sending the data.
     *
     * @return void
     * @throws EventException
     */
    public function sendEventDataBatches(): void
    {
        $waitingEvents = $this->eventRetriever->getWaitingEvents();

        while (count($waitingEvents) != 0) {
            $eventBatch = $this->batchGenerator->generateBatch($waitingEvents);

            $eventIds = array_keys($eventBatch);
            $this->storageWriter->updateStatus($eventIds, EventInterface::SENDING_STATUS);
            $eventData = array_values($eventBatch);

            try {
                $response = $this->client->sendEventDataBatch($eventData);

                if ($response->getStatusCode() == 200) {
                    $this->logger->info(sprintf(
                        'Event data batch of %s events was successfully published.',
                        count($eventData)
                    ));
                    $this->storageWriter->updateStatus($eventIds, EventInterface::SUCCESS_STATUS);
                    $waitingEvents = $this->unsetEvents($waitingEvents, $eventIds);
                } else {
                    $this->logger->error(sprintf(
                        'Publishing of batch of %s events failed. Error code: %d; reason: %s %s',
                        count($eventBatch),
                        $response->getStatusCode(),
                        $response->getReasonPhrase(),
                        $response->getBody()->getContents()
                    ));
                    $failedStatusEvents = $this->setFailure($eventIds);
                    $waitingEvents = $this->unsetEvents($waitingEvents, $failedStatusEvents);
                }
            } catch (GuzzleException $exception) {
                $this->logger->error(sprintf(
                    'Publishing of batch of %s events failed: %s',
                    count($eventBatch),
                    $exception->getMessage()
                ));
                $failedStatusEvents = $this->setFailure($eventIds);
                $waitingEvents = $this->unsetEvents($waitingEvents, $failedStatusEvents);
            } catch (InvalidConfigurationException $exception) {
                $this->logger->error(sprintf(
                    'Publishing of batch of %s events failed. Configuration is not valid: %s',
                    count($eventBatch),
                    $exception->getMessage()
                ));
                $failedStatusEvents = $this->setFailure($eventIds);
                $waitingEvents = $this->unsetEvents($waitingEvents, $failedStatusEvents);
            }
        }
    }

    /**
     * @param array $eventIds
     * @return array
     */
    private function setFailure(array $eventIds): array
    {
        $maxRetries = (int)$this->config->getValue(self::CONFIG_PATH_MAX_RETRIES);
        return $this->storageWriter->updateFailure($eventIds, $maxRetries);
    }

    /**
     * @param array $events
     * @param array $eventIds
     * @return array
     */
    private function unsetEvents(array $events, array $eventIds): array
    {
        foreach ($eventIds as $eventId) {
            unset($events[$eventId]);
        }
        return $events;
    }
}
