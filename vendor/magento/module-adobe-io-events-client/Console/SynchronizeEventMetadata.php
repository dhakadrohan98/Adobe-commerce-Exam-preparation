<?php

namespace Magento\AdobeIoEventsClient\Console;

use Magento\AdobeIoEventsClient\Api\EventMetadataRegistryInterface;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\IOEventsAPIClient;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SynchronizeEventMetadata extends Command
{
    /**
     * @var EventMetadataRegistryInterface
     */
    private EventMetadataRegistryInterface $eventRegistry;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var IOEventsAPIClient
     */
    private IOEventsAPIClient $IOEventsAPIClient;

    /**
     * @param EventMetadataRegistryInterface $eventRegistry
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param IOEventsAPIClient $IOEventsAPIClient
     */
    public function __construct(
        EventMetadataRegistryInterface $eventRegistry,
        AdobeIOConfigurationProvider $configurationProvider,
        IOEventsAPIClient $IOEventsAPIClient
    ) {
        $this->eventRegistry = $eventRegistry;
        $this->configurationProvider = $configurationProvider;
        $this->IOEventsAPIClient = $IOEventsAPIClient;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("events:sync-events-metadata");
        $this->setDescription("Synchronise event metadata for this instance");

        $this->addOption(
            "delete",
            "d",
            InputOption::VALUE_NONE,
            "Delete events metadata no longer required"
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws InputException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $provider = $this->configurationProvider->retrieveProvider();
        if (is_null($provider)) {
            $output->writeln(
                sprintf(
                    "<error>No event provider is configured, please run bin/magento %s</error>",
                    CreateEventProvider::COMMAND_NAME
                )
            );
            return Cli::RETURN_FAILURE;
        }

        $output->writeln(
            sprintf(
                "Event provider with ID <info>%s</info> retrieved from configuration",
                $provider->getId()
            )
        );

        $output->writeln("<info>The following events are declared on your instance:</info>");
        $declaredEventMetadata = $this->eventRegistry->getDeclaredEventMetadataList();
        foreach ($declaredEventMetadata as $eventMetadata) {
            $output->writeln("- $eventMetadata");
        }

        $registeredEventMetadata = $this->IOEventsAPIClient->listRegisteredEventMetadata($provider);

        $eventTypeToDelete = array_diff($registeredEventMetadata, $declaredEventMetadata);

        $output->writeln("<info>Updating event types:</info>");
        foreach ($declaredEventMetadata as $eventType) {
            $this->IOEventsAPIClient->createEventMetadata($provider, $eventType);
            $output->writeln("- <info>[UPDATED]</info> $eventType");
        }

        if (count($eventTypeToDelete) > 0) {
            if ($input->getOption("delete")) {
                $output->writeln("<info>Delete the following event metedata:</info>");
                foreach ($eventTypeToDelete as $eventType) {
                    $deleted = $this->IOEventsAPIClient->deleteEventMetadata(
                        $provider,
                        $eventType
                    );
                    if ($deleted) {
                        $output->writeln("- <comment>[DELETED]</comment> $eventType");
                    } else {
                        $output->writeln("- <error>[FAILURE]</error> $eventType");
                    }
                }
            } else {
                $output->writeln(
                    "<info>The following event metadata could be deleted, by using --delete option</info>"
                );
                foreach ($eventTypeToDelete as $eventType) {
                    $output->writeln("- $eventType");
                }
            }
        }

        return Cli::RETURN_SUCCESS;
    }
}
